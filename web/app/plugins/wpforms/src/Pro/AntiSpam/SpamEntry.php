<?php

namespace WPForms\Pro\AntiSpam;

/**
 * Class SpamEntry.
 *
 * @since 1.8.3
 */
class SpamEntry {

	/**
	 * Entry status.
	 *
	 * @since 1.8.3
	 */
	const ENTRY_STATUS = 'spam';

	/**
	 * Initialize.
	 *
	 * @since 1.8.3
	 *
	 * @return void
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.3
	 *
	 * @return void
	 */
	public function hooks() {

		// Spam entry save.
		add_filter( 'wpforms_entry_save_args', [ $this, 'add_spam_status' ], 10, 2 );

		// Spam entries list.
		add_filter( 'wpforms_entries_table_counts', [ $this, 'entries_table_counts' ], 10, 2 );
		add_filter( 'wpforms_entries_table_views', [ $this, 'entries_table_views' ], 10, 3 );
		add_filter( 'wpforms_entries_single_details_actions_disable', [ $this, 'disallow_details_actions' ], 10, 2 );
		add_filter( 'wpforms_entry_table_actions', [ $this, 'filter_entry_actions' ], 10, 2 );

		// Spam entries table.
		add_filter( 'wpforms_entries_table_display_date_range_filter_disable', [ $this, 'disable_date_range_filter' ] );
		add_action( 'wpforms_entries_table_extra_tablenav', [ $this, 'add_remove_spam_entries_button' ] );
		add_filter( 'wpforms_entries_table_get_bulk_actions', [ $this, 'filter_bulk_actions' ] );
		add_filter( 'wpforms_entries_table_get_table_classes', [ $this, 'add_spam_entries_table_class' ] );
		add_filter( 'wpforms_entries_single_details_form_url', [ $this, 'filter_spam_form_url' ], 10, 3 );

		// Spam entry actions.
		add_action( 'admin_init', [ $this, 'entry_actions' ] );
		add_action( 'admin_notices', [ $this, 'entry_notices' ] );
		add_action( 'wpforms_process_entry_saved', [ $this, 'add_meta_data' ], 20, 4 );
		add_filter( 'wpforms_entries_table_process_actions_entries_list', [ $this, 'filter_process_actions_entries_list' ], 10, 2 );
		add_filter( 'wpforms_entry_details_sidebar_actions_link', [ $this, 'add_spam_action_link' ], 10, 2 );

		add_filter( 'wpforms_entry_email' , [ $this, 'disable_entry_email' ], 10, 4 );

		// Additional wrap classes.
		add_filter( 'wpforms_entries_list_list_all_wrap_classes', [ $this, 'add_wrap_classes' ] );

		// Enable storing spam entries for new setup.
		add_filter( 'wpforms_create_form_args', [ $this, 'enable_store_spam_entries' ], 15 );

		// Akismet submit ham.
		add_action( 'wpforms_pro_anti_spam_entry_set_as_not_spam', [ $this, 'maybe_akismet_submit_ham' ], 10, 2 );

		// Akismet submit spam.
		add_action( 'wpforms_pro_anti_spam_entry_marked_as_spam', [ $this, 'maybe_akismet_submit_spam' ], 10, 2 );
	}

	/**
	 * Add spam status to the entry save args.
	 *
	 * @since 1.8.3
	 *
	 * @param array $args      Entry save args.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function add_spam_status( $args, $form_data ) {

		if ( $this->is_spam_form_data( $form_data ) ) {
			$args['status'] = self::ENTRY_STATUS;
		}

		return $args;
	}

	/**
	 * Add spam meta data to the entry.
	 *
	 * @since 1.8.3
	 *
	 * @param array $fields    Entry fields.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 */
	public function add_meta_data( $fields, $entry, $form_data, $entry_id ) {

		$spam_reason = ! empty( $form_data['spam_reason'] ) ? $form_data['spam_reason'] : null;

		if ( ! $spam_reason ) {
			return;
		}

		wpforms()->obj( 'entry_meta' )->add(
			[
				'entry_id' => absint( $entry_id ),
				'form_id'  => absint( $form_data['id'] ),
				'type'     => 'spam',
				'data'     => sanitize_text_field( $spam_reason ),
			],
			'entry_meta'
		);

		wpforms()->obj( 'entry_meta' )->add(
			[
				'entry_id' => absint( $entry_id ),
				'form_id'  => absint( $form_data['id'] ),
				'type'     => 'post_data_raw',
				'data'     => wp_json_encode( $form_data['post_data_raw'] ),
			],
			'entry_meta'
		);
	}

