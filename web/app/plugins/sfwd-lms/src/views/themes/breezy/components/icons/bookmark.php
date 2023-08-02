<?php
/**
 * View: Bookmark icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--bookmark' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M7.9 5.6C7.68783 5.6 7.48434 5.68429 7.33431 5.83431C7.18429 5.98434 7.1 6.18783 7.1 6.4V17.6454L11.435 14.549C11.7132 14.3503 12.0868 14.3503 12.365 14.549L16.7 17.6454V6.4C16.7 6.18783 16.6157 5.98434 16.4657 5.83431C16.3157 5.68429 16.1122 5.6 15.9 5.6H7.9ZM6.20294 4.70294C6.65303 4.25286 7.26348 4 7.9 4H15.9C16.5365 4 17.147 4.25286 17.5971 4.70294C18.0471 5.15303 18.3 5.76348 18.3 6.4V19.2C18.3 19.4997 18.1325 19.7742 17.8661 19.9113C17.5996 20.0485 17.2789 20.0252 17.035 19.851L11.9 16.1831L6.76499 19.851C6.52114 20.0252 6.20039 20.0485 5.93393 19.9113C5.66748 19.7742 5.5 19.4997 5.5 19.2V6.4C5.5 5.76348 5.75286 5.15303 6.20294 4.70294Z" fill="#0E0E2C"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
