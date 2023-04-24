<?php
/**
 * Output a setting for WP Job Manager.
 *
 * @since 1.0.0
 *
 * @package PluginUpdater
 * @category Integrations
 * @author Astoundify
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Job Manager Integrations.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Astoundify_PluginUpdater_Integration_WPJobManager {

	/**
	 * Plugin
	 *
	 * @since 1.0.0
	 * @var Astoundify_PluginUpdater_Plugin
	 */
	protected $plugin;

	/**
	 * License
	 *
	 * @since 1.0.0
	 * @var Astoundify_PluginUpdater_Plugin
	 */
	protected $license;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin File.
	 * @return void
	 */
	public function __construct( $plugin_file ) {
		$this->plugin = new Astoundify_PluginUpdater_Plugin( $plugin_file );
		$this->license = new Astoundify_PluginUpdater_License( $plugin_file );

		// Add a setting with a type the same name of the plugin slug.
		add_action( 'wp_job_manager_admin_field_' . $this->plugin->get_slug() . '_license', array( $this, 'license_field' ), 10, 4 );
	}

	/**
	 * Output field.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $option      Option.
	 * @param array  $attributes  Input attributes.
	 * @param string $value       Option value.
	 * @param string $placeholder Placeholder.
	 * @return void
	 */
	public function license_field( $option, $attributes, $value, $placeholder ) {
		$status = $this->license->get_status();
		$deactivate_link = Astoundify_PluginUpdater_Helpers::deactivate_license_link(
			$this->plugin->get_file(),
			admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' )
		);
?>

<input id="setting-<?php echo esc_attr( $option['name'] ); ?>" class="regular-text" type="text" name="<?php echo esc_attr( $option['name'] ); ?>" value="<?php esc_attr( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> />

<?php if ( $option['desc'] ) : ?>
	<p class="description"><?php echo wp_kses_post( $option['desc'] ); ?></p>
<?php endif; ?>

<?php if ( false !== $status && 'valid' === $status ) : ?>
	<p>
		<a href="<?php echo esc_url( $deactivate_link ); ?>" class="button-secondary"><?php esc_html_e( 'Deactivate', 'placeholder' ); ?></a>
	</p>
<?php endif; ?>

<?php
	}

}
