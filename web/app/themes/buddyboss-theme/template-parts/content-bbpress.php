<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package BuddyBoss_Theme
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php
	$class = '';
	if ( bbp_is_single_forum() && bbp_has_forums() ) {
		$class = 'has-subforums';
	}
	if ( ! empty( $post->post_parent ) && bbp_is_single_forum() ) {
		$class .= 'has-parent-forums';
	}
	$forum_cover_photo = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
	if ( bbp_is_single_forum() ) :
		if ( ! empty( $forum_cover_photo ) ) {
			?>
			<div class="bb-topic-banner-container bb-topic-banner">
				<img src="<?php echo esc_url( $forum_cover_photo ); ?>" alt="<?php the_title_attribute( array( 'post' => $post->ID ) ); ?>" class="banner-img wp-post-image"/>
				<header class="entry-header container bb-single-forum <?php echo esc_attr( $class ); ?>">
					<h1 class="entry-title"><?php esc_html( bbp_forum_title() ); ?></h1>
					<?php if ( ! bp_is_group_single() ) { ?>
						<div class="bbp-forum-content-wrap"><?php echo wp_kses_post( bbp_get_forum_content_excerpt_view_more() ); ?></div>
					<?php } ?>
				</header> <!--.entry-header -->
			</div>
			<?php
		} else {
			?>
			<header class="entry-header bb-single-forum <?php echo esc_attr( $class ); ?>">
				<h1 class="entry-title"><?php esc_html( bbp_forum_title() ); ?></h1>
			</header> <!--.entry-header -->
			<?php
		}
	endif;
	?>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'buddyboss-theme' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->

	<?php if ( get_edit_post_link() ) : ?>
		<footer class="entry-footer">
			<?php
			edit_post_link(
				sprintf(
					wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Edit <span class="screen-reader-text">%s</span>', 'buddyboss-theme' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				),
				'<span class="edit-link">',
				'</span>'
			);
			?>
		</footer><!-- .entry-footer -->
	<?php endif; ?>

</article>
