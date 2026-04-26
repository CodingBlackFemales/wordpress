<?php
/**
 * LearnDash Order Deletion class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore untrash .
 */

namespace LearnDash\Core\Modules\Payments\Orders\Admin\Actions;

use LearnDash\Core\Models\Transaction;
use WP_Post;
use WP_Posts_List_Table;

/**
 * LearnDash Order Deletion class.
 *
 * @since 4.19.0
 */
class Delete {
	/**
	 * Sends child Orders to the trash when the parent Order is sent to the trash.
	 *
	 * @since 4.19.0
	 *
	 * @param string  $new_status New Order Post Status.
	 * @param string  $old_status Old Order Post Status.
	 * @param WP_Post $post       Order Post object.
	 *
	 * @return void
	 */
	public function send_to_trash( $new_status, $old_status, $post ): void {
		if (
			'trash' !== $new_status
			|| ! in_array(
				$post->post_type,
				Transaction::get_allowed_post_types(),
				true
			)
		) {
			return;
		}

		$this->process_child_transactions(
			$post->ID,
			function ( Transaction $child_transaction ) {
				/**
				 * This gets called directly when you Trash a post from WP_List_Table.
				 * If you try to call wp_delete_post() with the second argument set to false,
				 * unless it is a Page or Post it will be permanently deleted anyway.
				 */
				wp_trash_post(
					$child_transaction->get_id()
				);
			}
		);
	}

	/**
	 * Permanently deletes child Orders when the parent Order is permanently deleted.
	 *
	 * @since 4.19.0
	 *
	 * @param int $post_id Order Post ID.
	 *
	 * @return void
	 */
	public function permanently_delete( $post_id ): void {
		if (
			! in_array(
				get_post_type( $post_id ),
				Transaction::get_allowed_post_types(),
				true
			)
		) {
			return;
		}

		$wp_list_table = false;
		if (
			is_admin()
			&& class_exists( 'WP_Posts_List_Table' )
		) {
			// The necessary code for WP List Tables only exists in the backend.
			$wp_list_table = new WP_Posts_List_Table();
		}

		/**
		 * If "Empty Trash" was clicked, WordPress handles deleting Children in the Trash for us.
		 * See /wp-admin/edit.php .
		 */
		if (
			$wp_list_table instanceof WP_Posts_List_Table
			&& $wp_list_table->current_action() === 'delete_all'
		) {
			return;
		}

		$this->process_child_transactions(
			$post_id,
			function ( Transaction $child_transaction ) {
				wp_delete_post(
					$child_transaction->get_id(),
					true
				);
			}
		);
	}

	/**
	 * Restores child Orders from the trash when the parent Order is restored from the trash.
	 *
	 * @since 4.19.0
	 *
	 * @param string  $new_status New Order Post Status.
	 * @param string  $old_status Old Order Post Status.
	 * @param WP_Post $post       Order Post object.
	 *
	 * @return void
	 */
	public function restore_from_trash( $new_status, $old_status, $post ): void {
		if (
			'trash' !== $old_status
			|| ! in_array(
				$post->post_type,
				Transaction::get_allowed_post_types(),
				true
			)
		) {
			return;
		}

		// Everything but Attachments are made Drafts, which we do not want.
		wp_update_post(
			[
				'ID'          => $post->ID,
				'post_status' => 'publish',
			]
		);

		$this->process_child_transactions(
			$post->ID,
			function ( Transaction $child_transaction ) {
				wp_untrash_post(
					$child_transaction->get_id()
				);

				wp_update_post(
					[
						'ID'          => $child_transaction->get_id(),
						'post_status' => 'publish',
					]
				);
			}
		);
	}

	/**
	 * Applies a callback to every child Transaction of a given parent Transaction by Post ID.
	 *
	 * @since 4.19.0
	 *
	 * @param int      $post_id  Order Post ID.
	 * @param callable $callback Callback to apply to each Child Transaction.
	 *
	 * @return void
	 */
	private function process_child_transactions( int $post_id, callable $callback ): void {
		$transaction = Transaction::find( $post_id );

		if ( ! $transaction ) {
			return;
		}

		foreach ( $transaction->get_children() as $child_transaction ) {
			$callback( $child_transaction );
		}
	}
}
