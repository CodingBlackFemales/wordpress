<?php
/**
 * View: Materials icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--materials' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 4C6.28587 4 5.84075 4.17744 5.51256 4.49329C5.18438 4.80914 5 5.23753 5 5.68421V18.3158C5 18.7625 5.18437 19.1909 5.51256 19.5067C5.84075 19.8226 6.28587 20 6.75 20H17.25C17.7141 20 18.1592 19.8226 18.4874 19.5067C18.8156 19.1909 19 18.7625 19 18.3158V5.68421C19 5.23753 18.8156 4.80915 18.4874 4.49329C18.1592 4.17744 17.7141 4 17.25 4H12.8748H9.37484H6.75ZM8.49984 5.68421H6.75L6.75 18.3158H17.25L17.25 5.68421H13.7498V11.5789C13.7498 11.9195 13.5367 12.2266 13.2097 12.357C12.8827 12.4873 12.5064 12.4152 12.2561 12.1744L11.1248 11.0857L9.99356 12.1744C9.74331 12.4152 9.36696 12.4873 9.03999 12.357C8.71303 12.2266 8.49984 11.9195 8.49984 11.5789V5.68421ZM11.9998 5.68421H10.2498V9.54593L10.5061 9.29928C10.8478 8.97042 11.4018 8.97042 11.7436 9.29928L11.9998 9.54593V5.68421Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
