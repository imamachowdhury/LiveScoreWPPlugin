<?php
defined( 'ABSPATH' ) || exit;

class LSB_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $matches = "CREATE TABLE {$wpdb->prefix}lsb_matches (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            sport       ENUM('football','cricket') NOT NULL DEFAULT 'football',
            title       VARCHAR(200) NOT NULL,
            slug        VARCHAR(200) NOT NULL UNIQUE,
            team_a      VARCHAR(100) NOT NULL,
            team_b      VARCHAR(100) NOT NULL,
            status      ENUM('upcoming','live','finished') NOT NULL DEFAULT 'upcoming',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset;";

        $football = "CREATE TABLE {$wpdb->prefix}lsb_football_score (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            match_id    BIGINT(20) UNSIGNED NOT NULL,
            score_a     TINYINT UNSIGNED NOT NULL DEFAULT 0,
            score_b     TINYINT UNSIGNED NOT NULL DEFAULT 0,
            half        ENUM('1st','2nd','ET1','ET2','Penalties','Full Time') NOT NULL DEFAULT '1st',
            minute      TINYINT UNSIGNED NOT NULL DEFAULT 0,
            events      LONGTEXT,
            PRIMARY KEY (id),
            UNIQUE KEY match_id_unique (match_id)
        ) $charset;";

        $cricket = "CREATE TABLE {$wpdb->prefix}lsb_cricket_score (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            match_id        BIGINT(20) UNSIGNED NOT NULL,
            batting_team    TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=team_a, 2=team_b',
            innings         TINYINT UNSIGNED NOT NULL DEFAULT 1,
            score_a_runs    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            score_a_wkts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            score_a_overs   DECIMAL(4,1) NOT NULL DEFAULT 0.0,
            score_b_runs    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            score_b_wkts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            score_b_overs   DECIMAL(4,1) NOT NULL DEFAULT 0.0,
            target          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            result_text     VARCHAR(255),
            events          LONGTEXT,
            PRIMARY KEY (id),
            UNIQUE KEY match_id_unique (match_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $matches );
        dbDelta( $football );
        dbDelta( $cricket );
    }
}
