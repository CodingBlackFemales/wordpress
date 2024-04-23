<?php
/**
 * Reaction filters.
 *
 * @package BuddyBossPro
 *
 * @since   2.4.50
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Filter for backend fields update.
add_filter( 'bb_reactions_get_settings_fields', 'bb_pro_admin_setting_reactions_field' );

// Filter for backend sections update.
add_filter( 'bb_reactions_get_settings_sections', 'bb_admin_setting_reaction_add_footer_notice' );

// Add class in body tag when migration is in-progress.
add_filter( 'admin_body_class', 'bb_pro_admin_reaction_add_migration_class' );

// Filters for the REST settings.
add_filter( 'bp_rest_platform_settings', 'bb_rest_reaction_platform_settings', 10, 1 );

// Filter to add new class in body.
add_filter( 'body_class', 'bb_pro_reaction_body_class' );

/**
 * Function to add emotions field.
 *
 * @since 2.4.50
 *
 * @param array $fields List of reactions settings fields.
 *
 * @return array The updated array of reactions settings fields
 */
function bb_pro_admin_setting_reactions_field( $fields ) {

	$html = bb_admin_setting_reaction_notice_field_callback( false );

	$new_fields['bp_reaction_settings_section']['bb_reaction_notice'] = array(
		'title'             => esc_html__( 'Notice', 'buddyboss-pro' ),
		'callback'          => 'bb_admin_setting_reaction_notice_field_callback',
		'sanitize_callback' => 'string',
		'args'              => array(
			'class' => 'bb-pro-reaction-notices' . ( empty( $html ) ? ' bp-hide' : '' ),
		),
	);

	$fields = array_merge_recursive( $new_fields, $fields );

	$field_class = 'bb_emotions_list_row';
	if ( function_exists( 'bb_get_reaction_mode' ) && 'emotions' !== bb_get_reaction_mode() ) {
		$field_class .= ' bp-hide';
	}

	$fields['bp_reaction_settings_section']['bb_reaction_emotions'] = array(
		'title'             => esc_html__( 'Emotions', 'buddyboss-pro' ),
		'callback'          => 'bb_admin_setting_reaction_emotions_field_callback',
		'sanitize_callback' => 'string',
		'args'              => array(
			'class' => $field_class,
		),
	);

	return $fields;
}

/**
 * Add footer notice to reaction settings metabox.
 *
 * @since 2.4.50
 *
 * @param array $settings The reaction settings
 *
 * @return array The reaction settings
 */
function bb_admin_setting_reaction_add_footer_notice( $settings ) {

	if ( ! empty( $settings['bp_reaction_settings_section'] ) ) {
		$settings['bp_reaction_settings_section']['notice'] = sprintf(
			wp_kses_post(
				__( 'When switching reactions mode, use our %s to map existing reactions to the new options.', 'buddyboss-pro' )
			),
			'<a href="#" class="footer-reaction-migration-wizard">' . esc_html__( 'migration wizard', 'buddyboss-pro' ) . '</a>'
		);
	}

	return $settings;
}

/**
 * Function to add migration class when migration is in-progress.
 *
 * @sicne 2.4.50
 *
 * @param string $classes Space-separated list of CSS classes.
 *
 * @return string
 */
function bb_pro_admin_reaction_add_migration_class( $classes ) {
	$classes .= ' bb-pro-reaction-settings';
	if ( 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		$classes .= ' bb-pro-reaction-migration';
	}

	return $classes;
}

/**
 * Add reaction settings into API.
 *
 * @since 2.4.50
 *
 * @param array $settings Array settings.
 *
 * @return array Array of settings.
 */
function bb_rest_reaction_platform_settings( $settings ) {

	if ( ! function_exists( 'bp_is_active' ) ) {
		return $settings;
	}

	$settings['bb_reaction_emotions'] = bb_pro_get_reactions( 'emotions', true );
	$settings['bb_reactions_button']  = function_exists( 'bb_get_reaction_button_settings' ) ? bb_get_reaction_button_settings() : array();

	return $settings;
}

/**
 * Function to add body class when reaction mode is active
 *
 * @sicne 2.4.50
 *
 * @return array
 */
function bb_pro_reaction_body_class( $classes ) {

	if (
		function_exists( 'bb_is_reaction_emotions_enabled' ) &&
		bb_is_reaction_emotions_enabled()
	) {
		$classes[] = 'bb-reactions-mode';
	}

	return $classes;
}
