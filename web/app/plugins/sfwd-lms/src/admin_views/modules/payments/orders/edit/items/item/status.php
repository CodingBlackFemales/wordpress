<?php
/**
 * Template: Order item enrollment status.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Transaction $transaction Order object.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

$product  = $transaction->get_product();
$customer = $transaction->get_user();
?>
<div class="ld-order-items__status-text">
	<?php
	if ( $product ) {
		echo esc_html(
			$product->user_has_access( $customer )
				? __( 'Enrolled', 'learndash' )
				: (
					$product->is_pre_ordered( $customer )
						? __( 'Pre-Enrolled', 'learndash' )
						: __( 'Not Enrolled', 'learndash' )
				)
		);
	}
	?>
</div>

<div class="ld-order-items__edit-access">
	<?php if ( $product ) : ?>
		<a href="<?php echo esc_url( get_edit_user_link( $customer->ID ) ); ?>" class="ld-order-items__edit-access-link">
			<?php
			printf(
				// Translators: %s is the product type label (course or group).
				esc_html__( 'Edit %s access', 'learndash' ),
				esc_html(
					$product->get_type() === LDLMS_Post_Types::COURSE
						? learndash_get_custom_label_lower( 'course' )
						: learndash_get_custom_label_lower( 'group' ),
				)
			);
			?>
		</a>
	<?php endif; ?>
</div>
