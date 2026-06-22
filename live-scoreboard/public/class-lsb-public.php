<?php
defined( 'ABSPATH' ) || exit;

class LSB_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action( 'user_register', array( $this, 'assign_match_manager_role' ), 30 );
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
            'canManage'    => LSB_Membership::can_manage(),
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
        if ( $post && has_shortcode( $post->post_content, 'match_manager_subscription' ) ) return true;
        return false;
    }

    public function assign_match_manager_role( $user_id ) {
        if ( ! LSB_Membership::expires_timestamp( $user_id ) && ! LSB_Membership::has_manual_access( $user_id ) ) {
            LSB_Membership::setup_new_user( $user_id );
        }
    }
}
