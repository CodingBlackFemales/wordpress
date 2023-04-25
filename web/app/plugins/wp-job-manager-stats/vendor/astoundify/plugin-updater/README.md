# Astoundify Plugin Updater

Major 🔑 (Management)

## Usage

This drop-in relies on the Easy Digital Downloads - Software Licensing plugin to
do the heavy lifting. All this does is offer a way to manage the license key
itself. It does not do any of the notifications, update processing, etc. 

See
[`lib/EDD_SL_Plugin_Updater.php`](https://github.com/Astoundify/plugin-updater/blob/master/updater/lib/EDD_SL_Plugin_Updater.php)

### Settings API

A setting with the same `option_name` as the plugin's slug will automatically
activate and deactivate on save and clearing of the value.

```php
function edd_sl_plugin_updater() {
	require_once( dirname( __FILE__ ) . '/vendor/astoundify/plugin-updater/astoundify-pluginupdater.php' );
	
	new Astoundify_PluginUpdater( __FILE__ );
}
add_action( 'admin_init', 'edd_sl_plugin_updater', 9 );
```

### WP Job Manager

```php
function wpjm_plugin_updater() {
	require_once( dirname( __FILE__ ) . '/vendor/astoundify/plugin-updater/astoundify-pluginupdater.php' );
	
	new Astoundify_PluginUpdater( __FILE__ );
	new Astoundify_PluginUpdater_Integration_WPJobManager( __FILE__ );
}
add_action( 'admin_init', 'wpjm_plugin_updater', 9 );
```

Add the setting:

```php
array(
	'name' => 'wp-job-manager-stats',         // plugin slug
	'type' => 'wp-job-manager-stats_license', // {plugin_slug}_license
	'std' > '',
	'placeholder' => '',
	'label'	=> __( 'License Key', 'wp-job-manager-plugin' ),
	'desc' => __( 'Enter the license key you received with your purchase receipt to continue receiving plugin updates.', 'wp-job-manager-plugin' )
)
```
