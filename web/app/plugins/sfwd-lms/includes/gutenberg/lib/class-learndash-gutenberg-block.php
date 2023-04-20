<?php
/**
 * Base class for all LearnDash Gutenberg Blocks.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Gutenberg_Block' ) ) {
	/**
	 * Abstract Parent class to hold common functions used by specific LearnDash Blocks.
	 *
	 * @since 2.5.9
	 */
	class LearnDash_Gutenberg_Block {

		/**
		 * Block base
		 *
		 * @var string $block_base
		 */
		protected $block_base = 'learndash';

		/**
		 * Block directory where block.json is located.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		protected $block_dir;

		/**
		 * Shortcode slug
		 *
		 * @var string $shortcode_slug
		 */
		protected $shortcode_slug;

		/**
		 * Block slug
		 *
		 * @var string $block_slug
		 */
		protected $block_slug;

		/**
		 * Block attributes
		 *
		 * @var array $block_attributes
		 */
		protected $block_attributes;

		/**
		 * Self closing
		 *
		 * @var boolean $self_closing
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
		 * @since 2.5.9
		 */
		public function init() {
			if ( function_exists( 'register_block_type' ) ) {
				add_action( 'init', array( $this, 'register_blocks' ) );
				add_filter( 'learndash_block_markers_shortcode_atts', array( $this, 'learndash_block_markers_shortcode_atts_filter' ), 30, 4 );

				if ( ( defined( 'LEARNDASH_GUTENBERG_CONTENT_PARSE_LEGACY' ) ) && ( true === LEARNDASH_GUTENBERG_CONTENT_PARSE_LEGACY ) ) {
					/**
					 * Filter on the 'the_content' hook from WP. This needs to be at a priority
					 * before the 'run_shortcode' function which runs at a priority of 8.
					 */
					add_filter( 'the_content', array( $this, 'the_content_filter' ), 5 );
					add_filter( 'learndash_convert_block_markers_to_shortcode_content', array( $this, 'convert_block_markers_to_shortcode_content_filter' ), 30, 4 );
				}
			}
		}

		/**
		 * Register Block for Gutenberg
		 *
		 * @since 2.5.9
		 */
		public function register_blocks() {
			$block_register = ! empty( $this->block_dir ) ? $this->block_dir : $this->block_base . '/' . $this->block_slug;

			register_block_type(
				$block_register,
				array(
					'render_callback' => array( $this, 'render_block' ),
					'attributes'      => $this->block_attributes,
				)
			);
		}

		/**
		 * Hook into 'the_content' WP filter and parse out our block. We want to convert the Gutenberg Block notation to a normal LD shortcode.
		 * Called at high priority BEFORE do_shortcode() and do_blocks().
		 *
		 * @since 2.5.9
		 *
		 * @param string $content The post content containing all the inline HTML and blocks.
		 *
		 * @return string $content.
		 */
		public function the_content_filter( $content = '' ) {
			if ( ( is_admin() ) && ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) ) && ( ( isset( $_REQUEST['action'] ) ) && ( 'edit' === $_REQUEST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $content;
			}

			if ( ! empty( $content ) ) {
				$content = $this->convert_block_markers_to_shortcode( $content, $this->block_slug, $this->shortcode_slug, $this->self_closing );
			}
			return $content;
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
		 * @since 2.5.9
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return void The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
		}

		/**
		 * Add wrapper content around content to be returned to server.
		 *
		 * @since 2.5.9
		 *
		 * @param string  $content Content text to be wrapper.
		 * @param boolean $with_inner Flag to control inclusion of inner block div element.
		 *
		 * @return string wrapped content.
		 */
		public function render_block_wrap( $content = '', $with_inner = true ) {
			$return_content  = '';
			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block begin -->';

			if ( true === $with_inner ) {

				/**
				 * Temp hack until we update all the block/shortcodes.
				 */
				$extra_classes = '';
				if ( ! strstr( $content, 'learndash-wrap' ) ) {
					$extra_classes = ' learndash-wrap';
				}
				if ( ! strstr( $content, 'learndash-wrapper' ) ) {
					$extra_classes = ' learndash-wrapper';
				}
				$return_content .= '<div class="learndash-block-inner' . $extra_classes . '">';
			}

			$return_content .= $content;

			if ( true === $with_inner ) {
				$return_content .= '</div>';
			}

			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block end -->';

			return $return_content;
		}

		/**
		 * Pre-Process the block attributes before render.
		 *
		 * @since 3.2.3
		 *
		 * @param array $block_attributes Shortcode attributes.
		 *
		 * @return array $block_attributes
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

				if ( 'editing_post_meta' === $key ) {
					$block_attributes_new[ $key ] = (array) $val;
				} else {
					if ( isset( $this->block_attributes[ $key ]['type'] ) ) {
						$attribute_type = $this->block_attributes[ $key ]['type'];
					} else {
						$attribute_type = 'string';
					}

					if ( 'string' === $attribute_type ) {
						$block_attributes_new[ $key ] = esc_attr( $val );
					} elseif ( 'boolean' === $attribute_type ) {
						$block_attributes_new[ $key ] = (bool) $val;
					} elseif ( 'integer' === $attribute_type ) {
						$block_attributes_new[ $key ] = intval( $val );
					} elseif ( 'array' === $attribute_type ) {
						$block_attributes_new[ $key ] = (array) $val;
					} elseif ( 'object' === $attribute_type ) {
						$block_attributes_new[ $key ] = (object) $val;
					} else {
						$block_attributes_new[ $key ] = $val;
					}
				}
			}

			return $block_attributes_new;
		}

		/**
		 * Build the block shortcode from attributes string.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $block_attributes Block attributes array.
		 * @param string $block_content   Used for inner blocks.
		 *
		 * @return string $shortcode_str.
		 */
		protected function build_block_shortcode( $block_attributes = array(), $block_content = '' ) {

			$shortcode_str = '[' . $this->shortcode_slug;
			foreach ( $block_attributes as $key => $val ) {
				if ( substr( $key, 0, strlen( 'preview_' ) ) == 'preview_' ) {
					continue;
				}

				if ( substr( $key, 0, strlen( 'editing_' ) ) == 'editing_' ) {
					continue;
				}

				if ( ( empty( $key ) ) || ( '' == $val ) || ( is_null( $val ) ) ) {
					continue;
				}

				if ( ! empty( $shortcode_str ) ) {
					$shortcode_str .= ' ';
				}
				$shortcode_str .= $key . '="' . esc_attr( $val ) . '"';
			}
			$shortcode_str .= ']';

			if ( false === $this->self_closing ) {
				if ( ! empty( $block_content ) ) {
					$shortcode_str .= $block_content;
				}
				$shortcode_str .= '[/' . $this->shortcode_slug . ']';
			}

			return $shortcode_str;
		}


		/**
		 * Utility function to parse the WP Block content looking for specific token patterns.
		 *
		 * @since 2.6.0
		 *
		 * @param string  $content Full page/post content to be searched.
		 * @param string  $block_slug This is the block token pattern to search for. Ex: ld-user-meta, ld-visitor, ld-profile.
		 * @param string  $shortcode_slug This is the actual shortcode token to be used.
		 * @param boolean $self_closing true if not an inner block.
		 * @return string $content
		 */
		public function convert_block_markers_to_shortcode( $content = '', $block_slug = '', $shortcode_slug = '', $self_closing = false ) {
			if ( ( ! empty( $content ) ) && ( ! empty( $block_slug ) ) && ( ! empty( $shortcode_slug ) ) ) {
				$pattern_atts_array = array();
				if ( true === $self_closing ) {
					preg_match_all( '#<!--\s+wp:' . $this->block_base . '/' . $block_slug . '(.*?) /-->#is', $content, $ar );
					if ( ( isset( $ar[0] ) ) && ( is_array( $ar[0] ) ) && ( ! empty( $ar[0] ) ) ) {
						if ( ( isset( $ar[1] ) ) && ( is_array( $ar[1] ) ) && ( ! empty( $ar[1] ) ) ) {
							foreach ( $ar[1] as $pattern_key => $pattern_atts_json ) {
								$replacement_text = '[' . $shortcode_slug;

								if ( ! empty( $pattern_atts_json ) ) {
									$pattern_atts_array = (array) json_decode( $pattern_atts_json );
									if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
										foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
											if ( 'meta' === $attr_key ) {
												continue;
											}

											/**
											 * Only accept our known block attributes.
											 *
											 * @since 3.2.3
											 */
											if ( ! isset( $this->block_attributes[ $attr_key ] ) ) {
												unset( $pattern_atts_array[ $attr_key ] );
											}
										}
									}

									/** This filter is documented in includes/gutenberg/blocks/ld-course-list/index.php */
									$pattern_atts_array = apply_filters( 'learndash_block_markers_shortcode_atts', $pattern_atts_array, $shortcode_slug, $block_slug, $content );
									if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
										$shortcode_atts = '';
										foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
											if ( 'meta' === $attr_key ) {
												continue;
											}

											if ( '' !== $attr_value ) {
												if ( ! empty( $shortcode_atts ) ) {
													$shortcode_atts .= ' ';
												}

												if ( is_array( $attr_value ) ) {
													$attr_value = implode( ',', $attr_value );
												}

												$shortcode_atts .= $attr_key . '="' . $attr_value . '"';
											}
										}

										if ( ! empty( $shortcode_atts ) ) {
											$replacement_text .= ' ' . $shortcode_atts;
										}
									}
								}

								// If we have built a replacement text then replace it in the main $content.
								if ( ! empty( $replacement_text ) ) {
									$replacement_text .= ']';
									$content           = str_replace( $ar[0][ $pattern_key ], $replacement_text, $content );
									/**
									 * Filters the shortcode content after converting it from WordPress block content.
									 *
									 * @param string $content            Shortcode content after conversion.
									 * @param array  $pattern_atts_array An array of pattern attributes to use for conversion.
									 * @param string $shortcode_slug     The slug of shortcode.
									 * @param string $block_slug         The slug of gutenberg block.
									 */
									$content = apply_filters( 'learndash_convert_block_markers_to_shortcode_content', $content, $pattern_atts_array, $shortcode_slug, $block_slug );
								}
							}
						}
					}
				} else {
					/**
					 * A non-self closing WP block will look like the following for the ld-student. The
					 * patter will have an outer wrapper of the block which will be converted into a shortcode
					 * wrapper like [ld_student]<content here>[/ld_student]
					 *
					 * <!-- wp:learndash/ld-student {"course_id":"109"} -->
					 * <!-- wp:paragraph -->
					 * <p>This is the inner content. </p>
					 * <!-- /wp:paragraph -->
					 * <!-- /wp:learndash/ld-student -->
					 */
					preg_match_all( '#<!--\s+wp:' . $this->block_base . '/' . $block_slug . '(.*?)-->(.*?)<!--\s+/wp:' . $this->block_base . '/' . $block_slug . '\s+-->#is', $content, $ar );
					if ( ( isset( $ar[0] ) ) && ( is_array( $ar[0] ) ) && ( ! empty( $ar[0] ) ) ) {
						if ( ( isset( $ar[1] ) ) && ( is_array( $ar[1] ) ) && ( ! empty( $ar[1] ) ) ) {
							foreach ( $ar[1] as $pattern_key => $pattern_atts_json ) {
								$pattern_atts_json = trim( $pattern_atts_json );

								// Ensure the inner content is not empty.
								if ( ( isset( $ar[2][ $pattern_key ] ) ) && ( ! empty( $ar[2][ $pattern_key ] ) ) ) {
									$replacement_text = '[' . $shortcode_slug;

									if ( ! empty( $pattern_atts_json ) ) {
										$pattern_atts_array = (array) json_decode( $pattern_atts_json );

										if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
											foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
												if ( 'meta' === $attr_key ) {
													continue;
												}

												/**
												 * Only accept our known block attributes.
												 *
												 * @since 3.2.3
												 */
												if ( ! isset( $this->block_attributes[ $attr_key ] ) ) {
													unset( $pattern_atts_array[ $attr_key ] );
												}
											}
										}

										/** This filter is documented in includes/gutenberg/blocks/ld-course-list/index.php */
										$pattern_atts_array = apply_filters( 'learndash_block_markers_shortcode_atts', $pattern_atts_array, $shortcode_slug, $block_slug, $content );

										$shortcode_atts = '';

										if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
											foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
												if ( 'meta' === $attr_key ) {
													continue;
												}

												if ( '' !== $attr_value ) {
													if ( ! empty( $shortcode_atts ) ) {
														$shortcode_atts .= ' ';
													}

													if ( is_array( $attr_value ) ) {
														$attr_value = implode( ',', $attr_value );
													}

													$shortcode_atts .= $attr_key . '="' . $attr_value . '"';
												}
											}
										}
										if ( ! empty( $shortcode_atts ) ) {
											$replacement_text .= ' ' . $shortcode_atts;
										}
									}
									$replacement_text .= ']' . $ar[2][ $pattern_key ] . '[/' . $shortcode_slug . ']';

									// If we have built a replacement text then replace it in the main $content.
									if ( ! empty( $replacement_text ) ) {
										$content = str_replace( $ar[0][ $pattern_key ], $replacement_text, $content );
										/** This filter is documented in includes/gutenberg/lib/class-learndash-gutenberg-block.php */
										$content = apply_filters( 'learndash_convert_block_markers_to_shortcode_content', $content, $pattern_atts_array, $shortcode_slug, $block_slug );
									}
								}
							}
						}
					}
				}
			}

			return $content;
		}

		/**
		 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
		 * Each sub-subclassed instance should provide its own version of this function.
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
			return $block_attributes;
		}

		/**
		 * Called from the LD function convert_block_markers_to_shortcode() when parsing the block content.
		 * This function allows hooking into the converted content.
		 *
		 * @since 2.6.4
		 *
		 * @param string $content This is the original full content being parsed.
		 * @param array  $block_attributes The array of attributes parse from the block content.
		 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
		 * @param string $block_slug This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
		 *
		 * @return string $content.
		 */
		public function convert_block_markers_to_shortcode_content_filter( $content = '', $block_attributes = array(), $shortcode_slug = '', $block_slug = '' ) {
			return $content;
		}

		/**
		 * Common function used by the ld_course_list, ld_lesson_list, ld_topic_list,
		 * and ld_quiz_list called from the render_block short/block processing function.
		 * Converts the array of attributes to a normalized shortcode parameter string.
		 *
		 * @since 2.6.4
		 * @param array $block_attributes Array of block attributes.
		 * @return string.
		 */
		protected function prepare_course_list_atts_to_param( $block_attributes = array() ) {
			$shortcode_params_str = '';

			foreach ( $block_attributes as $key => $val ) {
				if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
					continue;
				}

				if ( ( 'preview_show' === $key ) || ( 'editing_post_meta' === $key ) ) {
					continue;
				} elseif ( 'preview_user_id' === $key ) {
					if ( ( isset( $block_attributes['user_id'] ) ) && ( ! empty( $block_attributes['user_id'] ) ) ) {
						continue;
					}

					if ( ( 'preview_user_id' === $key ) && ( '' !== $val ) ) {
						if ( ! $this->block_attributes_is_editing_post( $block_attributes ) ) {
							continue;
						}
						if ( ! learndash_is_admin_user( get_current_user_id() ) ) {
							if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
								// If group leader user we ensure the preview user_id is within their group(s).
								if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $val ) ) {
									continue;
								}
							} else {
								// If neither admin or group leader then we don't see the user_id for the shortcode.
								continue;
							}
						}
						$key = str_replace( 'preview_', '', $key );
						$val = absint( $val );
					}
				} elseif ( 'per_page' === $key ) {
					if ( '' === $val ) {
						continue;
					}
					$key = 'num';
					$val = (int) $val;

				} elseif ( ( 'show_content' === $key ) || ( 'show_thumbnail' === $key ) || ( 'course_grid' === $key ) || ( 'progress_bar' === $key ) ) {
					if ( ( 1 === $val ) || ( true === $val ) || ( 'true' === $val ) ) {
						$val = 'true';
					} else {
						$val = 'false';
					}
				} elseif ( 'col' === $key ) {
					if ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) {
						$val = absint( $val );
						if ( $val < 1 ) {
							$val = 3;
						}
					} else {
						continue;
					}
				} elseif ( 'lesson_id' === $key ) {
					if ( '' === $val ) {
						continue;
					}
				} elseif ( 'status' === $key ) {
					if ( empty( $val ) ) {
						continue;
					}

					$val_str = implode( ',', $val );
					$val     = $val_str;
				} elseif ( 'price_type' === $key ) {
					if ( empty( $val ) ) {
						continue;
					}

					$val_str = implode( ',', $val );
					$val     = $val_str;
				} elseif ( empty( $val ) ) {
					continue;
				}

				if ( ! empty( $shortcode_params_str ) ) {
					$shortcode_params_str .= ' ';
				}
				$shortcode_params_str .= $key . '="' . esc_attr( $val ) . '"';
			}

			return $shortcode_params_str;
		}

		/**
		 * Get example user ID. This is used as part of WP 5.3 Gutenberg Block Example / Preview.
		 *
		 * @since 3.1.0
		 *
		 * @return integer $user_id User ID.
		 */
		public function get_example_user_id() {
			$user_id = 0;
			/**
			 * Filters gutenberg block example ID.
			 *
			 * @param int    $id         The ID of the resource.
			 * @param string $context    The context of the resource.
			 * @param string $post_type  The post type slug of the resource.
			 * @param string $block_slug The slug of the block.
			 */
			$user_id = apply_filters( 'learndash_gutenberg_block_example_id', $user_id, 'user_id', 'user', $this->block_slug );
			$user_id = absint( $user_id );
			if ( ! empty( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
					$user_id = 0;
				}
			}

			if ( empty( $user_id ) ) {
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
				}
			}

			return $user_id;
		}

		/**
		 * Get example post ID. This is used as part of WP 5.3 Gutenberg Block Example / Preview.
		 *
		 * @since 3.1.0
		 *
		 * @param string $post_type Post Type Slug to retrieve.
		 *
		 * @return integer $post_id Post ID.
		 */
		public function get_example_post_id( $post_type = '' ) {
			$post_id = 0;
			/** This filter is documented in includes/gutenberg/lib/class-learndash-gutenberg-block.php */
			$post_id = apply_filters( 'learndash_gutenberg_block_example_id', $post_id, 'post_id', $post_type, $this->block_slug );
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$_post = get_post( $post_id );
				if ( ( ! $_post ) || ( ! is_a( $_post, 'WP_Post' ) ) ) {
					$course_id = 0;
				}
			}

			if ( empty( $post_id ) ) {
				$post_id = learndash_get_single_post( $post_type );
			}

			return $post_id;
		}

		/**
		 * Utility function to check if we are editing a post.
		 *
		 * @since 4.0.0
		 *
		 * @param array $block_attributes The block attributes.
		 *
		 * @return boolean true if we are editing a post.
		 */
		protected function block_attributes_is_editing_post( $block_attributes = array() ) {
			if ( isset( $block_attributes['editing_post_meta']['editing'] ) ) {
				if ( ( 'true' === $block_attributes['editing_post_meta']['editing'] ) || ( true === $block_attributes['editing_post_meta']['editing'] ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Utility function to determine the Post ID from the block attributes.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $block_attributes The block attributes.
		 * @param string $post_prefix     The post prefix to use. Example: 'course', 'quiz', 'group', etc.
		 *
		 * @return int Post ID.
		 */
		protected function block_attributes_get_post_id( $block_attributes = array(), $post_prefix = '' ) {
			if ( ! empty( $post_prefix ) ) {
				if ( ( isset( $block_attributes[ $post_prefix . '_id' ] ) ) && ( '' != $block_attributes[ $post_prefix . '_id' ] ) ) {
					return absint( $block_attributes[ $post_prefix . '_id' ] );
				}

				if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
					if ( ( isset( $block_attributes[ 'preview_' . $post_prefix . '_id' ] ) ) && ( '' != $block_attributes[ 'preview_' . $post_prefix . '_id' ] ) ) {
						return absint( $block_attributes[ 'preview_' . $post_prefix . '_id' ] );
					}
				}
			}

			return '';
		}

		/**
		 * Utility function to determine the User ID from the block attributes.
		 *
		 * @since 4.0.0
		 *
		 * @param array $block_attributes The block attributes.
		 *
		 * @return int User ID.
		 */
		protected function block_attributes_get_user_id( $block_attributes = array() ) {
			$user_id = '';
			if ( ( isset( $block_attributes['user_id'] ) ) && ( '' != $block_attributes['user_id'] ) ) {
				$user_id = absint( $block_attributes['user_id'] );
			}

			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				if ( ( isset( $block_attributes['preview_user_id'] ) ) && ( '' != $block_attributes['preview_user_id'] ) ) {
					$user_id = absint( $block_attributes['preview_user_id'] );
				}
			}

			if ( ! empty( $user_id ) ) {
				if ( ! learndash_is_admin_user( get_current_user_id() ) ) {
					if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
						// If group leader user we ensure the preview user_id is within their group(s).
						if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
							$user_id = '';
						}
					} elseif ( (int) get_current_user_id() !== (int) $block_attributes['user_id'] ) {
						$user_id = '';
					}
				}
			}

			return $user_id;
		}

		/**
		 * Utility function to determine the Edit Post Type.
		 *
		 * @since 4.0.0
		 *
		 * @param array $block_attributes The block attributes.
		 *
		 * @return string Preview Post Type.
		 */
		protected function block_attributes_get_editing_post_type( $block_attributes = array() ) {
			return $this->block_attributes_get_editing_post_data( $block_attributes, 'post_type' );
		}

		/**
		 * Utility function to determine the Edit Post ID.
		 *
		 * @since 4.0.0
		 *
		 * @param array $block_attributes The block attributes.
		 *
		 * @return int Edit Post ID.
		 */
		protected function block_attributes_get_editing_post_id( $block_attributes = array() ) {
			return $this->block_attributes_get_editing_post_data( $block_attributes, 'post_id' );
		}

		/**
		 * Utility function to determine the Edit Course Post ID.
		 *
		 * @since 4.0.0
		 *
		 * @param array $block_attributes The block attributes.
		 *
		 * @return int Edit Course Post ID.
		 */
		protected function block_attributes_get_editing_course_id( $block_attributes = array() ) {
			return $this->block_attributes_get_editing_post_data( $block_attributes, 'course_id' );
		}

		/**
		 * Utility function to determine the Edit Post Data.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $block_attributes The block attributes.
		 * @param string $return_key       The post data key value to return.
		 *
		 * @return array Preview Post Data.
		 */
		protected function block_attributes_get_editing_post_data( $block_attributes = array(), $return_key = '' ) {
			$editing_post_data = array(
				'post_id'   => 0,
				'post_type' => '',
				'course_id' => 0,
			);

			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				if ( isset( $block_attributes['editing_post_meta']['post_type'] ) ) {
					$editing_post_data['post_type'] = esc_attr( $block_attributes['editing_post_meta']['post_type'] );
				}

				if ( isset( $block_attributes['editing_post_meta']['post_id'] ) ) {
					$editing_post_data['post_id'] = absint( $block_attributes['editing_post_meta']['post_id'] );
				}

				if ( isset( $block_attributes['editing_post_meta']['course_id'] ) ) {
					$editing_post_data['course_id'] = absint( $block_attributes['editing_post_meta']['course_id'] );
				}
			}

			if ( ! empty( $return_key ) ) {
				if ( isset( $editing_post_data[ $return_key ] ) ) {
					return $editing_post_data[ $return_key ];
				}
			}
			return $editing_post_data;
		}

		// End of functions.
	}
}
