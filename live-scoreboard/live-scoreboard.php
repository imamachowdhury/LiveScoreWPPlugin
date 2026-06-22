<?php
/**
 * Plugin Name: Live Scoreboard
 * Plugin URI:  https://imamahmed.net
 * Description: Football & cricket live scoreboard with Match Manager membership access.
 * Version:     1.2.3
 * Author:      Imam Ahmed Chowdhury
 * Author URI:  https://imamahmed.net
 * Text Domain: live-scoreboard
 * License:     GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

define( 'LSB_VERSION',  '1.2.3' );
define( 'LSB_FILE',     __FILE__ );
define( 'LSB_DIR',      plugin_dir_path( __FILE__ ) );
define( 'LSB_URL',      plugin_dir_url( __FILE__ ) );

require_once LSB_DIR . 'includes/class-lsb-activator.php';
require_once LSB_DIR . 'includes/class-lsb-db.php';
require_once LSB_DIR . 'includes/class-lsb-membership.php';
require_once LSB_DIR . 'includes/class-lsb-match.php';
require_once LSB_DIR . 'includes/class-lsb-ajax.php';
require_once LSB_DIR . 'includes/class-lsb-rewrite.php';
require_once LSB_DIR . 'includes/class-lsb-shortcode.php';
require_once LSB_DIR . 'public/class-lsb-public.php';
require_once LSB_DIR . 'admin/class-lsb-admin.php';

register_activation_hook( __FILE__,   array( 'LSB_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LSB_Activator', 'deactivate' ) );

add_action( 'plugins_loaded', 'lsb_init' );
function lsb_init() {
    if ( get_option( 'lsb_version' ) !== LSB_VERSION ) {
        LSB_DB::create_tables();
        LSB_Membership::add_role_and_caps();
        update_option( 'lsb_version', LSB_VERSION );
    }

    LSB_Membership::init();
    new LSB_Ajax();
    new LSB_Rewrite();
    new LSB_Shortcode();
    new LSB_Public();
    if ( is_admin() ) {
        new LSB_Admin();
    }
}
