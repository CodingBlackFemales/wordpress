<?php
/**
 * This template will display the main select box selected options based on the DB settings.
 *
 * @var        $label
 * @var        $multiple
 * @var        $db_option_key
 * @var        $checked
 * @var        $option_name
 * @var        $ajax
 * @var array  $option
 * @var array  $access_controls
 * @var array  $option_access_controls
 * @var string $sub_label
 * @var array  $component_settings
 * @var  array $access_control_settings
 *
 * @since   1.1.0
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_default_disabled = false;

if ( ! empty( $component_settings ) ) {
	$current_tab = bb_pro_filter_input_string( INPUT_GET, 'tab' );
	if ( 'bp-groups' === $current_tab && bb_access_control_create_group_key() === $db_option_key && isset( $component_settings['component'] ) && 'groups' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_group_creation']['is_disabled'] ) && $component_settings['notices']['disable_group_creation']['is_disabled'] ) {
		$is_default_disabled = true;
		bb_access_control_display_feedback( $component_settings['notices']['disable_group_creation']['message'], $component_settings['notices']['disable_group_creation']['type'] );
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_media_key() === $db_option_key && isset( $component_settings['component'] ) && 'media' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_photos_creation']['is_disabled'] ) && $component_settings['notices']['disable_photos_creation']['is_disabled'] ) {
		$is_default_disabled = true;
		bb_access_control_display_feedback( $component_settings['notices']['disable_photos_creation']['message'], $component_settings['notices']['disable_photos_creation']['type'] );
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_video_key() === $db_option_key && isset( $component_settings['component'] ) && 'video' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_videos_creation']['is_disabled'] ) && $component_settings['notices']['disable_videos_creation']['is_disabled'] ) {
		$is_default_disabled = true;
		bb_access_control_display_feedback( $component_settings['notices']['disable_videos_creation']['message'], $component_settings['notices']['disable_videos_creation']['type'] );
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_document_key() === $db_option_key && isset( $component_settings['component'] ) && 'document' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_document_creation']['is_disabled'] ) && $component_settings['notices']['disable_document_creation']['is_disabled'] ) {
		$is_default_disabled = true;
		bb_access_control_display_feedback( $component_settings['notices']['disable_document_creation']['message'], $component_settings['notices']['disable_document_creation']['type'] );
	}
}
?>
<p class="description access_control_label_header"><?php echo esc_html( $label ); ?></p>

<br/>

<select data-label="<?php echo esc_attr( $label ); ?>"
		data-sub-label="<?php echo esc_attr( $sub_label ); ?>"
		data-component-settings="<?php echo esc_attr( wp_json_encode( $component_settings ) ); ?>"
		class="access-control-select-box <?php echo esc_attr( $multiple ); ?>"
		id="<?php echo esc_attr( $db_option_key ); ?>"
		name="<?php echo esc_attr( $option_name ); ?>[access-control-type]"
		<?php echo $is_default_disabled ? 'disabled' : ''; ?>
		>
	<option value=""><?php esc_html_e( '- Select -', 'buddyboss-pro' ); ?></option>
	<?php

	foreach ( $access_controls as $k => $access_control ) {

		$access_control_key      = $k;
		$access_control_label    = $access_control['label'];
		$access_control_enabled  = $access_control['is_enabled'];
		$access_control_selected = $access_control['is_enabled'];

		if ( $access_control_enabled && ! empty( $access_control_settings ) ) {
			if ( ( isset( $access_control_settings['access-control-type'] ) && 'membership' === $access_control_settings['access-control-type'] ) && '' !== $access_control_settings['plugin-access-control-type'] ) {
				$plugin_lists  = $access_controls[ $access_control_settings['access-control-type'] ]['class']::instance()->bb_get_access_control_plugins_lists();
				$options_lists = $plugin_lists[ $access_control_settings['plugin-access-control-type'] ]['class']::instance()->get_level_lists();
				if ( empty( $options_lists ) ) {
					$access_control_selected = false;
				} elseif ( ! $plugin_lists[ $access_control_settings['plugin-access-control-type'] ]['is_enabled'] ) {
					$access_control_selected = false;
				}
			}
		}

		?>
		<option <?php ( $access_control_selected ) ? selected( $option_access_controls, $access_control_key ) : ''; ?>
			value="<?php echo esc_attr( $access_control_key ); ?>" <?php echo ( ! $access_control_enabled ) ? esc_attr( 'disabled' ) : ''; ?>><?php echo esc_html( $access_control_label ); ?></option>
		<?php
	}
	?>
</select>
