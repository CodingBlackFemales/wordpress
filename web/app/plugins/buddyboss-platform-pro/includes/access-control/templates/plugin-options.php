<?php
/**
 * This template will display the main external plugin select box selected options based on the DB settings.
 *
 * @var        $label
 * @var        $multiple
 * @var        $db_option_key
 * @var        $option_name
 * @var        $ajax
 * @var array  $access_control_settings
 * @var array  $plugin_lists
 * @var array  $variable
 * @var string $sub_label
 * @var array  $component_settings
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
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_media_key() === $db_option_key && isset( $component_settings['component'] ) && 'media' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_photos_creation']['is_disabled'] ) && $component_settings['notices']['disable_photos_creation']['is_disabled'] ) {
		$is_default_disabled = true;
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_video_key() === $db_option_key && isset( $component_settings['component'] ) && 'video' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_videos_creation']['is_disabled'] ) && $component_settings['notices']['disable_videos_creation']['is_disabled'] ) {
		$is_default_disabled = true;
	}
	if ( 'bp-media' === $current_tab && bb_access_control_upload_document_key() === $db_option_key && isset( $component_settings['component'] ) && 'document' === $component_settings['component'] && isset( $component_settings['notices'] ) && isset( $component_settings['notices']['disable_document_creation']['is_disabled'] ) && $component_settings['notices']['disable_document_creation']['is_disabled'] ) {
		$is_default_disabled = true;
	}
}

if ( isset( $access_control_settings ) && isset( $access_control_settings['access-control-type'] ) && 'membership' === $access_control_settings['access-control-type'] && isset( $access_control_settings['plugin-access-control-type'] ) && '' !== $access_control_settings['plugin-access-control-type'] && isset( $plugin_lists[ $access_control_settings['plugin-access-control-type'] ] ) && $plugin_lists[ $access_control_settings['plugin-access-control-type'] ]['is_enabled'] ) {
	?>
	<select data-label="<?php echo esc_attr( $label ); ?>"
			data-sub-label="<?php echo esc_attr( $sub_label ); ?>"
			data-component-settings="<?php echo esc_attr( wp_json_encode( $component_settings ) ); ?>"
			class="access-control-plugin-select-box <?php echo esc_attr( $multiple ); ?>"
			data-id="<?php echo esc_attr( $db_option_key ); ?>"
			name="<?php echo esc_attr( $option_name ); ?>[plugin-access-control-type]" <?php echo $is_default_disabled ? 'disabled' : ''; ?> >
		<option value=""><?php esc_html_e( '- Select Membership Type -', 'buddyboss-pro' ); ?></option>
		<?php
		foreach ( $plugin_lists as $k => $plugin_list ) {

			$plugin_key     = $k;
			$plugin_label   = $plugin_list['label'];
			$plugin_enabled = $plugin_list['is_enabled'];

			?>
			<option <?php ( $plugin_enabled ) ? selected( $access_control_settings['plugin-access-control-type'], $plugin_key ) : ''; ?>
				value="<?php echo esc_attr( $plugin_key ); ?>" <?php echo ( ! $plugin_enabled ) ? esc_attr( 'disabled' ) : ''; ?>><?php echo esc_html( $plugin_label ); ?></option>
			<?php
		}
		?>
	</select>
	<?php
} else {
	$class = ( isset( $access_control_settings ) && empty( $access_control_settings['plugin-access-control-type'] ) && isset( $access_control_settings['access-control-type'] ) && 'membership' === $access_control_settings['access-control-type'] ) ? '' : 'hidden';
	?>
	<select data-label="<?php echo esc_attr( $label ); ?>"
			data-sub-label="<?php echo esc_attr( $sub_label ); ?>"
			data-component-settings="<?php echo esc_attr( wp_json_encode( $component_settings ) ); ?>"
			class="access-control-plugin-select-box <?php echo esc_attr( $class ); ?> <?php echo esc_attr( $multiple ); ?>"
			data-id="<?php echo esc_attr( $db_option_key ); ?>"
			name="<?php echo esc_attr( $option_name ); ?>[plugin-access-control-type]">
		<option value=""><?php esc_html_e( '- Select Membership Type -', 'buddyboss-pro' ); ?></option>
		<?php
		foreach ( $plugin_lists as $k => $plugin_list ) {

			$plugin_key     = $k;
			$plugin_label   = $plugin_list['label'];
			$plugin_enabled = $plugin_list['is_enabled'];

			?>
			<option <?php selected( $variable, $plugin_key ); ?>
				value="<?php echo esc_attr( $plugin_key ); ?>" <?php echo ( ! $plugin_enabled ) ? esc_attr( 'disabled' ) : ''; ?>><?php echo esc_html( $plugin_label ); ?></option>
			<?php
		}
		?>
	</select>
	<?php
}
