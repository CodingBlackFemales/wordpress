<?php
/**
 * View: Award 2 icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--award2' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M18.8654 5.40357H17.0056V4H6.99368V5.40357H5.1339C4.50862 5.40357 4 5.91219 4 6.53748V7.65102C4 9.70986 5.40441 11.4425 7.30441 11.9537C7.86942 13.4927 9.1654 14.6742 10.7745 15.0798V17.0961H10.4356C9.08208 17.0961 7.98482 18.1933 7.98482 19.5469H16.0154C16.0154 18.1933 14.9182 17.0961 13.5646 17.0961H13.2255V15.0798C14.8345 14.6741 16.1305 13.4927 16.6956 11.9537C18.5955 11.4425 20 9.70974 20 7.65102V6.53748C20 5.91219 19.4914 5.40357 18.8661 5.40357H18.8654ZM5.22515 7.65099V6.62903H6.99335V10.2314C6.99335 10.3317 7.0025 10.4297 7.00829 10.5285C5.95465 9.99465 5.22501 8.91078 5.22501 7.65108L5.22515 7.65099ZM13.6525 11.3012L11.9993 10.4322L10.3462 11.3012L10.6619 9.46046L9.32448 8.15689L11.1727 7.8883L11.9992 6.21361L12.8257 7.8883L14.6739 8.15689L13.3365 9.46046L13.6522 11.3012H13.6525ZM18.7738 7.65099C18.7738 8.91069 18.0442 9.99471 16.9905 10.5284C16.9963 10.4296 17.0055 10.3316 17.0055 10.2313V6.62894H18.7737V7.6509L18.7738 7.65099Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
