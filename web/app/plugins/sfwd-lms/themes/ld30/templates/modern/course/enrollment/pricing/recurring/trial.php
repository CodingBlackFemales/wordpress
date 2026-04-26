<?php
/**
 * View: Course Pricing Recurring With Trial.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

$pricing        = $product->get_pricing();
$trial_category = $pricing->trial_price === 0. ? 'free' : 'paid';

?>
<section class="ld-enrollment__pricing ld-enrollment__pricing--recurring ld-enrollment__pricing--<?php echo esc_attr( $trial_category ); ?>-trial">
	<h2 class="ld-enrollment__pricing-label" id="ld-enrollment__pricing-label">
		<?php echo esc_html__( 'Price', 'learndash' ); ?>
	</h2>

	<span class="ld-enrollment__pricing-price">
		<?php
		printf(
			// translators: placeholder: %1$s = price, %2$s = trial interval, %3$s = trial frequency.
			esc_html_x( '%1$s for %2$s %3$s', 'Subscribe with a free trial. Example with a free trial: Free for 2 weeks. Example with a paid trial: $20 for 2 months', 'learndash' ),
			esc_html( $product->get_display_trial_price() ),
			esc_html( (string) $pricing->trial_duration_value ),
			esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->trial_duration_value, $pricing->trial_duration_length ) ),
		);
		?>
	</span>

	<span class="ld-enrollment__pricing-label">
		<?php
		if ( $pricing->recurring_times === 0 && $pricing->duration_value === 1 ) {
			// A simple subscription with a duration of 1 and no end date.
			printf(
				// translators: placeholder: %1$s = price, %2$s = frequency.
				esc_html_x( 'Then %1$s per %2$s', 'Subscribe with an interval of 1. Example: Then $20 per month', 'learndash' ),
				esc_html( $product->get_display_price() ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
			);
		} elseif ( $pricing->recurring_times === 0 ) {
			// A simple subscription with a duration > 1 and no end date.
			printf(
				// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency.
				esc_html_x( 'Then %1$s every %2$s %3$s', 'Subscribe with an interval > 1. Example: Then $20 every 2 weeks', 'learndash' ),
				esc_html( $product->get_display_price() ),
				esc_html( (string) $pricing->duration_value ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
			);
		} elseif ( $pricing->duration_value === 1 ) {
			// A repeating subscription with an interval of 1.
			printf(
				// translators: placeholder: %1$s = price, %2$s = frequency, %3$s = entire duration, %4$s = entire frequency.
				esc_html_x( 'Then %1$s per %2$s for %3$s %4$s', 'Repeating subscription with an interval of 1. Example: Then $20 per week for 20 weeks', 'learndash' ),
				esc_html( $product->get_display_price() ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
				esc_html( (string) $pricing->recurring_times ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->recurring_times, $pricing->duration_length ) ),
			);
		} else {
			// A repeating subscription with an interval > 1.
			printf(
				// translators: placeholder: %1$s = price, %2$s = interval, %3$s = frequency, %4$s = entire duration, %5$s = entire frequency.
				esc_html_x( 'Then %1$s every %2$s %3$s for %4$s %5$s', 'Repeating subscription with an interval > 1. Example: Then $20 every 2 weeks for 20 weeks', 'learndash' ),
				esc_html( $product->get_display_price() ),
				esc_html( (string) $pricing->duration_value ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
				esc_html( (string) ( $pricing->duration_value * $pricing->recurring_times ) ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value * $pricing->recurring_times, $pricing->duration_length ) ),
			);
		}
		?>
	</span>
</section>

