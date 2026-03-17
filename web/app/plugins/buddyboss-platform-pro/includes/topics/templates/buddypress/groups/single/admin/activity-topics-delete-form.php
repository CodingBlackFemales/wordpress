<?php
/**
 * BuddyBoss - Groups Activity Topics Delete/Migrate Form
 *
 * @since   2.7.40
 *
 * @package BuddyBoss_Platform_Pro\Includes\Topics\Templates\BuddyPress\Groups\Single\Admin
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/activity-topics-delete-form.php.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-activity-migrate-topic_modal topic_form">
	<div class="bb-action-popup bb-action-popup--activity-migrate-topic" id="bb-activity-migrate-topic_modal" style="display: none">
		<transition name="modal">
			<div class="modal-mask bb-white bbm-model-wrap">
				<div class="modal-wrapper">
					<div class="modal-container">
						<header class="bb-model-header">
							<h4>
								<span class="target_name">
									<?php esc_html_e( 'Deleting', 'buddyboss-pro' ); ?>										
								</span>
							</h4>
							<a class="bb-close-action-popup bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
								<span class="bb-icon-l bb-icon-times"></span>
							</a>
						</header>
						<div class="bb-action-popup-content">
							<p class="bb-action-popup-description">
								<?php esc_html_e( 'Would you like to move all previously tagged posts into another topic?', 'buddyboss-pro' ); ?>
							</p>
							<div class="bb-existing-topic-list" id="bb_existing_topic_list">
								<div class="form-fields">
									<div class="input-field">
										<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_existing_topic" value="migrate" checked>
										<label for="bb_migrate_existing_topic"><?php esc_html_e( 'Yes, move posts to another topic', 'buddyboss-pro' ); ?></label>
										<div class="sub-input-field">
											<select name="bb_existing_topic_id" id="bb_existing_topic_id">
												<option value="0"><?php esc_html_e( 'Select topic', 'buddyboss-pro' ); ?></option>
											</select>
										</div>
									</div>
									<div class="input-field">
										<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_uncategorized_topic" value="delete">
										<label for="bb_migrate_uncategorized_topic"><?php esc_html_e( 'No, delete the topic', 'buddyboss-pro' ); ?></label>
									</div>
								</div>
							</div>
						</div><!-- .bb-action-popup-content -->
						<footer class="bb-model-footer">
							<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="0">
							<input type="hidden" id="bb_item_id" name="bb_item_id" value="0">
							<input type="hidden" id="bb_item_type" name="bb_item_type" value="groups">
							<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_migrate_topic' ) ); ?>">
							<input type="hidden" id="bb_is_global_activity" name="bb_is_global_activity" value="">
							<a href="#" class="bb-topic-cancel"><?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?></a>
							<button type="button" id="bb_topic_migrate" class="button button-primary">
								<?php esc_html_e( 'Confirm', 'buddyboss-pro' ); ?>
							</button>
						</footer>
					</div>
				</div>
			</div>
		</transition>
	</div>
</div>