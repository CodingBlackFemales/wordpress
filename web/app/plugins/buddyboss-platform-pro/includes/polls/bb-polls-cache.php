<?php
/**
 * Functions related to the BuddyBoss Poll Cache.
 *
 * @package BuddyBossPro
 *
 * @since   2.6.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bb_poll_after_add_poll', 'bb_poll_cache_clear' );
add_action( 'bb_poll_after_remove_poll', 'bb_poll_cache_clear_after_delete_poll', 10, 2 );
add_action( 'bb_poll_after_add_poll_option', 'bb_poll_options_cache_clear', 10, 2 );
add_action( 'bb_poll_after_remove_poll_options', 'bb_poll_options_cache_clear_after_remove_option', 10, 3 );
add_action( 'bb_poll_after_add_poll_vote', 'bb_poll_votes_cache_clear', 10, 2 );
add_action( 'bb_poll_after_remove_poll_votes', 'bb_poll_options_cache_clear_after_remove_poll_votes', 10, 3 );

/**
 * Function to clear the cache after adding a poll.
 *
 * @since 2.6.00
 *
 * @param int $poll_id Poll id.
 *
 * @return void
 */
function bb_poll_cache_clear( $poll_id ) {
	if ( ! empty( $poll_id ) ) {
		wp_cache_delete( $poll_id, 'bb_poll' );
	}
}

/**
 * Function to clear the cache after removing a poll.
 *
 * @since 2.6.00
 *
 * @param int $deleted  Poll id.
 * @param int $get_poll Poll data.
 *
 * @return void
 */
function bb_poll_cache_clear_after_delete_poll( $deleted, $get_poll ) {
	if ( isset( $get_poll->id ) ) {
		wp_cache_delete( $get_poll->id, 'bb_poll' );
	}
}

/**
 * Function to clear the cache after adding a poll option.
 *
 * @since 2.6.00
 *
 * @param int   $option_id Poll option id.
 * @param array $args      Arguments of an array.
 *
 * @return void
 */
function bb_poll_options_cache_clear( $option_id, $args ) {
	// Clear the cache.
	bp_core_reset_incrementor( 'bb_poll_options' );
	if ( ! empty( $option_id ) ) {
		wp_cache_delete( $option_id, 'bb_poll_options' );
	}
}

/**
 * Function to clear the cache after removing a poll option.
 *
 * @since 2.6.00
 *
 * @param int   $deleted      Poll option id.
 * @param array $args         Arguments of an array.
 * @param array $poll_options Poll options.
 *
 * @return void
 */
function bb_poll_options_cache_clear_after_remove_option( $deleted, $args, $poll_options ) {
	// Clear the cache.
	bp_core_reset_incrementor( 'bb_poll_options' );
	if ( ! empty( $args['id'] ) ) {
		wp_cache_delete( $args['id'], 'bb_poll_options' );
	}
}

/**
 * Function to clear the cache after adding a poll vote.
 *
 * @since 2.6.00
 *
 * @param int   $vote_id Poll vote id.
 * @param array $args    Arguments of an array.
 *
 * @return void
 */
function bb_poll_votes_cache_clear( $vote_id, $args ) {
	// Clear the cache.
	bp_core_reset_incrementor( 'bb_poll_votes' );
	if ( ! empty( $vote_id ) ) {
		wp_cache_delete( $vote_id, 'bb_poll_votes' );
	}
}

/**
 * Function to clear the cache after removing a poll vote.
 *
 * @since 2.6.00
 *
 * @param int   $deleted    Poll vote id.
 * @param array $args       Arguments of an array.
 * @param array $poll_votes Poll votes.
 *
 * @return void
 */
function bb_poll_options_cache_clear_after_remove_poll_votes( $deleted, $args, $poll_votes ) {
	// Clear the cache.
	bp_core_reset_incrementor( 'bb_poll_votes' );
	if ( ! empty( $args['id'] ) ) {
		wp_cache_delete( $args['id'], 'bb_poll_votes' );
	}
}
