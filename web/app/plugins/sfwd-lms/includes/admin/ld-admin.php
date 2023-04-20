<?php
/**
 * Functions for wp-admin
 *
 * @since 2.1.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the LearnDash post type to the admin body class.
 *
 * Fires on `admin_body_class` hook.
 *
 * @since 2.5.8
 *
 * @param string $class Optional. The admin body CSS classes. Default empty.
 *
 * @return string Admin body CSS classes.
 */
function learndash_admin_body_class( $class = '' ) {
	global $learndash_post_types;

	$screen = get_current_screen();
	if ( in_array( $screen->id, $learndash_post_types, true ) ) {
		$class .= ' learndash-post-type ' . $screen->post_type;
	}

	if ( in_array( $screen->post_type, $learndash_post_types, true ) ) {
		$class .= ' learndash-screen';
	}

	if ( learndash_is_group_leader_user() ) {
		$class .= ' learndash-user-group-leader';
	} else {
		$class .= ' learndash-user-admin';
	}

	return $class;
}
add_filter( 'admin_body_class', 'learndash_admin_body_class' );

/**
 * Hides the top-level menus with no submenus.
 *
 * Fires on `admin_footer` hook.
 *
 * @since 2.1.0
 */
function learndash_hide_menu_when_not_required() {
	?>
		<script>
		jQuery( function() {
		if(jQuery(".toplevel_page_learndash-lms").length && jQuery(".toplevel_page_learndash-lms").find("li").length <= 1)
			jQuery(".toplevel_page_learndash-lms").hide();
		});
		</script>
	<?php
}

add_filter( 'admin_footer', 'learndash_hide_menu_when_not_required', 99 );

/**
 * Checks whether to load the admin assets.
 *
 * @global string  $pagenow
 * @global string  $typenow
 * @global WP_Post $post                 Global post object.
 * @global array   $learndash_post_types An array of LearnDash post types.
 * @global array   $learndash_pages      An array of LearnDash pages.
 *
 * @since 3.0.0
 *
 * @return boolean Returns true to load the admin assets otherwise false.
 */
function learndash_should_load_admin_assets() {
	global $pagenow, $post, $typenow;
	global $learndash_post_types, $learndash_pages;

	// Get post type.
	$post_type = get_post_type();
	if ( ! $post_type ) {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : $post_type; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$is_ld_page = false;
	if ( ( isset( $_GET['page'] ) ) && ( in_array( $_GET['page'], $learndash_pages, true ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_ld_page = true;
	}

	$is_ld_post_type = false;
	if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, $learndash_post_types, true ) ) ) {
		$is_ld_post_type = true;
	}

	$is_ld_pagenow = false;
	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, $learndash_post_types, true ) ) ) {
		$is_ld_pagenow = true;
	}

	$load_admin_assets = false;
	if ( ( true === $is_ld_page ) || ( true === $is_ld_post_type ) || ( true === $is_ld_pagenow ) ) {
		$load_admin_assets = true;
	}

	/**
	 * Filters whether to load the admin assets or not.
	 *
	 * @param boolean $load_admin_assets Whether to load admin assets.
	 */
	return apply_filters( 'learndash_load_admin_assets', $load_admin_assets );
}

/**
 * Enqueues the scripts and styles for admin.
 *
 * Fires on `admin_enqueue_scripts` hook.
 *
 * @global string  $pagenow
 * @global string  $typenow
 * @global WP_Post $post                    Global post object.
 * @global array   $learndash_assets_loaded An array of loaded styles and scripts.
 *
 * @since 2.1.0
 */
