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
<path fill-rule="evenodd" clip-rule="evenodd" d="M19.596 4.40399C20.1347 4.94264 20.1347 5.81598 19.596 6.35463L6.35463 19.596C5.81598 20.1347 4.94264 20.1347 4.40399 19.596C3.86534 19.0574 3.86534 18.184 4.40399 17.6454L17.6454 4.40399C18.184 3.86534 19.0574 3.86534 19.596 4.40399Z" fill="currentColor"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M4.40399 4.40399C4.94264 3.86534 5.81598 3.86534 6.35463 4.40399L19.596 17.6454C20.1347 18.184 20.1347 19.0574 19.596 19.596C19.0574 20.1347 18.184 20.1347 17.6454 19.596L4.40399 6.35463C3.86534 5.81598 3.86534 4.94264 4.40399 4.40399Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
