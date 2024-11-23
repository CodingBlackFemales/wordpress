<?php
/**
 * Theme Update Hooks.
 *
 * @package BuddyBoss_Theme
 */

// Clear transient after theme update.
if ( ! function_exists( 'buddyboss_theme_update' ) ) {

	/**
	 * Function is called when theme is updated.
	 *
	 * @since 1.7.3
	 */
	function buddyboss_theme_update() {
		$current_version = wp_get_theme( get_template() )->get( 'Version' );
		$old_version     = get_option( 'buddyboss_theme_version', '1.7.2' );

		if ( $old_version !== $current_version ) {

			// Call clear learndash group users transient.
			if ( version_compare( $current_version, '1.7.2', '>' ) && function_exists( 'bb_theme_update_1_7_3' ) ) {
				bb_theme_update_1_7_3();
			}

			// Call to back up default cover images.
			if ( version_compare( $current_version, '1.8.2', '>' ) && function_exists( 'bb_theme_update_1_8_3' ) ) {
				bb_theme_update_1_8_3();
			}

			// Call to back up default cover images.
			if ( version_compare( $current_version, '1.8.6', '>' ) && function_exists( 'bb_theme_update_1_8_7' ) ) {
				bb_theme_update_1_8_7();
			}

			// Call to back up default cover images.
			if ( version_compare( $current_version, '2.2.5', '>' ) && function_exists( 'bb_theme_update_2_2_6' ) ) {
				bb_theme_update_2_2_6();
			}

			// Set default logo destination url.
			if ( version_compare( $current_version, '2.3.40', '>' ) && function_exists( 'bb_theme_update_2_3_60' ) ) {
				bb_theme_update_2_3_60();
			}

			// update not to run twice.
			update_option( 'buddyboss_theme_version', $current_version );
		}

		bb_theme_setup_updater();

	}

	if ( is_admin() ) {
		add_action( 'after_setup_theme', 'buddyboss_theme_update' );
	}
}

/**
 * Clear the learndash course enrolled user count transient.
 *
 * @since 1.7.3
 */
function bb_theme_update_1_7_3() {
	global $wpdb;
	$sql       = 'select option_name from ' . $wpdb->options . ' where option_name like "%_transient_buddyboss_theme_ld_course_enrolled_users_count_%"';
	$all_cache = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! empty( $all_cache ) ) {
		foreach ( $all_cache as $cache_name ) {
			$cache_name = str_replace( '_site_transient_', '', $cache_name );
			$cache_name = str_replace( '_transient_', '', $cache_name );
			delete_transient( $cache_name );
			delete_site_transient( $cache_name );
		}
	}
}

/**
 * Backup default cover images.
 *
 * @since 1.8.4
 */
function bb_theme_update_1_8_3() {
	global $buddyboss_theme_options;

	$theme_default_member_cover = '';
	$theme_default_group_cover  = '';

	/* Check if options are set */
	if ( ! isset( $buddyboss_theme_options ) ) {
		$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
	}

	if ( isset( $buddyboss_theme_options['buddyboss_profile_cover_default'] ) ) {
		$theme_default_member_cover = $buddyboss_theme_options['buddyboss_profile_cover_default'];
	}

	if ( isset( $buddyboss_theme_options['buddyboss_group_cover_default'] ) ) {
		$theme_default_group_cover = $buddyboss_theme_options['buddyboss_group_cover_default'];
	}

	update_option( 'buddyboss_profile_cover_default_migration', $theme_default_member_cover );
	update_option( 'buddyboss_group_cover_default_migration', $theme_default_group_cover );

	// Delete custom css transient.
	delete_transient( 'buddyboss_theme_compressed_elementor_custom_css' );
}

/**
 * Backup cover width and height for profile and group.
 *
 * @since 1.8.7
 */
