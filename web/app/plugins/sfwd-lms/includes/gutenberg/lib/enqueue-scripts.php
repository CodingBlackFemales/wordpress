<?php
/**
 * Enqueue scripts and stylesheets for Blocks
 *
 * @package LearnDash
 * @since 2.5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues block editor styles and scripts.
 *
 * Fires on `enqueue_block_editor_assets` hook.
 *
 * @since 2.5.8
 */
function learndash_editor_scripts() {
	// Make paths variables so we don't write em twice ;).
	$learndash_block_path         = '../assets/js/index.js';
	$learndash_editor_style_path  = '../assets/css/blocks.editor.css';
	$learndash_block_dependencies = include dirname( dirname( __FILE__ ) ) . '/assets/js/index.asset.php';

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'ldlms-blocks-js',
		plugins_url( $learndash_block_path, __FILE__ ),
		$learndash_block_dependencies['dependencies'],
		LEARNDASH_SCRIPT_VERSION_TOKEN,
		true
	);

	// @TODO: This needs to move to an external JS library since it will be used globally.
	$ldlms = array(
		'settings' => array(),
	);

	$ldlms_settings['version'] = LEARNDASH_VERSION;

	$ldlms_settings['settings']['custom_labels'] = LearnDash_Settings_Section_Custom_Labels::get_section_settings_all();
	if ( ( is_array( $ldlms_settings['settings']['custom_labels'] ) ) && ( ! empty( $ldlms_settings['settings']['custom_labels'] ) ) ) {
		foreach ( $ldlms_settings['settings']['custom_labels'] as $key => $val ) {
			if ( empty( $val ) ) {
				$ldlms_settings['settings']['custom_labels'][ $key ] = LearnDash_Custom_Label::get_label( $key );
				if ( substr( $key, 0, strlen( 'button' ) ) != 'button' ) {
					$ldlms_settings['settings']['custom_labels'][ $key . '_lower' ] = learndash_get_custom_label_lower( $key );
					$ldlms_settings['settings']['custom_labels'][ $key . '_slug' ]  = learndash_get_custom_label_slug( $key );
				}
			}
		}
	}

	$ldlms_settings['settings']['per_page']            = LearnDash_Settings_Section_General_Per_Page::get_section_settings_all();
	$ldlms_settings['settings']['courses_taxonomies']  = LearnDash_Settings_Courses_Taxonomies::get_section_settings_all();
	$ldlms_settings['settings']['lessons_taxonomies']  = LearnDash_Settings_Lessons_Taxonomies::get_section_settings_all();
	$ldlms_settings['settings']['topics_taxonomies']   = LearnDash_Settings_Topics_Taxonomies::get_section_settings_all();
	$ldlms_settings['settings']['quizzes_taxonomies']  = LearnDash_Settings_Quizzes_Taxonomies::get_section_settings_all();
	$ldlms_settings['settings']['groups_taxonomies']   = LearnDash_Settings_Groups_Taxonomies::get_section_settings_all();
	$ldlms_settings['settings']['groups_cpt']          = array( 'public' => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) );
	$ldlms_settings['settings']['registration_fields'] = LearnDash_Settings_Section_Registration_Fields::get_section_settings_all();

	// Templates - Added LD v4.0.0.
	$ldlms_settings['templates'] = array(
		'active' => LearnDash_Theme_Register::get_active_theme_key(),
	);

	$themes = LearnDash_Theme_Register::get_themes();
	if ( ! is_array( $themes ) ) {
		$themes = array();
	}

	$themes_list = array();
	foreach ( $themes as $theme ) {
		$ldlms_settings['templates']['list'][ $theme['theme_key'] ] = $theme['theme_name'];
	}

	/**
	 * Include the LD post types with key.
	 *
	 * @since 4.0.0
	 */
	$ldlms_settings['post_types'] = LDLMS_Post_Types::get_all_post_types_set();

	$ldlms_settings['plugins'] = array();

	$ldlms_settings['plugins']['learndash-core']            = array();
	$ldlms_settings['plugins']['learndash-core']['version'] = LEARNDASH_VERSION;

	$ldlms_settings['plugins']['learndash-course-grid']                = array();
	$ldlms_settings['plugins']['learndash-course-grid']['enabled']     = learndash_enqueue_course_grid_scripts();
	$ldlms_settings['plugins']['learndash-course-grid']['col_default'] = 3;
	$ldlms_settings['plugins']['learndash-course-grid']['col_max']     = 12;

	if ( true === $ldlms_settings['plugins']['learndash-course-grid']['enabled'] ) {
		if ( defined( 'LEARNDASH_COURSE_GRID_COLUMNS' ) ) {
			$col_default = intval( LEARNDASH_COURSE_GRID_COLUMNS );
			if ( ( ! empty( $col_default ) ) && ( $col_default > 0 ) ) {
				$ldlms_settings['plugins']['learndash-course-grid']['col_default'] = $col_default;
			}
		}

		if ( defined( 'LEARNDASH_COURSE_GRID_MAX_COLUMNS' ) ) {
			$col_max = intval( LEARNDASH_COURSE_GRID_MAX_COLUMNS );
			if ( ( ! empty( $col_max ) ) && ( $col_max > 0 ) ) {
				$ldlms_settings['plugins']['learndash-course-grid']['col_max'] = $col_max;
			}
		}
	}

	$ldlms_settings['meta']                   = array();
	$ldlms_settings['meta']['posts_per_page'] = get_option( 'posts_per_page' ); // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page

	$ldlms_settings['meta']['post']              = array();
	$ldlms_settings['meta']['post']['post_id']   = 0;
	$ldlms_settings['meta']['post']['post_type'] = '';
	$ldlms_settings['meta']['post']['editing']   = '';
	$ldlms_settings['meta']['post']['course_id'] = 0;

	if ( is_admin() ) {
		$current_screen = get_current_screen();
		if ( 'post' === $current_screen->base ) {

			global $post, $post_type, $editing;
			$ldlms_settings['meta']['post'] = array();

			$ldlms_settings['meta']['post']['post_id']   = $post->ID;
			$ldlms_settings['meta']['post']['post_type'] = $post_type;
			$ldlms_settings['meta']['post']['editing']   = $editing;

			$ldlms_settings['meta']['post']['course_id'] = 0;

			if ( ! empty( $post_type ) ) {
				$course_post_types = LDLMS_Post_Types::get_post_types( 'course' );

				if ( 'sfwd-courses' === $post_type ) {
					$ldlms_settings['meta']['post']['course_id'] = $post->ID;
				} elseif ( in_array( $post_type, $course_post_types, true ) ) {
					$ldlms_settings['meta']['post']['course_id'] = learndash_get_course_id();
				}
			}
		}
	}

	// Load the MO file translations into wp.i18n script hook.
	learndash_load_inline_script_locale_data();

	wp_localize_script( 'ldlms-blocks-js', 'ldlms_settings', $ldlms_settings );

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'ldlms-blocks-editor-css',
		plugins_url( $learndash_editor_style_path, __FILE__ ),
		array(),
		LEARNDASH_SCRIPT_VERSION_TOKEN
	);
	wp_style_add_data( 'ldlms-blocks-editor-css', 'rtl', 'replace' );

	// Call our function to load CSS/JS used by the shortcodes.
	learndash_load_resources();

	$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
	if ( ! empty( $filepath ) ) {
		wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
		wp_style_add_data( 'learndash_pager_css', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
	}

	$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
	if ( ! empty( $filepath ) ) {
		wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
		$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
	}
}
// Hook scripts function into block editor hook.
add_action( 'enqueue_block_editor_assets', 'learndash_editor_scripts' );

