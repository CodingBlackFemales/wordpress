<?php
/**
 * LearnDash PayPal Provider class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints;
use LearnDash\Core\App;
use Learndash_Payment_Gateway;

/**
 * Service provider class for PayPal.
 *
 * @since 4.25.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.25.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Admin\Provider::class );

		$this->container->when( Order_Data::class )
			->needs( '$gateway' )
			->give(
				function () {
					$gateway = App::get( Payment_Gateway::class );

					if ( ! $gateway instanceof Payment_Gateway ) {
						throw new ContainerException( 'Payment gateway not found' );
					}

					return $gateway;
				}
			);

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Register payment gateway.
		add_filter(
			'learndash_payment_gateways',
			[ $this, 'register_payment_gateway' ]
		);

		// Update the PayPal Checkout SDK script attributes.
		add_filter(
			'wp_script_attributes',
			$this->container->callback(
				Assets::class,
				'update_sdk_script_attributes'
			)
		);

		// Register admin assets.
		add_action(
			'current_screen',
			$this->container->callback(
				Assets::class,
				'register_admin_assets'
			)
		);

		// Enqueue admin assets.
		add_action(
			'admin_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_admin_assets'
			)
		);

		// Enqueue profile assets.
		add_filter(
			'learndash_shortcode_profile_before_template',
			$this->container->callback(
				Assets::class,
				'register_profile_assets'
			)
		);

		// Register frontend assets.
		add_action(
			'init', // We need to use init to follow what the registration assets do.
			$this->container->callback(
				Assets::class,
				'register_frontend_assets'
			)
		);

		// Register REST endpoints.
		add_filter(
			'learndash_rest_endpoints',
			function ( $endpoints ) {
				return array_merge(
					$endpoints,
					[
						Endpoints\Onboarding\Access_Token::class,
						Endpoints\Onboarding\Disconnect::class,
						Endpoints\Onboarding\Reconnect::class,
						Endpoints\Onboarding\Signup_Url::class,
						Endpoints\Payments\Cancel::class,
						Endpoints\Payments\Capture::class,
						Endpoints\Payments\Cards::class,
						Endpoints\Payments\Confirm_Order::class,
						Endpoints\Payments\Order::class,
						Endpoints\Payments\Payment_Token::class,
						Endpoints\Payments\Setup_Token::class,
						Endpoints\Payments\Start_Trial::class,
						Endpoints\Payments\Webhook::class,
					]
				);
			}
		);

		// Register client data.
		add_filter(
			'learndash_paypal_checkout_client_data',
			$this->container->callback(
				Client::class,
				'get_client_data'
			)
		);

		// Display the FraudNet JSON.
		add_action(
			'wp_footer',
			$this->container->callback( Assets::class, 'display_fraudnet_json' ),
			20 // Display after all other scripts.
		);

		// Register the subscription payment processing filter.
		add_filter(
			'learndash_payment_subscription_process_with_gateway_paypal_checkout',
			$this->container->callback(
				Payment_Gateway::class,
				'process_subscription_payment'
			),
			10,
			4
		);

		// Register the subscription failure processing filter.
		add_filter(
			'learndash_payment_subscription_after_failure_paypal_checkout',
			$this->container->callback(
				Payment_Gateway::class,
				'process_subscription_failure'
			),
			10,
			2
		);

		// Register the profile show saved cards filter.
		add_filter(
			'learndash_profile_show_saved_cards',
			$this->container->callback(
				Profile_Handler::class,
				'show_saved_cards'
			),
			10
		);

		// Register profile payment method information filter.
		add_filter(
			'learndash_subscription_payment_method_information',
			$this->container->callback(
				Profile_Handler::class,
				'get_payment_method_information'
			),
			10,
			2
		);

		// Register the saved cards filter.
		add_filter(
			'learndash_model_user_cards',
			$this->container->callback(
				Profile_Handler::class,
				'get_saved_cards'
			),
			10,
			2
		);

		// Register the card removal filter.
		add_filter(
			'learndash_handle_remove_card_paypal_checkout',
			$this->container->callback(
				Profile_Handler::class,
				'handle_remove_card'
			),
			10,
			4
		);

		// Register the card manager form content filter.
		add_filter(
			'learndash_profile_add_card_form_content',
			$this->container->callback(
				Payment_Gateway::class,
				'render_card_manager_form_content'
			)
		);
	}

	/**
	 * Registers the payment gateway.
	 *
	 * @since 4.25.0
	 *
	 * @param Learndash_Payment_Gateway[] $gateways Payment gateways.
	 *
	 * @return Learndash_Payment_Gateway[] Updated payment gateways.
	 */
	public function register_payment_gateway( array $gateways ): array {
		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Learndash_Payment_Gateway ) {
			return $gateways;
		}

		$gateways[] = $gateway;

		return $gateways;
	}
}
