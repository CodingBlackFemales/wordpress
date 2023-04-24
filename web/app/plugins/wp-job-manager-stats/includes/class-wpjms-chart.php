<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}
/**
 * Chart
 *
 * @since 2.0.0
 */
class WPJMS_Chart {

	/* Var */
	var $config;

	/**
	 * Constructor.
	 */
	public function __construct( $config = array() ) {

		/* Default */
		$defaults = array(
			'id'       => 'wpjms_chart',
			'name'     => __( 'Chart: %s total views', 'wp-job-manager-stats' ),
			'data'     => array(),
			'max'      => false,
			'legend'   => array(
				'display'   => false,
				'position'  => 'bottom',
			),
		);

		/* Set vars */
		$this->config = wp_parse_args( $config, $defaults );
	}

	/*
	 Functions
	------------------------------------------ */

	/**
	 * Get Chart ID
	 */
	public function get_id() {
		return sanitize_title( $this->config['id'] );
	}

	/**
	 * Get Chart Name
	 */
	public function get_name() {
		$name = sprintf( $this->config['name'], $this->get_total() );
		return wp_kses_post( $name );
	}

	/**
	 * Get Chart Data
	 */
	public function get_data() {
		return $this->sanitize_data( $this->config['data'] );
	}

	/**
	 * Get Labels
	 */
	public function get_labels() {
		$data = $this->get_data();
		return $data['labels'];
	}
	/**
	 * Get Datasets
	 */
	public function get_datasets() {
		$data = $this->get_data();
		return $data['datasets'];
	}

	/**
	 * Get Chart Total
	 */
	public function get_total() {
		$datasets = $this->get_datasets();
		$total = 0;
		foreach ( $datasets as $dataset ) {
			$total = $total + array_sum( $dataset['data'] );
		}
		return $total;
	}

	/*
	 Output
	------------------------------------------ */

	/**
	 * Display Chart
	 */
	public function display() {
		global $wpjms_chart_stepsize;
		wp_enqueue_script( 'chart-js' ); // script
		$id      = sanitize_html_class( $this->get_id() );
		$name    = $this->get_name();
		$data    = json_encode( $this->get_data() );
		$legend  = json_encode( $this->config['legend'] );
		?>
		<div id="<?php echo $id; ?>" class="wpjms-chart">

			<?php if ( $name ) { ?>
				<h3><?php echo $name; ?></h3>
			<?php } ?>

			<div class="wpjms-chart-wrap">
				<canvas class="wpjms-chart-canvas"></canvas>
			</div><!-- .wpjms-chart-wrap -->

			<script type="text/javascript">
				jQuery( document ).ready( function($){
					var wpjms_chart = new Chart( $( '#<?php echo $id; ?> .wpjms-chart-canvas' ), {
						type: 'line',
						data: <?php echo $data; ?>,
						options: {
							tooltips: {
								mode: 'label',
								callbacks: {
									label: function( label, data ){
										var i = label.datasetIndex;
										var out = data.datasets[i].label + " - " + data.datasets[i].data[label.index];
										var max = 10 - 1; // max 10 hover legend
										if( max == i ){
											out += '...'; 
										}
										if( max < i ){
											return false; 
										}
										return out;
									},
								},
							},
							scales: {
								yAxes: [{
									ticks: {
										stepSize: <?php echo $wpjms_chart_stepsize; ?>,
									}
								}]
							},
							legend: <?php echo $legend; ?>,
						},
					});
				});
			</script>

		</div><!-- .wpjms-chart -->
		<?php
	}
	/*
	 Sanitize Functions
	------------------------------------------ */

	/**
	 * Sanitize Data
	 */
	public function sanitize_data( $data ) {
		$defaults = array(
			'labels'   => array(),
			'datasets' => array(),
		);
		$data = wp_parse_args( $data, $defaults );
		$data['labels'] = $this->sanitize_labels( $data['labels'] );
		$data['datasets'] = $this->sanitize_datasets( $data['datasets'] );
		return $data;
	}

	/**
	 * Sanitize Labels
	 */
	public function sanitize_labels( $labels ) {
		$new_labels = array();
		foreach ( $labels as $label ) {
			$new_labels[] = $label;
		}
		return $new_labels;
	}

	/**
	 * Sanitize Datasets
	 */
	public function sanitize_datasets( $datasets ) {
		global $wpjms_chart_stepsize;
		$wpjms_chart_stepsize = 1;

		/* Max Entry */
		if ( false !== $this->config['max'] ) {
			$datasets = array_slice( $datasets, 0, $this->config['max'] );
		}

		/* Output */
		$new_datasets = array();

		/* Colors */
		$colors = $this->chart_colors();

		$i = 0;
		foreach ( $datasets as $k => $dataset ) {
			$i++;
			$color = isset( $colors[ $i ] ) ? $colors[ $i ] : mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 ) . ',' . mt_rand( 0, 255 );
			$defaults = array(
				'id'                   => $k,
				'label'                => $k,
				'data'                 => array(),
				'pointBackgroundColor' => "rgba({$color},1)",
				'borderColor'          => "rgba({$color},1)",
				'backgroundColor'      => "rgba({$color},0.1)",
			);
			if ( isset( $dataset['data'] ) && max( $dataset['data'] ) > 10 ) {
				$wpjms_chart_stepsize = 0;
			}
			$new_datasets[] = wp_parse_args( $dataset, $defaults );
		}

		return $new_datasets;
	}

	/*
	 Utility
	------------------------------------------ */

	/**
	 * Nice Color Schemes
	 */
	public function chart_colors() {
		$colors = array(
			'26, 188, 156',
		'46, 204, 113',
		'52, 152, 219',
			'155, 89, 182',
		'52, 73, 94',
		'241, 196, 15',
			'230, 126, 34',
		'231, 76, 60',
		'236, 240, 241',
			'149, 165, 166',
		'255, 204, 188',
		'206, 160, 228',
			'199, 44, 28',
		'255, 140, 200',
		'41, 197, 255',
			'255, 194, 155',
		'255, 124, 108',
		'94, 252, 161',
			'46, 204, 113',
		'140, 154, 169',
		'255, 207, 75',
			'255, 146, 107',
		'255, 108, 168',
		'18, 151, 224',
			'155, 89, 182',
		'80, 80, 80',
		'231, 76, 60',
		);
		return apply_filters( 'wpjms_chart_colors', $colors, $this->get_id() );
	}

}
