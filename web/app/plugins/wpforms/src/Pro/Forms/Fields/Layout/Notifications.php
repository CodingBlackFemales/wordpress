<?php

namespace WPForms\Pro\Forms\Fields\Layout;

use WPForms\Emails\Notifications as EmailNotifications;
use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

/**
 * Layout field's Notifications class.
 *
 * @since 1.9.0
 */
class Notifications {

	/**
	 * Email type (Plain or HTML).
	 *
	 * @since 1.9.0.4
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Field data.
	 *
	 * @since 1.9.0.4
	 *
	 * @var array
	 */
	private $field;

	/**
	 * Fields data.
	 *
	 * @since 1.9.1
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Form data.
	 *
	 * @since 1.9.1
	 *
	 * @var array
	 */
	private $form_data;

	/**
	 * Email notification object.
	 *
	 * @since 1.9.0.4
	 *
	 * @var EmailNotifications
	 */
	private $notifications;

	/**
	 * Whether to display empty fields in the email.
	 *
	 * @since 1.9.0.4
	 *
	 * @var bool
	 */
	private $show_empty_fields;

	/**
	 * List of field types.
	 *
	 * @since 1.9.0.4
	 *
	 * @var array
	 */
	private $other_fields;

	/**
	 * Initialize.
	 *
	 * @since 1.9.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.0
	 */
	private function hooks() {

		add_filter( 'wpforms_emails_notifications_field_message_html', [ $this, 'get_layout_field_html' ], 10, 7 );
		add_filter( 'wpforms_emails_notifications_field_message_plain', [ $this, 'get_layout_field_plain' ], 10, 6 );
		add_filter( 'wpforms_emails_notifications_field_ignored', [ $this, 'notifications_field_ignored' ], 10, 3 );
	}

	/**
	 * Ignore the field if it is part of the layout field.
	 *
	 * @since 1.9.1
	 *
	 * @param bool  $ignore    Whether to ignore the field.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function notifications_field_ignored( $ignore, array $field, array $form_data ): bool {

		$ignore = (bool) $ignore;

		if ( empty( $field['id'] ) || strpos( $field['id'], '_' ) ) {
			return $ignore;
		}

		if ( empty( $form_data['fields'] ) ) {
			return $ignore;
		}

		$layout_fields = LayoutHelpers::get_layout_fields( $form_data['fields'] );

		foreach ( $layout_fields as $layout_field ) {
			$fields = LayoutHelpers::get_layout_all_field_ids( $layout_field );

			if ( in_array( (int) $field['id'], $fields, true ) ) {
				return true;
			}
		}

		return $ignore;
	}

	/**
	 * Check if the field is a layout field.
	 *
	 * @since 1.9.0.4
	 *
	 * @param array $field Field data.
	 *
	 * @return bool
	 */
	private function is_layout_field( array $field ): bool {

		return isset( $field['type'] ) && $field['type'] === 'layout';
	}

	/**
	 * Get the layout field HTML markup.
	 *
	 * @since 1.9.0
	 *
	 * @param string|mixed       $message           Field message.
	 * @param array              $field             Field data.
	 * @param bool               $show_empty_fields Whether to display empty fields in the email.
	 * @param array              $other_fields      List of field types.
	 * @param array              $form_data         Form data.
	 * @param array              $fields            List of submitted fields.
	 * @param EmailNotifications $notifications     Notifications instance.
	 *
	 * @return string
	 */
	public function get_layout_field_html( $message, array $field, bool $show_empty_fields, array $other_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		$message = (string) $message;

		if ( ! $this->is_layout_field( $field ) ) {
			return $message;
		}

		$this->type              = 'html';
		$this->field             = $field;
		$this->fields            = $fields;
		$this->form_data         = $form_data;
		$this->notifications     = $notifications;
		$this->show_empty_fields = $show_empty_fields;
		$this->other_fields      = $other_fields;

		return $this->get_field_message();
	}

