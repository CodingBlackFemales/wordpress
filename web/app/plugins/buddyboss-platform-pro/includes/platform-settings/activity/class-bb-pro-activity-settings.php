<?php
/**
 * BuddyBoss Activity Settings.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Platform Settings/Activity
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bb activity settings class.
 *
 * @since 2.9.0
 */
class BB_Pro_Activity_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.9.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Feature manager's array.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_Activity_Post_Feature_Image
	 */
	private $feature_image_managers;

	/**
	 * Get the instance of this class.
	 *
	 * @since 2.9.0
	 *
	 * @return object Instance.
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Activity Settings Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {

		// Include the code.
		$this->setup_actions();
	}

	/**
	 * Setup actions for activity Settings.
	 *
	 * @since 2.9.0
	 */
	public function setup_actions() {
		$this->load_feature_managers();
	}

	/**
	 * Load and initialize feature-specific managers.
	 *
	 * @since 2.9.0
	 */
	public function load_feature_managers() {
		// Load feature-specific files.
		$this->load_feature_files();

		// Initialize feature managers.
		$this->initialize_feature_managers();
	}

	/**
	 * Load feature-specific files.
	 *
	 * @since 2.9.0
	 */
	private function load_feature_files() {
		$feature_image_dir = __DIR__ . '/post-feature-image/';

		// Load feature image class file.
		if ( file_exists( $feature_image_dir . 'class-bb-activity-post-feature-image.php' ) ) {
			require_once $feature_image_dir . 'class-bb-activity-post-feature-image.php';
		}
	}

	/**
	 * Initialize feature managers.
	 *
	 * @since 2.9.0
	 */
	private function initialize_feature_managers() {
		// Feature Image Manager.
		if ( class_exists( 'BB_Activity_Post_Feature_Image' ) ) {
			$this->feature_image_managers = BB_Activity_Post_Feature_Image::instance();
		}
	}
}
