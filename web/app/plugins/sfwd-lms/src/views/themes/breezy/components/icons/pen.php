<?php
/**
 * View: Pen icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--pen' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M18.5294 4.4321C17.9519 3.85494 17.03 3.85814 16.4592 4.42865L16.4588 4.42864L6.07617 14.8114L9.18959 17.9247L19.5729 7.5419C20.1444 6.9704 20.142 6.04462 19.5692 5.47213L18.5294 4.4321ZM5.55683 15.3301L4 20.0001L8.67025 18.4433L5.55683 15.3301Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
