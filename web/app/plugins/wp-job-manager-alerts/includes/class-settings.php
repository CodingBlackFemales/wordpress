<?php
/**
 * File containing the class Settings.
 *
 * @package wp-job-manager-alerts
 * @since   3.0.0
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for handling the settings related to the WP Job Manager Alerts addon.
 */
class Settings {

	use Singleton;

	public const OPTION_ACCOUNT_REQUIRED        = 'job_manager_alerts_account_required';
	public const OPTION_BRAND_COLOR             = 'job_manager_alerts_brand_color';
	public const DEFAULT_BRAND_COLOR            = '#0453EB';
	public const OPTION_FORM_FIELDS             = 'job_manager_alerts_form_fields';
	public const OPTION_EMAIL_TEMPLATE          = 'job_manager_alerts_email_template';
	public const OPTION_JOB_DETAILS_VISIBLE     = 'job_manager_job_details_visible';
	public const DEFAULT_JOB_DETAILS_VISIBLE    = [ 'fields' => [ 'company_name', 'company_logo', 'location' ] ];
	public const OPTION_JOB_ALERTS_AUTO_DISABLE = 'job_manager_alerts_auto_disable';
	public const OPTION_JOB_MATCHES_ONLY        = 'job_manager_alerts_matches_only';
	public const OPTION_JOB_ALERTS_PAGE_ID      = 'job_manager_alerts_page_id';
	public const OPTION_ALERTS_PLUGIN_VERSION   = 'wp_job_manager_alerts_version';

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since 3.0.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up initial hooks.
	 */
	private function __construct() {
		add_filter( 'job_manager_settings', [ $this, 'settings' ] );
	}

	/**
	 * Add Settings
	 *
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = [] ) {
		if ( ! get_option( self::OPTION_EMAIL_TEMPLATE ) ) {
			delete_option( self::OPTION_EMAIL_TEMPLATE );
		}

		$settings['job_alerts'] = [
			__( 'Job Alerts', 'wp-job-manager-alerts' ),
			apply_filters(
				'wp_job_manager_alerts_settings',
				[
					[
						'name'     => self::OPTION_ACCOUNT_REQUIRED,
						'std'      => '1',
						'label'    => __( 'Account Required', 'wp-job-manager-alerts' ),
						'cb_label' => __( 'Require an account to create job alerts', 'wp-job-manager-alerts' ),
						'desc'     => __( 'Limit alert creation to registered, logged-in users.', 'wp-job-manager-alerts' ),
						'type'     => 'checkbox',
						'track'    => 'bool',
					],
					[
						'name'  => self::OPTION_BRAND_COLOR,
						'label' => __( 'Brand Color', 'wp-job-manager-alerts' ),
						'std'   => self::DEFAULT_BRAND_COLOR,
						'type'  => 'color',
						'desc'  => __( 'Set the color used for links and buttons in e-mails and shortcodes.', 'wp-job-manager-alerts' ),
						'track' => 'is-default',
					],
					[
						'name'    => self::OPTION_FORM_FIELDS,
						'label'   => __( 'Alert Form Fields', 'wp-job-manager-alerts' ),
						'type'    => 'multi_checkbox',
						'desc'    => 'Select what fields are displayed on the Add Alert form.',
						'options' => Alert_Form_Fields::get_default_fields(),
						'std'     => [
							'fields' => array_keys( Alert_Form_Fields::get_default_fields() ),
						],
						'track'   => 'is-default',
					],
					[
						'name'     => self::OPTION_EMAIL_TEMPLATE,
						'class'    => 'job-manager-alerts-email-template',
						'std'      => self::get_default_email(),
						'label'    => __( 'Alert Email Content', 'wp-job-manager-alerts' ),
						'desc'     => __( 'Enter the content for your email alerts or leave it blank to use the default message. The following tags can be used to insert data dynamically:', 'wp-job-manager-alerts' ) . '<br/><br/>' .
							'<code>{alert_name}</code> - ' . __( 'The name of the alert being sent', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{jobs}</code> - ' . __( 'The jobs found matching your alert', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_next_date}</code> - ' . __( 'The next date this alert will be sent', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_expiry}</code> - ' . __( 'When this job alert expires', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{display_name}</code> - ' . __( 'The user WordPress username', 'wp-job-manager-alerts' ) . '<br/>' .
							'<br>' .
							'<strong>Note: </strong> {display_name}' . __( ' is not available when accounts are not required.', 'wp-job-manager-alerts' ),
						'type'     => 'textarea',
						'required' => true,
						'track'    => 'is-default',
					],
					[
						'name'    => self::OPTION_JOB_DETAILS_VISIBLE,
						'class'   => 'job_details_visible',
						'label'   => __( 'Job Details Visible', 'wp-job-manager-alerts' ),
						'type'    => 'multi_checkbox',
						'options' => [
							'company_name' => __( 'Company Name', 'wp-job-manager-alerts' ),
							'company_logo' => __( 'Company Logo', 'wp-job-manager-alerts' ),
							'location'     => __( 'Location', 'wp-job-manager-alerts' ),
						],
						'std'     => self::DEFAULT_JOB_DETAILS_VISIBLE,
						'track'   => 'is-default',
					],
					[
						'name'  => self::OPTION_JOB_ALERTS_AUTO_DISABLE,
						'std'   => '90',
						'label' => __( 'Alert Duration', 'wp-job-manager-alerts' ),
						'desc'  => __( 'Enter the number of days before alerts are automatically disabled, or leave blank to disable this feature. By default, alerts will be turned off for a search after 90 days.', 'wp-job-manager-alerts' ),
						'type'  => 'input',
						'track' => 'value',
					],
					[
						'name'     => self::OPTION_JOB_MATCHES_ONLY,
						'std'      => '0',
						'label'    => __( 'Alert Matches', 'wp-job-manager-alerts' ),
						'cb_label' => __( 'Send alerts with matches only', 'wp-job-manager-alerts' ),
						'desc'     => __( 'Only send an alert when jobs are found matching its criteria. When disabled, an alert is sent regardless.', 'wp-job-manager-alerts' ),
						'type'     => 'checkbox',
						'track'    => 'bool',
					],
					[
						'name'  => self::OPTION_JOB_ALERTS_PAGE_ID,
						'std'   => '',
						'label' => __( 'Alerts Page ID', 'wp-job-manager-alerts' ),
						'desc'  => __( 'So that the plugin knows where to link users to view their alerts, you must select the page where you have placed the [job_alerts] shortcode.', 'wp-job-manager-alerts' ),
						'type'  => 'page',
						'track' => 'bool',
					],
				]
			),
		];
		return $settings;
	}

	/**
	 * Whether signing in is required to create an alert.
	 *
	 * @return bool
	 */
	public function is_account_required() {
		return (bool) get_option( self::OPTION_ACCOUNT_REQUIRED );
	}

