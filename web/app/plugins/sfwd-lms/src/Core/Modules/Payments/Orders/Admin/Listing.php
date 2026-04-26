<?php
/**
 * LearnDash Orders Admin Listing class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore stripe_sesion_id
 */

namespace LearnDash\Core\Modules\Payments\Orders\Admin;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use Learndash_Payment_Gateway;
use Learndash_Paypal_IPN_Gateway;
use Learndash_Stripe_Gateway;
use stdClass;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use WP_Query;

/**
 * LearnDash Orders Admin Listing class.
 *
 * @since 4.19.0
 */
class Listing {
	/**
	 * The Post Type for the list screen.
	 *
	 * @since 4.19.0
	 *
	 * @var string Post type slug.
	 */
	private string $post_type;

	/**
	 * Constructor for the Orders Admin Listing class.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->post_type = learndash_get_post_type_slug( LDLMS_Post_Types::TRANSACTION );
	}

	/**
	 * Registers scripts on the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		if ( ! $this->is_listing_screen() ) {
			return;
		}

		Asset::add( 'learndash-order-listing', 'listing.js' )
			->set_dependencies( 'learndash-breakpoints', 'jquery' )
			->add_to_group( 'learndash-module-payments' )
			->set_path( 'src/assets/dist/js/admin/modules/payments/orders', false )
			->register();
	}

	/**
	 * Manages the columns for the Orders list screen.
	 *
	 * It only removes unwanted columns. The custom columns are managed by the legacy `Learndash_Admin_Transactions_Listing` class.
	 *
	 * @since 4.19.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string> Returned columns.
	 */
	public function manage_posts_columns( $columns ): array {
		unset( $columns['title'] );
		unset( $columns['date'] );

		return $columns;
	}

	/**
	 * Manages the sortable columns for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param array<string, string|array<string|bool>> $columns Existing sortable columns.
	 *
	 * @return array<string, string|array<string|bool>> Returned sortable columns.
	 */
	public function manage_sortable_columns( $columns ): array {
		$columns['id']       = 'ID';
		$columns['item']     = 'item';
		$columns['date']     = [
			'date', // Internal name.
			true, // Descending initial sorting.
			__( 'Date', 'learndash' ), // Translatable abbr attribute.
			__( 'Table ordered by date.', 'learndash' ), // Translatable string for the current sorting order.
			'desc', // Default order string.
		];
		$columns['customer'] = 'customer';
		$columns['gateway']  = 'gateway';

		return $columns;
	}

	/**
	 * Adds a filter for the test mode on the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string $post_type The current post type.
	 *
	 * @return void
	 */
	public function restrict_manage_posts( $post_type ): void {
		if ( $post_type !== $this->post_type ) {
			return;
		}

		// Add the test mode filter. Cast to string because esc_attr expects a string.

		$is_test_mode = Cast::to_string( SuperGlobals::get_var( 'is_test_mode', false ) );

		?>
		<input type="hidden" name="is_test_mode" value="<?php echo esc_attr( $is_test_mode ); ?>">
		<?php
	}

	/**
	 * Adds the test mode parameter to the filters reset button on the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string $redirect_url The current redirect URL.
	 * @param string $post_type    The current post type.
	 *
	 * @return string
	 */
	public function add_test_mode_parameter_on_reset_button( string $redirect_url, string $post_type ): string {
		if ( $post_type !== $this->post_type ) {
			return $redirect_url;
		}

		return add_query_arg(
			'is_test_mode',
			Cast::to_bool(
				SuperGlobals::get_var( 'is_test_mode', false )
			),
			$redirect_url
		);
	}

	/**
	 * Adds the test mode parameter to the views on the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string[] $views The current views.
	 *
	 * @return string[]
	 */
	public function add_test_mode_parameter_on_views( $views ): array {
		// Loop through the views and add the test mode parameter.

		foreach ( $views as $key => $view ) {
			// Extract the URL from the view link.
			// Example: <a href="edit.php?post_type=sfwd-transactions" class="current" aria-current="page">All <span class="count">(288)</span></a>.
			$view_url = Cast::to_string(
				preg_replace(
					'/.*href="([^"]+)".*/',
					'$1',
					$view
				)
			);

			// Add the test mode parameter to the URL.
			$view_url = add_query_arg(
				'is_test_mode',
				Cast::to_bool(
					SuperGlobals::get_var( 'is_test_mode', false )
				),
				html_entity_decode( $view_url )
			);

			// Add the test mode parameter to the URL.
			$views[ $key ] = Cast::to_string(
				preg_replace(
					'/href="([^"]+)"/',
					'href="' . esc_url( $view_url ) . '"',
					$view
				)
			);
		}

		return $views;
	}

