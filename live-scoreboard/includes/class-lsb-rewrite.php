<?php
defined( 'ABSPATH' ) || exit;

class LSB_Rewrite {

    public function __construct() {
        add_action( 'init',              array( $this, 'add_rules' ) );
        add_filter( 'query_vars',        array( $this, 'add_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_template' ) );
    }

    public function add_rules() {
        // Overlay URL must come first (more specific)
        add_rewrite_rule(
            '^scoreboard/overlay/([^/]+)/?$',
            'index.php?lsb_overlay=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^scoreboard/([^/]+)/?$',
            'index.php?lsb_match=$matches[1]',
            'top'
        );
    }

    public function add_vars( $vars ) {
        $vars[] = 'lsb_match';
        $vars[] = 'lsb_overlay';
        return $vars;
    }

    public function handle_template() {
        $overlay_slug = get_query_var( 'lsb_overlay' );
        $match_slug   = get_query_var( 'lsb_match' );

        if ( $overlay_slug ) {
            if ( ! is_user_logged_in() ) {
                wp_safe_redirect( wp_login_url( home_url( '/scoreboard/overlay/' . $overlay_slug ) ) );
                exit;
            }

            $match = LSB_Match::get( $overlay_slug );
            if ( ! $match ) {
                status_header( 404 );
                exit( 'Match not found.' );
            }

            if ( ! LSB_Match::can_edit( $match->id, get_current_user_id() ) ) {
                wp_die( esc_html__( 'You do not have permission to view this overlay.', 'live-scoreboard' ), 403 );
            }

            set_query_var( 'lsb_match_data', $match );
            include LSB_DIR . 'templates/overlay.php';
            exit;
        }

        if ( $match_slug ) {
            $match = LSB_Match::get( $match_slug );
            if ( ! $match ) {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
                return;
            }
            $theme_tpl = locate_template( 'scoreboard-single.php' );
            $tpl       = $theme_tpl ?: LSB_DIR . 'templates/scoreboard-single.php';
            set_query_var( 'lsb_match_data', $match );
            include $tpl;
            exit;
        }
    }
}
