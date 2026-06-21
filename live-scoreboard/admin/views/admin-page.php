<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap lsb-admin-wrap">
    <h1><?php esc_html_e( 'Live Scoreboard — All Matches', 'live-scoreboard' ); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Title', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Sport', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Teams', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Status', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Scorer', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'URL', 'live-scoreboard' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'live-scoreboard' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( $matches ) : foreach ( $matches as $m ) : ?>
            <tr>
                <td><?php echo (int) $m->id; ?></td>
                <td><?php echo esc_html( $m->title ); ?></td>
                <td><?php echo esc_html( ucfirst( $m->sport ) ); ?></td>
                <td><?php echo esc_html( $m->team_a . ' vs ' . $m->team_b ); ?></td>
                <td><span class="lsb-status lsb-status--<?php echo esc_attr( $m->status ); ?>"><?php echo esc_html( $m->status ); ?></span></td>
                <td><?php echo esc_html( $m->user_login ); ?></td>
                <td><a href="<?php echo esc_url( home_url( '/scoreboard/' . $m->slug ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'live-scoreboard' ); ?></a></td>
                <td>
                    <button class="button button-small lsb-admin-delete" data-id="<?php echo (int) $m->id; ?>">
                        <?php esc_html_e( 'Delete', 'live-scoreboard' ); ?>
                    </button>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="8"><?php esc_html_e( 'No matches yet.', 'live-scoreboard' ); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
