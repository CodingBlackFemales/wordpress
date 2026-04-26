<?php
/**
 * Learndash ProPanel Group Courses Users Progress Reporting.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 *
 * Available variables:
 *
 * @var $users array of WP_User objects
 */

defined( 'ABSPATH' ) || exit;

// $headers = $this->filter_table_headers();
if ( ! empty( $this->filter_headers ) ) {
	ob_start();
	include ld_propanel_get_template( 'ld-propanel-reporting-pager.php' );
	$report_pager_html = ob_get_clean();

	ob_start();
	include ld_propanel_get_template( 'ld-propanel-reporting-search.php' );
	$report_search_html = ob_get_clean();

	ob_start();
	include ld_propanel_get_template( 'ld-propanel-reporting-download-button.php' );
	$report_download_button_html = ob_get_clean();


	?>
	<div class="pager top">
		<?php echo $report_pager_html; ?>
		<?php echo $report_search_html; ?>
	</div>

	<table id="table" class="tablesorter ld-propanel-reporting-table <?php echo apply_filters( 'ld-propanel-reporting-table-class', 'ld-propanel-reporting-table-' . $this->filter_key . '-' . $this->post_data['container_type'], $this->post_data['container_type'] ); ?>">
		<thead>
		<tr>
		<?php
		foreach ( $this->filter_headers as $header_key => $header_label ) {
			switch ( $header_key ) {
				case 'checkbox':
					?>
					<th class="sorter-checkbox checkbox-col <?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>" data-sorter="false"><input class="ld-propanel-report-checkbox" data-user-id="all" type="checkbox"></th>
					<?php
					break;

				case 'course_id':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  class="user-col"><?php echo $header_label; ?></th>
					<?php
					break;

				case 'course':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  class="user-col"><?php echo $header_label; ?></th>
					<?php
					break;

				case 'user_id':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  data-sorter="false"><?php echo $header_label; ?></th>
					<?php
					break;

				case 'user':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  data-sorter="false"><?php echo $header_label; ?></th>
					<?php
					break;

				case 'progress':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  data-sorter="false" class="progress-col"><?php echo $header_label; ?></th>
					<?php
					break;

				case 'last_update':
					?>
					<th class="<?php echo apply_filters( 'ld-propanel-column-class', 'ld-propanel-reporting-col-' . $header_key, $this->filter_key, $header_key, $this->post_data['container_type'] ); ?>"  data-sorter="false"><?php echo $header_label; ?></th>
					<?php
					break;
			}
		}
		?>
		</tr>
		</thead>
		<tbody>
		</tbody>
	</table>

	<div class="pager bottom">
		<?php echo $report_pager_html; ?>
		<?php echo $report_download_button_html; ?>
	</div>
	<?php
}
