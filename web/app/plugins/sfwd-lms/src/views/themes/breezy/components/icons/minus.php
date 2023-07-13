<?php
/**
 * View: Minus icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--minus' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M19 10.838C19.5523 10.838 20 11.2857 20 11.838V12.162C20 12.7143 19.5523 13.162 19 13.162H5C4.44772 13.162 4 12.7143 4 12.162V11.838C4 11.2857 4.44772 10.838 5 10.838H19Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
