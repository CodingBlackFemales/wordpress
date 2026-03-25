<?php
/**
 * Functions related to the MemberpressLMS group course WP Cache.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cache incrementor.
 *
 * @since 2.6.30
 */
function bb_meprlms_reset_cache_incrementor_for_group_course() {
	bp_core_reset_incrementor( 'bb_meprlms' );
}
add_action( 'bb_meprlms_before_add_group_course', 'bb_meprlms_reset_cache_incrementor_for_group_course' );
add_action( 'bb_meprlms_after_add_group_course', 'bb_meprlms_reset_cache_incrementor_for_group_course' );
