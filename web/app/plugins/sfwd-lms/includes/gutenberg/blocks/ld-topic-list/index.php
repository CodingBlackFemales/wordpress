<?php
/**
 * Handles all server side logic for the ld-topic-list Gutenberg Block. This block is functionally the same
 * as the ld_topic_list shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Topic_List' ) ) ) {
	/**
	 * Class for handling LearnDash Topic List Block
	 */
	class LearnDash_Gutenberg_Block_Topic_List extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_topic_list';
			$this->block_slug       = 'ld-topic-list';
			$this->block_attributes = array(
				'orderby'                => array(
					'type' => 'string',
				),
				'order'                  => array(
					'type' => 'string',
				),
				'per_page'               => array(
					'type' => 'string',
				),
				'course_id'              => array(
					'type' => 'string',
				),
				'lesson_id'              => array(
					'type' => 'string',
				),
				'show_content'           => array(
					'type' => 'boolean',
				),
				'show_thumbnail'         => array(
					'type' => 'boolean',
				),
				'topic_category_name'    => array(
					'type' => 'string',
				),
				'topic_cat'              => array(
					'type' => 'string',
				),
				'topic_categoryselector' => array(
					'type' => 'boolean',
				),
				'topic_tag'              => array(
					'type' => 'string',
				),
				'topic_tag_id'           => array(
					'type' => 'string',
				),
				'category_name'          => array(
					'type' => 'string',
				),
				'cat'                    => array(
					'type' => 'string',
				),
				'categoryselector'       => array(
					'type' => 'boolean',
				),
				'tag'                    => array(
					'type' => 'string',
				),
				'tag_id'                 => array(
					'type' => 'string',
				),
				'course_grid'            => array(
					'type' => 'boolean',
				),
				'col'                    => array(
					'type' => 'integer',
				),
				'example_show'           => array(
					'type' => 'boolean',
				),
				'preview_show'           => array(
					'type' => 'boolean',
				),
				'editing_post_meta'      => array(
					'type' => 'object',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. In the case of this function the rendered output will be for the
		 * [ld_profile] shortcode.
		 *
		 * @since 2.5.9
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes = $this->preprocess_block_attributes( $block_attributes );

			// Only the 'editing_post_meta' element will be sent from within the post edit screen.
			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				$block_attributes['user_id'] = $this->block_attributes_get_user_id( $block_attributes );
			}

			/**
			 * Filters WordPress block content shortcode attributes.
			 *
			 * @since 2.5.9
			 *
			 * @param array  $block_attributes An array of shortcode attributes.
			 * @param string $shortcode_slug   Slug of the shortcode.
			 * @param string $block_slug       Slug of the gutenberg block.
			 * @param string $content          Shortcode content.
			 */
			$block_attributes = apply_filters( 'learndash_block_markers_shortcode_atts', $block_attributes, $this->shortcode_slug, $this->block_slug, '' );

			$shortcode_out = '';

			$shortcode_str = $this->prepare_course_list_atts_to_param( $block_attributes );
			$shortcode_str = '[' . $this->shortcode_slug . ' ' . $shortcode_str . ']';

			if ( ! empty( $shortcode_str ) ) {
				$shortcode_out = do_shortcode( $shortcode_str );
			}

			if ( ! empty( $shortcode_out ) ) {
				if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
					$shortcode_out = $this->render_block_wrap( $shortcode_out );
				} else {
					$shortcode_out = '<div class="learndash-wrap">' . $shortcode_out . '</div>';
				}
			}

			return $shortcode_out;
		}

		/**
		 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
		 *
		 * @since 2.5.9
		 *
		 * @param array  $block_attributes The array of attributes parse from the block content.
		 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
		 * @param string $block_slug This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
		 * @param string $content This is the original full content being parsed.
		 *
		 * @return array $block_attributes.
		 */
		public function learndash_block_markers_shortcode_atts_filter( $block_attributes = array(), $shortcode_slug = '', $block_slug = '', $content = '' ) {
			if ( $shortcode_slug === $this->shortcode_slug ) {
				if ( isset( $block_attributes['per_page'] ) ) {
					if ( ! isset( $block_attributes['num'] ) ) {
						$block_attributes['num'] = $block_attributes['per_page'];
						unset( $block_attributes['per_page'] );
					}
				}

				if ( ( ! isset( $block_attributes['topic_categoryselector'] ) ) || ( true === $block_attributes['topic_categoryselector'] ) ) {
					$block_attributes['topic_categoryselector'] = 'true';
				}

				if ( ( isset( $block_attributes['categoryselector'] ) ) && ( true === $block_attributes['categoryselector'] ) ) {
					$block_attributes['categoryselector'] = 'true';
				}

				if ( ( ! isset( $block_attributes['course_grid'] ) ) || ( true === $block_attributes['course_grid'] ) ) {
					$block_attributes['course_grid'] = 'true';
				}

				/**
				 * Not the best place to make this call this but we need to load the
				 * Course Grid resources.
				 */
				if ( 'true' === $block_attributes['course_grid'] ) {
					learndash_enqueue_course_grid_scripts();
				}
			}

			return $block_attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Topic_List();
