<?php
/**
 * Section template for dashboard index
 *
 * @since 3.14.0
 * @since 3.30.1 Added dynamic filter on the `$more` var to allow customization of the URL and text on the "More" button.
 * @version  3.30.1
 */

defined( 'ABSPATH' ) || exit;
global $certificates, $achievements;
$more = apply_filters( 'llms_' . $action . '_more', $more );


$student = llms_get_student( get_current_user_id() );
if ( ! $student ) {
	return;
}

$courses = $student->get_courses(
	apply_filters(
		'llms_my_courses_loop_courses_query_args',
		array(
			'limit' => 500,
		),
		$student
	)
);

$memberships = $student->get_membership_levels();
?>

<section class="llms-sd-section <?php echo $slug; ?>">

	<div class="flex align-items-center llms-sd-section__heading">

		<?php if ( $title ) : ?>
			<h3 class="llms-sd-section-title">
				<?php echo apply_filters( 'lifterlms_' . $action . '_title', $title ); ?>
			</h3>
		<?php endif; ?>

		<?php if ( $more && 'my_courses' === $action && $courses['results'] ) { ?>
			<footer class="llms-sd-section-footer push-right">
				<a class="llms-button-secondary" href="<?php echo esc_url( $more['url'] ); ?>">
					<?php esc_html_e( 'View All', 'buddyboss-theme' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</footer>
		<?php } elseif ( $more && 'my_achievements' === $action && $achievements ) { ?>
			<footer class="llms-sd-section-footer push-right">
				<a class="llms-button-secondary" href="<?php echo esc_url( $more['url'] ); ?>">
					<?php esc_html_e( 'View All', 'buddyboss-theme' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</footer>
		<?php } elseif ( $more && 'my_certificates' === $action && $certificates ) { ?>
			<footer class="llms-sd-section-footer push-right">
				<a class="llms-button-secondary" href="<?php echo esc_url( $more['url'] ); ?>">
					<?php esc_html_e( 'View All', 'buddyboss-theme' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</footer>
		<?php } ?>

	</div>

	<?php do_action( 'lifterlms_before_' . $action ); ?>

	<?php

	if ( ! $courses['results'] && 'my_courses' === $action ) {
		?>
		<div class="llms-sd-section__blank">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/my-courses.svg" alt="Courses" />
			<p><?php echo apply_filters( 'lifterlms_no_courses_text', __( 'You do not have any courses yet.', 'buddyboss-theme' ) ); ?></p>
		</div>
		<?php
	} else if ( ! $memberships && 'my_memberships' === $action ) {
		?>
		<div class="llms-sd-section__blank">
			<img src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/my-membership.svg" alt="Membership" />
			<p><?php echo apply_filters( 'lifterlms_no_memberships_text', __( 'You do not have any memberships yet.', 'buddyboss-theme' ) ); ?></p>
		</div>
		<?php
	} else {
		echo $content;
	}

    ?>

	<?php do_action( 'lifterlms_after_' . $action ); ?>

</section>
