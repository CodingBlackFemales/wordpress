<?php
/**
 * View: Play icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--play' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M5.77778 12C5.77778 8.56356 8.56356 5.77778 12 5.77778C15.4364 5.77778 18.2222 8.56356 18.2222 12C18.2222 15.4364 15.4364 18.2222 12 18.2222C8.56356 18.2222 5.77778 15.4364 5.77778 12ZM12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4ZM15.0784 12.342C15.4776 12.111 15.4776 11.5335 15.0784 11.3024L10.5871 8.70353C10.1879 8.47251 9.68889 8.76128 9.68889 9.22331V14.4211C9.68889 14.8832 10.1879 15.1719 10.5871 14.9409L15.0784 12.342Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
