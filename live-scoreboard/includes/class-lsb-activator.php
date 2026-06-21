<?php
defined( 'ABSPATH' ) || exit;

class LSB_Activator {

    public static function activate() {
        LSB_DB::create_tables();
        update_option( 'lsb_version', LSB_VERSION );
        // Give all existing users the scorer capability
        self::add_scorer_role();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    private static function add_scorer_role() {
        add_role(
            'scorer',
            __( 'Scorer', 'live-scoreboard' ),
            array(
                'read'          => true,
                'lsb_manage'    => true,
            )
        );
        // Also grant admins and editors the capability
        foreach ( array( 'administrator', 'editor' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->add_cap( 'lsb_manage' );
            }
        }
    }
}
