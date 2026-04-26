<?php
/**
 * Payment Retry Email Trigger.
 *
 * Handles sending payment retry emails with proper placeholder replacement.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Modules\Payments\Emails\Settings\Initial_Payment_Failed;
use LearnDash\Core\Modules\Payments\Emails\Settings\Second_Attempt_Failed;
use LearnDash\Core\Modules\Payments\Emails\Settings\Final_Attempt_Coming_Up;
use LearnDash\Core\Modules\Payments\Emails\Settings\Payment_Failed_Access_Revoked;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_User;

/**
 * Payment Retry Email Trigger class.
 *
 * @since 4.25.3
 */
class Retry_Email_Trigger {
	/**
	 * Sends the retry email based on the retry count.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 *
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public static function send_retry_email( Subscription $subscription, WP_User $user ): bool {
		$retry_count = $subscription->get_retry_count();

		if ( $retry_count === 1 ) {
			return self::send_initial_payment_failed_email( $subscription, $user );
		} elseif ( $retry_count === 2 ) {
			return self::send_second_attempt_failed_email( $subscription, $user );
		} elseif ( $retry_count === 3 ) {
			return self::send_final_attempt_coming_up_email( $subscription, $user );
		}

		return false;
	}

	/**
	 * Sends payment failed access revoked email.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 *
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	public static function send_payment_failed_access_revoked_email( Subscription $subscription, WP_User $user ): bool {
		$email_settings = Payment_Failed_Access_Revoked::get_section_settings_all();

		if (
			is_null( $email_settings )
			|| ! Arr::get( $email_settings, 'enabled', '' )
		) {
			return false;
		}

		$email_settings = self::apply_placeholders( $email_settings, $subscription, $user );

		return learndash_emails_send(
			$user->user_email,
			$email_settings
		);
	}

	/**
	 * Sends initial payment failed email.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 *
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	private static function send_initial_payment_failed_email( Subscription $subscription, WP_User $user ): bool {
		$email_settings = Initial_Payment_Failed::get_section_settings_all();

		if (
			is_null( $email_settings )
			|| ! Arr::get( $email_settings, 'enabled', '' )
		) {
			return false;
		}

		$email_settings = self::apply_placeholders( $email_settings, $subscription, $user );

		return learndash_emails_send(
			$user->user_email,
			$email_settings
		);
	}

	/**
	 * Sends second attempt failed email.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 *
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	private static function send_second_attempt_failed_email( Subscription $subscription, WP_User $user ): bool {
		$email_settings = Second_Attempt_Failed::get_section_settings_all();

		if (
			is_null( $email_settings )
			|| ! Arr::get( $email_settings, 'enabled', '' )
		) {
			return false;
		}

		$email_settings = self::apply_placeholders( $email_settings, $subscription, $user );

		return learndash_emails_send(
			$user->user_email,
			$email_settings
		);
	}

	/**
	 * Sends final attempt coming up email.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 *
	 * @return bool True if email was sent successfully, false otherwise.
	 */
	private static function send_final_attempt_coming_up_email( Subscription $subscription, WP_User $user ): bool {
		$email_settings = Final_Attempt_Coming_Up::get_section_settings_all();

		if (
			is_null( $email_settings )
			|| ! Arr::get( $email_settings, 'enabled', '' )
		) {
			return false;
		}

		$email_settings = self::apply_placeholders( $email_settings, $subscription, $user );

		return learndash_emails_send(
			$user->user_email,
			$email_settings
		);
	}

	/**
	 * Applies placeholders to email settings.
	 *
	 * @since 4.25.3
	 *
	 * @param array<string, mixed> $email_settings The email settings.
	 * @param Subscription         $subscription   The subscription.
	 * @param WP_User              $user           The user.
	 *
	 * @return array<string, mixed> The email settings with placeholders replaced.
	 */
	private static function apply_placeholders( array $email_settings, Subscription $subscription, WP_User $user ): array {
		$product = $subscription->get_product();

		if ( ! $product ) {
			return $email_settings;
		}

		$placeholders = [
			'{user_login}'   => $user->user_login,
			'{first_name}'   => $user->user_firstname,
			'{last_name}'    => $user->user_lastname,
			'{display_name}' => $user->display_name,
			'{user_email}'   => $user->user_email,
			'{product_id}'   => $product->get_id(),
			'{product_name}' => $product->get_title(),
			'{product_url}'  => get_permalink( $product->get_id() ),
			'{site_title}'   => get_bloginfo( 'name' ),
			'{site_url}'     => get_site_url(),
		];

		/**
		 * Filters payment retry email placeholders.
		 *
		 * @since 4.25.3
		 *
		 * @param array<string, mixed> $placeholders Array of placeholders.
		 * @param int                  $user_id      User ID.
		 * @param int                  $product_id   Product ID.
		 * @param Subscription         $subscription The subscription.
		 */
		$placeholders = apply_filters(
			'learndash_subscription_payment_retry_email_placeholders',
			$placeholders,
			$user->ID,
			$product->get_id(),
			$subscription
		);

		/**
		 * Filters payment retry email subject.
		 *
		 * @since 4.25.3
		 *
		 * @param string       $email_subject Email subject text.
		 * @param int          $user_id       User ID.
		 * @param int          $product_id    Product ID.
		 * @param Subscription $subscription The subscription.
		 */
		$email_settings['subject'] = apply_filters(
			'learndash_subscription_payment_retry_email_subject',
			Cast::to_string( $email_settings['subject'] ?? '' ),
			$user->ID,
			$product->get_id(),
			$subscription
		);

		if ( ! empty( $email_settings['subject'] ) ) {
			$email_settings['subject'] = learndash_emails_parse_placeholders(
				$email_settings['subject'],
				$placeholders
			);
		}

		/**
		 * Filters payment retry email message.
		 *
		 * @since 4.25.3
		 *
		 * @param string       $email_message Email message text.
		 * @param int          $user_id       User ID.
		 * @param int          $product_id    Product ID.
		 * @param Subscription $subscription  The subscription.
		 */
		$email_settings['message'] = apply_filters(
			'learndash_subscription_payment_retry_email_message',
			Cast::to_string( $email_settings['message'] ?? '' ),
			$user->ID,
			$product->get_id(),
			$subscription
		);

		if ( ! empty( $email_settings['message'] ) ) {
			$email_settings['message'] = learndash_emails_parse_placeholders(
				$email_settings['message'],
				$placeholders
			);
		}

		return $email_settings;
	}
}
