<?php
/**
 * Rows of Courses for a selected User.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Should be checked later.
 */

defined( 'ABSPATH' ) || exit;

if ( current_user_can( 'edit_user', $activity->user_id ) ) {
	$user_link = get_edit_user_link( $activity->user_id ) . '#ld_course_info';
} else {
	$user_link = '#';
}

if ( current_user_can( 'edit_courses', $activity->post_id ) ) {
	$post_link = get_edit_post_link( $activity->post_id ) . '#ld_course_info';
} else {
	$post_link = '#';
}

switch ( $header_key ) {
	case 'course_id':
		echo $activity->post_id;
		break;

	case 'course':
		?>
			<strong title="
			<?php
			printf(
			// translators: Course ID
				esc_html_x( '%s ID:', 'Course ID', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);
			?>
			<?php echo $activity->post_id; ?>" class="display-name"><?php echo esc_html( $activity->post_title ); ?></strong>
			<?php
		break;

	case 'progress':
		?>
			<div class="progress-bar" title="
			<?php
			printf(
			// translators: Number of completed course steps.
				esc_html_x( '%1$d of %2$d steps completed', 'Number of completed course steps', 'learndash' ),
				absint( LearnDash_ProPanel_Activity::get_activity_steps_completed( $activity ) ),
				absint( LearnDash_ProPanel_Activity::get_activity_steps_total( $activity ) )
			);
			?>
				">
				<?php
				if ( is_null( $activity->activity_status ) ) {
					$progress_percent = 0;
					$progress_label   = __( 'Not Started', 'learndash' );
				} elseif ( $activity->activity_status == false ) {
					$steps_completed = absint( LearnDash_ProPanel_Activity::get_activity_steps_completed( $activity ) );
					$steps_total     = absint( LearnDash_ProPanel_Activity::get_activity_steps_total( $activity ) );
					if ( ( ! empty( $steps_total ) ) && ( ! empty( $steps_completed ) ) ) {
						$progress_percent = round( 100 * ( $steps_completed / $steps_total ) );
					} else {
						$progress_percent = 0;
					}
					$progress_label = $progress_percent . '%';
				} elseif ( $activity->activity_status == true ) {
					$progress_percent = 100;
					$progress_label   = $progress_percent . '%';
				}
				?>
				<span class="actual-progress" style="width: <?php echo $progress_percent; ?>%;"></span>
			</div>
			<strong class="progress-amount"><?php echo $progress_label; ?></strong>
		<?php
		break;

	case 'last_update':
		echo learndash_adjust_date_time_display( intval( $activity->activity_completed ) );
		break;
}
