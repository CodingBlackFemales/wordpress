<?php
global $post;

if ( 'sfwd-courses' != $post->post_type ) {
	return;
}
$course = $post;
?>

<?php if ( class_exists( 'BuddyPress' ) ) { ?>
	
    <?php if( buddyboss_theme_get_option('learndash_course_author') ) { ?>
	<?php $is_author_info = (buddyboss_theme_get_option('learndash_course_author_info')) ? 'bb-about-instructor--is-info' : 'bb-about-instructor--no-info'; ?>
	<div class="bb-about-instructor <?php echo $is_author_info; ?>">
		<h4><?php _e( 'About Instructor', 'buddyboss-theme' ); ?></h4>
		<div class="bb-grid">
            <div class="bb-instructor-wrap flex">
                <div class="bb-avatar-wrap">
    				<div>
						<?php if ( class_exists( 'BuddyPress' ) ) { ?>
						<a href="<?php echo bp_core_get_user_domain( get_the_author_meta( 'ID', $post->post_author ) ); ?>">
						<?php } else { ?>
							<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID', $post->post_author ), get_the_author_meta( 'user_nicename', $post->post_author ) ); ?>">
						<?php } ?>
							<?php echo get_avatar( get_the_author_meta( 'ID', $course->post_author ), 300, '', '', array('class' => array('round', 'avatar'))  ); ?>
						</a>
    				</div>
    			</div>
    			<div class="bb-content-wrap">
    				<h5>
                        <?php if ( class_exists( 'BuddyPress' ) ) { ?>
        				<a href="<?php echo bp_core_get_user_domain( get_the_author_meta( 'ID', $post->post_author ) ); ?>">
            			<?php } else { ?>
            			     <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID', $post->post_author ), get_the_author_meta( 'user_nicename', $post->post_author ) ); ?>">
            			<?php } ?>
                            <?php echo get_the_author_meta( 'display_name', $course->post_author ); ?>
                        </a>
                    </h5>
					<?php if( buddyboss_theme_get_option('learndash_course_author_info') ) { ?>
						<p class="bb-author-info"><?php echo get_the_author_meta( 'description', $course->post_author ); ?></p>
					<?php } ?>
    				<p class="bb-author-meta"><?php echo count_user_posts( get_the_author_meta( 'ID', $post->post_author ), 'sfwd-courses' ); ?> <?php echo count_user_posts( get_the_author_meta( 'ID', $post->post_author ), 'sfwd-courses' ) > 1 ? LearnDash_Custom_Label::get_label( 'courses' ) : LearnDash_Custom_Label::get_label( 'course' ); ?></p>
    			</div>
            </div>
            <div class="bb-instructor-message">
                <?php
                if ( bp_is_active( 'messages' ) && ( ( function_exists('bp_force_friendship_to_message') && ! bp_force_friendship_to_message() ) ||
                           ( function_exists('bp_force_friendship_to_message') && bp_force_friendship_to_message() && bp_is_active( 'friends' ) && friends_check_friendship( bp_loggedin_user_id(), $course->post_author ) ) ) && is_user_logged_in() && ( get_current_user_id() != get_the_author_meta( 'ID', $course->post_author ) ) ) { ?>
					<a href="<?php echo apply_filters( 'bp_get_send_private_message_link', wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username( $course->post_author ) ) ); ?>" class="button small push-bottom"><i class="bb-icon-l bb-icon-comment"></i><?php _e( 'Message', 'buddyboss-theme' ); ?></a>
				<?php } ?>
            </div>
		</div>
	</div>
    <?php } ?>

<?php } ?>