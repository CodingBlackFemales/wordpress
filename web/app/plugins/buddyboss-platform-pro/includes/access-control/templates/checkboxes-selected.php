<?php
/**
 * This template will display the selected options based on the DB settings.
 *
 * @var        $option_name
 * @var        $option_access_controls
 * @var        $db_option_key
 * @var        $threaded
 * @var        $label
 * @var        $ajax
 * @var array  $access_control_settings
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
?>
<div class="access-control-checkbox-list"><?php //phpcs:ignore
if ( '' !== $option_access_controls && isset( $access_control_settings ) && ( ( isset( $access_control_settings['plugin-access-control-type'] ) || isset( $access_control_settings['gamipress-access-control-type'] ) ) || ( $access_control_settings['access-control-options'] ) ) ) {
	$variable        = ( isset( $access_control_settings['plugin-access-control-type'] ) ) ? $access_control_settings['plugin-access-control-type'] : '';
	$options_lists   = array();
	$access_controls = self::bb_get_access_control_lists();
	if ( 'membership' === $option_access_controls && '' !== $variable ) {
		$plugin_lists = $access_controls[ $option_access_controls ]['class']::instance()->bb_get_access_control_plugins_lists();
		if ( isset( $plugin_lists[ $variable ] ) && isset( $plugin_lists[ $variable ]['is_enabled'] ) && $plugin_lists[ $variable ]['is_enabled'] ) {
			$options_lists = $plugin_lists[ $variable ]['class']::instance()->get_level_lists();
		}
	} elseif ( 'gamipress' === $option_access_controls ) {
		$variable        = ( isset( $access_control_settings['gamipress-access-control-type'] ) ) ? $access_control_settings['gamipress-access-control-type'] : '';
		$gamipress_lists = $access_controls[ $option_access_controls ]['class']::instance()->bb_get_access_control_gamipress_lists();
		if ( isset( $gamipress_lists[ $variable ] ) && isset( $gamipress_lists[ $variable ]['is_enabled'] ) && $gamipress_lists[ $variable ]['is_enabled'] ) {
			$options_lists = $gamipress_lists[ $variable ]['class']::instance()->get_level_lists();
		}
	} elseif ( 'membership' !== $option_access_controls ) {
		$options_lists = $access_controls[ $option_access_controls ]['class']::instance()->get_level_lists();
	}
	if ( $options_lists ) {
		foreach ( $options_lists as $option ) {
			$default = $option['default'];
			$disable = ( $default ) ? ' disabled' : '';
			$checked = ( $default ) ? ' checked' : '';
			if ( 'disabled' === trim( $disable ) && 'checked' === trim( $checked ) ) {
				continue;
			}
			if ( $is_default_disabled ) {
				$disable = ' disabled';
			}
			if ( $threaded ) {
				?>
					<div class="parent <?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>">
					<?php
					if ( isset( $access_control_settings['access-control-options'] ) ) {
						?>
							<input
								<?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> class="access-control-threaded-input" id="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>" type="checkbox"
										<?php
											checked(
							                    // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
												in_array( $option['id'], $access_control_settings['access-control-options'] )
											);
										?>
										value="<?php echo esc_attr( $option['id'] ); ?>" data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-options][]">
							<?php
					} else {
						?>
							<input <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> class="access-control-threaded-input" id="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>" type="checkbox" value="<?php echo esc_attr( $option['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-options][]">
						<?php
					}
					?>
							<label for="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>">
								<strong><?php echo esc_html( $option['text'] ); ?></strong>
							</label>
					</div>
					<?php
					$key                = 'access-control-' . $option['id'] . '-options';
					$is_hide_default    = ( ! array_key_exists( $key, $access_control_settings ) ) ? 'access-control-hide-div' : '';
					$is_default_checked = ( ! array_key_exists( $key, $access_control_settings ) ) ? 'checked' : '';
					$in_array           = in_array( $option['id'], $access_control_settings['access-control-options'] ) // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					?>
					<div class="access-control-checkbox-list child-<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?> <?php echo esc_attr( $in_array ? '' : 'access-control-hide-div' ); ?>">
						<p class="description"><?php echo esc_html( $sub_label ); ?></p>
					<?php
					// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$all_checked = ( isset( $access_control_settings['access-control-options'] ) && isset( $access_control_settings[ $key ] ) && in_array( 'all', $access_control_settings[ $key ] ) ) ? 'checked' : '';
					// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$specific_checked = ( isset( $access_control_settings['access-control-options'] ) && isset( $access_control_settings[ $key ] ) && ! in_array( 'all', $access_control_settings[ $key ] ) ) ? 'checked' : '';
					?>
						<div class="multiple_options">
							<input id="all_<?php echo esc_attr( $option['id'] ); ?>" type="radio" value="all" name="<?php echo esc_attr( $option_name ); ?>[access-control-<?php echo esc_attr( $option['id'] ); ?>-options][]" class="chb" data-value="all" <?php echo esc_attr( $all_checked ); ?> <?php echo esc_attr( $is_default_checked ); ?> data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>" />
							<label for="all_<?php echo esc_attr( $option['id'] ); ?>"><?php esc_html_e( 'Any', 'buddyboss-pro' ); ?></label><br>
							<input id="specific_<?php echo esc_attr( $option['id'] ); ?>" type="radio" class="chb" data-value="specific" <?php echo esc_attr( $specific_checked ); ?> data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>" />
							<label for="specific_<?php echo esc_attr( $option['id'] ); ?>"><?php esc_html_e( 'Specific', 'buddyboss-pro' ); ?></label>
						</div>
					<?php
					$def_disable = false;
					if ( isset( $access_control_settings['access-control-options'] ) ) {
						// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$def_disable = in_array( $option['id'], $access_control_settings['access-control-options'] );
					}

					$is_hide = ! empty( $all_checked ) ? 'access-control-hide-div' : '';

					?>
					<div class="sub-child-wrap <?php echo esc_attr( $is_hide_default ); ?> <?php echo esc_attr( $is_hide ); ?>">
					<?php

					foreach ( $options_lists as $child_option ) {
						?>
							<div class="sub-child-<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?> <?php echo esc_attr( $is_hide ); ?> <?php echo esc_attr( $is_hide_default ); ?>">
							<?php
							if ( isset( $access_control_settings[ 'access-control-' . $option['id'] . '-options' ] ) ) {
								$in_array = in_array( $child_option['id'], $access_control_settings[ 'access-control-' . $option['id'] . '-options' ] ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								?>
									<input data-parent="<?php echo esc_attr( $option['id'] ); ?>" class="click_class" <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> <?php checked( $in_array ); ?> id="<?php echo esc_attr( $option_name ) . '_' . esc_attr( $option['id'] ); ?>_access-control-options_<?php echo esc_attr( $child_option['id'] ); ?>_sub" value="<?php echo esc_attr( $child_option['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-<?php echo esc_attr( $option['id'] ); ?>-options][]" type="checkbox">
									<?php
							} else {
								$is_disabled = ( ! $def_disable ) ? 'disabled' : '';
								?>
									<input data-parent="<?php echo esc_attr( $option['id'] ); ?>" class="click_class" <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> id="<?php echo esc_attr( $option_name ) . '_' . esc_attr( $option['id'] ); ?>_access-control-options_<?php echo esc_attr( $child_option['id'] ); ?>_sub" value="<?php echo esc_attr( $child_option['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-<?php echo esc_attr( $option['id'] ); ?>-options][]" <?php echo esc_attr( $is_disabled ); ?> type="checkbox">
									<?php
							}
							?>
								<label for="<?php echo esc_attr( $option_name ) . '_' . esc_attr( $option['id'] ); ?>_access-control-options_<?php echo esc_attr( $child_option['id'] ); ?>_sub"><?php echo esc_html( $child_option['text'] ); ?></label>
							</div>
							<?php
					}
					?>
					</div><!-- .sub-child-wrap -->
					</div>
					<?php
			} else {
				?>
					<div>
				<?php
				if ( isset( $access_control_settings['access-control-options'] ) ) {
					$in_array = in_array( $option['id'], $access_control_settings['access-control-options'] ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					?>
							<input <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> id="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>" <?php checked( $in_array ); ?> value="<?php echo esc_attr( $option['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-options][]" type="checkbox">
						<?php
				} else {
					?>
							<input <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> id="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>" value="<?php echo esc_attr( $option['id'] ); ?>" name="<?php echo esc_attr( $option_name ); ?>[access-control-options][]" type="checkbox">
						<?php
				}
				?>
						<label for="<?php echo esc_attr( $option_name ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['text'] ); ?></label>
					</div>
					<?php
			}
		}
	}
}
?>
</div>
<?php
