<?php
/**
 * Class BB_SSO_Notices.
 *
 * Handles admin notices and success/error messages for the BuddyBoss Social Login.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/BBSSO
 */

namespace BBSSO;

use BBSSO\Persistent\BB_SSO_Persistent;
use WP_Error;

/**
 * Class BB_SSO_Notices.
 *
 * This class is responsible for managing and displaying admin notices.
 *
 * @since 2.6.30
 */
class BB_SSO_Notices {

	/**
	 * Holds the notice array.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	public static $notices;

	/**
	 * Instance of the class.
	 *
	 * @since 2.6.30
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * BB_SSO_Notices constructor.
	 *
	 * Sets up actions and notices to be displayed on the admin dashboard.
	 *
	 * @since 2.6.30
	 */
	private function __construct() {
		if (
			is_admin() ||
			(
				isset( $_GET['bb-sso-notice'] ) && // phpcs:ignore WordPress.Security.NonceVerification
				1 === (int) $_GET['bb-sso-notice'] // phpcs:ignore WordPress.Security.NonceVerification
			)

		) {
			add_action( 'init', array( $this, 'load' ), 11 );
			add_action( 'admin_print_footer_scripts', array( $this, 'notices_fallback' ) );
			add_action( 'wp_print_footer_scripts', array( $this, 'notices_fallback' ) );
		}
	}

	/**
	 * Initializes the BB_SSO_Notices class.
	 *
	 * @since 2.6.30
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Adds an error notice.
	 *
	 * @since 2.6.30
	 * @since 2.6.60 Added $type parameter.
	 *
	 * @param string|WP_Error $message The error message or WP_Error object.
	 */
	public static function add_error( $message, $type = 'error' ) {
		if ( is_wp_error( $message ) ) {
			foreach ( $message->get_error_messages() as $m ) {
				self::add( $type, $m );
			}
		} else {
			self::add( $type, $message );
		}
	}

	/**
	 * Adds a notice of a specific type.
	 *
	 * @since 2.6.30
	 *
	 * @param string $type    The type of notice ('error', 'success').
	 * @param string $message The notice message.
	 */
	private static function add( $type, $message ) {
		if ( ! isset( self::$notices[ $type ] ) ) {
			self::$notices[ $type ] = array();
		}

		if ( ! in_array( $message, self::$notices[ $type ], true ) ) {
			self::$notices[ $type ][] = $message;
		}

		self::set();
	}

	/**
	 * Saves notices to persistent storage.
	 *
	 * @since 2.6.30
	 */
	private static function set() {
		BB_SSO_Persistent::set( 'notices', self::$notices );
	}

	/**
	 * Retrieves all error notices.
	 *
	 * @since 2.6.30
	 *
	 * @return array|false The error messages, or false if no errors.
	 */
	public static function get_errors() {
		if ( isset( self::$notices['error'] ) ) {

			$errors = self::$notices['error'];

			unset( self::$notices['error'] );
			self::set();

			return $errors;
		}

		return false;
	}

	/**
	 * Adds a success notice.
	 *
	 * @since 2.6.30
	 *
	 * @param string $message The success message.
	 */
	public static function add_success( $message ) {
		self::add( 'success', $message );
	}

	/**
	 * Loads the stored notices.
	 *
	 * @since 2.6.30
	 */
	public function load() {
		self::$notices = maybe_unserialize( self::get() );
		if ( ! is_array( self::$notices ) ) {
			self::$notices = array();
		}
	}

	/**
	 * Retrieves notices from persistent storage.
	 *
	 * @since 2.6.30
	 *
	 * @return mixed Notices stored in persistent storage.
	 */
	private static function get() {
		return BB_SSO_Persistent::get( 'notices' );
	}

	/**
	 * Fallback display for non-displayed notices in a lightbox.
	 *
	 * @since 2.6.30
	 */
	public function notices_fallback() {
		if ( isset( self::$notices ) ) {
			foreach ( self::$notices as $type => $notices ) {
				if ( ! empty( $notices ) ) {
					foreach ( $notices as $message ) {
						?>
						<script type="text/javascript">
							jQuery( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									'<?php echo wp_kses_post( $message ); ?>',
									'<?php echo esc_html( $type ); ?>',
									null,
									true
								]
							);

							jQuery( document ).on( 'ready', function () {
								if ( jQuery( '.sso-lists .bb-box-panel' ).length ) {
									var noticeHtml = jQuery( '<div class="bb-sso-provider-notice"></div>' );
									noticeHtml.html( '<div class="notice-container notice-container--success">' + '<?php echo esc_html( $message ); ?>' + '</div>' );
									jQuery( '.sso-lists .bb-box-panel' ).after( noticeHtml );
									setTimeout( function () {
										noticeHtml.fadeOut( 300, function () {
											noticeHtml.remove();
										} );
									}, 5000 );
								}
							} );
						</script>
						<?php
					}
				}
			}
			self::clear();
		}
	}

	/**
	 * Clears all stored notices.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public static function clear() {
		BB_SSO_Persistent::delete( 'notices' );
		self::$notices = array();
	}

	/**
	 * Get info messages.
	 *
	 * @since 2.6.60
	 */
	public static function get_infos() {
		if ( isset( self::$notices['info'] ) ) {

			$errors = self::$notices['info'];

			unset( self::$notices['info'] );
			self::set();

			return $errors;
		}

		return false;
	}
}
