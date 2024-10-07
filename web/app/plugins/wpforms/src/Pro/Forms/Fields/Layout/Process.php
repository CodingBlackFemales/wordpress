<?php

namespace WPForms\Pro\Forms\Fields\Layout;

/**
 * Layout field's Process class.
 *
 * @since 1.8.9
 */
class Process {
	/**
	 * Initialize.
	 *
	 * @since 1.8.9
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.9
	 */
	private function hooks() {

		add_filter( 'wpforms_pro_admin_entries_export_ajax_form_data', [ Helpers::class, 'reorder_fields_within_rows' ], 30 );
		add_filter( 'wpforms_process_before_form_data', [ Helpers::class, 'reorder_fields_within_rows' ], 30 );
		add_filter( 'wpforms_emails_notifications_form_data', [ Helpers::class, 'reorder_fields_within_rows' ], 30 );
		add_filter( 'wpforms_pro_admin_entries_edit_form_data', [ Helpers::class, 'reorder_fields_within_rows' ], 30 );
		add_filter( 'wpforms_entry_preview_form_data', [ Helpers::class, 'reorder_fields_within_rows' ], 30 );
	}
}
