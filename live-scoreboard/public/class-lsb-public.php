<?php
defined( 'ABSPATH' ) || exit;

class LSB_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
        // Grant scorer cap to any newly registered user
        add_action( 'user_register', array( $this, 'assign_scorer_cap' ) );
    }

    public function enqueue() {
        // Only load on pages that have a board
        if ( ! $this->page_has_board() ) return;

        wp_enqueue_style(
            'lsb-public',
            LSB_URL . 'assets/css/scoreboard.css',
            array(),
            LSB_VERSION
        );

        wp_enqueue_script(
            'lsb-public',
            LSB_URL . 'assets/js/scoreboard.js',
            array( 'jquery' ),
            LSB_VERSION,
            true
        );

        wp_localize_script( 'lsb-public', 'lsbConfig', array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'siteUrl'      => home_url(),
            'nonce'        => wp_create_nonce( 'lsb_nonce' ),
            'pollInterval' => 3000,   // ms between polls
            'isLoggedIn'   => is_user_logged_in(),
            'canManage'    => current_user_can( 'lsb_manage' ),
            'i18n'         => array(
                'live'     => __( 'LIVE', 'live-scoreboard' ),
                'upcoming' => __( 'Upcoming', 'live-scoreboard' ),
                'finished' => __( 'Full Time', 'live-scoreboard' ),
                'loading'  => __( 'Loading…', 'live-scoreboard' ),
            ),
        ) );
    }

    private function page_has_board() {
        global $post;

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( preg_match( '#/scoreboard/[^/]+/?#', $request_uri ) ) return true;

        if ( get_query_var( 'lsb_match' ) ) return true;
        if ( $post && has_shortcode( $post->post_content, 'scoreboard' ) )        return true;
        if ( $post && has_shortcode( $post->post_content, 'scoreboard_list' ) )   return true;
        if ( $post && has_shortcode( $post->post_content, 'live_scores' ) )      return true;
        if ( $post && has_shortcode( $post->post_content, 'scorer_dashboard' ) )  return true;
        return false;
    }

    public function assign_scorer_cap( $user_id ) {
        $user = new WP_User( $user_id );
        $user->add_cap( 'lsb_manage' );
    }
}
