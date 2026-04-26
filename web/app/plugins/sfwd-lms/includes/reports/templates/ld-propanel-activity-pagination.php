<?php
/**
 * Activity Pagination.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @var array<mixed> $post_args This is the filtered version of the _$GET vars passed in from AJAX
 * @var array<mixed> $activity_query_args Another array build from the $post_args. Used to call the LD reporting functions to query activity.
 * @var array{
 *     pager: array{
 *         current_page: int,
 *         total_pages: int,
 *         total_items: int,
 *     }
 * } $activities Query results. Contains elements 'results', 'pager, etc.
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

use LearnDash\Core\Utilities\Cast;

if ( $activities['pager']['total_items'] > 0 ) {
	if ( $activities['pager']['current_page'] == 1 ) {
		$pager_left_disabled = ' disabled="disabled" ';
	} else {
		$pager_left_disabled = '';
	}

	if ( $activities['pager']['current_page'] == $activities['pager']['total_pages'] ) {
		$pager_right_disabled = ' disabled="disabled" ';
	} else {
		$pager_right_disabled = '';
	}
	?>
	<p class="ld-propanel-reporting-pager-info">
		<button class="button button-simple first" data-page="1" title="<?php esc_attr_e( 'First Page', 'learndash' ); ?>" <?php echo esc_attr( $pager_left_disabled ); ?>>&laquo;</button>

		<button class="button button-simple prev" data-page="<?php echo esc_attr( Cast::to_string( ( $activities['pager']['current_page'] > 1 ) ? $activities['pager']['current_page'] - 1 : 1 ) ); ?>" title="<?php esc_attr_e( 'Previous Page', 'learndash' ); ?>" <?php echo esc_attr( $pager_left_disabled ); ?> >&lsaquo;</button>

		<span><?php esc_html_e( 'page', 'learndash' ); ?> <span class="pagedisplay"><span class="current_page"><?php echo esc_attr( Cast::to_string( $activities['pager']['current_page'] ) ); ?></span> / <span class="total_pages"><?php echo esc_html( Cast::to_string( $activities['pager']['total_pages'] ) ); ?></span> (<span class="total_items"><?php echo esc_html( Cast::to_string( $activities['pager']['total_items'] ) ); ?></span>)</span></span>

		<button class="button button-simple next" data-page="<?php echo esc_attr( Cast::to_string( ( $activities['pager']['current_page'] < $activities['pager']['total_pages'] ) ? $activities['pager']['current_page'] + 1 : $activities['pager']['total_pages'] ) ); ?>" title="<?php esc_attr_e( 'Next Page', 'learndash' ); ?>" <?php echo esc_attr( $pager_right_disabled ); ?> >&rsaquo;</button>

		<button class="button button-simple last" data-page="<?php echo esc_attr( Cast::to_string( $activities['pager']['total_pages'] ) ); ?>" title="<?php esc_attr_e( 'Last Page', 'learndash' ); ?>" <?php echo esc_attr( $pager_right_disabled ); ?> >&raquo;</button>
	</p>
	<?php
}
