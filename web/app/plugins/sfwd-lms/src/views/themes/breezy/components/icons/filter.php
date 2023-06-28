<?php
/**
 * View: Filter icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--filter' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M4.10552 5.71855C4.32438 5.28117 4.76209 5 5.26234 5H18.7689C19.2379 5 19.6756 5.28117 19.8945 5.71855C20.0821 6.15593 20.0195 6.68703 19.7069 7.06192L14.0166 14.0287V17.9964C14.0166 18.4025 13.7978 18.7462 13.4538 18.9024C13.1099 19.0586 12.7035 19.0273 12.3908 18.8086L10.3898 17.3091C10.1397 17.1216 10.0147 16.8404 10.0147 16.4968V14.0287L4.29311 7.06192C3.98046 6.68703 3.91793 6.15593 4.10552 5.71855Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
