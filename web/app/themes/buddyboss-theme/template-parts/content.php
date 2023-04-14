<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package BuddyBoss_Theme
 */
?>

<?php
global $post;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php if ( ! is_single() || is_related_posts() ) { ?>
		<div class="post-inner-wrap">
	<?php } ?>

	<?php
	if ( ( ! is_single() || is_related_posts() ) && function_exists( 'buddyboss_theme_entry_header' ) ) {
		buddyboss_theme_entry_header( $post );
	}
	?>

	<div class="entry-content-wrap primary-entry-content">
		<?php
		$featured_img_style = buddyboss_theme_get_option( 'blog_featured_img' );

		if ( ! empty( $featured_img_style ) && $featured_img_style == 'full-fi-invert' ) {

			if ( is_single() && ! is_related_posts() ) {
				if ( has_post_thumbnail() ) {
					?>
					<figure class="entry-media entry-img bb-vw-container1">
						<?php the_post_thumbnail( 'large', array( 'sizes' => '(max-width:768px) 768px, (max-width:1024px) 1024px, (max-width:1920px) 1920px, 1024px' ) ); ?>
					</figure>
					<?php
				}
			}

			if ( has_post_format( 'link' ) && ( ! is_singular() || is_related_posts() ) ) {
				echo '<span class="post-format-icon"><i class="bb-icon-l bb-icon-link"></i></span>';
			}

			if ( has_post_format( 'quote' ) && ( ! is_singular() || is_related_posts() ) ) {
				echo '<span class="post-format-icon white"><i class="bb-icon-l bb-icon-quote-left"></i></span>';
			}

			if ( ! is_singular( 'lesson' ) && ! is_singular( 'llms_assignment' ) && ! is_singular( 'sfwd-lessons' ) ) :
				?>

				<header class="entry-header">
					<?php
					if ( is_singular() && ! is_related_posts() ) :
						the_title( '<h1 class="entry-title">', '</h1>' );
					else :
						$prefix = '';
						if ( has_post_format( 'link' ) ) {
							$prefix  = __( '[Link]', 'buddyboss-theme' );
							$prefix .= ' ';// whitespace.
						}
						the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $prefix, '</a></h2>' );
					endif;

					if ( has_post_format( 'link' ) && function_exists( 'buddyboss_theme_get_first_url_content' ) && ( $first_url = buddyboss_theme_get_first_url_content( $post->post_content ) ) != '' ) :
						?>
						<p class="post-main-link"><?php echo $first_url; ?></p>
					<?php endif; ?>
				</header><!-- .entry-header -->

				<?php
			endif;

			if ( ! is_singular() || is_related_posts() ) {
				?>
				<div class="entry-content">
					<?php
					if ( empty( $post->post_excerpt ) ) {
						the_excerpt();
					} else {
						echo bb_get_excerpt( $post->post_excerpt, 150 );
					}
					?>
				</div>
				<?php
			}

			if ( ( isset( $post->post_type ) && $post->post_type === 'post' ) || ( ! has_post_format( 'quote' ) && is_singular( 'post' ) ) ) :
				get_template_part( 'template-parts/entry-meta' );
			endif;

		} else {

			if ( has_post_format( 'link' ) && ( ! is_singular() || is_related_posts() ) ) {
				echo '<span class="post-format-icon"><i class="bb-icon-l bb-icon-link"></i></span>';
			}

			if ( has_post_format( 'quote' ) && ( ! is_singular() || is_related_posts() ) ) {
				echo '<span class="post-format-icon white"><i class="bb-icon-l bb-icon-quote-left"></i></span>';
			}

			if ( ! is_singular( 'sfwd-lessons' ) ) {
				?>
				<header class="entry-header">
					<?php
					if ( is_singular() && ! is_related_posts() ) :
						the_title( '<h1 class="entry-title">', '</h1>' );
					else :
						$prefix = '';
						if ( has_post_format( 'link' ) ) {
							$prefix  = __( '[Link]', 'buddyboss-theme' );
							$prefix .= ' ';// whitespace.
						}
						the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . esc_attr( $prefix ), '</a></h2>' );
					endif;

					if ( has_post_format( 'link' ) && function_exists( 'buddyboss_theme_get_first_url_content' ) ) :

						$first_url = buddyboss_theme_get_first_url_content( $post->post_content );

						if ( ! empty( $first_url ) ) {
							?>
								<p class="post-main-link"><?php echo esc_url( $first_url ); ?></p>
							<?php
						}

					endif;
					?>
				</header><!-- .entry-header -->
				<?php
			}

			if ( ! is_singular() || is_related_posts() ) {
				?>
				<div class="entry-content">
					<?php
					if ( empty( $post->post_excerpt ) ) {
						the_excerpt();
					} else {
						echo bb_get_excerpt( $post->post_excerpt, 150 );
					}
					?>
				</div>
				<?php
			}

			if ( ( isset( $post->post_type ) && 'post' === $post->post_type ) || ( ! has_post_format( 'quote' ) && is_singular( 'post' ) ) ) :
				get_template_part( 'template-parts/entry-meta' );
			endif;

			if ( is_single() && ! is_related_posts() ) {
				if ( has_post_thumbnail() ) {
					?>
					<figure class="entry-media entry-img bb-vw-container1">
						<?php
						if ( ! empty( $featured_img_style ) && $featured_img_style == 'full-fi' ) {
							the_post_thumbnail( 'large', array( 'sizes' => '(max-width:768px) 768px, (max-width:1024px) 1024px, (max-width:1920px) 1920px, 1024px' ) );
						} else {
							the_post_thumbnail( 'large' );
						}
						?>
					</figure>
					<?php
				}
			}
		}
		?>

		<?php if ( is_singular() && ! is_related_posts() ) { ?>
			<div class="entry-content">
			<?php
				the_content(
					sprintf(
						wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
							__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'buddyboss-theme' ),
							array(
								'span' => array(
									'class' => array(),
								),
							)
						),
						get_the_title()
					)
				);

				wp_link_pages(
					array(
						'before'      => '<nav class="page-links bb-page-links">',
						'after'       => '</nav>',
						'link_before' => '',
						'link_after'  => ''
					)
				);
			?>
			</div><!-- .entry-content -->
		<?php } ?>
	</div>

	<?php if ( ! is_single() || is_related_posts() ) { ?>
		</div><!--Close '.post-inner-wrap'-->
	<?php } ?>

