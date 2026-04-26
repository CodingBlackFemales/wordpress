<?php
/**
 * Template for error and success Notices.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var string $message Message Text.
 * @var string $type    Notice Type.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

?>

<div class="learndash-course-reviews-notice callout <?php echo esc_attr( $type ); ?>" data-closable>

	<?php echo wp_kses_post( $message ); ?>

	<button class="close-button" aria-label="<?php esc_attr_e( 'Close notice', 'learndash' ); ?>" type="button" data-close>
		<span aria-hidden="true">&times;</span>
	</button>

</div>
