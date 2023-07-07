<?php
/**
 * View: Search icon.
 *
 * @since   4.6.0
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

$icon_classes = [ 'ld-icon', 'ld-icon--search' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M5.95118 11.2194C5.95118 8.30983 8.30983 5.95118 11.2194 5.95118C14.1289 5.95118 16.4876 8.30983 16.4876 11.2194C16.4876 12.6352 15.9291 13.9206 15.0203 14.8672C14.9926 14.8895 14.9658 14.9135 14.94 14.9392C14.9143 14.965 14.8902 14.9918 14.8679 15.0196C13.9212 15.9288 12.6356 16.4876 11.2194 16.4876C8.30983 16.4876 5.95118 14.1289 5.95118 11.2194ZM15.5883 16.9672C14.3754 17.8905 12.8614 18.4387 11.2194 18.4387C7.23222 18.4387 4 15.2065 4 11.2194C4 7.23222 7.23222 4 11.2194 4C15.2065 4 18.4387 7.23222 18.4387 11.2194C18.4387 12.861 17.8908 14.3746 16.9679 15.5874L19.7148 18.3342C20.0958 18.7152 20.0958 19.3329 19.7148 19.7139C19.3338 20.0949 18.7161 20.0949 18.3351 19.7139L15.5883 16.9672Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
