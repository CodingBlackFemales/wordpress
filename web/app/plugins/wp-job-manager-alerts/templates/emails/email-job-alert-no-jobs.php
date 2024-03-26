<?php
/**
 * The content of the {jobs} tag when no jobs are found.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 *
 */
?>
<div
	style="margin: 24px 0; padding: 24px; border: 1px solid #E6E6E6; line-height: 1.8; ">
	<?php esc_html_e( 'No jobs were found matching your search.', 'wp-job-manager-alerts' ); ?>
</div>
