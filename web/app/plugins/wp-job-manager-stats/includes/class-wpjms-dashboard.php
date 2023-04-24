<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Dashboard::get_instance();

/**
 * Stuff
 *
 * @since 2.0.0
 */
class WPJMS_Dashboard {

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

		/* Init */
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init
	 */
	public function init() {

		/* Register "stat_dashboard" shortcode */
		add_shortcode( 'stats_dashboard', array( $this, 'stats_dashboard_shortcode' ) );

		/* Dashboard Ajax Callback */
		add_action( 'wp_ajax_wpjms_job_dashboard_chart', array( $this, 'job_dashboard_chart_ajax' ) );
		add_action( 'wp_ajax_wpjms_legend_search', array( $this, 'legend_search_ajax' ) );
		add_action( 'wp_ajax_wpjms_job_stats_chart', array( $this, 'job_stats_chart_ajax' ) );

		/* Scripts */
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * Stats Dashboard Shortcode
	 */
	public function stats_dashboard_shortcode() {
		ob_start();
		if ( ! is_user_logged_in() ) {
			?>
			<div class="wpjms-job-dashboard wpjms-job-dashboard-login-notice">
				<p><?php _e( 'You need to login to view this page', 'wp-job-manager-stats' ); ?></p>
			</div><!-- .wpjms-job-dashboard -->
			<?php
			return ob_get_clean();
		}
		if ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) {
			wp_enqueue_script( 'wpjms-dashboard-job-stats' );
			?>
			<div id="wpjms-job-stats" class="wpjms-job-dashboard">
				<?php $this->job_stats(); ?>
			</div><!-- #wpjms-job-stats -->
			<?php
		} else {
			wp_enqueue_script( 'wpjms-dashboard' );
			?>
			<div id="wpjms-job-dashboard" class="wpjms-job-dashboard">
				<?php $this->job_dashboard(); ?>
			</div><!-- #wpjms-job-dashboard -->
			<?php
		}
		return ob_get_clean();
	}

	/*
	 Job Stats
	------------------------------------------ */

	/**
	 * Job Stats
	 */
	public function job_stats() {
		$post_id = intval( $_GET['job_id'] );

		/* Date Range Picker Field */
		$this->date_range_picker_field();

		/* Chart Job Dashboard */
		echo '<div id="wpjms_job_stats_chart" data-post_id="' . $post_id . '" class="wpjms-chart"></div>';

		echo wpautop( '<a class="button back-to-stat-dashboard" href="' . esc_url( get_permalink() ) . '">' . __( 'View All Listing Stats', 'wp-job-manager-stats' ) . '</a>' );
	}

	/**
	 * Update Job Stats Ajax
	 */
	public function job_stats_chart_ajax() {

		/* Strip Slash */
		$request = stripslashes_deep( $_POST );

		/* Post ID */
		$post_id = intval( $request['post_id'] );
		$post = get_post( $post_id );
		if ( ! $post || 'job_listing' != $post->post_type || ! current_user_can( 'administrator' ) && get_current_user_id() != $post->post_author ) {
			ob_start();
			?>
			<div class="wpjms-error">
				<?php _e( 'No Listing Found', 'wp-job-manager-stats' ); ?>
			</div><!--wpjms-error-->
			<?php
			$data = ob_get_clean();
			wp_send_json_error( $data );
		}

		/* Get Stat Data */
		$stats_data = new WPJMS_Stats_Data( array(
			'stat_ids'  => wpjms_stat_ids(),
			'post_ids'  => array( $post_id ),
			'date_from' => $request['date_from'],
			'date_to'   => $request['date_to'],
		) );

		/* Get Chart */
		$chart = new WPJMS_Chart( array(
			'id'    => 'wpjms_job_stats_chart',
			'name'  => sprintf( __( '%s Stats', 'wp-job-manager-stats' ), "{$post->post_title}" ),
			'data'  => $stats_data->get_post_data(),
			'legend' => array(
				'display'  => true,
				'position' => 'bottom',
			),
		) );

		/* Display Chart HTML */
		ob_start();
		$chart->display();
		$data = ob_get_clean();
		wp_send_json_success( $data );
	}

