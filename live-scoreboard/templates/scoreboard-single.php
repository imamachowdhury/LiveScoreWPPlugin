<?php
defined( 'ABSPATH' ) || exit;
$match = get_query_var( 'lsb_match_data' );
get_header();
?>

<div class="lsb-page-wrap">
    <div class="lsb-page-inner">

        <?php lsb_render_board( $match ); ?>

        <?php if ( is_user_logged_in() && current_user_can( 'lsb_manage' ) && LSB_Match::can_edit( $match->id, get_current_user_id() ) ) : ?>
            <?php include LSB_DIR . 'templates/update-panel.php'; ?>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
