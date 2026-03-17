<?php
/**
 * SSO actions.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bb_admin_setting_general_registration_fields', 'bb_sso_admin_setting_general_registration_fields' );
add_action( 'update_option_users_can_register', 'bb_sso_single_site_registration_on', 10, 2 );
add_action( 'update_option_bp-enable-site-registration', 'bb_sso_single_site_registration_on', 10, 2 );
add_action( 'update_option_bb-sso-reg-options', 'bb_sso_save_validate_reg_options', 10, 2 );

/**
 * Add SSO fields to the general registration settings.
 *
 * @since 2.6.30
 *
 * @param BP_Admin_Setting_General $field_obj Field object.
 */
function bb_sso_admin_setting_general_registration_fields( $field_obj ) {
	$enable_sso                = function_exists( 'bb_enable_sso' ) ? bb_enable_sso() : false;
	$sso_list_args             = array();
	$sso_list_args['class']    = 'child-no-padding sso-lists ' . ( function_exists( 'bb_pro_should_lock_features' ) && ! bb_pro_should_lock_features() ? '' : 'hidden' ) . ( function_exists( 'bb_enable_sso' ) && bb_enable_sso() ? '' : 'sso-fields-disable' );
	$sso_list_args['disabled'] = ! $enable_sso;
	$field_obj->add_field(
		'bb_enable_sso_lists',
		'',
		'bb_pro_admin_setting_sso_lists',
		'intval',
		$sso_list_args
	);

	if ( $enable_sso ) {
		$enabled_providers = BB_SSO::$enabled_providers;
		$disabled          = false;
		if ( ! empty( $enabled_providers ) && 1 === count( $enabled_providers ) && isset( $enabled_providers['twitter'] ) ) {
			$disabled = true;
		}

		$args             = array();
		$args['class']    = 'child-no-padding-first sso-additional-fields';
		$args['disabled'] = $disabled;
		$field_obj->add_field(
			'bb-additional-sso-name',
			__( 'Pull Additional Data from Social Account', 'buddyboss-pro' ),
			'bb_additional_sso_name_callback',
			'intval',
			$args
		);

		$args             = array();
		$args['class']    = 'child-no-padding sso-additional-fields';
		$args['disabled'] = $disabled;
		$field_obj->add_field(
			'bb-additional-sso-profile-picture',
			'',
			'bb_additional_sso_profile_picture_callback',
			'intval',
			$args
		);

		$args          = array();
		$args['class'] = 'child-no-padding sso-additional-fields';
		$field_obj->add_field(
			'',
			'',
			'bb_additional_sso_descriptions',
			'',
			$args
		);

		$args          = array();
		$args['class'] = 'child-no-padding-first sso-additional-fields';
		$field_obj->add_field(
			'bb-sso-reg-options',
			__( 'Registration Option', 'buddyboss-pro' ),
			'bb_sso_reg_option_callback',
			'strval',
			$args
		);
	}
}

/**
 * Display the SSO lists.
 *
 * @since 2.6.30
 *
 * @param array $args Arguments.
 */
