<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lsb_stepper_input' ) ) {
    function lsb_stepper_input( $name, $value, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'min'   => 0,
            'max'   => '',
            'step'  => 1,
            'class' => '',
            'style' => '',
        ) );

        $attrs = '';
        if ( $args['max'] !== '' ) {
            $attrs .= ' max="' . esc_attr( $args['max'] ) . '"';
        }

        echo '<span class="lsb-stepper">';
        echo '<button type="button" class="button lsb-step-btn" data-step="-1" aria-label="' . esc_attr__( 'Decrease', 'live-scoreboard' ) . '">-</button>';
        echo '<input type="number" name="' . esc_attr( $name ) . '" min="' . esc_attr( $args['min'] ) . '"' . $attrs . ' step="' . esc_attr( $args['step'] ) . '" value="' . esc_attr( $value ) . '" class="lsb-field lsb-step-input ' . esc_attr( $args['class'] ) . '" style="' . esc_attr( $args['style'] ) . '">';
        echo '<button type="button" class="button lsb-step-btn" data-step="1" aria-label="' . esc_attr__( 'Increase', 'live-scoreboard' ) . '">+</button>';
        echo '</span>';
    }
}

// Pre-load current score from DB so all fields are correctly populated on page load
if ( $match->sport === 'football' ) {
    $saved = LSB_Match::get_football_score( (int) $match->id );
} else {
    $saved = LSB_Match::get_cricket_score( (int) $match->id );
}

