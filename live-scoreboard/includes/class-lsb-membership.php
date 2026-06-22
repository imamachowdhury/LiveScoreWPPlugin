<?php
defined( 'ABSPATH' ) || exit;

class LSB_Membership {

    const ROLE               = 'match_manager';
    const CAP                = 'lsb_manage';
    const META_EXPIRES       = 'lsb_subscription_expires';
    const META_MANUAL_ACCESS = 'lsb_manual_access';
    const META_PLAN_NAME     = 'lsb_subscription_plan_name';
    const OPTION_SETTINGS    = 'lsb_membership_settings';
    const OPTION_PACKAGES    = 'lsb_membership_packages';

    public static function init() {
        add_action( 'user_register', array( __CLASS__, 'setup_new_user' ), 20 );
        add_action( 'set_user_role', array( __CLASS__, 'upgrade_legacy_scorer_role' ), 20, 3 );
    }

    public static function add_role_and_caps() {
        add_role(
            self::ROLE,
            __( 'Match Manager', 'live-scoreboard' ),
            array(
                'read'       => true,
                self::CAP    => true,
            )
        );

        $match_manager = get_role( self::ROLE );
        if ( $match_manager ) {
            $match_manager->add_cap( self::CAP );
        }

        foreach ( array( 'administrator', 'editor' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->add_cap( self::CAP );
            }
        }
    }

    public static function setup_new_user( $user_id ) {
        $user = new WP_User( $user_id );
        $user->set_role( self::ROLE );
        $user->add_cap( self::CAP );

        update_user_meta( $user_id, self::META_MANUAL_ACCESS, 'no' );
        update_user_meta( $user_id, self::META_EXPIRES, '' );
        update_user_meta( $user_id, self::META_PLAN_NAME, '' );
    }

    public static function upgrade_legacy_scorer_role( $user_id, $role, $old_roles ) {
        static $upgrading = false;

        if ( $upgrading || ( self::ROLE !== $role && 'scorer' !== $role ) ) {
            return;
        }

        if ( 'scorer' === $role && ! user_can( $user_id, 'manage_options' ) ) {
            $upgrading = true;
            $user      = new WP_User( $user_id );
            $user->set_role( self::ROLE );
            $user->add_cap( self::CAP );
            $upgrading = false;
        }
    }

    public static function settings() {
        $defaults = array(
            'plan_name'    => __( 'Match Manager Subscription', 'live-scoreboard' ),
            'price_label'  => '',
            'default_days' => 0,
            'description'  => __( 'Create matches, update live scores, and manage scoreboard links while your subscription is active.', 'live-scoreboard' ),
        );

        $settings = get_option( self::OPTION_SETTINGS, array() );
        return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
    }

    public static function packages() {
        $packages = get_option( self::OPTION_PACKAGES, array() );

        if ( ! is_array( $packages ) || ! $packages ) {
            $settings = self::settings();
            $packages = array(
                array(
                    'id'          => 'default',
                    'name'        => $settings['plan_name'],
                    'days'        => absint( $settings['default_days'] ) ?: 30,
                    'price_label' => $settings['price_label'],
                    'description' => $settings['description'],
                    'active'      => 'yes',
                ),
            );
        }

        return array_values( array_filter( array_map( array( __CLASS__, 'sanitize_package' ), $packages ) ) );
    }

    public static function active_packages() {
        return array_values( array_filter( self::packages(), function ( $package ) {
            return ! empty( $package['active'] ) && 'yes' === $package['active'];
        } ) );
    }

