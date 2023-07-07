<?php
/**
 * View: Drag icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--drag' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path d="M12.5719 4.23694C12.4943 4.15938 12.4049 4.10086 12.3095 4.06138L11.9998 4C11.7764 4 11.5742 4.09055 11.4278 4.23694L9.27048 6.39422C8.95455 6.71015 8.95455 7.22237 9.27048 7.53829C9.58641 7.85422 10.0986 7.85422 10.4146 7.53829L11.1908 6.76203V11.191H6.76206L7.53833 10.4148C7.85426 10.0988 7.85426 9.58661 7.53833 9.27069C7.2224 8.95476 6.71018 8.95476 6.39425 9.27069L4.23695 11.428C4.15938 11.5055 4.10086 11.5949 4.06139 11.6903C4.02183 11.7857 4 11.8903 4 12C4 12.1097 4.02183 12.2143 4.06139 12.3097C4.10086 12.4051 4.15938 12.4945 4.23695 12.572L6.39425 14.7293C6.71018 15.0452 7.2224 15.0452 7.53833 14.7293C7.85426 14.4134 7.85426 13.9012 7.53833 13.5852L6.76206 12.809H11.1908V17.238L10.4146 16.4617C10.0986 16.1458 9.58641 16.1458 9.27048 16.4617C8.95455 16.7776 8.95455 17.2899 9.27048 17.6058L11.4278 19.7631C11.7437 20.079 12.2559 20.079 12.5719 19.7631L14.7292 17.6058C15.0451 17.2899 15.0451 16.7776 14.7292 16.4617C14.4132 16.1458 13.901 16.1458 13.5851 16.4617L12.8088 17.238V12.809H17.2376L16.4613 13.5852C16.1454 13.9012 16.1454 14.4134 16.4613 14.7293C16.7772 15.0452 17.2895 15.0452 17.6054 14.7293L19.7487 12.586C19.9035 12.4387 20 12.2306 20 12C20 11.7694 19.9035 11.5613 19.7487 11.414L17.6054 9.27069C17.2895 8.95476 16.7772 8.95476 16.4613 9.27069C16.1454 9.58661 16.1454 10.0988 16.4613 10.4148L17.2376 11.191H12.8088V6.76203L13.5851 7.53829C13.901 7.85422 14.4132 7.85422 14.7292 7.53829C15.0451 7.22237 15.0451 6.71015 14.7292 6.39422L12.5719 4.23694Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