function learndash_load_admin_resources() {
	global $pagenow, $post, $typenow;
	global $learndash_assets_loaded;

	wp_enqueue_style(
		'learndash-admin-menu-style',
		LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-menu' . learndash_min_asset() . '.css',
		array(),
		LEARNDASH_SCRIPT_VERSION_TOKEN
	);
	wp_style_add_data( 'learndash-admin-menu-style', 'rtl', 'replace' );
	$learndash_assets_loaded['styles']['learndash-admin-menu-style'] = __FUNCTION__;

	wp_enqueue_script(
		'learndash-admin-menu-script',
		LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-menu' . learndash_min_asset() . '.js',
		array( 'jquery' ),
		LEARNDASH_SCRIPT_VERSION_TOKEN,
		true
	);
	wp_style_add_data( 'learndash-admin-menu-script', 'rtl', 'replace' );
	$learndash_assets_loaded['scripts']['learndash-admin-menu-script'] = __FUNCTION__;

	if ( learndash_should_load_admin_assets() ) {

		/**
		 * Needed for standalone Builders.
		 */
		// to get the tinyMCE editor.
		wp_enqueue_editor();

		// for media uploads.
		wp_enqueue_media();

		wp_enqueue_style(
			'learndash_style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style' . learndash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'learndash_style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['learndash_style'] = __FUNCTION__;

		wp_enqueue_style(
			'learndash-admin-style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-style' . learndash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'learndash-admin-style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['learndash-admin-style'] = __FUNCTION__;

		wp_enqueue_style(
			'sfwd-module-style',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module' . learndash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

		if ( ( 'edit.php' === $pagenow ) && ( in_array( $typenow, array( 'sfwd-essays', 'sfwd-assignment', 'sfwd-topic', 'sfwd-quiz' ), true ) ) ) {
			wp_enqueue_script(
				'sfwd-module-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/sfwd_module' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['sfwd-module-script'] = __FUNCTION__;
			wp_localize_script( 'sfwd-module-script', 'sfwd_data', array() );
		}
	}

	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) && ( 'sfwd-quiz' === $post->post_type ) ) {
		wp_enqueue_script(
			'wpProQuiz_admin_javascript',
			plugins_url( 'js/wpProQuiz_admin' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
			array( 'jquery' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);
		$learndash_assets_loaded['scripts']['wpProQuiz_admin_javascript'] = __FUNCTION__;
	}

	if ( ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) && ( 'sfwd-lessons' === $post->post_type ) ) {
		wp_enqueue_style(
			'ld-datepicker-ui-css',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/jquery-ui' . learndash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'ld-datepicker-ui-css', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['ld-datepicker-ui-css'] = __FUNCTION__;
	}

	if ( ( ( 'admin.php' === $pagenow ) && ( isset( $_GET['page'] ) ) && ( 'ldAdvQuiz' === $_GET['page'] ) ) && ( ( isset( $_GET['module'] ) ) && ( 'statistics' === $_GET['module'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_enqueue_style(
			'ld-datepicker-ui-css',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/css/jquery-ui' . learndash_min_asset() . '.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_style_add_data( 'ld-datepicker-ui-css', 'rtl', 'replace' );
		$learndash_assets_loaded['styles']['ld-datepicker-ui-css'] = __FUNCTION__;
	}
}
add_action( 'admin_enqueue_scripts', 'learndash_load_admin_resources' );

/**
 * Outputs the Reports Page.
 *
 * @since 2.1.0
 */
function learndash_lms_reports_page() {
	?>
		<div  id="learndash-reports"  class="wrap">
			<h1><?php esc_html_e( 'User Reports', 'learndash' ); ?></h1>
			<br>
			<div class="sfwd_settings_left">
				<div class=" " id="sfwd-learndash-reports_metabox">
					<div class="inside">
						<a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . esc_attr( wp_create_nonce( 'sfwd-nonce' ) ) . '&page_options=sfp_home_description&courses_export_submit=Export' ) ); ?>">
						<?php
						// translators: Export User Course Data Label.
						printf( esc_html_x( 'Export User %s Data', 'Export User Course Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						?>
						</a>
						<a class="button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=learndash-lms-reports&action=sfp_update_module&nonce-sfwd=' . esc_attr( wp_create_nonce( 'sfwd-nonce' ) ) . '&page_options=sfp_home_description&quiz_export_submit=Export' ) ); ?>">
						<?php
						printf(
						// translators: Export Quiz Data Label.
							esc_html_x( 'Export %s Data', 'Export Quiz Data Label', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						);
						?>
						</a>
						<?php
							/**
							 * Fires after report page buttons.
							 *
							 * @since 2.1.0
							 */
							do_action( 'learndash_report_page_buttons' );
						?>
					</div>
				</div>
			</div>
		</div>
	<?php
}

/**
 * Adds JavaScript code to the admin footer.
 *
 * @since 2.1.0
 *
 * @global string $learndash_current_page_link
 * @global string $parent_file
 * @global string $submenu_file
 *
 * @TODO We need to get rid of this JS logic and replace with filter to set the $parent_file
 * See:
 * https://developer.wordpress.org/reference/hooks/parent_file/
 * https://developer.wordpress.org/reference/hooks/submenu_file/
 */
function learndash_select_menu() {
	global $learndash_current_page_link;
	global $parent_file, $submenu_file;

	if ( ! empty( $learndash_current_page_link ) ) {
		?>
		<script type="text/javascript">
		//jQuery(window).on('load', function( $) {
			jQuery("body").removeClass("sticky-menu");
			jQuery("#toplevel_page_learndash-lms, #toplevel_page_learndash-lms > a").removeClass('wp-not-current-submenu' );
			jQuery("#toplevel_page_learndash-lms").addClass('current wp-has-current-submenu wp-menu-open' );
			jQuery("#toplevel_page_learndash-lms a[href='<?php echo esc_url( $learndash_current_page_link ); ?>']").parent().addClass("current");
		//});
		</script>
		<?php
	}
};

/**
 * Prints the AJAX lazy loaded element results.
 *
 * Fires on `learndash_element_lazy_loader` AJAX action.
 *
 * @since 2.2.1
 */
function learndash_element_lazy_loader() {

	$reply_data = array();

	if ( current_user_can( 'read' ) ) {
		if ( ( isset( $_POST['query_data']['nonce'] ) ) && ( ! empty( $_POST['query_data']['nonce'] ) ) ) {
			if ( ( isset( $_POST['query_data']['query_vars']['post_type'] ) ) && ( ! empty( $_POST['query_data']['query_vars']['post_type'] ) ) ) {
				if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['query_data']['nonce'] ) ), sanitize_text_field( wp_unslash( $_POST['query_data']['query_vars']['post_type'] ) ) ) ) {

					if ( ( isset( $_POST['query_data']['query_vars'] ) ) && ( ! empty( $_POST['query_data']['query_vars'] ) ) ) {
						$reply_data['query_data'] = $_POST['query_data']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

						if ( isset( $_POST['query_data']['query_type'] ) ) {
							switch ( $_POST['query_data']['query_type'] ) {
								case 'WP_Query':
									$query = new WP_Query( $_POST['query_data']['query_vars'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
									if ( $query instanceof WP_Query ) {
										if ( ! empty( $query->posts ) ) {
											$reply_data['html_options'] = '';
											foreach ( $query->posts as $p ) {
												if ( intval( $p->ID ) == intval( $_POST['query_data']['value'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
													$selected = ' selected="selected" ';
												} else {
													$selected = '';
												}
												$reply_data['html_options'] .= '<option ' . $selected . ' value="' . $p->ID . '">' . apply_filters( 'the_title', $p->post_title, $p->ID ) . '</option>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
											}
										}
									}
									break;

								case 'WP_User_Query':
									$query = new WP_User_Query( $_POST['query_data']['query_vars'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
									break;

								default:
									break;
							}
						}
					}
				}
			}
		}
	}

	echo wp_json_encode( $reply_data );

	wp_die(); // this is required to terminate immediately and return a proper response.
}
add_action( 'wp_ajax_learndash_element_lazy_loader', 'learndash_element_lazy_loader' );

/**
 * Adds the changelog link in plugin row meta.
 *
 * Fires on `plugin_row_meta` hook.
 *
 * @since 2.4.0
 *
 * @param array  $plugin_meta An array of the plugin's metadata.
 * @param string $plugin_file  Path to the plugin file.
 * @param array  $plugin_data An array of plugin data.
 * @param string $status      Status of the plugin.
 *
 * @return array An array of the plugin's metadata.
 */
function learndash_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( LEARNDASH_LMS_PLUGIN_KEY === $plugin_file ) {
		if ( ! isset( $plugin_meta['changelog'] ) ) {
			$plugin_meta['changelog'] = '<a target="_blank" href="https://www.learndash.com/changelog">' . esc_html__( 'Changelog', 'learndash' ) . '</a>';
		}
	}

	return $plugin_meta;
}
add_filter( 'plugin_row_meta', 'learndash_plugin_row_meta', 10, 4 );

/**
 * Overrides the post tag edit 'count' column to show only the related count for the LearnDash post types.
 *
 * Fires on `manage_edit-post_tag_columns` and `manage_edit-category_columns` hook.
 *
 * @since 2.4.0
 *
 * @param array $columns Optional. An array of column headers. Default empty array.
 *
 * @return array An array of column headers.
 */
function learndash_manage_edit_post_tag_columns( $columns = array() ) {
	if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $columns['posts'] ) ) {
				unset( $columns['posts'] );
			}
			$columns['ld_posts'] = esc_html__( 'Count', 'learndash' );
		}
	}

	return $columns;
}
add_filter( 'manage_edit-post_tag_columns', 'learndash_manage_edit_post_tag_columns' );
add_filter( 'manage_edit-category_columns', 'learndash_manage_edit_post_tag_columns' );

/**
 * Gets the custom column content for post_tag taxonomy in the terms list table.
 *
 * Fires on `manage_post_tag_custom_column` hook.
 *
 * @since 2.4.0
 *
 * @param string $column_content Column content. Default empty.
 * @param string $column_name    Name of the column.
 * @param int    $term_id        Term ID.
 *
 * @return string Taxonomy custom column content.
 */
function learndash_manage_post_tag_custom_column( $column_content, $column_name, $term_id ) {
	if ( 'ld_posts' === $column_name ) {
		if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query_args = array(
					'post_type'   => sanitize_text_field( wp_unslash( $_GET['post_type'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'post_status' => 'publish',
					'tag_id'      => $term_id,
					'fields'      => 'ids',
					'nopaging'    => true,
				);

				$query_results = new WP_Query( $query_args );
				if ( is_a( $query_results, 'WP_Query' ) ) {
					$count = count( $query_results->posts );
					if ( $count > 0 ) {
						$term = get_term_by( 'id', $term_id, 'category' );
						if ( is_a( $term, 'WP_Term' ) ) {
							$column_content = "<a href='" . esc_url(
								add_query_arg(
									array(
										'post_type' => sanitize_text_field( wp_unslash( $_GET['post_type'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										'taxonomy'  => 'post_tag',
										'post_tag'  => $term->slug,
									),
									'edit.php'
								)
							) . "'>" . count( $query_results->posts ) . '</a>';
						}
					} else {
						$column_content = 0;
					}
				}
			}
		}
	}
	return $column_content;
}
add_filter( 'manage_post_tag_custom_column', 'learndash_manage_post_tag_custom_column', 10, 3 );

/**
 * Gets the custom column content for category taxonomy in the terms list table.
 *
 * Fires on `manage_category_custom_column` hook.
 *
 * @since 2.4.0
 *
 * @param string $column_content Column content. Default empty.
 * @param string $column_name    Name of the column.
 * @param int    $term_id        Term ID.
 *
 * @return string Taxonomy custom column content.
 */
function learndash_manage_category_custom_column( $column_content, $column_name, $term_id ) {
	if ( 'ld_posts' === $column_name ) {
		if ( ( isset( $_GET['post_type'] ) ) && ( ! empty( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $_GET['post_type'], array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query_args = array(
					'post_type'   => sanitize_text_field( wp_unslash( $_GET['post_type'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'post_status' => 'publish',
					'cat'         => $term_id,
					'fields'      => 'ids',
					'nopaging'    => true,
				);

				$query_results = new WP_Query( $query_args );
				if ( is_a( $query_results, 'WP_Query' ) ) {
					$count = count( $query_results->posts );
					if ( $count > 0 ) {
						$column_content = "<a href='" . esc_url(
							add_query_arg(
								array(
									'post_type' => sanitize_text_field( wp_unslash( $_GET['post_type'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
									'taxonomy'  => 'category',
									'cat'       => $term_id,
								),
								'edit.php'
							)
						) . "'>" . count( $query_results->posts ) . '</a>';
					} else {
						$column_content = 0;
					}
				}
			}
		}
	}
	return $column_content;
}
add_filter( 'manage_category_custom_column', 'learndash_manage_category_custom_column', 10, 3 );

/**
 * Deletes all the LearnDash data.
 *
 * @since 2.4.5
 *
 * @global wpdb  $wpdb                 WordPress database abstraction object.
 * @global array $learndash_post_types An array of learndash post types.
 * @global array $learndash_taxonomies An array of learndash taxonomies.
 */
function learndash_delete_all_data() {
	global $wpdb, $learndash_post_types, $learndash_taxonomies;

	/**
	 * Under Multisite we don't even want to remove user data. This is because users and usermeta
	 * is shared. Removing the LD user data could result in lost information for other sites.
	 */
	if ( ! is_multisite() ) {
		// USER META SETTINGS.

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key='_sfwd-course_progress'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key='_sfwd-quizzes'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'completed_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_%_access_from'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_completed_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_course_expired_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_group_users_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_group_leaders_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'ld-upgraded-user-meta-courses'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'ld-upgraded-user-meta-quizzes'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'course_points'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	// CUSTOM OPTIONS.

	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE 'learndash_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE 'wpProQuiz_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// CUSTOMER POST TYPES.

	$ld_post_types = '';
	foreach ( $learndash_post_types as $post_type ) {
		if ( ! empty( $ld_post_types ) ) {
			$ld_post_types .= ',';
		}
		$ld_post_types .= "'" . $post_type . "'";
	}

	$post_ids = $wpdb->get_col( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type IN (' . $ld_post_types . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	if ( ! empty( $post_ids ) ) {

		$offset = 0;

		while ( true ) {
			$post_ids_part = array_slice( $post_ids, $offset, 1000 );
			if ( empty( $post_ids_part ) ) {
				break;
			} else {
				$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . ' WHERE post_id IN (' . implode( ',', $post_ids ) . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( 'DELETE FROM ' . $wpdb->posts . ' WHERE post_parent IN (' . implode( ',', $post_ids ) . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( 'DELETE FROM ' . $wpdb->posts . ' WHERE ID IN (' . implode( ',', $post_ids ) . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared,

				$offset += 1000;
			}
		}
	}

	// CUSTOM TAXONOMIES & TERMS.

	foreach ( $learndash_taxonomies as $taxonomy ) {
		// Prepare & execute SQL.
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder

			// Delete Terms.
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		// Delete Taxonomy.
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	// CUSTOM DB TABLES.

	$learndash_db_tables = LDLMS_DB::get_tables();
	if ( ! empty( $learndash_db_tables ) ) {
		foreach ( $learndash_db_tables as $table_name ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( 'DROP TABLE ' . $table_name ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			}
		}
	}

	// USER ROLES AND CAPABILITIES.

	remove_role( 'group_leader' );

	// Remove any user/role capabilities we added.
	$roles = get_editable_roles();
	if ( ! empty( $roles ) ) {
		foreach ( $roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( ( $role ) && ( $role instanceof WP_Role ) ) {
				$role->remove_cap( 'read_assignment' );
				$role->remove_cap( 'edit_assignment' );
				$role->remove_cap( 'edit_assignments' );
				$role->remove_cap( 'edit_others_assignments' );
				$role->remove_cap( 'publish_assignments' );
				$role->remove_cap( 'read_assignment' );
				$role->remove_cap( 'read_private_assignments' );
				$role->remove_cap( 'delete_assignment' );
				$role->remove_cap( 'edit_published_assignments' );
				$role->remove_cap( 'delete_others_assignments' );
				$role->remove_cap( 'delete_published_assignments' );

				$role->remove_cap( 'group_leader' );
				$role->remove_cap( 'enroll_users' );

				$role->remove_cap( 'edit_essays' );
				$role->remove_cap( 'edit_others_essays' );
				$role->remove_cap( 'publish_essays' );
				$role->remove_cap( 'read_essays' );
				$role->remove_cap( 'read_private_essays' );
				$role->remove_cap( 'delete_essays' );
				$role->remove_cap( 'edit_published_essays' );
				$role->remove_cap( 'delete_others_essays' );
				$role->remove_cap( 'delete_published_essays' );

				$role->remove_cap( 'wpProQuiz_show' );
				$role->remove_cap( 'wpProQuiz_add_quiz' );
				$role->remove_cap( 'wpProQuiz_edit_quiz' );
				$role->remove_cap( 'wpProQuiz_delete_quiz' );
				$role->remove_cap( 'wpProQuiz_show_statistics' );
				$role->remove_cap( 'wpProQuiz_reset_statistics' );
				$role->remove_cap( 'wpProQuiz_import' );
				$role->remove_cap( 'wpProQuiz_export' );
				$role->remove_cap( 'wpProQuiz_change_settings' );
				$role->remove_cap( 'wpProQuiz_toplist_edit' );
				$role->remove_cap( 'wpProQuiz_toplist_edit' );
			}
		}
	}

	// ASSIGNMENT & ESSAY UPLOADS.

	$url_link_arr   = wp_upload_dir();
	$assignment_dir = $url_link_arr['basedir'] . '/assignments';
	learndash_recursive_rmdir( $assignment_dir );

	$essays_dir = $url_link_arr['basedir'] . '/essays';
	learndash_recursive_rmdir( $essays_dir );

	$ld_template_dir = $url_link_arr['basedir'] . '/template';
	learndash_recursive_rmdir( $ld_template_dir );
}

/**
 * Loads the plugin translations into `wp.i18n` for use in JavaScript.
 *
 * @since 3.0.0
 */
function learndash_load_inline_script_locale_data() {
	static $loaded = false;

	if ( false === $loaded ) {
		$loaded      = true;
		$locale_data = learndash_get_jed_locale_data( LEARNDASH_LMS_TEXT_DOMAIN );
		wp_add_inline_script(
			'wp-i18n',
			'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ', "learndash" );'
		);
	}
}

/**
 * Loads the translations MO file into memory.
 *
 * @since 3.0.0
 *
 * @param string $domain The textdomain.
 *
 * @return array An array of translated strings.
 */
function learndash_get_jed_locale_data( $domain = '' ) {
	if ( empty( $domain ) ) {
		$domain = LEARNDASH_LMS_TEXT_DOMAIN;
	}
	$translations = get_translations_for_domain( $domain );

	$locale = array(
		'' => array(
			'domain' => $domain,
			'lang'   => is_admin() ? get_user_locale() : get_locale(),
		),
	);

	if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
		$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
	}

	foreach ( $translations->entries as $msgid => $entry ) {
		$locale[ $msgid ] = $entry->translations;
	}

	return $locale;
}

$learndash_other_plugins_active_text = '';
global $learndash_other_plugins_active_text;

/**
 * Check for other LMS plugins
 *
 * @since 3.2.3
 */
function learndash_check_other_lms_plugins() {
	global $learndash_other_plugins_active_text;

	$learndash_other_plugins_active_text = '';

	$lms_plugins = array(
		'lifterlms/lifterlms.php'         => array(
			'label'    => 'Lifter LMS',
			'plugin'   => 'lifterlms/lifterlms.php',
			'function' => 'llms',
		),
		'sensei-lms/sensei-lms.php'       => array(
			'label'  => 'Sensei LMS',
			'plugin' => 'sensei-lms/sensei-lms.php',
		),
		'tutor/tutor.php'                 => array(
			'label'  => 'Tutor LMS',
			'plugin' => 'tutor/tutor.php',
		),
		'wp-courses/wp-courses.php'       => array(
			'label'  => 'WP Courses LMS',
			'plugin' => 'wp-courses/wp-courses.php',
		),
		'wp-courseware/wp-courseware.php' => array( // cspell:disable-line.
			'label'    => 'WP Courseware', // cspell:disable-line.
			'function' => 'WPCW_plugin_init',
		),
		'WPLMS'                           => array(
			'label'  => 'WPLMS Theme',
			'define' => 'WPLMS_VERSION',
		),
	);

	foreach ( $lms_plugins as $plugin_set ) {
		$plugin_active = false;

		if ( ( isset( $plugin_set['plugin'] ) ) && ( ! empty( $plugin_set['plugin'] ) ) ) { // @phpstan-ignore-line
			if ( ( is_plugin_active( $plugin_set['plugin'] ) ) || ( ( is_multisite() ) && ( is_plugin_active_for_network( $plugin_set['plugin'] ) ) ) ) {
				$plugin_active = true;
			}
		} elseif ( ( isset( $plugin_set['class'] ) ) && ( ! empty( $plugin_set['class'] ) ) ) { // @phpstan-ignore-line
			if ( class_exists( $plugin_set['class'] ) ) {
				$plugin_active = true;
			}
		} elseif ( ( isset( $plugin_set['function'] ) ) && ( ! empty( $plugin_set['function'] ) ) ) { // @phpstan-ignore-line
			if ( function_exists( $plugin_set['function'] ) ) { // @phpstan-ignore-line
				$plugin_active = true;
			}
		} elseif ( ( isset( $plugin_set['define'] ) ) && ( ! empty( $plugin_set['define'] ) ) ) { // @phpstan-ignore-line
			if ( defined( $plugin_set['define'] ) ) {
				$plugin_active = true;
			}
		}

		if ( ( $plugin_active ) && ( isset( $plugin_set['label'] ) ) && ( ! empty( $plugin_set['label'] ) ) ) { // @phpstan-ignore-line
			if ( ! empty( $learndash_other_plugins_active_text ) ) {
				$learndash_other_plugins_active_text .= ', ';
			}
			$learndash_other_plugins_active_text .= $plugin_set['label'];
		}
	}
}

add_action( 'admin_init', 'learndash_check_other_lms_plugins' );

/**
 * Admin notice other LMS plugins
 *
 * @since 3.2.3
 */
function learndash_admin_notice_other_lms_plugins() {
	global $learndash_other_plugins_active_text;

	$current_screen = get_current_screen();

	if ( ! empty( $learndash_other_plugins_active_text ) ) {
		$notice_time = get_user_meta( get_current_user_id(), 'learndash_other_plugins_notice_dismissed_nonce', true );
		$notice_time = absint( $notice_time );
		if ( ! empty( $notice_time ) ) {
			return;
		}

		?>
		<div class="notice notice-error notice-alt is-dismissible ld-plugin-other-plugins-notice" data-notice-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'notice-dismiss-nonce-' . get_current_user_id() ) ); ?>">
		<?php
			echo wp_kses_post(
				wpautop(
					sprintf(
						// translators: placeholder: list of active LMS plugins.
						_x( '<strong>LearnDash LMS</strong> has detected other active LMS plugins which may cause conflicts: <strong>%s</strong>', 'placeholder: list of active LMS plugins', 'learndash' ),
						$learndash_other_plugins_active_text
					)
				)
			);
		?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'learndash_admin_notice_other_lms_plugins' );


/**
 * AJAX function to handle other plugins notice dismiss action from browser.
 *
 * @since 3.2.3
 */
function learndash_admin_other_plugins_notice_dismissed_ajax() {
	$user_id = get_current_user_id();
	if ( ! empty( $user_id ) ) {
		if ( ( isset( $_POST['action'] ) ) && ( 'learndash_other_plugins_notice_dismissed' === $_POST['action'] ) ) {
			if ( ( isset( $_POST['learndash_other_plugins_notice_dismissed_nonce'] ) ) && ( ! empty( $_POST['learndash_other_plugins_notice_dismissed_nonce'] ) ) && ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash_other_plugins_notice_dismissed_nonce'] ) ), 'notice-dismiss-nonce-' . $user_id ) ) ) {
				update_user_meta( $user_id, 'learndash_other_plugins_notice_dismissed_nonce', time() );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_learndash_other_plugins_notice_dismissed', 'learndash_admin_other_plugins_notice_dismissed_ajax' );