function bb_theme_update_1_8_7() {
	global $buddyboss_theme_options;

	$is_platform_upto_date = function_exists( 'buddypress' ) && defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION, '1.9.1', '>=' );

	/* Check if options are empty */
	if ( ! isset( $buddyboss_theme_options ) ) {
		$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
	}

	if ( ! empty( $buddyboss_theme_options ) ) {
		update_option( 'old_buddyboss_theme_options_1_8_7', $buddyboss_theme_options );
	}

	if ( isset( $buddyboss_theme_options['buddyboss_profile_cover_width'] ) ) {
		$profile_cover_width = buddyboss_theme_get_option( 'buddyboss_profile_cover_width' );

		// If platform is not updated then option will migrate.
		if ( ! $is_platform_upto_date ) {
			delete_option( 'bb-pro-cover-profile-width' );
			add_option( 'bb-pro-cover-profile-width', $profile_cover_width );
		}
		unset( $buddyboss_theme_options['buddyboss_profile_cover_width'] );
	}

	if ( isset( $buddyboss_theme_options['buddyboss_profile_cover_height'] ) ) {
		$profile_cover_height = buddyboss_theme_get_option( 'buddyboss_profile_cover_height' );

		// If platform is not updated then option will migrate.
		if ( ! $is_platform_upto_date ) {
			delete_option( 'bb-pro-cover-profile-height' );
			add_option( 'bb-pro-cover-profile-height', $profile_cover_height );
		}
		unset( $buddyboss_theme_options['buddyboss_profile_cover_height'] );
	}

	if ( isset( $buddyboss_theme_options['buddyboss_group_cover_width'] ) ) {
		$group_cover_width = buddyboss_theme_get_option( 'buddyboss_group_cover_width' );

		// If platform is not updated then option will migrate.
		if ( ! $is_platform_upto_date ) {
			delete_option( 'bb-pro-cover-group-width' );
			add_option( 'bb-pro-cover-group-width', $group_cover_width );
		}
		unset( $buddyboss_theme_options['buddyboss_group_cover_width'] );
	}

	if ( isset( $buddyboss_theme_options['buddyboss_group_cover_height'] ) ) {
		$group_cover_height = buddyboss_theme_get_option( 'buddyboss_group_cover_height' );

		// If platform is not updated then option will migrate.
		if ( ! $is_platform_upto_date ) {
			delete_option( 'bb-pro-cover-group-height' );
			add_option( 'bb-pro-cover-group-height', $group_cover_height );
		}
		unset( $buddyboss_theme_options['buddyboss_group_cover_height'] );
	}

	if ( ! empty( $buddyboss_theme_options ) ) {
		update_option( 'buddyboss_theme_options', $buddyboss_theme_options );
	}
}

/**
 * Set up the BuddyBoss theme updater.
 *
 * @return void
 *
 * @since 1.8.7
 */
function bb_theme_setup_updater() {
	// Are we running an outdated version of BuddyBoss Theme?
	if ( wp_doing_ajax() || ! bb_theme_is_update() ) {
		return;
	}

	bb_theme_version_updater();
}

/**
 * Is this a BuddyBoss theme update?
 *
 * @return bool True if update, otherwise false.
 * @since 1.8.7
 */
function bb_theme_is_update() {

	// Current DB version of this site (per site in a multisite network).
	$current_db   = (int) get_option( '_bb_theme_db_version' );
	$current_live = (int) bb_theme_get_db_version();

	// Theme version history.
	bb_theme_version_bump();
	$bb_theme_version_history = (array) get_option( 'bb_theme_version_history', array() );
	$initial_version_data     = ! empty( $bb_theme_version_history ) ? end( $bb_theme_version_history ) : array();
	$bb_version_exists        = ! empty( $initial_version_data ) && ! empty( $initial_version_data['version'] ) && (string) buddyboss_theme()->version() === (string) $initial_version_data['version'];
	if ( ! $bb_version_exists || $current_live !== $current_db ) {
		$current_date               = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$bb_latest_theme_version    = array(
			'db_version' => $current_live,
			'date'       => $current_date->format( 'Y-m-d H:i:s' ),
			'version'    => buddyboss_theme()->version(),
		);
		$bb_theme_version_history[] = $bb_latest_theme_version;
		update_option( 'bb_theme_version_history', array_filter( $bb_theme_version_history ) );
	}

	$is_update = false;
	if ( $current_live !== $current_db ) {
		$is_update = true;
	}

	// Return the product of version comparison.
	return $is_update;
}

