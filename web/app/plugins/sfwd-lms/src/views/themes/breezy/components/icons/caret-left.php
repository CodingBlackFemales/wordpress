<?php
/**
 * View: Caret Left icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--caret-left' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16.6339 4.33474C17.0845 4.74672 17.1192 5.39498 16.7379 5.84331L16.6339 5.95098L10.0188 12L16.6339 18.049C17.0845 18.461 17.1192 19.1093 16.7379 19.5576L16.6339 19.6653C16.1833 20.0772 15.4742 20.1089 14.9839 19.7603L14.8661 19.6653L7.36612 12.8081C6.91551 12.3961 6.88085 11.7479 7.26213 11.2995L7.36612 11.1919L14.8661 4.33474C15.3543 3.88842 16.1457 3.88842 16.6339 4.33474Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
