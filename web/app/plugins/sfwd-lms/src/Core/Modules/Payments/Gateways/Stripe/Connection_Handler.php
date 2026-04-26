<?php
/**
 * Stripe Connection Handler.
 *
 * @since 4.20.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Stripe;

use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Stripe\Webhook_Auto_Configuring;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Settings_Section_Stripe_Connect;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Stripe Connection Handler.
 *
 * @since 4.20.1
 */
class Connection_Handler {
	/**
	 * Ajax action name for pre-disconnect tasks.
	 *
	 * @since 4.20.1
	 *
	 * @var string
	 */
	public static $ajax_action_pre_disconnect = 'learndash_stripe_pre_disconnect';

	/**
	 * Ajax action name for post-connect tasks.
	 *
	 * @since 4.20.1
	 *
	 * @var string
	 */
	public static $ajax_action_post_connect = 'learndash_stripe_post_connect';

	/**
	 * Handles pre-disconnect ajax request.
	 *
	 * This method is used to run tasks on the server before we redirect the user
	 * to our Stripe connect server to disconnect the account.
	 *
	 * @since 4.20.1
	 *
	 * @return void
	 */
	public function handle_ajax_pre_disconnect_request(): void {
		// Validate the request.

		check_ajax_referer( self::$ajax_action_pre_disconnect, 'nonce' );

		if ( ! learndash_is_admin_user() ) {
			wp_send_json_error();
		}

		$stripe_settings = LearnDash_Settings_Section_Stripe_Connect::get_section_settings_all();

		// Ignore if Stripe is not enabled.

		if ( $stripe_settings['enabled'] !== 'yes' ) {
			wp_send_json_error();
		}

		$webhook_auto_configuring = App::get( Webhook_Auto_Configuring::class );

		if ( ! $webhook_auto_configuring instanceof Webhook_Auto_Configuring ) {
			wp_send_json_error();
		}

		// Disable webhooks (live and test).

		$webhook_auto_configuring->disable(
			Cast::to_string( $stripe_settings['account_id'] ),
			Cast::to_string( $stripe_settings['webhook_url'] ),
			true
		);

		$webhook_auto_configuring->disable(
			Cast::to_string( $stripe_settings['account_id'] ),
			Cast::to_string( $stripe_settings['webhook_url'] ),
			false
		);

		// We don't need to report an error. If it fails, we can't do anything.
		wp_send_json_success();
	}

	/**
	 * Handles post-connect ajax request.
	 *
	 * This method is used to run async tasks on the server after the user has connected.
	 * For example, enable webhooks.
	 *
	 * @since 4.20.1
	 *
	 * @return void
	 */
	public function handle_ajax_post_connect_request(): void {
		// Validate the request.

		check_ajax_referer( self::$ajax_action_post_connect, 'nonce' );

		if ( ! learndash_is_admin_user() ) {
			wp_send_json_error();
		}

		$stripe_settings = LearnDash_Settings_Section_Stripe_Connect::get_section_settings_all();

		// Ignore if Stripe is not enabled.

		if ( $stripe_settings['enabled'] !== 'yes' ) {
			wp_send_json_error();
		}

		$webhook_auto_configuring = App::get( Webhook_Auto_Configuring::class );

		if ( ! $webhook_auto_configuring instanceof Webhook_Auto_Configuring ) {
			wp_send_json_error();
		}

		$is_live_mode = SuperGlobals::get_var( 'is_live_mode' ) === 'true';

		// Enable webhooks.

		$webhook_created = $webhook_auto_configuring->enable(
			Cast::to_string( $stripe_settings['account_id'] ),
			Cast::to_string( $stripe_settings['webhook_url'] ),
			$is_live_mode
		);

		wp_send_json_success(
			[
				'stripe_webhook_created'      => $webhook_created,
				'stripe_webhook_html_error'   => wp_kses_post( $webhook_auto_configuring->get_last_error_message() ),
				'stripe_webhook_is_live_mode' => $is_live_mode,
				'stripe_webhook_signing_key'  => $webhook_auto_configuring->get_signing_key( $is_live_mode ),
			]
		);
	}
}
