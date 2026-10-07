<?php
/**
 * Enrollment Status section class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Admin\Pages\Sections;

use LearnDash\Core\Template\Template;
use LearnDash_Settings_Section;
use WP_Screen;

/**
 * Enrollment Status section class.
 *
 * @since 5.2.0
 */
class Settings_Enrollment_Status extends LearnDash_Settings_Section {
	/**
	 * Order status setting prefix.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	public static string $order_setting_prefix = 'order_';

	/**
	 * Subscription status setting prefix.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	public static string $subscription_setting_prefix = 'subscription_';

	/**
	 * Order statuses.
	 *
	 * @since 5.2.0
	 *
	 * @var array<string, string>
	 */
	private array $order_statuses = [];

	/**
	 * Subscription statuses.
	 *
	 * @since 5.2.0
	 *
	 * @var array<string, string>
	 */
	private array $subscription_statuses = [];

	/**
	 * Constructor.
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		$this->settings_screen_id           = 'admin_page_learndash-woocommerce-settings';
		$this->settings_page_id             = 'learndash-woocommerce-settings';
		$this->setting_option_key           = 'learndash_woocommerce_enrollment_status_settings';
		$this->setting_field_prefix         = 'learndash_woocommerce_enrollment_status_settings';
		$this->settings_section_key         = 'enrollment_status_settings';
		$this->settings_section_label       = esc_html__( 'Enrollment Status', 'learndash' );
		$this->settings_section_description = esc_html__( 'Control the LearnDash enrollment status based on WooCommerce order and subscription statuses.', 'learndash' );

		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$this->order_statuses = wc_get_order_statuses();
		}

		if ( function_exists( 'wcs_get_subscription_statuses' ) ) {
			$this->subscription_statuses = wcs_get_subscription_statuses();
		}

		parent::__construct();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		$screen = get_current_screen();

		if (
			! $screen instanceof WP_Screen
			|| $screen->id !== 'admin_page_learndash-woocommerce-settings'
		) {
			return;
		}

		wp_add_inline_style(
			'learndash-admin-settings-page',
			'
			.col-name-label {
				width: 400px;
			}
			'
		);
	}

	/**
	 * Load settings values.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function load_settings_values(): void {
		parent::load_settings_values();

		if (
			$this->setting_option_initialized
			|| ! empty( $this->setting_option_values )
		) {
			return;
		}

		$this->setting_option_values = [];

		$this->set_default_setting_option_values( 'order' );
		$this->set_default_setting_option_values( 'subscription' );
	}


	/**
	 * Load settings fields.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function load_settings_fields(): void {
		$this->setting_option_fields = [];

		foreach ( $this->order_statuses as $status => $label ) {
			$this->set_status_field( self::$order_setting_prefix, $status, $label );
		}

		foreach ( $this->subscription_statuses as $status => $label ) {
			$this->set_status_field( self::$subscription_setting_prefix, $status, $label );
		}

		/** This filter is documented in LearnDash Core includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters(
			'learndash_settings_fields',
			$this->setting_option_fields,
			$this->settings_section_key
		);

		parent::load_settings_fields();
	}

	/**
	 * Output the settings section.
	 *
	 * @since 5.2.0
	 *
	 * @param array<string, array<string, string>|callable>|string|null $section Section to be shown. Optional.
	 *
	 * @return void
	 */
	public function show_settings_section( $section = null ): void {
		if (
			! is_null( $section )
			&& ! empty( $section['callback'] )
			&& is_callable( $section['callback'] )
		) {
			call_user_func( $section['callback'] );
		}

		Template::show_admin_template(
			'settings/sections/enrollment-status',
			[
				'nonce_field'           => wp_nonce_field( $this->setting_option_key, $this->setting_option_key . '_nonce', true, false ),
				'setting_option_fields' => $this->setting_option_fields,
				'order_statuses'        => $this->order_statuses,
				'subscription_statuses' => $this->subscription_statuses,
				'prefix_order'          => self::$order_setting_prefix,
				'prefix_subscription'   => self::$subscription_setting_prefix,
			]
		);
	}

	/**
	 * Sets default setting option values.
	 *
	 * @since 5.2.0
	 *
	 * @param string $type Either 'order' or 'subscription'.
	 *
	 * @return void
	 */
	private function set_default_setting_option_values( string $type ): void {
		if ( ! in_array( $type, [ 'order', 'subscription' ], true ) ) {
			return;
		}

		if ( $type === 'order' ) {
			$statuses = $this->order_statuses;
			$prefix   = self::$order_setting_prefix;

			$default_statuses = [
				'wc-completed',
				'wc-processing',
			];
		} else {
			$statuses = $this->subscription_statuses;
			$prefix   = self::$subscription_setting_prefix;

			$default_statuses = [
				'wc-active',
				'wc-pending-cancel',
				'wc-on-hold',
			];
		}

		foreach ( $statuses as $status => $label ) {
			$default_value = in_array( $status, $default_statuses, true )
				? 'on'
				: '';

			$this->setting_option_values[ $prefix . $status ] = $default_value;
		}
	}

	/**
	 * Sets status field.
	 *
	 * @since 5.2.0
	 *
	 * @param string $prefix Status key prefix.
	 * @param string $status Status key.
	 * @param string $label  Status label.
	 *
	 * @return void
	 */
	private function set_status_field( string $prefix, string $status, string $label ): void {
		$key = $prefix . $status;

		$this->setting_option_fields[ $key ] = [
			'name'    => $key,
			'type'    => 'checkbox-switch',
			'label'   => $label,
			'value'   => $this->setting_option_values[ $key ] ?? '',
			'options' => [
				''   => esc_html__( 'Deny', 'learndash' ),
				'on' => esc_html__( 'Grant', 'learndash' ),
			],
		];
	}
}
