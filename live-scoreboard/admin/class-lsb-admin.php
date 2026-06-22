<?php
defined( 'ABSPATH' ) || exit;

class LSB_Admin {

    public function __construct() {
        add_action( 'admin_init',            array( $this, 'handle_membership_forms' ) );
        add_action( 'admin_menu',            array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    public function add_menu() {
        add_menu_page(
            __( 'Live Scoreboard', 'live-scoreboard' ),
            __( 'Scoreboard', 'live-scoreboard' ),
            'manage_options',
            'lsb-admin',
            array( $this, 'render_page' ),
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'lsb-admin',
            __( 'Subscriptions', 'live-scoreboard' ),
            __( 'Subscriptions', 'live-scoreboard' ),
            'manage_options',
            'lsb-membership',
            array( $this, 'render_membership_page' )
        );

    }

    public function enqueue( $hook ) {
        if ( 'toplevel_page_lsb-admin' !== $hook && false === strpos( $hook, 'lsb-membership' ) ) return;
        wp_enqueue_style(  'lsb-admin', LSB_URL . 'assets/css/admin.css',  array(), LSB_VERSION );
        wp_enqueue_script( 'lsb-admin', LSB_URL . 'assets/js/admin.js', array( 'jquery' ), LSB_VERSION, true );
        wp_localize_script( 'lsb-admin', 'lsbAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'lsb_nonce' ),
        ) );
    }

    public function render_page() {
        global $wpdb;
        $matches = $wpdb->get_results(
            "SELECT m.*, u.user_login
             FROM {$wpdb->prefix}lsb_matches m
             LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id
             ORDER BY m.created_at DESC
             LIMIT 100"
        );
        include LSB_DIR . 'admin/views/admin-page.php';
    }

    public function handle_membership_forms() {
        if ( empty( $_POST['lsb_membership_action'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = sanitize_key( wp_unslash( $_POST['lsb_membership_action'] ) );

        if ( 'save_packages' === $action || 'save_settings' === $action ) {
            check_admin_referer( 'lsb_membership_settings' );
            LSB_Membership::save_packages( $_POST );
            wp_safe_redirect( add_query_arg( array( 'page' => 'lsb-membership', 'updated' => 'packages' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        if ( 'update_member' === $action ) {
            check_admin_referer( 'lsb_membership_member' );

            $user_id       = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
            $member_action = isset( $_POST['member_action'] ) ? sanitize_key( wp_unslash( $_POST['member_action'] ) ) : '';

            if ( $user_id && ! user_can( $user_id, 'manage_options' ) ) {
                if ( 'package' === $member_action ) {
                    $package_id = isset( $_POST['package_id'] ) ? sanitize_key( wp_unslash( $_POST['package_id'] ) ) : '';
                    LSB_Membership::grant_package( $user_id, $package_id );
                } elseif ( 'custom_expiry' === $member_action ) {
                    $expiry_date = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
                    $plan_name   = isset( $_POST['plan_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_name'] ) ) : __( 'Custom subscription', 'live-scoreboard' );
                    LSB_Membership::set_expiry_date( $user_id, $expiry_date, $plan_name );
                } elseif ( 'manual' === $member_action ) {
                    LSB_Membership::set_manual_access( $user_id, true );
                } elseif ( 'clear' === $member_action ) {
                    LSB_Membership::clear_access( $user_id );
                }
            }

            wp_safe_redirect( add_query_arg( array( 'page' => 'lsb-membership', 'updated' => 'user' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        if ( 'grant_user' === $action || 'revoke_manual' === $action ) {
            $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
            if ( $user_id ) {
                if ( 'grant_user' === $action ) {
                    check_admin_referer( 'lsb_membership_grant' );
                    $days    = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 0;
                    $package = isset( $_POST['package_id'] ) ? sanitize_key( wp_unslash( $_POST['package_id'] ) ) : '';
                    $manual  = ! empty( $_POST['manual_access'] );

                    if ( $manual ) {
                        LSB_Membership::set_manual_access( $user_id, true );
                    } elseif ( $package && LSB_Membership::grant_package( $user_id, $package ) ) {
                        // Package granted.
                    } elseif ( $days ) {
                        LSB_Membership::grant_subscription( $user_id, $days );
                    }
                } else {
                    check_admin_referer( 'lsb_membership_revoke' );
                    LSB_Membership::set_manual_access( $user_id, false );
                }
            }

            wp_safe_redirect( add_query_arg( array( 'page' => 'lsb-membership', 'updated' => 'user' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    public function render_membership_page() {
        $settings = LSB_Membership::settings();
        $packages = LSB_Membership::packages();
        $users    = get_users( array(
            'role__not_in' => array( 'administrator' ),
            'number'       => 100,
            'orderby'      => 'registered',
            'order'        => 'DESC',
        ) );

        include LSB_DIR . 'admin/views/membership-page.php';
    }
}
