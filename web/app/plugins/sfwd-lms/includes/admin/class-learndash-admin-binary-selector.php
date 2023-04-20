<?php
/**
 * LearnDash Binary Selector Class.
 *
 * @since 2.1.2
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Binary_Selector' ) ) {

	/**
	 * Class for LearnDash Binary Selector.
	 *
	 * @since 2.1.2
	 */
	class Learndash_Binary_Selector {

		/**
		 * Selector args used.
		 *
		 * @var array $args Array of arguments used by the selector
		 */
		protected $args = array();

		/**
		 * Selector default settings.
		 *
		 * @var array $defaults Array of default settings for the selector
		 */
		private $defaults = array();

		/**
		 * Stores the class as a var. This is so when we send the class back over AJAX we know how to recreate it.
		 *
		 * @var string $selector_class Class reference to selector.
		 */
		protected $selector_class = '';

		/**
		 * Nonce for the AJAX calls.
		 *
		 * @var string $selector_nonce Nonce value.
		 */
		protected $selector_nonce;

		/**
		 * Element data passed to DOM.
		 *
		 * @var array $element_data Array of data passed to DOM
		 */
		protected $element_data = array();

		/**
		 * Container for the query result items.
		 *
		 * @var array $element_items Array of query result items to process.
		 */
		protected $element_items = array();

		/**
		 * Container for the queries.
		 *
		 * @var array $element_queries Array of Queries.
		 */
		protected $element_queries = array();

		/**
		 * Allowed classes
		 *
		 * @var array $allowed_classes.
		 */
		protected static $allowed_classes = array(
			'Learndash_Binary_Selector_Users',
			'Learndash_Binary_Selector_Course_Users',
			'Learndash_Binary_Selector_Group_Users',
			'Learndash_Binary_Selector_Group_Leaders',
			'Learndash_Binary_Selector_Posts',
			'Learndash_Binary_Selector_Group_Courses',
			'Learndash_Binary_Selector_Group_Courses_Enroll',
			'Learndash_Binary_Selector_Course_Groups',
			'Learndash_Binary_Selector_User_Courses',
			'Learndash_Binary_Selector_User_Groups',
			'Learndash_Binary_Selector_Leader_Groups',
			'Learndash_Binary_Selector_Exam_Challenge_Courses',
		);

		/**
		 * Public constructor for class.
		 *
		 * @since 2.1.2
		 *
		 * @param array $args Array of selector args used to initialize instance.
		 */
		public function __construct( $args = array() ) {

			$this->defaults = array(
				'html_title'         => '',
				'html_id'            => '',
				'html_name'          => '',
				'html_class'         => '',
				'selected_ids'       => array(),
				'included_ids'       => array(),
				'max_height'         => '250px',
				'min_height'         => '250px',
				'lazy_load'          => false,
				'search_label_left'  => esc_html__( 'Search:', 'learndash' ),
				'search_label_right' => esc_html__( 'Search:', 'learndash' ),
				'is_search'          => false,
				'is_pager'           => false,
			);

			$this->args = wp_parse_args( $args, $this->defaults );

			$this->args['html_slug'] = sanitize_title_with_dashes( $this->args['html_id'] );

			// We want to convert this to an array.
			if ( ( ! empty( $this->args['selected_ids'] ) ) && ( is_string( $this->args['selected_ids'] ) ) ) {
				$this->args['selected_ids'] = explode( ',', $this->args['selected_ids'] );
			} elseif ( ( empty( $this->args['selected_ids'] ) ) && ( is_string( $this->args['selected_ids'] ) ) ) {
				$this->args['selected_ids'] = array();
			}

			// If for some reason the 'include' element is passed in we convert it to our 'included_ids'.
			if ( ( isset( $this->args['include'] ) ) && ( ! empty( $this->args['include'] ) ) && ( empty( $this->args['included_ids'] ) ) ) {
				$this->args['included_ids'] = $this->args['include'];
				unset( $this->args['include'] );
			}
			if ( ( ! empty( $this->args['included_ids'] ) ) && ( is_string( $this->args['included_ids'] ) ) ) {
				$this->args['included_ids'] = explode( ',', $this->args['included_ids'] );
			}

			// Let the outside world override some settings.
			/**
			 * Filters binary selector setting arguments.
			 *
			 * @since 2.2.1
			 *
			 * @param array  $args           An Array of arguments used by the selector.
			 * @param string $selector_class Class reference to selector.
			 */
			$this->args = apply_filters( 'learndash_binary_selector_args', $this->args, $this->selector_class );

			$this->element_items['left']  = array();
			$this->element_items['right'] = array();

			$this->element_queries['left']  = array();
			$this->element_queries['right'] = array();
		}

		/**
		 * Show function for selector.
		 *
		 * @since 2.1.2
		 */
		public function show() {
			$this->query_selection_section_items( 'left' );
			$this->query_selection_section_items( 'right' );

			// If we don't have items for the left (All items) then something is wrong. Abort.
			if ( ( empty( $this->element_items['left'] ) ) && ( empty( $this->element_items['right'] ) ) ) {
				return;
			}

			// Before we add our data element we remove all the unneeded keys. Just to keep it small.
			$element_data = $this->element_data;
			foreach ( $this->defaults as $key => $val ) {
				if ( isset( $element_data['query_vars'][ $key ] ) ) {
					unset( $element_data['query_vars'][ $key ] );
				}
			}

			// Aware of the PHP post number vars limit we convert the include and exclude arrays to json so they are sent back as strings.
			if ( ( isset( $element_data['query_vars']['include'] ) ) && ( ! empty( $element_data['query_vars']['include'] ) ) ) {
				$element_data['query_vars']['include'] = wp_json_encode( $element_data['query_vars']['include'], JSON_FORCE_OBJECT );
			}

			if ( ( isset( $element_data['query_vars']['exclude'] ) ) && ( ! empty( $element_data['query_vars']['exclude'] ) ) ) {
				$element_data['query_vars']['exclude'] = wp_json_encode( $element_data['query_vars']['exclude'], JSON_FORCE_OBJECT );
			}

			?>
			<div id="<?php echo esc_attr( $this->args['html_id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-binary-selector-' . get_current_user_id() ) ); ?>" class="<?php echo esc_attr( $this->args['html_class'] ); ?> learndash-binary-selector" data="<?php echo htmlspecialchars( wp_json_encode( $element_data ) ); ?>"><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" class="learndash-binary-selector-form-element" name="<?php echo esc_attr( $this->args['html_name'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $this->args['selected_ids'], JSON_FORCE_OBJECT ) ); ?>"/><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="<?php echo esc_attr( $this->args['html_id'] ); ?>-nonce" value="<?php echo esc_attr( wp_create_nonce( $this->args['html_id'] ) ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $this->args['html_id'] ); ?>-changed" class="learndash-binary-selector-form-changed" value="" />

				<?php $this->show_selections_title(); ?>
				<table class="learndash-binary-selector-table">
				<tr>
					<?php
						$this->show_selections_section( 'left' );
						$this->show_selections_section_controls();
						$this->show_selections_section( 'right' );
					?>
				</tr>
			</table>
				<?php
				if ( ( isset( $this->args['max_height'] ) ) && ( ! empty( $this->args['max_height'] ) ) ) {
					?>
					<style>
					.learndash-binary-selector .learndash-binary-selector-section .learndash-binary-selector-items {
						max-height: <?php echo esc_attr( $this->args['max_height'] ); ?>;
						overflow-y:scroll;
					}
					</style>
					<?php
				}
				?>
				<?php
				if ( ( isset( $this->args['min_height'] ) ) && ( ! empty( $this->args['min_height'] ) ) ) {
					?>
					<style>
					.learndash-binary-selector .learndash-binary-selector-section .learndash-binary-selector-items {
						min-height: <?php echo esc_attr( $this->args['min_height'] ); ?>;
					}
					</style>
					<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Show Selections Title.
		 * This is the title shown above the binary selector widget.
		 *
		 * @since 2.1.2
		 */
		protected function show_selections_title() {
			if ( ! empty( $this->args['html_title'] ) ) {
				echo wp_kses_post( $this->args['html_title'] );
			}
		}

		/**
		 * Show Selector Controls.
		 * Shows the Add/Remove buttons which lives between the left/right side selectors.
		 *
		 * @since 2.2.1
		 */
		protected function show_selections_section_controls() {
			?>
			<td class="learndash-binary-selector-section learndash-binary-selector-section-middle">
				<a href="#" class="learndash-binary-selector-button-add">
				<?php if ( is_rtl() ) { ?>
					<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_left.png' ); ?>" />
				<?php } else { ?>
					<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_right.png' ); ?>" />
				<?php } ?>
				</a><br>

				<a href="#" class="learndash-binary-selector-button-remove">
				<?php if ( is_rtl() ) { ?>
					<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_right.png' ); ?>" />
				<?php } else { ?>
					<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_left.png' ); ?>" />
				<?php } ?>
				</a>
			</td>
			<?php
		}

		/**
		 * Show Selector section.
		 * Shows the left/right selector sections.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section( $position = '' ) {
			$position = esc_attr( $position );
			if ( ( 'left' === $position ) || ( 'right' === $position ) ) {
				?>
				<td class="learndash-binary-selector-section learndash-binary-selector-section-<?php echo esc_attr( $position ); ?>">
					<input placeholder="<?php echo esc_attr( $this->get_search_label( $position ) ); ?>" type="text" id="learndash-binary-selector-search-<?php echo esc_attr( $this->args['html_slug'] ); ?>-<?php echo esc_attr( $position ); ?>" class="learndash-binary-selector-search learndash-binary-selector-search-<?php echo esc_attr( $position ); ?>" />

					<select multiple="multiple" class="learndash-binary-selector-items learndash-binary-selector-items-<?php echo esc_attr( $position ); ?>">
						<?php $this->show_selections_section_items( $position ); ?>
					</select>

					<ul class="learndash-binary-selector-pager learndash-binary-selector-pager-<?php echo esc_attr( $position ); ?>">
						<?php $this->show_selections_section_pager( $position ); ?>
					</ul>
				</td>
				<?php
			}
		}

		/**
		 * Show selector section items.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_items( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				echo $this->build_options_html( $position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
			}
		}

		/**
		 * Show selector section legend.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_legend( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					?>
					<span class="items-loaded-count" style="display:none"> /</span> <span class="items-total-count"></span>
					<?php
				} elseif ( 'right' === $position ) {
					?>
					<span class="items-loaded-count" style="display:none"> /</span> <span class="items-total-count"></span>
					<?php
				}
			}
		}

		/**
		 * Show selector section pager.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_pager( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				?>
				<li class="learndash-binary-selector-pager-prev"><a class="learndash-binary-selector-pager-prev" style="display:none;" href="#"><?php esc_html_e( '&lsaquo; prev', 'learndash' ); ?></a></li>
				<li class="learndash-binary-selector-pager-info" style="display:none;">
				<?php
				echo sprintf(
					// translators: placeholder: Page X of Y.
					esc_html_x( 'Page %1$s of %2$s', 'placeholder: Page X of Y', 'learndash' ),
					'<span class="current_page"></span>',
					'<span class="total_pages"></span>'
				);
				?>
				</li>
				<li class="learndash-binary-selector-pager-next"><a class="learndash-binary-selector-pager-next" style="display:none;" href="#"><?php esc_html_e( 'next  &rsaquo;', 'learndash' ); ?></a></li>
				<?php
			}
		}

		/**
		 * Get selector section search label.
		 *
		 * @since 2.1.2
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_search_label( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( isset( $this->args[ 'search_label_' . $position ] ) ) {
					return $this->args[ 'search_label_' . $position ];
				} elseif ( isset( $this->args['search_label'] ) ) {
					return $this->args['search_label'];
				} else {
					return esc_html__( 'Search', 'learndash' );
				}
			}
		}

		/**
		 * Get selector section pager data.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_pager_data( $position = '' ) {
		}

		/**
		 * Build selector section options HTML.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function build_options_html( $position = '' ) {
		}

		/**
		 * Get selector section items.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
		}

		/**
		 * Process selector section query.
		 *
		 * @since 2.2.1
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function process_query( $query_args = array(), $position = '' ) {
		}

		/**
		 * Load selector section page AJAX.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_pager_ajax( $position = '' ) {
			$reply_data['html_options'] = '';

			if ( $this->is_valid_position( $position ) ) {
				$this->query_selection_section_items( $position );
				$reply_data                 = $this->element_data[ $position ];
				$reply_data['html_options'] = $this->build_options_html( $position );
			}
			return $reply_data;
		}

		/**
		 * Load selector section search AJAX.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_search_ajax( $position = '' ) {
			$reply_data['html_options'] = '';

			if ( $this->is_valid_position( $position ) ) {
				$this->args['is_search'] = true;

				$this->query_selection_section_items( $position );
				if ( isset( $this->element_data[ $position ] ) ) {
					$reply_data                 = $this->element_data[ $position ];
					$reply_data['html_options'] = $this->build_options_html( $position );
				}
			}
			return $reply_data;
		}

		/**
		 * Get selector section nonce.
		 *
		 * @since 2.2.1
		 */
		protected function get_nonce_data() {
			return wp_create_nonce( $this->selector_class . '-' . get_current_user_id() );
		}

		/**
		 * Validate selector section nonce.
		 *
		 * @since 2.2.1
		 *
		 * @param string $nonce Nonce value to validate.
		 */
		public function validate_nonce_data( $nonce = '' ) {
			if ( ! empty( $nonce ) ) {
				return wp_verify_nonce( $nonce, $this->selector_class . '-' . get_current_user_id() );
			}
		}

		/**
		 * Utility function to check and validate the $position
		 * variable. It should be only 'left' or 'right'.
		 *
		 * @since 2.6.0
		 *
		 * @param string $position Should have value 'left' or 'right'.
		 *
		 * @return bool true if valid.
		 */
		public function is_valid_position( $position = '' ) {
			if ( ! empty( $position ) ) {
				$position = esc_attr( $position );
				if ( ( 'left' === $position ) || ( 'right' === $position ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Check the class is valid.
		 *
		 * Used by the learndash_binary_selector_pager_ajax() function.
		 *
		 * @since 3.2.0
		 *
		 * @param string $class_name Class name to check.
		 *
		 * @return bool true if valid.
		 */
		public static function check_class( $class_name ) {
			if ( ( ! empty( $class_name ) ) && ( in_array( $class_name, static::$allowed_classes, true ) ) ) {
				return true;
			}

			return false;
		}
	}
}

require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-users.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-course-users.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-group-users.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-group-leaders.php';

require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-posts.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-group-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-group-courses-enroll.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-course-groups.php';

require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-user-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-user-groups.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-leader-groups.php';

/**
 * Handler function for AJAX pager.
 *
 * @since 2.1.2
 */
function learndash_binary_selector_pager_ajax() {

	$reply_data = array( 'status' => false );

	if ( ( is_user_logged_in() ) && ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'learndash-binary-selector-' . get_current_user_id() ) ) ) {
		if ( ( isset( $_POST['query_data'] ) ) && ( ! empty( $_POST['query_data'] ) ) ) {
			if ( ( isset( $_POST['query_data']['query_vars'] ) ) && ( ! empty( $_POST['query_data']['query_vars'] ) ) ) {

				$args = $_POST['query_data']['query_vars']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( ( isset( $args['include'] ) ) && ( ! empty( $args['include'] ) ) ) {
					if ( learndash_is_valid_JSON( stripslashes( $args['include'] ) ) ) {
						$args['include'] = (array) json_decode( stripslashes( $args['include'] ) );
					}
				}

				if ( ( isset( $args['exclude'] ) ) && ( ! empty( $args['exclude'] ) ) ) {
					if ( learndash_is_valid_JSON( stripslashes( $args['exclude'] ) ) ) {
						$args['exclude'] = (array) json_decode( stripslashes( $args['exclude'] ) );
					}
				}

				if ( ( isset( $_POST['query_data']['selected_ids'] ) ) && ( ! empty( $_POST['query_data']['selected_ids'] ) ) ) {
					$args['selected_ids'] = (array) json_decode( wp_unslash( $_POST['query_data']['selected_ids'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}

				// Set our reference flag so other functions know we are running pager.
				$args['is_pager'] = true;

				if ( isset( $_POST['query_data']['selector_class'] ) ) {
					$bs_class = sanitize_text_field( wp_unslash( $_POST['query_data']['selector_class'] ) );
					if ( ( Learndash_Binary_Selector::check_class( $bs_class ) ) && ( is_subclass_of( $bs_class, 'Learndash_Binary_Selector' ) ) ) {

						$selector = new $bs_class( $args );

						if ( ( isset( $_POST['query_data']['selector_nonce'] ) ) && ( ! empty( $_POST['query_data']['selector_nonce'] ) ) ) {
							if ( $selector->validate_nonce_data( sanitize_text_field( wp_unslash( $_POST['query_data']['selector_nonce'] ) ) ) ) {
								if ( ( isset( $_POST['query_data']['position'] ) ) && ( ! empty( $_POST['query_data']['position'] ) ) ) {
									if ( ( isset( $_POST['query_data']['query_vars']['search'] ) ) && ( ! empty( $_POST['query_data']['query_vars']['search'] ) ) ) {
										$reply_data = $selector->load_search_ajax( sanitize_text_field( wp_unslash( $_POST['query_data']['position'] ) ) );
									} else {
										$reply_data = $selector->load_pager_ajax( sanitize_text_field( wp_unslash( $_POST['query_data']['position'] ) ) );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	if ( ! empty( $reply_data ) ) {
		echo wp_json_encode( $reply_data );
	}

	wp_die(); // this is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_learndash_binary_selector_pager', 'learndash_binary_selector_pager_ajax' );
