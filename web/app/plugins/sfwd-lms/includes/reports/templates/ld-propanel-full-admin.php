<?php
/**
 * Full Reporting Page.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'LearnDash Reporting', 'learndash' ); ?></h1>
	<div id="learndash-propanel-reporting" class="single-view">
		<div class="inside">
			<?php echo do_shortcode( '[ld_reports widget="filtering"]<br />[ld_reports widget="reporting"]' ); ?>
		</div>
	</div>
</div>