    public static function save_packages( $data ) {
        $names        = isset( $data['package_name'] ) && is_array( $data['package_name'] ) ? wp_unslash( $data['package_name'] ) : array();
        $days         = isset( $data['package_days'] ) && is_array( $data['package_days'] ) ? wp_unslash( $data['package_days'] ) : array();
        $prices       = isset( $data['package_price'] ) && is_array( $data['package_price'] ) ? wp_unslash( $data['package_price'] ) : array();
        $descriptions = isset( $data['package_description'] ) && is_array( $data['package_description'] ) ? wp_unslash( $data['package_description'] ) : array();
        $active       = isset( $data['package_active'] ) && is_array( $data['package_active'] ) ? wp_unslash( $data['package_active'] ) : array();
        $packages     = array();

        foreach ( $names as $index => $name ) {
            $name = sanitize_text_field( $name );
            if ( '' === $name ) {
                continue;
            }

            $packages[] = self::sanitize_package( array(
                'id'          => sanitize_title( $name . '-' . $index ),
                'name'        => $name,
                'days'        => isset( $days[ $index ] ) ? absint( $days[ $index ] ) : 0,
                'price_label' => isset( $prices[ $index ] ) ? sanitize_text_field( $prices[ $index ] ) : '',
                'description' => isset( $descriptions[ $index ] ) ? sanitize_textarea_field( $descriptions[ $index ] ) : '',
                'active'      => isset( $active[ $index ] ) ? 'yes' : 'no',
            ) );
        }

        update_option( self::OPTION_PACKAGES, $packages );
    }

    public static function package_by_id( $package_id ) {
        foreach ( self::packages() as $package ) {
            if ( $package['id'] === $package_id ) {
                return $package;
            }
        }
        return null;
    }

    public static function save_settings( $data ) {
        $settings = array(
            'plan_name'    => isset( $data['plan_name'] ) ? sanitize_text_field( wp_unslash( $data['plan_name'] ) ) : '',
            'price_label'  => isset( $data['price_label'] ) ? sanitize_text_field( wp_unslash( $data['price_label'] ) ) : '',
            'default_days' => isset( $data['default_days'] ) ? absint( $data['default_days'] ) : 0,
            'description'  => isset( $data['description'] ) ? sanitize_textarea_field( wp_unslash( $data['description'] ) ) : '',
        );

        update_option( self::OPTION_SETTINGS, wp_parse_args( $settings, self::settings() ) );
    }

    public static function grant_subscription( $user_id, $days, $plan_name = '' ) {
        $days    = max( 1, absint( $days ) );
        $current = self::expires_timestamp( $user_id );
        $base    = $current && $current > current_time( 'timestamp' ) ? $current : current_time( 'timestamp' );
        $expires = gmdate( 'Y-m-d H:i:s', $base + ( DAY_IN_SECONDS * $days ) );

        update_user_meta( $user_id, self::META_EXPIRES, $expires );
        update_user_meta( $user_id, self::META_PLAN_NAME, $plan_name ? sanitize_text_field( $plan_name ) : self::settings()['plan_name'] );
        update_user_meta( $user_id, self::META_MANUAL_ACCESS, 'no' );

        $user = new WP_User( $user_id );
        $user->set_role( self::ROLE );
        $user->add_cap( self::CAP );
    }

    public static function grant_package( $user_id, $package_id ) {
        $package = self::package_by_id( $package_id );
        if ( ! $package ) {
            return false;
        }

        self::grant_subscription( $user_id, absint( $package['days'] ), $package['name'] );
        return true;
    }

    public static function set_expiry_date( $user_id, $date, $plan_name = '' ) {
        $date = sanitize_text_field( $date );

        if ( '' === $date ) {
            self::clear_access( $user_id );
            return true;
        }

        $timestamp = strtotime( $date . ' 23:59:59 UTC' );
        if ( ! $timestamp ) {
            return false;
        }

        update_user_meta( $user_id, self::META_EXPIRES, gmdate( 'Y-m-d H:i:s', $timestamp ) );
        update_user_meta( $user_id, self::META_PLAN_NAME, sanitize_text_field( $plan_name ) );
        update_user_meta( $user_id, self::META_MANUAL_ACCESS, 'no' );

        $user = new WP_User( $user_id );
        $user->set_role( self::ROLE );
        $user->add_cap( self::CAP );

        return true;
    }

