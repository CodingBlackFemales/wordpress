<?php
/**
 * LearnDash LD30 Displays content of Group
 *
 * Available Variables:
 * $group_id				: (int) ID of the group
 * $group					: (object) Post object of the group
 * $user_id					: Current User ID
 * $group_courses			: (array) Courses in the group
 * $group_status			: Group Status
 * $has_access				: User has access to course or is enrolled.
 * $has_group_content		: Group has course content
 * 
 * @since 4.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ld-item-list ld-lesson-list">
	<div class="ld-section-heading">
		<h2>
		<?php
		printf(
			// translators: placeholders: Group, Courses.
			esc_html_x( '%1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
			esc_attr( LearnDash_Custom_Label::get_label( 'group' ) ),
			esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) )
		);
		?>
		</h2>

		<?php if ( true === $has_access ) { ?>
		<div class="ld-item-list-actions" data-ld-expand-list="true">
			<?php
			// Only display if there is something to expand.
			if ( ( isset( $group_courses ) ) && ( ! empty( $group_courses ) ) ) {
				?>
				<div class="ld-expand-button ld-primary-background" id="<?php echo esc_attr( 'ld-expand-button-' . $group_id ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $group_id ); ?>" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>">
					<span class="ld-icon-arrow-down ld-icon"></span>
					<span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>
				</div> <!--/.ld-expand-button-->
				<?php
			}
			?>
		</div> <!--/.ld-item-list-actions-->
		<?php } ?>
	</div> <!--/.ld-section-heading-->
	<?php
	SFWD_LMS::get_template(
		'group/listing.php',
		array(
			'group_id'             => $group_id,
			'user_id'              => $user_id,
			'group_courses'        => $group_courses,
			'has_access'           => $has_access,
			'course_pager_results' => $course_pager_results,
		),
		true
	);
	?>

</div> <!--/.ld-item-list-->