/**
 * Enqueues the required styles and scripts for the course grid.
 *
 * @since 2.5.9
 *
 * @return boolean Returns true if the assets are enqueued otherwise false.
 */
function learndash_enqueue_course_grid_scripts() {

	// Check if Course Grid add-on is installed.
	if ( ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) && ( file_exists( LEARNDASH_COURSE_GRID_FILE ) ) ) {
		// Newer versions of Course Grid have a function to load resources.
		if ( function_exists( 'learndash_course_grid_load_resources' ) ) {
			learndash_course_grid_load_resources();
			return true;
		}
	}

	return false;
}


/**
 * Registers a custom block category.
 *
 * Fires on `block_categories` hook.
 *
 * @since 2.6.0
 *
 * @param array         $block_categories Optional. An array of current block categories. Default empty array.
 * @param WP_Post|false $post             Optional. The `WP_Post` instance of post being edited. Default false.
 *
 * @return array An array of block categories.
 */
function learndash_block_categories( $block_categories = array(), $post = false ) {
	if ( is_array( $block_categories ) ) {
		if ( ! in_array( 'learndash-blocks', wp_list_pluck( $block_categories, 'slug' ), true ) ) {
			if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, LDLMS_Post_Types::get_post_types(), true ) ) ) {
				$block_categories = array_merge(
					array(
						array(
							'slug'  => 'learndash-blocks',
							'title' => esc_html__( 'LearnDash LMS Blocks', 'learndash' ),
							'icon'  => false,
						),
					),
					$block_categories
				);
			} else {
				$block_categories[] = array(
					'slug'  => 'learndash-blocks',
					'title' => esc_html__( 'LearnDash LMS Blocks', 'learndash' ),
					'icon'  => false,
				);
			}
		}
	}

	// Always return $default_block_categories.
	return $block_categories;
}

