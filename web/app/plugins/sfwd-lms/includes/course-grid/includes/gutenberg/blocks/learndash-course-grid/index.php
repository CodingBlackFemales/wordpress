<?php
/**
 * LearnDash course grid Gutenberg block class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid\Gutenberg\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LDLMS_Post_Types;
use LearnDash;
use LearnDash\Course_Grid;
use LearnDash\Course_Grid\Lib\LearnDash_Gutenberg_Block;
use WP_Block;

/**
 * Course grid block class.
 *
 * @since 4.21.4
 */
class LearnDash_Course_Grid extends LearnDash_Gutenberg_Block {
	/**
	 * Object constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		$this->shortcode_slug   = 'learndash_course_grid';
		$this->block_slug       = 'ld-course-grid';
		$this->block_attributes = array(
			'post_type'                    => array(
				'type'    => 'string',
				'default' => function_exists( 'learndash_get_post_type_slug' )
					? learndash_get_post_type_slug( LDLMS_Post_Types::COURSE )
					: 'post',
			),
			'per_page'                     => array(
				'type'    => 'integer',
				'default' => 9,
			),
			'orderby'                      => array(
				'type'    => 'string',
				'default' => 'ID',
			),
			'order'                        => array(
				'type'    => 'string',
				'default' => 'DESC',
			),
			'taxonomies'                   => array(
				'type' => 'string',
			),
			'enrollment_status'            => array(
				'type' => 'string',
			),
			'progress_status'              => array(
				'type' => 'string',
			),
			'thumbnail'                    => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'thumbnail_size'               => array(
				'type'    => 'string',
				'default' => 'course-thumbnail',
			),
			'ribbon'                       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'content'                      => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'title'                        => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'title_clickable'              => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'description'                  => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'description_char_max'         => array(
				'type'    => 'integer',
				'default' => 120,
			),
			'post_meta'                    => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'button'                       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'pagination'                   => array(
				'type'    => 'string',
				'default' => 'button',
			),
			'grid_height_equal'            => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'progress_bar'                 => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'filter'                       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'skin'                         => array(
				'type'    => 'string',
				'default' => 'grid',
			),
			'card'                         => array(
				'type'    => 'string',
				'default' => 'grid-1',
			),
			'columns'                      => array(
				'type'    => 'integer',
				'default' => 3,
			),
			'min_column_width'             => array(
				'type'    => 'string',
				'default' => 250,
			),
			'items_per_row'                => array(
				'type'    => 'integer',
				'default' => 5,
			),
			'font_family_title'            => array(
				'type' => 'string',
			),
			'font_family_description'      => array(
				'type' => 'string',
			),
			'font_size_title'              => array(
				'type' => 'string',
			),
			'font_size_description'        => array(
				'type' => 'string',
			),
			'font_color_title'             => array(
				'type' => 'string',
			),
			'font_color_description'       => array(
				'type' => 'string',
			),
			'background_color_title'       => array(
				'type' => 'string',
			),
			'background_color_description' => array(
				'type' => 'string',
			),
			'background_color_ribbon'      => array(
				'type' => 'string',
			),
			'font_color_ribbon'            => array(
				'type' => 'string',
			),
			'background_color_icon'        => array(
				'type' => 'string',
			),
			'font_color_icon'              => array(
				'type' => 'string',
			),
			'background_color_button'      => array(
				'type' => 'string',
			),
			'font_color_button'            => array(
				'type' => 'string',
			),
			// Misc.
			'id'                           => array(
				'type' => 'string',
			),
			'className'                    => array(
				'type' => 'string',
			),
			'preview_show'                 => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'display_state'                => array(
				'type' => 'object',
			),
			// Filter.
			'filter_search'                => [
				'type'    => 'boolean',
				'default' => true,
			],
			'filter_taxonomies'            => [
				'type'    => 'array',
				'default' => [ 'category', 'post_tag' ],
			],
			'filter_price'                 => [
				'type'    => 'boolean',
				'default' => true,
			],
			'filter_price_min'             => [
				'type'    => 'string',
				'default' => '0',
			],
			'filter_price_max'             => [
				'type'    => 'string',
				'default' => '1000',
			],
		);

		$this->self_closing = true;

		$this->init();
	}

	/**
	 * Renders block.
	 *
	 * This function is called per the register_block_type() function above. This function will output
	 * the block rendered content.
	 *
	 * @since 4.21.4
	 *
	 * @param array         $attributes    Shortcode attributes.
	 * @param string        $block_content Block content.
	 * @param WP_Block|null $block         Block object.
	 *
	 * @return string Block output.
	 */
	public function render_block( $attributes = array(), $block_content = '', ?WP_Block $block = null ) {
		$attributes = $this->preprocess_block_attributes( $attributes );

		/**
		 * Filters the block attributes before processing the shortcode.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, mixed>  $attributes     The block attributes.
		 * @param string                $shortcode_slug The shortcode slug.
		 * @param string                $block_slug     The block slug.
		 * @param string                $content        The block content.
		 *
		 * @return array<string, mixed> Returned block attributes.
		 */
		$attributes = apply_filters( 'learndash_block_markers_shortcode_atts', $attributes, $this->shortcode_slug, $this->block_slug, '' );

		$shortcode_params_str = '';
		foreach ( $attributes as $key => $val ) {
			if ( is_null( $val ) ) {
				continue;
			}

			if ( is_array( $val ) ) {
				$val = implode( ',', $val );
			}

			if ( ! empty( $shortcode_params_str ) ) {
				$shortcode_params_str .= ' ';
			}
			$shortcode_params_str .= $key . '="' . esc_attr( $val ) . '"';
		}

		$shortcode_params_str = '[' . $this->shortcode_slug . ' ' . $shortcode_params_str . ']';

		$args = Course_Grid::instance()->skins->parse_shortcode_tags( $shortcode_params_str );

		$style = Course_Grid::instance()->skins->generate_custom_css( $args );

		ob_start();
		?>
		<div class="learndash-course-grid-temp-css" style="display: none;">
			<?php echo esc_html( $style ); ?>
		</div>
		<?php
		$script = ob_get_clean();

		$shortcode_out  = $script;
		$shortcode_out .= do_shortcode( $shortcode_params_str );

		if ( ( empty( $shortcode_out ) ) ) {
			$shortcode_out = '[' . $this->shortcode_slug . '] placeholder output.';
		}

		return $this->render_block_wrap( $shortcode_out, true );
	}

