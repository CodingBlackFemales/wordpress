<?php
/**
 * View: Group Enrollment Registration Button.
 *
 * It is used for the free button when a user is not logged in.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var string $registration_url Registration URL.
 * @var string $button_label     Button label.
 *
 * @package LearnDash\Core
 */

?>
<a class="ld-enrollment__join-button" href="<?php echo esc_url( $registration_url ); ?>">
	<?php echo esc_html( $button_label ); ?>
</a>
