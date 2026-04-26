<?php
/**
 * Learndash ProPanel Reporting - Filters sections.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="reporting-actions toggle-section <?php echo $filter_tab_display; ?>" id="table-filters">

	<div class="filter-selection" style="display: inline-block;"><?php esc_html_e( 'Filter By:', 'learndash' ); ?></div>
	<?php echo LearnDash_ProPanel::get_instance()->filtering_widget->show_filters(); ?>
	<?php
	// The new date selector fields were added in LD 3.2.3.
	// @phpstan-ignore-next-line -- Should be checked later.
	if ( version_compare( LEARNDASH_VERSION, '3.2.3' ) >= 0 ) {
		?>
		<div class="filter-selection filter-section-date filter-section-date-start">
			<input name="filter-date-start" placeholder="<?php esc_html_e( 'Start Date', 'learndash' ); ?>" class="ld_filter_section_date" />
		</div>
		<div class="filter-selection filter-section-date filter-section-date-end">
			<input name="filter-date-end" placeholder="<?php esc_html_e( 'End Date', 'learndash' ); ?>" class="ld_filter_section_date" />
		</div>
		<?php
	}
	?>
	<p>
		<?php esc_html_e( 'Per Page:', 'learndash' ); ?>
		<?php
			$per_page_array = ld_propanel_get_pager_values();
		if ( ! empty( $per_page_array ) ) {
			?>
				<select id="ld-propanel-pagesize" class="pagesize">
				<?php
				$selected_per_page = 0;
				foreach ( $per_page_array as $per_page ) {
					if ( empty( $selected_per_page ) ) {
						$selected_per_page = $per_page;
					}
					?>
					<option <?php selected( $selected_per_page, $per_page ); ?> value="<?php echo abs( intval( $per_page ) ); ?>"><?php echo abs( intval( $per_page ) ); ?></option>
					<?php
				}
				?>
				</select>
				<?php
		}
		?>
	</p>

	<p><button type="button" class="button button-primary filter"><?php esc_html_e( 'Filter', 'learndash' ); ?></button>  <button type="button" class="button reset button-secondary"><?php esc_html_e( 'Reset', 'learndash' ); ?></button></p>
</div>
