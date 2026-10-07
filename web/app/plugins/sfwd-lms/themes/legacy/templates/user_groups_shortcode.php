<?php
/**
 * Displays a user group lists.
 * This template is called from the [user_groups] shortcode.
 *
 * @param array $admin_groups Array of admin group IDs.
 * @param array $user_groups Array of user group IDs.
 * @param boolean $has_admin_groups True if there are admin groups.
 * @param boolean $has_user_groups True if there are user groups.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\Legacy\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="learndash-user-groups">
<?php if ( $has_admin_groups ) { ?>
	<div class="learndash-user-groups-section learndash-user-groups-section-leader-list">
		<div class="learndash-user-groups-header">
		<?php
		printf(
			// translators: placeholder: Group Leader.
			esc_html_x( '%s in : ', 'placeholder: Group Leader', 'learndash' ),
			learndash_get_custom_label( 'group_leader' )
		)
		?>
		</div>
		<ul class="learndash-user-groups-items">
			<?php
			foreach ( $admin_groups as $group_id ) {
				if ( ! empty( $group_id ) ) {
					$group = get_post( $group_id );
					if ( ( $group ) && ( is_a( $group, 'WP_Post' ) ) ) {
						?>
							<li class="learndash-user-groups-item">
								<span class="learndash-user-groups-item-title"><?php echo $group->post_title; ?></span>
							<?php
							if ( ! empty( $group->post_content ) ) {
								SFWD_LMS::content_filter_control( false );
								/** This filter is documented in https://developer.wordpress.org/reference/hooks/the_content/ */
								$group_content = apply_filters( 'the_content', $group->post_content );
								$group_content = str_replace( ']]>', ']]&gt;', $group_content );
								echo $group_content;

								SFWD_LMS::content_filter_control( true );
							}
							?>
								</li>
							<?php
					}
				}
			}
			?>
		</ul>
	</div>
<?php } ?>

<?php if ( $has_user_groups ) { ?>
	<div class="learndash-user-groups-section learndash-user-groups-section-assigned-list">
		<div class="learndash-user-groups-header">
		<?php
		printf(
			// translators: group.
			esc_html_x( 'Assigned %s(s) : ', 'placeholder: group', 'learndash' ),
			learndash_get_custom_label( 'group' )
		)
		?>
		</div>
		<ul class="learndash-user-groups-items">
			<?php
			foreach ( $user_groups as $group_id ) {
				if ( ! empty( $group_id ) ) {
					$group = get_post( $group_id );
					if ( ( $group ) && ( is_a( $group, 'WP_Post' ) ) ) {
						?>
							<li class="learndash-user-groups-item">
								<span class="learndash-user-groups-item-title"><?php echo $group->post_title; ?></span>
							<?php
							if ( ! empty( $group->post_content ) ) {
								/** This filter is documented in https://developer.wordpress.org/reference/hooks/the_excerpt/ */
								$group_content = apply_filters( 'the_excerpt', $group->post_content );
								$group_content = str_replace( ']]>', ']]&gt;', $group_content );
								echo $group_content;
							}
							?>
							</li>
							<?php
					}
				}
			}
			?>
		</ul>
	</div>
<?php } ?>
</div>
