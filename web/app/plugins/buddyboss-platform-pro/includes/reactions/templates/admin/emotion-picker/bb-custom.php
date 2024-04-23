<?php
/**
 * This template will display the custom icons fields for reactions settings.
 *
 * @since   2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div class="bbpro-icon-uploader-main bbpro-hide" id="bbpro-custom-upload">
	<ul class="bbpro-tabs-uploader">
		<li class="tab-link bbpro-tab-uploaded" data-tab="bbpro-tab-uploaded"><?php esc_html_e( 'Uploaded', 'buddyboss-pro' ); ?></li>
		<li class="tab-link bbpro-tab-upload-icon current" data-tab="bbpro-tab-upload-icon"><?php esc_html_e( 'Add new', 'buddyboss-pro' ); ?></li>
	</ul>
	<div id="bbpro-tab-uploaded" class="tab-uploader-content bbpro-tab-uploaded-content bbpro_custom_icons">
		<div class="bbpro-dialog-icon-picker" data-id="">
			<div class="bbpro-custom-icons-list icons">
				<?php
				$custom_icons = bb_get_default_reaction_icons( 'custom' );
				foreach ( $custom_icons as $icon ) {
					printf(
						'<a href="javascript:void(0);" class="bbpro-icon custom" data-value="%1$s"><img src="%2$s" alt=""/></a>',
						esc_attr( $icon['id'] ),
						esc_url( $icon['icon_url'] )
					);
				}
				?>
			</div>
		</div>
	</div>
	<div id="bbpro-tab-upload-icon" class="tab-uploader-content bbpro-tab-upload-icon-content bbpro_custom_icons current">
		<div class="bbpro_icon_uploader">
			<h2><?php esc_html_e( 'Add file to upload', 'buddyboss-pro' ); ?></h2>
			<!--Upload Code Start-->
			<div class="icons custom_icons">

				<div class="bbpro_icon_preview" style="display: none">
					<span><?php esc_html_e( 'Crop & Adjust', 'buddyboss-pro' ); ?></span>
					<div class="inner"></div>
					<span class="cr-percentage"></span>
					<span class="loading" style="display:none"><?php esc_html_e( 'Uploading...', 'buddyboss-pro' ); ?></span>
					<a href="#" class="done"><?php esc_html_e( 'Done', 'buddyboss-pro' ); ?></a>
				</div>

			</div>
			<div class="bbpro-icon-file-upload button button-hero">
				<span><?php esc_html_e( 'Upload Icon', 'buddyboss-pro' ); ?></span>
				<input type="hidden" name="bbpro-upload-nonce" id="bbpro-upload-nonce" value="<?php echo esc_attr( wp_create_nonce( 'bbpro-upload-custom-icon' ) ); ?>">
				<input type="file" name="bbpro_custom_icon_upload" class="bbpro_custom_icon_upload" value="<?php esc_attr_e( 'Upload Icon', 'buddyboss-pro' ); ?>"/>
			</div>
			<!--Upload Code End-->
			<p class="bbpro_icon_description"><?php esc_attr_e( 'For best results upload an image greater than 200px in size.', 'buddyboss-pro' ); ?></p>
		</div>
	</div>
</div>