$cur_status = esc_attr( $match->status );
?>
<div class="lsb-update-panel" data-match-id="<?php echo (int) $match->id; ?>" data-sport="<?php echo esc_attr( $match->sport ); ?>">
    <h2><?php esc_html_e( 'Update Score', 'live-scoreboard' ); ?></h2>

    <div class="lsb-status-row">
        <label><?php esc_html_e( 'Match status:', 'live-scoreboard' ); ?></label>
        <select name="status" class="lsb-field">
            <option value="upcoming" <?php selected( $cur_status, 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'live-scoreboard' ); ?></option>
            <option value="live"     <?php selected( $cur_status, 'live' );     ?>><?php esc_html_e( 'Live', 'live-scoreboard' ); ?></option>
            <option value="finished" <?php selected( $cur_status, 'finished' ); ?>><?php esc_html_e( 'Finished', 'live-scoreboard' ); ?></option>
        </select>
    </div>

    <?php if ( $match->sport === 'football' ) :
        $score_a = $saved ? (int) $saved->score_a : 0;
        $score_b = $saved ? (int) $saved->score_b : 0;
        $minute  = $saved ? (int) $saved->minute  : 0;
        $half    = $saved ? $saved->half           : '1st';
    ?>
    <div class="lsb-football-controls">
        <div class="lsb-score-inputs">
            <div>
                <label><?php echo esc_html( $match->team_a ); ?></label>
                <?php lsb_stepper_input( 'score_a', $score_a, array( 'max' => 99, 'class' => 'lsb-score-input' ) ); ?>
            </div>
            <span class="lsb-vs">:</span>
            <div>
                <label><?php echo esc_html( $match->team_b ); ?></label>
                <?php lsb_stepper_input( 'score_b', $score_b, array( 'max' => 99, 'class' => 'lsb-score-input' ) ); ?>
            </div>
        </div>
        <div class="lsb-minute-row">
            <label><?php esc_html_e( 'Minute:', 'live-scoreboard' ); ?></label>
            <?php lsb_stepper_input( 'minute', $minute, array( 'max' => 120, 'style' => 'width:70px' ) ); ?>
            <label><?php esc_html_e( 'Half:', 'live-scoreboard' ); ?></label>
            <select name="half" class="lsb-field">
                <?php foreach ( array( '1st' => '1st Half', '2nd' => '2nd Half', 'ET1' => 'Extra Time 1', 'ET2' => 'Extra Time 2', 'Penalties' => 'Penalties', 'Full Time' => 'Full Time' ) as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $half, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?php esc_html_e( 'Events / Commentary:', 'live-scoreboard' ); ?></label>
            <input type="text" name="events" value="<?php echo $saved ? esc_attr( $saved->events ) : ''; ?>" class="lsb-field" style="width:100%;max-width:400px" placeholder="e.g. ⚽ 67' Haaland · 🟨 45' Saka">
        </div>
        <button class="button button-primary lsb-update-btn" data-action="lsb_update_football">
            <?php esc_html_e( 'Update Football Score', 'live-scoreboard' ); ?>
        </button>
    </div>

    <?php else :
        $ar = $saved ? (int)   $saved->score_a_runs  : 0;
        $aw = $saved ? (int)   $saved->score_a_wkts  : 0;
        $ao = $saved ? (float) $saved->score_a_overs : 0.0;
        $br = $saved ? (int)   $saved->score_b_runs  : 0;
        $bw = $saved ? (int)   $saved->score_b_wkts  : 0;
        $bo = $saved ? (float) $saved->score_b_overs : 0.0;
        $tg = $saved ? (int)   $saved->target        : 0;
        $rt = $saved ?         $saved->result_text   : '';
        $bt = $saved ? (int)   $saved->batting_team  : 1;
        $in = $saved ? (int)   $saved->innings       : 1;
    ?>
    <div class="lsb-cricket-controls">
        <div class="lsb-cricket-row">
            <label><?php esc_html_e( 'Innings:', 'live-scoreboard' ); ?></label>
            <select name="innings" class="lsb-field">
                <option value="1" <?php selected( $in, 1 ); ?>><?php esc_html_e( '1st Innings', 'live-scoreboard' ); ?></option>
                <option value="2" <?php selected( $in, 2 ); ?>><?php esc_html_e( '2nd Innings', 'live-scoreboard' ); ?></option>
            </select>
            <label><?php esc_html_e( 'Batting:', 'live-scoreboard' ); ?></label>
            <select name="batting_team" class="lsb-field">
                <option value="1" <?php selected( $bt, 1 ); ?>><?php echo esc_html( $match->team_a ); ?></option>
                <option value="2" <?php selected( $bt, 2 ); ?>><?php echo esc_html( $match->team_b ); ?></option>
            </select>
        </div>
        <div class="lsb-cricket-scores">
            <fieldset>
                <legend><?php echo esc_html( $match->team_a ); ?></legend>
                <label><?php esc_html_e( 'Runs', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_a_runs', $ar, array( 'style' => 'width:70px' ) ); ?>
                <div class="lsb-run-quick" data-target="score_a_runs">
                    <button type="button" class="button lsb-run-btn" data-runs="2">+2</button>
                    <button type="button" class="button lsb-run-btn" data-runs="4">+4</button>
                    <button type="button" class="button lsb-run-btn" data-runs="6">+6</button>
                </div>
                <label><?php esc_html_e( 'Wkts', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_a_wkts', $aw, array( 'max' => 10, 'style' => 'width:55px' ) ); ?>
                <label><?php esc_html_e( 'Overs', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_a_overs', $ao, array( 'step' => 0.1, 'style' => 'width:65px' ) ); ?>
            </fieldset>
            <fieldset>
                <legend><?php echo esc_html( $match->team_b ); ?></legend>
                <label><?php esc_html_e( 'Runs', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_b_runs', $br, array( 'style' => 'width:70px' ) ); ?>
                <div class="lsb-run-quick" data-target="score_b_runs">
                    <button type="button" class="button lsb-run-btn" data-runs="2">+2</button>
                    <button type="button" class="button lsb-run-btn" data-runs="4">+4</button>
                    <button type="button" class="button lsb-run-btn" data-runs="6">+6</button>
                </div>
                <label><?php esc_html_e( 'Wkts', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_b_wkts', $bw, array( 'max' => 10, 'style' => 'width:55px' ) ); ?>
                <label><?php esc_html_e( 'Overs', 'live-scoreboard' ); ?></label>
                <?php lsb_stepper_input( 'score_b_overs', $bo, array( 'step' => 0.1, 'style' => 'width:65px' ) ); ?>
            </fieldset>
        </div>
        <div>
            <label><?php esc_html_e( 'Target:', 'live-scoreboard' ); ?></label>
            <?php lsb_stepper_input( 'target', $tg, array( 'style' => 'width:80px' ) ); ?>
            <label><?php esc_html_e( 'Result:', 'live-scoreboard' ); ?></label>
            <input type="text" name="result_text" value="<?php echo esc_attr( $rt ); ?>" class="lsb-field" placeholder="e.g. India won by 5 wickets" style="width:260px">
        </div>
        <button class="button button-primary lsb-update-btn" data-action="lsb_update_cricket">
            <?php esc_html_e( 'Update Cricket Score', 'live-scoreboard' ); ?>
        </button>
    </div>
    <?php endif; ?>

    <p class="lsb-update-msg"></p>

    <?php if ( $match->status !== 'finished' ) : ?>
    <div class="lsb-end-match-row">
        <hr>
        <button class="button lsb-end-match-btn" data-match-id="<?php echo (int) $match->id; ?>">
            &#9632; <?php esc_html_e( 'End Match', 'live-scoreboard' ); ?>
        </button>
        <span class="lsb-end-msg"></span>
    </div>
    <?php else : ?>
    <div class="lsb-end-match-row">
        <hr>
        <p class="lsb-finished-notice">&#10003; <?php esc_html_e( 'This match has ended.', 'live-scoreboard' ); ?></p>
    </div>
    <?php endif; ?>
</div>
