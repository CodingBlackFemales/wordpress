<?php
/**
 * View: Pricing Free.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Product $product Product model.
 * @var WP_User $user    User.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Product;

// This filter is documented in themes/ld30/templates/components/infobar/group.php.
$label = apply_filters( 'learndash_no_price_price_label', __( 'Free', 'learndash' ) );
?>
<span class="ld-pricing__main-price">
	<span class="ld-pricing__note">
		<?php echo esc_html( $label ); ?>
	</span>
</span>
