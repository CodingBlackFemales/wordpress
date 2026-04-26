<?php
/**
 * View: Course Pricing Recurring No Trial.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

$pricing = $product->get_pricing();
?>
<section class="ld-enrollment__pricing ld-enrollment__pricing--recurring ld-enrollment__pricing--no-trial">
	<h2 class="ld-enrollment__pricing-label" id="ld-enrollment__pricing-label">
		<?php echo esc_html__( 'Price', 'learndash' ); ?>
	</h2>

	<span class="ld-enrollment__pricing-price">
		<?php echo esc_html( $product->get_display_price() ); ?>
	</span>

	<span class="ld-enrollment__pricing-label">
		<?php
		if ( $pricing->recurring_times === 0 && $pricing->duration_value === 1 ) {
			// A simple subscription with a duration of 1 and no end date.
			printf(
				// translators: placeholder: %1$s = frequency.
				esc_html_x( 'Every %1$s', 'Subscribe with an interval of 1. Example: Every month', 'learndash' ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
			);
		} elseif ( $pricing->recurring_times === 0 ) {
			// A simple subscription with a duration > 1 and no end date.
			printf(
				// translators: placeholder: %1$s = interval, %2$s = frequency.
				esc_html_x( 'Every %1$s %2$s', 'Subscribe with an interval > 1. Example: Every 2 weeks', 'learndash' ),
				esc_html( (string) $pricing->duration_value ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
			);
		} elseif ( $pricing->duration_value === 1 ) {
			// A repeating subscription with an interval of 1.
			printf(
				// translators: placeholder: %1$s = frequency, %3$s = entire duration, %3$s = entire frequency.
				esc_html_x( 'Every %1$s for %2$s %3$s', 'Repeating subscription with an interval of 1. Example: Every week for 20 weeks', 'learndash' ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
				esc_html( (string) $pricing->recurring_times ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->recurring_times, $pricing->duration_length ) ),
			);
		} else {
			// A repeating subscription with an interval > 1.
			printf(
				// translators: placeholder: %1$s = interval, %2$s = frequency, %3$s = entire duration, %4$s = entire frequency.
				esc_html_x( 'Every %1$s %2$s for %3$s %4$s', 'Repeating subscription with an interval > 1. Example: Every 2 weeks for 20 weeks', 'learndash' ),
				esc_html( (string) $pricing->duration_value ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value, $pricing->duration_length ) ),
				esc_html( (string) ( $pricing->duration_value * $pricing->recurring_times ) ),
				esc_html( learndash_get_grammatical_number_label_for_interval( $pricing->duration_value * $pricing->recurring_times, $pricing->duration_length ) ),
			);
		}
		?>
	</span>
</section>

