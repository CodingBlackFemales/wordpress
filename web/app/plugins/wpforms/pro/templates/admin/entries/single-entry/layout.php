<?php
/**
 * Single entry layout field template.
 *
 * @since 1.9.0
 *
 * @var array                  $field           Field data.
 * @var array                  $form_data       Form data and settings.
 * @var WPForms_Entries_Single $entries_single  Single entry object.
 * @var bool                   $is_hidden_by_cl Is the field hidden by conditional logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $field['display'] ) && $field['display'] === 'rows' ) {
	echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'admin/entries/single-entry/layout-rows',
		[
			'field'           => $field,
			'form_data'       => $form_data,
			'entries_single'  => $entries_single,
			'is_hidden_by_cl' => $is_hidden_by_cl,
		],
		true
	);
} else {
	echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'admin/entries/single-entry/layout-columns',
		[
			'field'           => $field,
			'form_data'       => $form_data,
			'entries_single'  => $entries_single,
			'is_hidden_by_cl' => $is_hidden_by_cl,
		],
		true
	);
}
