<?php
/**
 * Reporting Widget Pagination.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>

<p class="ld-propanel-reporting-pager-info"><button class="button button-simple first" title="<?php esc_attr_e( 'First Page', 'learndash' ); ?>">&laquo;</button><button class="button button-simple prev" title="<?php esc_attr_e( 'Previous Page', 'learndash' ); ?>">&lsaquo;</button><span><?php esc_html_e( 'page', 'learndash' ); ?> <span class="pagedisplay"><span class="current_page"></span> / <span class="total_pages"></span> (<span class="total_items"></span>)</span></span><button class="button button-simple next" title="<?php esc_attr_e( 'Next Page', 'learndash' ); ?>">&rsaquo;</button><button class="button button-simple last" title="<?php esc_attr_e( 'Last Page', 'learndash' ); ?>">&raquo;</button></p>
