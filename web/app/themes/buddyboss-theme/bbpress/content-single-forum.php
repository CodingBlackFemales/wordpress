<?php

/**
 * Single Forum Content Part
 *
 * @package    bbPress
 * @subpackage Theme
 */

$class = '';

if ( bbp_is_single_forum() && bbp_has_forums() ) {
	$class = 'single-with-sub-forum ';
}

$forum_cover_photo = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
?>

<div id="bbpress-forums" class="<?php echo esc_attr( $class ); ?>">

	<?php bbp_breadcrumb(); ?>

	<?php if ( empty( $forum_cover_photo ) && bbp_is_single_forum() && ! bp_is_group_single() ) { ?>
		<div class="bbp-forum-content-wrap"><?php echo wp_kses_post( bbp_get_forum_content_excerpt_view_more() ); ?></div>
	<?php } ?>


	<?php do_action( 'bbp_template_before_single_forum' ); ?>

	<?php if ( post_password_required() ) : ?>

		<?php bbp_get_template_part( 'form', 'protected' ); ?>

	<?php else : ?>

		<?php if ( bbp_has_forums() ) : ?>
			<?php if ( bp_is_group_single() || bbp_is_single_forum() ) { ?>
				<div class="bp-group-single-forums">
				<hr>
				<h3 class="bb-sub-forum-title"><?php esc_html_e( 'Sub Forums', 'buddyboss-theme' ); ?></h3>
			<?php } ?>

			<?php bbp_get_template_part( 'loop', 'forums' ); ?>

			<?php if ( bp_is_group_single() || bbp_is_single_forum() ) { ?>
				<?php bbp_get_template_part( 'pagination', 'forums' ); ?>
				</div>
			<?php } ?>
		<?php endif; ?>

		<?php if ( ! bbp_is_forum_category() && bbp_has_topics() ) : ?>

			<?php bbp_get_template_part( 'loop', 'topics' ); ?>

			<?php bbp_get_template_part( 'pagination', 'topics' ); ?>

			<?php bbp_get_template_part( 'form', 'topic' ); ?>

		<?php elseif ( ! bbp_is_forum_category() ) : ?>

			<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>

			<?php bbp_get_template_part( 'form', 'topic' ); ?>

		<?php endif; ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_single_forum' ); ?>

</div>
