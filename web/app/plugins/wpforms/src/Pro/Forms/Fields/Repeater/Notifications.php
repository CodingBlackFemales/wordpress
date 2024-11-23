<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;
use WPForms\Emails\Notifications as EmailNotifications;

/**
 * Repeater field's Notifications class.
 *
 * @since 1.8.9
 */
class Notifications {
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

		add_filter( 'wpforms_emails_notifications_field_message_plain', [ $this, 'get_repeater_field_plain' ], 10, 6 );
		add_filter( 'wpforms_emails_notifications_field_message_html', [ $this, 'get_repeater_field_html' ], 10, 7 );
		add_filter( 'wpforms_emails_notifications_field_ignored', [ $this, 'notifications_field_ignored' ], 10, 3 );
	}

	/**
	 * Ignore the field if it is part of the repeater field.
	 *
	 * @since 1.9.0
	 *
	 * @param bool|mixed $ignore    Whether to ignore the field.
	 * @param array      $field     Field data.
	 * @param array      $form_data Form data.
	 *
	 * @return bool
	 */
	public function notifications_field_ignored( $ignore, array $field, array $form_data ): bool {

		$ignore = (bool) $ignore;

		$form_fields = $form_data['fields'] ?? [];

		$repeater_fields = RepeaterHelpers::get_repeater_fields( $form_fields );

		foreach ( $repeater_fields as $repeater_field ) {
			$fields = RepeaterHelpers::get_repeater_all_field_ids( $repeater_field );

			if ( in_array( $field['id'], $fields, false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
				$ignore = true;
			}
		}

		return $ignore;
	}

	/**
	 * Get the repeater field HTML markup.
	 *
	 * @since 1.8.9
	 * @since 1.8.9.3 The $notifications parameter was added.
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
	public function get_repeater_field_html( $message, array $field, bool $show_empty_fields, array $other_fields, array $form_data, array $fields, $notifications ): string { // phpcs:ignore Generic.Metrics.CyclomaticComplexity, Generic.Metrics.NestingLevel.MaxExceeded

		$message = (string) $message;

		if ( isset( $field['type'] ) && $field['type'] !== 'repeater' ) {
			return $message;
		}

		if ( ! $notifications ) {
			return $message;
		}

		$blocks = RepeaterHelpers::get_blocks( $field, $form_data );

		if ( ! $blocks ) {
			return $message;
		}

		$repeater_message = '';

		foreach ( $blocks as $key => $rows ) {
			$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';
			$divider      = '';

			if ( isset( $field['label_hide'] ) && ! $field['label_hide'] ) {
				$divider = '<tr><td class="field-repeater-name field-name"><strong>' . $field['label'] . $block_number . '</strong></td><td class="field-value"></td></tr>';
			}

			$fields_message = '';

			foreach ( $rows as $row ) {
				foreach ( $row as $column ) {
					if ( ! isset( $column['field'] ) ) {
						continue;
					}

					$field_id = $column['field'];

					if ( isset( $form_data['fields'][ $field_id ] ) ) {
						$fields_message .= $notifications->get_field_html( $form_data['fields'][ $field_id ], $show_empty_fields, $other_fields );
					}
				}
			}

			if ( $fields_message ) {
				$repeater_message .= $divider . $fields_message;
			}
		}

		return $repeater_message;
	}

	/**
	 * Get the repeater field plain text markup.
	 *
	 * @since 1.8.9
	 * @since 1.8.9.3 The $notifications parameter was added.
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
	public function get_repeater_field_plain( $message, array $field, bool $show_empty_fields, array $form_data, array $fields, $notifications ): string { // phpcs:ignore Generic.Metrics.CyclomaticComplexity, Generic.Metrics.NestingLevel.MaxExceeded

		$message = (string) $message;

		if ( isset( $field['type'] ) && $field['type'] !== 'repeater' ) {
			return $message;
		}

		if ( ! $notifications ) {
			return $message;
		}

		$blocks = RepeaterHelpers::get_blocks( $field, $form_data );

		if ( ! $blocks ) {
			return $message;
		}

		$repeater_message = '';

		foreach ( $blocks as $key => $rows ) {
			$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';
			$divider      = '';

			if ( isset( $field['label_hide'] ) && ! $field['label_hide'] ) {
				$divider = '--- ' . $field['label'] . $block_number . " ---\r\n\r\n";
			}

			$fields_message = '';

			foreach ( $rows as $row_data ) {
				foreach ( $row_data as $data ) {
					if ( isset( $data['field'], $form_data['fields'][ $data['field'] ] ) ) {
						$fields_message .= $notifications->get_field_plain( $form_data['fields'][ $data['field'] ], $show_empty_fields );
					}
				}
			}

			if ( $fields_message ) {
				$repeater_message .= $divider . $fields_message;
			}
		}

		return $repeater_message;
	}
}
