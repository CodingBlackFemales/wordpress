<?php
/**
 * View: Clock icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--clock' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 5.77778C8.56356 5.77778 5.77778 8.56356 5.77778 12C5.77778 15.4364 8.56356 18.2222 12 18.2222C15.4364 18.2222 18.2222 15.4364 18.2222 12C18.2222 8.56356 15.4364 5.77778 12 5.77778ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 6.84444C12.4909 6.84444 12.8889 7.24241 12.8889 7.73333V11.4506L15.242 12.6272C15.6811 12.8467 15.859 13.3807 15.6395 13.8197C15.4199 14.2588 14.886 14.4368 14.4469 14.2173L11.6025 12.795C11.3013 12.6445 11.1111 12.3367 11.1111 12V7.73333C11.1111 7.24241 11.5091 6.84444 12 6.84444Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
