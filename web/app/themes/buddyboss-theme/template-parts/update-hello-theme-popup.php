<?php
$video_url = '';
?>

<div id="theme-hello-backdrop" style="display: none;"></div>

<div id="theme-hello-container" class="theme-hello-buddyboss-app bb-update-theme-modal" role="dialog" aria-labelledby="theme-hello-title" style="display: none;">
	<div class="theme-hello-header" role="document">
		<div class="theme-hello-close">
			<button type="button" class="close-modal button theme-tooltip" data-theme-tooltip-pos="down" data-theme-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss-theme' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss-theme' ); ?>
			</button>
		</div>

		<div class="theme-hello-title">
			<h1 id="theme-hello-title" tabindex="-1"><?php esc_html_e( 'Important Notice', 'buddyboss-theme' ); ?></h1>
		</div>
	</div>

	<div class="theme-hello-content">
		<?php
		if ( ! empty( $video_url ) ) {
			?>
			<div class="video-wrapper">
				<div class="video-container">
					<iframe src="<?php echo esc_url( $video_url ); ?>?byline=0&portrait=0&autoplay=0" width="560" height="315" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
			<?php
		}
		?>
		<p><?php esc_html_e( 'You are about to update to BuddyBoss Theme 2.0. 🥳', 'buddyboss-theme' ); ?></p>
		<p><?php esc_html_e( 'This release is a major update that contains a number of improvements to the theme\'s colors, layouts and styling. After updating, you will need to reconfigure your theme colors to make use of their new settings. You may also need to review any custom CSS you have, as we have changed some of our element classes.', 'buddyboss-theme' ); ?></p>
		<p><?php esc_html_e( 'Before updating, we strongly recommend backing up your site. It\'s a good idea to test this update on a staging environment first.', 'buddyboss-theme' ); ?></p>
		<p>
			<?php
			echo sprintf(
				/* translators: 1. Description. */
				esc_html__( 'For more information about this update, %1$s', 'buddyboss-theme' ),
				sprintf(
				/* translators: 1. View tutorial link. 2. View tutorial text. */
					'<a href="%1$s" target="_blank"> %2$s</a>',
					esc_url( 'https://www.buddyboss.com/resources/docs/buddyboss-theme/getting-started/updating-to-buddyboss-theme-2-0' ),
					esc_html__( 'view this tutorial', 'buddyboss-theme' )
				)
			);
			?>
		</p>
	</div>

	<div class="bp-theme-hello-footer">
		<div class="bp-hello-button-links">
			<?php
			$theme            = wp_get_theme();
			$stylesheet       = $theme->get_stylesheet();
			$theme_name       = $theme->display( 'Name' );
			$update_url       = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&amp;theme=' . rawurlencode( $stylesheet ) ), 'upgrade-theme_' . $stylesheet );
			$theme_aria_label = sprintf( esc_html__( 'Update %s now', 'buddyboss-theme' ), $theme_name );
			?>
			<a href="javascript:void(0);" class="button" id="bb-skip-now"><?php esc_html_e( 'Skip for Now', 'buddyboss-theme' ); ?></a>
			<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary" id="update-theme" aria-label="<?php echo esc_attr( $theme_aria_label ); ?>" data-slug="<?php echo esc_attr( $stylesheet ); ?>">
				<?php esc_html_e( 'Update Now', 'buddyboss-theme' ); ?>
			</a>
		</div>
	</div>
</div>