/**
 * Initialize an update or installation of BuddyBoss Theme.
 *
 * BuddyBoss Theme's version updater looks at what the current database version is,
 * and runs whatever other code is needed - either the "update" or "install"
 * code.
 *
 * @since 1.8.7
 */
function bb_theme_version_updater() {

	// Get current DB version.
	$current_db = (int) get_option( '_bb_theme_db_version' );
	// Get the raw database version.
	$raw_db_version = (int) bb_theme_get_db_version_raw();

	/* All done! *************************************************************/

	// Add the conditional logic for each version migration code.
	if ( $raw_db_version < 400 ) {
		bb_theme_update_2_0_0();
	}

	if ( $raw_db_version < 430 ) {
		// Function to migrate all menu icon type.
		bb_theme_update_nav_menu_icon_type_2_0_5();

		// Function to migrate image icon type to enabled by defaults.
		bb_theme_update_support_custom_icon_2_0_5();
	}

	if ( $raw_db_version < 435 ) {
		bb_theme_update_2_2_1_2();
	}

	if ( $raw_db_version !== $current_db ) {
		bb_theme_migrate_google_plus();
	}
}

/**
 * Update the BuddyBoss Theme version stored in the database to the current version.
 *
 * @since 1.8.7
 */
function bb_theme_version_bump() {
	update_option( '_bb_theme_db_version', bb_theme_get_db_version() );
}

/**
 * Output the BuddyBoss Theme database version.
 *
 * @since 1.8.7
 */
function bb_theme_db_version() {
	echo bb_theme_get_db_version();
}
/**
 * Return the BuddyBoss Theme database version.
 *
 * @since 1.8.7
 *
 * @return string The BuddyBoss Theme database version.
 */
function bb_theme_get_db_version() {
	return buddyboss_theme()->bb_theme_db_version;
}

/**
 * Output the BuddyBoss Theme database version.
 *
 * @since 1.8.7
 */
function bb_theme_db_version_raw() {
	echo bb_theme_get_db_version_raw();
}

/**
 * Return the BuddyBoss Theme database version.
 *
 * @since 1.8.7
 *
 * @return string The BuddyBoss Theme version direct from the database.
 */
function bb_theme_get_db_version_raw() {
	return ! empty( buddyboss_theme()->bb_theme_db_version_raw ) ? buddyboss_theme()->bb_theme_db_version_raw : 0;
}

/**
 * Migrate options for 2.0.0
 *
 * @since 2.0.0
 */
