<?php

/**
 * Single Topic Content Part
 *
 * @package bbPress
 * @subpackage Theme
 */

$topic_id   = bbp_get_topic_id();
$query_page = get_query_var( 'paged' );

$topic_reply_count = 0;
if ( bbp_show_lead_topic() ) {
	$topic_reply_count = bbp_get_topic_reply_count( $topic_id );
} else {
	$topic_reply_count = bbp_get_topic_post_count( $topic_id );
}


?>

<div id="bbpress-forums" class="bb-content-area bs-replies-wrapper">
	<div class="bb-grid">

		<div class="replies-content">
			<?php bbp_breadcrumb(); ?>

			<?php do_action( 'bbp_template_before_single_topic' ); ?>

			<?php if ( post_password_required() ) : ?>

				<?php bbp_get_template_part( 'form', 'protected' ); ?>

			<?php else : ?>

				<?php bbp_topic_tag_list(); ?>

				<?php if ( bbp_show_lead_topic() ) : ?>

					<?php bbp_get_template_part( 'content', 'single-topic-lead' ); ?>

				<?php endif; ?>

				<?php
				if ( bbp_has_replies() ) :
					// bbp_get_template_part( 'pagination', 'replies' );

					bbp_get_template_part( 'loop', 'replies' );

					bbp_get_template_part( 'pagination', 'replies' );
				else :
					bbp_get_template_part( 'loop', 'replies' );
					bbp_get_template_part( 'feedback', 'no-replies' );
				endif;
				?>

				<p class="bb-topic-reply-link-wrap mobile-only">
					<?php
					bbp_topic_reply_link();
					if ( ! bbp_current_user_can_access_create_reply_form() && ! bbp_is_topic_closed() && ! bbp_is_forum_closed( bbp_get_topic_forum_id() ) && ! is_user_logged_in() ) {
						?>
						<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bbp-topic-login-link bb-style-primary-bgr-color bb-style-border-radius"><?php esc_html_e( 'Log In to Reply', 'buddyboss-theme' ); ?></a>
					<?php } ?>
				</p>
				<p class="bb-topic-subscription-link-wrap mobile-only">
					<?php
					$args = array( 'before' => '' );
					echo bbp_get_topic_subscription_link( $args );
					?>
				</p>
				<?php
				if ( bbp_is_favorites_active() ) {
					?>
					<p class="bb-topic-favorite-link-wrap mobile-only">
						<?php
						$args = array( 'before' => '' );
						echo bbp_get_topic_favorite_link( $args );
						?>
					</p>
					<?php
				}

				bbp_get_template_part( 'form', 'reply' );
				?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_single_topic' ); ?>

		</div>

		<div class="bb-sm-grid bs-single-topic-sidebar">
			<div class="bs-topic-sidebar-inner">
				<div class="single-topic-sidebar-links">
					<p class="bb-topic-reply-link-wrap">
						<?php
						bbp_topic_reply_link();
						if ( ! bbp_current_user_can_access_create_reply_form() && ! bbp_is_topic_closed() && ! bbp_is_forum_closed( bbp_get_topic_forum_id() ) && ! is_user_logged_in() ) {
							?>
							<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bbp-topic-login-link bb-style-primary-bgr-color bb-style-border-radius"><?php esc_html_e( 'Log In to Reply', 'buddyboss-theme' ); ?></a>
						<?php } ?>
					</p>
					<p class="bb-topic-subscription-link-wrap">
					<?php
					$args = array( 'before' => '' );
					echo bbp_get_topic_subscription_link( $args );
					?>
					</p>
					<?php
					if ( bbp_is_favorites_active() ) {
						?>
						<p class="bb-topic-favorite-link-wrap">
							<?php
							$args = array( 'before' => '' );
							echo bbp_get_topic_favorite_link( $args );
							?>
						</p>
						<?php
					}
					?>
				</div>

				<?php
				$bbp = bbpress();
				if ( property_exists( $bbp->reply_query, 'total_items_per_page' ) ) {

					if ( $bbp->reply_query->offset > 0 ) {
						$from_num = $bbp->reply_query->offset + 1;
					} else {
						$from_num = intval( ( $bbp->reply_query->paged - 1 ) * $bbp->reply_query->total_items_per_page ) + 1;
					}

					$to_num = intval( $from_num + ( $bbp->reply_query->total_items_per_page - 1 ) > $bbp->reply_query->found_posts ) ? $bbp->reply_query->found_posts : $from_num + ( $bbp->reply_query->total_items_per_page - 1 );
				} else {
					$from_num = intval( ( $bbp->reply_query->paged - 1 ) * $bbp->reply_query->posts_per_page ) + 1;
					$to_num   = intval( $from_num + ( $bbp->reply_query->posts_per_page - 1 ) > $bbp->reply_query->found_posts ) ? $bbp->reply_query->found_posts : $from_num + ( $bbp->reply_query->posts_per_page - 1 );
				}
				?>
				<div class="scrubber light" id="scrubber" data-key="<?php echo esc_attr( buddyboss_unique_id( 'forums_scrubber_' ) ); ?>" data-total-item="<?php echo esc_attr( $topic_reply_count ); ?>" data-total-page="<?php echo esc_attr( $bbp->reply_query->total_pages ); ?>" data-current-page="<?php echo esc_attr( $bbp->reply_query->paged ); ?>" data-from="<?php echo esc_attr( $from_num ); ?>" data-to="<?php echo esc_attr( $to_num ); ?>">
					<a href="#" class="firstpostbtn">
						<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11">
							<path fill="none" stroke="#C8CBCF" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.44" d="M1 10l4.5-4 4.5 4M1 5l4.5-4L10 5"/>
						</svg>
						<span><?php esc_html_e( 'Start of Discussion', 'buddyboss-theme' ); ?></span>
					</a>
					<div class="reply-timeline-container" id="reply-timeline-container">
						<div class="handle" id="handle">
							<span id="currentpost">0</span> <?php esc_html_e( 'of', 'buddyboss-theme' ); ?> <span id="totalposts">0</span> <?php esc_html_e( 'replies', 'buddyboss-theme' ); ?>
							<span class="desc" id="date"><?php esc_html_e( 'June 2018', 'buddyboss-theme' ); ?></span>
						</div>
					</div>
					<a href="#" class="lastpostbtn">
						<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11">
							<path fill="none" stroke="#C8CBCF" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.44" d="M1 1l4.5 4L10 1M1 6l4.5 4L10 6"/>
						</svg>
						<span><?php esc_html_e( 'Now', 'buddyboss-theme' ); ?></span>
					</a>
				</div>
			</div>
		</div>

	</div>

</div>
