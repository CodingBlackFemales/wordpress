<?php
/**
 * Notification email subscription manager.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Subscription Manager class
 */
class LD_Notifications_Subscription_Manager {
	/**
	 * WP_Query's query_vars
	 *
	 * @var array
	 */
	public static $query_vars;

	/**
	 * URL slug for the notifications subscription manager.
	 *
	 * @var string
	 */
	public static $slug;

	/**
	 * Init the class
	 *
	 * @return void
	 */
	public static function init() {
		self::$slug = apply_filters( 'learndash_notifications_subscription_page_slug', 'learndash-notifications-subscription' );

		add_filter( 'learndash_notifications_email_content', [ __CLASS__, 'subscription_notice' ], 99, 2 );

		add_action( 'template_redirect', [ __CLASS__, 'redirect_to_login_page' ] );
		add_action( 'template_include', [ __CLASS__, 'subscription_page' ] );
		add_filter( 'document_title_parts', [ __CLASS__, 'subscription_page_title' ] );

		add_action( 'init', [ __CLASS__, 'save_subscriptions' ] );
	}

	/**
	 * Init and get query vars
	 *
	 * @return array
	 */
	public static function get_query_vars() {
		global $wp_query;
		return $wp_query->query_vars;
	}

	/**
	 * Add subscription manager notice at the end of notification email
	 *
	 * @param string $content         Original email content
	 * @param int    $notification_id Notification post ID
	 * @return string                  New returned email content
	 */
	public static function subscription_notice( $content, $notification_id ) {
		$content     .= '<p class="subscription-manager-notice" style="font-size: 12px; font-style: italic;">';
			// translators: %s: URL of the notification email subscription management page.
			$content .= sprintf( __( 'Don\'t want to receive this email anymore? <a href="%s">Click here</a> to manage your notification email subscriptions.', 'learndash' ), home_url( trailingslashit( self::$slug ) ) );
		$content     .= '</p>';

		return $content;
	}

	/**
	 * Redirect to login page if user is not logged in
	 *
	 * @return void
	 */
	public static function redirect_to_login_page() {
		$query_vars = self::get_query_vars();

		if ( ! is_user_logged_in() && array_search( self::$slug, $query_vars, true ) !== false ) {
			$login_page = wp_login_url( home_url( trailingslashit( self::$slug ) ) );

			wp_safe_redirect( $login_page );
			exit();
		}
	}

	/**
	 * Change template to subscription
	 *
	 * @param string $template Default template path
	 * @return string           Template path
	 */
	public static function subscription_page( $template ) {
		$query_vars = self::get_query_vars();

		if ( is_user_logged_in() && array_search( self::$slug, $query_vars, true ) !== false ) {
			http_response_code( 200 );
			return LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . 'templates/subscription-page.php';
		} else {
			return $template;
		}
	}

	/**
	 * Change subscription page title
	 *
	 * @param string $title Default title
	 * @return string        New title
	 */
	public static function subscription_page_title( $title ) {
		$query_vars = self::get_query_vars();

		if ( array_search( self::$slug, $query_vars, true ) !== false ) {
			$title['title'] = __( 'LearnDash Notifications Subscription', 'learndash' );
		}

		return $title;
	}

	/**
	 * Save user notifications subscription setting
	 *
	 * @return void
	 */
	public static function save_subscriptions() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'learndash_notifications_subscription' ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['ld_nonce'], 'learndash_notifications_subscription' ) ) {
			return;
		}

		$user_id = intval( $_POST['user_id'] );

		if ( empty( $user_id ) ) {
			return;
		}

		$triggers      = learndash_notifications_get_triggers();
		$subscriptions = [];
		foreach ( $triggers as $key => $label ) {
			if ( isset( $_POST[ $key ] ) && $_POST[ $key ] == 1 ) {
				$subscriptions[ $key ] = 1;
			} else {
				$subscriptions[ $key ] = 0;
			}
		}

		$update = update_user_meta( $user_id, 'learndash_notifications_subscription', $subscriptions );

		if ( $update ) {
			$redirect_url = add_query_arg( [ 'message' => 'success' ], home_url( trailingslashit( self::$slug ) ) );
		} else {
			$redirect_url = add_query_arg( [ 'message' => 'fail' ], home_url( trailingslashit( self::$slug ) ) );
		}

		wp_redirect( $redirect_url );
		exit();
	}
}

LD_Notifications_Subscription_Manager::init();
