<?php
/**
 * View: Step List Start.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step|null $step Step.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Steps\Step;
?>
<?php if ( isset( $step ) ) : ?>
	<ol
		id="ld-steps-list-<?php echo esc_attr( (string) $step->get_id() ); ?>"
		class="ld-steps__list ld-steps__list--child"
		data-steps-total="<?php echo esc_attr( (string) $step->get_steps_number() ); ?>"
		data-steps-sub-steps-page-size="<?php echo esc_attr( (string) $step->get_sub_steps_page_size() ); ?>"
		data-steps-page="2" <?php // 2 is the default page, because the 1st page is always visible. ?>
		data-step-id="<?php echo esc_attr( (string) $step->get_id() ); ?>"
		data-step-parent-id="<?php echo esc_attr( (string) $step->get_parent_id() ); ?>"
		aria-hidden="true"
		aria-labelledby="ld-steps-heading-<?php echo esc_attr( (string) $step->get_id() ); ?>"
	>
<?php else : ?>
	<ol class="ld-steps__list ld-steps__list--parent">
<?php endif; ?>