function bb_pro_admin_setting_sso_lists( $args ) {
	?>
	<div class="bb-box-panel bb-sso-list bb-box-panel--sortable">
		<?php
		foreach ( BB_SSO::$providers as $provider ) {
			$state                    = $provider->get_state();
			$provider_admin           = $provider->get_admin();
			$sso_checkbox_label_class = 'bb-box-item-actions-disable';
			$sso_checkbox_class       = '';
			$sso_checkbox_checked     = '';
			$tested_status            = (int) $provider->settings->get( 'tested' );
			if ( 1 === $tested_status ) {
				$sso_checkbox_label_class = 'bb-box-item-actions-enable';
				$sso_checkbox_class       = ( 'enabled' === $state ) ? 'sso-disable' : 'sso-enable';
				$sso_checkbox_checked     = ( 'enabled' === $state ) ? 'checked' : '';
			}
			$hidden_attr      = array(
				'url'         => add_query_arg( 'test', '1', $provider->get_login_url() ),
				'width'       => $provider->get_popup_width(),
				'height'      => $provider->get_popup_height(),
				'test_status' => $tested_status,
				'state'       => $state,
			);
			$tooltip          = '';
			$disable_checkbox = false;
			if ( 'not-configured' === $state || 'not-tested' === $state ) {
				$disable_checkbox = true;
				$tooltip          = 'data-balloon-pos="up" data-balloon="' . esc_html__( 'Update the keys', 'buddyboss-pro' ) . '"';
			}
			?>
			<div class="bb-box-item bb-sso-item <?php echo esc_attr( ( 'enabled' !== $state ) ? 'is-disabled' : '' ); ?>" data-provider="<?php echo esc_attr( $provider->get_id() ); ?>" data-state="<?php echo esc_attr( $state ); ?>">
				<input type='hidden' id='sso_validate_popup_<?php echo esc_attr( $provider->get_id() ); ?>_data' name='sso_validate_popup_<?php echo esc_attr( $provider->get_id() ); ?>_data' value="" data-hidden-attr='<?php echo esc_attr( wp_json_encode( $hidden_attr ) ); ?>'>
				<div class="bb-box-item-actions">
					<label class="bb-box-item-actions-label <?php echo esc_attr( $sso_checkbox_label_class ); ?>" <?php echo $tooltip; ?>>
						<input type="checkbox" name="sso_checks[<?php echo esc_attr( $provider->get_id() ); ?>]"
							<?php echo esc_attr( $sso_checkbox_checked ); ?>
							value="1" data-provider="<?php echo esc_attr( $provider->get_id() ); ?>"
							class="<?php echo esc_attr( $sso_checkbox_class ); ?>"
							data-state="<?php echo esc_attr( $state ); ?>"
							<?php
							if ( $args['disabled'] ) {
								disabled( $args['disabled'] );
							} elseif ( $disable_checkbox ) {
								disabled( true );
							}
							?>
						/>
					</label>
				</div>

				<div class="bb-box-item-icon">
					<img src="<?php echo esc_url( $provider->get_icon() ); ?>" alt="" />
				</div>

				<div class="bb-box-item-footer">
					<span class="bb-box-item-label"><?php echo esc_html( $provider->get_label() ); ?></span>
					<button
							class="bb-box-item-edit bb-box-item-edit--sso"
							aria-label="<?php esc_attr_e( 'Edit Social Icon', 'buddyboss-pro' ); ?>">
						<i class="bb-icon-l bb-icon-pencil"></i>
					</button>
				</div>
				<input type="hidden" class="bb-admin-setting-sso-item" name="sso_items[<?php echo esc_attr( $provider->get_id() ); ?>]" value="">
			</div>
			<?php
		}
		?>
		<div id="bb-hello-backdrop" class="bb-hello-backdrop-sso bb-modal-backdrop" style="display: none;"></div>
		<div id="bb-hello-container" class="bb-hello-sso bb-modal-panel bb-modal-panel--sso" role="dialog" aria-labelledby="bb-hello-title" style="display: none;">

		</div>
	</div>
	<?php
	if ( function_exists( 'bbapp' ) ) {
		?>
		<div class="bp-feedback info bp-feedback--clean bp-feedback--sso-info">
			<span class="bp-icon" aria-hidden="true"></span>
			<span>
				<?php esc_html_e( 'Any change will require new iOS and Android app builds.', 'buddyboss-pro' ); ?>
			</span>
		</div>
		<?php
	}
}

/**
 * SSO name setting field.
 *
 * @since 2.6.30
 *
 * @param array $args Arguments.
 */
function bb_additional_sso_name_callback( $args ) {
	?>

	<input id="bb-additional-sso-name" name="bb-additional-sso-name" type="checkbox" value="1" <?php checked( bb_enable_additional_sso_name( false ) ); ?> <?php disabled( $args['disabled'] ); ?> />
	<label for="bb-additional-sso-name">
		<?php esc_html_e( 'Name', 'buddyboss-pro' ); ?>
	</label>

	<?php
}

