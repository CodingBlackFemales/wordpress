<?php
/**
 * View: Certificate icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--certificate' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M7.81818 9.06122C7.81818 6.7281 9.69045 4.83673 12 4.83673C14.3096 4.83673 16.1818 6.7281 16.1818 9.06122C16.1818 10.5234 15.4464 11.8121 14.3289 12.5705C14.284 12.5946 14.2415 12.6225 14.2017 12.6535C13.5623 13.0543 12.8079 13.2857 12 13.2857C9.69045 13.2857 7.81818 11.3943 7.81818 9.06122ZM14.0217 14.7697C13.39 14.9981 12.7094 15.1224 12 15.1224C11.2909 15.1224 10.6106 14.9982 9.97914 14.77L9.5132 18.3139L11.5323 17.0901C11.8202 16.9156 12.1799 16.9156 12.4678 17.0901L14.4872 18.3141L14.0217 14.7697ZM8.2712 13.8101C6.88736 12.6998 6 10.9852 6 9.06122C6 5.7137 8.68629 3 12 3C15.3137 3 18 5.7137 18 9.06122C18 10.9848 17.113 12.699 15.7297 13.8094L16.5376 19.9608C16.5835 20.3104 16.4273 20.6557 16.1355 20.8492C15.8437 21.0428 15.468 21.0506 15.1687 20.8691L12.0001 18.9485L8.83143 20.8691C8.53209 21.0506 8.15634 21.0428 7.86458 20.8492C7.57281 20.6556 7.41656 20.3103 7.46253 19.9607L8.2712 13.8101Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