	/*
	 Job Dashboard
	------------------------------------------ */

	/**
	 * Job Dashboard
	 */
	public function job_dashboard() {

		/* Check if job exists */
		$post_ids = $this->post_ids();
		if ( ! $post_ids ) {
			?>
			<div class="wpjms-error">
				<?php _e( 'No Listing Found', 'wp-job-manager-stats' ); ?>
			</div><!--wpjms-error-->
			<?php
			return;
		}

		/* Hook */
		do_action( 'wpjms_job_dashboard_before' );

		/* Date Range Picker Field */
		$this->date_range_picker_field();

		/* Chart Job Dashboard */
		echo '<div id="wpjms_job_dashboard_chart" class="wpjms-chart"></div>';

		echo '<div class="wpjms-stat-legend-wrap">';

			/* Stats Field */
			$this->stats_field();

			/* Chart Legend */
			$this->chart_legend( $post_ids );

		echo '</div>';

		/* Hook */
		do_action( 'wpjms_job_dashboard_after' );
	}

	/**
	 * Ajax Update Chart
	 */
	public function job_dashboard_chart_ajax() {

		/* Strip Slash */
		$request = stripslashes_deep( $_POST );

		/* Check Ajax */
		check_ajax_referer( 'wpjms-view_stats', 'nonce' );

		/* Date From + To */
		$date_from = $request['date_from'];
		$date_to = $request['date_to'];

		/* Post IDs & Color */
		$post_ids = array();
		$post_colors = array();
		$items = $request['items'];
		foreach ( $items as $item ) {
			$post_id = intval( $item['id'] );

			/* Only load current user listings */
			$current_user_id = get_current_user_id();
			$post_author_id  = get_post_field( 'post_author', $post_id );
			if ( $current_user_id == $post_author_id ) {
				$post_colors[ $post_id ] = $item['color'];
				$post_ids[] = $post_id;
			}
		}

		if ( ! $post_ids ) {
			ob_start();
			?>
			<div class="wpjms-error">
				<?php _e( 'No Listing Found', 'wp-job-manager-stats' ); ?>
			</div><!--wpjms-error-->
			<?php
			$data = ob_get_clean();
			wp_send_json_error( $data );
		}

		/* Stat ID */
		$stat_id = $request['stat_id'];
		$stats_label = wpjms_stat_label( $stat_id );

		/* Get Stat Data */
		$stats_data = new WPJMS_Stats_Data( array(
			'stat_ids'  => array( $stat_id ),
			'post_ids'  => $post_ids,
			'date_from' => $date_from,
			'date_to'   => $date_to,
		) );

		/* Add Chart Color */
		$posts_data = $stats_data->get_posts_data();
		$datasets = array();
		foreach ( $posts_data['datasets'] as $id => $dataset ) {
			$color = $post_colors[ $id ];
			$datasets[ $id ] = $dataset;
			$datasets[ $id ]['pointBackgroundColor'] = "rgba({$color},1)";
			$datasets[ $id ]['borderColor'] = "rgba({$color},1)";
			$datasets[ $id ]['backgroundColor'] = "rgba({$color},0.1)";
		}
		$posts_data['datasets'] = $datasets;

		/* Get Chart */
		$chart = new WPJMS_Chart( array(
			'id'    => 'wpjms_job_dashboard_chart',
			'name'  => sprintf( '%s: %s', wpjms_stat_label( $stat_id ), __( '%s total views', 'wp-job-manager-stats' ) ),
			'data'  => $posts_data,
		) );

		/* Display Chart HTML */
		ob_start();
		$chart->display();
		$data = ob_get_clean();
		wp_send_json_success( $data );
	}

