<?php
/**
 * @package CBF Academy
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since CBF Academy 1.0.0
 */
function cbf_academy_theme_languages() {
	/**
	 * Makes child theme available for translation.
	 * Translations can be added into the /languages/ directory.
	 */

	// Translate text from the PARENT theme.
	load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

	// Translate text from the CHILD theme only.
	// Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
	// load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'cbf_academy_theme_languages' );

/**
 * Version string for a theme asset: its modification time.
 *
 * The theme version only changes when it is bumped by hand, so browsers would
 * otherwise keep serving a cached copy of an edited stylesheet or script.
 *
 * @param string $path Path relative to the theme directory, e.g. `assets/css/custom.css`.
 * @return string
 */
function cbf_academy_asset_version( $path ) {
	$file = get_stylesheet_directory() . '/' . $path;

	return file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme( 'cbf-academy' )->get( 'Version' );
}

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since CBF Academy Theme  1.0.0
 */
function cbf_academy_theme_scripts_styles() {
	/**
	  * Scripts and Styles loaded by the parent theme can be unloaded if needed
	  * using wp_deregister_script or wp_deregister_style.
	  *
	  * See the WordPress Codex for more information about those functions:
	  * http://codex.wordpress.org/Function_Reference/wp_deregister_script
	  * http://codex.wordpress.org/Function_Reference/wp_deregister_style
	  **/
	// Styles
	wp_enqueue_style( 'cbf-academy-css', get_stylesheet_directory_uri() . '/assets/css/custom.css', false, cbf_academy_asset_version( 'assets/css/custom.css' ) );

	// Javascript
	wp_enqueue_script( 'cbf-academy-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', false, cbf_academy_asset_version( 'assets/js/custom.js' ) );
}
add_action( 'wp_enqueue_scripts', 'cbf_academy_theme_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

/**
 * Sets the correct database table prefix for BuddyBoss, based on the current subsite
 *
 * @param  string $base_prefix The base table prefix (e.g. `wp_`).
 * @return string
 */
function cbf_academy_bp_core_get_table_prefix( $base_prefix ) {
	if ( is_multisite() ) {
		$blog_id = get_current_blog_id();
		$base_prefix .= "{$blog_id}_";
	}

	return $base_prefix;
}
add_filter( 'bp_core_get_table_prefix', 'cbf_academy_bp_core_get_table_prefix' );

/**
 * Return true as 'buddypages-member-pages' option value for site admins
 *
 * @param bool   $value The value of the 'buddypages-member-pages' site option
 * @param string $option The option name
 * @return bool
 */
function cbf_academy_option_buddypages_member_pages( $value, $option ) {
	return ! is_admin() && current_user_can( 'manage_options' ) || $value;
}
add_filter( 'option_buddypages-member-pages', 'cbf_academy_option_buddypages_member_pages', 10, 2 );

add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_style(
			'cbf-academy-editor-style',
			get_stylesheet_directory_uri() . '/assets/css/editor-style.css',
			array(),
			cbf_academy_asset_version( 'assets/css/editor-style.css' )
		);
	}
);


/****************************** LESSON CONTENT ******************************/

/**
 * Post types whose content uses the CBF Academy content styles.
 *
 * LearnDash steps, plus synced patterns so they look right when edited on
 * their own screen.
 *
 * @return string[]
 */
function cbf_academy_content_post_types() {
	return array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'wp_block' );
}

/**
 * Whether the current request shows lesson content: a LearnDash page on the
 * front end, or the block editor for one of those post types.
 *
 * @return bool
 */
function cbf_academy_is_content_request() {
	if ( ! is_admin() ) {
		return is_singular( cbf_academy_content_post_types() );
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	return $screen && in_array( $screen->post_type, cbf_academy_content_post_types(), true );
}

/**
 * Enqueues the lesson content styles and their typefaces.
 *
 * Runs on enqueue_block_assets, so the styles reach the block editor canvas
 * (an iframe) as well as the front end, and patterns look the same in both.
 * The activity and callout patterns carry class names only; content.css holds
 * everything visual about them.
 */
function cbf_academy_content_assets() {
	if ( ! cbf_academy_is_content_request() ) {
		return;
	}

	wp_enqueue_style(
		'cbf-academy-fonts',
		'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Mono:wght@400&family=Atkinson+Hyperlegible+Next:ital,wght@0,400;0,700;1,400&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts versions its own files.
	);

	wp_enqueue_style(
		'cbf-academy-content',
		get_stylesheet_directory_uri() . '/assets/css/content.css',
		array( 'cbf-academy-fonts' ),
		cbf_academy_asset_version( 'assets/css/content.css' )
	);
}
add_action( 'enqueue_block_assets', 'cbf_academy_content_assets' );

/**
 * Registers the "CBF Academy" pattern category.
 *
 * Holds the activity and callout patterns in patterns/. The synced patterns
 * (learning checkpoint, Q&A, break) are created from
 * synced-patterns/ with `wp cbf patterns sync`, and appear in the same
 * category through the matching pattern category term.
 */
function cbf_academy_pattern_category() {
	register_block_pattern_category(
		'cbf-academy',
		array(
			'label'       => __( 'CBF Academy', 'cbf-academy' ),
			'description' => __( 'Activities and callouts for lesson content.', 'cbf-academy' ),
		)
	);
}
add_action( 'init', 'cbf_academy_pattern_category' );

/**
 * Offers only the CBF Academy patterns in lesson content.
 *
 * Core, Otter Blocks and the form plugins register dozens of page-building
 * patterns that have no place in a lesson. Each pattern outside the CBF
 * Academy category is limited to the site's other post types, so the editor
 * hides it in LearnDash content and synced patterns but still offers it on
 * pages. This runs after every plugin has registered its patterns.
 */
function cbf_academy_limit_patterns() {
	$registry   = WP_Block_Patterns_Registry::get_instance();
	$post_types = array_values( array_diff( get_post_types( array( 'show_in_rest' => true ) ), cbf_academy_content_post_types() ) );

	foreach ( $registry->get_all_registered() as $pattern ) {
		if ( in_array( 'cbf-academy', $pattern['categories'] ?? array(), true ) ) {
			continue;
		}

		$name = $pattern['name'];
		unset( $pattern['name'] );

		$pattern['postTypes'] = isset( $pattern['postTypes'] )
			? array_values( array_intersect( $pattern['postTypes'], $post_types ) )
			: $post_types;

		$registry->unregister( $name );
		$registry->register( $name, $pattern );
	}
}
add_action( 'init', 'cbf_academy_limit_patterns', PHP_INT_MAX );

// The editor would otherwise fetch featured patterns from WordPress.org.
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Lets editors change the blocks inside the patterns they insert.
 *
 * WordPress 7 treats a group inserted from an unsynced pattern as a locked,
 * content-only section: its text can be edited, but blocks can't be added,
 * pasted or removed. Activities are templates to fill in, often with code
 * blocks and extra steps, so that lock gets in the way. Synced patterns stay
 * locked, as they should.
 *
 * @param array                   $settings Block editor settings.
 * @param WP_Block_Editor_Context $context  Editor context.
 * @return array
 */
function cbf_academy_block_editor_settings( $settings, $context ) {
	if ( isset( $context->post ) && in_array( $context->post->post_type, cbf_academy_content_post_types(), true ) ) {
		$settings['disableContentOnlyForUnsyncedPatterns'] = true;
	}

	return $settings;
}
add_filter( 'block_editor_settings_all', 'cbf_academy_block_editor_settings', 10, 2 );
