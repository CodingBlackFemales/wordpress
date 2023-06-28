<?php
/**
 * View: Caret Up icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--caret-up' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M19.6653 17.6339C19.2533 18.0845 18.605 18.1192 18.1567 17.7379L18.049 17.6339L12 11.0187L5.95098 17.6339C5.539 18.0845 4.89073 18.1191 4.4424 17.7379L4.33474 17.6339C3.92275 17.1833 3.89106 16.4742 4.23966 15.9839L4.33474 15.8661L11.1919 8.36612C11.6039 7.91551 12.2521 7.88085 12.7005 8.26213L12.8081 8.36612L19.6653 15.8661C20.1116 16.3543 20.1116 17.1457 19.6653 17.6339Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
