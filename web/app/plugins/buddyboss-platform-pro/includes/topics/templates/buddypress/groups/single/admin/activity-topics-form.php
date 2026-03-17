<?php
/**
 * BuddyBoss - Groups Activity Topics Form
 *
 * @since   2.7.40
 *
 * @package BuddyBoss_Platform_Pro\Includes\Topics\Templates\BuddyPress\Groups\Single\Admin
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/activity-topics-form.php.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$args             = isset( $args ) ? $args : array();
$current_action   = isset( $args['action'] ) ? $args['action'] : '';
$group_id         = isset( $args['group_id'] ) ? $args['group_id'] : 0;
$permission_types = isset( $args['permission_types'] ) ? $args['permission_types'] : array();
?>

<div class="bb-activity-topic_modal topic_form">
	<div class="bb-action-popup bb-action-popup--activity-topic" id="bb-activity-topic-form_modal" style="display: none">
		<transition name="modal">
			<div class="modal-mask bb-white bbm-model-wrap">
				<div class="modal-wrapper">
					<div class="modal-container">
						<header class="bb-model-header">
							<h4>
								<span class="target_name">
									<?php esc_html_e( 'Create Topic', 'buddyboss-pro' ); ?>										
								</span>
							</h4>
							<a class="bb-close-action-popup bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
								<span class="bb-icon-l bb-icon-times"></span>
							</a>
						</header>
						<div class="bb-action-popup-content">
							<label><?php esc_html_e( 'Topic Name', 'buddyboss-pro' ); ?></label>
							<div class="input-field">
								<?php
								$group_activity_topic_options = bb_get_group_activity_topic_options();
								$allow_select                 = 'only_from_activity_topics' === $group_activity_topic_options || 'allow_both' === $group_activity_topic_options;
								if ( $allow_select ) {
									?>
									<select name="bb_topic_name" class="bb-topic-name-field bb_topic_name_select" id="bb_topic_name" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Select a topic', 'buddyboss-pro' ); ?>">
										<option value=""><?php esc_html_e( 'Select a topic', 'buddyboss-pro' ); ?></option>
										<?php
										$topics = bb_topics_manager_instance()->bb_get_topics( array( 'item_type' => 'activity' ) );
										if ( ! empty( $topics['topics'] ) ) {
											foreach ( $topics['topics'] as $topic ) {
												$is_global_activity = bb_topics_manager_instance()->bb_is_topic_global( $topic->topic_id );
												?>
												<option value="<?php echo esc_attr( $topic->slug ); ?>" data-is-global-activity="<?php echo (bool) esc_attr( $is_global_activity ); ?>"><?php echo esc_html( $topic->name ); ?></option>
												<?php
											}
										}
										?>
									</select>
									<?php
								} else {
									?>
									<input type="text" name="bb_topic_name" class="bb-topic-name-field" value="" id="bb_topic_name">
									<?php
								}
								?>
							</div>
							<label><?php esc_html_e( 'Who can post in this topic?', 'buddyboss-pro' ); ?></label>
							<div class="input-field">
								<?php
								if ( ! empty( $permission_types ) ) {
									foreach ( $permission_types as $key => $value ) {
										?>
										<label>
											<div class="bp-checkbox-wrap">
												<input type="radio" name="bb_permission_type" id="bb_permission_type_<?php echo esc_attr( $key ); ?>" class="bs-styled-radio" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'members' === $key, true ); ?> />
												<label for="bb_permission_type_<?php echo esc_attr( $key ); ?>">
													<?php echo esc_html( $value ); ?>
												</label>
											</div>
										</label>
										<?php
									}
								}
								?>
							</div>
						</div><!-- .bb-action-popup-content -->
						<footer class="bb-model-footer">
							<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="">
							<input type="hidden" id="bb_item_id" name="bb_item_id" value="<?php echo esc_attr( $group_id ); ?>">
							<input type="hidden" id="bb_item_type" name="bb_item_type" value="groups">
							<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_add_topic' ) ); ?>">
							<input type="hidden" id="bb_action_from" name="bb_action_from" value="admin">
							<input type="hidden" id="bb_is_global_activity" name="bb_is_global_activity" value="">
							<a href="#" class="bb-topic-cancel"><?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?></a>
							<button type="button" id="bb_topic_submit" class="button button-primary" disabled="disabled">
								<?php esc_html_e( 'Confirm', 'buddyboss-pro' ); ?>
							</button>
						</footer>
					</div>
				</div>
			</div>
		</transition>
	</div>
</div>