	/**
	 * Filters the main query for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param WP_Query $query The current query object.
	 *
	 * @return void
	 */
	public function filter_posts( $query ): void {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return;
		}

		// Only list parent posts.
		$query->set( 'post_parent', 0 );
	}

	/**
	 * Filters the post count for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param stdClass $counts The post counts.
	 * @param string   $type   The post type.
	 *
	 * @return stdClass
	 */
	public function filter_wp_count_posts( $counts, $type ) {
		if ( $type !== $this->post_type ) {
			return $counts;
		}

		$is_test_mode = Cast::to_bool( SuperGlobals::get_var( 'is_test_mode', false ) );
		$cache_key    = $is_test_mode ? 'ld_orders_counts_test_mode' : 'ld_orders_counts_live_mode';

		// Get the new counts.

		$order_counts = wp_cache_get( $cache_key, 'counts' );

		if (
			false === $order_counts
			|| ! is_array( $order_counts )
		) {
			global $wpdb;

			// Prepare the query to get the counts for the Orders list screen.
			$query = "
				SELECT post_status, COUNT(*) AS num_posts FROM {$wpdb->posts}
				{$this->get_join_clauses_for_test_mode()}
				{$this->get_join_clauses_for_payment_processor()}
				WHERE post_type = {$wpdb->prepare( '%s', $this->post_type )}
				AND {$wpdb->posts}.post_parent = 0
				{$this->get_where_clauses_for_test_mode()}
				{$this->get_where_clauses_for_payment_processor()}
				GROUP BY post_status
				";

			$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- All values are prepared.

			// Build the new counts array.

			$order_counts = [];

			foreach ( $results as $row ) {
				$order_counts[ $row['post_status'] ] = $row['num_posts'];
			}

			// Save the cached counts.

			wp_cache_set( $cache_key, $order_counts, 'counts' );
		}

		// Replace the counts with the new ones.

		foreach ( (array) $counts as $status => $count ) {
			$counts->$status = $order_counts[ $status ] ?? 0;
		}

		return $counts;
	}

	/**
	 * Filters the search query for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string   $search The search term.
	 * @param WP_Query $query  The current query object.
	 *
	 * @return string
	 */
	public function filter_posts_search( $search, $query ): string {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return Cast::to_string( $search );
		}

		global $wpdb;

		$search = Cast::to_string( SuperGlobals::get_var( 's' ) );

		if ( empty( $search ) ) {
			return $search;
		}

		$search = $wpdb->prepare(
			" AND (
				{$wpdb->posts}.ID = %d
				OR ld_order_product.post_title LIKE %s
				OR ld_order_user.user_email LIKE %s
				OR ld_order_user.display_name LIKE %s
				OR ld_order_child_meta_gateway.meta_value LIKE %s
			)",
			$search, // Order ID.
			"%{$search}%", // Product title.
			"%{$search}%", // User email.
			"%{$search}%", // Display name.
			"%{$search}%" // Payment processor.
		);

		return $search;
	}

	/**
	 * Filters the select clause for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string   $select The SELECT clause.
	 * @param WP_Query $query  The current query object.
	 */
	public function filter_posts_fields( $select, $query ): string {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return Cast::to_string( $select );
		}

		$order_by = SuperGlobals::get_var( 'orderby' );

		switch ( $order_by ) {
			case 'item':
				$select .= ', ld_order_product.post_title';
				break;
			case 'customer':
				$select .= ', ld_order_user.user_email';
				break;
			case 'gateway':
				$select .= ', ld_order_child_meta_gateway.meta_value';
				break;
			default:
				break;
		}

		return $select;
	}


	/**
	 * Filters the JOIN clause for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string   $join  The JOIN clause.
	 * @param WP_Query $query The current query object.
	 *
	 * @return string Modified JOIN clause.
	 */
	public function filter_posts_join( $join, $query ): string {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return Cast::to_string( $join );
		}

		$join .= $this->get_join_clauses_for_test_mode();

		// Add JOIN clauses for filters.
		$join .= $this->get_join_clauses_for_filters();

		// Add JOIN clauses for ORDER BY and global search.
		$join .= $this->get_join_clauses_for_order_by_and_post_search();

		return $join;
	}

	/**
	 * Filters the WHERE clause for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string   $where The WHERE clause.
	 * @param WP_Query $query The current query object.
	 *
	 * @return string Modified WHERE clause.
	 */
	public function filter_posts_where( $where, $query ): string {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return Cast::to_string( $where );
		}

		$where .= $this->get_where_clauses_for_test_mode();

		// Add the payment processor filter.
		$where .= $this->get_where_clauses_for_payment_processor();

		return $where;
	}

	/**
	 * Filters the ORDER BY clause for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param string   $orderby The ORDER BY clause.
	 * @param WP_Query $query   The current query object.
	 *
	 * @return string Modified ORDER BY clause.
	 */
	public function filter_posts_orderby( $orderby, $query ): string {
		if (
			! is_admin()
			|| ! $query->is_main_query()
			|| $query->get( 'post_type' ) !== $this->post_type
		) {
			return Cast::to_string( $orderby );
		}

		global $wpdb;

		$orderby = Cast::to_string( SuperGlobals::get_var( 'orderby' ) );

		switch ( $orderby ) {
			case 'item':
				$orderby = 'ld_order_product.post_title';
				break;
			case 'date':
				$orderby = "{$wpdb->posts}.post_date_gmt";
				break;
			case 'customer':
				$orderby = 'ld_order_user.user_email';
				break;
			case 'gateway':
				$orderby = 'ld_order_child_meta_gateway.meta_value';
				break;
			default:
				// Default to ID.
				$orderby = "{$wpdb->posts}.ID";
				break;
		}

		// Add and validate the order direction.

		$order = Cast::to_string(
			SuperGlobals::get_var( 'order' )
		);

		if ( ! in_array(
			strtoupper( $order ),
			[ 'ASC', 'DESC' ],
			true
		) ) {
			$order = 'DESC';
		}

		return "$orderby $order";
	}

	/**
	 * Registers admin notices for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function register_notices(): void {
		AdminNotices::show(
			'learndash_orders_exclusive',
			sprintf(
			// Translators: %1$s is orders label, %2$s is the opening anchor tag with URL, %3$s is the closing anchor tag.
				__( 'Only %1$s generated through the LearnDash Registration page will display on this page. %2$sLearn More%3$s', 'learndash' ),
				learndash_get_custom_label_lower( 'orders' ),
				'<a href="https://go.learndash.com/orderslistexclusions" target="_blank" rel="noopener noreferrer">',
				'</a>'
			)
		)
		->on( 'edit.php?post_type=' . $this->post_type )
		->when(
			function () {
				return (
					is_plugin_active( 'woocommerce/woocommerce.php' )
					|| is_plugin_active( 'surecart/surecart.php' )
					|| is_plugin_active( 'learndash-samcart/learndash-samcart.php' )
					|| is_plugin_active( 'learndash-thrivecart/learndash-thrivecart.php' )
				);
			}
		)
		->dismissible()
		->asWarning()
		->withWrapper()
		->autoParagraph();
	}

	/**
	 * Filters the months dropdown for the Orders list screen.
	 *
	 * @since 4.19.0
	 *
	 * @param bool   $disable   Whether to disable the months dropdown.
	 * @param string $post_type The post type.
	 *
	 * @return bool
	 */
	public function disable_months_dropdown( $disable, string $post_type ) {
		if ( $post_type !== $this->post_type ) {
			return $disable;
		}

		return true;
	}

	/**
	 * Returns if we're currently viewing the listing screen for an Order.
	 *
	 * @since 4.19.0
	 *
	 * @return bool
	 */
	private function is_listing_screen(): bool {
		$current_screen = get_current_screen();

		return $current_screen
		&& $current_screen->post_type === $this->post_type
		&& $current_screen->base === 'edit';
	}

	/**
	 * Returns the JOIN clauses for the test mode filter.
	 *
	 * @since 4.19.0
	 *
	 * @return string JOIN clauses for the test mode filter.
	 */
	private function get_join_clauses_for_test_mode(): string {
		global $wpdb;

		$is_test_mode = Cast::to_bool( SuperGlobals::get_var( 'is_test_mode', false ) );

		// If test mode is false, we need to use LEFT JOINs to include legacy orders (before 4.19.0).
		$join_type = $is_test_mode ? 'INNER' : 'LEFT';

		/**
		 * This first JOIN clause is used to get the first child post ID for each parent post.
		 * It's useful because almost all relevant meta data is stored in the child post.
		 */
		$join = "
				{$join_type} JOIN (
					SELECT post_parent, MIN(id) AS ld_order_first_child_id
					FROM   {$wpdb->posts}
					WHERE  post_parent != 0
					GROUP  BY post_parent
				) ld_order_first_child ON {$wpdb->posts}.ID = ld_order_first_child.post_parent
				{$join_type} JOIN {$wpdb->postmeta} ld_order_child_meta_test_mode
				ON ld_order_child_meta_test_mode.post_id = ld_order_first_child.ld_order_first_child_id
				AND ld_order_child_meta_test_mode.meta_key = 'is_test_mode'
				";

		if ( $is_test_mode ) {
			$join .= "AND ld_order_child_meta_test_mode.meta_value = '1'";
		}

		return $join;
	}

	/**
	 * Returns the JOIN clauses for the ORDER BY statement and the post search.
	 *
	 * @since 4.19.0
	 *
	 * @return string JOIN clauses if needed or an empty string.
	 */
	private function get_join_clauses_for_order_by_and_post_search(): string {
		global $wpdb;

		$join = '';

		$order_by = SuperGlobals::get_var( 'orderby' );

		// If search term is not empty, we need to join all tables to allow searching in all columns.
		$search_term = Cast::to_string( SuperGlobals::get_var( 's' ) );

		// Case item.

		if (
			! empty( $search_term )
			|| $order_by === 'item'
		) {
			$join .= "
					LEFT JOIN {$wpdb->postmeta} ld_order_child_meta_product_id
					ON ld_order_child_meta_product_id.post_id = ld_order_first_child.ld_order_first_child_id
					AND ld_order_child_meta_product_id.meta_key = 'post_id'
					LEFT JOIN {$wpdb->posts} ld_order_product
					ON ld_order_product.ID = ld_order_child_meta_product_id.meta_value
					";
		}

		// Case customer.

		if (
			! empty( $search_term )
			|| $order_by === 'customer'
		) {
			$join .= "
					LEFT JOIN {$wpdb->users} ld_order_user ON ld_order_user.ID = {$wpdb->posts}.post_author
					";
		}

		return $join;
	}

	/**
	 * Returns the JOIN clauses for filters.
	 *
	 * @since 4.19.0
	 *
	 * @return string JOIN clauses if needed or an empty string.
	 */
	private function get_join_clauses_for_filters(): string {
		global $wpdb;

		// We always need to include the filter for the payment processors.
		$join_clauses = $this->get_join_clauses_for_payment_processor();

		// Add more JOIN clauses for additional filters.

		// Course ID.

		$course_id = Cast::to_int( SuperGlobals::get_var( 'course_id', 0 ) );

		if ( $course_id > 0 ) {
			$join_clauses .= $wpdb->prepare(
				"
				INNER JOIN {$wpdb->postmeta} ld_order_child_meta_course_id
				ON ld_order_child_meta_course_id.post_id = ld_order_first_child.ld_order_first_child_id
				AND (
					ld_order_child_meta_course_id.meta_key = 'post_id'
					OR ld_order_child_meta_course_id.meta_key = 'course_id'
				)
				AND ld_order_child_meta_course_id.meta_value = %d
				",
				$course_id
			);
		}

		// Group ID.

		$group_id = Cast::to_int( SuperGlobals::get_var( 'group_id', 0 ) );

		if ( $group_id > 0 ) {
			$join_clauses .= $wpdb->prepare(
				"
				INNER JOIN {$wpdb->postmeta} ld_order_child_meta_group_id
				ON ld_order_child_meta_group_id.post_id = ld_order_first_child.ld_order_first_child_id
				AND (
					ld_order_child_meta_group_id.meta_key = 'post_id'
					or ld_order_child_meta_group_id.meta_key = 'group_id'
				)
				AND ld_order_child_meta_group_id.meta_value = %d
				",
				$group_id
			);
		}

		return $join_clauses;
	}

	/**
	 * Returns the JOIN clauses for the payment processor filter.
	 *
	 * @since 4.19.0
	 *
	 * @return string JOIN clauses for the payment processor filter.
	 */
	private function get_join_clauses_for_payment_processor(): string {
		global $wpdb;

		$join = "
			LEFT JOIN {$wpdb->postmeta} ld_order_child_meta_gateway
			ON ld_order_child_meta_gateway.post_id = ld_order_first_child.ld_order_first_child_id
			AND ld_order_child_meta_gateway.meta_key = 'ld_payment_processor'
			/* Be sure to include the zero price orders. */
            LEFT JOIN {$wpdb->postmeta} ld_order_zero_meta_gateway
            ON ld_order_zero_meta_gateway.post_id = ld_order_first_child.ld_order_first_child_id
            AND ld_order_zero_meta_gateway.meta_key = 'is_zero_price'
			";

		// We grab a specific gateway if the user wants it. Otherwise, we grab all available gateways.

		$gateways = SuperGlobals::get_var( 'payment_processors' );
		$gateways = ! empty( $gateways )
			? [ $gateways ]
			: array_keys( Learndash_Payment_Gateway::get_select_list() );

		// Support legacy meta data for specific gateways.

		// PayPal.

		if ( in_array(
			Learndash_Paypal_IPN_Gateway::get_name(),
			$gateways,
			true
		) ) {
			// Join the legacy meta data for PayPal.

			$join .= "
			LEFT JOIN {$wpdb->postmeta} ld_order_meta_gateway_legacy_paypal
			ON ld_order_meta_gateway_legacy_paypal.post_id = {$wpdb->posts}.ID
			AND ld_order_meta_gateway_legacy_paypal.meta_key = 'ipn_track_id'
			";
		}

		// Stripe.

		if ( in_array(
			Learndash_Stripe_Gateway::get_name(),
			$gateways,
			true
		) ) {
			/**
			 * Join the legacy meta data for Stripe.
			 * The meta key can be 'stripe_sesion_id' because of a typo in the past.
			 * We can't determine if the meta without the typo exists, so we need to check both.
			 */
			$join .= "
			LEFT JOIN {$wpdb->postmeta} ld_order_meta_gateway_legacy_stripe
			ON ld_order_meta_gateway_legacy_stripe.post_id = {$wpdb->posts}.ID
			AND (
				ld_order_meta_gateway_legacy_stripe.meta_key = 'stripe_sesion_id'
				OR ld_order_meta_gateway_legacy_stripe.meta_key = 'stripe_session_id'
			)
			";
		}

		return $join;
	}

	/**
	 * Returns the WHERE clauses for the payment processor filter.
	 *
	 * @since 4.19.0
	 *
	 * @return string WHERE clauses for the payment processor filter.
	 */
	private function get_where_clauses_for_payment_processor(): string {
		global $wpdb;

		$where = '';
		// Be sure to include the zero price orders.
		$inner_ors_where = ' OR ld_order_zero_meta_gateway.meta_value = "1"';

		// We grab a specific gateway if the user wants it. Otherwise, we grab all available gateways.

		$gateways = SuperGlobals::get_var( 'payment_processors' );
		$gateways = ! empty( $gateways )
			? [ $gateways ]
			: array_keys( Learndash_Payment_Gateway::get_select_list() );

		// Support legacy meta data for specific gateways.

		// PayPal.

		if ( in_array(
			Learndash_Paypal_IPN_Gateway::get_name(),
			$gateways,
			true
		) ) {
			// Join the legacy meta key value for PayPal.
			$gateways[] = 'paypal';

			$inner_ors_where .= ' OR ld_order_meta_gateway_legacy_paypal.meta_value IS NOT NULL';
		}

		// Stripe.

		if ( in_array(
			Learndash_Stripe_Gateway::get_name(),
			$gateways,
			true
		) ) {
			$inner_ors_where .= ' OR ld_order_meta_gateway_legacy_stripe.meta_value IS NOT NULL';
		}

		$gateways_in = ! empty( $gateways )
			? implode(
				',',
				array_map(
					function ( $gateway ) use ( $wpdb ): string {
							return $wpdb->prepare( '%s', $gateway );
					},
					$gateways
				)
			)
			: '-1'; // Hide all if no gateways are available.

		// Add the default WHERE clause for the payment processor filter.

		$where .= "
			AND (
				ld_order_child_meta_gateway.meta_value IN ({$gateways_in})
				{$inner_ors_where}
			)";

		return $where;
	}

	/**
	 * Returns the WHERE clauses for the test mode filter.
	 *
	 * @since 4.19.0
	 *
	 * @return string WHERE clauses for the test mode filter.
	 */
	private function get_where_clauses_for_test_mode(): string {
		$is_test_mode = Cast::to_bool( SuperGlobals::get_var( 'is_test_mode', false ) );

		return ! $is_test_mode
			? "AND (ld_order_child_meta_test_mode.meta_value is null or ld_order_child_meta_test_mode.meta_value = '0' or ld_order_child_meta_test_mode.meta_value = '') "
			: '';
	}
}
