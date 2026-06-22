<?php
defined( 'ABSPATH' ) || exit;

class LSB_Ajax {

    public function __construct() {
        $actions = array(
            'lsb_create_match'          => array( 'cb' => 'create_match',          'priv' => true ),
            'lsb_update_football'       => array( 'cb' => 'update_football',       'priv' => true ),
            'lsb_update_cricket'        => array( 'cb' => 'update_cricket',        'priv' => true ),
            'lsb_end_match'             => array( 'cb' => 'end_match',             'priv' => true ),
            'lsb_delete_match'          => array( 'cb' => 'delete_match',          'priv' => true ),
            'lsb_get_score'             => array( 'cb' => 'get_score',             'priv' => false ),
            'lsb_get_live_scores'       => array( 'cb' => 'get_live_scores',       'priv' => false ),
        );

        foreach ( $actions as $action => $cfg ) {
            add_action( "wp_ajax_{$action}", array( $this, $cfg['cb'] ) );
            if ( ! $cfg['priv'] ) {
                add_action( "wp_ajax_nopriv_{$action}", array( $this, $cfg['cb'] ) );
            }
        }
    }

    // ── Create match ──────────────────────────────────────────────────────
    public function create_match() {
        $this->verify_nonce( 'lsb_nonce' );
        $this->require_cap();

        $required = array( 'sport', 'title', 'team_a', 'team_b' );
        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array( 'message' => sprintf( __( 'Field %s is required.', 'live-scoreboard' ), $field ) ) );
            }
        }

        $result = LSB_Match::create( $_POST, get_current_user_id() );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $match = LSB_Match::get( $result );
        wp_send_json_success( array(
            'match_id' => $result,
            'slug'     => $match->slug,
            'url'      => home_url( '/scoreboard/' . $match->slug ),
        ) );
    }

    // ── Update football ────────────────────────────────────────────────────
    public function update_football() {
        $this->verify_nonce( 'lsb_nonce' );
        $this->require_cap();

        $match_id = absint( $_POST['match_id'] ?? 0 );
        if ( ! $match_id || ! LSB_Match::can_edit( $match_id, get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'live-scoreboard' ) ) );
        }

        global $wpdb;
        LSB_Match::update_football( $match_id, $_POST );

        if ( $wpdb->last_error ) {
            wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
        }

        wp_send_json_success( $this->build_score_payload( $match_id ) );
    }

    // ── Update cricket ─────────────────────────────────────────────────────
    public function update_cricket() {
        $this->verify_nonce( 'lsb_nonce' );
        $this->require_cap();

        $match_id = absint( $_POST['match_id'] ?? 0 );
        if ( ! $match_id || ! LSB_Match::can_edit( $match_id, get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'live-scoreboard' ) ) );
        }

        global $wpdb;
        LSB_Match::update_cricket( $match_id, $_POST );

        if ( $wpdb->last_error ) {
            wp_send_json_error( array( 'message' => 'DB error: ' . $wpdb->last_error ) );
        }

        wp_send_json_success( $this->build_score_payload( $match_id ) );
    }

    // ── End match ─────────────────────────────────────────────────────────
    public function end_match() {
        $this->verify_nonce( 'lsb_nonce' );
        $this->require_cap();

        $match_id = absint( $_POST['match_id'] ?? 0 );
        if ( ! $match_id || ! LSB_Match::can_edit( $match_id, get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'live-scoreboard' ) ) );
        }

        LSB_Match::update_status( $match_id, 'finished' );
        wp_send_json_success( $this->build_score_payload( $match_id ) );
    }

    // ── Get all live scores (homepage polling) ─────────────────────────────
    public function get_live_scores() {
        nocache_headers();

        $sport   = sanitize_text_field( $_GET['sport'] ?? '' );
        $matches = LSB_Match::get_live( $sport );
        $payload = array();
        foreach ( $matches as $match ) {
            $payload[] = $this->build_score_payload( $match->id );
        }
        wp_send_json_success( $payload );
    }

    // ── Delete match ───────────────────────────────────────────────────────
    public function delete_match() {
        $this->verify_nonce( 'lsb_nonce' );
        $this->require_cap();

        $match_id = absint( $_POST['match_id'] ?? 0 );
        if ( ! $match_id || ! LSB_Match::can_edit( $match_id, get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'live-scoreboard' ) ) );
        }

        LSB_Match::delete( $match_id );
        wp_send_json_success();
    }

    // ── Get score (public polling) ─────────────────────────────────────────
    public function get_score() {
        nocache_headers();

        $match_id = absint( $_GET['match_id'] ?? 0 );
        $slug     = sanitize_text_field( $_GET['slug'] ?? '' );
        $key      = $match_id ?: $slug;

        if ( ! $key ) {
            wp_send_json_error( array( 'message' => 'Missing match id or slug.' ) );
        }

        $payload = $this->build_score_payload( $key );
        if ( ! $payload ) {
            wp_send_json_error( array( 'message' => 'Match not found.' ) );
        }

        wp_send_json_success( $payload );
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function build_score_payload( $id_or_slug ) {
        $match = LSB_Match::get( $id_or_slug );
        if ( ! $match ) return null;

        $payload = array(
            'match_id' => (int) $match->id,
            'slug'     => $match->slug,
            'sport'    => $match->sport,
            'title'    => $match->title,
            'team_a'   => $match->team_a,
            'team_b'   => $match->team_b,
            'status'   => $match->status,
        );

        if ( $match->sport === 'football' ) {
            $score = LSB_Match::get_football_score( $match->id );
            $payload['score'] = $score ? (array) $score : array();
        } else {
            $score = LSB_Match::get_cricket_score( $match->id );
            $payload['score'] = $score ? (array) $score : array();
        }

        return $payload;
    }

    private function verify_nonce( $action ) {
        if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'live-scoreboard' ) ) );
        }
    }

    private function require_cap() {
        if ( ! is_user_logged_in() || ! LSB_Membership::can_manage() ) {
            wp_send_json_error( array( 'message' => LSB_Membership::access_required_message() ) );
        }
    }
}
