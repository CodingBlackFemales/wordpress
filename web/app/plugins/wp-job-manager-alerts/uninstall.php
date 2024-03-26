<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = [
	'job_manager_alerts_account_required',
	'job_manager_alerts_auto_disable',
	'job_manager_alerts_email_template',
	'job_manager_alerts_email_template_value',
	'job_manager_alerts_matches_only',
	'job_manager_alerts_page_id',
	'job_manager_alerts_page_slug',
	'job_manager_permission_checkbox',
	'job_manager_alerts_brand_color',
	'job_manager_job_details_visible',
	'job_manager_alerts_form_fields',
	'wp_job_manager_alerts_version',
];

foreach ( $options as $option ) {
	delete_option( $option );
}
