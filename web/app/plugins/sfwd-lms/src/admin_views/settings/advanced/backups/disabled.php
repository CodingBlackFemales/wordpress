<?php
/**
 * View: Settings > Advanced > Backups disabled card.
 *
 * @since 4.14.0
 * @version 4.14.0
 *
 * @var string $images_dir The URL to the images directory.
 * @var string $button_url The button URL.
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-settings-backups ld-solid-disabled">
	<div class="ld-settings-backups-content">
		<h1>
			<?php
				echo esc_html(
					sprintf(
						// translators: %s: Course label.
						__( 'Protect Your %s Site From the Worst-Case Scenario', 'learndash' ),
						learndash_get_custom_label( 'course' )
					)
				);
			?>
		</h1>

		<p>
			<?php esc_html_e( 'With Solid Backups, your hard work and valuable data remain secure, allowing you to quickly recover and get back on track in case of any unfortunate events.', 'learndash' ); ?>
		</p>

		<ul>
			<li><?php esc_html_e( 'Don&rsquo;t lose all your hard work', 'learndash' ); ?></li>
			<li><?php esc_html_e( 'Your backup plan is a click away', 'learndash' ); ?></li>
			<li><?php esc_html_e( 'Keep your website visitors and Google happy', 'learndash' ); ?></li>
		</ul>

		<p>
			<a
				class="button"
				href="<?php echo esc_url( $button_url ); ?>"
			>
				<?php esc_html_e( 'Get Solid Backups', 'learndash' ); ?>
			</a>
		</p>
	</div>

	<div class="ld-settings-backups-thumbnail">
		<p>
			<img
				src="<?php echo esc_url( $images_dir . 'backups-in-app.png' ); ?>"
				alt="<?php esc_attr_e( 'Backup', 'learndash' ); ?>"
			>
		</p>
	</div>
</div>
