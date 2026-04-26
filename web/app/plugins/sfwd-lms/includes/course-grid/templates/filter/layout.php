<?php
/**
 * LearnDash Course Grid filter layout template.
 *
 * @since 4.21.4
 * @version 4.21.4
 *
 * @package LearnDash\Course_Grid
 */

use LearnDash\Course_Grid\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="filter-wrapper">
	<form method="POST" name="learndash_course_grid_filter">
		<?php wp_nonce_field( 'ld_cg_apply_filter' ); ?>
		<?php if ( $atts['search'] == true ) : ?>
			<div class="filter search">
				<label for="search-<?php echo esc_attr( $atts['course_grid_id'] ); ?>"><?php _e( 'Keyword', 'learndash' ); ?></label>
				<input type="text" name="search" id="search-<?php echo esc_attr( $atts['course_grid_id'] ); ?>">
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $atts['taxonomies'] ) ) : ?>
			<div class="filter taxonomies">
				<div class="taxonomies-wrapper">
					<?php foreach ( $atts['taxonomies'] as $taxonomy ) : ?>
						<?php

						$tax_object = get_taxonomy( $taxonomy );

						if ( is_a( $tax_object, 'WP_Taxonomy' ) ) :
							$terms = get_terms(
								[
									'taxonomy'   => $taxonomy,
									'orderby'    => 'name',
									'order'      => 'ASC',
									'hide_empty' => false,
								]
							);

							?>

							<div class="taxonomy <?php echo esc_attr( $taxonomy ); ?>">
								<label><?php echo esc_html( $tax_object->labels->name ); ?></label>
								<ul class="terms">
									<?php foreach ( $terms as $term ) : ?>
										<li class="term">
											<label
												for="<?php echo esc_attr( $atts['course_grid_id'] ) . '-' . esc_attr( $taxonomy ) . '-' . esc_attr( $term->slug ); ?>"
											>
												<input
													id="<?php echo esc_attr( $atts['course_grid_id'] ) . '-' . esc_attr( $taxonomy ) . '-' . esc_attr( $term->slug ); ?>"
													type="checkbox"
													name="<?php echo esc_attr( $taxonomy ); ?>[]"
													value="<?php echo esc_attr( $term->term_id ); ?>"
													<?php Utilities::checked_array( @$term->slug, @$default_taxonomies[ $taxonomy ]['terms'], true ); ?>
												>
												<?php echo esc_html( $term->name ); ?>
											</label>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $atts['price'] == true ) : ?>
			<div class="filter price">
				<label><?php _e( 'Price', 'learndash' ); ?></label>
				<div class="range-wrapper">
					<input name="price_min_range" class="range" id="price_min_range-<?php echo esc_attr( $atts['course_grid_id'] ); ?>" type="range" min="<?php echo esc_attr( $atts['price_min'] ); ?>" max="<?php echo esc_attr( $atts['price_max'] / 2 ); ?>" step="<?php echo esc_attr( $atts['price_step'] ); ?>">
					<input name="price_max_range" class="range" id="price_max_range-<?php echo esc_attr( $atts['course_grid_id'] ); ?>" type="range" min="<?php echo esc_attr( $atts['price_max'] / 2 ); ?>" max="<?php echo esc_attr( $atts['price_max'] ); ?>" step="<?php echo esc_attr( $atts['price_step'] ); ?>">
					<div style="clear: both;"></div>
				</div>
				<div class="number-wrapper left">
					<label for="price_min-<?php echo esc_attr( $atts['course_grid_id'] ); ?>"><?php _ex( 'Min', 'Minimum', 'learndash' ); ?></label>
					<input type="number" name="price_min" id="price_min-<?php echo esc_attr( $atts['course_grid_id'] ); ?>" min="<?php echo esc_attr( $atts['price_min'] ); ?>" step="<?php echo esc_attr( $atts['price_step'] ); ?>">
				</div>
				<div class="number-wrapper right">
					<label for="price_max-<?php echo esc_attr( $atts['course_grid_id'] ); ?>"><?php _ex( 'Max', 'Maximum', 'learndash' ); ?></label>
					<input type="number" name="price_max" id="price_max-<?php echo esc_attr( $atts['course_grid_id'] ); ?>" max="<?php echo esc_attr( $atts['price_max'] ); ?>" step="<?php echo esc_attr( $atts['price_step'] ); ?>">
				</div>
				<div style="clear: both;"></div>
			</div>
		<?php endif; ?>

		<div class="buttons">
			<button class="button apply blue"><?php _e( 'Apply', 'learndash' ); ?></button>
			<button class="button clear grey"><?php _e( 'Clear', 'learndash' ); ?></button>
		</div>
	</form>
</div>
