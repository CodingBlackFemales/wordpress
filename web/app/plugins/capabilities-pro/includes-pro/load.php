<?php
/*
 * PublishPress Capabilities Pro
 *
 * Functions with broad scope and available for all URLs (including front end), which are not contained within a class
 *
 */

require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/classes/Pro.php');
publishpress_caps_pro();

add_action('init', function(){
	if (is_admin() && !empty($_REQUEST['publishpress_caps_ajax_settings'])) {
        include_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/pro-activation-ajax.php');
    }
});

if (class_exists('BuddyPress')) {
	add_filter(
		'bp_user_can_create_groups',
		function($can_create, $restricted) {
			return ($restricted) ? current_user_can('bp_create_groups') : $can_create;
		}, 10, 2
	);
}

// Status capabilities library: loader stub to select latest version of library (@todo: vendor library?)
require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/status-capabilities/status-capabilities.php');

add_action('plugins_loaded', function() {
	if (defined('PUBLISHPRESS_STATUSES_VERSION') && !class_exists('PublishPress\StatusCapabilities')) {
		$status_caps_package = apply_filters('publishpress_status_capabilities_library', false);

		if (is_object($status_caps_package) && isset($status_caps_package->path) && file_exists($status_caps_package->path)) {
			require_once($status_caps_package->path);

			if (class_exists('PublishPress\StatusCapabilities')) {
				\PublishPress\StatusCapabilities::instance();
			}
		}
	}
});

add_filter('pp_custom_status_list', 'cme_filter_custom_status_list', 10, 2);

/**
 * Filters the list of custom statuses
 *
 * @param array   $custom_statuses
 * @param WP_Post $post
 *
 * @return  array
 */
function cme_filter_custom_status_list($custom_statuses, $post)
{
	if (!get_option('cme_custom_status_control') && !(\PublishPress\Capabilities\Pro::presspermitStatusControlActive())) {
		return $custom_statuses;
	}

	if (!class_exists('publishpress') || !method_exists('publishpress', 'instance')) {
		global $publishpress;
	} else {
		$publishpress = publishpress::instance();
	}

	if (empty($publishpress)) {
		return $custom_statuses;
	}

	$filtered       = [];
	$option_group   = 'global';

	$default_status = !empty($publishpress->custom_status->module->options->default_status) ? $publishpress->custom_status->module->options->default_status : 'draft';

	if ( ! is_null($post)) {
		// Adding a new post? Set the correct default status
		if ('auto-draft' === $post->post_status) {
			$post->post_status = $default_status;
		}
	}

	foreach ($custom_statuses as &$status) {
		$slug = str_replace('-', '_', $status->slug);

		// Check if the user, or any of his user groups are capable to use the status. If not, but it is the
		// current status, we still display it.
		if (in_array($slug, ['draft', 'draft-revision'])
			|| current_user_can('status_change_' . $slug)
			|| (is_null($post) ? false : $status->slug === $post->post_status)
			|| $status->slug === $default_status
		) {
			$filtered[] = $status;
		}
	}

	return $filtered;
}

function publishpress_caps_pro() {
    return \PublishPress\Capabilities\Pro::instance();
}
