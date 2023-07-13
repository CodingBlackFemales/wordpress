<?php
/**
 * View: Grid icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--grid' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
	<rect x="4" y="4" width="7.19997" height="7.19997" rx="1" fill="currentColor"/>
	<rect x="4" y="12.7999" width="7.19997" height="7.19997" rx="1" fill="currentColor"/>
	<rect x="12.8027" y="12.7999" width="7.19997" height="7.19997" rx="1" fill="currentColor"/>
	<rect x="12.8027" y="4" width="7.19997" height="7.19997" rx="1" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
