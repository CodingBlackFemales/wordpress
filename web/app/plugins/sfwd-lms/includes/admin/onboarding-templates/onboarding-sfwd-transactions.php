<?php
/**
 * Orders onboarding template.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var string $screen_post_type Screen post type.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Views\Onboarding;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Template\Template;
use WP_Query;

defined( 'ABSPATH' ) || exit;

// TODO: Implement a better way to query existing Live and Test Transactions.
$learndash_order_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );
$learndash_customer_orders = new WP_Query(
	[
		'post_type'           => $learndash_order_post_type,
		'post_status'         => 'publish',
		'post_parent__not_in' => [ 0 ],
		'posts_per_page'      => 1,
		'fields'              => 'ids',
		'meta_query'          => [
			'relation' => 'OR',
			[
				'key'     => Transaction::$meta_key_is_test_mode,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'   => Transaction::$meta_key_is_test_mode,
				'value' => '',
			],
			[
				'key'   => Transaction::$meta_key_is_test_mode,
				'value' => '0',
			],
		],
	]
);
$learndash_test_orders     = new WP_Query(
	[
		'post_type'           => $learndash_order_post_type,
		'post_status'         => 'publish',
		'post_parent__not_in' => [ 0 ],
		'posts_per_page'      => 1,
		'fields'              => 'ids',
		'meta_query'          => [
			'relation' => 'AND',
			[
				'key'   => Transaction::$meta_key_is_test_mode,
				'value' => '1',
			],
		],
	]
);

if (
	! $learndash_customer_orders->have_posts()
	&& ! $learndash_test_orders->have_posts()
) {
	Template::show_admin_template( 'modules/payments/orders/list/onboarding' );
} elseif ( ! $learndash_customer_orders->have_posts() ) {
	Template::show_admin_template( 'modules/payments/orders/list/onboarding/customer-orders' );
} elseif ( ! $learndash_test_orders->have_posts() ) {
	Template::show_admin_template( 'modules/payments/orders/list/onboarding/test-orders' );
}
