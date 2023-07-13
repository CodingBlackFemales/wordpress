<?php
/**
 * View: Pricing Closed.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

// This filter is documented in themes/ld30/templates/components/infobar/group.php.
$label = apply_filters( 'learndash_no_price_price_label', __( 'Closed', 'learndash' ) );
?>
<span class="ld-pricing__main-price">
	<span class="ld-pricing__note">
		<?php echo esc_html( $label ); ?>
	</span>
</span>
