<?php
/**
 * View: Caret Down icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--caret-down' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M4.33474 8.36612C4.74672 7.91551 5.39498 7.88085 5.84331 8.26213L5.95098 8.36612L12 14.9812L18.049 8.36612C18.461 7.91551 19.1093 7.88085 19.5576 8.26213L19.6653 8.36612C20.0772 8.81672 20.1089 9.52576 19.7603 10.0161L19.6653 10.1339L12.8081 17.6339C12.3961 18.0845 11.7479 18.1192 11.2995 17.7379L11.1919 17.6339L4.33474 10.1339C3.88842 9.64573 3.88842 8.85427 4.33474 8.36612Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
