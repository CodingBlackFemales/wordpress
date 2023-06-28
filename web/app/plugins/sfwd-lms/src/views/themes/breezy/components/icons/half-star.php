<?php
/**
 * View: Half Star icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--half-star' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M12 4L9.32174 9.32174L4 9.98261L7.65217 14.1217L6.64348 20L12 17.3565L17.3565 20L16.3478 14.1217L20 9.98261L14.6783 9.32174L12 4ZM12 5.98261L14.087 10.1565L18.2261 10.6783L15.4087 13.8435L16.1739 18.4348L12 16.3478V5.98261Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
