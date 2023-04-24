<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_WCPL_WooCommerce_Setup::get_instance();

/**
 * Stuff
 *
 * @since 2.0.0
 */
class WPJMS_WCPL_WooCommerce_Setup {

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
		$active = get_option( 'wp_job_manager_stats_require_paid_listing' );
		if ( $active ) {

			/* Extra check box in product option */
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_allow_stats_checkbox' ), 15 );

			/* Save product checkbox */
			add_action( 'woocommerce_process_product_meta_job_package', array( $this, 'save_product_data' ) );
			add_action( 'woocommerce_process_product_meta_job_package_subscription', array( $this, 'save_product_data' ) );

		}
	}

	/**
	 * Add product checkbox.
	 * Add a checkbox to the job package products to set the allowance
	 * of viewing statistics.
	 */
	public function add_allow_stats_checkbox() {
		global $post;
		?>
		<div class="options_group show_if_job_package show_if_job_package_subscription">

			<?php woocommerce_wp_checkbox( array(
				'id'           => '_job_listing_stats',
				'label'        => __( 'Display statistics?', 'wp-job-manager-stats' ),
				'description'  => __( 'Show statistics for listings that have this package.', 'wp-job-manager-stats' ),
				'value'        => get_post_meta( $post->ID, '_job_listing_stats', true ),
			) ); ?>

		</div>
		<?php
	}

	/**
	 * Save product data.
	 * Save the custom product data fields.
	 */
	public function save_product_data( $post_id ) {
		$value = isset( $_POST['_job_listing_stats'] ) ? $_POST['_job_listing_stats'] : '';
		$value = ( 'yes' == $value ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_job_listing_stats', $value );
	}

}
