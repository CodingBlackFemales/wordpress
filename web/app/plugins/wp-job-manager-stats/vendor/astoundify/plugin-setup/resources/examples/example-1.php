<?php
// Initialize Class.
APS_Example::init();

/**
 * Plugin Setup Example.
 * To test this example, include this file from main plugin file.
 *
 * @since 1.0.0
 */
class APS_Example {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		$config = array(
			'id'           => 'aps-ex-setup',
			'capability'   => 'manage_options',
			'menu_title'   => __( 'Setup Plugin', 'example' ),
			'page_title'   => __( 'Setup Plugin', 'example' ),
			'redirect'     => true,
			'steps'        => array( // Steps must be using 1, 2, 3... in order, last step have no handler.
				'1' => array(
					'title'   => __( 'Site Title', 'example' ),
					'view'    => array( __CLASS__, 'step1_view' ),
					'handler' => array( __CLASS__, 'step1_handler' ),
				),
				'2' => array(
					'title'   => __( 'Tagline', 'example' ),
					'view'    => array( __CLASS__, 'step2_view' ),
					'handler' => array( __CLASS__, 'step2_handler' ),
				),
				'3' => array(
					'view'    => array( __CLASS__, 'step3_view' ),
				),
			),
			'labels'       => array(
				'next_step_button' => __( 'Next Step', 'example' ),
				'skip_step_button' => __( 'Skip', 'example' ),
			),
		);

		// Init setup.
		new Astoundify_PluginSetup( $config );
	}

	/**
	 * Step 1 View.
	 *
	 * @since 1.0.0
	 */
	public static function step1_view() {
?>

<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="blogname"><?php _e( 'Site Title', 'example' ); ?></label>
			</th>
			<td>
				<input id="blogname" name="blogname" type="text" value="<?php echo esc_attr( get_option( 'blogname' ) ); ?>" class="regular-text">
			</td>
		</tr>
	</tbody>
</table>

<?php
	}

	/**
	 * Step 1 Handler.
	 *
	 * @since 1.0.0
	 */
	public static function step1_handler() {
		$updated = update_option( 'blogname', wp_kses_post( $_POST['blogname'] ) );

		// Show a notice. Optional.
		if ( $updated ) {
			add_action( 'admin_notices', function() {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Blog Name Updated', 'example' ); ?></p>
				</div>
				<?php
			} );
		}
	}

	/**
	 * Step 2 View.
	 *
	 * @since 1.0.0
	 */
	public static function step2_view() {
?>

<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="blogdescription"><?php _e( 'Tagline', 'example' ); ?></label>
			</th>
			<td>
				<input id="blogdescription" name="blogdescription" type="text" value="<?php echo esc_attr( get_option( 'blogdescription' ) ); ?>" class="regular-text">
			</td>
		</tr>
	</tbody>
</table>

<?php
	}

	/**
	 * Step 2 Handler
	 *
	 * @since 1.0.0
	 */
	public static function step2_handler() {
		$updated = update_option( 'blogdescription', wp_kses_post( $_POST['blogdescription'] ) );

		// Show a notice. Optional.
		if ( $updated ) {
			add_action( 'admin_notices', function() {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Tagline Updated', 'example' ); ?></p>
				</div>
				<?php
			} );
		}
	}

	/**
	 * Step 3 View (Final)
	 *
	 * @since 1.0.0
	 */
	public static function step3_view() {
?>

<p><?php esc_html_e( 'All done. Thank you! You can change your settings in ', 'example' ); ?></p>
<p><?php esc_html_e( 'You can change your settings anytime in Settings', 'example' ); ?></p>
<p><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Settings', 'example' ); ?></a></p>

<?php
	}

}
