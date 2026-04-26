<?php
/**
 * View: PayPal Standard - Pagination Page Input.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var int $current_page Current page number.
 * @var int $total_pages  Total number of pages.
 *
 * @package LearnDash\Core
 */

?>
<li class="pagination-item pagination-input">
	<span class="paging-input">
		<label for="current-page-selector" class="screen-reader-text">
			<?php esc_html_e( 'Current Page', 'learndash' ); ?>
		</label>
		<input
			class="current-page"
			id="current-page-selector"
			type="text"
			name="paged"
			value="<?php echo esc_html( number_format_i18n( $current_page ) ); ?>"
			size="1"
			aria-describedby="table-paging"
		>
		<span class="tablenav-paging-text">
			<?php echo esc_html( _x( 'of', 'current page number of total pages', 'learndash' ) ); ?>
			<span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span>
		</span>
	</span>
</li>
