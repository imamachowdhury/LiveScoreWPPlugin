<?php
defined( 'ABSPATH' ) || exit;
$user_id = get_current_user_id();
$matches = LSB_Match::get_for_user( $user_id );
?>
<div class="lsb-dashboard">
    <div class="lsb-membership-status">
        <strong><?php esc_html_e( 'Subscription:', 'live-scoreboard' ); ?></strong>
        <span><?php echo esc_html( LSB_Membership::status_label( $user_id ) ); ?></span>
    </div>

    <div class="lsb-dashboard-header">
        <div>
            <h2><?php esc_html_e( 'My Matches', 'live-scoreboard' ); ?></h2>
        </div>
        <span class="lsb-dashboard-count"><?php echo esc_html( count( $matches ) ); ?> <?php esc_html_e( 'matches', 'live-scoreboard' ); ?></span>
    </div>

    <div class="lsb-create-form">
        <h3><?php esc_html_e( 'Create New Match', 'live-scoreboard' ); ?></h3>
        <div class="lsb-create-grid">
            <label><?php esc_html_e( 'Sport', 'live-scoreboard' ); ?>
                <select name="sport" class="lsb-field" id="lsb-new-sport">
                    <option value="football"><?php esc_html_e( 'Football', 'live-scoreboard' ); ?></option>
                    <option value="cricket"><?php esc_html_e( 'Cricket', 'live-scoreboard' ); ?></option>
                </select>
            </label>
            <label><?php esc_html_e( 'Match Title', 'live-scoreboard' ); ?>
                <input type="text" id="lsb-new-title" class="lsb-field" placeholder="<?php esc_attr_e( 'e.g. Premier League Round 5', 'live-scoreboard' ); ?>">
            </label>
            <label><?php esc_html_e( 'Team A', 'live-scoreboard' ); ?>
                <input type="text" id="lsb-new-team-a" class="lsb-field" placeholder="<?php esc_attr_e( 'Team A name', 'live-scoreboard' ); ?>">
            </label>
            <label><?php esc_html_e( 'Team B', 'live-scoreboard' ); ?>
                <input type="text" id="lsb-new-team-b" class="lsb-field" placeholder="<?php esc_attr_e( 'Team B name', 'live-scoreboard' ); ?>">
            </label>
        </div>
        <button id="lsb-create-btn" class="button button-primary"><?php esc_html_e( 'Create Match', 'live-scoreboard' ); ?></button>
        <p class="lsb-create-msg"></p>
    </div>

    <?php if ( $matches ) : ?>
    <table class="lsb-dashboard-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Title', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Sport', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Teams', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Status', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'live-scoreboard' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $matches as $m ) : ?>
            <tr>
                <td><strong><?php echo esc_html( $m->title ); ?></strong></td>
                <td><span class="lsb-sport-pill"><?php echo esc_html( ucfirst( $m->sport ) ); ?></span></td>
                <td><?php echo esc_html( $m->team_a . ' vs ' . $m->team_b ); ?></td>
                <td><span class="lsb-status lsb-status--<?php echo esc_attr( $m->status ); ?>"><?php echo esc_html( $m->status ); ?></span></td>
                <td>
                    <div class="lsb-row-actions">
                        <a href="<?php echo esc_url( home_url( '/scoreboard/' . $m->slug ) ); ?>" class="button button-small" target="_blank">
                            <?php esc_html_e( 'View / Update', 'live-scoreboard' ); ?>
                        </a>
                        <button class="button button-small lsb-copy-overlay" data-url="<?php echo esc_attr( home_url( '/scoreboard/overlay/' . $m->slug ) ); ?>">
                            <?php esc_html_e( 'Copy OBS URL', 'live-scoreboard' ); ?>
                        </button>
                        <button class="button button-small lsb-delete-btn" data-id="<?php echo (int) $m->id; ?>">
                            <?php esc_html_e( 'Delete', 'live-scoreboard' ); ?>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
        <div class="lsb-empty-state">
            <strong><?php esc_html_e( 'No matches yet', 'live-scoreboard' ); ?></strong>
            <p><?php esc_html_e( 'Create your first match above and it will appear here.', 'live-scoreboard' ); ?></p>
        </div>
    <?php endif; ?>
</div>
