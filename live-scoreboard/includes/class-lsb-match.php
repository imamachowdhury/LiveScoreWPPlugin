<?php
defined( 'ABSPATH' ) || exit;

class LSB_Match {

    // ── Create ────────────────────────────────────────────────────────────
    public static function create( $data, $user_id ) {
        global $wpdb;

        $sport  = in_array( $data['sport'], array( 'football', 'cricket' ), true ) ? $data['sport'] : 'football';
        $title  = sanitize_text_field( $data['title'] );
        $team_a = sanitize_text_field( $data['team_a'] );
        $team_b = sanitize_text_field( $data['team_b'] );
        $slug   = self::unique_slug( $title );

        $wpdb->insert(
            $wpdb->prefix . 'lsb_matches',
            compact( 'user_id', 'sport', 'title', 'slug', 'team_a', 'team_b' ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        $match_id = (int) $wpdb->insert_id;
        if ( ! $match_id ) {
            return new WP_Error( 'db_error', __( 'Could not create match.', 'live-scoreboard' ) );
        }

        // Seed sport-specific score row
        if ( $sport === 'football' ) {
            $wpdb->insert( $wpdb->prefix . 'lsb_football_score', array( 'match_id' => $match_id ), array( '%d' ) );
            self::save_score_backup( 'football', $match_id, self::default_football_score( $match_id ) );
        } else {
            $wpdb->insert( $wpdb->prefix . 'lsb_cricket_score', array( 'match_id' => $match_id ), array( '%d' ) );
            self::save_score_backup( 'cricket', $match_id, self::default_cricket_score( $match_id ) );
        }

        return $match_id;
    }

    // ── Get single ────────────────────────────────────────────────────────
    public static function get( $id_or_slug ) {
        global $wpdb;
        $field = is_numeric( $id_or_slug ) ? 'id' : 'slug';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lsb_matches WHERE $field = %s",
                $id_or_slug
            )
        );
    }

