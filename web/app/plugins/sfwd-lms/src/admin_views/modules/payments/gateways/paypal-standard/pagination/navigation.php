<?php
/**
 * View: PayPal Standard - Pagination Navigation.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template $this    Current instance of template engine rendering this template.
 * @var int $current_page Current page number.
 * @var int $total_pages  Total number of pages.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<nav class="pagination-links" aria-label="<?php esc_attr_e( 'Pagination', 'learndash' ); ?>">
	<ul class="pagination-list">
		<?php
		// First and Previous page buttons.
		if ( $current_page > 1 ) :
			?>
			<li class="pagination-item pagination-arrow">
				<a
					class="first-page button"
					href="<?php echo esc_url( add_query_arg( 'paged', 1, admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal' ) ) ); ?>"
					aria-label="<?php esc_attr_e( 'First page', 'learndash' ); ?>"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'First page', 'learndash' ); ?></span>
					<span aria-hidden="true">&laquo;</span>
				</a>
			</li>
			<li class="pagination-item pagination-arrow">
				<a
					class="prev-page button"
					href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal' ) ) ); ?>"
					aria-label="<?php esc_attr_e( 'Previous page', 'learndash' ); ?>"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'learndash' ); ?></span>
					<span aria-hidden="true">&lsaquo;</span>
				</a>
			</li>
		<?php else : ?>
			<li class="pagination-item pagination-arrow">
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true" aria-label="<?php esc_attr_e( 'First page', 'learndash' ); ?>">&laquo;</span>
			</li>
			<li class="pagination-item pagination-arrow">
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true" aria-label="<?php esc_attr_e( 'Previous page', 'learndash' ); ?>">&lsaquo;</span>
			</li>
		<?php endif; ?>

		<?php
		// Current page input and total pages display.
		$this::show_admin_template(
			'modules/payments/gateways/paypal-standard/pagination/page-input',
			[
				'current_page' => $current_page,
				'total_pages'  => $total_pages,
			]
		);
		?>

		<?php
		// Next and Last page buttons.
		if ( $current_page < $total_pages ) :
			?>
			<li class="pagination-item pagination-arrow">
				<a
					class="next-page button"
					href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal' ) ) ); ?>"
					aria-label="<?php esc_attr_e( 'Next page', 'learndash' ); ?>"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'learndash' ); ?></span>
					<span aria-hidden="true">&rsaquo;</span>
				</a>
			</li>
			<?php
			/* translators: %d: Page number. */
			$last_page_aria_label = sprintf( __( 'Last page, page %d', 'learndash' ), $total_pages );
			?>
			<li class="pagination-item pagination-arrow">
				<a
					class="last-page button"
					href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal' ) ) ); ?>"
					aria-label="<?php echo esc_attr( $last_page_aria_label ); ?>"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'learndash' ); ?></span>
					<span aria-hidden="true">&raquo;</span>
				</a>
			</li>
		<?php else : ?>
			<li class="pagination-item pagination-arrow">
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true" aria-label="<?php esc_attr_e( 'Next page', 'learndash' ); ?>">&rsaquo;</span>
			</li>
			<li class="pagination-item pagination-arrow">
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true" aria-label="<?php esc_attr_e( 'Last page', 'learndash' ); ?>">&raquo;</span>
			</li>
		<?php endif; ?>
	</ul>
</nav>
