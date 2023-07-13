<?php
/**
 * View: Caret Right icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--caret-right' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M7.36612 19.6653C6.91551 19.2533 6.88085 18.605 7.26213 18.1567L7.36612 18.049L13.9813 12L7.36612 5.95098C6.91551 5.539 6.88085 4.89073 7.26213 4.4424L7.36612 4.33474C7.81672 3.92275 8.52576 3.89106 9.01612 4.23966L9.13388 4.33474L16.6339 11.1919C17.0845 11.6039 17.1192 12.2521 16.7379 12.7005L16.6339 12.8081L9.13388 19.6653C8.64573 20.1116 7.85427 20.1116 7.36612 19.6653Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
