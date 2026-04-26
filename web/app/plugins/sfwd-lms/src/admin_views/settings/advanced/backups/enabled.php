<?php
/**
 * View: Settings > Advanced > Backups enabled card.
 *
 * @since 4.14.0
 * @version 4.14.0
 *
 * @var string  $images_dir The URL to the images directory.
 * @var string  $button_url The button URL.
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-settings-backups ld-solid-enabled">
	<div class="ld-settings-backups-content">
		<h1>
			<?php esc_html_e( 'Maximize Your Solid Backups Experience', 'learndash' ); ?>
		</h1>

		<p>
			<?php esc_html_e( 'Congratulations on your purchase of Solid Backups &#x1F389; You&rsquo;ve taken the first step to protect your website from the worst-case scenario. All that&rsquo;s left to do is to review your backup settings.', 'learndash' ); ?>
		</p>

		<p>
			<a
				class="button"
				href="<?php echo esc_url( $button_url ); ?>"
			>
				<?php esc_html_e( 'Review Backup Settings', 'learndash' ); ?>
			</a>
		</p>
	</div>

	<div class="ld-settings-backups-thumbnail">
		<p>
			<img
				src="<?php echo esc_url( $images_dir . 'backups-in-app-confirmation.png' ); ?>"
				alt="<?php esc_attr_e( 'Backup', 'learndash' ); ?>"
			>
		</p>
	</div>
</div>
