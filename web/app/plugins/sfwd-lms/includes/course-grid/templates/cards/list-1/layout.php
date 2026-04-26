<?php
/**
 * List 1 card layout file.
 *
 * @since 4.21.4
 * @version 4.22.0
 *
 * @package LearnDash\Course_Grid
 *
 * cSpell:ignore buddicons
 */

use LearnDash\Core\Utilities\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="item list-1">
	<article id="post-<?php echo esc_attr( $post->ID ); ?>" <?php post_class( 'post', $post->ID ); ?>>
		<?php if ( $atts['thumbnail'] == true ) : ?>
			<div class="thumbnail">
			<?php if ( $atts['post_meta'] == true && ! empty( $reviews ) ) : ?>
				<div class="reviews">
					<span class="icon dashicons dashicons-star-filled"></span>
					<span class="number"><?php echo esc_html( $reviews ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $video == true && ! empty( $video_embed_code ) ) : ?>
				<div class="video">
					<?php echo wp_kses( $video_embed_code, 'learndash_course_grid_embed_code' ); ?>
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
			</div>
		<?php endif; ?>
		<div class="content">
			<?php if ( $atts['ribbon'] == true && ! empty( $ribbon_text ) ) : ?>
				<div class="ribbon">
					<?php echo esc_html( $ribbon_text ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $atts['post_meta'] == true && ! empty( $categories ) ) : ?>
				<div class="categories">
					<?php echo esc_html( $categories ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $atts['post_meta'] == true && ! empty( $author ) ) : ?>
				<div class="author">
					<img class="avatar" src="<?php echo esc_url( $author['avatar'] ); ?>" alt="<?php echo $author['name']; ?>">
					<span><?php printf( _x( 'By %s', 'By author name', 'learndash' ), '<span class="name">' . $author['name'] . '</span>' ); ?></span>
				</div>
			<?php endif; ?>
			<h3 class="entry-title">
				<?php if ( $atts['title_clickable'] == true ) : ?>
					<a href="<?php echo esc_url( $button_link ); ?>">
				<?php endif; ?>
					<?php echo esc_html( $title ); ?>
				<?php if ( $atts['title_clickable'] == true ) : ?>
					</a>
				<?php endif; ?>
			</h3>
			<?php if ( $atts['post_meta'] == true ) : ?>
				<div class="meta">
					<?php if ( $students ) : ?>
						<div class="students">
							<span class="icon dashicons dashicons-groups"></span>
							<span><?php printf( _nx( '%s Student', '%s Students', $students['count'], 'Total students', 'learndash' ), $students['count'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $duration ) : ?>
						<div class="duration">
							<span class="icon dashicons dashicons-clock"></span>
							<span><?php echo esc_html( $duration ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $lessons ) : ?>
						<div class="lessons">
							<span class="icon dashicons dashicons-text-page"></span>
							<span><?php printf( _nx( '%s Lesson', '%s Lessons', $lessons['count'], 'Total lessons', 'learndash' ), $lessons['count'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $forums ) : ?>
						<div class="forums">
						<span class="icon dashicons dashicons-buddicons-forums"></span>
							<span><?php printf( _nx( '%s Forum', '%s Forums', $forums['count'], 'Total forums', 'learndash' ), $forums['count'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $quizzes ) : ?>
						<div class="quizzes">
							<span class="icon dashicons dashicons-format-chat"></span>
							<span><?php printf( _nx( '%s Quiz', '%s Quizzes', $quizzes['count'], 'Total quizzes', 'learndash' ), $quizzes['count'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
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
				<div class="button-wrapper">
					<a
						aria-label="<?php echo esc_attr( trim( wp_strip_all_tags( $button_text ) ) . ': ' . $title ); ?>"
						class="link"
						href="<?php echo esc_url( $button_link ); ?>"
					>
						<?php echo wp_kses( $button_text, Sanitize::extended_kses() ); ?>
					</a>
					<div
						aria-hidden="true"
						class="button"
						tabindex="-1"
					>
						<div class="arrow">
							<a href="<?php echo esc_url( $button_link ); ?>">
								<span class="dashicons dashicons-arrow-right-alt"></span>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</article>
</div>
