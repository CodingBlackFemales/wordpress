<?php
/**
 * View: Step Status.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string $status Status label.
 * @var int    $depth  Step depth.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

?>
<span class="ld-steps__status ld-steps__status--<?php echo esc_attr( 0 === $depth ? 'parent' : 'child' ); ?>">
	<?php echo esc_html( $status ); ?>
</span>
