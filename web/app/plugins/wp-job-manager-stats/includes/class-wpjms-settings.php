<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Settings::get_instance();

/**
 * Stuff
 *
 * @since 2.0.0
 */
class WPJMS_Settings {

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

		/* Add Settings */
		add_action( 'job_manager_settings', array( $this, 'add_settings' ) );
		add_action( 'job_manager_settings', array( $this, 'add_license_field' ), 11 );

		/* Sanitize Options */
		add_filter( 'sanitize_option_wp_job_manager_stats_default_stat_days', 'intval' );
		add_filter( 'sanitize_option_wp_job_manager_stats_purge_data', array( $this, 'sanitize_checkbox' ) );
		add_filter( 'sanitize_option_wp_job_manager_stats_page_id', 'intval' );

		/* Create pages */
		add_action( 'admin_init', array( $this, 'create_pages' ) );
	}

	/**
	 * Settings page.
	 * Add an settings tab to the Listings -> settings page.
	 */
	public function add_settings( $settings ) {

		$settings['wpjms_settings'] = array(
			__( 'Statistics', 'wp-job-manager-stats' ),
			apply_filters( 'wpjms_settings', array(

				array(
					'name'          => 'wp_job_manager_stats_purge_data',
					'type'          => 'checkbox',
					'label'         => __( 'Purge Data on Deletion', 'wp-job-manager-stats' ),
					'cb_label'      => __( 'Purge Data on Deletion', 'wp-job-manager-stats' ),
					'desc'          => __( 'Purge all statistics data when the plugin is deleted. <strong>This is irreversible</strong>.', 'wp-job-manager-stats' ),
					'std'           => 0,
				),

				array(
					'name'          => 'wp_job_manager_stats_default_stat_days',
					'std'           => 7,
					'type'          => 'input',
					'label'         => __( 'Default Stat Days', 'wp-job-manager-stats' ),
					'cb_label'      => __( 'Default number of statistic days showing in the chart', 'wp-job-manager-stats' ),
					'desc'          => '',
				),

			) ),
		);

		/* Select Stat Page */
		$settings['job_pages'][1][] = array(
			'name'        => 'wp_job_manager_stats_page_id',
			'std'         => '',
			'label'       => __( 'Stats Dashboard Page', 'wp-job-manager-stats' ),
			'desc'        => __( 'Select the page where you have placed the [stats_dashboard] shortcode. This lets the plugin know where the chart is located.', 'wp-job-manager-stats' ),
			'type'        => 'page',
		);

		return $settings;
	}

	/**
	 * Add Plugin Updater license field
	 *
	 * Separate so it is at the end of the list.
	 *
	 * @since 2.2.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function add_license_field( $settings ) {
		$settings['wpjms_settings'][1][] = array(
			'name'			=> 'wp-job-manager-stats',
			'type'          => 'wp-job-manager-stats_license',
			'std'			=> '',
			'placeholder'	=> '',
			'label'			=> __( 'License Key', 'wp-job-manager-stats' ),
			'desc'			=> __( 'Enter the license key you received with your purchase receipt to continue receiving plugin updates.', 'wp-job-manager-stats' ),
			'attributes'	=> array(),
		);

		return $settings;
	}
	/*
	 Install Pages
	------------------------------------------ */

	/**
	 * Create the page where the statistics will show.
	 */
	public function create_pages() {

		/* Install Pages */
		if ( ! get_option( 'wp_job_manager_stats_page_id' ) && ! get_option( 'wp_job_manger_stats_installed_pages' ) && current_user_can( 'manage_options' ) ) {

			/* Add Admin notice */
			add_action( 'admin_notices', array( $this, 'admin_notice_install_pages' ) );

			/* Check request and nonce and caps */
			if ( isset( $_GET['install_pages'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'wpjms-install_pages' ) && current_user_can( 'manage_options' ) ) {

				/* Install Pages */
				if ( 1 == $_GET['install_pages'] ) {

					/* Create page */
					$page_id = wp_insert_post( array(
						'post_type'     => 'page',
						'post_title'    => __( 'Listings Stats Dashboard', 'wp-job-manager-stats' ),
						'post_status'   => 'publish',
						'post_content'  => '[stats_dashboard]',
					) );

					/* Update setting */
					update_option( 'wp_job_manager_stats_page_id', $page_id );

					/* Notice that page is ready. */
					add_action( 'admin_notices', array( $this, 'admin_notice_pages_installed' ) );
				}

				/* Hide notice after action */
				add_action( 'admin_head', array( $this, 'hide_notice' ) );

				/* Track action: do not repeat process. */
				update_option( 'wp_job_manger_stats_installed_pages', true );
			}
		}
	}

	/**
	 * Add a admin notice when the pages are not yet installed (and the notice has not been dismissed).
	 */
	public function admin_notice_install_pages() {
		?>
		<div class="notice notice-info wpjms-install-pages-notice">

			<p>
				<strong><?php _e( 'Thanks for using WP Job Manager Stats!', 'wp-job-manager-stats' );
				?></strong><br/>
				<?php _e( 'Your users are almost ready to start tracking their listing statistics.', 'wp-job-manager-stats' ); ?>
			</p>

			<p>
				<a class='button-primary' href='<?php echo esc_url( add_query_arg( array(
					'install_pages' => 1,
					'nonce' => wp_create_nonce( 'wpjms-install_pages' ),
				) ) ); ?>'><?php _e( 'Install Content', 'wp-job-manager-stats' ); ?></a>

				<a class='skip button-secondary' href='<?php echo esc_url( add_query_arg( array(
					'install_pages' => 0,
					'nonce' => wp_create_nonce( 'wpjms-install_pages' ),
				) ) ); ?>'><?php _e( 'Skip Installation', 'wp-job-manager-stats' ); ?></a>
			</p>

		</div>
		<?php
	}

	/**
	 * Pages Installed
	 *
	 * @since 2.0.0
	 */
	public function admin_notice_pages_installed() {
		$page_id = wpjms_stat_page_id();
		if ( ! $page_id ) {
			return false;
		}
		?><div class="updated wpjms-pages-ready-notice">

			<p><?php
				_e( 'Content and settings successfully setup.', 'wp-job-manager-stats' ); ?>
			</p>

			<p>
				<?php if ( $edit_url = get_edit_post_link( $page_id ) ) { ?>
					<a class='button-primary' href='<?php echo esc_url( $edit_url ); ?>'><?php
					_e( 'Edit Page', 'wp-job-manager-stats' ); ?></a>
				<?php } ?>
				
				<a class='button-secondary' href='<?php echo esc_url( get_permalink( $page_id ) ); ?>'><?php
					_e( 'View Page', 'wp-job-manager-stats' );
				?></a>
			</p>

		</div><?php
	}
	/*
	 Utility
	------------------------------------------ */

	/**
	 * Hide Notice
	 *
	 * @since 2.0.0
	 */
	public function hide_notice() {
		?>
		<style type="text/css" id="wpjms_hide_notice">
			.wpjms-install-pages-notice{display:none;}
		</style>
		<?php
	}

	/**
	 * Utility: Sanitize Checkbox
	 */
	public function sanitize_checkbox( $input ) {
		return $input ? 1 : 0;
	}
}