	/**
	 * Get the page with the [job_alerts] shortcode.
	 */
	public function get_alerts_page() {
		return get_option( 'job_manager_alerts_page_id' );
	}

	/**
	 * Return the default email content for alerts
	 */
	public function get_default_email() {
		return __(
			'Hello {display_name},

The following jobs were found matching your "{alert_name}" job alert.

{jobs}

Your next alert for this search will be sent {alert_next_date}.

{alert_expiry}
',
			'wp-job-manager-alerts'
		);
	}

	/**
	 * Get job details to show in alert emails.
	 *
	 * @return array
	 */
	public function get_visible_email_fields() {
		$option = get_option( self::OPTION_JOB_DETAILS_VISIBLE, self::DEFAULT_JOB_DETAILS_VISIBLE );
		return $option['fields'] ?? [];
	}

	/**
	 * Get the brand color.
	 *
	 * @return string
	 */
	public function get_the_brand_color() {
		return get_option( self::OPTION_BRAND_COLOR, self::DEFAULT_BRAND_COLOR );
	}

	/**
	 * Get the alert consent message.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $with_checkbox whether the form has a checkbox or not.
	 * @return string
	 */
	public static function get_alert_consent_message( $with_checkbox = false ) {
		$privacy_policy_url = get_privacy_policy_url();
		$main_text          = $with_checkbox ? __( 'I agree to receiving job alert e-mails', 'wp-job-manager-alerts' ) : __( 'By subscribing, you agree to receive job alert e-mails', 'wp-job-manager-alerts' );

		if ( ! empty( $privacy_policy_url ) ) {
			$message = sprintf(
				/* Translators: 1: beginning text, 2: opening anchor tag, 3: closing anchor tag. */
				esc_html__( '%1$s and accept the %2$sPrivacy Policy%3$s.', 'wp-job-manager-alerts' ),
				$main_text,
				'<a href="' . esc_url( $privacy_policy_url ) . '">',
				'</a>'
			);
		} else {
			// Translators: %s: alert consent message when no privacy policy is set.
			$message = sprintf( esc_html__( '%s.', 'wp-job-manager-alerts' ), $main_text );
		}
		/**
		 * Filters the alert consent message.
		 *
		 * @since 3.0.0
		 *
		 * @param string $message The alert consent message.
		 */
		return apply_filters(
			'job_manager_alerts_permission_checkbox_label',
			$message
		);
	}
}
