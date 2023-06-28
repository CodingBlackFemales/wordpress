<?php
/**
 * View: Body.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var bool   $show_content Whether to show content.
 * @var string $content      Body content.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

if ( ! $show_content ) {
	return;
}
?>
<div class="ld-quiz__body">
	<?php echo $content; // phpcs:ignore ?>
</div>
