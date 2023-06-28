<?php
/**
 * View: Lesson icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--lesson' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 4C6.28587 4 5.84075 4.17744 5.51256 4.49329C5.18438 4.80914 5 5.23753 5 5.68421V18.3158C5 18.7625 5.18437 19.1909 5.51256 19.5067C5.84075 19.8226 6.28587 20 6.75 20H17.25C17.7141 20 18.1592 19.8226 18.4874 19.5067C18.8156 19.1909 19 18.7625 19 18.3158V5.68421C19 5.23753 18.8156 4.80915 18.4874 4.49329C18.1592 4.17744 17.7141 4 17.25 4H6.75ZM6.75 5.68421H17.25L17.25 18.3158H6.75L6.75 5.68421ZM9.37524 8.21051C8.89199 8.21051 8.50024 8.58753 8.50024 9.05262C8.50024 9.5177 8.89199 9.89472 9.37524 9.89472L14.6252 9.89472C15.1085 9.89472 15.5002 9.5177 15.5002 9.05262C15.5002 8.58753 15.1085 8.21051 14.6252 8.21051L9.37524 8.21051ZM9.37524 11.5789C8.89199 11.5789 8.50024 11.956 8.50024 12.421C8.50024 12.8861 8.89199 13.2631 9.37524 13.2631H14.6252C15.1085 13.2631 15.5002 12.8861 15.5002 12.421C15.5002 11.956 15.1085 11.5789 14.6252 11.5789H9.37524ZM9.37524 14.9474C8.89199 14.9474 8.50024 15.3244 8.50024 15.7895C8.50024 16.2545 8.89199 16.6316 9.37524 16.6316L14.6252 16.6316C15.1085 16.6316 15.5002 16.2545 15.5002 15.7895C15.5002 15.3244 15.1085 14.9474 14.6252 14.9474H9.37524Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
