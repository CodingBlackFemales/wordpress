<?php
/**
 * BuddyBoss - Groups Create: Topics Step for ReadyLaunch.
 *
 * @since BuddyBoss 2.7.50
 *
 * @package BuddyBoss_Platform_Pro\Includes\Topics\Templates\ReadyLaunch\Groups\Single\Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$current_action = isset( $args['action'] ) ? $args['action'] : '';
$group_id       = isset( $args['group_id'] ) ? $args['group_id'] : 0;
if ( empty( $group_id ) ) {
	return;
}

$is_admin       = is_admin();
$topics_manager = bb_topics_manager_instance();
$topics         = $topics_manager->bb_get_topics(
	array(
		'item_id'   => $group_id,
		'item_type' => 'groups',
	)
);

$list_args = array(
	'action'               => $current_action,
	'group_id'             => $group_id,
	'topics'               => $topics,
	'topics_limit_reached' => $topics_manager->bb_topics_limit_reached(
		array(
			'item_type' => 'groups',
			'item_id'   => $group_id,
		)
	),
	'permission_types'     => bb_group_activity_topic_permission_type(),
);

$form_args = array(
	'action'           => $current_action,
	'group_id'         => $group_id,
	'permission_types' => bb_group_activity_topic_permission_type(),
);

?>
<div class="bb-activity-topic-container">
	<?php
	if ( 'create' === $current_action ) {
		?>
		<h3 class="bb-activity-topic-title">
			<?php esc_html_e( 'Topics', 'buddyboss-pro' ); ?>
		</h3>
		<?php
	}
	?>
	<h4 class="bb-activity-topic-subtitle">
		<?php esc_html_e( 'Organize your group posts with topics and make them easier to find.', 'buddyboss-pro' ); ?>
	</h4>
	<p class="bb-activity-topic-desc">
		<?php esc_html_e( 'You can create up to 20 custom topics for each group, which members can use to filter posts using the topic navigation bar. If you use a global topic, your post will also appear in the main activity feed under that topic—giving it even more visibility.', 'buddyboss-pro' ); ?>
	</p>
	<?php
	bp_get_template_part( 'groups/single/admin/activity-topics-list', null, $list_args );
	?>
</div>
<?php
if ( 'create' === $current_action || 'edit' === $current_action ) {
	if ( $is_admin ) {
		?>
		<div id="bb-hello-backdrop" class="bb-hello-backdrop-activity-topic bb-modal-backdrop" style="display: none;"></div>
		<div id="bb-hello-container" class="bb-hello-activity-topic bb-modal-panel bb-modal-panel--activity-topic" role="dialog" aria-labelledby="bb-hello-activity-topic" style="display: none;">
			<div class="bb-hello-header">
				<div class="bb-hello-title">
					<h2 id="bb-hello-title" tabindex="-1">
						<?php esc_html_e( 'Create topic', 'buddyboss-pro' ); ?>
					</h2>
				</div>
				<div class="bb-hello-close">
					<button type="button" class="close-modal button" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<div class="bb-hello-content">
				<div class="form-fields">
					<div class="form-field">
						<div class="field-label">
							<label for="bb_topic_name"><?php esc_html_e( 'Topic name', 'buddyboss-pro' ); ?></label>
						</div>
						<div class="field-input">
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
											?>
											<option value="<?php echo esc_attr( $topic->slug ); ?>"><?php echo esc_html( $topic->name ); ?></option>
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
					</div>
					<div class="form-field">
						<div class="field-label">
							<label for="bb_permission_type"><?php esc_html_e( 'Who can post?', 'buddyboss-pro' ); ?></label>
						</div>
						<div class="field-input">
							<?php
							$permission_type = bb_group_activity_topic_permission_type();
							if ( ! empty( $permission_type ) ) {
								foreach ( $permission_type as $key => $value ) {
									?>
									<div class="bb-topic-who-can-post-option">
										<input type="radio" id="bb_permission_type_<?php echo esc_attr( $key ); ?>" name="bb_permission_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'members' === $key, true ); ?> />
										<label for="bb_permission_type_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
				<div class="bb-popup-buttons">
					<span id="bb_topic_cancel" class="button" tabindex="0">
						<?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?>
					</span>
					<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="">
					<input type="hidden" id="bb_item_id" name="bb_item_id" value="<?php echo esc_attr( $group_id ); ?>">
					<input type="hidden" id="bb_item_type" name="bb_item_type" value="groups">
					<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_add_topic' ) ); ?>">
					<input type="hidden" id="bb_action_from" name="bb_action_from" value="admin">
					<input type="hidden" id="bb_is_global_activity" name="bb_is_global_activity" value="">
					<button type="button" id="bb_topic_submit" class="button button-primary" disabled="disabled">
						<?php esc_html_e( 'Confirm', 'buddyboss-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		
		<!-- Migrate Topic Form -->
		<div id="bb-hello-topic-migrate-backdrop" class="bb-hello-backdrop-activity-topic-migrate bb-modal-backdrop" style="display: none;"></div>
		<div id="bb-hello-topic-migrate-container" class="bb-hello-activity-topic-migrate bb-modal-panel bb-modal-panel--activity-topic-migrate" role="dialog" aria-labelledby="bb-hello-activity-topic-migrate" style="display: none;">
			<div class="bb-hello-header">
				<div class="bb-hello-title">
					<h2 id="bb-hello-title" tabindex="-1">
						<?php esc_html_e( 'Deleting', 'buddyboss-pro' ); ?>
					</h2>
				</div>
				<div class="bb-hello-close">
					<button type="button" class="close-modal button" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<div class="bb-hello-content">
				<p class="bb-hello-content-description">
					<?php esc_html_e( 'Would you like to move all previously tagged posts into another topic?', 'buddyboss-pro' ); ?>
				</p>
				<div class="bb-existing-topic-list" id="bb_existing_topic_list">
					<div class="form-fields">
						<div class="form-field">
							<div class="field-label">
								<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_existing_topic" value="migrate" checked>
								<label for="bb_migrate_existing_topic"><?php esc_html_e( 'Yes, move posts to another topic', 'buddyboss-pro' ); ?></label>
							</div>
							<div class="field-input">
								<select name="bb_existing_topic_id" id="bb_existing_topic_id">
									<option value="0"><?php esc_html_e( 'Select topic', 'buddyboss-pro' ); ?></option>
								</select>
							</div>
						</div>
						<div class="form-field">
							<div class="field-label">
								<input type="radio" name="bb_migrate_existing_topic" id="bb_migrate_uncategorized_topic" value="delete">
								<label for="bb_migrate_uncategorized_topic"><?php esc_html_e( 'No, delete the topic', 'buddyboss-pro' ); ?></label>
							</div>
						</div>
					</div>
				</div>
				<div class="bb-popup-buttons">
					<span id="bb_topic_cancel" class="button" tabindex="0">
						<?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?>
					</span>
					<input type="hidden" id="bb_topic_id" name="bb_topic_id" value="0">
					<input type="hidden" id="bb_item_id" name="bb_item_id" value="0">
					<input type="hidden" id="bb_item_type" name="bb_item_type" value="activity">
					<input type="hidden" id="bb_topic_nonce" name="bb_topic_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bb_migrate_topic' ) ); ?>">
					<button type="button" id="bb_topic_migrate" class="button button-primary" disabled="disabled">
						<?php esc_html_e( 'Confirm', 'buddyboss-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	} else {
		bp_get_template_part( 'groups/single/admin/activity-topics-form', null, $form_args );
		bp_get_template_part( 'groups/single/admin/activity-topics-delete-form', null, array() );
	}
}
