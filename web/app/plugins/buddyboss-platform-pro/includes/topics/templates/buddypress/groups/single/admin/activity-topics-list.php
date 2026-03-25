<?php
/**
 * BuddyBoss - Groups Activity Topics List
 *
 * @since   2.7.40
 *
 * @package BuddyBoss_Platform_Pro\Includes\Topics\Templates\BuddyPress\Groups\Single\Admin
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/activity-topics-list.php.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$args                 = isset( $args ) ? $args : array();
$current_action       = isset( $args['action'] ) ? $args['action'] : '';
$group_id             = isset( $args['group_id'] ) ? $args['group_id'] : 0;
$topics               = isset( $args['topics'] ) ? $args['topics'] : array();
$topics_limit_reached = isset( $args['topics_limit_reached'] ) ? $args['topics_limit_reached'] : false;
$permission_types     = isset( $args['permission_types'] ) ? $args['permission_types'] : array();

?>

<div class="bb-activity-topics-content">
		<div class="bb-activity-topics-list">
			<?php
			$topics = ! empty( $topics['topics'] ) ? $topics['topics'] : array();
			if ( ! empty( $topics ) ) {
				foreach ( $topics as $topic ) {
					if ( ! is_object( $topic ) ) {
						continue;
					}
					$is_global_activity = bb_topics_manager_instance()->bb_is_topic_global( $topic->topic_id );
					$topic_attr         = array(
						'topic_id'           => $topic->topic_id,
						'item_id'            => ! empty( $topic->item_id ) ? $topic->item_id : $group_id,
						'item_type'          => ! empty( $topic->item_type ) ? $topic->item_type : 'groups',
						'is_global_activity' => $is_global_activity,
					);
					?>
					<div class="bb-activity-topic-item" data-topic-id="<?php echo esc_attr( $topic->topic_id ); ?>">
						<div class="bb-topic-left">
							<span class="bb-topic-drag">
								<i class="bb-icon-grip-v"></i>
							</span>
							<span class="bb-topic-title"><?php echo esc_html( $topic->name ); ?></span>
							<?php
							if ( $is_global_activity ) {
								?>
								<span class="bb-topic-privacy" data-bp-tooltip="<?php esc_html_e( 'Global', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="up"><i class="bb-icon-globe"></i></span>
								<?php
							}
							?>
						</div>
						<div class="bb-topic-right">
							<span class="bb-topic-access">
								<?php
								if ( ! empty( $permission_types ) && in_array( $topic->permission_type, array_keys( $permission_types ), true ) ) {
									echo esc_html( $permission_types[ $topic->permission_type ] );
								}
								?>
							</span>
							<div class="bb-topic-actions-wrapper">
								<span class="bb-topic-actions">
									<a href="#" class="bb-topic-actions_button" aria-label="<?php esc_attr_e( 'More options', 'buddyboss-pro' ); ?>">
										<i class="bb-icon-ellipsis-h"></i>
									</a>
								</span>
								<div class="bb-topic-more-dropdown">
									<a href="#" class="button edit bb-edit-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Edit', 'buddyboss-pro' ); ?>" data-topic-attr="<?php echo esc_attr( wp_json_encode( array_merge( $topic_attr, array( 'nonce' => wp_create_nonce( 'bb_edit_topic' ) ) ) ) ); ?>">
										<span class="bp-screen-reader-text"><?php esc_html_e( 'Edit', 'buddyboss-pro' ); ?></span>
										<span class="edit-label"><?php esc_html_e( 'Edit', 'buddyboss-pro' ); ?></span>
									</a>
									<?php
									$delete_text = $is_global_activity ? __( 'Remove from group', 'buddyboss-pro' ) : __( 'Delete', 'buddyboss-pro' );
									?>
									<a href="#" class="button delete bb-delete-topic bp-secondary-action bp-tooltip" title="<?php esc_html_e( 'Delete', 'buddyboss-pro' ); ?>" data-topic-attr="<?php echo esc_attr( wp_json_encode( array_merge( $topic_attr, array( 'nonce' => wp_create_nonce( 'bb_delete_topic' ) ) ) ) ); ?>">
										<span class="bp-screen-reader-text"><?php esc_html_e( 'Delete', 'buddyboss-pro' ); ?></span>
										<span class="delete-label"><?php echo esc_html( $delete_text ); ?></span>
									</a>
								</div>
							</div>
						</div>
						<input disabled="<?php echo esc_attr( $topics_limit_reached ? 'disabled' : '' ); ?>" id="bb_activity_topics" name="bb_activity_topic_options[<?php echo esc_attr( $topic->slug ); ?>]" type="hidden" value="bb_activity_topic_options[<?php echo esc_attr( $topic->slug ); ?>]">
					</div>
					<?php
				}
			}
			?>
		</div>
		<?php
		$button_class = $topics_limit_reached ? 'bp-hide' : '';
		?>
	<button type="button" class="button button-secondary bb-add-topic <?php echo esc_attr( $button_class ); ?>">
		<i class="bb-icon-plus"></i>
		<?php esc_html_e( 'Add New Topic', 'buddyboss-pro' ); ?>
	</button>
</div>