<?php
/**
 * Activity Pagination Shortcode.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="activity-item pagination">
	<?php if ( 1 < $paged ) : ?>
		<a href="<?php echo add_query_arg( 'paged', intval( $paged ) - 1 ); ?>" class="prev" data-page="<?php echo $paged - 1; ?>">
							<?php
							printf(
							// translators: activity widget pagination previous page link
								esc_html_x( '&laquo; Previous', 'activity widget pagination previous page link', 'learndash' )
							);
							?>
		</a>
	<?php endif; ?>

	<?php if ( $paged != $activities['pager']['total_pages'] ) : ?>
		<a href="<?php echo add_query_arg( 'paged', intval( $paged ) + 1 ); ?>" class="next" data-page="<?php echo $paged + 1; ?>">
							<?php
							// translators: activity widget pagination next page link
							echo esc_html_x( 'Next &raquo;', 'activity widget pagination next page link', 'learndash' );
							?>
		</a>
	<?php endif; ?>

	<div class="clearfix"></div>
</div>
