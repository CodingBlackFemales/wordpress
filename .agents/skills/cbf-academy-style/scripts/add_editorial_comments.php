<?php
/**
 * Add review notes to posts as PublishPress editorial comments.
 *
 * Editorial comments sit on the post, not in its content, and the whole
 * editorial team sees them in the Editorial Comments box on the post and on
 * the Planner's Editorial Comments screen. This inserts them the way
 * PublishPress does (comment_type and comment_approved both
 * "editorial-comment"), but doesn't fire its notification hook, so adding
 * notes sends no email.
 *
 * Usage, from the repository root:
 *   wp @<env> eval-file .agents/skills/cbf-academy-style/scripts/add_editorial_comments.php <notes.json> <user-login>
 *
 * notes.json is a list of {"post": <id>, "content": "<text>"} objects. Start
 * each note with "Agent review", name the section it refers to, and say what
 * was uncertain and what was done. Allowed markup: <a href>, <strong>, <em>.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this with `wp eval-file`.\n" );
	return;
}

if ( count( $args ) < 2 ) {
	fwrite( STDERR, "Usage: wp eval-file add_editorial_comments.php <notes.json> <user-login>\n" );
	return;
}

$cbf_notes = json_decode( (string) file_get_contents( $args[0] ), true );
if ( ! is_array( $cbf_notes ) ) {
	fwrite( STDERR, "Cannot read a list of notes from {$args[0]}.\n" );
	return;
}

$cbf_user = get_user_by( 'login', $args[1] );
if ( ! $cbf_user ) {
	fwrite( STDERR, "No user with the login {$args[1]}.\n" );
	return;
}

foreach ( $cbf_notes as $cbf_note ) {
	$cbf_post_id = (int) ( $cbf_note['post'] ?? 0 );
	$cbf_content = trim( (string) ( $cbf_note['content'] ?? '' ) );

	if ( ! get_post( $cbf_post_id ) || '' === $cbf_content ) {
		fwrite( STDERR, "Skipped a note: post {$cbf_post_id} not found, or the note is empty.\n" );
		continue;
	}

	$cbf_now = current_time( 'mysql' );
	$cbf_id  = wp_insert_comment(
		array(
			'comment_post_ID'      => $cbf_post_id,
			'comment_author'       => $cbf_user->display_name,
			'comment_author_email' => $cbf_user->user_email,
			'comment_author_url'   => $cbf_user->user_url,
			'comment_content'      => wp_kses(
				$cbf_content,
				array(
					'a'      => array( 'href' => array() ),
					'strong' => array(),
					'em'     => array(),
				)
			),
			'comment_type'         => 'editorial-comment',
			'comment_approved'     => 'editorial-comment',
			'comment_parent'       => 0,
			'user_id'              => $cbf_user->ID,
			'comment_date'         => $cbf_now,
			'comment_date_gmt'     => get_gmt_from_date( $cbf_now ),
		)
	);

	echo $cbf_id ? "Added editorial comment {$cbf_id} to post {$cbf_post_id}.\n" : "Failed to add a comment to post {$cbf_post_id}.\n";
}
