<?php
/**
 * Grid 2 card layout file.
 *
 * @since 4.21.4
 * @version 4.22.0
 *
 * @package LearnDash\Course_Grid
 */

use LearnDash\Core\Utilities\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="item grid-2">
	<article id="post-<?php echo esc_attr( $post->ID ); ?>" <?php post_class( 'post', $post->ID ); ?>>
		<?php if ( $atts['thumbnail'] == true ) : ?>
			<div class="thumbnail">
				<?php if ( $atts['ribbon'] == true && ! empty( $ribbon_text ) ) : ?>
					<div class="<?php echo esc_attr( $ribbon_class ); ?>">
						<?php echo esc_html( $ribbon_text ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $video == true ) : ?>
					<div class="video">
						<?php echo htmlspecialchars_decode( $video_embed_code ); ?>
					</div>
				<?php elseif ( has_post_thumbnail( $post->ID ) ) : ?>
					<div class="image">
						<a
							aria-hidden="true"
							href="<?php echo esc_url( $button_link ); ?>"
							tabindex="-1"
						>
							<?php echo get_the_post_thumbnail( $post->ID, $atts['thumbnail_size'], [ 'alt' => $title ] ); ?>
						</a>
					</div>
				<?php elseif ( ! has_post_thumbnail( $post->ID ) ) : ?>
					<div class="image">
						<a
							aria-hidden="true"
							href="<?php echo esc_url( $button_link ); ?>"
							tabindex="-1"
						>
							<img
								alt="<?php echo esc_attr( $title ); ?>"
								src="<?php echo esc_url( LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/thumbnail.jpg' ); ?>"
							/>
						</a>
					</div>
				<?php endif; ?>

				<div
					aria-hidden="true"
					class="arrow button"
					tabindex="-1"
				>
					<a href="<?php echo esc_url( $button_link ); ?>">
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $atts['content'] == true ) : ?>
			<div class="content">
				<?php if ( $atts['post_meta'] == true ) : ?>
					<div class="meta">
						<?php if ( $author ) : ?>
							<div class="author">
								<img class="avatar" src="<?php echo esc_url( $author['avatar'] ); ?>" alt="<?php echo $author['name']; ?>">
								<div class="wrapper">
									<span class="name"><?php echo esc_html( $author['name'] ); ?></span>
									<span class="inner-wrapper">
										<?php if ( $duration ) : ?>
											<span class="duration"><?php printf( __( 'Duration: %s', 'learndash' ), $duration ); ?></span>
										<?php endif; ?>
										<?php if ( $lessons ) : ?>
											<span class="lessons"><?php printf( __( '%d Lessons', 'learndash' ), $lessons['count'] ); ?></span>
										<?php endif; ?>
									</span>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $atts['title'] == true ) : ?>
					<h3 class="entry-title">
						<?php if ( $atts['title_clickable'] == true ) : ?>
							<a href="<?php echo esc_url( $button_link ); ?>">
						<?php endif; ?>
							<?php echo esc_html( $title ); ?>
						<?php if ( $atts['title_clickable'] == true ) : ?>
							</a>
						<?php endif; ?>
					</h3>
				<?php endif; ?>
				<?php if ( $atts['description'] == true && ! empty( $description ) ) : ?>
					<div class="entry-content">
						<?php echo wp_kses( $description, 'post' ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $atts['progress_bar'] == true && defined( 'LEARNDASH_VERSION' ) ) : ?>
					<?php if ( $post->post_type == 'sfwd-courses' ) : ?>
						<?php echo do_shortcode( '[learndash_course_progress course_id="' . $post->ID . '" user_id="' . $user_id . '"]' ); ?>
					<?php elseif ( $post->post_type == 'groups' ) : ?>
						<div class="learndash-wrapper learndash-widget">
						<?php $progress = learndash_get_user_group_progress( $post->ID, $user_id ); ?>
						<?php
						learndash_get_template_part(
							'modules/progress-group.php',
							array(
								'context'  => 'group',
								'user_id'  => $user_id,
								'group_id' => $post->ID,
							),
							true
						);
						?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $atts['button'] == true ) : ?>
					<div class="button">
						<a
							aria-label="<?php echo esc_attr( trim( wp_strip_all_tags( $button_text ) ) . ': ' . $title ); ?>"
							href="<?php echo esc_url( $button_link ); ?>"
						>
							<?php echo wp_kses( $button_text, Sanitize::extended_kses() ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</article>
</div>