function bb_theme_migrate_components_options() {
	global $buddyboss_theme_options;

	/* Check if options are set */
	if ( ! isset( $buddyboss_theme_options ) ) {
		$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
	}

	// Set default styling option to theme 1.0 when updating the theme.
	// Set default logo on for header 3 style.
	if (
		! empty( $buddyboss_theme_options ) &&
		! isset( $buddyboss_theme_options['theme_template'] )
	) {
		$buddyboss_theme_options['theme_template'] = '1';
	} else {
		$buddyboss_theme_options['theme_template'] = '2';
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['mobile_header_search'] ) ) {
		$buddyboss_theme_options['mobile_component_opt_multi_checkbox']['mobile_header_search'] = buddyboss_theme_get_option( 'mobile_header_search' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['mobile_messages'] ) ) {
		$buddyboss_theme_options['mobile_component_opt_multi_checkbox']['mobile_messages'] = buddyboss_theme_get_option( 'mobile_messages' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['mobile_shopping_cart'] ) ) {
		$buddyboss_theme_options['mobile_component_opt_multi_checkbox']['mobile_shopping_cart'] = buddyboss_theme_get_option( 'mobile_shopping_cart' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['mobile_notifications'] ) ) {
		$buddyboss_theme_options['mobile_component_opt_multi_checkbox']['mobile_notifications'] = buddyboss_theme_get_option( 'mobile_notifications' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['header_search'] ) ) {
		$buddyboss_theme_options['desktop_component_opt_multi_checkbox']['desktop_header_search'] = buddyboss_theme_get_option( 'header_search' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['messages'] ) ) {
		$buddyboss_theme_options['desktop_component_opt_multi_checkbox']['desktop_messages'] = buddyboss_theme_get_option( 'messages' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['shopping_cart'] ) ) {
		$buddyboss_theme_options['desktop_component_opt_multi_checkbox']['desktop_shopping_cart'] = buddyboss_theme_get_option( 'shopping_cart' );
	}

	if ( isset( $buddyboss_theme_options ) && isset( $buddyboss_theme_options['notifications'] ) ) {
		$buddyboss_theme_options['desktop_component_opt_multi_checkbox']['desktop_notifications'] = buddyboss_theme_get_option( 'notifications' );
	}

	if (
		isset( $buddyboss_theme_options['buddyboss_header'] ) &&
		'3' === $buddyboss_theme_options['buddyboss_header']
	) {
		$buddyboss_theme_options['buddypanel_show_logo'] = '1';
	}

	// Set default 404 featured image to custom when updating the theme and image uploaded.
	$img_404 = buddyboss_theme_get_option( '404_image' );
	if ( is_array( $img_404 ) && $img_404['url'] ) {
		$buddyboss_theme_options['404_featured_image'] = 'custom';
	}

	// Migrate all the older maintenance social network to latest version.
	$social_network_twitter   = buddyboss_theme_get_option( 'social_network_twitter' );
	$social_network_facebook  = buddyboss_theme_get_option( 'social_network_facebook' );
	$social_network_google    = buddyboss_theme_get_option( 'social_network_google' );
	$social_network_instagram = buddyboss_theme_get_option( 'social_network_instagram' );
	$social_network_youtube   = buddyboss_theme_get_option( 'social_network_youtube' );
	$buddyboss_theme_options['maintenance_social_links']['twitter']   = ! empty( $social_network_twitter ) ? $social_network_twitter : '';
	$buddyboss_theme_options['maintenance_social_links']['facebook']  = ! empty( $social_network_facebook ) ? $social_network_facebook : '';
	$buddyboss_theme_options['maintenance_social_links']['google']    = ! empty( $social_network_google ) ? $social_network_google : '';
	$buddyboss_theme_options['maintenance_social_links']['instagram'] = ! empty( $social_network_instagram ) ? $social_network_instagram : '';
	$buddyboss_theme_options['maintenance_social_links']['youtube']   = ! empty( $social_network_youtube ) ? $social_network_youtube : '';

	update_option( 'buddyboss_theme_options', $buddyboss_theme_options );

	// Backward compatibility of icon picker.
	icon_picker_backward_compatibility();
}

/**
 * Backward compatibility of icon picker.
 *
 * @since 2.0.0
 */
function icon_picker_backward_compatibility() {
	// fix option table data.
	$menu_icons = get_option( 'menu-icons' );
	if ( isset( $menu_icons['global']['icon_types'] ) && ! empty( $menu_icons['global']['icon_types'] ) ) {
		if ( ! in_array( 'buddyboss', $menu_icons['global']['icon_types'], true ) ) {
			$menu_icons['global']['icon_types'][] = 'buddyboss';
		}
		if ( ! in_array( 'buddyboss_legacy', $menu_icons['global']['icon_types'], true ) ) {
			$menu_icons['global']['icon_types'][] = 'buddyboss_legacy';
		}
	} else {
		$menu_icons = array(
			'global' => array(
				'icon_types' => array(
					'buddyboss',
					'buddyboss_legacy',
				),
			),
		);
	}
	// update option.
	update_option( 'menu-icons', $menu_icons );

	// fix postmeta table data.
	$args = array(
		'post_type'   => 'nav_menu_item',
		'post_status' => 'publish',
	);

	$r              = wp_parse_args( null, $args );
	$get_posts      = new \WP_Query();
	$nav_menu_items = $get_posts->query( $r );

	if ( isset( $nav_menu_items ) && ! empty( $nav_menu_items ) ) {
		$nav_menu_items = wp_list_pluck( $nav_menu_items, 'ID' );
		foreach ( $nav_menu_items as $single ) {
			$menu_icons = get_post_meta( $single, 'menu-icons', true );
			if ( isset( $menu_icons['type'] ) && 'buddyboss' === $menu_icons['type'] ) {
				$menu_icons['type'] = 'buddyboss_legacy';
				update_post_meta( $single, 'menu-icons', $menu_icons );
			}
		}
	}
}

