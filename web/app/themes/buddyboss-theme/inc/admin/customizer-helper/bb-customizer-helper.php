<?php
/**
 * Customizer Helper Class.
 * This file will merge options value when change with customizer preview.
 *
 * @package BB_Customizer_Helper
 * @class   BB_Customizer_Helper
 * @version 1.8.4
 */

defined( 'ABSPATH' ) || exit;

// Don't duplicate me!
if ( ! class_exists( 'BB_Customizer_Helper', false ) ) {
	
	/**
	 * Main Customizer_Helper customizer extension class
	 *
	 * @since 1.8.4
	 */
	class BB_Customizer_Helper {
		
		/**
		 * Set parent option.
		 *
		 * @since 1.8.4
		 * @var array
		 */
		public $parent;
		
		/**
		 * Set customizer DB values.
		 *
		 * @since 1.8.4
		 * @var array
		 */
		public static $changed_value;
		
		/**
		 * Customizer_Helper constructor.
		 *
		 * @param object $parent ReduxFramework pointer.
		 *
		 * @since 1.8.4
		 */
		public function __construct( $parent ) {
			$this->parent = $parent;
			$this->load();
		}
		
		/**
		 * Load hook and filters.
		 *
		 * @since 1.8.4
		 */
		public function load() {
			add_action( "redux/options/{$this->parent->args['opt_name']}/options", array( $this, 'bb_override_values' ), 101, 1 );
			add_action( 'customize_save', array( $this, 'bb_customizer_save_before' ) );
		}
		
		/**
		 * Override customizer values.
		 * Override redux framework function here because facing issue.
		 * Also create issue on redux framework's repo - https://github.com/reduxframework/redux-framework/issues/3839
		 *
		 * @since 1.8.4
		 *
		 * @param array $data Values Get all options value.
		 *
		 * @return array $data Return all options value.
		 */
		public function bb_override_values( array $data ): array {
			if ( isset( $_POST['customized'] ) && ! empty( $_POST['customized'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$current_post_value          = json_decode( stripslashes_deep( sanitize_text_field( wp_unslash( $_POST['customized'] ) ) ), true ); // phpcs:ignore WordPress.Security.NonceVerification
				$bb_existing_customized_data = isset( $_POST['customize_changeset_uuid'] ) ? $this->bb_existing_customized_data( $_POST['customize_changeset_uuid'], 'customize_changeset' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! empty( $bb_existing_customized_data ) && ! empty( $current_post_value ) ) {
					$current_post_value = array_merge( $bb_existing_customized_data, $current_post_value );
				}
				self::$changed_value = $current_post_value;
				if ( ! empty( $current_post_value ) ) {
					foreach ( $current_post_value as $key => $value ) {
						if ( strpos( $key, $this->parent->args['opt_name'] ) !== false ) {
							$key                                                       = str_replace( $this->parent->args['opt_name'] . '[', '', rtrim( $key, ']' ) );
							$data[ $key ]                                              = $value;
							$GLOBALS[ $this->parent->args['global_variable'] ][ $key ] = $value;
							$this->parent->options[ $key ]                             = $value;
						}
					}
				}
			}
			return $data;
		}

		/**
		 * Get post content for customize_changeset based on current customize_changeset_uuid.
		 *
		 * @since 1.8.4
		 *
		 * @param string $post_type Post type.
		 * @param string $post_name Post name.
		 *
		 * @return array Return customized data.
		 */
		public function bb_existing_customized_data( $post_name, $post_type ) {
			global $wpdb;
			$customized_query = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE post_name = %s AND post_type= %s", $post_name, $post_type ) );
			$customized_data  = array();
			if ( ! empty( $customized_query ) ) {
				$existing_data = json_decode( $customized_query, true );
				$current_theme = wp_get_theme();
				if ( ! empty( $existing_data ) ) {
					foreach ( $existing_data as $raw_setting_id => $setting_params ) {
						$replace_key_word                   = $current_theme->get_stylesheet() . '::';
						$raw_setting_id                     = str_replace( $replace_key_word, '', $raw_setting_id );
						$customized_data[ $raw_setting_id ] = $setting_params['value'];
					}
				}
			}
			return $customized_data;
		}
		
		/**
		 * Function will fire before publish customizer data.
		 * Note - Before save need to unset new changes from parent options because redux framework
		 * check changes data while do process for save.
		 *
		 * @since 1.8.4
		 */
		public function bb_customizer_save_before() {
			$current_theme = wp_get_theme();
			if ( ! empty( self::$changed_value ) ) {
				foreach ( self::$changed_value as $key => $value ) {
					if ( strpos( $key, $this->parent->args['opt_name'] ) !== false ) {
						$replace_key_word = $current_theme->get_stylesheet() . '::';
						$key              = str_replace( $replace_key_word, '', $key );
						$key              = str_replace( $this->parent->args['opt_name'] . '[', '', rtrim( $key, ']' ) );
						unset( $this->parent->options[ $key ] );
					}
				}
			}
		}
	}
}
