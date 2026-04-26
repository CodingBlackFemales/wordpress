<?php
/**
 * Masonry layout template.
 *
 * @since 4.21.4
 * @version 4.21.4
 *
 * @package LearnDash\Core
 *
 * Available variables:
 *
 * @var WP_Post[]            $posts   Array of WP_Post objects, result of the WP_Query->get_posts().
 * @var array<string, mixed> $atts    Shortcode/Block editor attributes that call this template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>
<div class="items-wrapper <?php echo esc_attr( $atts['skin'] ); ?>">
	<?php foreach ( $posts as $post ) : ?>
		<?php learndash_course_grid_load_card_template( $atts, $post ); ?>
	<?php endforeach; ?>
</div>
