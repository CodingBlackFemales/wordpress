<?php
/**
 * Main Learndash_WooCommerce integration class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use LearnDash\Core\Models\Product;
use LearnDash\WooCommerce\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main integration class.
 *
 * @since 5.2.0
 */
class Learndash_WooCommerce {
	/**
	 * Debug
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * Constructor
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		self::includes();
		self::hooks();
	}

	/**
	 * Hooks
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'before_woocommerce_init', [ __CLASS__, 'declare_custom_order_tables_compatibility' ] );

		// Meta box.
		add_filter( 'product_type_selector', [ __CLASS__, 'add_product_type' ], 10, 1 );
		add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'render_course_selector' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ], 1 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'deregister_admin_scripts' ], 20 );
		add_action( 'save_post', [ __CLASS__, 'store_related_courses' ], 10, 2 );

		// Product variation hooks.
		add_action( 'woocommerce_product_after_variable_attributes', [ __CLASS__, 'render_variation_course_selector' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ __CLASS__, 'store_variation_related_courses' ], 10, 2 );

		/**
		 * Order and subscription hooks.
		 *
		 * Both of the following methods are required to hook into `learndash_loaded` action
		 * at the earliest because they use LearnDash settings page and sections API which are available
		 * after the `learndash_loaded` action.
		 */
		add_action( 'learndash_loaded', [ self::class, 'handle_order_status_enrollment_update' ] );
		add_action( 'learndash_loaded', [ self::class, 'handle_subscription_status_enrollment_update' ] );

		add_action( 'woocommerce_before_trash_order', [ __CLASS__, 'delete_order' ], 10, 2 );
		add_action( 'woocommerce_before_delete_order', [ __CLASS__, 'delete_order' ], 10, 2 );

		add_action( 'woocommerce_process_shop_order_meta', [ __CLASS__, 'update_order_meta' ] );
		add_action( 'woocommerce_process_shop_subscription_meta', [ __CLASS__, 'update_subscription_meta' ] );

		add_action( 'woocommerce_new_order_item', [ __CLASS__, 'process_new_order_item' ], 10, 3 );
		add_action( 'woocommerce_before_delete_order_item', [ __CLASS__, 'delete_order_item' ], 10, 1 );

		add_filter( 'woocommerce_order_get_items', [ __CLASS__, 'filter_subscription_products_out' ], 10, 3 );

		add_filter( 'woocommerce_subscription_settings', [ __CLASS__, 'add_subscription_settings' ], 20, 1 );

		add_action( 'woocommerce_subscription_renewal_payment_complete', [ __CLASS__, 'remove_course_access_on_billing_cycle_completion' ], 10, 2 );

		// WC Subscription switcher.
		add_action( 'woocommerce_subscription_checkout_switch_order_processed', [ __CLASS__, 'switch_subscription' ], 10, 2 );

		// Silent background course enrollment process.
		add_action( 'learndash_woocommerce_cron', [ __CLASS__, 'process_silent_course_enrollment' ] );

		// Force user to log in or create account if there is LD course in WC cart.
		add_filter( 'woocommerce_checkout_registration_enabled', [ __CLASS__, 'enable_registration' ], 20 );
		add_filter( 'woocommerce_checkout_registration_required', [ __CLASS__, 'require_registration' ], 20 );
		add_action( 'woocommerce_before_checkout_process', [ __CLASS__, 'force_registration_during_checkout' ] );

		// Auto complete course transaction.
		add_action( 'woocommerce_payment_complete', [ __CLASS__, 'auto_complete_transaction' ] );
		add_action( 'woocommerce_thankyou', [ __CLASS__, 'auto_complete_transaction' ] );