	/**
	 * Legend Search Ajax
	 */
	public function legend_search_ajax() {

		/* Strip Slash */
		$request = stripslashes_deep( $_POST );

		/* Check Ajax */
		check_ajax_referer( 'wpjms-view_stats', 'nonce' );

		/* Data Output */
		$data = array();

		/* WP Query */
		$args = array(
			'post_type'       => array( 'job_listing' ),
			'author'          => get_current_user_id(),
			's'               => esc_attr( $request['keyword'] ),
			'posts_per_page'  => 1,
			'post__not_in'    => $request['exclude'],
		);
		$args = apply_filters( 'wpjms_job_listing_loop_args', $args, $context = 'legend_search' );

		$the_query = new WP_Query( $args );

		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				/* Data */
				ob_start();

				$this->chart_item( array(
					'checked' => true,
				) );

				$html = ob_get_clean();
				$data[] = array(
					'value' => get_the_title(),
					'html'  => $html,
				);
			}
			wp_reset_postdata();
		} else {
			$data[] = array(
				'value' => __( 'No listing found', 'wp-job-manager-stats' ),
			);
		}
		wp_send_json_success( $data );
	}
	/*
	 Parts
	------------------------------------------ */

	/**
	 * Chart Post IDs
	 */
	public function post_ids() {
		$args = array(
			'post_type'       => array( 'job_listing' ),
			'author'          => get_current_user_id(),
			'posts_per_page'  => 10,
		);
		$args = apply_filters( 'wpjms_job_listing_loop_args', $args, $context = 'chart_init' );
		$get_posts = get_posts( $args );
		if ( ! $get_posts ) {
			return array();
		}
		$post_ids = array();
		foreach ( $get_posts as $get_post ) {
			$post_ids[] = $get_post->ID;
		}
		return $post_ids;
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
			<h4><?php _e( 'Stats', 'wp-job-manager-stats' ); ?></h4>
			<ul>
			<?php foreach ( $stats as $stat_id => $stat_data ) {
				$checked = $stat_id == $default_stat ? ' checked="checked"' : '';
				$data_checked = $checked ? 'checked' : 'unchecked';
				$stat_label = $stat_data['label'];
				?>
				<li class="wpjms-stats-option" data-checked="<?php echo $data_checked; ?>">
					<label>
					<input autocomplete="off" type="radio" name="stats-options" value="<?php echo $stat_id; ?>"<?php echo $checked; ?>> <span class="stat-label"><?php echo $stat_label; ?></span></label>
				</li>
			<?php } ?>
			</ul>
		</div><!-- .wpjms-stats-field -->
		<?php
	}

	/**
	 * Chart Legend
	 */
	public function chart_legend( $post_ids ) {

		/* Query Args */
		$args = array(
			'post_type'       => array( 'job_listing' ),
			'author'          => get_current_user_id(),
			'posts_per_page'  => 10,
			'post__in'        => $post_ids,
		);
		$args = apply_filters( 'wpjms_job_listing_loop_args', $args, $context = 'legend_init' );

		$the_query = new WP_Query( $args );
		$chart = new WPJMS_Chart();
		$colors = $chart->chart_colors();
		?>

		<?php if ( $the_query->have_posts() ) {
			$i = 0;
			?>

			<div class="wpjms-chart-legend">
				<h4><?php _e( 'Add Listing to Chart', 'wp-job-manager-stats' ); ?></h4>

				<div class="wpjms-legend-search-wrap">
					<input autocomplete="off" type="search" id="wpjms-legend-search" placeholder="<?php esc_attr_e( 'Search listings...', 'wp-job-manager-stats' );?>"/>
				</div>

				<ul id="wpjms-chart-legend-list">
					<?php while ( $the_query->have_posts() ) {
						$the_query->the_post();
						$i++;

						$this->chart_item( array(
							'color' => isset( $colors[ $i ] ) ? $colors[ $i ] : mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 ),
							'checked' => in_array( get_the_ID(), $post_ids ),
						) );

} // End while().
?>
				</ul>
			</div><!-- .wpjms-chart-legend -->

			<?php wp_reset_postdata(); ?>

		<?php } // End if().
	}

	/**
	 * Chart Item
	 */
	public function chart_item( $args = array() ) {
		setup_postdata( get_the_ID() );

		$defaults = array(
			'color' => mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 ),
			'post_id' => get_the_ID(),
			'checked' => false,
			'expires' => get_post()->_job_expires,
		);

		$args = wp_parse_args( $args, $defaults );

		$expires_info = $args['expires'] ? sprintf( __( '(Expires: %s)', 'wp-job-manager-stats' ), date_i18n( get_option( 'date_format' ), strtotime( $args['expires'] ) ) ) : '';
		$data_checked = $args['checked'] ? 'checked' : 'unchecked';
