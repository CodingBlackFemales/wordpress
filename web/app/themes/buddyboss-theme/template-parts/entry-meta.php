<?php
/**
 * Get the Author link for the Post
 */
$platform_author_link = buddyboss_theme_get_option( 'blog_platform_author_link' );
$author_name = get_the_author_meta( 'user_nicename' );

if ( function_exists( 'bp_core_get_user_domain' ) && $platform_author_link ) {
    $author_link = bp_core_get_user_domain( get_the_author_meta( 'ID' ), $author_name );
} else {
    $author_link = get_author_posts_url( get_the_author_meta( 'ID' ), $author_name );
}

$author_link = esc_url( $author_link );

?>
<div class="entry-meta">
	<div class="bb-user-avatar-wrap">
		<div class="avatar-wrap">
			<a href="<?php echo $author_link; ?>">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 80, '', $author_name ); ?>
			</a>
		</div>
		<div class="meta-wrap">
			<a class="post-author" href="<?php echo $author_link; ?>">
				<?php the_author(); ?>
			</a>
			<span class="post-date" ><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo get_the_date(); ?></a></span>
		</div>
	</div>
	<div class="push-right flex align-items-center top-meta">
			<?php if ( is_single() ) { ?>
				<?php 
				if ( is_user_logged_in() ) { ?>
					<?php
						if ( function_exists( 'bb_bookmarks' ) ) {
							echo bb_bookmarks()->action_button( array(
								'object_id'		 => get_the_ID(),
								'object_type'	 => 'post_' . get_post_type( get_the_ID() ),
								'action_type'	 => 'like',
								'wrapper_class'	 => 'bb-like-wrap',
								'icon_class'	 => 'bb-icon-l bb-icon-thumbs-up',
								'text_template'	 => '{{bookmarks_count}} ' . __( 'Likes', 'buddyboss-theme' ),
								'title_add'		 => __( 'Like this entry', 'buddyboss-theme' ),
								'title_remove'	 => __( 'Remove like', 'buddyboss-theme' ),
							) );
						}
					?>
				<?php } ?>
                    <?php 
                    if ( comments_open() || get_comments_number() ) { ?>
                        <a href="<?php echo get_comments_link( get_the_ID() ); ?>" class="flex align-items-center bb-comments-wrap"><i class="bb-icon-l bb-icon-comment-square"></i><span class="comments-count"><?php printf( _nx( '1 <span class="bb-comment-text">Comment</span>', '%1$s <span class="bb-comment-text">Comments</span>', get_comments_number(), 'comments title', 'buddyboss-theme' ), number_format_i18n( get_comments_number() ) ); ?></span></a>
				<?php } ?>
			<?php } ?>
             

		<?php
		$blog_type = 'standard';
        $blog_type = apply_filters( 'bb_blog_type', $blog_type );

		if ( !is_single() && ( 'standard' === $blog_type || 'masonry' === $blog_type || 'grid' === $blog_type ) ) {
			?>

			<?php
			if ( function_exists( 'bb_bookmarks' ) && is_user_logged_in() ) {
				echo bb_bookmarks()->action_button( array(
					'object_id'		 => get_the_ID(),
					'object_type'	 => 'post_' . get_post_type( get_the_ID() ),
					'action_type'	 => 'like',
					'wrapper_class'	 => 'bb-like-wrap',
					'icon_class'	 => 'bb-icon-l bb-icon-thumbs-up',
					'text_template'	 => '{{bookmarks_count}} ' . __( 'Likes', 'buddyboss-theme' ),
					'title_add'		 => __( 'Like this entry', 'buddyboss-theme' ),
					'title_remove'	 => __( 'Remove like', 'buddyboss-theme' ),
				) );
			}
			?>

			<?php if ( comments_open() || get_comments_number() ) { ?>
				<a href="<?php echo get_comments_link( get_the_ID() ); ?>" class="flex align-items-center bb-comments-wrap">
					<i class="bb-icon-l bb-icon-comment-square"></i>
					<span class="comments-count"><?php printf( _nx( '1 <span class="bb-comment-text">Comment</span>', '%1$s <span class="bb-comment-text">Comments</span>', get_comments_number(), 'comments title', 'buddyboss-theme' ), number_format_i18n( get_comments_number() ) ); ?></span>
				</a>
			<?php } ?>

		<?php } ?>

        <?php
        if ( is_user_logged_in() ) { ?>
                <?php
				$tooltip_pos = (!is_single()) ? 'left' : 'up';
        		if ( function_exists( 'bb_bookmarks' ) && is_user_logged_in() ) {
        			echo bb_bookmarks()->action_button( array(
        				'object_id'		 => get_the_ID(),
        				'object_type'	 => 'post_' . get_post_type( get_the_ID() ),
        				'action_type'	 => 'bookmark',
        				'wrapper_class'	 => 'bookmark-link-container',
        				'icon_class'	 => 'bb-bookmark bb-icon-l bb-icon-bookmark',
        				'text_template'	 => '',
        				'title_add'		 => __( 'Bookmark this story to read later', 'buddyboss-theme' ),
        				'title_remove'	 => __( 'Remove bookmark', 'buddyboss-theme' ),
						'tooltip_pos'   => $tooltip_pos,
        			) );
        		}
        		?>
        <?php } ?>
	</div>
</div>