	/**
	 * Get entry spam reason.
	 *
	 * @since 1.8.3
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return string
	 */
	private function get_spam_reason( $entry_id ) {

		$reason = wpforms()->obj( 'entry_meta' )->get_meta(
			[
				'entry_id' => absint( $entry_id ),
				'type'     => 'spam',
				'number'   => 1,
			]
		);

		return ! empty( $reason[0] ) ? $reason[0]->data : '';
	}

	/**
	 * Get entry post data raw.
	 *
	 * @since 1.8.3
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return array
	 */
	private function get_entry_post_data_raw( $entry_id ) {

		$post_data_raw = wpforms()->obj( 'entry_meta' )->get_meta(
			[
				'entry_id' => absint( $entry_id ),
				'type'     => 'post_data_raw',
				'number'   => 1,
			]
		);

		return ! empty( $post_data_raw[0] ) ? json_decode( $post_data_raw[0]->data, true ) : [];
	}

	/**
	 * Disable entry email if the entry is marked as spam.
	 *
	 * @since 1.8.3
	 *
	 * @param bool  $enabled   Whether the email is enabled.
	 * @param array $fields    Entry fields.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function disable_entry_email( $enabled, $fields, $entry, $form_data ) {

		if ( $this->is_spam_form_data( $form_data ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Send entry email if the entry is marked as spam.
	 *
	 * @since 1.8.3
	 *
	 * @param int   $entry_id  Entry ID.
	 * @param array $fields    Entry fields.
	 * @param array $form_data Form data.
	 */
	private function send_entry_email( $entry_id, $fields, $form_data ) {

		wpforms()->obj( 'process' )->entry_email( $fields, [], $form_data, $entry_id, 'entry' );
	}

	/**
	 * Delete entry spam reason.
	 *
	 * @since 1.8.3
	 *
	 * @param int $entry_id Entry ID.
	 */
	private function delete_spam_reason( $entry_id ) {

		wpforms()->obj( 'entry_meta' )->delete(
			[
				'entry_id' => absint( $entry_id ),
				'type'     => 'spam',
			]
		);
	}

	/**
	 * Add spam entries to the entries table counts.
	 *
	 * @since 1.8.3
	 *
	 * @param array $counts    Entries table counts.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function entries_table_counts( $counts, $form_data ) {

		$counts['spam'] = $this->get_spam_entries_count( $form_data['id'] );

		return $counts;
	}

	/**
	 * Add spam entries to the entries table views.
	 *
	 * @since 1.8.3
	 *
	 * @param array $views     Entries table views.
	 * @param array $form_data Form data.
	 * @param array $counts    Entries table counts.
	 *
	 * @return array
	 */
	public function entries_table_views( $views, $form_data, $counts ) {

		$views['spam'] = sprintf(
			'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
			esc_url( $this->get_spam_entries_list_url( $form_data['id'] ) ),
			$this->is_spam_list() ? 'current' : '',
			esc_html__( 'Spam', 'wpforms' ),
			absint( $counts['spam'] )
		);

		return $views;
	}

	/**
	 * Display spam entry notices.
	 *
	 * @since 1.8.3
	 */
	public function entry_notices() {

		if ( ! wpforms_is_admin_page( 'entries' ) ) {
			return;
		}

		$this->maybe_display_success_message();

		$this->maybe_display_error_message();

		$this->display_purge_notice();
	}

