<?php
/**
 * View: Image icon.
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

$icon_classes = [ 'ld-icon', 'ld-icon--image' ];

if ( ! empty( $classes ) ) {
	$icon_classes = array_merge( $icon_classes, $classes );
}

$aria_hidden = $aria_hidden ?? true;
?>
<?php $this->template( 'components/icons/icon/start', compact( 'icon_classes', 'aria_hidden' ) ); ?>
<path fill-rule="evenodd" clip-rule="evenodd" d="M6.53659 5.95122C6.2133 5.95122 5.95122 6.2133 5.95122 6.53659V17.4634C5.95122 17.6714 6.0597 17.8541 6.22317 17.9579L14.4319 9.74918C14.8129 9.36818 15.4306 9.36818 15.8116 9.74918L18.0488 11.9864V6.53659C18.0488 6.2133 17.7867 5.95122 17.4634 5.95122H6.53659ZM18.0488 14.7458L15.1217 11.8188L8.89172 18.0488H17.4634C17.7867 18.0488 18.0488 17.7867 18.0488 17.4634V14.7458ZM6.54257 20H17.4634C18.8643 20 20 18.8643 20 17.4634V6.53659C20 5.13567 18.8643 4 17.4634 4H6.53659C5.13567 4 4 5.13567 4 6.53659V17.4634C4 18.8616 5.13124 19.9956 6.52838 20C6.53111 20 6.53385 20 6.53659 20H6.54257ZM9.26787 9.07318C9.16011 9.07318 9.07275 9.16054 9.07275 9.2683C9.07275 9.37607 9.16011 9.46343 9.26787 9.46343C9.37563 9.46343 9.46299 9.37607 9.46299 9.2683C9.46299 9.16054 9.37563 9.07318 9.26787 9.07318ZM7.12153 9.2683C7.12153 8.08291 8.08248 7.12196 9.26787 7.12196C10.4533 7.12196 11.4142 8.08291 11.4142 9.2683C11.4142 10.4537 10.4533 11.4146 9.26787 11.4146C8.08248 11.4146 7.12153 10.4537 7.12153 9.2683Z" fill="currentColor"/>
<?php $this->template( 'components/icons/icon/end' ); ?>
