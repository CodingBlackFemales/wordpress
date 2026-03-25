<?php
namespace BuddyBossTheme\Admin\Mothership;

?>
<div class="wrap buddyboss-mothership-wrap">

	<h2><?php echo esc_html( BB_Theme_License_Page::pageTitle() ); ?></h2>

	<div class="buddyboss-mothership-block-container">
		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Auto Connect (Recommended)', 'buddyboss-theme' ); ?></h2>
				<p>
					<?php printf( esc_html__( 'Click the "Connect to BuddyBoss" button to log into your BuddyBoss account. Then click "Allow" to have your license automatically filled in to activate your products.', 'buddyboss-theme' ) ); ?>
				</p>
				<br/>
				<button id="btn_bb_connect" class="button button-primary">
					<?php esc_html_e( 'Connect to BuddyBoss', 'buddyboss-theme' ); ?>
				</button>
				<span class="connecting" style="display:none;"><?php esc_html_e( 'Connecting', 'buddyboss-theme' ); ?></span>
			</div>
		</div>

		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Manual Connect', 'buddyboss-theme' ); ?></h2>
				<p>
					<li>
						<?php printf( __( 'Log into %s', 'buddyboss-theme' ), '<a href="https://my.buddyboss.com/wp-admin">BuddyBoss.com</a>' ); ?>
					</li>
					<li>
						<?php printf( __( 'Go to your %s', 'buddyboss-theme' ), '<a href="https://my.buddyboss.com/my-account/">Account</a>' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Go to the "Subscriptions" tab', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Find your product\'s license key', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your license key below', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Enter your BuddyBoss account email', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Click "Update License"', 'buddyboss-theme' ); ?>
					</li>
				</p>
			</div>
		</div>

		<div class="buddyboss-mothership-block">
			<div class="inside">
				<h2><?php esc_html_e( 'Benefits of a License', 'buddyboss-theme' ); ?></h2>
				<ul>
					<li>
						<strong><?php esc_html_e( 'Stay Up to Date', 'buddyboss-theme' ); ?></strong><br/>
						<?php esc_html_e( 'Get the latest features right away', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Admin Notifications', 'buddyboss-theme' ); ?></strong><br/>
						<?php esc_html_e( 'Get updates in WordPress', 'buddyboss-theme' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Professional Support', 'buddyboss-theme' ); ?></strong><br/>
						<?php esc_html_e( 'Get help with any questions', 'buddyboss-theme' ); ?>
					</li>
				</ul>
			</div>
		</div>

	</div>

	<div class='buddyboss-mothership-settings clearfix'>
		<?php
			// Use our custom BB_Theme_License_Manager instead of the base LicenseManager.
			$license_manager = new BB_Theme_License_Manager();
			echo '<div class="setting-wrapper">';
			echo $license_manager->generateLicenseActivationForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		?>
	</div><!-- .buddyboss-mothership-settings -->

	<!-- Reset License Settings Section -->
	<div class="buddyboss-mothership-reset-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
		<h3><?php esc_html_e( 'Troubleshooting', 'buddyboss-theme' ); ?></h3>
		<p><?php esc_html_e( 'If you\'re experiencing activation issues, you can reset all license settings and try again.', 'buddyboss-theme' ); ?></p>
		<p><strong><?php esc_html_e( 'Warning:', 'buddyboss-theme' ); ?></strong> <?php esc_html_e( 'This will clear all license data including activation status. You will need to re-activate your license after resetting.', 'buddyboss-theme' ); ?></p>
		<button type="button" id="bb-reset-license-btn" class="button button-secondary">
			<?php esc_html_e( 'Reset License Settings', 'buddyboss-theme' ); ?>
		</button>
		<span id="bb-reset-license-spinner" class="spinner" style="float: none; margin: 0 10px;"></span>
		<div id="bb-reset-license-message" style="margin-top: 10px;"></div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#bb-reset-license-btn').on('click', function(e) {
			e.preventDefault();

			// Confirm action.
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reset all license settings? This will clear your license activation and you will need to re-activate.', 'buddyboss-theme' ) ); ?>')) {
				return;
			}

			var $btn = $(this);
			var $spinner = $('#bb-reset-license-spinner');
			var $message = $('#bb-reset-license-message');

			// Show loading state.
			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$message.html('');

			// Make AJAX request.
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'bb_reset_license_settings',
					nonce: '<?php echo esc_js( wp_create_nonce( 'bb_reset_license_settings' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						// Create message safely to prevent XSS.
						var $successMsg = $('<div class="notice notice-success inline"><p></p></div>');
						$successMsg.find('p').text(response.data.message);
						$message.html($successMsg);

						// Reload the page after 2 seconds to show the clean state.
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					} else {
						// Create error message safely.
						var $errorMsg = $('<div class="notice notice-error inline"><p></p></div>');
						$errorMsg.find('p').text(response.data || '<?php echo esc_js( __( 'An error occurred', 'buddyboss-theme' ) ); ?>');
						$message.html($errorMsg);

						// Re-enable button.
						$btn.prop('disabled', false);
					}
				},
				error: function() {
					// Create error message safely.
					var $errorMsg = $('<div class="notice notice-error inline"><p></p></div>');
					$errorMsg.find('p').text('<?php echo esc_js( __( 'Network error. Please try again.', 'buddyboss-theme' ) ); ?>');
					$message.html($errorMsg);

					// Re-enable button.
					$btn.prop('disabled', false);
				},
				complete: function() {
					// Hide spinner.
					$spinner.removeClass('is-active');
				}
			});
		});
	});
	</script>

</div>