	/**
	 * Maybe display a success message.
	 *
	 * @since 1.8.3
	 */
	private function maybe_display_success_message() {

		// Show a success message after marking entry as not spam.
		$message = ! empty( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$action  = ! empty( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $message === 'unspam' && $action !== 'spam' ) {
			wpforms()->obj( 'notice' )->success( esc_html__( 'Entry successfully unmarked as spam.', 'wpforms' ) );
		}
	}

	/**
	 * Maybe display an error message.
	 *
	 * @since 1.8.3
	 */
	private function maybe_display_error_message() {

		if ( ! wpforms_is_admin_page( 'entries', 'details' ) ) {
			return;
		}

		$entry_id = $this->get_current_entry_id();

		if ( ! $entry_id ) {
			return;
		}

		$is_spam = $this->is_spam_entry( $entry_id );

		if ( ! $is_spam ) {
			return;
		}

		$spam_reason = $this->get_spam_reason( $entry_id );

		$mark_not_spam_url = wp_nonce_url(
			add_query_arg(
				[
					'action'   => 'mark_not_spam',
					'entry_id' => $entry_id,
				]
			),
			'edit-entry'
		);

		// Show error message if entry is spam.
		wpforms()->obj( 'notice' )->error(
			sprintf(
				'%s %s',
				sprintf(
				/* translators: %s - antispam method. */
					esc_html__( 'This entry was marked as spam by %s.', 'wpforms' ),
					esc_html( $spam_reason )
				),
				sprintf( '<a href="%1$s">%2$s</a>', esc_url( $mark_not_spam_url ), esc_html__( 'Mark as Not Spam', 'wpforms' ) )
			),
			[
				'class' => 'wpforms-notice-spam',
			]
		);
	}

	/**
	 * Display purge notice.
	 *
	 * The notice should be displayed over the Spam Entries list informing the user
	 * that the entries will be purged automatically.
	 *
	 * @since 1.9.1
	 **/
	private function display_purge_notice() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['status'] ) || $_GET['status'] !== self::ENTRY_STATUS ) {
			return;
		}

		$days = Helpers::get_delete_spam_entries_days();

		if ( $days === false ) {
			return;
		}

		/* translators: %d - number of days. */
		$days_text = sprintf( esc_html__( '%d days', 'wpforms' ), $days );

		$link = defined( 'WPFORMS_DELETE_SPAM_ENTRIES' ) ?
			$days_text :
			'<a href="' . esc_url( admin_url( 'admin.php?page=wpforms-settings&view=misc' ) ) . '">' . $days_text . '</a>';

		$message = sprintf(
			'<p>%s</p>',
			sprintf(
				/* translators: %s - number of days wrapped in the link to the settings page. */
				esc_html__( 'Spam entries older than %s are automatically deleted.', 'wpforms' ),
				$link
			)
		);

		// Display the notice.
		wpforms()->obj( 'notice' )->info( $message );
	}

	/**
	 * Entry actions.
	 *
	 * @since 1.8.3
	 */
	public function entry_actions() {

		$nonce = ! empty( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'edit-entry' ) ) {
			return;
		}

		$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

		if ( $action === 'mark_not_spam' ) {
			$this->action_mark_not_spam();
		}

		if ( $action === 'spam' ) {
			$this->action_mark_spam();
		}
	}

	/**
	 * Mark entry as not spam action.
	 *
	 * @since 1.8.3
	 */
	private function action_mark_not_spam() {

		$entry_id = $this->get_current_entry_id();

		if ( ! $this->is_spam_entry( $entry_id ) ) {
			return;
		}

		// Prepare entry data to use in post-processing.
		$entry = wpforms()->obj( 'entry' )->get( $entry_id );

		if ( ! $entry ) {
			return;
		}

		// Mark entry as not spam.
		$this->set_as_not_spam( $entry );

		$this->process_complete( $entry );
	}

	/**
	 * Mark entry as spam action.
	 *
	 * @since 1.8.9
	 */
	private function action_mark_spam() {

		$entry_id = $this->get_current_entry_id();

		if ( $this->is_spam_entry( $entry_id ) ) {
			return;
		}

		$form_id = $this->get_current_form_id();

		$user = get_user_by( 'id', get_current_user_id() );

		$this->set_as_spam( $entry_id, $form_id, $user->display_name );
	}

	/**
	 * Process entry as not spam.
	 *
	 * @since 1.8.3
	 *
	 * @param object $entry Entry data.
	 */
	private function process_complete( $entry ) {

		$entry_id = $entry->entry_id;
		$form_id  = $entry->form_id;

		$fields    = wpforms_decode( $entry->fields );
		$form_data = wpforms()->obj( 'form' )->get(
			$form_id,
			[
				'content_only' => true,
			]
		);

		$form_data['post_data_raw'] = $this->get_entry_post_data_raw( $entry_id );

		wpforms()->obj( 'process' )->process_complete( $form_id, $form_data, $fields, [], $entry_id );

		// Send email notification.
		$this->send_entry_email( $entry_id, $fields, $form_data );

		$url = remove_query_arg( [ 'action', '_wpnonce' ], wp_get_referer() );

		wp_safe_redirect( add_query_arg( 'message', 'unspam', $url ) );
		exit;
	}

	/**
	 * Filter entries list by spam status.
	 *
	 * @since 1.8.3
	 *
	 * @param array $entries_list Entries list.
	 * @param array $args         Query args.
	 *
	 * @return array
	 */
	public function filter_process_actions_entries_list( $entries_list, $args ) {

		$ids = $args['entry_id'];

		if ( empty( $ids ) ) {
			return $entries_list;
		}

		/**
		 * If we have just one entry ID in the list, it means that bulk action fired for spam entries.
		 * If it is, we need to change the status of the query to 'spam' to get the correct list.
		 */
		if ( $this->is_spam_entry( $ids[0] ) ) {
			$args['status'] = self::ENTRY_STATUS;
			$entries_list   = (array) wpforms()->obj( 'entry' )->get_entries( $args );
		}

		return $entries_list;
	}

	/**
	 * Add spam action link to the entry details sidebar.
	 *
	 * @since 1.8.9
	 *
	 * @param array  $action_links Action links.
	 * @param object $entry        Entry data.
	 *
	 * @return array
	 */
	public function add_spam_action_link( array $action_links, $entry ): array {

		$action_links['spam'] = [
			'label' => esc_html__( 'Mark as Spam', 'wpforms' ),
			'icon'  => 'dashicons-shield',
			'url'   => wp_nonce_url(
				add_query_arg(
					[
						'action'   => 'spam',
						'entry_id' => $entry->entry_id,
						'form_id'  => $entry->form_id,
					]
				),
				'edit-entry'
			),
		];

		return $action_links;
	}

	/**
	 * Set entry as not spam.
	 *
	 * @since 1.8.3
	 *
	 * @param object $entry Entry data.
	 */
	public function set_as_not_spam( $entry ) {

		wpforms()->obj( 'entry' )->update( $entry->entry_id, [ 'status' => '' ] );

		// Add record to entry meta.
		wpforms()->obj( 'entry_meta' )->add(
			[
				'entry_id' => (int) $entry->entry_id,
				'form_id'  => (int) $entry->form_id,
				'user_id'  => get_current_user_id(),
				'type'     => 'log',
				'data'     => wpautop( sprintf( '<em>%s</em>', __( 'Marked as not spam.', 'wpforms' ) ) ),
			],
			'entry_meta'
		);

		/**
		 * Fires after the entry is set as not spam.
		 *
		 * @since 1.8.8
		 *
		 * @param int $entry_id Entry ID.
		 * @param int $form_id  Form ID.
		 */
		do_action( 'wpforms_pro_anti_spam_entry_set_as_not_spam', $entry->entry_id, $entry->form_id );

		$this->delete_spam_reason( $entry->entry_id );
	}

	/**
	 * Disallow details actions for spam entries.
	 *
	 * @since 1.8.3
	 *
	 * @param bool   $disable Disable actions.
	 * @param object $entry   Entry object.
	 *
	 * @return bool
	 */
	public function disallow_details_actions( $disable, $entry ) {

		return $this->is_spam_entry( $entry->entry_id );
	}

	/**
	 * Filter entry actions for spam entries.
	 *
	 * @since 1.8.3
	 *
	 * @param array  $actions Actions.
	 * @param object $entry   Entry object.
	 *
	 * @return array
	 */
	public function filter_entry_actions( $actions, $entry ) {

		if ( ! wpforms_current_user_can( 'edit_entries_form_single', $entry->form_id ) ) {
			return $actions;
		}

		if ( $this->is_spam_entry( $entry->entry_id ) ) {

			$args = [
				'action'   => 'mark_not_spam',
				'entry_id' => $entry->entry_id,
				'form_id'  => $entry->form_id,
			];

			$url = wp_nonce_url( $this->get_spam_entries_list_url( $entry->form_id, $args ), 'edit-entry' );

			$actions['edit'] = sprintf(
				'<a href="%s" title="%s" class="mark-not-spam">%s</a>',
				esc_url( $url ),
				esc_attr__( 'Mark as Not Spam', 'wpforms' ),
				esc_html__( 'Not Spam', 'wpforms' )
			);
		}

		if ( ! $this->is_spam_entry( $entry->entry_id ) ) {

			$action = [
				'spam' => sprintf(
					'<a href="%s" title="%s" class="mark-spam">%s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								[
									'view'     => 'list',
									'action'   => 'spam',
									'form_id'  => $entry->form_id,
									'entry_id' => $entry->entry_id,
								]
							),
							'edit-entry'
						)
					),
					esc_attr__( 'Mark as Spam', 'wpforms' ),
					esc_html__( 'Spam', 'wpforms' )
				),
			];

			$actions = wpforms_list_insert_before( $actions, 'trash', $action );
		}

		return $actions;
	}

	/**
	 * Disallow entry date range filter for spam entries.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	public function disable_date_range_filter() {

		return $this->is_spam_list();
	}

	/**
	 * Add button to remove spam entries.
	 *
	 * @since 1.8.3
	 */
	public function add_remove_spam_entries_button() {

		$form_id = $this->get_current_form_id();

		if ( ! $form_id ) {
			return;
		}

		if ( ! $this->is_spam_list() ) {
			return;
		}

		if ( ! $this->get_spam_entries_count( $form_id ) ) {
			return;
		}

		$base = add_query_arg(
			[
				'page'    => 'wpforms-entries',
				'view'    => 'list',
				'form_id' => absint( $form_id ),
			],
			admin_url( 'admin.php' )
		);

		printf(
			'<a href="%1$s" class="button delete-all form-details-actions-removeall" data-page="spam">%2$s</a>',
			esc_url( wp_nonce_url( $base, 'bulk-entries' ) ),
			esc_html__( 'Empty Spam', 'wpforms' )
		);
	}

	/**
	 * Filter bulk actions for spam entries.
	 *
	 * @since 1.8.3
	 *
	 * @param array $actions Bulk actions.
	 *
	 * @return array
	 */
	public function filter_bulk_actions( $actions ) {

		if ( ! $this->is_spam_list() ) {
			unset( $actions['unspam'] );
		}

		if ( $this->is_spam_list() ) {
			$allowed_actions = [
				'read',
				'unread',
				'null',
				'trash',
				'delete',
				'unspam',
			];

			$actions = array_intersect_key( $actions, array_flip( $allowed_actions ) );
		}

		return $actions;
	}

	/**
	 * Add spam entries table class.
	 *
	 * @since 1.8.3
	 *
	 * @param array $classes Table classes.
	 *
	 * @return array
	 */
	public function add_spam_entries_table_class( $classes ) {

		if ( $this->is_spam_list() ) {
			$classes[] = 'wpforms-entries-table-spam';
		}

		return $classes;
	}

	/**
	 * Add spam entries wrap class.
	 *
	 * @since 1.8.3
	 *
	 * @param array $classes Wrap classes.
	 *
	 * @return array
	 */
	public function add_wrap_classes( $classes ) {

		if ( $this->is_spam_list() && ! $this->get_spam_entries_count( $this->get_current_form_id() ) ) {
			$classes[] = 'wpforms-entries-spam-empty';
		}

		return $classes;
	}

	/**
	 * Enable storing entries for new setup.
	 *
	 * @since 1.8.7
	 *
	 * @param array $args Form args.
	 */
	public function enable_store_spam_entries( $args ) {

		if ( ! wpforms()->is_pro() ) {
			return $args;
		}

		$post_content = $args['post_content'] ?? '';

		if ( ! empty( $post_content ) ) {
			$post_content = json_decode( wp_unslash( $post_content ), true );

			// New forms created from templates may explicitly set it to 0|false, we must respect that.
			$post_content['settings']['store_spam_entries'] = $post_content['settings']['store_spam_entries'] ?? 1;

			$args['post_content'] = wpforms_encode( $post_content );
		}

		return $args;
	}

	/**
	 * Filter Back to All Entries link for spam entries.
	 *
	 * @since 1.8.3
	 *
	 * @param string $url      Form URL.
	 * @param int    $entry_id Entry ID.
	 * @param int    $form_id  Form ID.
	 *
	 * @return string
	 */
	public function filter_spam_form_url( $url, $entry_id, $form_id ) {

		if ( $this->is_spam_entry( $entry_id ) ) {
			return $this->get_spam_entries_list_url( $form_id );
		}

		return $url;
	}

	/**
	 * Submit Akismet ham (false positives) after marking entry as not spam.
	 *
	 * This call is intended for the submission of false positives –
	 * items that were incorrectly classified as spam by Akismet.
	 *
	 * See docs: https://akismet.com/developers/detailed-docs/submit-ham-false-positives/
	 *
	 * @since 1.8.8
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 */
	public function maybe_akismet_submit_ham( $entry_id, $form_id ) {

		$this->submit_akismet( $entry_id, $form_id, 'ham' );
	}

	/**
	 * Submit Akismet spam after marking entry as spam.
	 *
	 * This call is intended for the submission of missed spam –
	 * items that were incorrectly classified as ham by Akismet.
	 *
	 * See docs: https://akismet.com/developers/detailed-docs/submit-spam/
	 *
	 * @since 1.8.9
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 */
	public function maybe_akismet_submit_spam( $entry_id, $form_id ) {

		$this->submit_akismet( $entry_id, $form_id, 'spam' );
	}

	/**
	 * Submit entry to Akismet.
	 * This method is used to submit the entry as spam or ham in Akismet.
	 *
	 * @since 1.8.9
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $form_id  Form ID.
	 * @param string $type     Type of submission (spam or ham).
	 */
	private function submit_akismet( $entry_id, $form_id, $type ) {

		$form_data = $this->get_form_data( $form_id );

		if ( ! $this->is_akismet_allowed( $form_data ) ) {
			return;
		}

		$entry_data = $this->get_entry_data( $entry_id );

		if ( $type === 'spam' ) {
			// Submit the entry as spam in Akismet.
			wpforms()->obj( 'akismet' )->submit_missed_spam( $form_data, $entry_data );
		}

		if ( $type === 'ham' ) {
			// Submit the entry as not spam in Akismet.
			wpforms()->obj( 'akismet' )->set_entry_not_spam( $form_data, $entry_data );
		}
	}

	/**
	 * Check if Akismet is allowed for the form.
	 *
	 * @since 1.8.9
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	private function is_akismet_allowed( array $form_data ): bool {

		// Check if Akismet is enabled for the form.
		if ( empty( $form_data['settings']['akismet'] ) ) {
			return false;
		}

		// Check if Akismet is configured.
		if ( ! wpforms()->obj( 'akismet' )::is_configured() ) {
			return false;
		}

		return true;
	}

	/**
	 * Set entry as spam.
	 *
	 * @since 1.8.9
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $form_id  Form ID.
	 * @param string $reason   Reason for marking as spam.
	 */
	public function set_as_spam( $entry_id, $form_id, $reason ) {

		wpforms()->obj( 'entry' )->update( $entry_id, [ 'status' => self::ENTRY_STATUS ] );

		wpforms()->obj( 'entry_meta' )->add(
			[
				'entry_id' => (int) $entry_id,
				'form_id'  => (int) $form_id,
				'type'     => 'spam',
				'data'     => sanitize_text_field( $reason ),
			],
			'entry_meta'
		);

		/**
		 * Fires after the entry is marked as spam.
		 *
		 * @since 1.8.9
		 *
		 * @param int $entry_id Entry ID.
		 * @param int $form_id  Form ID.
		 */
		do_action( 'wpforms_pro_anti_spam_entry_marked_as_spam', $entry_id, $form_id );
	}

	/**
	 * Get the form data.
	 *
	 * @since 1.8.9
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array
	 */
	private function get_form_data( $form_id ) {

		return wpforms()->obj( 'form' )->get(
			$form_id,
			[
				'content_only' => true,
			]
		);
	}

	/**
	 * Get the entry data.
	 *
	 * @since 1.8.9
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return array
	 */
	private function get_entry_data( $entry_id ) {

		$entry = wpforms()->obj( 'entry' )->get( $entry_id );

		if ( ! $entry ) {
			return [];
		}

		$entry_fields = wpforms_decode( $entry->fields );

		return [
			'entry_id' => $entry_id,
			'fields'   => $entry_fields,
		];
	}

	/**
	 * Check if entry is spam.
	 *
	 * @since 1.8.3
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return bool
	 */
	private function is_spam_entry( $entry_id ) {

		$entry = wpforms()->obj( 'entry' )->get( $entry_id );

		if ( ! $entry ) {
			return false;
		}

		return $entry->status === self::ENTRY_STATUS;
	}

	/**
	 * Check if form data marked as spam.
	 *
	 * @since 1.8.3
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	private function is_spam_form_data( $form_data ) {

		return ! empty( $form_data['spam_reason'] );
	}

	/**
	 * Check if the current page is a spam list.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	public function is_spam_list() {

		$status = ! empty( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $status === self::ENTRY_STATUS;
	}

	/**
	 * Get spam entries count.
	 *
	 * @since 1.8.3
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return int
	 */
	private function get_spam_entries_count( $form_id ) {

		return wpforms()->obj( 'entry' )->get_entries(
			[
				'form_id' => $form_id,
				'status'  => self::ENTRY_STATUS,
			],
			true
		);
	}

	/**
	 * Get spam entries list URL.
	 *
	 * @since 1.8.3
	 *
	 * @param int   $form_id Form ID.
	 * @param array $args    Additional arguments.
	 *
	 * @return string
	 */
	private function get_spam_entries_list_url( $form_id, $args = [] ) {

		$defaults = [
			'page'    => 'wpforms-entries',
			'view'    => 'list',
			'form_id' => $form_id,
			'status'  => self::ENTRY_STATUS,
		];

		$args = wp_parse_args( $args, $defaults );

		$base = remove_query_arg( [ 'type', 'status', 'paged' ] );

		return add_query_arg( $args, $base );
	}

	/**
	 * Get current entry ID.
	 *
	 * @since 1.8.3
	 *
	 * @return int
	 */
	private function get_current_entry_id() {

		return ! empty( $_REQUEST['entry_id'] ) ? absint( $_REQUEST['entry_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get current form ID.
	 *
	 * @since 1.8.3
	 *
	 * @return int
	 */
	private function get_current_form_id() {

		return ! empty( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
