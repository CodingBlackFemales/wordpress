<?php
/**
 * BuddyBoss Platform Pro Activity Functions.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the BBActivityPostFeatureImage class.
 *
 * @since 2.9.0
 *
 * @return BB_Activity_Post_Feature_Image|false Returns BB_Activity_Post_Feature_Image instance or false if class doesn't exist.
 */
function bb_pro_activity_post_feature_image_instance() {
	if ( class_exists( 'BB_Activity_Post_Feature_Image' ) ) {
		return BB_Activity_Post_Feature_Image::instance();
	}

	return false;
}
