<?php
/**
 * View: Pricing Subscribe without Trial.
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

$pricing = $product->get_pricing( $user );

// TODO: Refactor.
?>
<span class="ld-pricing__main-price">
	<span class="ld-pricing__amount">
		<?php
		echo sprintf(
			// Translators: placeholders: %1$s Amount, %2$d Number of recurring payments, %3$s Frequency of recurring payments: day, week, month or year.
			esc_html_x( '%1$s / %2$s %3$s', 'Subscription billing cycle', 'learndash' ),
			esc_html( $product->get_display_price() ),
			esc_html( 1 === $pricing->duration_value ? '' : (string) $pricing->duration_value ),
			esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length, true ) )
		);
		?>
	</span>

	<?php if ( $pricing->recurring_times > 0 ) : ?>
		<span class="ld-pricing__duration">
			<?php
			echo sprintf(
				// Translators: placeholders: %1$s Number of times the recurring payment repeats, %2$s Frequency of recurring payments: day, week, month, year.
				esc_html__( ' for %1$s %2$s', 'learndash' ),
				absint( $pricing->duration_value * $pricing->recurring_times ), // Get correct total time by multiplying interval by number of repeats.
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->recurring_times, $pricing->duration_length, true ) )
			);
			?>
		</span>
	<?php endif; ?>
</span>