/**
 * SSO profile picture setting field.
 *
 * @since 2.6.30
 *
 * @param array $args Arguments.
 */
function bb_additional_sso_profile_picture_callback( $args ) {
	?>

	<input id="bb-additional-sso-profile-picture" name="bb-additional-sso-profile-picture" type="checkbox" value="1" <?php checked( bb_enable_additional_sso_profile_picture( false ) ); ?> <?php disabled( $args['disabled'] ); ?> />
	<label for="bb-additional-sso-profile-picture">
		<?php esc_html_e( 'Profile Picture', 'buddyboss-pro' ); ?>
	</label>

	<?php
}

/**
 * SSO descriptions.
 *
 * @since 2.6.30
 */
function bb_additional_sso_descriptions() {
	printf(
		'<p class="description">%s</p>',
		esc_html__( "Deselect the data options you do not want your users to sync when registering using a social login option. These data options can not be disabled for 'X'.", 'buddyboss-pro' )
	);
}

/**
 * SSO registration option setting field.
 *
 * @since 2.6.60
 */
function bb_sso_reg_option_callback() {
	$saved_value = bb_enable_sso_reg_options();
	?>
	<div class="sso-reg-options-field">
		<input id="bb-sso-registration-enable" name="bb-sso-reg-options" type="radio" value="1" <?php checked( $saved_value ); ?> />
		<label for="bb-sso-registration-enable">
			<?php esc_html_e( 'Enable', 'buddyboss-pro' ); ?>
		</label>
		<p>
			<?php esc_html_e( 'Use both Wordpress and social login.', 'buddyboss-pro' ); ?>
		</p>
	</div>

	<div class="sso-reg-options-field">
		<input id="bb-sso-registration-disable" name="bb-sso-reg-options" type="radio" value="0" <?php checked( $saved_value, false ); ?> />
		<label for="bb-sso-registration-disable">
			<?php esc_html_e( 'Disable', 'buddyboss-pro' ); ?>
		</label>
		<p>
			<?php esc_html_e( 'Does not allow registration but allows sign in from those who already have an account.', 'buddyboss-pro' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Synchronize an SSO registration option with site and BuddyBoss registration settings.
 *
 * This function ensures that if the "Membership Registration" or "BuddyBoss Registration" settings
 * are disabled, the SSO registration option (`bb-sso-reg-options`) is also set to `false`.
 *
 * - When the `users_can_register` option (WordPress) or the `bp-enable-site-registration` option
 *   (BuddyBoss) is set to `false`, this function will update the `bb-sso-reg-options` to `false`.
 *
 * @since 2.6.60
 *
 * @param mixed $old_value The previous value of the option.
 * @param mixed $value     The new value of the option.
 */
function bb_sso_single_site_registration_on( $old_value, $value ) {
	// Check if the new value is `0` (registration disabled).
	if ( 0 === (int) $value ) {
		// Update the SSO registration option to `0` (disabled).
		bp_update_option( 'bb-sso-reg-options', 0 );
	}
}

/**
 * Save and validate the SSO registration options.
 *
 * - When the `bp-enable-site-registration` option is set to `false`,
 *   then the SSO registration option (`bb-sso-reg-options`) is also set to `false` using jquery.
 *   But if manually changed an SSO registration option set to `true`,
 *   then this function will update the `bb-sso-reg-options` to `false`.
 *
 * @since 2.6.60
 *
 * @param mixed $old_value The previous value of the option.
 * @param mixed $value     The new value of the option.
 */
function bb_sso_save_validate_reg_options( $old_value, $value ) {
	$allow_registration = function_exists( 'bp_enable_site_registration' ) && bp_enable_site_registration();
	if ( 0 === (int) $allow_registration ) {
		// Update the SSO registration option to `0` (disabled) if the site registration is disabled.
		bp_update_option( 'bb-sso-reg-options', 0 );
	} else {
		bp_update_option( 'bb-sso-reg-options', $value );
	}
}
