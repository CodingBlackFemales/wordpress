<?php
/**
 * View: Icon Opening Tag.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array<int, string> $icon_classes List of classes to add to the icon.
 * @var bool|null          $aria_hidden  Whether to hide the icon from screen readers.
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
<svg
	<?php if ( $aria_hidden ) : ?>
		aria-hidden="true"
	<?php endif; ?>
	class="<?php echo esc_attr( implode( ' ', $icon_classes ) ); ?>"
	width="100%"
	height="100%"
	viewBox="0 0 24 24"
	fill="none"
	xmlns="http://www.w3.org/2000/svg"
>