		// Remove course increment record if a course unenrolled manually.
		add_action( 'learndash_delete_user_data', [ __CLASS__, 'remove_access_increment_count' ] );
	}

	/**
	 * Setup constants
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function setup_constants() {
		// Plugin file.
		if ( ! defined( 'LEARNDASH_WOOCOMMERCE_FILE' ) ) {
			define( 'LEARNDASH_WOOCOMMERCE_FILE', __FILE__ );
		}

		// Plugin folder path.
		if ( ! defined( 'LEARNDASH_WOOCOMMERCE_PLUGIN_PATH' ) ) {
			define( 'LEARNDASH_WOOCOMMERCE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL.
		if ( ! defined( 'LEARNDASH_WOOCOMMERCE_PLUGIN_URL' ) ) {
			define( 'LEARNDASH_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Check and set dependencies
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function check_dependency() {
		LearnDash_Dependency_Check_LD_WooCommerce::get_instance()->set_dependencies(
			[
				'sfwd-lms/sfwd_lms.php'       => [
					'label'       => '<a href="https://learndash.com">LearnDash LMS</a>',
					'class'       => 'SFWD_LMS',
					'min_version' => '3.0.0',
				],
				'woocommerce/woocommerce.php' => [
					'label'       => '<a href="https://woocommerce.com/">WooCommerce</a>',
					'class'       => 'WooCommerce',
					'min_version' => '4.5.0',
				],
			]
		);

		LearnDash_Dependency_Check_LD_WooCommerce::get_instance()->set_message(
			__( 'LearnDash LMS - WooCommerce Integration Add-on requires the following plugin(s) to be active:', 'learndash' )
		);
	}

	/**
	 * Includes
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function includes() {
		require_once LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-upgrade.php';
		require_once LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-cron.php';
	}

	/**
	 * Declare the plugin to be compatible with WooCommerce custom order tables feature introduced in WooCommerce 8.0.0.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function declare_custom_order_tables_compatibility(): void {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			LEARNDASH_LMS_PLUGIN_DIR . 'sfwd_lms.php',
			true
		);
	}

	/**
	 * Load translations
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @return void
	 */
	public static function load_translation() {
		_deprecated_function( __METHOD__, '2.0.0' );

		global $wp_version;
		// Set filter for plugin language directory.
		$lang_dir = dirname( plugin_basename( LEARNDASH_WOOCOMMERCE_FILE ) ) . '/languages/';
		$lang_dir = apply_filters( 'ld_woocommerce_languages_directory', $lang_dir ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$get_locale = get_locale();

		if ( $wp_version >= '4.7' ) {
			$get_locale = get_user_locale();
		}

		$mofile = sprintf( '%s-%s.mo', 'learndash', $get_locale );
		$mofile = WP_LANG_DIR . 'plugins/' . $mofile;

		if ( file_exists( $mofile ) ) {
			load_textdomain( 'learndash', $mofile );
		} else {
			load_plugin_textdomain( 'learndash', false, $lang_dir );
		}
	}

	/**
	 * Handles WooCommerce order status update for LearnDash course/group enrollment update.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function handle_order_status_enrollment_update(): void {
		$granted_statuses = Settings\Status_Access::get_access_granted_order_statuses();
		$denied_statuses  = Settings\Status_Access::get_access_denied_order_statuses();

		foreach ( $granted_statuses as $status => $label ) {
			add_action( 'woocommerce_order_status_' . $status, [ self::class, 'add_course_access' ], 10, 1 );
		}

		foreach ( $denied_statuses as $status => $label ) {
			/**
			 * Refunded status requires a separate action `woocommerce_order_refunded` to support partial refund.
			 * Partial refund order still has a status of `completed`, hence the need for another action to
			 * process course/group access update.
			 */
			if ( $status === 'refunded' ) {
				add_action( 'woocommerce_order_refunded', [ self::class, 'remove_course_access_on_refund' ], 10, 2 );
			} else {
				add_action( 'woocommerce_order_status_' . $status, [ self::class, 'remove_course_access' ], 10, 1 );
			}
		}
	}

	/**
	 * Handles WooCommerce subscription status update for LearnDash course/group enrollment update.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function handle_subscription_status_enrollment_update(): void {
		if ( ! function_exists( 'wcs_get_subscription_statuses' ) ) {
			return;
		}

		$granted_statuses = Settings\Status_Access::get_access_granted_subscription_statuses();
		$denied_statuses  = Settings\Status_Access::get_access_denied_subscription_statuses();

		foreach ( $granted_statuses as $status => $label ) {
			add_action( 'woocommerce_subscription_status_' . $status, [ self::class, 'add_subscription_course_access' ], 10, 1 );
		}

		foreach ( $denied_statuses as $status => $label ) {
			if (
				$status === 'expired'
				&& Settings\Status_Access::is_access_removal_disabled_on_expiration()
			) {
				continue;
			}

			add_action( 'woocommerce_subscription_status_' . $status, [ self::class, 'remove_subscription_course_access' ], 10, 1 );
		}
	}

	/**
	 * Add product type.
	 *
	 * @since 5.2.0
	 *
	 * @param array<string, string> $types Array of custom post types.
	 *
	 * @return array<string, string> Array of custom post types.
	 */
	public static function add_product_type( $types ) {
		$types['course'] = learndash_get_custom_label( 'course' );
		return $types;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if (
			$screen->id === 'product'
			&& $screen->base === 'post'
		) {
			wp_enqueue_style( 'learndash-woocommerce-select2', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'lib/select2/select2.min.css', [], '4.0.13', 'screen' );
			wp_enqueue_script( 'learndash-woocommerce-select2', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'lib/select2/select2.full.min.js', [ 'jquery' ], '4.0.13', false );

			wp_enqueue_style( 'learndash-woocommerce-product', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'assets/css/product.min.css', [], LEARNDASH_SCRIPT_VERSION_TOKEN, 'screen' );
			wp_enqueue_script( 'learndash-woocommerce-product', LEARNDASH_WOOCOMMERCE_PLUGIN_URL . 'assets/js/product.js', [ 'jquery' ], LEARNDASH_SCRIPT_VERSION_TOKEN, true );
		}
	}

	/**
	 * Deregister admin scripts
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function deregister_admin_scripts() {
		$screen = get_current_screen();

		if (
			$screen->id === 'product'
			&& $screen->base === 'post'
		) {
			wp_deregister_script( 'learndash-select2-jquery-script' );
			wp_deregister_style( 'learndash-select2-jquery-style' );
		}
	}

	/**
	 * Add front scripts
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @return void
	 */
	public static function add_front_scripts() {
		_deprecated_function( __METHOD__, '2.0.0' );

		wp_enqueue_script( 'ld_wc_front', plugins_url( '/front.js', LEARNDASH_WOOCOMMERCE_FILE ), [ 'jquery' ], LEARNDASH_SCRIPT_VERSION_TOKEN, true );
	}

	/**
	 * Render course selector
	 *
	 * @since 5.2.0
	 */
	public static function render_course_selector() {
		global $post;

		$courses_options = self::list_courses();
		$groups_options  = self::list_groups();

		/**
		 * Filter for course selector class names.
		 *
		 * @since 5.2.0
		 *
		 * @param string $default Default class names, separated by space.
		 * @param object $post    WP_Post object.
		 *
		 * @return string Returned class names.
		 */
		$class = apply_filters( 'learndash_woocommerce_course_selector_class', 'options_group show_if_course show_if_simple', $post );

		echo '<div class="' . esc_attr( $class ) . '">';

		wp_nonce_field( 'save_post', 'ld_wc_nonce' );

		$values = (array) get_post_meta( $post->ID, '_related_course', true );
		if ( ! $values ) {
			$values = [];
		}

		$groups_values = (array) get_post_meta( $post->ID, '_related_group', true );
		if ( ! $groups_values ) {
			$groups_values = [];
		}

		?>
		<p>
			<?php
			printf(
			// translators: HTML string, Support doc link.
				esc_html_x(
					'%1$s When customers checkout with 5 or more associated courses and groups in a single cart, course enrollment process is done in the background and you will need to set up a cron job. To set up a cron job please follow %2$s .',
					'placeholders: HTML string, Support doc link',
					'learndash'
				),
				'<strong>Important:</strong>',
				'<a href="https://www.learndash.com/support/docs/faqs/email-notifications-send-time/#create-cron-job-in-cpanel" target="_blank">these steps</a>'
			);
			?>
		</p>
		<?php

		self::woocommerce_wp_select_multiple(
			[
				'id'       => '_related_course[]',
				'class'    => 'select2 ld-woocommerce-select2 regular-width select short ld_related_courses',
				'label'    => sprintf(
				// translators: LearnDash Courses.
					_x(
						'LearnDash %s',
						'LearnDash Courses',
						'learndash'
					),
					learndash_get_custom_label( 'courses' )
				),
				'options'  => $courses_options,
				'desc_tip' => true,
				'value'    => $values,
			]
		);

		/**
		 * Select multiple input field
		 */
		self::woocommerce_wp_select_multiple(
			[
				'id'       => '_related_group[]',
				'class'    => 'select2 ld-woocommerce-select2 regular-width select short ld_related_groups',
				'label'    => sprintf(
				// translators: LearnDash Groups.
					_x(
						'LearnDash %s',
						'LearnDash Groups',
						'learndash'
					),
					learndash_get_custom_label( 'groups' )
				),
				'options'  => $groups_options,
				'desc_tip' => true,
				'value'    => $groups_values,
			]
		);

		echo '</div>';
	}

	/**
	 * Store related courses
	 *
	 * @since 5.2.0
	 *
	 * @param int     $id   ID of the post.
	 * @param WP_Post $post WP_Post object.
	 *
	 * @return void
	 */
	public static function store_related_courses( $id, $post ) {
		if (
			! isset( $_POST['ld_wc_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ld_wc_nonce'] ) ), 'save_post' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! $post->post_type === 'product' ) {
			return;
		}

		// Delete the meta and bail if product is variable type.
		$product = wc_get_product( $id );
		if ( false !== $product ) {
			if ( in_array( $product->get_type(), [ 'variable', 'variable-subscription' ], true ) ) {
				delete_post_meta( $id, '_related_course' );
				delete_post_meta( $id, '_related_group' );

				return;
			}
		}

		if (
			isset( $_POST['_related_course'] )
			&& ! empty( $_POST['_related_course'] )
		) {
			$related_courses = array_map( 'intval', $_POST['_related_course'] );
			update_post_meta( $id, '_related_course', $related_courses );
		} else {
			delete_post_meta( $id, '_related_course' );
		}

		if (
			isset( $_POST['_related_group'] )
			&& ! empty( $_POST['_related_group'] )
		) {
			$related_groups = array_map( 'intval', $_POST['_related_group'] );
			update_post_meta( $id, '_related_group', $related_groups );
		} else {
			delete_post_meta( $id, '_related_group' );
		}
	}

	/**
	 * Render variation course selector
	 *
	 * @since 5.2.0
	 *
	 * @param int                  $loop      Position in the loop.
	 * @param array<string, mixed> $data      Array of variation data @deprecated 4.4.0.
	 * @param WP_Post              $variation Post data.
	 */
	public static function render_variation_course_selector( $loop, $data, $variation ) {
		$courses_options = self::list_courses();
		$groups_options  = self::list_groups();

		echo '<div class="form-field form-row form-row-full">';

		wp_nonce_field( 'save_post', 'ld_wc_nonce' );

		$values = (array) get_post_meta( $variation->ID, '_related_course', true );
		if ( ! $values ) {
			$values = [];
		}

		$groups_values = (array) get_post_meta( $variation->ID, '_related_group', true );
		if ( ! $groups_values ) {
			$groups_values = [];
		}

		?>
		<p>
			<?php
			printf(
			// translators: HTML string, Support doc link.
				esc_html_x(
					'%1$s When customers checkout with 5 or more associated courses and groups in a single cart, course enrollment process is done in the background and you will need to set up a cron job. To set up a cron job please follow %2$s.',
					'placeholders: HTML string, Support doc link',
					'learndash'
				),
				'<strong>Important:</strong>',
				'<a href="https://www.learndash.com/support/docs/faqs/email-notifications-send-time/#create-cron-job-in-cpanel" target="_blank">these steps</a>'
			);
			?>
		</p>
		<?php

		self::woocommerce_wp_select_multiple(
			[
				'id'       => '_related_course[' . $loop . '][]',
				'class'    => 'select2 ld-woocommerce-select2 full-width select short ld_related_courses_variation',
				'label'    => sprintf(
				// translators: LearnDash Courses.
					_x(
						'LearnDash %s',
						'LearnDash Courses',
						'learndash'
					),
					learndash_get_custom_label( 'courses' )
				),
				'options'  => $courses_options,
				'desc_tip' => true,
				'value'    => $values,
			]
		);

		self::woocommerce_wp_select_multiple(
			[
				'id'       => '_related_group[' . $loop . '][]',
				'class'    => 'select2 ld-woocommerce-select2 full-width select short ld_related_groups_variation',
				'label'    => sprintf(
				// translators: LearnDash Groups.
					_x(
						'LearnDash %s',
						'LearnDash Groups',
						'learndash'
					),
					learndash_get_custom_label( 'groups' )
				),
				'options'  => $groups_options,
				'desc_tip' => true,
				'value'    => $groups_values,
			]
		);

		echo '</div>';
	}

	/**
	 * Store variation related courses
	 *
	 * @since 5.2.0
	 *
	 * @param int $variation_id WC_Product_Variation object ID.
	 * @param int $loop         Position in the loop.
	 *
	 * @return void
	 */
	public static function store_variation_related_courses( $variation_id, $loop ) {
		if (
			! isset( $_POST['ld_wc_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ld_wc_nonce'] ) ), 'save_post' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$post = get_post( $variation_id );

		if (
			! $post instanceof WP_Post
			|| $post->post_type !== 'product_variation'
		) {
			return;
		}

		if ( ! empty( $_POST['_related_course'] ) ) {
			/**
			 * Related courses.
			 *
			 * @var array<int, array<int>> $related_courses
			 */
			$related_courses = [];
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The value is sanitized inside the following loop.
			foreach ( wp_unslash( $_POST['_related_course'] ) as $key => $value ) {
				$key = sanitize_text_field( $key );

				if ( is_array( $value ) ) {
					$related_courses[ $key ] = array_map( 'intval', $value );
				} else {
					$related_courses[ $key ] = [];
				}

				update_post_meta( $variation_id, '_related_course', $related_courses[ $loop ] );
			}
		} else {
			delete_post_meta( $variation_id, '_related_course' );
		}

		if ( ! empty( $_POST['_related_group'] ) ) {
			/**
			 * Related groups.
			 *
			 * @var array<int, array<int>> $related_groups
			 */
			$related_groups = [];
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The value is sanitized inside the following loop.
			foreach ( wp_unslash( $_POST['_related_group'] ) as $key => $value ) {
				$key = sanitize_text_field( $key );

				if ( is_array( $value ) ) {
					$related_groups[ $key ] = array_map( 'intval', $value );
				} else {
					$related_groups[ $key ] = [];
				}

				update_post_meta( $variation_id, '_related_group', $related_groups[ $loop ] );
			}
		} else {
			delete_post_meta( $variation_id, '_related_group' );
		}
	}

	/**
	 * Remove course when order is refunded
	 *
	 * @param int   $order_id    Order ID.
	 * @param int   $customer_id Customer ID (optional).
	 * @param array $products    Custom products if exists.
	 */
	public static function remove_course_access( $order_id, $customer_id = null, $products = [] ) {
		$order = wc_get_order( $order_id );

		if (
			$order !== false
			&& is_a( $order, 'WC_Order' )
		) {
			/**
			 * Only get items for non-subscription products
			 *
			 * The $learndash_woocommerce_get_items_filter_out_subscriptions variable is required to be "true" for the filter to work
			 *
			 * @see Learndash_WooCommerce::filter_subscription_products_out() Filter subscription products out when getting items for course access update
			 * @var array
			 */
			global $learndash_woocommerce_get_items_filter_out_subscriptions;
			$learndash_woocommerce_get_items_filter_out_subscriptions = true;

			if ( empty( $products ) ) {
				$products = $order->get_items();
			}

			$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $order->get_user_id();

			foreach ( $products as $product ) {
				if ( ! empty( $product->get_variation_id() ) ) {
					$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
					$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
				} else {
					$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
					$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
				}

				if (
					$courses_id
					&& is_array( $courses_id )
				) {
					foreach ( $courses_id as $course_id ) {
						self::update_remove_course_access( $course_id, $customer_id, $order_id );
					}
				}

				if (
					$groups_id
					&& is_array( $groups_id )
				) {
					foreach ( $groups_id as $group_id ) {
						self::update_remove_group_access( $group_id, $customer_id, $order_id );
					}
				}
			}
		}
	}

	/**
	 * Remove course access on order refund
	 *
	 * @param int $order_id  ID of the order.
	 * @param int $refund_id ID of the refund.
	 * @return void
	 */
	public static function remove_course_access_on_refund( $order_id, $refund_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $refund_id parameter is passed in the filter function and kept for backward compatibility in case the method is called directly in other places.
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$products     = [];
		$refunds      = $order->get_refunds();
		$used_refunds = [];

		if ( empty( $refunds ) ) {
			return;
		}

		foreach ( $refunds as $refund ) {
			if (
				/**
				 * Filters whether to skip order refund actions or not in LearnDash WooCommerce.
				 *
				 * @since 5.2.0
				 *
				 * @param bool            $skip     Whether to skip the order refund actions. Default false.
				 * @param WC_Order_Refund $refund   WC_Order_Refund object.
				 * @param int             $order_id Order ID.
				 *
				 * @return bool True to skip, false otherwise.
				 */
				apply_filters( 'learndash_woocommerce_order_refund_skip', false, $refund, $order_id )
			) {
				continue;
			}

			$used_refunds[]    = $refund;
			$refunded_products = $refund->get_items();
			$products          = array_merge( $products, $refunded_products );
		}

		if (
			$order->get_status() !== 'refunded'
			&& ! empty( $products )
		) {
			self::remove_course_access( $order_id, null, $products );
		} elseif (
			$order->get_status() === 'refunded'
			&& ! empty( $used_refunds )
		) {
			self::remove_course_access( $order_id );
		}

		/**
		 * Action to perform additional actions after order refunds are refunded.
		 *
		 * @since 5.2.0
		 *
		 * @param WC_Order_Refund[] $refunds  Refunds array.
		 * @param int               $order_id Order ID.
		 */
		do_action( 'learndash_woocommerce_order_refund_after', $refunds, $order_id );
	}

	/**
	 * Enroll customer into order's associated courses and groups
	 *
	 * @param int $order_id     WC_Order ID.
	 * @param int $customer_id  Customer ID (optional).
	 *
	 * @return void
	 */
	public static function add_course_access( $order_id, $customer_id = null ) {
		$order = wc_get_order( $order_id );

		if (
			$order !== false
			&& is_a( $order, 'WC_Order' )
		) {
			/**
			 * Only get items for non-subscription products
			 *
			 * @see Learndash_WooCommerce::filter_subscription_products_out() Filter subscription products out when getting items for course access update
			 * @var array
			 */
			global $learndash_woocommerce_get_items_filter_out_subscriptions;
			$learndash_woocommerce_get_items_filter_out_subscriptions = true;
			$products = $order->get_items();

			$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $order->get_user_id();

			$user = get_user_by( 'ID', $customer_id );

			if ( ! $user ) {
				return;
			}

			$courses_count = 0;
			$groups_count  = 0;
			array_walk(
				$products,
				function ( $product ) use ( &$courses_count, &$groups_count ) {
					if ( ! empty( $product->get_variation_id() ) ) {
						$courses = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
						$groups  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
					} else {
						$courses = (array) get_post_meta( $product['product_id'], '_related_course', true );
						$groups  = (array) get_post_meta( $product['product_id'], '_related_group', true );
					}

					$courses = array_filter(
						$courses,
						function ( $course_id ) {
							return ! empty( $course_id ) && is_numeric( $course_id );
						}
					);

					$groups = array_filter(
						$groups,
						function ( $group_id ) {
							return ! empty( $group_id ) && is_numeric( $group_id );
						}
					);

					$courses_count += count( $courses );
					$groups_count  += count( $groups );
				}
			);

			if (
				$courses_count + $groups_count >= self::get_products_count_for_silent_course_enrollment()
				&& ! self::is_running_in_background_batch()
			) {
				self::enqueue_silent_course_enrollment( [ 'order_id' => $order_id ] );
			} else {
				foreach ( $products as $product ) {
					if ( ! empty( $product->get_variation_id() ) ) {
						$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
						$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
					} else {
						$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
						$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
					}

					if (
						$courses_id
						&& is_array( $courses_id )
					) {
						foreach ( $courses_id as $course_id ) {
							$course_id = intval( $course_id );

							if ( ! self::is_order_product_access_expired( $order_id, $course_id ) ) {
								self::update_add_course_access( $course_id, $customer_id, $order_id );
							} else {
								self::update_remove_course_access( $course_id, $customer_id, $order_id );
							}
						}
					}

					if (
						$groups_id
						&& is_array( $groups_id )
					) {
						foreach ( $groups_id as $group_id ) {
							$group_id = intval( $group_id );

							if ( ! self::is_order_product_access_expired( $order_id, $group_id ) ) {
								self::update_add_group_access( $group_id, $customer_id, $order_id );
							} else {
								self::update_remove_group_access( $group_id, $customer_id, $order_id );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Check if a course or group associated with an order is already expired based on order paid date.
	 *
	 * @since 5.2.0
	 *
	 * @param int $order_id   WooCommerce order or subscription ID.
	 * @param int $product_id LearnDash course or group ID.
	 *
	 * @return bool True if expired, false otherwise.
	 */
	private static function is_order_product_access_expired( int $order_id, int $product_id ): bool {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$product = Product::find( $product_id );
		$user_id = $order->get_user_id();

		if ( ! $product ) {
			return false;
		}

		$course_expiration = $product->get_setting( 'expire_access' );

		if ( empty( $course_expiration ) ) {
			return false;
		}

		$order_date = $order->get_date_paid();

		if ( ! $order_date ) {
			return false;
		}

		$expiration_days = $product->get_setting( 'expire_access_days' );

		if (
			empty( $expiration_days )
			|| $expiration_days < 1
		) {
			return false;
		}

		$start_time = $product->get_start_date( $user_id );
		$order_time = strtotime( $order_date->date( 'Y-m-d H:i:s' ) );

		$start_time = ! empty( $start_time )
			? $start_time
			: $order_time;

		$should_expire_at = strtotime( '+' . $expiration_days . ' days', intval( $start_time ) );

		return time() > $should_expire_at;
	}

	/**
	 * Handler when an order is deleted or trashed.
	 *
	 * @param int      $order_id WooCommerce order ID.
	 * @param WC_Order $order    WooCommerce order object.
	 *
	 * @return void
	 */
	public static function delete_order( $order_id, $order ) {
		if ( 'shop_order' === $order->get_type() ) {
			self::remove_course_access( $order_id );
		} elseif ( 'shop_subscription' === $order->get_type() ) {
			if ( function_exists( 'wcs_get_subscription' ) ) {
				$subscription = wcs_get_subscription( $order_id );

				if ( $subscription ) {
					self::remove_subscription_course_access( $subscription );
				}
			}
		}
	}

	/**
	 * Handler when an order is updated, hooked to
	 * woocommerce_process_shop_order_meta
	 *
	 * This method is used to handle course access update
	 * in some scenarios such as:
	 * 1. Order's customer change
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function update_order_meta( $order_id ) {
		$order = wc_get_order( $order_id );

		// try to validate again.
		if ( ! is_object( $order ) ) {
			// break out so no fatal.
			return;
		}

		$old_customer = $order->get_customer_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The method is called in WooCommerce action hook and the WooCommerce methods that call the hook already do nonce validation.
		$new_customer = isset( $_POST['customer_user'] ) ? intval( $_POST['customer_user'] ) : false;

		if (
			$old_customer
			&& $new_customer
			&& $old_customer !== $new_customer
		) {
			if ( in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ) {
				self::remove_course_access( $order_id, $old_customer );
				self::add_course_access( $order_id, $new_customer );
			}
		}
	}

	/**
	 * Handler when an subscription is updated, hooked to
	 * woocommerce_process_shop_subscription_meta
	 *
	 * This method is used to handle course access update
	 * in some scenarios such as:
	 * 1. Order's customer change
	 *
	 * @param int $subscription_id ID of the subscription.
	 * @return void
	 */
	public static function update_subscription_meta( $subscription_id ) {
		if ( is_object( $subscription_id ) ) {
			$subscription = $subscription_id;
		} else {
			$subscription = wcs_get_subscription( $subscription_id );
		}

		if ( ! is_object( $subscription ) ) {
			return;
		}

		if ( empty( $subscription->get_id() ) ) {
			return;
		}

		$old_customer = $subscription->get_customer_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The method is called in WooCommerce Subscription action hook and the WooCommerce Subscription methods that call the hook already do nonce validation.
		$new_customer = isset( $_POST['customer_user'] ) ? intval( $_POST['customer_user'] ) : false;

		if (
			$old_customer
			&& $new_customer
			&& $old_customer !== $new_customer
		) {
			if ( in_array( $subscription->get_status(), [ 'active' ], true ) ) {
				self::remove_subscription_course_access( $subscription, [], $old_customer );

				self::add_subscription_course_access( $subscription, [], $new_customer );
			}
		}
	}

	/**
	 * Process new order item added to existing order or subscription
	 *
	 * @since 5.2.0
	 *
	 * @param int           $item_id    Order item ID.
	 * @param WC_Order_Item $item       WC order item object.
	 * @param int           $order_id   WooCommerce order ID.
	 *
	 * @return void
	 */
	public static function process_new_order_item( $item_id, $item, $order_id ) {
		$order = wc_get_order( $order_id );

		if (
			! $order
			|| ! is_a( $order, 'WC_Order' )
		) {
			return;
		}

		if (
			function_exists( 'wcs_is_subscription' )
			&& wcs_is_subscription( $order )
		) {
			$subscription = wcs_get_subscription( $order_id );

			if ( in_array( $subscription->get_status(), [ 'active' ], true ) ) {
				self::add_subscription_course_access( $subscription );
			}
		} elseif ( $order->get_type() === 'shop_order' ) {
			if ( in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ) {
				self::add_course_access( $order_id );
			}
		}
	}

	/**
	 * Process order item deletion from order or subscription
	 *
	 * @param int $item_id WooCommerce order item ID.
	 *
	 * @return void
	 */
	public static function delete_order_item( $item_id ) {
		$order_id = wc_get_order_id_by_order_item_id( $item_id );
		$order    = wc_get_order( $order_id );

		if (
			! $order
			|| ! is_a( $order, 'WC_Order' )
		) {
			return;
		}

		$order_item = new WC_Order_Item_Product( $item_id );

		if (
			function_exists( 'wcs_is_subscription' )
			&& wcs_is_subscription( $order )
		) {
			$subscription = wcs_get_subscription( $order_id );

			self::remove_subscription_course_access( $subscription, [ $order_item ] );
		} elseif ( $order->get_type() === 'shop_order' ) {
			self::remove_course_access( $order_id, null, [ $order_item ] );
		}
	}

	/**
	 * Filter only subscription products out from order processing methods
	 *
	 * @param array $items Original items.
	 * @param array $order Order object.
	 * @param array $types Item types.
	 *
	 * @return array $items Modified $items
	 */
	public static function filter_subscription_products_out( $items, $order, $types ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $order and $types parameter are passed in the filter function and kept for backward compatibility in case the method is called directly in other places.
		global $learndash_woocommerce_get_items_filter_out_subscriptions;
		if ( $learndash_woocommerce_get_items_filter_out_subscriptions ) {
			$learndash_woocommerce_get_items_filter_out_subscriptions = false;

			$items = array_filter(
				$items,
				function ( $item ) {
					$product = $item->get_product();

					if (
						$product
						&& is_a( $product, 'WC_Product' )
					) {
						return ! self::is_subscription_product( $product );
					} else {
						return true;
					}
				}
			);
		}

		return $items;
	}

	/**
	 * Checks whether a product is a subscription of any kind.
	 *
	 * Access granted by a subscription follows the subscription's own status rather than the status
	 * of the orders behind it, so subscription products are kept out of the order based access
	 * handlers. A variable subscription reaches those handlers as a `subscription_variation` line
	 * item, and its parent as `variable-subscription`, so the type cannot be compared to
	 * `subscription` on its own.
	 *
	 * @since 5.2.0
	 *
	 * @param WC_Product $product WooCommerce product object.
	 *
	 * @return bool True when the product is a subscription of any type, false otherwise.
	 */
	private static function is_subscription_product( $product ): bool {
		// Preferred, because a store can register its own subscription types with WooCommerce Subscriptions.
		if ( class_exists( 'WC_Subscriptions_Product' ) ) {
			return (bool) WC_Subscriptions_Product::is_subscription( $product );
		}

		return $product->is_type( [ 'subscription', 'subscription_variation', 'variable-subscription' ] );
	}

	/**
	 * Checks whether course enrollment is already running inside a background batch.
	 *
	 * Enrollment for a product carrying many courses is handed to the queue, so that one request
	 * does not enroll a customer in dozens of courses at once. The processes that drain that queue
	 * are batched already, and queueing from inside one of them would mean the work is never
	 * carried out, only handed back to the queue it came from.
	 *
	 * @since 5.2.0
	 *
	 * @return bool True when a background batch is running, false otherwise.
	 */
	private static function is_running_in_background_batch(): bool {
		$batch_hooks = [
			// Drains the silent enrollment queue.
			'learndash_woocommerce_cron',
			// The retroactive access tool, which runs in batches of its own through Action Scheduler.
			'learndash_woocommerce_retroactive_access_tool',
			// The same tool before 2.0.0, still registered for backward compatibility.
			'wp_ajax_ld_wc_retroactive_access',
		];

		return in_array( current_filter(), $batch_hooks, true );
	}

	/**
	 * Debug
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0 The method is deprecated and will be removed in future versions.
	 *
	 * @param string $msg Debug message.
	 *
	 * @return void
	 */
	public static function debug( $msg ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- The $msg parameter is kept for backward compatibility.
		_deprecated_function( __METHOD__, '2.0.0' );
	}

	/**
	 * Get available courses
	 *
	 * @return array<int, string>
	 */
	public static function list_courses() {
		$courses = get_posts(
			[
				'post_type'        => learndash_get_post_type_slug( 'course' ),
				'posts_per_page'   => -1,
				'suppress_filters' => true,
			]
		);

		$returned_courses = [];
		foreach ( $courses as $course ) {
			$returned_courses[ $course->ID ] = $course->post_title;
		}

		return $returned_courses;
	}

	/**
	 * Get available groups
	 *
	 * @return array<int, string>
	 */
	public static function list_groups() {
		$groups = get_posts(
			[
				'post_type'        => learndash_get_post_type_slug( 'group' ),
				'posts_per_page'   => -1,
				'suppress_filters' => true,
			]
		);

		$returned_groups = [];
		foreach ( $groups as $group ) {
			$returned_groups[ $group->ID ] = $group->post_title;
		}

		return $returned_groups;
	}

	/**
	 * Handle subscription status change to remove LD course access
	 *
	 * @param WC_Subscription $subscription   WC_Subscription object.
	 * @param WC_Order_Item[] $products       WC order items.
	 * @param int             $customer_id    Customer ID (optional).
	 * @return void
	 */
	public static function remove_subscription_course_access( $subscription, $products = [], $customer_id = null ) {
		if ( ! apply_filters( 'ld_woocommerce_remove_subscription_course_access', true, $subscription, current_filter() ) ) {
			return;
		}

		if ( empty( $products ) ) {
			// Get products related to this order.
			$products = $subscription->get_items();
		}

		$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $subscription->get_user_id();

		foreach ( $products as $product ) {
			if ( ! empty( $product->get_variation_id() ) ) {
				$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
				$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
			} else {
				$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
				$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
			}

			if (
				$courses_id
				&& is_array( $courses_id )
			) {
				foreach ( $courses_id as $course_id ) {
					self::update_remove_course_access( $course_id, $customer_id, $subscription->get_id() );

					foreach ( $subscription->get_related_orders() as $order_id ) {
						self::update_remove_course_access( $course_id, $customer_id, $order_id );
					}
				}
			}

			if (
				$groups_id
				&& is_array( $groups_id )
			) {
				foreach ( $groups_id as $group_id ) {
					self::update_remove_group_access( $group_id, $customer_id, $subscription->get_id() );

					foreach ( $subscription->get_related_orders() as $order_id ) {
						self::update_remove_group_access( $group_id, $customer_id, $order_id );
					}
				}
			}
		}
	}

	/**
	 * Handle subscription status change to add LD course access
	 *
	 * @param WC_Subscription $subscription   WC_Subscription object.
	 * @param WC_Order_Item[] $products       WC order items.
	 * @param int             $customer_id    WC Customer ID (optional).
	 *
	 * @return void
	 */
	public static function add_subscription_course_access( $subscription, $products = [], $customer_id = null ) {
		if (
			false === $subscription
			|| ! is_a( $subscription, 'WC_Subscription' )
		) {
			return;
		}

		if ( ! apply_filters( 'ld_woocommerce_add_subscription_course_access', true, $subscription, current_filter() ) ) {
			return;
		}

		if ( empty( $products ) ) {
			// Get products related to this order.
			$products = $subscription->get_items();
		}

		$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $subscription->get_user_id();

		$start_date = $subscription->get_date( 'date_created' );

		$courses_count = 0;
		$groups_count  = 0;
		array_walk(
			$products,
			function ( $product ) use ( &$courses_count, &$groups_count ) {
				if ( ! empty( $product->get_variation_id() ) ) {
					$courses = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
					$groups  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
				} else {
					$courses = (array) get_post_meta( $product['product_id'], '_related_course', true );
					$groups  = (array) get_post_meta( $product['product_id'], '_related_group', true );
				}

				$courses = array_filter(
					$courses,
					function ( $course_id ) {
						return ! empty( $course_id ) && is_numeric( $course_id );
					}
				);

				$groups = array_filter(
					$groups,
					function ( $group_id ) {
						return ! empty( $group_id ) && is_numeric( $group_id );
					}
				);

				$courses_count += count( $courses );
				$groups_count  += count( $groups );
			}
		);

		if (
			$courses_count + $groups_count >= self::get_products_count_for_silent_course_enrollment()
			&& ! self::is_running_in_background_batch()
		) {
			self::enqueue_silent_course_enrollment( [ 'subscription_id' => $subscription->get_id() ] );
		} else {
			foreach ( $products as $product ) {
				if ( ! empty( $product->get_variation_id() ) ) {
					$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
					$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
				} else {
					$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
					$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
				}

				// Update access to the courses.
				if (
					$courses_id
					&& is_array( $courses_id )
				) {
					foreach ( $courses_id as $course_id ) {
						$access_from_key = 'course_' . $course_id . '_access_from';

						/*
						 * The start date anchors drip feeding, so it belongs to the subscription that
						 * granted the course and is set once. A subscription passes through an access
						 * granting status on every renewal, and rewriting the date there would discard
						 * one the store owner had corrected by hand.
						 *
						 * The access counter records which subscription granted which course, so it
						 * tells a renewal of the same subscription apart from a different one taking
						 * over. A date left behind by an earlier subscription is not carried into a new
						 * one, whatever the store's settings do about withdrawing access. A student with
						 * no date at all is always given one, so none of this can leave them without.
						 *
						 * Both reads have to happen before enrolling. Enrollment stamps a start date of
						 * its own and adds the subscription to the counter, so checking afterwards would
						 * find every subscription already granted and every student already dated.
						 */
						$granting_ids = array_map(
							'intval',
							(array) ( self::get_courses_access_counter( $customer_id )[ $course_id ] ?? [] )
						);

						$reset_access_from =
							empty( get_user_meta( $customer_id, $access_from_key, true ) )
							|| ! in_array( (int) $subscription->get_id(), $granting_ids, true );

						self::update_add_course_access( $course_id, $customer_id, $subscription->get_id() );

						/**
						 * Filters whether to reset the course access start date when a subscription is activated.
						 *
						 * Defaults to true when this subscription has not already granted the course, or the student has no start date.
						 *
						 * @since 5.2.0
						 *
						 * @param bool            $reset        True to reset|false otherwise.
						 * @param int             $course_id    Course ID.
						 * @param WC_Subscription $subscription WC_Subscription object.
						 *
						 * @return bool True to reset|false otherwise.
						 */
						if ( apply_filters( 'learndash_woocommerce_reset_subscription_course_access_from', $reset_access_from, $course_id, $subscription ) ) {
							update_user_meta( $customer_id, $access_from_key, strtotime( $start_date ) );
						}
					}
				}

				if (
					$groups_id
					&& is_array( $groups_id )
				) {
					foreach ( $groups_id as $group_id ) {
						self::update_add_group_access( $group_id, $customer_id, $subscription->get_id() );

						// Replace start date to keep the drip feeding working.

						/**
						 * Filters whether to reset the group access start date when a subscription is activated.
						 *
						 * @since 5.2.0
						 *
						 * @param bool            $reset        True to reset|false otherwise.
						 * @param int             $group_id     Group ID.
						 * @param WC_Subscription $subscription WC_Subscription object.
						 *
						 * @return bool True to reset|false otherwise.
						 */
						if ( apply_filters( 'learndash_woocommerce_reset_subscription_group_access_from', true, $group_id, $subscription ) ) {
							update_user_meta( $customer_id, 'learndash_group_enrolled_' . $group_id, strtotime( $start_date ) );
						}
					}
				}
			}
		}
	}

	/**
	 * Add subscription settings related to LearnDash
	 *
	 * @param array $settings Original settings array.
	 */
	public static function add_subscription_settings( $settings ) {
		return array_merge(
			$settings,
			[
				[
					'name' => 'LearnDash',
					'type' => 'title',
					'desc' => __( 'WooCommerce subscription settings related to LearnDash.', 'learndash' ),
					'id'   => 'learndash_woocommerce_section',
				],
				[
					'name'     => __( 'Access Removal on Expiration', 'learndash' ),
					'desc'     => __( 'Disable', 'learndash' ),
					'desc_tip' => __( 'Check the box to disable course access removal on subscription expiration. By default, course access will be revoked when a subscription expires.', 'learndash' ),
					'id'       => 'learndash_woocommerce_disable_access_removal_on_expiration',
					'css'      => '',
					'type'     => 'checkbox',
					'default'  => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'learndash_woocommerce_section',
				],
			]
		);
	}

	/**
	 * Filter hook function to check subscription course access removal
	 *
	 * @deprecated 5.2.0
	 *
	 * @param bool            $remove       True to remove|false otherwise.
	 * @param WC_Subscription $subscription WC_Subscription object.
	 * @param string          $filter       Current filter hook.
	 *
	 * @return bool                 Returned $remove value.
	 */
	public static function check_subscription_course_access_removal( $remove, $subscription, $filter ) {
		_deprecated_function( __METHOD__, '2.0.1' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- Kept to prevent regression error. TODO: refactor this not to use wp_debug_backtrace_summary().
		$backtrace = wp_debug_backtrace_summary();

		if (
			$filter === 'woocommerce_subscription_status_on-hold'
			&& (
				strpos( $backtrace, 'process_renewal' ) !== false
				|| strpos( $backtrace, 'Renewal_Order' ) !== false
			)
		) {
			return false;
		}

		return $remove;
	}

	/**
	 * Filter hook function to check subscription course access addition
	 *
	 * @deprecated 5.2.0
	 *
	 * @param bool            $add          True to remove|false otherwise.
	 * @param WC_Subscription $subscription WC_Subscription object.
	 * @param string          $filter       Current filter hook.
	 *
	 * @return bool                 Returned $add value.
	 */
	public static function check_subscription_course_access_addition( $add, $subscription, $filter ) {
		_deprecated_function( __METHOD__, '2.0.1' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- Kept to prevent regression error. TODO: refactor this not to use wp_debug_backtrace_summary().
		$backtrace = wp_debug_backtrace_summary();

		if (
			$filter === 'woocommerce_subscription_status_active'
			&& (
				strpos( $backtrace, 'process_renewal' ) !== false
				|| strpos( $backtrace, 'Renewal_Order' ) !== false
			)
		) {
			return false;
		}

		return $add;
	}

	/**
	 * Remove course access when user completes billing cycle
	 *
	 * @param WC_Subscription $subscription WC_Subscription object.
	 * @param array           $last_order   Last order details.
	 */
	public static function remove_course_access_on_billing_cycle_completion( $subscription, $last_order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $last_order parameter is passed in the filter method and kept for backward compatibility.
		if ( self::is_course_access_removed_on_subscription_billing_cycle_completion( $subscription ) ) {
			$next_payment_date = $subscription->calculate_date( 'next_payment' );

			// Check if there's no next payment date.
			// See calculate_date() in class-wc-subscriptions.php.
			if ( 0 === $next_payment_date ) {
				self::remove_subscription_course_access( $subscription );
			}
		}
	}

	/**
	 * Handle course access when subscription switching happens
	 *
	 * @param Automattic\WooCommerce\Admin\Overrides\Order $order WC order object.
	 * @param array                                        $data  WC order data.
	 *
	 * @return void
	 */
	public static function switch_subscription( $order, $data ) {
		foreach ( $data as $subscription_id => $subscription_data ) {
			foreach ( $subscription_data['switches'] as $switch_item_id => $switch_data ) {
				$subscriptions = wcs_get_subscriptions_for_switch_order( $order );

				if ( ! empty( $switch_data['remove_line_item'] ) ) {
					$old_order = wc_get_order( wc_get_order_id_by_order_item_id( $switch_data['remove_line_item'] ) );
					if (
						$old_order
						&& is_a( $old_order, 'WC_Order' )
					) {
						foreach ( $subscriptions as $subscription ) {
							self::remove_subscription_course_access( $subscription, $old_order->get_items() );
						}
					}
				}

				if ( ! empty( $switch_data['add_line_item'] ) ) {
					self::add_subscription_course_access( $subscription, $order->get_items() );
				}
			}
		}
	}

	/**
	 * Enqueue course enrollment in database for product with many courses
	 *
	 * @param array{order_id?: int, subscription_id?: int} $args Enrollment arguments.
	 *
	 * @return void
	 */
	public static function enqueue_silent_course_enrollment( $args ) {
		$queue = get_option( 'learndash_woocommerce_silent_course_enrollment_queue', [] );
		$queue = is_array( $queue ) ? $queue : [];

		if ( ! empty( $args['order_id'] ) ) {
			$queue[ $args['order_id'] ] = $args;
		} elseif ( ! empty( $args['subscription_id'] ) ) {
			$queue[ $args['subscription_id'] ] = $args;
		}

		update_option( 'learndash_woocommerce_silent_course_enrollment_queue', $queue, false );
	}

	/**
	 * Process silent background course enrollment using cron
	 *
	 * @return void
	 */
	public static function process_silent_course_enrollment() {
		$queue = get_option( 'learndash_woocommerce_silent_course_enrollment_queue', [] );

		/**
		 * Filters the number of items to process in the silent course enrollment queue.
		 *
		 * @since 5.2.0
		 *
		 * @param int $queue_count Number of items to process in the silent course enrollment queue.
		 *
		 * @return int Number of items to process in the silent course enrollment queue.
		 */
		$queue_count = apply_filters( 'learndash_woocommerce_process_silent_course_enrollment_queue_count', 1 );

		$processed_queue = array_slice( $queue, 0, $queue_count, true );

		foreach ( $processed_queue as $id => $args ) {
			if ( ! empty( $args['order_id'] ) ) {
				self::add_course_access( $args['order_id'] );
			} elseif ( ! empty( $args['subscription_id'] ) ) {
				self::add_subscription_course_access( wcs_get_subscription( $args['subscription_id'] ) );
			}

			unset( $queue[ $id ] );
		}

		update_option( 'learndash_woocommerce_silent_course_enrollment_queue', $queue, false );
	}

	/**
	 * Check if cart contains LearnDash course or group.
	 *
	 * @return bool True if LD object found|false otherwise.
	 */
	private static function cart_contains_learndash_object(): bool {
		$ld_object_found = false;

		$wc_cart = WC()->cart;
		if ( is_a( $wc_cart, 'WC_Cart' ) ) {
			$cart_items = $wc_cart->cart_contents;
			foreach ( $cart_items as $key => $item ) {
				$courses = (array) get_post_meta( $item['data']->get_id(), '_related_course', true );
				$courses = maybe_unserialize( $courses );

				$groups = (array) get_post_meta( $item['data']->get_id(), '_related_group', true );
				$groups = maybe_unserialize( $groups );

				// Filter out invalid courses and groups first.
				$courses = array_filter(
					$courses,
					function ( $course_id ) {
						$course = get_post( $course_id );

						return $course && $course->post_status === 'publish' && $course->post_type === 'sfwd-courses';
					}
				);

				$groups = array_filter(
					$groups,
					function ( $group_id ) {
						$group = get_post( $group_id );

						return $group && $group->post_status === 'publish' && $group->post_type === 'groups';
					}
				);

				if ( ! empty( $courses ) ) {
					$ld_object_found = true;
				}

				if ( ! empty( $groups ) ) {
					$ld_object_found = true;
				}
			}
		}

		return $ld_object_found;
	}

	/**
	 * Loads script to enable registration on checkout page.
	 *
	 * @deprecated 5.2.0
	 *
	 * @param object $checkout Checkout object.
	 */
	public static function enable_signup_on_checkout( $checkout ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- The $checkout parameter is kept for backward compatibility.
		_deprecated_function( __METHOD__, '2.0.0' );

		if (
			self::cart_contains_learndash_object()
			&& ! is_user_logged_in()
		) {
			self::add_front_scripts();
		}
	}

	/**
	 * Filters WooCommerce registration enabled setting.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $enabled Whether registration is enabled or not.
	 *
	 * @return bool
	 */
	public static function enable_registration( $enabled ) {
		if (
			self::cart_contains_learndash_object()
			&& ! is_user_logged_in()
		) {
			$enabled = true;
		}

		return $enabled;
	}

	/**
	 * Filters WooCommerce registration required setting.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $required Whether registration is required or not.
	 *
	 * @return bool
	 */
	public static function require_registration( $required ) {
		/**
		 * Filters whether registration is required during checkout.
		 *
		 * @since 5.2.0
		 *
		 * @param bool $required True to require registration|false otherwise.
		 *
		 * @return bool
		 */
		$is_required = apply_filters( 'learndash_woocommerce_registration_required', true );

		if (
			$is_required
			&& self::cart_contains_learndash_object()
			&& ! is_user_logged_in()
		) {
			$required = true;
		}

		return $required;
	}

	/**
	 * Forces registration when the cart contains a product with associated course(s) or group(s).
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public static function force_registration_during_checkout(): void {
		if (
			self::cart_contains_learndash_object()
			&& ! is_user_logged_in()
		) {
			$_POST['createaccount'] = 1;
		}
	}

	/**
	 * Autocomplete transaction if all cart items are course items
	 *
	 * @since 5.2.0
	 *
	 * @param int $order_id ID of the order.
	 *
	 * @return void
	 */
	public static function auto_complete_transaction( $order_id ) {
		/**
		 * Filters to enable/disable auto complete order.
		 *
		 * @since 5.2.0
		 *
		 * @param bool $enable True to enable|false otherwise.
		 * @param int  $order_id Order ID.
		 *
		 * @return bool True to enable|false otherwise.
		 */
		if ( ! apply_filters( 'learndash_woocommerce_auto_complete_order', true, $order_id ) ) {
			return;
		}

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if (
			! $order
			|| ! is_a( $order, 'WC_Order' )
		) {
			return;
		}

		if ( ! $order->is_paid() ) {
			return;
		}

		if ( 'completed' === $order->get_status() ) {
			return;
		}

		$items          = $order->get_items();
		$payment_method = $order->get_payment_method();

		/**
		 * Filters the list of manual payment methods.
		 *
		 * @since 5.2.0
		 *
		 * @param array<string> $manual_payment_methods List of manual payment methods.
		 *
		 * @return array<string> List of manual payment methods.
		 */
		$manual_payment_methods = apply_filters(
			'learndash_woocommerce_manual_payment_methods',
			[
				'bacs',
				'cheque',
				'cod',
			]
		);

		// If using manual payment, bail.
		if ( in_array( $payment_method, $manual_payment_methods, true ) ) {
			return;
		}

		$found = [];
		foreach ( $items as $item ) {
			// If variation product.
			if ( $item->get_variation_id() > 0 ) {
				$item_id = $item->get_variation_id();
				$courses = (array) get_post_meta( $item->get_variation_id(), '_related_course', true );
				$groups  = (array) get_post_meta( $item->get_variation_id(), '_related_group', true );
			} elseif ( $item->get_product_id() > 0 ) {
				// Else if normal product.
				$item_id = $item->get_product_id();
				$courses = (array) get_post_meta( $item->get_product_id(), '_related_course', true );
				$groups  = (array) get_post_meta( $item->get_product_id(), '_related_group', true );
			}

			// Filter out invalid courses and groups first.
			$courses = array_map(
				function ( $course_id ) {
					$course = get_post( $course_id );

					return $course
							&& $course->post_status === 'publish'
							&& $course->post_type === 'sfwd-courses';
				},
				$courses
			);

			$groups = array_map(
				function ( $group_id ) {
					$group = get_post( $group_id );

					return $group
							&& $group->post_status === 'publish'
							&& $group->post_type === 'groups';
				},
				$groups
			);

			if (
				(
					is_array( $courses )
					&& ! empty( $courses )
					&& ! in_array( 0, $courses )
				)
				|| (
					(
						is_array( $courses )
						&& count( $courses ) > 1
						&& in_array( 0, $courses )
					)
					|| (
						$item->is_type( 'virtual' )
						|| $item->is_type( 'downloadable' )
					)
				)
				|| (
					is_array( $groups )
					&& ! empty( $groups )
					&& ! in_array( 0, $groups )
				)
				|| (
					(
						is_array( $groups )
						&& count( $groups ) > 1
						&& in_array( 0, $groups )
					)
					|| (
						$item->is_type( 'virtual' )
						|| $item->is_type( 'downloadable' )
					)
				)
			) {
				$found[] = $item_id;
			}
		}

		// Autocomplete transaction if all items are course.
		if ( count( $found ) === count( $items ) ) {
			$order->update_status( 'completed' );
		}
	}

	/**
	 * Remove course access count if user data is removed
	 *
	 * @param int $user_id ID of the user.
	 */
	public static function remove_access_increment_count( $user_id ) {
		delete_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter' );
	}

	/**
	 * Add course access.
	 *
	 * @since 5.2.0
	 *
	 * @param int $course_id ID of a course.
	 * @param int $user_id   ID of a user.
	 * @param int $order_id  ID of a WooCommerce order.
	 *
	 * @return void
	 */
	private static function update_add_course_access( $course_id, $user_id, $order_id ) {
		self::increment_course_access_counter( $course_id, $user_id, $order_id );

		// check if user already enrolled.
		if ( ! self::is_user_enrolled_to_course( $user_id, $course_id ) ) {
			ld_update_course_access( $user_id, $course_id );

			self::update_course_access_from_with_order_data( (int) $course_id, (int) $user_id, (int) $order_id );
		} elseif (
			self::is_user_enrolled_to_course( $user_id, $course_id )
			&& ld_course_access_expired( $course_id, $user_id )
		) {
			// Remove access first.
			// @todo: only remove access counter from old WC orders.
			self::reset_course_access_counter( $course_id, $user_id );
			ld_update_course_access( $user_id, $course_id, $remove = true );

			// Re-enroll to get new access from value.
			self::increment_course_access_counter( $course_id, $user_id, $order_id );
			ld_update_course_access( $user_id, $course_id );
		}
	}

	/**
	 * Update course access from value with order data.
	 *
	 * @since 5.2.0
	 *
	 * @param int $course_id LearnDash course ID.
	 * @param int $user_id   WordPress user ID.
	 * @param int $order_id  WooCommerce user ID.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	private static function update_course_access_from_with_order_data( int $course_id, int $user_id, int $order_id ): bool {
		$product = Product::find( $course_id );

		if ( ! $product ) {
			return false;
		}

		$start_time = $product->get_start_date( $user_id );

		if ( ! empty( $start_time ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$order_date = $order->get_date_paid();

		if ( ! $order_date ) {
			return false;
		}

		$order_time = $order_date->date( 'Y-m-d H:i:s' );

		return ld_course_access_from_update( $course_id, $user_id, $order_time );
	}

	/**
	 * Remove course access
	 *
	 * @since 5.2.0
	 *
	 * @param int $course_id ID of a course.
	 * @param int $user_id   ID of a user.
	 * @param int $order_id  ID of an order.
	 *
	 * @return void
	 */
	private static function update_remove_course_access( $course_id, $user_id, $order_id ) {
		$courses = self::decrement_course_access_counter( $course_id, $user_id, $order_id );

		if (
			! isset( $courses[ $course_id ] )
			|| empty( $courses[ $course_id ] )
		) {
			ld_update_course_access( $user_id, $course_id, $remove = true );
		}
	}

	/**
	 * Add group access
	 *
	 * @since 5.2.0
	 *
	 * @param int $group_id LearnDash group ID.
	 * @param int $user_id  WP_User ID.
	 * @param int $order_id WC order ID.
	 *
	 * @return void
	 */
	private static function update_add_group_access( $group_id, $user_id, $order_id ) {
		self::increment_course_access_counter( $group_id, $user_id, $order_id );

		if ( ! learndash_is_user_in_group( $user_id, $group_id ) ) {
			ld_update_group_access( $user_id, $group_id );
		}
	}

	/**
	 * Remove group access
	 *
	 * @since 5.2.0
	 *
	 * @param int $group_id LearnDash group ID.
	 * @param int $user_id  WP_User ID.
	 * @param int $order_id WC order ID.
	 *
	 * @return void
	 */
	private static function update_remove_group_access( $group_id, $user_id, $order_id ) {
		$access = self::decrement_course_access_counter( $group_id, $user_id, $order_id );

		if (
			! isset( $access[ $group_id ] )
			|| empty( $access[ $group_id ] )
		) {
			ld_update_group_access( $user_id, $group_id, $remove = true );
		}
	}

	/**
	 * Check if a user is already enrolled to a course
	 *
	 * @since 5.2.0
	 *
	 * @param integer $user_id   User ID.
	 * @param integer $course_id Course ID.
	 *
	 * @return boolean            True if enrolled|false otherwise
	 */
	private static function is_user_enrolled_to_course( $user_id = 0, $course_id = 0 ) {
		$enrolled_courses = learndash_user_get_enrolled_courses( $user_id );

		// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Not using true strict is kept to prevent regression error.
		if (
			is_array( $enrolled_courses )
			&& in_array( $course_id, $enrolled_courses )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get all LearnDash courses
	 *
	 * @return object LearnDash course
	 */
	private static function get_learndash_courses() {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT posts.* FROM $wpdb->posts posts WHERE posts.post_type = %s AND posts.post_status = %s ORDER BY posts.post_title",
				'sfwd-courses',
				'publish'
			)
		);
	}

	/**
	 * Add enrolled course record to a user
	 *
	 * @param int $course_id ID of a course.
	 * @param int $user_id   ID of a user.
	 * @param int $order_id  ID of an order.
	 */
	private static function increment_course_access_counter( $course_id, $user_id, $order_id ) {
		$courses = self::get_courses_access_counter( $user_id );

		if (
			isset( $courses[ $course_id ] )
			&& ! is_array( $courses[ $course_id ] )
		) {
			$courses[ $course_id ] = [];
		}

		if (
			! isset( $courses[ $course_id ] )
			|| (
				isset( $courses[ $course_id ] )
				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Not using true strict is kept to prevent regression error.
				&& array_search( $order_id, $courses[ $course_id ] ) === false
			)
		) {
			// Add order ID to course access counter.
			$courses[ $course_id ][] = $order_id;
		}

		update_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter', $courses );

		return $courses;
	}

	/**
	 * Delete enrolled course record from a user
	 *
	 * @param int $course_id ID of a course.
	 * @param int $user_id   ID of a user.
	 * @param int $order_id  ID of an order.
	 */
	private static function decrement_course_access_counter( $course_id, $user_id, $order_id ) {
		$courses = self::get_courses_access_counter( $user_id );

		if (
			isset( $courses[ $course_id ] )
			&& ! is_array( $courses[ $course_id ] )
		) {
			$courses[ $course_id ] = [];
		}

		if ( isset( $courses[ $course_id ] ) ) {
			// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Not using true strict is kept to prevent regression error.
			$keys = array_keys( $courses[ $course_id ], $order_id );
			if ( is_array( $keys ) ) {
				foreach ( $keys as $key ) {
					unset( $courses[ $course_id ][ $key ] );
				}
			}
		}

		update_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter', $courses );

		return $courses;
	}

	/**
	 * Reset course access counter
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 *
	 * @return void
	 */
	private static function reset_course_access_counter( $course_id, $user_id ) {
		$courses = self::get_courses_access_counter( $user_id );

		if ( isset( $courses[ $course_id ] ) ) {
			unset( $courses[ $course_id ] );
		}

		update_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter', $courses );
	}

	/**
	 * Get user enrolled course access counter
	 *
	 * @since 5.2.0 Changed visibility to public, so the access expiration module can read it too.
	 *
	 * @param int $user_id ID of a user.
	 * @return array        Course access counter array
	 */
	public static function get_courses_access_counter( $user_id ) {
		$courses = get_user_meta( $user_id, '_learndash_woocommerce_enrolled_courses_access_counter', true );

		if ( ! empty( $courses ) ) {
			$courses = maybe_unserialize( $courses );
		} else {
			$courses = [];
		}

		return $courses;
	}

	/**
	 * Get setting if course access should be removed when user completing subscription payment billing cycle
	 *
	 * @param WC_Subscription $subscription WC_Subscription object.
	 *
	 * @return boolean
	 */
	public static function is_course_access_removed_on_subscription_billing_cycle_completion( $subscription ) {
		/**
		 * Filters whether to remove course access when a subscription billing cycle is completed.
		 *
		 * @since 5.2.0
		 *
		 * @param bool            $remove       True to remove|false otherwise.
		 * @param WC_Subscription $subscription WC_Subscription object.
		 *
		 * @return bool True to remove|false otherwise.
		 */
		return apply_filters( 'learndash_woocommerce_remove_course_access_on_subscription_billing_cycle_completion', false, $subscription );
	}

	/**
	 * Output a select input box.
	 *
	 * @param array $field Input field array data.
	 */
	public static function woocommerce_wp_select_multiple( $field ) {
		global $thepostid, $post;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Kept to prevent regression error in case it's an intended behavior to assign value to global variable.
		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

		// Custom attribute handling.
		$custom_attributes = [];

		if (
			! empty( $field['custom_attributes'] )
			&& is_array( $field['custom_attributes'] )
		) {
			foreach ( $field['custom_attributes'] as $attribute => $value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
			}
		}

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
			<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		if (
			! empty( $field['description'] )
			&& false !== $field['desc_tip']
		) {
			echo wc_help_tip( $field['description'] );
		}

		echo '<select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" ' . esc_attr( implode( ' ', $custom_attributes ) ) . ' multiple="multiple">';

		foreach ( $field['options'] as $key => $value ) {
			// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Not using true strict is kept to prevent regression error.
			$selected = in_array( $key, $field['value'] ) ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $value ) . '</option>';
		}

		echo '</select> ';

		if (
			! empty( $field['description'] )
			&& false === $field['desc_tip']
		) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

		echo '</p>';
	}

	/**
	 * Get product count for silent course enrollment
	 *
	 * @since 5.2.0
	 */
	public static function get_products_count_for_silent_course_enrollment() {
		/**
		 * Filters the number of products to process in the silent course enrollment queue.
		 *
		 * @since 5.2.0
		 *
		 * @param int $products_count Number of products to process in the silent course enrollment queue.
		 *
		 * @return int Number of products to process in the silent course enrollment queue.
		 */
		return apply_filters( 'learndash_woocommerce_products_count_for_silent_course_enrollment', 5 );
	}

	/**
	 * Output a custom error log file
	 *
	 * @deprecated 5.2.0 The method is deprecated and will be removed in future versions.
	 *
	 * @param mixed $message Message.
	 */
	public static function log( $message = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- The $message parameter is kept for backward compatibility.
		_deprecated_file( __METHOD__, '2.0.0' );
	}
}
