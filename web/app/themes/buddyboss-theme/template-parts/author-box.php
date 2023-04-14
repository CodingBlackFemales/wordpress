<?php 
$author_box = buddyboss_theme_get_option( 'blog_author_box' );

if ( is_single() && !is_related_posts() && is_singular('post') ) : ?>
	<div class="post-author-info">
        <?php if ( !empty( $author_box ) ) :
		$description = get_the_author_meta( 'description' );

		$platform_author_link = buddyboss_theme_get_option( 'blog_platform_author_link' );
		if ( function_exists( 'bp_core_get_user_domain' ) && $platform_author_link ) {
			$user_link = bp_core_get_user_domain( get_the_author_meta( 'ID' ) );
		} else {
			$user_link = get_author_posts_url( get_the_author_meta( 'ID' ) );
		}
		$class = empty( $description ) ? 'align-items-center' : '';
		?>
		<div class="post-author-details <?php echo $class; ?>">
			<a href="<?php echo $user_link; ?>">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
			</a>
			<div class="author-desc-wrap">
				<a class="post-author" href="<?php echo $user_link; ?>"><?php the_author(); ?></a>
				<?php if( !empty($description) ) { ?>
					<div class="author-desc"><?php the_author_meta( 'description' ); ?></div>
				<?php } ?>
			</div>
		</div>
        <?php endif; ?>
	</div><!--.post-author-info-->
<?php endif; ?>
