<?php
/**
 * Entry print repeater field template.
 *
 * @since 1.8.9
 *
 * @var array  $field           Field data.
 * @var array  $form_data       Form data and settings.
 * @var object $entry           Entry.
 * @var bool   $is_hidden_by_cl Whether the field is hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $field['display'] ) && $field['display'] === 'rows' ) {
	echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'admin/entry-print/repeater-rows',
		[
			'entry'           => $entry,
			'field'           => $field,
			'form_data'       => $form_data,
			'is_hidden_by_cl' => $is_hidden_by_cl,
		],
		true
	);
} else {
	echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'admin/entry-print/repeater-blocks',
		[
			'entry'           => $entry,
			'field'           => $field,
			'form_data'       => $form_data,
			'is_hidden_by_cl' => $is_hidden_by_cl,
		],
		true
	);
}
