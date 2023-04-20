<?php

$str      = get_home_url();
$site_url = preg_replace( '#^https?://#', '', $str );

return array(
	/*
	 * Plugins short name appears on the License Menu Page
	 */
	'pluginShortName'   => EDD_LDCC_ITEM_NAME,

	/*
	 * this slug is used to store the data in db. License is checked using two options viz edd_<slug>_license_key and edd_<slug>_license_status
	 */
	'pluginSlug'        => 'learndash-content-cloner',

	/*
	 * Download Id on EDD Server(1234 is dummy id please use your plugins ID)
	 */
	'itemId'            => 34202,

	/*
	 * Current Version of the plugin.
	 */
	'pluginVersion'     => LDCC_VERSION,

	/*
	 * Under this Name product should be created on WisdmLabs Site
	 */
	'pluginName'        => EDD_LDCC_ITEM_NAME,

	/*
	 * Url where program pings to check if update is available and license validity
	 */
	'storeUrl'          => EDD_LDCC_STORE_URL,

	/*
	 * Site url which will pass in API request.
	 */
	'siteUrl'           => $site_url,

	/*
	 * Author Name
	 */
	'authorName'        => 'WisdmLabs',

	/*
	 * Text Domain used for translation
	 */
	'pluginTextDomain'  => 'ld-content-cloner',

	/*
	 * Base Url for accessing Files
	 */
	'baseFolderUrl'     => plugins_url( '/', __FILE__ ),

	/*
	 * Base Directory path for accessing Files
	 */
	'baseFolderDir'     => untrailingslashit( plugin_dir_path( __FILE__ ) ),

	/*
	 * Plugin Main file name
	 */
	'mainFileName'      => 'ld-content-cloner.php',

	/**
	 * Set true if theme
	 */
	'isTheme'           => false,

	/**
	*  Changelog page link for theme
	*  Set false for plugin
	*/
	'themeChangelogUrl' => false,


	/*
	 * Dependent plugins for learndash content cloner
	 */
	'dependencies'      => array(
		'learndash' => LEARNDASH_VERSION,
	),
);
