<?php
/**
 * LearnDash LD30 Displays course navigation quiz row
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = array(
	'container' => 'ld-lesson-item' . ( get_the_ID() === absint( $quiz['post']->ID ) ? ' ld-is-current-lesson ' : '' ) . ( 'completed' === $quiz['status'] ? ' learndash-complete' : ' learndash-incomplete' ),
	'wrapper'   => 'ld-lesson-item-preview' . ( get_the_ID() === absint( $quiz['post']->ID ) ? ' ld-is-current-item ' : '' ),
	'anchor'    => 'ld-lesson-item-preview-heading ld-primary-color-hover',
	'title'     => 'ld-lesson-title',
);

if ( isset( $context ) && 'lesson' === $context ) {
	$classes['container'] = 'ld-table-list-item' . ( 'completed' === $quiz['status'] ? ' learndash-complete' : ' learndash-incomplete' );
	$classes['wrapper']   = 'ld-table-list-item-wrapper';
	$classes['anchor']    = 'ld-table-list-item-preview ld-primary-color-hover' . ( get_the_ID() === absint( $quiz['post']->ID ) ? ' ld-is-current-item ' : '' );
	$classes['title']     = 'ld-topic-title';
}
$attributes = learndash_get_course_step_attributes( $quiz['post']->ID, $course_id, $user_id );

$learndash_quiz_available_date = learndash_course_step_available_date( $quiz['post']->ID, $course_id, $user_id, true );
if ( ! empty( $learndash_quiz_available_date ) ) {
	$classes['wrapper'] .= ' learndash-not-available';
}
?>

<div class="<?php echo esc_attr( $classes['container'] ); ?>">
	<div class="<?php echo esc_attr( $classes['wrapper'] ); ?>">
		<a class="<?php echo esc_attr( $classes['anchor'] ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $quiz['post']->ID, $course_id ) ); ?>">

			<?php learndash_status_icon( $quiz['status'], 'sfwd-quiz', null, true ); ?>

			<div class="<?php echo esc_attr( $classes['title'] ); ?>"><?php
			echo wp_kses_post( apply_filters( 'the_title', $quiz['post']->post_title, $quiz['post']->ID ) ); 
			if ( ! empty( $attributes ) ) :
				foreach ( $attributes as $attribute ) :
					?>
				<span class="ld-status-icon <?php echo esc_attr( $attribute['class'] ); ?>" data-ld-tooltip="<?php echo esc_attr( $attribute['label'] ); ?>"><span class="ld-icon <?php echo esc_attr( $attribute['icon'] ); ?>"></span></span>
					<?php
				endforeach;
			endif;
			?> </div> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
			<!--/.ld-lesson-title-->

		</a> <!--/.ld-lesson-item-preview-heading-->
	</div> <!--/.ld-lesson-item-preview-->
</div>
