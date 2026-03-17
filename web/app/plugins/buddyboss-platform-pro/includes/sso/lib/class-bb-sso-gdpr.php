<?php
/**
 * BB_SSO_GDPR Class for BuddyBoss Platform Pro.
 *
 * Handles GDPR-related functionality such as adding privacy policy content,
 * exporting personal data for BuddyBoss Single Sign-On (SSO) users.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/BBSSO
 */

namespace BBSSO;

/**
 * Class BB_SSO_GDPR.
 *
 * This class is responsible for handling BB_SSO_GDPR-related functionality,
 * such as adding privacy policy content and managing personal data export.
 *
 * @since 2.6.30
 */
class BB_SSO_GDPR {

	/**
	 * BB_SSO_GDPR constructor.
	 *
	 * Initializes actions and filters for GDPR compliance.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		add_action(
			'admin_init',
			array( $this, 'add_privacy_policy_content' )
		);

		add_filter(
			'wp_privacy_personal_data_exporters',
			array( $this, 'register_exporter' ),
			-1
		);
	}

	/**
	 * Adds privacy policy content for BuddyBoss Platform Pro.
	 *
	 * This method checks if the wp_add_privacy_policy_content function exists
	 * and adds the relevant GDPR privacy content.
	 *
	 * @since 2.6.30
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		ob_start();
		?>
		<p class="privacy-policy-tutorial">
			<?php
			esc_html_e( 'We collect Telemetry data to better understand which features and configurations 
			are most popular, allowing us to create and refine tools that meet your needs. 
			This data empowers us to continuously enhance the platform in ways that benefit all users.', 'buddyboss-pro' );
			?>
		</p>
		<p class="privacy-policy-tutorial">
			<?php
			echo sprintf(
			    /* translators: %s: URL to BuddyBoss Telemetry page */
				__( 'BuddyBoss does not interact, track or share any Personally Identifiable Data 
				or User Generated Content from the users on your website. 
				You can find additional information about the statistics we gather and their purpose <a href="%1$s" target="_blank" rel="noopener">here</a>', 'buddyboss-pro' ),
				esc_url( 'https://www.buddyboss.com/usage-tracking/?utm_source=product&utm_medium=platform&utm_campaign=telemetry' )
			);
			?>
		</p>
		<strong class="privacy-policy-tutorial">
			<?php
			esc_html_e( 'Suggested text:', 'buddyboss-pro' );
			?>
		</strong>
		<?php
		esc_html_e( 'This website collects telemetry data to track feature and configuration usage. Collected data is processed in such a way that visitors cannot be identified and no Personally Identifiable Data or User Generated Content is shared.', 'buddyboss-pro' );
		?>
		<br>
		<?php
		wp_add_privacy_policy_content( 'BuddyBoss', wp_kses_post( wpautop( ob_get_clean(), false ) ) );
	}

	/**
	 * Registers an exporter for BuddyBoss Single Sign-On.
	 *
	 * @since 2.6.30
	 *
	 * @param array $exporters The existing personal data exporters.
	 *
	 * @return array Updated personal data exporters array.
	 */
	public function register_exporter( $exporters ) {
		$exporters['bb-sso'] = array(
			'exporter_friendly_name' => 'BuddyBoss Single Sign-On',
			'callback'               => array( $this, 'exporter' ),
		);

		return $exporters;
	}

	/**
	 * Exports personal data for a given email address.
	 *
	 * Retrieves and exports the user data for the specified email address
	 * from allowed social providers.
	 *
	 * @since 2.6.30
	 *
	 * @param string $email_address The email address for which personal data is exported.
	 * @param int    $page          Pagination for large data sets. Default is 1.
	 *
	 * @return array Exported data and status.
	 */
	public function exporter( $email_address, $page = 1 ) {
		$email_address = trim( $email_address );

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$user_data_to_export = array();

		foreach ( \BB_SSO::$allowed_providers as $provider ) {
			$user_data_to_export = array_merge( $user_data_to_export, $provider->export_personal_data( $user->ID ) );
		}

		if ( ! empty( $user_data_to_export ) ) {
			$data_to_export[] = array(
				'group_id'    => 'user',
				'group_label' => __( 'User', 'buddyboss-pro' ),
				'item_id'     => "user-{$user->ID}",
				'data'        => $user_data_to_export,
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}
}

new BB_SSO_GDPR();
