<?php
/**
 * Pagination for pages of forum index
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

	<div class="bbp-pagination">
        <?php if ( function_exists( 'bbp_forum_index_pagination_count' ) ) { ?>
		    <div class="bbp-pagination-count"><?php bbp_forum_index_pagination_count(); ?></div>
        <?php } ?>
        <?php if ( function_exists( 'bbp_forum_index_pagination_links' ) ) { ?>
		    <div class="bbp-pagination-links"><?php bbp_forum_index_pagination_links(); ?></div>
        <?php } ?>
	</div>


<?php do_action( 'bbp_template_after_pagination_loop' ); ?>