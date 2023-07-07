<?php
/**
 * View: Check icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--check' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M19.63 6.37658C20.1233 6.87868 20.1233 7.69275 19.63 8.19485L10.3669 17.6234C9.87358 18.1255 9.07379 18.1255 8.5805 17.6234L4.36997 13.3377C3.87668 12.8356 3.87668 12.0215 4.36997 11.5194C4.86326 11.0173 5.66305 11.0173 6.15635 11.5194L9.47368 14.896L17.8437 6.37658C18.3369 5.87447 19.1367 5.87447 19.63 6.37658Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
