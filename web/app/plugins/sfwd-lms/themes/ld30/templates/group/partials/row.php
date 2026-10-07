<?php
/**
 * LearnDash LD30 Displays a single lesson row that appears in the group course content listing
 *
 * Available Variables:
 * WIP
 *
 * @since 3.2.0
 * @version 4.21.3
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Populate a list of topics and quizzes for this lesson
 *
 * @var $topics [array]
 * @var $quizzes [array]
 * @since 3.2.0
 */
$attributes    = '';
$content_count = 0;

// Fallbacks.
$count = ( isset( $count ) ? $count : 0 );

/**
 * Filter Group row attributes.
 *
 * @since 3.2.0
 * @since 4.21.3 No longer used to set a tooltip message.
 *
 * @param string $attributes Attributes to add to the row.
 * @param int    $course_id  Course ID.
 * @param int    $group_id   Group ID.
 * @param int    $user_id    User ID.
 *
 * @return string
 */
$attributes = apply_filters( 'learndash_group_course_row_atts', '', $course->ID, $group_id, $user_id );

/**
 * Action to add custom content before a row
 *
 * @since 3.2.0
 *
 * @param int $course_id Course ID.
 * @param int $group_id  Group ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash_group_access_row_before', $course->ID, $group_id, $user_id );

$group_course_row_class = 'ld-item-list-item ld-expandable ld-item-lesson-item ld-lesson-item-' . $course->ID;

?>

<div
	class="<?php echo $group_course_row_class; ?>"
	id="<?php echo esc_attr( 'ld-expand-' . $course->ID ); ?>"
	<?php echo wp_kses_post( $attributes ); ?>
>
	<div class="ld-item-list-item-preview">
		<div class="ld-tooltip">
			<?php
			/**
			 * Action to add custom content before lesson title
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $group_id Group ID.
			 * @param int $user_id User ID.
			 *
			 * @return void
			 */
			do_action( 'learndash-lesson-row-title-before', $course->ID, $group_id, $user_id );
			?>

			<a
				<?php if ( isset( $has_access ) && ! $has_access ) : ?>
					aria-describedby="ld-group-course__row-tooltip--<?php echo esc_attr( $course->ID ); ?>"
				<?php endif; ?>
				class="ld-item-name ld-primary-color-hover"
				href="<?php echo get_permalink( $course->ID ); ?>"
			>
				<?php
				$course_status = learndash_course_status( $course->ID, $user_id, true );
				learndash_status_icon( $course_status, get_post_type(), null, true );
				?>
				<div class="ld-item-title">
				<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					echo wp_kses_post( apply_filters( 'the_title', $course->post_title, $course->ID ) );
				?>
				</div> <!--/.ld-item-title-->
			</a>

			<?php
			if (
				isset( $has_access )
				&& ! $has_access
			) :
			?>
				<div
					class="ld-tooltip__text"
					id="ld-group-course__row-tooltip--<?php echo esc_attr( $course->ID ); ?>"
					role="tooltip"
				>
					<?php echo esc_html__( "You don't currently have access to this content", 'learndash' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Action to add custom content after lesson title
		 *
		 * @since 3.2.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $group_id  Group ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash_group_course_row_title_after', $course->ID, $group_id, $user_id );
		?>

		<div class="ld-item-details">
					</div> <!--/.ld-item-details-->

		<?php
		/**
		 * Action to add custom content after the attribute bubbles
		 *
		 * @since 3.2.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $group_id  Group ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash_group_course_row_attributes_after', $course->ID, $group_id, $user_id );
		?>

	</div> <!--/.ld-item-list-item-preview-->
</div> <!--/.ld-item-list-item-->
	<?php
	/**
	 * Action to add custom content after a row
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $group_id  Group ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash_group_course_row_after', $course->ID, $group_id, $user_id ); ?>
