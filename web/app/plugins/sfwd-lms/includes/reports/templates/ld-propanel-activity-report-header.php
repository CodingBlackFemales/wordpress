<?php
/**
 * Activity Report Download Header
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<button class="button button-primary download-activity" data-template="activity-courses" data-nonce="<?php echo wp_create_nonce( 'learndash-data-reports-user-courses-' . get_current_user_id() ); ?>" data-slug="user-courses" type="button" title="
																												<?php
																												printf(
																												// translators: Export Course Data.
																													esc_html_x( 'Export %s Data', 'Export Course Data', 'learndash' ),
																													esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
																												);
																												?>
	"><span class="dashicons dashicons-download"></span>
	<?php
	echo esc_html( LearnDash_Custom_Label::get_label( 'course' ) );
	?>
		<span class="status"></span></button>
<button class="button button-primary download-activity" data-template="activity-quizzes" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-data-reports-user-quizzes-' . get_current_user_id() ) ); ?>" data-slug="user-quizzes" type="button" title="
																												<?php
																												printf(
																												// translators: Export Quiz Data.
																													esc_html_x( 'Export %s Data', 'Export Quiz Data', 'learndash' ),
																													esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) )
																												);
																												?>
	"><span class="dashicons dashicons-download"></span>
	<?php
	echo esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) );
	?>
		<span class="status"></span></button>
