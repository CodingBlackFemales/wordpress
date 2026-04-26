<?php
/**
 * View: Group Enrollment Access King Label
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product  $product Product model.
 * @var WP_User  $user    WP_User object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
?>
<span class="ld-enrollment__king-label">
	<?php if ( $product->is_pre_ordered( $user ) ) : ?>
		<?php esc_html_e( 'Schedule', 'learndash' ); ?>
	<?php else : ?>
		<?php esc_html_e( 'Access', 'learndash' ); ?>
	<?php endif; ?>
</span>