?>

<li class="chart-item" data-id="<?php echo $args['post_id']; ?>" data-checked="<?php echo $data_checked; ?>">
	<input autocomplete="off" id="chart-item-<?php echo $args['post_id']; ?>" type="checkbox" name="chart-item[]" value="<?php echo intval( $args['post_id'] ); ?>" data-color="<?php echo esc_attr( $args['color'] ); ?>" <?php checked( true, $args['checked'] ); ?>>
	
	<div class="chart-item-wrap">
		<label for="chart-item-<?php echo $args['post_id']; ?>">
			<span class="chart-item-color" style="background-color:rgba( <?php echo esc_attr( $args['color'] ); ?>, 1 )"></span>
			<strong class="chart-item-title"><?php echo strip_tags( get_the_title() );?> <span class="chart-item-info"><?php echo $expires_info; ?></span></strong><br />
			<span class="chart-item-action">
				<span><a href="<?php echo esc_url( wpjms_job_stat_url( $args['post_id'] ) ); ?>"><?php _e( 'View Stats', 'wp-job-manager-stats' ); ?></a></span> 
				<span><a href="<?php the_permalink(); ?>"><?php _e( 'View Listing', 'wp-job-manager-stats' ); ?></a></span>
				<span><a class="remove-chart-legend-item" href="#"><?php _e( 'Remove from list', 'wp-job-manager-stats' ); ?></a></span>
			</span>
		</label>
	</div><!-- .chart-item-wrap -->
</li>

<?php
	}
	/*
	 Scripts
	------------------------------------------ */

	/**
	 * Scripts
	 */
	public function scripts() {

		/* CSS */
		wp_register_style( 'wpjms-dashboard', WPJMS_URL . 'assets/dashboard/dashboard.min.css' , array( 'dashicons', 'date-range-picker', 'jquery-ui' ), WPJMS_VERSION );

		/* Enqueue Only CSS. JS is enqueued via Shortcode. */
		$page_id = wpjms_stat_page_id();
		if ( $page_id && is_page( $page_id ) ) {
			wp_enqueue_style( 'wpjms-dashboard' );
		}

		/*
		 Dashboard
		------------------------------------------ */

		/* JS */
		wp_register_script( 'wpjms-dashboard', WPJMS_URL . 'assets/dashboard/dashboard.min.js', array( 'wp-util', 'jquery', 'blockUI', 'moment-js', 'date-range-picker', 'chart-js', 'jquery-ui-autocomplete' ), WPJMS_VERSION, true );

		/* Ajax Data */
		$ajax_data = array(
			'ajax_nonce'       => wp_create_nonce( 'wpjms-view_stats' ),
			'this_week'        => __( 'This week', 'wp-job-manager-stats' ),
			'this_month'       => __( 'This month', 'wp-job-manager-stats' ),
			'min_char'         => __( 'Please enter 3 or more characters', 'wp-job-manager-stats' ),
		);
		wp_localize_script( 'wpjms-dashboard', 'wpjms', $ajax_data );

		/*
		 Single Job Stats
		------------------------------------------ */

		/* JS */
		wp_register_script( 'wpjms-dashboard-job-stats', WPJMS_URL . 'assets/dashboard/dashboard-job-stats.min.js', array( 'wp-util', 'jquery', 'blockUI', 'moment-js', 'date-range-picker', 'chart-js' ), WPJMS_VERSION, true );
		wp_localize_script( 'wpjms-dashboard-job-stats', 'wpjms', $ajax_data );
	}

}

