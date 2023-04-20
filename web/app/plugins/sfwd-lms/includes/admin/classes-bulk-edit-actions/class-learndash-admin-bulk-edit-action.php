<?php
/**
 * LearnDash Bulk Edit Action abstract class.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Bulk_Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Bulk_Edit_Action' ) ) {
	/**
	 * Learndash Bulk Edit Action abstract class.
	 *
	 * @since 4.2.0
	 */
	abstract class Learndash_Admin_Bulk_Edit_Action {
		/**
		 * Filters.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		protected $filters = array();

		/**
		 * Fields.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		protected $fields = array();

		/**
		 * The bulk edit scheduler instance.
		 *
		 * @since 4.2.0
		 *
		 * @var Learndash_Admin_Action_Scheduler
		 */
		private $bulk_edit_scheduler;

		/**
		 * Returns a tab name.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		abstract public function get_tab_name(): string;

		/**
		 * Returns a post type.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		abstract public function get_post_type(): string;

		/**
		 * Inits filters.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		abstract protected function init_filters(): void;

		/**
		 * Inits fields.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		abstract protected function init_fields(): void;

		/**
		 * Update the post field.
		 *
		 * @since 4.2.0
		 *
		 * @param int    $post_id     Post ID.
		 * @param string $field_name  Field name.
		 * @param string $field_value Field value.
		 *
		 * @return void
		 */
		abstract protected function update_post_field( int $post_id, string $field_name, string $field_value): void;

		/**
		 * Inits.
		 *
		 * @since 4.2.0
		 *
		 * @param Learndash_Admin_Action_Scheduler $bulk_edit_scheduler The bulk edit scheduler instance.
		 *
		 * @return void
		 */
		public function init( Learndash_Admin_Action_Scheduler $bulk_edit_scheduler ): void {
			$this->bulk_edit_scheduler = $bulk_edit_scheduler;

			$this->init_fields();
			$this->init_filters();

			if ( is_admin() && learndash_is_admin_user() ) {
				add_action(
					'wp_ajax_' . $this->get_ajax_action_get_affected_posts_number(),
					array( $this, 'process_ajax_action_get_affected_posts_number' )
				);

				add_action(
					'wp_ajax_' . $this->get_ajax_action_update_posts(),
					array( $this, 'process_ajax_action_update_posts' )
				);
			}

			// register task callback.
			$this->bulk_edit_scheduler->register_callback( $this->get_bulk_edit_task_name(), array( $this, 'update_posts_task' ), 10, 2 );
		}

		/**
		 * Returns fields.
		 *
		 * @since 4.2.0
		 *
		 * @return Learndash_Admin_Bulk_Edit_Field[]
		 */
		public function get_fields(): array {
			/**
			 * Filters bulk edit fields.
			 *
			 * @since 4.2.0
			 *
			 * @param array  $fields Bulk edit fields.
			 * @param object $class  Instance of the called class.
			 */
			$fields = apply_filters( 'learndash_bulk_edit_fields', $this->fields, $this );

			$mapped_fields = array();

			foreach ( $fields as $field ) {
				if ( ! $field instanceof Learndash_Admin_Bulk_Edit_Field ) {
					continue;
				}

				$mapped_fields[ $field->get_name() ] = $field;
			}

			return $mapped_fields;
		}

		/**
		 * Returns supported filters.
		 *
		 * @since 4.2.0
		 *
		 * @return Learndash_Admin_Filter[]
		 */
		public function get_filters(): array {
			/**
			 * Filters bulk edit filters.
			 *
			 * @since 4.2.0
			 *
			 * @param array  $filters Bulk edit filters.
			 * @param object $class   Instance of the called class.
			 */
			$filters = apply_filters( 'learndash_bulk_edit_filters', $this->filters, $this );

			$mapped_filters = array();

			foreach ( $filters as $filter ) {
				if ( ! $filter instanceof Learndash_Admin_Filter ) {
					continue;
				}

				$mapped_filters[ $filter->get_parameter_name() ] = $filter;
			}

			return $mapped_filters;
		}

		/**
		 * Returns the action name for affected posts number endpoint.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_ajax_action_get_affected_posts_number(): string {
			return $this->get_ajax_action_prefix() . '_get_affected_number';
		}

		/**
		 * Returns nonce for affected posts number endpoint.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_ajax_action_get_affected_posts_number_nonce(): string {
			return wp_create_nonce( $this->get_ajax_action_get_affected_posts_number() );
		}

		/**
		 * Processes the affected posts number action.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function process_ajax_action_get_affected_posts_number(): void {
			// validate nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->get_ajax_action_get_affected_posts_number() ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid request.', 'learndash' ),
					)
				);
			}

			// load enabled filters values.
			$enabled_filters = $this->get_enabled_items_from_request(
				isset( $_POST['filters'] ) ? wp_unslash( $_POST['filters'] ) : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitization will be done by the method.
			);

			// get the number of affected posts.
			$affected_posts_number = $this->get_affected_posts_number( $enabled_filters );
			if ( null === $affected_posts_number ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Failed to get affected posts number.', 'learndash' ),
					)
				);
			}

			wp_send_json_success(
				array(
					'posts_number' => $affected_posts_number,
				)
			);
		}

		/**
		 * Get the enabled items from the request.
		 *
		 * @since 4.2.0
		 *
		 * @param array $request_data The request data array with the items.
		 *
		 * @return array The enabled items from the request.
		 */
		private function get_enabled_items_from_request( array $request_data ): array {
			$enabled_items = array();

			foreach ( $request_data  as $item_data_key ) {
				if ( isset( $item_data_key['enabled'] ) && 'true' === sanitize_text_field( $item_data_key['enabled'] ) && isset( $item_data_key['name'] ) && isset( $item_data_key['value'] ) ) {
					$enabled_items[ sanitize_text_field( $item_data_key['name'] ) ] = is_array( $item_data_key['value'] )
						? array_map( 'sanitize_text_field', $item_data_key['value'] )
						: sanitize_text_field( $item_data_key['value'] );
				}
			}

			return $enabled_items;
		}

		/**
		 * Returns the number of affected posts after applying filters.
		 *
		 * @since 4.2.0
		 *
		 * @param array $enabled_filters Array of enabled filters as [filter_name => filter_value].
		 *
		 * @return string|null The number of affected posts or null.
		 */
		protected function get_affected_posts_number( array $enabled_filters ):?string {
			global $wpdb;

			$sql = "SELECT COUNT(*) FROM {$wpdb->posts}" . $this->get_sql_from_filters( $enabled_filters );

			return $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Returns the ids of affected posts after applying filters.
		 *
		 * @since 4.2.0
		 *
		 * @param array $enabled_filters Array of enabled filters as [filter_name => filter_value].
		 *
		 * @return array The array of affected posts ids.
		 */
		protected function get_affected_posts_ids( array $enabled_filters ):array {
			global $wpdb;

			$sql = "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}" . $this->get_sql_from_filters( $enabled_filters );

			return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Get the SQL clause for the given filter.
		 *
		 * @since 4.2.0
		 *
		 * @param array $enabled_filters The enabled filters.
		 *
		 * @return string The SQL query.
		 */
		protected function get_sql_from_filters( array $enabled_filters ): string {
			global $wpdb;

			$filters = $this->get_filters();
			$sql     = '';

			// append join clause.
			foreach ( $enabled_filters as $filter_name => $filter_value ) {
				if ( ! isset( $filters[ $filter_name ] ) ) {
					continue;
				}
				$sql .= ' ' . $filters[ $filter_name ]->get_sql_join_clause( $filter_value );
			}

			// append where clause.
			$sql .= " WHERE {$wpdb->posts}.post_type = '" . $this->get_post_type() . "'";
			foreach ( $enabled_filters as $filter_name => $filter_value ) {
				if ( ! isset( $filters[ $filter_name ] ) ) {
					continue;
				}
				$where_clause = $filters[ $filter_name ]->get_sql_where_clause( $filter_value );
				$sql         .= ! empty( $where_clause ) ? ' AND ' . $where_clause : '';
			}

			return $sql;
		}

		/**
		 * Returns action name for update posts endpoint.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_ajax_action_update_posts(): string {
			return $this->get_ajax_action_prefix() . '_update';
		}

		/**
		 * Returns nonce for update posts endpoint.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_ajax_action_update_posts_nonce(): string {
			return wp_create_nonce( $this->get_ajax_action_update_posts() );
		}

		/**
		 * Processes update posts action.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function process_ajax_action_update_posts() {
			// validate nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->get_ajax_action_update_posts() ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid request.', 'learndash' ),
					)
				);
			}

			// load enabled filters and fields values.
			$enabled_filters = $this->get_enabled_items_from_request(
				isset( $_POST['filters'] ) ? wp_unslash( $_POST['filters'] ) : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitization will be done by the method.
			);
			$enabled_fields  = $this->get_enabled_items_from_request(
				isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitization will be done by the method.
			);

			// validate enabled filters.
			if ( empty( $enabled_fields ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'No fields selected.', 'learndash' ),
					)
				);
			}

			$affected_posts_ids    = $this->get_affected_posts_ids( $enabled_filters );
			$affected_posts_number = count( $affected_posts_ids );

			if ( empty( $affected_posts_number ) ) {
				wp_send_json_error(
					array(
						// translators: placeholder: post type name.
						'message' => sprintf( __( 'No affected %s.', 'learndash' ), $this->get_tab_name() ),
					)
				);
			}

			// enqueue the update posts task.

			$this->bulk_edit_scheduler->enqueue_task(
				$this->get_bulk_edit_task_name(),
				array(
					'affected_posts_ids' => $affected_posts_ids,
					'fields'             => $enabled_fields,
				),
				$this->get_related_object_id( $affected_posts_ids, $enabled_fields ),
				sprintf(
					// translators: placeholder: affected posts number, post type.
					__( 'The bulk editing of %1$d %2$s is in the processing queue. Please refresh this page to see the progress.', 'learndash' ),
					$affected_posts_number,
					'<b>' . $this->get_tab_name() . '</b>'
				),
				sprintf(
					// translators: placeholder: affected posts number, post type.
					__( 'The bulk editing of %1$d %2$s is running. Please refresh this page to see the progress.', 'learndash' ),
					$affected_posts_number,
					'<b>' . $this->get_tab_name() . '</b>'
				)
			);

			wp_send_json_success(
				array(
					'message' => sprintf(
						// translators: placeholder: affected posts number, post type.
						__( 'The bulk editing of %1$d %2$s will be processed soon. You can see the progress refreshing this page.', 'learndash' ),
						$affected_posts_number,
						$this->get_tab_name()
					),
				)
			);
		}

		/**
		 * Process the update posts stuff.
		 *
		 * @since 4.2.0
		 *
		 * @param array $affected_posts_ids List of affected posts IDs.
		 * @param array $fields             The enabled fields.
		 *
		 * @return void
		 */
		public function update_posts_task( array $affected_posts_ids, array $fields ): void {
			foreach ( $affected_posts_ids as $post_id ) {
				foreach ( $fields as $field_name => $field_value ) {
					$this->update_post_field( absint( $post_id ), $field_name, $field_value );
				}
			}

			// add success notice.
			Learndash_Admin_Action_Scheduler::add_admin_notice(
				sprintf(
					// translators: placeholder: affected posts number, post type.
					__( 'The bulk edit of %1$d %2$s finished successfully.', 'learndash' ),
					count( $affected_posts_ids ),
					'<b>' . $this->get_tab_name() . '</b>'
				),
				'success',
				$this->get_related_object_id( $affected_posts_ids, $fields )
			);
		}

		/**
		 * Returns ajax query data for Select 2.
		 *
		 * @since 4.2.0
		 *
		 * @param string $post_type Post type.
		 *
		 * @return array
		 */
		protected function get_select_ajax_query_data_for_post_type( string $post_type ): array {
			$settings_element = array(
				'settings_class' => __CLASS__,
			);

			return array(
				'query_args'       => array(
					'post_type' => $post_type,
				),
				'settings_element' => $settings_element,
				'nonce'            => wp_create_nonce(
					wp_json_encode( $settings_element, JSON_FORCE_OBJECT )
				),
			);
		}

		/**
		 * Returns the action name for affected posts number endpoint.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		private function get_ajax_action_prefix(): string {
			return mb_strtolower( get_class( $this ) );
		}

		/**
		 * Returns the bulk edit task action name.
		 *
		 * @since 4.2.0
		 *
		 * @return string The bulk edit task action name.
		 */
		private function get_bulk_edit_task_name(): string {
			return 'learndash_bulk_edit_task_' . $this->get_post_type();
		}

		/**
		 * Generates the related object ID for a bulk edit task.
		 *
		 * @since 4.2.0
		 *
		 * @param array $affected_posts_ids The affected posts IDs.
		 * @param array $enabled_fields    The enabled fields.
		 *
		 * @return string The related object ID.
		 */
		private function get_related_object_id( array $affected_posts_ids, array $enabled_fields ): string {
			$base_str = wp_json_encode(
				array(
					'affected_posts_ids' => $affected_posts_ids,
					'fields'             => $enabled_fields,
				)
			);
			return md5( $this->get_bulk_edit_task_name() . $base_str );
		}
	}
}