/**
 * Registers a custom block category.
 *
 * Fires on `block_categories_all` hook.
 *
 * @since 3.4.2
 *
 * @param array                   $block_categories Optional. An array of current block categories. Default empty array.
 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
 *
 * @return array An array of block categories.
 */
function learndash_block_categories_all( $block_categories, $block_editor_context ) {
	if ( ( is_object( $block_editor_context ) ) && ( property_exists( $block_editor_context, 'post' ) ) && ( is_a( $block_editor_context->post, 'WP_Post' ) ) ) {
		$block_categories = learndash_block_categories( $block_categories, $block_editor_context->post );
	} else {
		$block_categories = learndash_block_categories( $block_categories );
	}

	return $block_categories;
}

/**
 * Register Block Pattern Categories.
 */
function learndash_block_pattern_categories() {
	register_block_pattern_category(
		'learndash',
		array(
			'label' => __( 'LearnDash', 'learndash' ),
		)
	);
}

/**
 * Register Block Patterns.
 */
function learndash_register_block_patterns() {
	register_block_pattern(
		'learndash/course-content',
		array(
			'title'       => __( 'Course Content Blocks', 'learndash' ),
			'categories'  => array( 'learndash' ),
			'description' => esc_html_x( 'Display the course or step content blocks collection.', 'Block pattern description', 'learndash' ),
			'content'     => "<!-- wp:learndash/ld-infobar /-->\n<!-- wp:learndash/ld-course-content /-->",
			'blockTypes'  => array( 'ld-course-content', 'ld-course-progress' ),
		)
	);
}

add_action(
	'learndash_init',
	function() {
		global $wp_version;

		if ( version_compare( $wp_version, '5.7.99', '>' ) ) {
			add_filter( 'block_categories_all', 'learndash_block_categories_all', 30, 2 );
		} else {
			add_filter( 'block_categories', 'learndash_block_categories', 30, 2 );
		}

		learndash_block_pattern_categories();
		learndash_register_block_patterns();
	}
);

/**
 * Get the Legacy template not supported message.
 *
 * This message is shows on blocks and shortcodes which don't support the "Legacy"
 * templates.
 *
 * @since 4.0.0
 */
function learndash_get_legacy_not_supported_message() {
	$message = '';
	if ( 'legacy' === LearnDash_Theme_Register::get_active_theme_key() ) {
		$message = sprintf(
			// translators: placeholder: current template name.
			esc_html_x(
				'The current LearnDash template "%s" may not support this block. Please select a different template.',
				'placeholder: current template name',
				'learndash'
			),
			LearnDash_Theme_Register::get_active_theme_name()
		);
	}

	return $message;
}