    public static function set_manual_access( $user_id, $enabled ) {
        update_user_meta( $user_id, self::META_MANUAL_ACCESS, $enabled ? 'yes' : 'no' );

        if ( $enabled ) {
            $user = new WP_User( $user_id );
            $user->set_role( self::ROLE );
            $user->add_cap( self::CAP );
        }
    }

    public static function clear_access( $user_id ) {
        update_user_meta( $user_id, self::META_EXPIRES, '' );
        update_user_meta( $user_id, self::META_PLAN_NAME, '' );
        update_user_meta( $user_id, self::META_MANUAL_ACCESS, 'no' );
    }

    public static function expires_timestamp( $user_id ) {
        $expires = get_user_meta( $user_id, self::META_EXPIRES, true );
        if ( ! $expires ) {
            return 0;
        }

        $timestamp = strtotime( $expires . ' UTC' );
        return $timestamp ? $timestamp : 0;
    }

    public static function has_manual_access( $user_id ) {
        return 'yes' === get_user_meta( $user_id, self::META_MANUAL_ACCESS, true );
    }

    public static function has_active_subscription( $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();

        if ( ! $user_id ) {
            return false;
        }

        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        if ( self::has_manual_access( $user_id ) ) {
            return true;
        }

        $expires = self::expires_timestamp( $user_id );
        return $expires && $expires >= current_time( 'timestamp' );
    }

    public static function can_manage( $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        return $user_id && user_can( $user_id, self::CAP ) && self::has_active_subscription( $user_id );
    }

    public static function status_label( $user_id ) {
        if ( self::has_manual_access( $user_id ) ) {
            return __( 'Manual access', 'live-scoreboard' );
        }

        $expires = self::expires_timestamp( $user_id );
        if ( ! $expires ) {
            return __( 'No active subscription', 'live-scoreboard' );
        }

        if ( $expires < current_time( 'timestamp' ) ) {
            return sprintf(
                /* translators: %s: expiry date */
                __( 'Expired on %s', 'live-scoreboard' ),
                wp_date( get_option( 'date_format' ), $expires )
            );
        }

        return sprintf(
            /* translators: %s: expiry date */
            __( 'Active until %s', 'live-scoreboard' ),
            wp_date( get_option( 'date_format' ), $expires )
        );
    }

    public static function status_key( $user_id ) {
        if ( self::has_manual_access( $user_id ) ) {
            return 'manual';
        }

        $expires = self::expires_timestamp( $user_id );
        if ( ! $expires ) {
            return 'none';
        }

        return $expires < current_time( 'timestamp' ) ? 'expired' : 'active';
    }

    public static function expiry_date_value( $user_id ) {
        $expires = self::expires_timestamp( $user_id );
        return $expires ? wp_date( 'Y-m-d', $expires ) : '';
    }

    public static function current_plan( $user_id ) {
        return sanitize_text_field( get_user_meta( $user_id, self::META_PLAN_NAME, true ) );
    }

    public static function access_required_message() {
        return __( 'Contact admin to activate subscription.', 'live-scoreboard' );
    }

    private static function sanitize_package( $package ) {
        if ( ! is_array( $package ) ) {
            return null;
        }

        $name = isset( $package['name'] ) ? sanitize_text_field( $package['name'] ) : '';
        if ( '' === $name ) {
            return null;
        }

        return array(
            'id'          => ! empty( $package['id'] ) ? sanitize_key( $package['id'] ) : sanitize_key( sanitize_title( $name ) ),
            'name'        => $name,
            'days'        => isset( $package['days'] ) ? absint( $package['days'] ) : 0,
            'price_label' => isset( $package['price_label'] ) ? sanitize_text_field( $package['price_label'] ) : '',
            'description' => isset( $package['description'] ) ? sanitize_textarea_field( $package['description'] ) : '',
            'active'      => ! empty( $package['active'] ) && 'no' !== $package['active'] ? 'yes' : 'no',
        );
    }
}