    // ── List for user ──────────────────────────────────────────────────────
    public static function get_for_user( $user_id, $limit = 20 ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lsb_matches WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
                $user_id, $limit
            )
        );
    }

    // ── All live ──────────────────────────────────────────────────────────
    public static function get_live( $sport = '' ) {
        global $wpdb;
        $where = $sport ? $wpdb->prepare( "AND sport = %s", $sport ) : '';
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}lsb_matches WHERE status = 'live' $where ORDER BY updated_at DESC"
        );
    }

    // ── Football score ─────────────────────────────────────────────────────
    public static function get_football_score( $match_id ) {
        global $wpdb;
        $backup = self::get_score_backup( 'football', $match_id );
        if ( $backup ) {
            return $backup;
        }

        $score = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lsb_football_score WHERE match_id = %d ORDER BY id DESC LIMIT 1",
                $match_id
            )
        );
        return $score;
    }

    public static function update_football( $match_id, $data ) {
        global $wpdb;

        $score_a = isset( $data['score_a'] ) ? absint( $data['score_a'] ) : 0;
        $score_b = isset( $data['score_b'] ) ? absint( $data['score_b'] ) : 0;
        $minute  = isset( $data['minute']  ) ? absint( $data['minute']  ) : 0;
        $half    = isset( $data['half']    ) ? sanitize_text_field( $data['half'] ) : '1st';
        $events  = isset( $data['events']  ) ? sanitize_text_field( $data['events'] ) : '';

        $table = $wpdb->prefix . 'lsb_football_score';
        $row   = array(
            'score_a' => $score_a,
            'score_b' => $score_b,
            'minute'  => $minute,
            'half'    => $half,
            'events'  => $events,
        );
        self::save_score_backup( 'football', $match_id, array_merge( array( 'match_id' => $match_id ), $row ) );

        if ( self::score_row_exists( $table, $match_id ) ) {
            $wpdb->update(
                $table,
                $row,
                array( 'match_id' => $match_id ),
                array( '%d', '%d', '%d', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $row['match_id'] = $match_id;
            $wpdb->insert(
                $table,
                $row,
                array( '%d', '%d', '%d', '%s', '%s', '%d' )
            );
        }

        if ( isset( $data['status'] ) ) {
            self::update_status( $match_id, $data['status'] );
        } else {
            self::touch( $match_id );
        }
    }

    // ── Cricket score ──────────────────────────────────────────────────────
    public static function get_cricket_score( $match_id ) {
        global $wpdb;
        $backup = self::get_score_backup( 'cricket', $match_id );
        if ( $backup ) {
            return $backup;
        }

        $score = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lsb_cricket_score WHERE match_id = %d ORDER BY id DESC LIMIT 1",
                $match_id
            )
        );
        return $score;
    }

    public static function update_cricket( $match_id, $data ) {
        global $wpdb;

        $batting_team  = isset( $data['batting_team']  ) ? absint( $data['batting_team']  ) : 1;
        $innings       = isset( $data['innings']       ) ? absint( $data['innings']       ) : 1;
        $score_a_runs  = isset( $data['score_a_runs']  ) ? absint( $data['score_a_runs']  ) : 0;
        $score_a_wkts  = isset( $data['score_a_wkts']  ) ? absint( $data['score_a_wkts']  ) : 0;
        $score_a_overs = isset( $data['score_a_overs'] ) ? self::normalize_cricket_overs( $data['score_a_overs'] ) : 0.0;
        $score_b_runs  = isset( $data['score_b_runs']  ) ? absint( $data['score_b_runs']  ) : 0;
        $score_b_wkts  = isset( $data['score_b_wkts']  ) ? absint( $data['score_b_wkts']  ) : 0;
        $score_b_overs = isset( $data['score_b_overs'] ) ? self::normalize_cricket_overs( $data['score_b_overs'] ) : 0.0;
        $target        = isset( $data['target']        ) ? absint( $data['target']        ) : 0;
        $result_text   = isset( $data['result_text']   ) ? sanitize_text_field( $data['result_text'] ) : '';

        $table = $wpdb->prefix . 'lsb_cricket_score';
        $row   = array(
            'batting_team'  => $batting_team,
            'innings'       => $innings,
            'score_a_runs'  => $score_a_runs,
            'score_a_wkts'  => $score_a_wkts,
            'score_a_overs' => $score_a_overs,
            'score_b_runs'  => $score_b_runs,
            'score_b_wkts'  => $score_b_wkts,
            'score_b_overs' => $score_b_overs,
            'target'        => $target,
            'result_text'   => $result_text,
        );
        self::save_score_backup( 'cricket', $match_id, array_merge( array( 'match_id' => $match_id ), $row ) );

        if ( self::score_row_exists( $table, $match_id ) ) {
            $wpdb->update(
                $table,
                $row,
                array( 'match_id' => $match_id ),
                array( '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%f', '%d', '%s' ),
                array( '%d' )
            );
        } else {
            $row['match_id'] = $match_id;
            $wpdb->insert(
                $table,
                $row,
                array( '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%f', '%d', '%s', '%d' )
            );
        }

        if ( isset( $data['status'] ) ) {
            self::update_status( $match_id, $data['status'] );
        } else {
            self::touch( $match_id );
        }
    }

    // ── Status ─────────────────────────────────────────────────────────────
    public static function update_status( $match_id, $status ) {
        global $wpdb;
        $allowed = array( 'upcoming', 'live', 'finished' );
        if ( in_array( $status, $allowed, true ) ) {
            $wpdb->update(
                $wpdb->prefix . 'lsb_matches',
                array(
                    'status'     => $status,
                    'updated_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $match_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        }
    }

    // ── Delete ─────────────────────────────────────────────────────────────
    public static function delete( $match_id ) {
        global $wpdb;
        $match = self::get( $match_id );
        if ( ! $match ) return;
        $wpdb->delete( $wpdb->prefix . 'lsb_football_score', array( 'match_id' => $match_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'lsb_cricket_score',  array( 'match_id' => $match_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'lsb_matches',        array( 'id'       => $match_id ), array( '%d' ) );
        delete_option( self::score_backup_key( 'football', $match_id ) );
        delete_option( self::score_backup_key( 'cricket', $match_id ) );
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private static function unique_slug( $title ) {
        global $wpdb;
        $base = sanitize_title( $title );
        $slug = $base;
        $i = 1;
        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}lsb_matches WHERE slug = %s", $slug ) ) ) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private static function score_row_exists( $table, $match_id ) {
        global $wpdb;
        return (bool) $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$table} WHERE match_id = %d LIMIT 1", $match_id )
        );
    }

    private static function touch( $match_id ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'lsb_matches',
            array( 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $match_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    private static function save_score_backup( $sport, $match_id, $score ) {
        $score['match_id'] = (int) $match_id;
        update_option( self::score_backup_key( $sport, $match_id ), $score, false );
    }

    private static function get_score_backup( $sport, $match_id ) {
        $score = get_option( self::score_backup_key( $sport, $match_id ), array() );
        if ( ! is_array( $score ) || ! $score ) {
            return null;
        }
        return (object) $score;
    }

    private static function score_backup_key( $sport, $match_id ) {
        return 'lsb_' . $sport . '_score_' . absint( $match_id );
    }

    private static function default_football_score( $match_id ) {
        return array(
            'match_id' => (int) $match_id,
            'score_a'  => 0,
            'score_b'  => 0,
            'half'     => '1st',
            'minute'   => 0,
            'events'   => '',
        );
    }

    private static function default_cricket_score( $match_id ) {
        return array(
            'match_id'        => (int) $match_id,
            'batting_team'    => 1,
            'innings'         => 1,
            'score_a_runs'    => 0,
            'score_a_wkts'    => 0,
            'score_a_overs'   => 0.0,
            'score_b_runs'    => 0,
            'score_b_wkts'    => 0,
            'score_b_overs'   => 0.0,
            'target'          => 0,
            'result_text'     => '',
        );
    }

    private static function normalize_cricket_overs( $value ) {
        $value = max( 0, (float) $value );
        $overs = (int) floor( $value );
        $balls = (int) round( ( $value - $overs ) * 10 );

        if ( $balls >= 6 ) {
            $overs += intdiv( $balls, 6 );
            $balls = $balls % 6;
        }

        return (float) sprintf( '%d.%d', $overs, $balls );
    }

    public static function can_edit( $match_id, $user_id ) {
        $match = self::get( $match_id );
        if ( ! $match ) return false;
        if ( (int) $match->user_id === (int) $user_id ) return true;
        return user_can( $user_id, 'manage_options' );
    }
}
