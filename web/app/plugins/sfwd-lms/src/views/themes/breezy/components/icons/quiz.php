<?php
/**
 * View: Quiz icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--quiz' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M10.2502 4C9.28372 4 8.50021 4.71634 8.50021 5.6V6.4C8.50021 7.28366 9.28372 8 10.2502 8H13.7502C14.7167 8 15.5002 7.28366 15.5002 6.4V5.6C15.5002 4.71634 14.7167 4 13.7502 4H10.2502ZM10.2502 5.6H13.7502V6.4H10.2502V5.6ZM6.75 4.8C6.28587 4.8 5.84075 4.96857 5.51256 5.26863C5.18438 5.56869 5 5.97565 5 6.4V18.4C5 18.8243 5.18437 19.2313 5.51256 19.5314C5.84075 19.8314 6.28587 20 6.75 20H17.25C17.7141 20 18.1592 19.8314 18.4874 19.5314C18.8156 19.2313 19 18.8243 19 18.4V6.4C19 5.97565 18.8156 5.56869 18.4874 5.26863C18.1592 4.96857 17.7141 4.8 17.25 4.8C16.7668 4.8 16.375 5.15817 16.375 5.6C16.375 6.04183 16.7668 6.4 17.25 6.4L17.25 18.4H6.75L6.75 6.4C7.23325 6.4 7.625 6.04183 7.625 5.6C7.625 5.15817 7.23325 4.8 6.75 4.8ZM13.8666 14.3C14.0875 14.6314 14.0278 15.1015 13.7333 15.35L11.9556 16.85C11.7185 17.05 11.3926 17.05 11.1556 16.85L10.2667 16.1C9.97215 15.8515 9.91246 15.3814 10.1334 15.05C10.3543 14.7187 10.7721 14.6515 11.0667 14.9L11.5556 15.3125L12.9333 14.15C13.2279 13.9015 13.6457 13.9687 13.8666 14.3ZM13.7333 11.35C14.0278 11.1015 14.0875 10.6314 13.8666 10.3C13.6457 9.96867 13.2279 9.90152 12.9333 10.15L11.5556 11.3125L11.0667 10.9C10.7721 10.6515 10.3543 10.7187 10.1334 11.05C9.91246 11.3814 9.97215 11.8515 10.2667 12.1L11.1556 12.85C11.3926 13.05 11.7185 13.05 11.9556 12.85L13.7333 11.35Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
