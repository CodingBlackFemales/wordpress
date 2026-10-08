<?php
/**
 * Deprecated LearnDash_Notifications class file.
 *
 * @deprecated 5.2.0
 *
 * @package LearnDash\Notifications\Deprecated
 */

_deprecated_file( __FILE__, '5.2.0' );

if ( ! class_exists( 'LearnDash_Notifications' ) ) {
	/**
	 * Deprecated main class.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	final class LearnDash_Notifications {
		/**
		 * The one and only true LearnDash_Notifications instance
		 *
		 * @since 5.2.0
		 * @access private
		 * @var object $instance
		 */
		private static $instance;

		/**
		 * Instantiate the main class
		 *
		 * This function instantiates the class, initialize all functions and return the object.
		 *
		 * @return object The one and only true LearnDash_Notifications instance.
		 * @since 5.2.0
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ( ! self::$instance instanceof LearnDash_Notifications ) ) {
				self::$instance = new LearnDash_Notifications();
				self::$instance->setup_constants();
				include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/class-map.php';
				add_action( 'plugins_loaded', [ self::$instance, 'load_textdomain' ] );

				self::$instance->check_dependency();

				add_action(
					'plugins_loaded',
					function () {
						if (
						LearnDash_Dependency_Check_LD_Notifications::get_instance()->check_dependency_results()
						|| php_sapi_name() === 'cli'
						) {
							self::$instance->includes();
							self::$instance->includes_after_plugins_loaded();
							self::$instance->init();
						}
					}
				);
			}

			return self::$instance;
		}

		/**
		 * Getting all registered notifications, and place the sniffing here
		 */
		public function init() {
			$triggers = [
				\LearnDash_Notification\Trigger\Enroll_Group::class,
				\LearnDash_Notification\Trigger\Enroll_Course::class,
				\LearnDash_Notification\Trigger\Complete_Course::class,
				\LearnDash_Notification\Trigger\Complete_Lesson::class,
				\LearnDash_Notification\Trigger\Drip_Lesson_Available::class,
				\LearnDash_Notification\Trigger\Complete_Topic::class,
				\LearnDash_Notification\Trigger\Quiz_Passed::class,
				\LearnDash_Notification\Trigger\Quiz_Failed::class,
				\LearnDash_Notification\Trigger\Quiz_Submitted::class,
				\LearnDash_Notification\Trigger\Quiz_Completed::class,
				\LearnDash_Notification\Trigger\Essay_Submitted::class,
				\LearnDash_Notification\Trigger\Essay_Graded::class,
				\LearnDash_Notification\Trigger\Assignment_Uploaded::class,
				\LearnDash_Notification\Trigger\Assignment_Approved::class,
				\LearnDash_Notification\Trigger\User_Login_Track::class,
				\LearnDash_Notification\Trigger\Before_Course_Expire::class,
				\LearnDash_Notification\Trigger\After_Course_Expire::class,
			];

			foreach ( $triggers as $trigger ) {
				$class = new $trigger();
				$class->listen();
			}
		}

		/**
		 * Function for setting up constants
		 *
		 * This function is used to set up constants used throughout the plugin.
		 *
		 * @since 5.2.0
		 */
		public function setup_constants() {
			// Plugin version.
			if ( ! defined( 'LEARNDASH_NOTIFICATIONS_VERSION' ) ) {
				define( 'LEARNDASH_NOTIFICATIONS_VERSION', '1.6.2' );
			}

			// Plugin file.
			if ( ! defined( 'LEARNDASH_NOTIFICATIONS_FILE' ) ) {
				define( 'LEARNDASH_NOTIFICATIONS_FILE', __FILE__ );
			}

			// Plugin folder path.
			if ( ! defined( 'LEARNDASH_NOTIFICATIONS_PLUGIN_DIR' ) ) {
				define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin folder URL.
			if ( ! defined( 'LEARNDASH_NOTIFICATIONS_PLUGIN_URL' ) ) {
				define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
		}

		/**
		 * Load text domain used for translation
		 *
		 * The strings now use the `learndash` text domain, which LearnDash core loads, and the
		 * add-on's own translations settings section is deprecated, so this method does nothing.
		 *
		 * @since 5.2.0
		 * @deprecated 5.2.0
		 */
		public function load_textdomain() {
			_deprecated_function( __METHOD__, '5.2.0' );
		}

		/**
		 * Check and set plugin dependencies
		 *
		 * @return void
		 */
		public function check_dependency() {
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-dependency-check.php';

			LearnDash_Dependency_Check_LD_Notifications::get_instance()->set_dependencies(
				[
					'sfwd-lms/sfwd_lms.php' => [
						'label'       => '<a href="https://learndash.com">LearnDash LMS</a>',
						'class'       => 'SFWD_LMS',
						'min_version' => '3.2.0',
					],
				]
			);

			LearnDash_Dependency_Check_LD_Notifications::get_instance()->set_message(
				__( 'LearnDash LMS - Notifications Add-on requires the following plugin(s) to be active:', 'learndash' )
			);
		}

		/**
		 * Includes all necessary PHP files
		 *
		 * This function is responsible for including all necessary PHP files.
		 *
		 * @since 5.2.0
		 */
		public function includes() {
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/functions.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/logger.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/cron.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/deactivation.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/database.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/meta-box.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/notification.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/post-type.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/shortcode.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/tools.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/update.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/user.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/subscription-manager.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/ajax.php';
		}

		public function includes_after_plugins_loaded() {
			if ( is_admin() ) {
				include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-settings.php';
				include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-status-page.php';
				include LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-logs-page.php';
			}
		}
	}
}
