<?php
/**
 * View: Step List Item Start.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step $step  Step.
 * @var int  $depth Depth.
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
<?php if ( $step->is_section() ) : ?>
	<li class="ld-steps__item ld-steps__item--section">
<?php elseif ( 0 === $depth ) : ?>
	<li class="ld-steps__item ld-steps__item--parent">
<?php else : ?>
	<li class="ld-steps__item ld-steps__item--child">
<?php endif; ?>
