<?php
/**
 * LearnDash course reviews module main included file.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Course_Reviews
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'LEARNDASH_COURSE_REVIEWS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_COURSE_REVIEWS_URL', plugins_url( '/', __FILE__ ) );
define( 'LEARNDASH_COURSE_REVIEWS_FILE', __FILE__ );

require_once LEARNDASH_COURSE_REVIEWS_DIR . 'core/learndash-course-reviews-functions.php';

if ( ! class_exists( 'LearnDash_Course_Reviews' ) ) {
	/**
	 * Main LearnDash_Course_Reviews class.
	 *
	 * @since 4.25.1
	 */
	final class LearnDash_Course_Reviews {
		/**
		 * RBM Field Helpers Object.
		 *
		 * @var object $field_helpers
		 *
		 * @since 4.25.1
		 * @deprecated 4.25.1
		 */
		public $field_helpers;

		/**
		 * Get active instance.
		 *
		 * @access public
		 * @since 4.25.1
		 * @return LearnDash_Course_Reviews Instance.
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new self();
			}

			return $instance;
		}

		/**
		 * LearnDash_Course_Reviews constructor.
		 *
		 * @since 4.25.1
		 */
		public function __construct() {
			$this->require_necessities();

			// Register our CSS/JS for the whole plugin.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 1 );
		}

		/**
		 * Includes different aspects of the plugin.
		 *
		 * @access private
		 * @since 4.25.1
		 *
		 * @return void
		 */
		private function require_necessities() {
			require_once LEARNDASH_COURSE_REVIEWS_DIR . 'core/class-learndash-course-reviews-walker.php';

			require_once LEARNDASH_COURSE_REVIEWS_DIR . 'core/class-learndash-course-reviews-loader.php';

			require_once LEARNDASH_COURSE_REVIEWS_DIR . 'core/class-learndash-course-reviews-rest.php';

			require_once LEARNDASH_COURSE_REVIEWS_DIR . 'core/admin/class-learndash-course-reviews-comment-edit.php';
		}

		/**
		 * Outputs Admin Notices.
		 *
		 * This is useful if you're too early in execution to use the add_settings_error()
		 * function as you can save them for later.
		 *
		 * @access public
		 * @since 4.25.1
		 * @deprecated 4.25.1
		 *
		 * @return void
		 */
		public function admin_notices() {
			_deprecated_function( __FUNCTION__, '4.25.1' );
		}

		/**
		 * Registers our CSS/JS to use later.
		 *
		 * @access public
		 * @since 4.25.1
		 *
		 * @return void
		 */
		public function register_scripts() {
			wp_register_style(
				'learndash-course-reviews',
				LEARNDASH_COURSE_REVIEWS_URL . 'dist/styles.css',
				array(),
				defined( 'LEARNDASH_SCRIPT_DEBUG' ) && LEARNDASH_SCRIPT_DEBUG ? strval( time() ) : LEARNDASH_VERSION
			);

			wp_register_script(
				'learndash-course-reviews',
				LEARNDASH_COURSE_REVIEWS_URL . 'dist/scripts.js',
				array( 'jquery' ),
				defined( 'LEARNDASH_SCRIPT_DEBUG' ) && LEARNDASH_SCRIPT_DEBUG ? strval( time() ) : LEARNDASH_VERSION,
				true
			);

			wp_localize_script(
				'learndash-course-reviews',
				'learndashCourseReviews',
				array(
					'restURL' => esc_url_raw( rest_url() ) . 'learndashCourseReviews/v1/',
				)
			);
		}
	}
} // End Class Exists Check

add_action( 'learndash_init', 'learndash_course_reviews_load' );
