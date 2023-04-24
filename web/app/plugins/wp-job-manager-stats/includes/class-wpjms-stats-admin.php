<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Stats_Admin::get_instance();

/**
 * Admin Overview of the Stats
 *
 * @since 2.0.0
 */
class WPJMS_Stats_Admin {

	/**
	 * Returns the instance.
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) { $instance = new self;
		}
		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		/* Add View Job Stat Icon in Admin Column */
		add_filter( 'job_manager_admin_actions', array( $this, 'job_listing_post_row_actions' ), 10, 2 );

		/* Display Initial in footer */
		add_action( 'admin_footer-edit.php', array( $this, 'admin_footer' ), 20 );

		/* Ajax Stats */
		add_action( 'wp_ajax_wpjms_admin_chart', array( $this, 'admin_stats_ajax' ) );

		/* Admin Scripts */
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Job Manager Row Action
	 */
	public function job_listing_post_row_actions( $actions, $post ) {
		if ( 'job_listing' == $post->post_type && wpjms_stat_page_id() ) {
			$actions['stats'] = array(
				'action'  => 'stats',
				'name'    => __( 'View stats', 'wp-job-manager-stats' ),
				'url'     => esc_url( wpjms_job_stat_url( $post->ID ) ),
			);
		}
		return $actions;
	}

	/**
	 * Admin Footer
	 */
	public function admin_footer() {
		?>
			<div id="wpjms-box-overlay" style="display:none;"></div>
			<div id="wpjms-box" style="display:none;width:900px;height:600px;">
				<div id="wpjms-box-container">
					<div id="wpjms-box-title"><?php _e( 'Stats', 'wp-job-manager-stats' );?><span class="wpjms-box-close"></span></div>
					<div id="wpjms-box-content">

						<div id="wpjms-job-admin" class="wpjms-job-dashboard">

							<div class="wpjms-admin-control">
								<?php $this->date_range_picker_field(); ?>
								<?php $this->stats_field(); ?>
							</div><!-- .wpjms-admin-control -->

							<div id="wpjms_admin_chart" class="wpjms-chart"></div>

						</div><!-- #wpjms-job-admin -->

					</div><!-- #wpjms-box-content -->
				</div><!-- #wpjms-box-container -->
			</div><!-- #wpjms-box -->

			<?php /* Setup columns here */ ?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ){
					$( '<option>' ).val( 'wpjms_stats' ).text( wpjms.view_stats ).appendTo( "select[name='action']" );
					$( '<option>' ).val( 'wpjms_stats' ).text( wpjms.view_stats ).appendTo( "select[name='action2']" );
					$( 'a.icon-stats' ).attr( 'target', '_blank' );
				});
			</script>
		<?php
	}

	/**
	 * Date Range Picker Field
	 */
	public function date_range_picker_field() {
		$days = intval( get_option( 'wp_job_manager_stats_default_stat_days', 7 ) );
		?>
		<div class="wpjms-date-range-field">
			<input type="text" name="chart-date-range" autocomplete="off" value="<?php echo sprintf( __( 'Date range: Last %s days', 'wp-job-manager-stats' ), $days ); ?>">

			<input type="hidden" name="chart-date_from" autocomplete="off" value="<?php echo date_i18n( 'Y-m-d', strtotime( '-' . $days + 1 . 'days' ) ); ?>">
			<input type="hidden" name="chart-date_to" autocomplete="off" value="<?php echo date_i18n( 'Y-m-d' ); ?>">
		</div><!-- .wpjms-date-range-field -->
		<?php
	}

	/**
	 * Stats Options
	 */
	public function stats_field() {
		$stats = wpjms_stats();
		$default_stat = wpjms_stats_default();
		?>
		<div class="wpjms-stats-field">
			<select id="wpjms-stats-options" name="stats-options" autocomplete="off">
				<?php foreach ( $stats as $stat_id => $stat_data ) {
					$selected = $stat_id == $default_stat ? ' selected="selected"' : '';
					$stat_label = $stat_data['label'];
					?>
					<option value="<?php echo esc_attr( $stat_id );?>" <?php echo $selected; ?>><?php echo strip_tags( $stat_label ); ?></option>
				<?php } ?>
			</select>
		</div><!-- .wpjms-stats-field -->
		<?php
	}
	/**
	 * AJAX: Admin Stats
	 */
	public function admin_stats_ajax() {

		/* Strip slash */
		$request = stripslashes_deep( $_POST );

		/* Check Ajax */
		check_ajax_referer( 'wpjms-view_stats', 'nonce' );

		/* Get Data */
		$stats_data = new WPJMS_Stats_Data( array(
			'stat_ids'  => array( $request['stat_id'] ),
			'post_ids'  => $request['items'],
			'date_from' => $request['date_from'],
			'date_to'   => $request['date_to'],
		) );

		/* Get Chart */
		$chart = new WPJMS_Chart( array(
			'id'       => 'wpjms_job_dashboard_chart',
			'name'     => __( '%s total views', 'wp-job-manager-stats' ),
			'data'     => $stats_data->get_posts_data(),
			'legend'   => array(
				'display'   => true,
				'position'  => 'bottom',
			),
		) );

		/* Display Chart HTML */
		ob_start();
		$chart->display();
		$data = ob_get_clean();
		wp_send_json_success( $data );
	}

	/**
	 * Admin Scripts
	 */
	public function admin_scripts( $hook_suffix ) {
		global $post_type;
		if ( 'edit.php' == $hook_suffix && 'job_listing' == $post_type ) {

			/* CSS */
			wp_enqueue_style( 'wpjms-admin', WPJMS_URL . 'assets/admin/admin.min.css', array( 'date-range-picker' ), WPJMS_VERSION );

			/* JS */
			wp_enqueue_script( 'wpjms-admin', WPJMS_URL . 'assets/admin/admin.min.js', array( 'wp-util', 'jquery', 'blockUI', 'moment-js', 'date-range-picker', 'chart-js' ), WPJMS_VERSION, true );
			$ajax_data = array(
				'ajax_nonce'       => wp_create_nonce( 'wpjms-view_stats' ),
				'stats'            => __( 'Stats', 'wp-job-manager-stats' ),
				'view_stats'       => __( 'View Stats', 'wp-job-manager-stats' ),
				'this_week'        => __( 'This week', 'wp-job-manager-stats' ),
				'this_month'       => __( 'This month', 'wp-job-manager-stats' ),
			);
			wp_localize_script( 'wpjms-admin', 'wpjms', $ajax_data );
		}
	}

}

