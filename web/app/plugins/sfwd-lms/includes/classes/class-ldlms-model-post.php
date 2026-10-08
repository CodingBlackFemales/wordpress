<?php
/**
 * Abstract class to extend LDLMS_Model to LDLMS_Model_Post.
 *
 * @since 2.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Post' ) ) && ( class_exists( 'LDLMS_Model' ) ) ) {
	/**
	 * Class for LearnDash Model Post.
	 *
	 * @since 2.5.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_Post extends LDLMS_Model {

		/**
		 * Post ID of Model.
		 *
		 * @var integer $id.
		 */
		protected $post_id = null;

		/**
		 * Post Type of Model.
		 *
		 * @var string $post_type WP_Post post_type.
		 */
		protected $post_type = null;

		/**
		 * Post Object of Model.
		 *
		 * @var object $post WP_Post instance.
		 */
		protected $post = null;

		/**
		 * Post Settings of Model.
		 *
		 * @var array $post_settings Array of Post Settings.
		 */
		protected $settings = null;

		/**
		 * Settings loaded for Model.
		 *
		 * @var boolean $settings_loaded Set to true when settings have been loaded.
		 */
		protected $settings_loaded = false;

		/**
		 * Settings changed for Model.
		 *
		 * @var boolean $settings_changed Set to true when settings have changed.
		 */
		protected $settings_changed = false;

		/**
		 * Private constructor for class.
		 */
		private function __construct() {
		}

		/**
		 * Get class post_type
		 *
		 * @return string.
		 */
		public function get_post_type() {
			return $this->post_type;
		}

		/**
		 * Load the Model Settings.
		 *
		 * @since 3.2.0
		 * @param boolean $force Control reloading of settings.
		 *
		 * @return boolean Status of settings loaded class var.
		 */
		public function load_settings( $force = false ) {
			if ( ( is_a( $this->post, 'WP_Post' ) ) && ( ( true !== $this->settings_loaded ) || ( true === $force ) ) ) {
				$this->settings_loaded = true;
				$this->settings        = array();

				$meta = get_post_meta( $this->post_id, '_' . $this->post_type, true );
				if ( ( ! empty( $meta ) ) && ( is_array( $meta ) ) ) {
					foreach ( $meta as $k => $v ) {
						$this->settings[ str_replace( $this->post_type . '_', '', $k ) ] = $v;
					}
				}
			}

			return $this->settings_loaded;
		}

		/**
		 * Save the Model Settings.
		 *
		 * @since 3.2.0
		 * @param boolean $force Control reloading of settings.
		 *
		 * @return boolean Status of settings loaded class var.
		 */
		public function save_settings( $force = false ) {
			$return = false;

			if ( ( true === $force ) || ( true === $this->settings_changed ) ) {
				$meta = array();
				foreach ( $this->settings as $k => $v ) {
					$meta[ '_' . $this->post_type . '_' . $k ] = $v;
				}

				$return = update_post_meta( $this->post_id, '_' . $this->post_type, $meta );
			}

			return $return;
		}

		/**
		 * Get Setting.
		 *
		 * @since 3.4.0
		 *
		 * @param string $setting_key           Setting key to retrieve. Blank to retrieve all settings.
		 * @param string $setting_default_value Setting default value if setting_key is not set.
		 * @param bool   $force                 Control reloading of settings.
		 *
		 * @return mixed Setting value.
		 */
		public function get_setting( $setting_key = '', $setting_default_value = '', $force = false ) {
			$setting_value = $setting_default_value;

			$this->load_settings( $force );

			if ( ! empty( $setting_key ) ) {
				if ( isset( $this->settings[ $setting_key ] ) ) {
					$setting_value = $this->settings[ $setting_key ];
				}
			} else {
				$setting_value = $this->settings;
			}

			return $setting_value;
		}

		/**
		 * Set Setting.
		 *
		 * @since 3.4.0
		 *
		 * @param string $setting_key           Setting key to retrieve. Blank to retrieve all settings.
		 * @param string $setting_value         Setting default value if setting_key is not set.
		 * @param bool   $force                 Control saving postmeta after of settings change.
		 *
		 * @return mixed Setting value.
		 */
		public function set_setting( $setting_key = '', $setting_value = '', $force = false ) {
			$this->load_settings( $force );

			if ( ! empty( $setting_key ) ) {
				$this->settings[ $setting_key ] = $setting_value;
				$this->settings_changed         = true;

				// $update
			}

			return $setting_value;
		}

		// End of functions.
	}
}
