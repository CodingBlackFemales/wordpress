<?php
/**
 * View: Progress Bar (Bar only).
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var int $value Progress bar value.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

?>
<div class="ld-progress-bar__bar" aria-labelledby="ld-progress-bar-heading">
	<div class="ld-progress-bar__bar-inner"  style="--ld-progress-bar-value: <?php echo esc_attr( $value . '%' ); ?>"></div>
</div>

