<?php
/**
 * View: Award 3 icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--award3' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M18.5303 4.5734C18.4397 4.40157 18.3026 4.25718 18.134 4.15613C17.9654 4.05499 17.7718 4.00105 17.5741 4H14.4532C14.0048 4.00337 13.5868 4.22205 13.3352 4.585L12.333 6.00834L11.3307 4.585C11.0792 4.22205 10.6612 4.00336 10.2127 4H7.09186C6.89415 4.00105 6.70049 4.05499 6.53191 4.15613C6.36332 4.25716 6.22625 4.40155 6.13564 4.5734C6.03613 4.75511 5.98976 4.96018 6.00189 5.16608C6.0139 5.37197 6.08381 5.57054 6.20388 5.74004L9.44222 10.3517C9.90672 10.097 10.4044 9.90522 10.9217 9.78165L7.93207 5.51498H10.1052L13.0145 9.64653C13.7898 9.73314 14.5395 9.96995 15.2201 10.3431L18.4687 5.73331C18.5864 5.56392 18.6543 5.36617 18.6651 5.16167C18.676 4.95717 18.6292 4.75361 18.5301 4.57328L18.5303 4.5734ZM14.3597 8.88829L13.274 7.34498L14.5626 5.51158H16.7339L14.3597 8.88829ZM17.0441 15.3883C17.0427 14.1668 16.5454 12.9958 15.6613 12.1329C14.7772 11.27 13.5788 10.7857 12.3298 10.7866C11.0808 10.7875 9.8831 11.2734 9.0005 12.1376C8.11775 13.0019 7.62211 14.1735 7.62247 15.3951C7.62295 16.6166 8.11955 17.7879 9.00287 18.6513C9.88632 19.5149 11.0843 20 12.3334 20C13.5828 20 14.7811 19.5146 15.6646 18.6507C16.548 17.7868 17.0444 16.6151 17.0444 15.3933L17.0441 15.3883ZM14.3955 15.125L13.603 15.8799L13.7905 16.945H13.7904C13.8064 17.0388 13.7796 17.1347 13.7169 17.2076C13.6543 17.2804 13.5621 17.3226 13.4649 17.3233C13.4108 17.3227 13.3576 17.3096 13.3098 17.2849L12.3332 16.7833L11.3548 17.2833C11.2447 17.3398 11.1114 17.331 11.01 17.2606C10.9086 17.1903 10.8562 17.0703 10.8741 16.95L11.0616 15.8849L10.2708 15.125C10.1804 15.0385 10.1483 14.9092 10.1883 14.7918C10.2281 14.6745 10.333 14.5896 10.4583 14.5733L11.5525 14.4184L12.0416 13.4501L12.0418 13.45C12.0974 13.3395 12.2124 13.2696 12.3383 13.2696C12.4642 13.2696 12.5791 13.3395 12.6348 13.45L13.1239 14.4183L14.2181 14.5732L14.2182 14.5733C14.3429 14.5907 14.4467 14.676 14.4859 14.7931C14.525 14.9103 14.4925 15.039 14.4023 15.125L14.3955 15.125Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
