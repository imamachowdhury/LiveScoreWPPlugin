<?php
defined( 'ABSPATH' ) || exit;

class LSB_Shortcode {

    public function __construct() {
        add_shortcode( 'scoreboard',       array( $this, 'render_single' ) );
        add_shortcode( 'scoreboard_list',  array( $this, 'render_list' ) );
        add_shortcode( 'scorer_dashboard', array( $this, 'render_dashboard' ) );
    }

    // [scoreboard id="123"]  or  [scoreboard slug="my-match"]
    public function render_single( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0, 'slug' => '' ), $atts );
        $key  = $atts['slug'] ?: (int) $atts['id'];
        if ( ! $key ) return '';

        $match = LSB_Match::get( $key );
        if ( ! $match ) return '<p>' . esc_html__( 'Match not found.', 'live-scoreboard' ) . '</p>';

        ob_start();
        lsb_render_board( $match );
        return ob_get_clean();
    }

    // [scoreboard_list sport="football"]  — live-polling homepage widget
    public function render_list( $atts ) {
        $atts  = shortcode_atts( array( 'sport' => '', 'title' => '' ), $atts );
        $sport = sanitize_text_field( $atts['sport'] );
        $title = sanitize_text_field( $atts['title'] );

        ob_start();
        echo '<div class="lsb-live-widget" data-sport="' . esc_attr( $sport ) . '">';
        if ( $title ) {
            echo '<div class="lsb-widget-header">';
            echo '<span class="lsb-widget-dot"></span>';
            echo '<span class="lsb-widget-title">' . esc_html( $title ) . '</span>';
            echo '</div>';
        }
        echo '<div class="lsb-widget-cards" id="lsb-live-cards-' . esc_attr( $sport ?: 'all' ) . '">';
        echo '<div class="lsb-widget-loading">' . esc_html__( 'Loading live matches…', 'live-scoreboard' ) . '</div>';
        echo '</div>';
        echo '</div>';
        return ob_get_clean();
    }

    // [scorer_dashboard]  — logged-in user match management
    public function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        if ( ! current_user_can( 'lsb_manage' ) ) {
            return '<p>' . esc_html__( 'Your account does not have scorer access.', 'live-scoreboard' ) . '</p>';
        }

        ob_start();
        include LSB_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }
}

// Render a board — score is built server-side so it shows instantly, JS polling updates it live
function lsb_render_board( $match ) {
    $sport  = $match->sport;
    $id     = (int) $match->id;

    // Fetch current score from DB right now
    if ( $sport === 'football' ) {
        $score = LSB_Match::get_football_score( $id );
    } else {
        $score = LSB_Match::get_cricket_score( $id );
    }

    echo '<div class="lsb-board lsb-board--' . esc_attr( $sport ) . '" data-match-id="' . $id . '" data-sport="' . esc_attr( $sport ) . '">';
    echo lsb_build_board_html( $match, $score );
    echo '</div>';
}

// Build the inner HTML for a board — used by both PHP and replicated in JS
function lsb_build_board_html( $match, $score ) {
    $sport  = $match->sport;
    $status = $match->status;

    if ( $status === 'live' ) {
        $badge = '<span class="lsb-badge lsb-badge--live">&#9679; ' . esc_html__( 'LIVE', 'live-scoreboard' ) . '</span>';
    } elseif ( $status === 'finished' ) {
        $badge = '<span class="lsb-badge lsb-badge--finished">' . esc_html__( 'Full Time', 'live-scoreboard' ) . '</span>';
    } else {
        $badge = '<span class="lsb-badge lsb-badge--upcoming">' . esc_html__( 'Upcoming', 'live-scoreboard' ) . '</span>';
    }

    $html  = '<div class="lsb-inner lsb-inner--' . esc_attr( $sport ) . '">';
    $html .= '<div class="lsb-header">';
    $html .= '<span class="lsb-title">' . esc_html( $match->title ) . '</span>';
    $html .= $badge;
    $html .= '</div>';

    if ( $sport === 'football' ) {
        $sa  = $score ? (int) $score->score_a : 0;
        $sb  = $score ? (int) $score->score_b : 0;
        $min = $score ? (int) $score->minute   : 0;
        $half = $score ? esc_html( $score->half ) : '';

        $html .= '<div class="lsb-football">';
        $html .= '<div class="lsb-teams">';
        $html .= '<div class="lsb-team"><span class="lsb-team-name">' . esc_html( $match->team_a ) . '</span><span class="lsb-goal">' . $sa . '</span></div>';
        $html .= '<div class="lsb-separator">';
        if ( $status === 'live' ) {
            $html .= '<span class="lsb-minute">' . $min . '\'</span><span class="lsb-half">' . $half . '</span>';
        } else {
            $html .= '<span class="lsb-vs">VS</span>';
        }
        $html .= '</div>';
        $html .= '<div class="lsb-team"><span class="lsb-team-name">' . esc_html( $match->team_b ) . '</span><span class="lsb-goal">' . $sb . '</span></div>';
        $html .= '</div>';
        if ( $score && $score->events ) {
            $html .= '<div class="lsb-events">' . esc_html( $score->events ) . '</div>';
        }
        $html .= '</div>';

    } else {
        // Cricket
        $ar = $score ? (int) $score->score_a_runs  : 0;
        $aw = $score ? (int) $score->score_a_wkts  : 0;
        $ao = $score ? (float) $score->score_a_overs : 0;
        $br = $score ? (int) $score->score_b_runs  : 0;
        $bw = $score ? (int) $score->score_b_wkts  : 0;
        $bo = $score ? (float) $score->score_b_overs : 0;
        $target  = $score ? (int) $score->target       : 0;
        $result  = $score ? $score->result_text        : '';
        $batting = $score ? (int) $score->batting_team : 0;
        $innings = $score ? (int) $score->innings      : 1;

        $html .= '<div class="lsb-cricket">';
        $html .= '<div class="lsb-cricket-innings">';
        $html .= '<div class="lsb-cricket-team"><span class="lsb-team-name">' . esc_html( $match->team_a ) . '</span><span class="lsb-runs">' . $ar . '/' . $aw . '</span><span class="lsb-overs">(' . $ao . ' ov)</span></div>';
        $html .= '<div class="lsb-cricket-divider">vs</div>';
        $html .= '<div class="lsb-cricket-team"><span class="lsb-team-name">' . esc_html( $match->team_b ) . '</span><span class="lsb-runs">' . $br . '/' . $bw . '</span><span class="lsb-overs">(' . $bo . ' ov)</span></div>';
        $html .= '</div>';
        if ( $target ) {
            $html .= '<div class="lsb-target">Target: ' . $target . '</div>';
        }
        if ( $result ) {
            $html .= '<div class="lsb-result">' . esc_html( $result ) . '</div>';
        }
        if ( $batting ) {
            $batting_name = ( $batting === 1 ) ? $match->team_a : $match->team_b;
            $html .= '<div class="lsb-batting">Batting: ' . esc_html( $batting_name ) . ' &mdash; Innings ' . $innings . '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
