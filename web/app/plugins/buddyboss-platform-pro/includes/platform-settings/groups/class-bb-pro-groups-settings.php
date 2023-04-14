<?php
/**
 * BuddyBoss Groups Settings.
 *
 * @package BuddyBossPro/Platform Settings/Groups
 *
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bb groups settings class.
 *
 * @since 1.2.0
 */
class BB_Pro_Groups_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.2.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Groups Settings Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		// Include the code.
		$this->setup_actions();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.2.0
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
	 * Setup actions for groups Settings.
	 *
	 * @since 1.2.0
	 */
	public function setup_actions() {
		// Registered group cover image width.
		add_filter( 'bb_admin_setting_field_bb-cover-group-width', array( $this, 'bb_admin_register_group_cover_image_width_field' ) );
		// Registered group cover image height.
		add_filter( 'bb_admin_setting_field_bb-cover-group-height', array( $this, 'bb_admin_register_group_cover_image_height_field' ) );

		// Registered group grid style.
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-grid-style', array( $this, 'bb_admin_register_group_grid_style_field' ) );

		// Registered group elements.
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-cover-images', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-avatars', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-group-privacy', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-group-type', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-last-activity', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-members', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-group-descriptions', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-directory-layout-element-join-buttons', array( $this, 'bb_admin_register_group_directory_layout_elements_field' ) );

		// Registered group header style.
		add_filter( 'bb_admin_setting_field_bb-group-header-style', array( $this, 'bb_admin_register_group_header_style_field' ) );

		// Registered group headers elements.
		add_filter( 'bb_admin_setting_field_bb-group-headers-element-group-type', array( $this, 'bb_admin_register_group_directory_layout_headers_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-headers-element-group-activity', array( $this, 'bb_admin_register_group_directory_layout_headers_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-headers-element-group-description', array( $this, 'bb_admin_register_group_directory_layout_headers_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-headers-element-group-organizers', array( $this, 'bb_admin_register_group_directory_layout_headers_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-group-headers-element-group-privacy', array( $this, 'bb_admin_register_group_directory_layout_headers_elements_field' ) );

		// Save settings.
		add_action( 'bp_admin_tab_setting_save', array( $this, 'bb_admin_registered_group_setting_fields_save' ), 10, 1 );
	}

	/**
	 * Create field attributes array of group cover image width field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group cover image width.
	 */
	public function bb_admin_register_group_cover_image_width_field( $args ) {
		$args['name']     = 'bb-pro-cover-group-width';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of group cover image height field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group cover image height.
	 */
	public function bb_admin_register_group_cover_image_height_field( $args ) {
		$args['name']     = 'bb-pro-cover-group-height';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of group header style field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group header style.
	 */
	public function bb_admin_register_group_header_style_field( $args ) {

		$args['name']     = 'bb-pro-group-single-page-header-style';
		$args['disabled'] = false;
		$args['value']    = bb_platform_pro_group_header_style();

		return $args;
	}

	/**
	 * Create field attributes array of group headers elements field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group headers elements.
	 */
	public function bb_admin_register_group_directory_layout_headers_elements_field( $args ) {

		$args['name']     = 'bb-pro-group-single-page-headers-elements[]';
		$args['disabled'] = false;
		$args['selected'] = bb_platform_pro_group_headers_element_enable( $args['value'] ) ? $args['value'] : '';

		return $args;
	}

	/**
	 * Create field attributes array of group grid style field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group grid style.
	 */
	public function bb_admin_register_group_grid_style_field( $args ) {
		$args['name']     = 'bb-pro-group-directory-layout-grid-style';
		$args['disabled'] = false;
		$args['value']    = bb_platform_pro_group_grid_style();

		return $args;
	}

	/**
	 * Create field attributes array of group elements field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of group elements.
	 */
	public function bb_admin_register_group_directory_layout_elements_field( $args ) {
		$args['name']     = 'bb-pro-group-directory-layout-elements[]';
		$args['disabled'] = false;
		$args['selected'] = bb_platform_pro_group_element_enable( $args['value'] ) ? $args['value'] : '';

		return $args;
	}

	/**
	 * Save registered settings to DB.
	 *
	 * @since 1.2.0
	 *
	 * @param string $current_tab Current setting tab.
	 */
	public function bb_admin_registered_group_setting_fields_save( $current_tab ) {

		if ( 'bp-groups' === $current_tab ) {

			// Group cover sizes default options.
			$group_cover_width  = 'default';
			$group_cover_height = 'small';

			// Group style default options.
			$group_grid_style   = 'left';
			$group_header_style = 'left';
			$headers_elements   = array(
				'group-type',
				'group-activity',
				'group-description',
				'group-organizers',
				'group-privacy',
			);
			$group_elements     = array(
				'cover-images',
				'avatars',
				'group-privacy',
				'group-type',
				'last-activity',
				'members',
				'group-descriptions',
				'join-buttons',
			);

			if ( bbp_pro_is_license_valid() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$group_cover_width = isset( $_POST['bb-pro-cover-group-width'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-cover-group-width'] ) ) : 'default';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$group_cover_height = isset( $_POST['bb-pro-cover-group-height'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-cover-group-height'] ) ) : 'small';

				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$group_grid_style = isset( $_POST['bb-pro-group-directory-layout-grid-style'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-group-directory-layout-grid-style'] ) ) : 'left';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['bb-pro-group-directory-layout-elements'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$group_elements = is_array( $_POST['bb-pro-group-directory-layout-elements'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bb-pro-group-directory-layout-elements'] ) ) : sanitize_text_field( wp_unslash( $_POST['bb-pro-group-directory-layout-elements'] ) );
				} else {
					$group_elements = array();
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$group_header_style = isset( $_POST['bb-pro-group-single-page-header-style'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-group-single-page-header-style'] ) ) : 'left';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['bb-pro-group-single-page-headers-elements'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$headers_elements = is_array( $_POST['bb-pro-group-single-page-headers-elements'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bb-pro-group-single-page-headers-elements'] ) ) : sanitize_text_field( wp_unslash( $_POST['bb-pro-group-single-page-headers-elements'] ) );
				} else {
					$headers_elements = array();
				}
			}

			bp_update_option( 'bb-pro-cover-group-width', $group_cover_width );
			bp_update_option( 'bb-pro-cover-group-height', $group_cover_height );

			bp_update_option( 'bb-pro-group-directory-layout-grid-style', $group_grid_style );
			bp_update_option( 'bb-pro-group-directory-layout-elements', $group_elements );

			bp_update_option( 'bb-pro-group-single-page-header-style', $group_header_style );
			bp_update_option( 'bb-pro-group-single-page-headers-elements', $headers_elements );
		}

	}
}
