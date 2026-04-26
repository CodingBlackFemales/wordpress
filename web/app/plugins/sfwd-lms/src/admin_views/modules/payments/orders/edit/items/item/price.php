<?php
/**
 * Template: Order item price.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Transaction $transaction     Order object.
 * @var Template    $this            Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;
?>
<?php if ( $transaction->has_trial() ) : ?>
	<span class="ld-order-items__price-status"><?php esc_html_e( 'Trial Price', 'learndash' ); ?></span>
<?php endif; ?>

<?php if ( $transaction->has_coupon() ) : ?>
	<span class="ld-order-items__old-price">
		<?php
		echo esc_html(
			learndash_get_price_formatted(
				$transaction->get_pricing()->price
			)
		);
		?>
	</span>
<?php endif; ?>

<span class="ld-order-items__price">
	<?php echo esc_html( $transaction->get_formatted_price() ); ?>
</span>
