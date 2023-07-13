<?php
/**
 * View: Pricing Subscribe with Trial.
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

// TODO: Refactor.
// TODO: What about 2 root elements?

$pricing = $product->get_pricing( $user );

// This filter is documented in themes/ld30/templates/components/infobar/group.php.
$free_label = apply_filters( 'learndash_no_price_price_label', __( 'Free', 'learndash' ) );
?>
<span class="ld-pricing__main-price">
	<?php if ( $pricing->trial_price > 0 ) : ?>
		<span class="ld-pricing__amount">
			<?php echo esc_html( $product->get_display_trial_price() ); ?>
		</span>
	<?php else : ?>
		<span class="ld-pricing__note">
			<?php echo esc_html( $free_label ); ?>
		</span>
	<?php endif; ?>

	<span class="ld-pricing__duration">
		<?php
		echo sprintf(
			// Translators: placeholders: %1$s Number of times the trial period repeats, %2$s Frequency of recurring payments: days, weeks, months, years.
			esc_html__( ' for %1$s %2$s', 'learndash' ),
			esc_html( (string) $pricing->trial_duration_value ),
			esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->trial_duration_value, $pricing->trial_duration_length, true ) )
		);
		?>
	</span>
</span>

<span class="ld-pricing__secondary-price">
	<?php
	echo sprintf(
		// Translators: placeholders: %1$s Subscription price, %2$s Interval of recurring payments (number), %3$s Frequency of recurring payments: day, week, month or year.
		esc_html_x( 'Then %1$s / %2$s %3$s', 'Recurring duration message', 'learndash' ),
		esc_html( $product->get_display_price() ),
		esc_html( 1 === $pricing->duration_value ? '' : (string) $pricing->duration_value ),
		esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length, true ) )
	);

	if ( $pricing->recurring_times > 0 ) {
		echo sprintf(
			// translators: placeholders: %1$s Number of times the recurring payment repeats, %2$s Frequency of recurring payments: day, week, month, year.
			esc_html__( ' for %1$s %2$s', 'learndash' ),
			absint( $pricing->duration_value * $pricing->recurring_times ), // Get correct total time by multiplying interval by number of repeats.
			esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->recurring_times, $pricing->duration_length, true ) )
		);
	}
	?>
</span>
