<?php
/**
 * View: Slash icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--slash' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.77778C8.56356 5.77778 5.77778 8.56356 5.77778 12C5.77778 15.4364 8.56356 18.2222 12 18.2222C15.4364 18.2222 18.2222 15.4364 18.2222 12C18.2222 8.56356 15.4364 5.77778 12 5.77778ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M6.34403 6.34386C6.69116 5.99672 7.25398 5.99672 7.60111 6.34386L17.6562 16.399C18.0034 16.7461 18.0034 17.3089 17.6562 17.656C17.3091 18.0032 16.7463 18.0032 16.3991 17.656L6.34403 7.60094C5.9969 7.2538 5.9969 6.69099 6.34403 6.34386Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
