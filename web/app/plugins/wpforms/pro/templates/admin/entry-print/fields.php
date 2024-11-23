<?php
/**
 * Fields template for the Entry Print page.
 *
 * @var object $entry     Entry.
 * @var array  $form_data Form data and settings.
 * @var array  $fields    Fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $fields ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wpforms_render(
		'admin/entry-print/no-fields',
		[
			'entry'     => $entry,
			'form_data' => $form_data,
		],
		true
	);

	return;
}

foreach ( $fields as $field ) {

	$is_hidden_by_cl = isset( $field['id'] ) && wpforms_conditional_logic_fields()->field_is_hidden( $form_data, $field['id'] );

	if ( $field['type'] === 'repeater' ) {
		echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/entry-print/repeater',
			[
				'entry'           => $entry,
				'field'           => $field,
				'form_data'       => $form_data,
				'is_hidden_by_cl' => $is_hidden_by_cl,
			],
			true
		);

		continue;
	}

	if ( $field['type'] === 'layout' ) {
		if ( isset( $field['display'] ) && $field['display'] === 'rows' ) {
			echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'admin/entry-print/layout-field-row',
				[
					'entry'           => $entry,
					'form_data'       => $form_data,
					'field'           => $field,
					'is_hidden_by_cl' => $is_hidden_by_cl,
				],
				true
			);
		} else {
			echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'admin/entry-print/layout-field-column',
				[
					'entry'           => $entry,
					'form_data'       => $form_data,
					'field'           => $field,
					'is_hidden_by_cl' => $is_hidden_by_cl,
				],
				true
			);
		}

		continue;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wpforms_render(
		'admin/entry-print/field',
		[
			'entry'           => $entry,
			'form_data'       => $form_data,
			'field'           => $field,
			'is_hidden_by_cl' => $is_hidden_by_cl,
		],
		true
	);
}
