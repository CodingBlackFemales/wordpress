<?php
/**
 * Functions related to the TutorLMS group course WP Cache.
 *
 * @since   2.4.40
 *
 * @package BuddyBossPro/Integration/TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function bb_tutorlms_reset_cache_incrementor_for_group_course() {
	bp_core_reset_incrementor( 'bb_tutorlms' );
}
add_action( 'bb_tutorlms_before_add_group_course', 'bb_tutorlms_reset_cache_incrementor_for_group_course' );
add_action( 'bb_tutorlms_after_add_group_course', 'bb_tutorlms_reset_cache_incrementor_for_group_course' );
