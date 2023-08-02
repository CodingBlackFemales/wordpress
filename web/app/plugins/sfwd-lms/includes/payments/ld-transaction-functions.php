<?php
/**
 * Functions related to transaction post type
 *
 * @since 4.2.0
 *
 * @package LearnDash
 */

use LearnDash\Core\Models\Transaction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates a transaction.
 *
 * @since 4.2.0
 * @since 4.5.0   Added optional $parent_transaction_id parameter.
 *
 * @param array   $meta_fields           Meta fields.
 * @param WP_Post $post                  Post.
 * @param WP_User $user                  User.
 * @param int     $parent_transaction_id Parent transaction ID. Default 0. If not set, a new parent transaction will be created.
 *
 * @return int Transaction ID or 0.
 */
function learndash_transaction_create( array $meta_fields, WP_Post $post, WP_User $user, int $parent_transaction_id = 0 ): int {
	$common_meta_fields = array(
		'learndash_version' => LEARNDASH_VERSION,
		'user'              => array(
			'display_name' => $user->display_name,
			'user_email'   => $user->user_email,
		),
	);

	// Create a parent transaction if not specified.

	if ( $parent_transaction_id <= 0 ) {
		/**
		 * Parent transaction ID.
		 *
		 * @var int|WP_Error $parent_transaction_id Parent transaction ID.
		 */
		$parent_transaction_id = wp_insert_post(
			array(
				'post_title'  => __( 'Parent transaction', 'learndash' ),
				'post_type'   => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ),
				'post_status' => 'publish',
				'post_author' => $user->ID,
			)
		);

		if ( is_wp_error( $parent_transaction_id ) || 0 === $parent_transaction_id ) {
			return 0;
		}

		wp_update_post(
			array(
				'ID'         => $parent_transaction_id,
				'post_title' => sprintf(
					// translators: placeholder: transaction ID.
					__( 'Order #%d', 'learndash' ),
					$parent_transaction_id
				),
			)
		);

		update_post_meta( $parent_transaction_id, Transaction::$meta_key_is_parent, true );
		foreach ( $common_meta_fields as $key => $value ) {
			update_post_meta( $parent_transaction_id, $key, $value );
		}
	}

	/**
	 * Filters transaction post title.
	 *
	 * @since 4.5.0
	 *
	 * @var WP_Post $post WP_Post      Object for the post related to the transaction.
	 * @var WP_User $user WP_User      Object for user related to the transaction.
	 * @var array<mixed>  $meta_fields Meta fields in key value pair.
	 *
	 * @return string Modified post title.
	 */
	$transaction_title = apply_filters( 'learndash_transaction_post_title', $post->post_title, $post, $user, $meta_fields );

	// Create a usual transaction.

	/**
	 * Transaction ID.
	 *
	 * @var int|WP_Error $transaction_id Transaction ID.
	 */
	$transaction_id = wp_insert_post(
		array(
			'post_title'  => $transaction_title,
			'post_type'   => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION ),
			'post_status' => 'publish',
			'post_author' => $user->ID,
			'post_parent' => $parent_transaction_id,
		)
	);

	if ( 0 === $transaction_id || is_wp_error( $transaction_id ) ) {
		return 0;
	}

	// Update usual transaction's meta.

	$meta_fields['post_id'] = $post->ID; // Duplicate for search.
	$meta_fields['post']    = array(
		'post_title' => $post->post_title,
		'post_type'  => $post->post_type,
	);

	$meta_fields = array_merge( $meta_fields, $common_meta_fields );

	foreach ( $meta_fields as $key => $value ) {
		update_post_meta( $transaction_id, $key, $value );
	}

	/**
	 * Fires after the payment transaction is created with all meta fields.
	 *
	 * @since 4.1.0
	 *
	 * @param int $transaction_id Transaction ID.
	 */
	do_action( 'learndash_transaction_created', $transaction_id );

	return $transaction_id;
}

// Saves the current LD version to the transaction meta.
add_action(
	'learndash_transaction_created',
	function ( int $transaction_id ): void {
		update_post_meta( $transaction_id, 'learndash_version', LEARNDASH_VERSION );
	}
);