	/**
	 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
	 *
	 * @since 4.21.4
	 *
	 * @param array  $attributes     The array of attributes parsed from the block content.
	 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
	 * @param string $block_slug     This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
	 * @param string $content        This is the original full content being parsed.
	 *
	 * @return array $attributes.
	 */
	public function learndash_block_markers_shortcode_atts_filter( $attributes = array(), $shortcode_slug = '', $block_slug = '', $content = '' ) {
		if ( $shortcode_slug === $this->shortcode_slug ) {
			if ( isset( $attributes['preview_show'] ) ) {
				unset( $attributes['preview_show'] );
			}

			if ( isset( $attributes['className'] ) ) {
				$attributes['class_name'] = $attributes['className'];
				unset( $attributes['className'] );
			}

			if ( isset( $attributes['display_state'] ) ) {
				unset( $attributes['display_state'] );
			}

			if ( ! isset( $attributes['filter_taxonomies'] ) ) {
				$attributes['filter_taxonomies'] = '';
			}

			foreach ( $attributes as $key => $value ) {
				if ( is_array( $value ) ) {
					$attributes[ $key ] = implode( ', ', $value );
				} elseif ( is_string( $value ) ) {
					// Remove quotes to prevent the attributes from being stripped out.
					$attributes[ $key ] = str_replace( [ '"', '\'' ], '', $attributes[ $key ] );
				}
			}
		}

		return $attributes;
	}
}
