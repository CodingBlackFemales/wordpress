<?php
/**
 * Base class for all ProPanel Gutenberg Blocks.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnDash_ProPanel_Gutenberg_Block' ) ) {
	/**
	 * Abstract Parent class to hold common functions used by specific LearnDash ProPanel Blocks.
	 */
	#[AllowDynamicProperties]
	class LearnDash_ProPanel_Gutenberg_Block {
		/**
		 * The block base name.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $block_base = 'ld-propanel';

		/**
		 * The shortcode slug.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $shortcode_slug;

		/**
		 * The widget to be used for the shortcode.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $shortcode_widget;

		/**
		 * The block slug.
		 *
		 * @since 4.17.0
		 *
		 * @var string
		 */
		protected $block_slug;

		/**
		 * Array of block attributes.
		 *
		 * @since 4.17.0
		 *
		 * @var array<string, array<string, string>>
		 */
		protected $block_attributes;

		/**
		 * Flag to control if the block is self-closing.
		 *
		 * @since 4.17.0
		 *
		 * @var boolean
		 */
		protected $self_closing;

		/**
		 * Constructor.
		 */
		public function __construct() {
		}

		/**
		 * Initialize the hooks.
		 *
		 * @since 4.17.0
		 */
		public function init() {
			if ( function_exists( 'register_block_type' ) ) {
				add_action( 'init', array( $this, 'register_blocks' ) );
			}
		}

		/**
		 * Register Block for Gutenberg
		 *
		 * @since 4.17.0
		 */
		public function register_blocks() {
			register_block_type(
				$this->block_base . '/' . $this->block_slug,
				array(
					'render_callback'      => array( $this, 'render_block' ),
					'attributes'           => $this->block_attributes,
					'editor_style_handles' => array(
						'ld-propanel-style',
						'dashicons',
					),
				)
			);
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. This is called from within the admin edit post type page via an
		 * AJAX-type call to the server.
		 *
		 * Each sub-subclassed instance should provide its own version of this function.
		 *
		 * @since 4.17.0
		 *
		 * @param array  $block_attributes Array of block attributes.
		 * @param string $block_content    Block content.
		 * @param object $block            Block instance.
		 *
		 * @return void The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', $block = null ) {
			if ( is_user_logged_in() ) {
				$shortcode_out = '';
				if ( defined( 'REST_REQUEST' ) ) {
					// is_admin() fails when Gutenberg Blocks render via ServerSideRender, but this is used within these templates.
					// These templates load via admin-ajax.php normally, which allows is_admin() to return true.
					if ( ! defined( 'WP_ADMIN' ) ) {
						define( 'WP_ADMIN', true );
					}

					$template = $this->shortcode_widget;

					$get_data    = wp_unslash( $_GET );
					$server_data = wp_unslash( $_SERVER );

					$nonce = '';
					if ( isset( $server_data['HTTP_X_WP_NONCE'] ) && ! empty( $server_data['HTTP_X_WP_NONCE'] ) ) {
						$nonce = $server_data['HTTP_X_WP_NONCE'];
					}

					if ( wp_verify_nonce( $nonce, 'wp_rest' ) ) {
						if ( isset( $get_data['template'] ) && ! empty( $get_data['template'] ) ) {
							$template = $get_data['template'];
						}
					}

					// If this rendered via ServerSideRender within the Block Editor, return the template itself rather than an empty wrapper.
					$shortcode_out = apply_filters( 'learndash_propanel_template_ajax', '', $template );

					// If this is the Reporting Widget, we need to generate and inject the HTML within the above template.
					if ( in_array(
						$template,
						array(
							'reporting',
							'group-reporting',
							'course-reporting',
							'user-reporting',
						)
					) ) {
						// Make our request. Reuse the current Auth Token (LOGGED_IN_COOKIE) to preserve the session and ensure the nonce check succeeds.
						$request = wp_remote_get(
							add_query_arg(
								array_merge(
									$get_data,
									array(
										'action' => 'learndash_propanel_reporting_get_result_rows',
										'nonce'  => wp_create_nonce( 'ld-propanel' ),
									)
								),
								admin_url( 'admin-ajax.php' ),
							),
							array(
								'cookies' => array(
									LOGGED_IN_COOKIE => $_COOKIE[ LOGGED_IN_COOKIE ],
								),
							)
						);

						// Inject the data into the template.
						if ( wp_remote_retrieve_response_code( $request ) === 200 ) {
							$result_rows = json_decode( wp_remote_retrieve_body( $request ) );

							$shortcode_out['rows_html'] = str_replace( '<span class="current_page">', '<span class="current_page">' . $result_rows->pager->current_page, $shortcode_out['rows_html'] );

							$shortcode_out['rows_html'] = str_replace( '<span class="total_pages">', '<span class="total_pages">' . $result_rows->pager->total_pages, $shortcode_out['rows_html'] );

							$shortcode_out['rows_html'] = str_replace( '<span class="total_items">', '<span class="total_items">' . $result_rows->pager->total_items, $shortcode_out['rows_html'] );

							$shortcode_out['rows_html'] = str_replace( '<tbody>', '<tbody>' . $result_rows->rows_html, $shortcode_out['rows_html'] );
						}
					}
				} else {
					$shortcode_str = '[' . $this->shortcode_slug;
					if ( ! empty( $this->shortcode_widget ) ) {
						$shortcode_str .= ' widget="' . $this->shortcode_widget . '"';
					}
					$shortcode_params_str = $this->block_attributes_to_string( $block_attributes );
					if ( ! empty( $shortcode_params_str ) ) {
						$shortcode_str .= $shortcode_params_str;
					}
					$shortcode_str .= ']';

					if ( ! $this->self_closing ) {
						$shortcode_str .= $block_content;
						$shortcode_str .= "[/{$this->shortcode_slug}]";
					}

					$shortcode_out = do_shortcode( $shortcode_str );
				}

				if ( empty( $shortcode_out ) ) {
					$shortcode_out = '[' . $this->shortcode_slug . '] placeholder output.';
				} elseif ( defined( 'REST_REQUEST' ) ) {
					if ( isset( $shortcode_out['rows_html'] ) && ! empty( $shortcode_out['rows_html'] ) ) {
						$shortcode_out = $shortcode_out['rows_html'];
					}
				}

				return $this->render_block_wrap( $shortcode_out, ( empty( $block_content ) && ! defined( 'REST_REQUEST' ) ? true : false ) );
			}

			return '';
		}

		/**
		 * Convert Block Attributes array to string for shortcode processing.
		 *
		 * @since 4.17.0
		 *
		 * @param array $block_attributes Array of block attributes.
		 *
		 * @return string
		 */
		protected function block_attributes_to_string( $block_attributes = array() ) {
			$shortcode_params_str = '';

			$block_attributes = $this->preprocess_block_attributes( $block_attributes );
			$block_attributes = $this->process_block_attributes( $block_attributes );

			if ( ! empty( $block_attributes ) ) {
				foreach ( $block_attributes as $key => $val ) {
					$shortcode_params_str .= ' ' . $key . '="' . esc_attr( $val ) . '"';
				}
			}

			return $shortcode_params_str;
		}

		/**
		 * Pre-Process the block attributes before render.
		 *
		 * This function will validate and remove any unrecognized attributes.
		 *
		 * @since 4.17.0
		 *
		 * @param array $block_attributes Array of block attributes.
		 *
		 * @return array $attributes
		 */
		protected function preprocess_block_attributes( $block_attributes = array() ) {
			$block_attributes_new = array();

			foreach ( $block_attributes as $key => $val ) {
				if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
					continue;
				}

				// Ignore any block attributes not part of out set.
				if ( ! isset( $this->block_attributes[ $key ] ) ) {
					continue;
				}

				$block_attributes_new[ $key ] = $val;
			}

			return $block_attributes_new;
		}

		/**
		 * Process the block attributes before render.
		 *
		 * @since 4.17.0
		 *
		 * @param array $block_attributes Array of block attributes.
		 *
		 * @return array $block_attributes
		 */
		protected function process_block_attributes( $block_attributes = array() ) {
			return $block_attributes;
		}

		/**
		 * Add wrapper content around content to be returned to server.
		 *
		 * @since 4.17.0
		 *
		 * @param string  $content Content text to be wrapper.
		 * @param boolean $with_inner Flag to control inclusion of inner block div element.
		 *
		 * @return string wrapped content.
		 */
		public function render_block_wrap( $block_content = '', $with_inner = true ) {
			$return_content  = '';
			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block begin -->';

			if ( true === $with_inner ) {
				$return_content .= '<div class="learndash-block-inner">';
			}

			$return_content .= $block_content;

			if ( true === $with_inner ) {
				$return_content .= '</div>';
			}

			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block end -->';

			return $return_content;
		}

		// End of functions.
	}
}
