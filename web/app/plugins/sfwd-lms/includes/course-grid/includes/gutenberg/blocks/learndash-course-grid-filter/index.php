<?php
/**
 * LearnDash course grid filter Gutenberg block class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid\Gutenberg\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid\Lib\LearnDash_Gutenberg_Block;
use WP_Block;

/**
 * Course grid filter block class.
 *
 * @since 4.21.4
 */
class LearnDash_Course_Grid_Filter extends LearnDash_Gutenberg_Block {
	/**
	 * Object constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		$this->shortcode_slug   = 'learndash_course_grid_filter';
		$this->block_slug       = 'ld-course-grid-filter';
		$this->block_attributes = [
			'course_grid_id' => [
				'type' => 'string',
			],
			'search'         => [
				'type'    => 'boolean',
				'default' => true,
			],
			'taxonomies'     => [
				'type'    => 'array',
				'default' => [ 'category', 'post_tag' ],
			],
			'price'          => [
				'type'    => 'boolean',
				'default' => true,
			],
			'price_min'      => [
				'type'    => 'integer',
				'default' => 0,
			],
			'price_max'      => [
				'type'    => 'integer',
				'default' => 1000,
			],
			'preview_show'   => [
				'type'    => 'boolean',
				'default' => true,
			],
		];

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
		 * Filters block attributes before processing.
		 *
		 * @since 4.21.4
		 *
		 * @param array $attributes Block attributes.
		 *
		 * @return array Processed block attributes.
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

		$shortcode_out = do_shortcode( $shortcode_params_str );

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
			if ( isset( $shortcode_slug['preview_show'] ) ) {
				unset( $attributes['preview_show'] );
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
