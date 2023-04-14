<?php

/**
 * Replies Loop - Single Reply
 *
 * @package    bbPress
 * @subpackage Theme
 */

$reply_id        = bbp_get_reply_id();
$reply_author_id = bbp_get_reply_author_id( $reply_id );
$is_user_blocked = $is_user_suspended = false;
if ( bp_is_active( 'moderation' ) ) {
	$is_user_blocked   = bp_moderation_is_user_blocked( $reply_author_id );
	$is_user_suspended = bp_moderation_is_user_suspended( $reply_author_id );
}
?>

<div id="post-<?php bbp_reply_id(); ?>" <?php bbp_reply_class( bbp_get_reply_id(), array(
	'bs-reply-list-item bs-reply-suspended-block',
	'scrubberpost'
) ); ?> data-date="<?php echo get_post_time( 'F Y', false, bbp_get_reply_id(), true ); ?>">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<div class="flex align-items-center bs-reply-header bs-reply-suspended-header">

			<div class="bbp-reply-author item-avatar bp-suspended-avatar">
				<img class="avatar avatar-96 photo avatar-default" src="<?php echo get_avatar_url( $reply_author_id, 300 ); ?>" />
			</div><!-- .bbp-reply-author -->

			<div class="item-meta flex-1">
				<h3>
					<?php 
						$args = array( 'type' => 'name' );
						echo bbp_get_reply_author_link( $args );
					?>
				</h3>
			</div>
			
		</div>

		<div class="bbp-reply-content bs-forum-content bs-forum-suspended-content">

			<?php if ( $is_user_suspended ) {
				esc_html_e( 'This content has been hidden as the member is suspended.', 'buddyboss-theme' );
			} else if ( $is_user_blocked ) {
				esc_html_e( 'This content has been hidden as you have blocked this member.', 'buddyboss-theme' );
			} else {
				esc_html_e( 'This content has been hidden from site admin.', 'buddyboss-theme' );
			} ?>

		</div>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

</div><!-- .reply -->
