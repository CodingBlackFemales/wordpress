<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Settings_Setup::init();

/**
 * Settings Setup.
 *
 * @since 2.6.0
 */
class WPJMS_Settings_Setup {

	/**
	 * Init
	 *
	 * @since 3.7.0
	 * @access public
	 *
	 * @return void
	 */
	public static function init() {
		if ( get_option( 'wp_job_manager_stats_page_id' ) ) {
			return;
		}

		// Load library.
		require_once( WPJMS_PATH . 'vendor/astoundify/plugin-setup/astoundify-pluginsetup.php' );

		$config = array(
			'id'           => 'wp-job-manager-stats-setup',
			'capability'   => 'manage_options',
			'menu_title'   => __( 'Stats Setup', 'wp-job-manager-stats' ),
			'page_title'   => __( 'Stats for WP Job Manager Setup', 'wp-job-manager-stats' ),
			'redirect'     => true,
			'steps'        => array( // Steps must be using 1, 2, 3... in order, last step have no handler.
				'1' => array(
					'view'    => array( __CLASS__, 'step1_view' ),
					'handler' => array( __CLASS__, 'step1_handler' ),
				),
				'2' => array(
					'view'    => array( __CLASS__, 'step2_view' ),
				),
			),
			'labels'       => array(
				'next_step_button' => __( 'Submit', 'wp-job-manager-stats' ),
				'skip_step_button' => __( 'Skip', 'wp-job-manager-stats' ),
			),
		);

		// Init setup.
		new \Astoundify_PluginSetup( $config );
	}

	/**
	 * Step 1 View.
	 *
	 * @since 3.7.0
	 */
	public static function step1_view() {
?>

<p><?php _e( 'Thanks for installing <em>Stats for WP Job Manager</em>!', 'wp-job-manager-stats' ); ?> <?php _e( 'This setup wizard will help you get started by creating stats dashboard page.', 'wp-job-manager-stats' ); ?></p>

<p><?php printf( __( 'If you want to skip the wizard and setup the page and shortcode yourself manually, the process is still reletively simple. Refer to the %1$sdocumentation%2$s for help.', 'wp-job-manager-stats' ), '<a href="http://docs.astoundify.com/category/770-wp-job-manager---stats" target="_blank">', '</a>' ); ?></p>

<h2 class="title"><?php esc_html_e( 'Stats Dashboard Setup', 'wp-job-manager-stats' ); ?></h2>

<p><?php printf( __( '<em>Stats for WP Job Manager</em> includes a %1$sshortcode%2$s which can be used within your %3$spage%2$s to output the stats. This can be created for you below.', 'wp-job-manager-stats' ), '<a href="http://codex.wordpress.org/Shortcode" title="What is a shortcode?" target="_blank" class="help-page-link">', '</a>', '<a href="http://codex.wordpress.org/Pages" target="_blank" class="help-page-link">' ); ?></p>

<table class="widefat">
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th><?php esc_html_e( 'Page Title', 'wp-job-manager-stats' ); ?></th>
			<th><?php esc_html_e( 'Page Description', 'wp-job-manager-stats' ); ?></th>
			<th><?php esc_html_e( 'Content Shortcode', 'wp-job-manager-stats' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><input type="checkbox" checked="checked" name="stats-page" /></td>
			<td><input type="text" value="<?php echo esc_attr__( 'Stats Dashboard', 'wp-job-manager-stats' ); ?>" name="stats-page-title" /></td>
			<td>
				<p><?php esc_html_e( 'Statistics Dashboard Page.', 'wp-job-manager-stats' ); ?></p>
			</td>
			<td><code>[stats_dashboard]</code></td>
		</tr>
	</tbody>
</table>

<?php
	}

	/**
	 * Step 1 Handler.
	 *
	 * @since 3.7.0
	 */
	public static function step1_handler() {
		if ( ! isset( $_POST['stats-page'] ) ) {
			return;
		}

		// Page Title.
		$title = isset( $_POST['stats-page-title'] ) && $_POST['stats-page-title'] ? esc_html( $_POST['stats-page-title'] ) : esc_html__( 'Stats Dashboard', 'wp-job-manager-stats' );

		// Create page.
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => get_current_user_id(),
			'post_name'      => sanitize_title( $title ),
			'post_title'     => $title,
			'post_content'   => '[stats_dashboard]',
			'post_parent'    => 0,
			'comment_status' => 'closed',
		);
		$page_id = wp_insert_post( $page_data );

		// Update Option.
		update_option( 'wp_job_manager_stats_page_id', intval( $page_id ) );
	}

	/**
	 * Step 2 View.
	 *
	 * @since 3.7.0
	 */
	public static function step2_view() {
?>
<h3><?php _e( 'All Done!', 'wp-job-manager-stats' ); ?></h3>

<p><?php _e( "Looks like you're all set to start using the plugin. In case you're wondering where to go next:", 'wp-job-manager-stats' ); ?></p>

<ul>
	<li><a href="<?php echo admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ); ?>"><?php _e( 'Adjust the plugin settings.', 'wp-job-manager-stats' ); ?></a></li>
</ul>
<?php
	}
}
