<?php
/**
 * LearnDash LD30 Displays course list
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

$course_id = (int) $shortcode_atts['course_id'];

if ( is_user_logged_in() ) {
	$user_id = get_current_user_id();
} else {
	$user_id = false;
}
?>

<div class="learndash-wrapper">
	<div class="ld-item-list">
		<div class="ld-item-list-item">
			<div class="ld-item-list-item-preview">
				<?php
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) !== 'yes' && $shortcode_atts['post_type'] === 'groups' ) {
					echo esc_html( get_the_title() );
				} else {
					?>
					<a class="ld-item-name ld-primary-color-hover" href="<?php echo esc_url( learndash_get_step_permalink( get_the_ID() ) ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
					<?php
				}
				?>
			</div>
		</div>
	</div>

	<?php
	switch ( get_post_type() ) {

		case ( 'sfwd-courses' ):
				$wrapper = array(
					'<div class="learndash-wrapper">
                        <div class="ld-item-list">',
					'</div>
                    </div>',
				);

				$output = learndash_get_template_part(
					'/course/partials/row.php',
					array(
						'course_id' => $course_id,
						'user_id'   => $user_id,
					)
				);


			break;

		case ( 'sfwd-lessons' ):
			global $course_lessons_results;

			if ( isset( $course_lessons_results['pager'] ) ) :
				learndash_get_template_part(
					'modules/pagination.php',
					array(
						'pager_results' => $course_lessons_results['pager'],
						'pager_context' => 'course_lessons',
					),
					true
				);
			endif;

			break;

		case ( 'sfwd-topic' ):
			$wrapper = array(
				'<div class="learndash-wrapper">
                    <div class="ld-item-list">',
				'</div>
                </div>',
			);

			$output = learndash_get_template_part(
				'/topic/partials/row.php',
				array(
					'topic'     => $post,
					'course_id' => $course_id,
					'user_id'   => $user_id,
				)
			);

			break;
	}
	?>
</div>
