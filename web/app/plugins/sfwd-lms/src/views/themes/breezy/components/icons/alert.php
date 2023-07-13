<?php
/**
 * View: Alert icon.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array<int, string>|null $classes     List of classes to add to the icon.
 * @var bool|null               $aria_hidden Whether to hide the icon from screen readers.
 * @var Template                $this        The template instance.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;

$icon_classes = [ 'ld-icon', 'ld-icon--alert' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.88235C8.62132 5.88235 5.88235 8.62132 5.88235 12C5.88235 15.3787 8.62132 18.1176 12 18.1176C15.3787 18.1176 18.1176 15.3787 18.1176 12C18.1176 8.62132 15.3787 5.88235 12 5.88235ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 8.23529C12.5198 8.23529 12.9412 8.65667 12.9412 9.17647V12C12.9412 12.5198 12.5198 12.9412 12 12.9412C11.4802 12.9412 11.0588 12.5198 11.0588 12V9.17647C11.0588 8.65667 11.4802 8.23529 12 8.23529Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.0588 14.8235C11.0588 14.3037 11.4802 13.8824 12 13.8824H12.0082C12.528 13.8824 12.9494 14.3037 12.9494 14.8235C12.9494 15.3433 12.528 15.7647 12.0082 15.7647H12C11.4802 15.7647 11.0588 15.3433 11.0588 14.8235Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