</article><!-- #post-<?php the_ID(); ?> -->


<?php if ( is_single() ) { ?>
	<div class="post-meta-wrapper-main">

		<?php if ( has_category() || has_tag() ) { ?>
			<div class="post-meta-wrapper">
				<?php if ( has_category() ) : ?>
					<div class="cat-links">
						<i class="bb-icon-l bb-icon-folder"></i>
						<?php _e( 'Categories: ', 'buddyboss-theme' ); ?>
						<span><?php the_category( __( ', ', 'buddyboss-theme' ) ); ?></span>
					</div>
					<?php
				endif;
				if ( has_tag() ) :
					?>
					<div class="tag-links">
						<i class="bb-icon-l bb-icon-tags"></i>
						<?php _e( 'Tags: ', 'buddyboss-theme' ); ?>
						<?php the_tags( '<span>', __( ', ', 'buddyboss-theme' ), '</span>' ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php } ?>

		<div class="show-support">
			<?php if ( function_exists( 'bb_bookmarks' ) ) { ?>
				<h6><?php _e( 'Show your support', 'buddyboss-theme' ); ?></h6>
				<p>
					<?php
					printf(
						'<span class="authors-avatar vcard table-cell">%1$s</span>',
						sprintf( __( 'Liking shows how much you appreciated %1$s’s story.', 'buddyboss-theme' ), get_the_author() )
					);
					?>
				</p>
			<?php } ?>

			<div class="flex author-post-meta">
				<?php
				if ( is_user_logged_in() ) {

					if ( function_exists( 'bb_bookmarks' ) ) {
						echo bb_bookmarks()->action_button(
							array(
								'object_id'     => get_the_ID(),
								'object_type'   => 'post_' . get_post_type( get_the_ID() ),
								'action_type'   => 'like',
								'wrapper_class' => 'bb-like-wrap',
								'icon_class'    => 'bb-icon-l bb-icon-thumbs-up',
								'title_add'     => esc_html__( 'Like this entry', 'buddyboss-theme' ),
								'title_remove'  => esc_html__( 'Remove like', 'buddyboss-theme' ),
							)
						);
					}
				}
				?>
				<span class="pa-share-fix push-left"></span>

				<?php
				if ( comments_open() || get_comments_number() ) {
					?>
					<a data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'View Comments', 'buddyboss-theme' ); ?>" href="#comments" class="push-right"><i class="bb-icon-l bb-icon-comment-square"></i></a>
					<?php
				}

				if ( is_singular( 'post' ) ) :
					?>
					<div class="author-box-share-wrap">
						<a href="#" class="bb-share"><i class="bb-icon-l bb-icon-share-dots"></i></a>
						<div class="bb-share-container bb-share-author-box">
							<div class="bb-shareIcons"></div>
						</div>
					</div>
					<?php
				endif;

				if ( function_exists( 'bb_bookmarks' ) && is_user_logged_in() ) {
					echo bb_bookmarks()->action_button(
						array(
							'object_id'     => get_the_ID(),
							'object_type'   => 'post_' . get_post_type( get_the_ID() ),
							'action_type'   => 'bookmark',
							'wrapper_class' => 'bookmark-link-container',
							'icon_class'    => 'bb-bookmark bb-icon-l bb-icon-bookmark',
							'text_template' => '',
							'title_add'     => esc_html__( 'Bookmark this story to read later', 'buddyboss-theme' ),
							'title_remove'  => esc_html__( 'Remove bookmark', 'buddyboss-theme' ),
						)
					);
				}
				?>
			</div>
		</div>

	</div>
	<?php
}
get_template_part( 'template-parts/content-subscribe' );
get_template_part( 'template-parts/author-box' );
get_template_part( 'template-parts/related-posts' );