/**
 * Backward compatibility of header menu.
 *
 * @since 2.0.0
 */
function bb_theme_migrate_header_menu() {
	// Set the Header Menu - Logged in users to Header Menu - Logged out.
	$locations = get_theme_mod( 'nav_menu_locations' );
	if ( isset( $locations ) && isset( $locations['header-menu'] ) ) {
		$locations['header-menu-logout'] = $locations['header-menu'];
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}

/**
 * Function to migrate all the theme data from old theme to new theme.
 *
 * @since 2.0.0
 */
function bb_theme_update_2_0_0() {

	// Migrate the header menu data.
	bb_theme_migrate_header_menu();

	// Migrate the component options and theme option.
	bb_theme_migrate_components_options();

	// Clear all the cache.
	if ( function_exists( 'buddyboss_theme_compressed_transient_delete' ) ) {
		buddyboss_theme_compressed_transient_delete();
	}

}

/**
 * Function to delete transient data from database.
 *
 * @since 2.2.1.2
 */
function bb_theme_update_2_2_1_2() {
	delete_transient( 'update_themes' );
	delete_transient( 'update_plugins' );
	delete_transient( 'bb_updates_bp-loader' );
	delete_transient( 'bb_updates_buddyboss-theme' );
	delete_transient( 'bb_updates_buddyboss-platform-pro' );
	// For Multi site.
	delete_site_transient( 'update_themes' );
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'bb_updates_bp-loader' );
	delete_site_transient( 'bb_updates_buddyboss-theme' );
	delete_site_transient( 'bb_updates_buddyboss-platform-pro' );
}

/**
 * Function to migrate all menu icon type.
 *
 * @since 2.0.5
 */
function bb_theme_update_nav_menu_icon_type_2_0_5() {
	$mapping_array = array(
		'buddyboss' => array(),
		'legacy'    => array(),
	);
	$args          = array(
		'post_type'   => 'nav_menu_item',
		'post_status' => 'publish',
	);

	if ( ! class_exists( 'Icon_Picker_Type_BuddyBoss_Legacy' ) ) {
		require_once buddyboss_theme()->inc_dir() . '/plugins/buddyboss-menu-icons/vendor/kucrut/icon-picker/includes/types/buddyboss_legacy.php';
	}

	if ( ! class_exists( 'Icon_Picker_Type_BuddyBoss' ) ) {
		require_once buddyboss_theme()->inc_dir() . '/plugins/buddyboss-menu-icons/vendor/kucrut/icon-picker/includes/types/buddyboss.php';
	}

	// Get BuddyBoss icons.
	$buddyboss_icon_object = new Icon_Picker_Type_BuddyBoss();
	$buddyboss_icon_array  = $buddyboss_icon_object->get_items();

	// Get BuddyBoss Legacy icons.
	$buddyboss_legacy_icon_object = new Icon_Picker_Type_BuddyBoss_Legacy();
	$buddyboss_legacy_icon_array  = $buddyboss_legacy_icon_object->get_items();

	$r              = wp_parse_args( null, $args );
	$get_posts      = new \WP_Query();
	$nav_menu_items = $get_posts->query( $r );

	if ( isset( $nav_menu_items ) && ! empty( $nav_menu_items ) ) {
		$nav_menu_items = wp_list_pluck( $nav_menu_items, 'ID' );
		foreach ( $nav_menu_items as $menu_id ) {
			$menu_icons = get_post_meta( $menu_id, 'menu-icons', true );

			$menu_icon      = '';
			$menu_icon_type = '';

			if ( isset( $menu_icons['icon'] ) && ! empty( $menu_icons['icon'] ) ) {
				$menu_icon = $menu_icons['icon'];
			}

			if ( ! empty( $menu_icon ) ) {

				if ( isset( $mapping_array['buddyboss'][ $menu_icon ] ) ) {
					$menu_icon_type = 'buddyboss';
				} elseif ( isset( $mapping_array['legacy'][ $menu_icon ] ) ) {
					$menu_icon_type = 'buddyboss_legacy';
				}

				if ( empty( $menu_icon_type ) ) {
					$buudyboss_icon_key = array_search( $menu_icon, array_column( $buddyboss_icon_array, 'id' ), true );

					if ( 0 <= $buudyboss_icon_key && isset( $buddyboss_icon_array[ $buudyboss_icon_key ] ) ) {
						$menu_icon_type                           = 'buddyboss';
						$mapping_array['buddyboss'][ $menu_icon ] = $buddyboss_icon_array[ $buudyboss_icon_key ];
					} else {

						$legacy_icon_key = array_search( $menu_icon, array_column( $buddyboss_legacy_icon_array, 'id' ), true );

						if ( 0 <= $legacy_icon_key && isset( $buddyboss_legacy_icon_array[ $legacy_icon_key ] ) ) {
							$menu_icon_type                        = 'buddyboss_legacy';
							$mapping_array['legacy'][ $menu_icon ] = $buddyboss_legacy_icon_array[ $legacy_icon_key ];
						}
					}
				}

				if ( ! empty( $menu_icon_type ) ) {
					$menu_icons['type'] = $menu_icon_type;
					update_post_meta( $menu_id, 'menu-icons', $menu_icons );
				}
			}
		}
	}
}