	/**
	 * Get the layout field plain text markup.
	 *
	 * @since 1.9.0
	 *
	 * @param string|mixed       $message           Field message.
	 * @param array              $field             Field data.
	 * @param bool               $show_empty_fields Whether to display empty fields in the email.
	 * @param array              $form_data         Form data.
	 * @param array              $fields            List of submitted fields.
	 * @param EmailNotifications $notifications     Notifications instance.
	 *
	 * @return string
	 */
	public function get_layout_field_plain( $message, array $field, bool $show_empty_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		$message = (string) $message;

		if ( ! $this->is_layout_field( $field ) ) {
			return $message;
		}

		$this->type              = 'plain';
		$this->field             = $field;
		$this->fields            = $fields;
		$this->form_data         = $form_data;
		$this->notifications     = $notifications;
		$this->show_empty_fields = $show_empty_fields;

		return $this->get_field_message();
	}

	/**
	 * Get field markup for an email.
	 *
	 * @since 1.9.0.4
	 *
	 * @return string
	 */
	private function get_field_message(): string {

		$header = $this->get_header();

		if ( isset( $this->field['display'] ) && $this->field['display'] === 'rows' ) {
			return $header . $this->get_layout_field_rows();
		}

		return $header . $this->get_layout_field_columns();
	}

	/**
	 * Get layout field header.
	 *
	 * @since 1.9.0.4
	 *
	 * @return string
	 */
	private function get_header(): string {

		if ( ! empty( $this->field['label_hide'] ) || ! isset( $this->field['label'] ) || wpforms_is_empty_string( $this->field['label'] ) ) {
			return '';
		}

		if ( $this->type === 'html' ) {
			return '<tr><td class="field-layout-name field-name"><strong>' . esc_html( $this->field['label'] ) . '</strong></td><td class="field-value"></td></tr>';
		}

		// In plain email all HTML tags deleted automatically before sending, so we can skip escaping at all.
		return '--- ' . $this->field['label'] . " ---\r\n\r\n";
	}

	/**
	 * Get the layout field rows markup.
	 *
	 * @since 1.9.0.4
	 *
	 * @return string
	 */
	private function get_layout_field_rows(): string {

		$rows = isset( $this->field['columns'] ) && is_array( $this->field['columns'] ) ? LayoutHelpers::get_row_data( $this->field ) : [];

		if ( empty( $rows ) ) {
			return '';
		}

		$fields_message = '';

		foreach ( $rows as $row ) {
			foreach ( $row as $column ) {
				if ( isset( $column['field'], $this->form_data['fields'][ $column['field'] ] ) ) {
					$fields_message .= $this->get_subfield_message( $this->form_data['fields'][ $column['field'] ] );
				}
			}
		}

		return $fields_message;
	}

	/**
	 * Get the layout field columns markup.
	 *
	 * @since 1.9.0.4
	 *
	 * @return string
	 */
	private function get_layout_field_columns(): string {

		if ( ! isset( $this->field['columns'] ) ) {
			return '';
		}

		$fields_message = '';

		foreach ( $this->field['columns'] as $column ) {
			if ( ! isset( $column['fields'] ) ) {
				continue;
			}

			foreach ( $column['fields'] as $child_field_id ) {
				if ( isset( $this->form_data['fields'][ $child_field_id ] ) ) {
					$fields_message .= $this->get_subfield_message( $this->form_data['fields'][ $child_field_id ] );
				}
			}
		}

		return $fields_message;
	}

	/**
	 * Get layout subfield markup for email.
	 *
	 * @since 1.9.0.4
	 *
	 * @param array $field Field data.
	 *
	 * @return string
	 */
	private function get_subfield_message( array $field ): string {

		return $this->type === 'html' ?
			$this->notifications->get_field_html( $field, $this->show_empty_fields, $this->other_fields ) :
			$this->notifications->get_field_plain( $field, $this->show_empty_fields );
	}
}
