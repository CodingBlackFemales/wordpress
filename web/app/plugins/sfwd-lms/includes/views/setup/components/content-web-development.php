<?php
/**
 * Custom Web Development content template
 *
 * @since 4.15.2
 * @version 4.15.2
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @var array<string, mixed>  $step
 * @var array<string, string> $overview_video
 * @var array<string, string> $overview_article
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>
<div class="overview-wrapper">
	<div class="copy">
		<p>
			<?php esc_html_e( 'Launch your website in as little as two weeks and start reaching your revenue goals faster than ever.', 'learndash' ); ?>
		</p>

		<p>
			<?php esc_html_e( 'Our dedicated team takes the time to thoroughly understand your unique needs. We keep you informed every step of the way, ensuring your complete satisfaction with regular reviews and final approval.', 'learndash' ); ?>
		</p>
	</div>

	<div class="buttons">
		<div class="buttons-list">
			<a href="https://go.learndash.com/sitedevcontact" target="_blank" class="button">
				<?php esc_html_e( 'Contact Us', 'learndash' ); ?>
			</a>

			<a href="https://go.learndash.com/sitedev" target="_blank">
				<?php esc_html_e( 'Learn More', 'learndash' ); ?>
			</a>
		</div>
	</div>
</div>
