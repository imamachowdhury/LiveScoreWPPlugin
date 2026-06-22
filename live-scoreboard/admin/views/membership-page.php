<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap lsb-admin-wrap">
    <h1><?php esc_html_e( 'Membership & Subscriptions', 'live-scoreboard' ); ?></h1>

    <?php if ( ! empty( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Subscription settings updated.', 'live-scoreboard' ); ?></p></div>
    <?php endif; ?>

    <form class="lsb-admin-card lsb-admin-card--wide" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=lsb-membership' ) ); ?>">
        <div class="lsb-card-heading">
            <div>
                <h2><?php esc_html_e( 'Package Manager', 'live-scoreboard' ); ?></h2>
                <p><?php esc_html_e( 'Create the subscription packages admins can manually assign to members.', 'live-scoreboard' ); ?></p>
            </div>
            <button type="button" class="button" id="lsb-add-package"><?php esc_html_e( 'Add Package', 'live-scoreboard' ); ?></button>
        </div>

        <input type="hidden" name="lsb_membership_action" value="save_packages">
        <?php wp_nonce_field( 'lsb_membership_settings' ); ?>

        <div class="lsb-package-list" id="lsb-package-list">
            <?php foreach ( $packages as $index => $package ) : ?>
            <div class="lsb-package-row">
                <label>
                    <span><?php esc_html_e( 'Package name', 'live-scoreboard' ); ?></span>
                    <input type="text" name="package_name[]" value="<?php echo esc_attr( $package['name'] ); ?>" placeholder="<?php esc_attr_e( 'Monthly access', 'live-scoreboard' ); ?>">
                </label>
                <label>
                    <span><?php esc_html_e( 'Days', 'live-scoreboard' ); ?></span>
                    <input type="number" min="1" name="package_days[]" value="<?php echo esc_attr( absint( $package['days'] ) ); ?>">
                </label>
                <label>
                    <span><?php esc_html_e( 'Price label', 'live-scoreboard' ); ?></span>
                    <input type="text" name="package_price[]" value="<?php echo esc_attr( $package['price_label'] ); ?>" placeholder="<?php esc_attr_e( 'Example: BDT 500', 'live-scoreboard' ); ?>">
                </label>
                <label>
                    <span><?php esc_html_e( 'Description', 'live-scoreboard' ); ?></span>
                    <textarea name="package_description[]" rows="2"><?php echo esc_textarea( $package['description'] ); ?></textarea>
                </label>
                <div class="lsb-package-row-actions">
                    <label class="lsb-checkbox-row">
                        <input type="checkbox" name="package_active[<?php echo esc_attr( $index ); ?>]" value="1" <?php checked( $package['active'], 'yes' ); ?>>
                        <span><?php esc_html_e( 'Active', 'live-scoreboard' ); ?></span>
                    </label>
                    <button type="button" class="button button-link-delete lsb-remove-package"><?php esc_html_e( 'Remove', 'live-scoreboard' ); ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="lsb-admin-actions">
            <?php submit_button( __( 'Save Packages', 'live-scoreboard' ), 'primary', 'submit', false ); ?>
        </div>
    </form>

    <div class="lsb-admin-card lsb-admin-card--wide">
        <div class="lsb-card-heading">
            <div>
                <h2><?php esc_html_e( 'Member Access', 'live-scoreboard' ); ?></h2>
                <p><?php esc_html_e( 'Assign packages, set an exact expiry date, give manual access, or remove access from each member row.', 'live-scoreboard' ); ?></p>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped lsb-member-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Member', 'live-scoreboard' ); ?></th>
                    <th><?php esc_html_e( 'Current Access', 'live-scoreboard' ); ?></th>
                    <th><?php esc_html_e( 'Update Access', 'live-scoreboard' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( $users ) : foreach ( $users as $member ) : ?>
                <?php
                $status_key  = LSB_Membership::status_key( $member->ID );
                $current_plan = LSB_Membership::current_plan( $member->ID );
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $member->display_name ); ?></strong>
                        <span><?php echo esc_html( $member->user_login ); ?></span>
                        <small><?php echo esc_html( $member->user_email ); ?></small>
                    </td>
                    <td>
                        <span class="lsb-member-status lsb-member-status--<?php echo esc_attr( $status_key ); ?>">
                            <?php echo esc_html( LSB_Membership::status_label( $member->ID ) ); ?>
                        </span>
                        <?php if ( $current_plan ) : ?>
                            <small><?php echo esc_html( $current_plan ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form class="lsb-member-actions" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=lsb-membership' ) ); ?>">
                            <input type="hidden" name="lsb_membership_action" value="update_member">
                            <input type="hidden" name="user_id" value="<?php echo (int) $member->ID; ?>">
                            <?php wp_nonce_field( 'lsb_membership_member' ); ?>

                            <label>
                                <span><?php esc_html_e( 'Package', 'live-scoreboard' ); ?></span>
                                <select name="package_id">
                                    <option value=""><?php esc_html_e( 'Select package', 'live-scoreboard' ); ?></option>
                                    <?php foreach ( LSB_Membership::active_packages() as $package ) : ?>
                                        <option value="<?php echo esc_attr( $package['id'] ); ?>">
                                            <?php echo esc_html( $package['name'] . ' - ' . absint( $package['days'] ) . ' days' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                <span><?php esc_html_e( 'Exact expiry', 'live-scoreboard' ); ?></span>
                                <input type="date" name="expiry_date" value="<?php echo esc_attr( LSB_Membership::expiry_date_value( $member->ID ) ); ?>">
                            </label>

                            <label>
                                <span><?php esc_html_e( 'Custom name', 'live-scoreboard' ); ?></span>
                                <input type="text" name="plan_name" value="<?php echo esc_attr( $current_plan ); ?>" placeholder="<?php esc_attr_e( 'Custom subscription', 'live-scoreboard' ); ?>">
                            </label>

                            <div class="lsb-member-buttons">
                                <button class="button button-primary" name="member_action" value="package"><?php esc_html_e( 'Assign Package', 'live-scoreboard' ); ?></button>
                                <button class="button" name="member_action" value="custom_expiry"><?php esc_html_e( 'Set Expiry', 'live-scoreboard' ); ?></button>
                                <button class="button" name="member_action" value="manual"><?php esc_html_e( 'Manual Access', 'live-scoreboard' ); ?></button>
                                <button class="button button-link-delete" name="member_action" value="clear"><?php esc_html_e( 'Remove Access', 'live-scoreboard' ); ?></button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="3"><?php esc_html_e( 'No members found.', 'live-scoreboard' ); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
