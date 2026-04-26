<?php
/**
 * Template: Order item name.
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

$product = $transaction->get_product();
?>
<div class="ld-order-items__course-title">
	<?php if ( $product ) : ?>
		<a href="<?php echo esc_url( (string) get_edit_post_link( $product->get_id() ) ); ?>" class="ld-order-items__course-link">
			<?php echo esc_html( $transaction->get_title() ); ?>
		</a>
	<?php else : ?>
		<?php echo esc_html( $transaction->get_title() ); ?>
	<?php endif; ?>
</div>

<div class="ld-order-items__id">
	<?php
	if ( $product ) {
		printf(
			// Translators: %1$s is the product type label (course or group), %2$d is the product ID.
			esc_html__( '%1$s ID: %2$d', 'learndash' ),
			esc_html(
				$product->get_type() === LDLMS_Post_Types::COURSE
					? learndash_get_custom_label( 'course' )
					: learndash_get_custom_label( 'group' )
			),
			esc_html(
				(string) $product->get_id()
			)
		);
	}
	?>
</div>
