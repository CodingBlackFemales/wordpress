<?php
/**
 * View: Transactions Dashboard Widget Pricing Price.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Transaction $transaction Transaction.
 * @var Template    $this        Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;

$pricing = $transaction->get_pricing();
?>
<span class="ld-dashboard-widget-transactions__label">
	<?php if ( $pricing->discount > 0 ) : ?>
		<?php echo esc_html( learndash_get_price_formatted( $pricing->discounted_price, $pricing->currency ) ); ?>
	<?php else : ?>
		<?php echo esc_html( learndash_get_price_formatted( $pricing->price, $pricing->currency ) ); ?>
	<?php endif; ?>

	<?php if ( $transaction->is_subscription() ) : ?>
		<?php
		printf(
			// Translators: placeholder: Transaction billing cycle value, Transaction billing cycle length.
			esc_html_x(
				'every %1$d %2$s',
				'placeholder: Transaction billing cycle value, Transaction billing cycle length',
				'learndash'
			),
			esc_attr( (string) $pricing->duration_value ),
			esc_html(
				learndash_get_grammatical_number_label_for_interval(
					$pricing->duration_value,
					$pricing->duration_length
				)
			)
		);
		?>

		<?php if ( $pricing->recurring_times > 0 ) : ?>
			<?php
			printf(
				esc_html(
					// Translators: placeholder: Number of times a billing cycle repeats.
					_nx(
						' (%d time)',
						' (%d times)',
						$pricing->recurring_times,
						'Number of times a billing cycle repeats',
						'learndash'
					)
				),
				esc_html( (string) $pricing->recurring_times )
			);
			?>
		<?php else : ?>
			<?php esc_html_e( '(unlimited)', 'learndash' ); ?>
		<?php endif; ?>
	<?php endif; ?>
</span>