/**
 * Function to migrate image icon type to enabled by defaults.
 *
 * @since 2.0.5
 */
function bb_theme_update_support_custom_icon_2_0_5() {
	// Add the default icon types.
	$menu_icons = get_option( 'menu-icons' );
	if ( isset( $menu_icons['global']['icon_types'] ) && ! empty( $menu_icons['global']['icon_types'] ) ) {
		if ( ! in_array( 'image', $menu_icons['global']['icon_types'], true ) ) {
			$menu_icons['global']['icon_types'][] = 'image';
		}
		if ( ! in_array( 'manage', $menu_icons['global']['icon_types'], true ) ) {
			$menu_icons['global']['icon_types'][] = 'manage';
		}
	} else {
		$menu_icons = array(
			'global' => array(
				'icon_types' => array(
					'buddyboss',
					'buddyboss_legacy',
					'image',
					'manage',
				),
			),
		);
	}
	// update option.
	update_option( 'menu-icons', $menu_icons );
}

/**
 * Run the DB engine update.
 *
 * @since 2.2.6
 *
 * @return void
 *
 * @throws ReflectionException
 */
function bb_theme_update_2_2_6() {
	if ( function_exists( 'buddyboss_theme' ) ) {
		$base_theme_reflection = new ReflectionClass( get_class( buddyboss_theme() ) );
		$related_posts_helper  = $base_theme_reflection->getProperty( '_related_posts_helper' );
		$related_posts_helper->setAccessible( true );
		$releated_post_class = $related_posts_helper->getValue( buddyboss_theme() );
		if ( is_a( $releated_post_class, 'BuddyBossTheme\RelatedPostsHelper' ) ) {
			$releated_post_class->crp_create_index();
		}
	}
}

/**
 * Save the default logo destination to db.
 *
 * @since 2.3.60
 *
 * @return void
 */
function bb_theme_update_2_3_60() {

	$bb_theme_options = get_option( 'buddyboss_theme_options', array() );

	if ( empty( $bb_theme_options['header_logo_loggedin_link'] ) ) {
		$bb_theme_options['header_logo_loggedin_link'] = 'default';
	}

	if ( empty( $bb_theme_options['header_logo_loggedout_link'] ) ) {
		$bb_theme_options['header_logo_loggedout_link'] = 'default';
	}

	update_option( 'buddyboss_theme_options', $bb_theme_options );
}

/**
 * Remove support of google plus.
 *
 * @since 2.4.20
 *
 * @return void
 */
function bb_theme_migrate_google_plus() {
	$footer_socials = buddyboss_theme_get_option( 'boss_footer_social_links' );
	if ( ! empty( $footer_socials ) && isset( $footer_socials['google-plus'] ) ) {
		$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
		$google_plus             = $footer_socials['google-plus'];
		if ( ! empty( $google_plus ) ) {
			update_option( 'bb_theme_google_plus', $google_plus );
		}
		unset( $footer_socials['google-plus'] );
		$buddyboss_theme_options['boss_footer_social_links'] = $footer_socials;
		update_option( 'buddyboss_theme_options', $buddyboss_theme_options );
	}
}
