<?php
/**
 * Logs settings template.
 *
 * @package LearnDash\Notifications
 *
 * @since 5.2.0
 * @version 5.2.0
 * @deprecated 5.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( __FILE__, '5.2.0' );
?>
<div class="logs-page">
	<form method="post">
		<?php wp_nonce_field( 'ld_notifications_clear_logs' ); ?>
		<button type="submit" class="clear-all-logs button">
			<?php esc_html_e( 'Clear all logs', 'learndash' ); ?>
		</button>
	</form>
	<div class="clearfix"></div>
	<?php if ( ! empty( $logs ) ) : ?>
		<div id="logs-tab">
			<ul>
				<?php
				foreach ( $logs as $key => $trigger ) :
					?>
					<li><a href="#<?php echo $key; ?>"><?php echo $trigger['name']; ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php foreach ( $logs as $key => $trigger ) : ?>
				<div id="<?php echo $key; ?>">
					<pre><?php echo $trigger['log']; ?></pre>
					<form method="post">
						<?php wp_nonce_field( 'ld_notifications_clear_logs' ); ?>
						<input type="hidden" name="trigger" value="<?php echo $key; ?>"/>
						<button type="submit" class="button">
							<?php esc_html_e( 'Clear log', 'learndash' ); ?>
						</button>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'The logs are empty.', 'learndash' ); ?></p>
	<?php endif; ?>
</div>
<script>
	jQuery(function ($) {
		$("#logs-tab").tabs();
	});
</script>
