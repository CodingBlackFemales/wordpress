<?php
/**
 * View: Arrow Left icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--arrow-left' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M12.8571 6.06922C13.3305 5.59586 13.3305 4.82838 12.8571 4.35502C12.3837 3.88166 11.6163 3.88166 11.1429 4.35502L4.35502 11.1429C3.88166 11.6163 3.88166 12.3837 4.35502 12.8571L11.1429 19.645C11.6163 20.1183 12.3837 20.1183 12.8571 19.645C13.3305 19.1716 13.3305 18.4041 12.8571 17.9308L8.13844 13.2121H18.8062C19.4655 13.2121 20 12.6694 20 12C20 11.3306 19.4655 10.7879 18.8062 10.7879H8.13844L12.8571 6.06922Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
