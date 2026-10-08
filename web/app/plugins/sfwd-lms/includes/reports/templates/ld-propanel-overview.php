<?php
/**
 * Learndash ProPanel Activity Overview.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="clearfix propanel-admin-row center">
<?php

	$user_list_href = '';
if ( learndash_is_group_leader_user() ) {
	$user_list_href = admin_url( 'admin.php?page=group_admin_page' );
} elseif ( learndash_is_admin_user() ) {
	$user_list_href = admin_url( 'users.php' );
}
if ( ! empty( $user_list_href ) ) {
	?>
		<div class="col-1-2 propanel-stat propanel-students">
			<div class="stat-inner">
				<h2 class="stat-label">
					<a href="<?php echo esc_url( $user_list_href ); ?>"><?php esc_html_e( 'Total Students', 'learndash' ); ?></a>
				</h2>
				<strong class="stat">
				<?php
				if ( learndash_is_group_leader_user() ) {
					$student_count = count( learndash_get_group_leader_groups_users() );
				} elseif ( ( learndash_is_admin_user() ) && ( current_user_can( 'list_users' ) ) ) {
					$student_count = ld_propanel_get_users_count();
				} else {
					$student_count = 0;
				}

				if ( ! empty( $student_count ) ) {
					?>
						<a href="<?php echo esc_url( $user_list_href ); ?>">
						<?php
				}
				echo $student_count;
				if ( ! empty( $student_count ) ) {
					?>
						</a>
						<?php
				}
				?>
					</strong>
			</div>
		</div>
		<?php
}

	$user_list_href = '';
if ( learndash_is_group_leader_user() ) {
	$user_list_href = admin_url( 'admin.php?page=group_admin_page' );
} elseif ( learndash_is_admin_user() ) {
	$user_list_href = admin_url( 'edit.php?post_type=sfwd-courses' );
}

if ( ! empty( $user_list_href ) ) {
	?>
		<div class="col-1-2 propanel-stat propanel-courses">
			<div class="stat-inner">
				<h2 class="stat-label">
					<a href="<?php echo esc_url( $user_list_href ); ?>">
										<?php
										echo esc_html( LearnDash_Custom_Label::get_label( 'courses' ) );
										?>
						</a>
				</h2>
				<strong class="stat">
				<?php
				if ( learndash_is_group_leader_user() ) {
					$course_count = count( learndash_get_group_leader_groups_courses() );
				} elseif ( learndash_is_admin_user() ) {
					$course_count = ld_propanel_count_post_type( 'sfwd-courses' ); // learndash_get_courses_count();
				} else {
					$course_count = 0;
				}

				if ( ! empty( $course_count ) ) {
					?>
						<a href="<?php echo esc_url( $user_list_href ); ?>">
						<?php
				}
				echo $course_count;
				if ( ! empty( $course_count ) ) {
					?>
						</a>
						<?php
				}

				?>
					</strong>
			</div>
		</div>
		<?php
}

if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
	?>
		<div class="col-1-2 propanel-stat propanel-assignments">
			<div class="stat-inner">
				<h2 class="stat-label"><a href="<?php echo esc_url( learndash_admin_get_assignments_pending_listing_link() ); ?>"><?php esc_html_e( 'Assignments Pending', 'learndash' ); ?></a></h2>
				<strong class="stat"><a href="<?php echo esc_url( learndash_admin_get_assignments_pending_listing_link() ); ?>"><?php echo ld_propanel_get_assignments_pending_count(); ?></a></strong>
			</div>
		</div>
		<?php
}

if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) ) {
	?>
		<div class="col-1-2 propanel-stat propanel-essays">
			<div class="stat-inner">
				<h2 class="stat-label"><a href="<?php echo esc_url( learndash_admin_get_essays_pending_listing_link() ); ?>"><?php esc_html_e( 'Essays Pending', 'learndash' ); ?></a></h2>
				<strong class="stat"><a href="<?php echo esc_url( learndash_admin_get_essays_pending_listing_link() ); ?>"><?php echo ld_propanel_get_essays_pending_count(); ?></a></strong>
			</div>
		</div>
		<?php
}
?>
</div>
