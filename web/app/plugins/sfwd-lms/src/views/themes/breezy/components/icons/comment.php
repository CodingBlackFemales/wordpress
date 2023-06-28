<?php
/**
 * View: Comment icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--comment' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M18.0121 4.50004H5.98794C4.87272 4.50004 4 5.40797 4 6.51239V14.4878C4 15.6167 4.89692 16.5002 5.98794 16.5002H6.86066C7.15152 16.5002 7.36984 16.7456 7.36984 17.0156V18.9788C7.36984 19.4205 7.87901 19.6659 8.2184 19.3715L11.5152 16.6472C11.6121 16.5736 11.7333 16.5245 11.8545 16.5245H18.0121C19.1273 16.5245 20 15.6166 20 14.5121V6.51218C20 5.40793 19.1029 4.50004 18.0121 4.50004Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
