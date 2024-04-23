<?php
/**
 * This template will display the saved custom icons for reactions settings.
 *
 * @since   2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div class="bbpro-custom-upload-settings bbpro-hide">
	<div class="bbpro-custom-icon-preview-box">
		<div class="bbpro-custom-icon-picker-message-box"><p><?php echo esc_html__( 'Select a custom icon to configure its appearance.', 'buddyboss-pro' ); ?></p></div>
		<div class="bbpro-custom-icon-picker-preview-box"></div>
		<div class="bbpro-custom-icon-picker-settings-box"></div>
	</div>
	<script type="text/html" id="tmpl-bbpro-custom-icon-picker-preview">
		<div class="bbpro-dialog-icon-picker">
			<div id="bbpro_icon_preview">
				<h3><?php echo esc_html__( 'Preview', 'buddyboss-pro' ); ?></h3>
				<div class="bbpro_custom_icon_picker_preview">
					<div class="icon-picker-preview">
						<img src="{{data.icon_path}}" alt="" />
						<#
						let defaultColor = bp.EmotionPicker.defaultColor
						let text_color = 'undefined' !== typeof data.text_color ? data.text_color : defaultColor;
						#>
						<strong style="color:{{text_color}}">{{data.icon_text}}</strong>
					</div>
				</div>
			</div>
		</div>
		<div class="bbpro-icon-delete">
			<input type="hidden" name="bbpro-delete-nonce" id="bbpro-delete-nonce" value="<?php echo esc_attr( wp_create_nonce( 'bbpro-delete-custom-icon' ) ); ?>">
			<button class="button button-link button-link-delete bbpro-delete-icon" id="bbpro-delete-icon">
				<div class="bbpro-loader bbpro-delete-icon-loader" style="display:none;"></div>
				<?php echo esc_html__( 'Delete Permanently', 'buddyboss-pro' ); ?>
			</button>
		</div>
	</script>
	<script type="text/html" id="tmpl-bbpro-custom-icon-picker-setting">
		<div class="bbpro-dialog-icon-picker">
			<div id="bbpro_icon_preview">
				<h3><?php esc_html_e( 'Settings', 'buddyboss-pro' ); ?></h3>
				<#
					let defaultColor = bp.EmotionPicker.defaultColor;
					let iconColor = 'undefined' !== typeof data.icon_color ? data.icon_color : defaultColor;
					let textColor = 'undefined' !== typeof data.text_color ? data.text_color : defaultColor;
				#>
				<div class="bbpro-new-icon-text-wrp">
					<label for="bbpro-new-icon-label">
						<?php esc_html_e( 'Label', 'buddyboss-pro' ); ?>
					</label>
					<span class="bbpro-icon-text-limit"><span>{{data.icon_text.length}}</span>/12</span>
					<input id="bbpro-new-icon-label" type="text" class="bbpro-new-icon-label" value="{{data.icon_text}}" />
				</div>
				<div class="bbpro-new-icon-notification-text-wrp bbpro-hide">
					<label for="bbpro-icon-notification-text">
						<?php esc_html_e( 'Notification Text', 'buddyboss-pro' ); ?>
					</label>
					<input id="bbpro-icon-notification-text" type="text" class="bbpro-icon-notification-text" value="{{data.notification_text}}" placeholder="<?php esc_html_e( 'reacted to', 'buddyboss-pro' ); ?>"/>
				</div>
				<div class="bbpro-new-icon-color-wrp">
					<label for="bbpro-icon-text-color">
						<?php esc_html_e( 'Text Color', 'buddyboss-pro' ); ?>
					</label>
					<div id="bbpro-icon-new-monochrome-color-picker-wrap">
						<label>
							<input id="bbpro-icon-text-color" type="text" class="bbpro-icon-text-color" value="{{textColor}}" />
						</label>
					</div>
				</div>
			</div>
		</div>
	</script>
</div>
