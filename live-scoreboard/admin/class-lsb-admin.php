<?php
defined( 'ABSPATH' ) || exit;

class LSB_Admin {

    public function __construct() {
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
    }

    public function enqueue( $hook ) {
        if ( 'toplevel_page_lsb-admin' !== $hook ) return;
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
}
