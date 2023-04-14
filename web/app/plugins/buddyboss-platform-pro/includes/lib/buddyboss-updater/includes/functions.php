<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! function_exists( 'bblicenses_switch__show_admin_notices' ) ) {
	function bblicenses_switch__show_admin_notices() {
		if ( ! get_transient( 'bblicenses_switch__show_admin_notices' ) ) {
			$api_host           = 'https://buddyboss.com/';
			$show_admin_notices = 'no';

			// do an api request
			$request_params = array(
				'bboss_license_api' => '1',
				'request'           => 'check-switch',
				'switch'            => 'show_admin_notices',
			);

			$request_url = add_query_arg( $request_params, $api_host );

			$q_response = wp_remote_get( $request_url, array( 'timeout' => 50 ) );

			if ( ! is_wp_error( $q_response ) && $q_response['response']['code'] == 200 ) {
				$response = (array) json_decode( $q_response['body'] );
				if ( $response['status'] && $response['val'] == 'yes' ) {
					$show_admin_notices = 'yes';
				}
			}

			set_transient( 'bblicenses_switch__show_admin_notices', $show_admin_notices, 2 * HOUR_IN_SECONDS );
		}

		return get_transient( 'bblicenses_switch__show_admin_notices' );
	}
}

if ( ! function_exists( 'bblicenses_switch__updates_without_license' ) ) {
	function bblicenses_switch__updates_without_license() {
		if ( ! get_transient( 'bblicenses_switch__updates_without_license' ) ) {
			$api_host                = 'https://buddyboss.com/';
			$updates_without_license = 'yes';

			// do an api request
			$request_params = array(
				'bboss_license_api' => '1',
				'request'           => 'check-switch',
				'switch'            => 'updates_check_license',
			);

			$request_url = add_query_arg( $request_params, $api_host );

			$q_response = wp_remote_get( $request_url, array( 'timeout' => 50 ) );

			if ( ! is_wp_error( $q_response ) && $q_response['response']['code'] == 200 ) {
				$response = (array) json_decode( $q_response['body'] );
				if ( $response['status'] && $response['val'] == 'yes' ) {
					// we need to check license before providing update
					$updates_without_license = 'no';
				}
			}

			set_transient( 'bblicenses_switch__updates_without_license', $updates_without_license, 2 * HOUR_IN_SECONDS );
		}

		return get_transient( 'bblicenses_switch__updates_without_license' );
	}
}


function bbupdater_discover_licensed_packages( $packages = array() ) {

	$all_themes = wp_get_themes();
	if ( empty( $all_themes ) ) {
		return $packages;
	}

	// themes - boss, onesocial, buddyboss theme, buddyboss mobile only, social portfolio,
	foreach ( $all_themes as $theme_directory => $theme_details ) {
		if ( 'boss' == $theme_directory ) {
			$packages['boss'] = array(
				'id'       => 'boss',
				'path'     => $theme_directory,
				'package'  => 'theme',
				'name'     => __( 'Boss Theme', 'buddyboss-pro' ),
				'products' => array(
					'BOSS' => array(
						'software_ids' => array( 'BOSS_1S', 'BOSS_5S', 'BOSS_20S' ),
						'name'         => __( 'Boss Theme', 'buddyboss-pro' ),
					),
				),
			);

			continue;
		}

		if ( 'onesocial' == $theme_directory ) {
			$packages['onesocial'] = array(
				'id'       => 'onesocial',
				'path'     => $theme_directory,
				'package'  => 'theme',
				'name'     => __( 'OneSocial Theme', 'buddyboss-pro' ),
				'products' => array(
					'ONESOCIAL' => array(
						'software_ids' => array( 'ONESOCIAL_1S', 'ONESOCIAL_5S', 'ONESOCIAL_20S' ),
						'name'         => __( 'OneSocial Theme', 'buddyboss-pro' ),
					),
				),
			);
			continue;
		}
	}

	/**
	 * Here we are adding the BB theme Licences directly
	 */
	$packages['buddyboss_theme'] = array(
		'id'       => 'buddyboss_theme',
		'path'     => 'buddyboss-theme',
		'package'  => 'theme',
		'name'     => __( 'BB Theme & Platform Pro', 'buddyboss-pro' ),
		'products' => array(
			// key should be unique for every individual buddyboss product
			// and if product X is included in 2 different packages, key for product X must be same in both packages.
			'BB_THEME'        => array(
				'software_ids' => array( 'BB_THEME_1S', 'BB_THEME_2S', 'BB_THEME_5S', 'BB_THEME_10S' ),
				'name'         => __( 'BuddyBoss Theme', 'buddyboss-pro' ),
			),
			'BB_PLATFORM_PRO' => array(
				'software_ids' => array( 'BB_PLATFORM_PRO_1S', 'BB_PLATFORM_PRO_2S', 'BB_PLATFORM_PRO_5S', 'BB_PLATFORM_PRO_10S' ),
				'name'         => __( 'BuddyBoss Platform Pro', 'buddyboss-pro' ),
			),
		),
	);

	/**
	 * look for all installed buddyboss plugins( active or inactive ) & themes and register those
	 */

	$all_plugins = get_plugins();
	if ( empty( $all_plugins ) ) {
		return $packages;
	}

	// plugins - media, wall, inbox, reply by email, location autocomplete,
	// Boss for Sensei, Boss for Learndash, marketplace,
	// bp user blog, BP Reorder Tabs, BP Portfolio Pro, bp member types
	foreach ( $all_plugins as $plugin_file => $plugin_details ) {

		if ( 'buddyboss-platform-pro/buddyboss-platform-pro.php' == $plugin_file ) {

			/**
			 * Get the BB theme licenses status
			 */
			$bb_theme_active_status = BuddyBoss_Updater_Admin::instance()->get_package_status_from_licenses( 'buddyboss_theme' );

			/**
			 * Check if the BB theme licenses is active
			 */
			if ( 'active' !== $bb_theme_active_status ) {
				$packages['bb_platform_pro'] = array(
					'id'       => 'bb_platform_pro',
					'path'     => $plugin_file,
					'package'  => 'plugin',
					'name'     => __( 'BB Platform Pro', 'buddyboss-pro' ),
					'products' => array(
						'BB_PLATFORM_PRO' => array(
							'software_ids' => array( 'BB_PLATFORM_PRO_1S', 'BB_PLATFORM_PRO_2S', 'BB_PLATFORM_PRO_5S', 'BB_PLATFORM_PRO_10S' ),
							'name'         => __( 'BuddyBoss Platform Pro', 'buddyboss-pro' ),
						),
					),
				);
			}
			continue;
		}

		if ( 'buddyboss-media/buddyboss-media.php' == $plugin_file ) {
			$packages['bbmedia'] = array(
				'id'       => 'bbmedia',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'BuddyBoss Media', 'buddyboss-pro' ),
				'products' => array(
					'BBMEDIA' => array(
						'software_ids' => array( 'BBMEDIA_1S', 'BBMEDIA_5S', 'BBMEDIA_20S' ),
						'name'         => __( 'BuddyBoss Media', 'buddyboss-pro' ),
					),
				),
			);

			continue;
		}

		if ( 'buddyboss-wall/buddyboss-wall.php' == $plugin_file ) {
			$packages['bbwall'] = array(
				'id'       => 'bbwall',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'BuddyBoss Wall', 'buddyboss-pro' ),
				'products' => array(
					'BBWALL' => array(
						'software_ids' => array( 'BBWALL_1S', 'BBWALL_5S', 'BBWALL_20S' ),
						'name'         => __( 'BuddyBoss Wall', 'buddyboss-pro' ),
					),
				),
			);
		}

		if ( 'buddypress-location-autocomplete/bp-location-autocomplete.php' == $plugin_file ) {
			$packages['bblacbp'] = array(
				'id'       => 'bblacbp',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'Location Autocomplete', 'buddyboss-pro' ),
				'products' => array(
					'BBLACBP' => array(
						'software_ids' => array( 'LACBP_1S', 'LACBP_5S', 'LACBP_20S' ),
						'name'         => __( 'Location Autocomplete', 'buddyboss-pro' ),
					),
				),
			);

			continue;
		}

		if ( 'boss-learndash/boss-learndash.php' == $plugin_file ) {
			$packages['slearner_ld'] = array(
				'id'       => 'slearner_ld',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'Social Learner - Learndash', 'buddyboss-pro' ),
				'products' => array(
					'SLEARNER_BFLD' => array(
						'software_ids' => array(
							'SLEARNER_LD_UPGRADE',
							'SLEARNER_LD_1S',
							'SLEARNER_LD_5S',
							'SLEARNER_LD_20S'
						),
						'name'         => __( 'Boss for Learndash', 'buddyboss-pro' ),
					),
					'BOSS'          => array(
						'software_ids' => array( 'BOSS_1S', 'BOSS_5S', 'BOSS_20S' ),
						'name'         => __( 'Boss', 'buddyboss-pro' ),
					),
				),
			);

			continue;
		}

		if ( 'buddyboss-marketplace/buddyboss-marketplace.php' == $plugin_file ) {
			$packages['socmp'] = array(
				'id'       => 'socmp',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'Social MarketPlace', 'buddyboss-pro' ),
				'products' => array(
					'SOCMP'     => array(
						'software_ids' => array( 'SOCMP_UPGRADE', 'SOCMP_1S', 'SOCMP_5S', 'SOCMP_20S' ),
						'name'         => __( 'Social MarketPlace', 'buddyboss-pro' ),
					),
					'ONESOCIAL' => array(
						'software_ids' => array( 'ONESOCIAL_1S', 'ONESOCIAL_5S', 'ONESOCIAL_20S' ),
						'name'         => __( 'OneSocial Theme', 'buddyboss-pro' ),
					)
				),
			);

			continue;
		}

		if ( 'buddypress-member-type/buddyboss-bmt.php' == $plugin_file ) {
			$packages['bpmemtypes'] = array(
				'id'       => 'bpmemtypes',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'BuddyPress Member Types', 'buddyboss-pro' ),
				'products' => array(
					'BPMEMTYPES' => array(
						'software_ids' => array( 'BPMEMTYPES_1S', 'BPMEMTYPES_5S', 'BPMEMTYPES_20S' ),
						'name'         => __( 'BuddyPress Member Types', 'buddyboss-pro' ),
					),
				),
			);
			continue;
		}
	}

	return $packages;
}
add_filter( 'bboss_licensed_packages', 'bbupdater_discover_licensed_packages', 11 );

function bbupdater_register_updatable_products( $products = array() ) {
	// register self
	/**
	 * look for all installed buddyboss plugins( active or inactive ) & themes and register those
	 */

	$all_plugins = get_plugins();
	if ( empty( $all_plugins ) ) {
		return $products;
	}

	// plugins - media, wall, inbox, reply by email, location autocomplete,
	// Boss for Sensei, Boss for Learndash, marketplace,
	// bp user blog, BP Reorder Tabs, BP Portfolio Pro, bp member types

	foreach ( $all_plugins as $plugin_file => $plugin_details ) {
		if ( 'buddyboss-media/buddyboss-media.php' == $plugin_file ) {
			$products['BBMEDIA'] = array(
				'path'         => $plugin_file,
				'id'           => 116,
				'software_ids' => array( 'BBMEDIA_1S', 'BBMEDIA_5S', 'BBMEDIA_20S' ),
				'type'         => 'plugin',
			);

			continue;
		}

		if ( 'buddypress-user-blog/bp-user-blog.php' == $plugin_file ) {
			$packages['bpublog'] = array(
				'id'       => 'bpublog',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'BuddyPress User Blog', 'buddyboss-pro' ),
				'products' => array(
					'BPUBLOG' => array(
						'software_ids' => array( 'BPUBLOG_1S', 'BPUBLOG_5S', 'BPUBLOG_20S' ),
						'name'         => __( 'BuddyPress User Blog', 'buddyboss-pro' ),
					),
				),
			);

			/**
			 * There is no separate product for 'Social Blogger'.
			 * So we'll add its license code into buddypress-user-blog plugin.
			 */
			$packages['socblogger'] = array(
				'id'       => 'socblogger',
				'path'     => $plugin_file,
				'package'  => 'plugin',
				'name'     => __( 'Social Blogger', 'buddyboss-pro' ),
				'products' => array(
					'BPUBLOG'   => array(
						'software_ids' => array( 'SOCBLOGGER_1S', 'SOCBLOGGER_5S', 'SOCBLOGGER_20S' ),
						'name'         => __( 'BuddyPress User Blog', 'buddyboss-pro' ),
					),
					'ONESOCIAL' => array(
						'software_ids' => array( 'ONESOCIAL_1S', 'ONESOCIAL_5S', 'ONESOCIAL_20S' ),
						'name'         => __( 'OneSocial Theme', 'buddyboss-pro' )
					),
				),
			);

			continue;
		}

		if ( 'bp-portfolio-pro/bp-portfolio-pro.php' === $plugin_file ) {

			$products['PORTFOLIOPRO'] = array(
				'path'         => $plugin_file,
				'id'           => 157,
				'software_ids' => array( 'PORTFOLIOPRO_1S', 'PORTFOLIOPRO_5S', 'PORTFOLIOPRO_20S' ),
				'type'         => 'plugin',
			);

			continue;
		}

		if ( 'buddyboss-wall/buddyboss-wall.php' == $plugin_file ) {
			$products['BBWALL'] = array(
				'path'         => $plugin_file,
				'id'           => 37,
				'software_ids' => array( 'BBWALL_1S', 'BBWALL_5S', 'BBWALL_20S' ),
				'type'         => 'plugin',
			);
			continue;
		}

		if ( 'buddypress-location-autocomplete/bp-location-autocomplete.php' == $plugin_file ) {
			$products['BBLACBP'] = array(
				'path'         => $plugin_file,
				'id'           => 466,
				'software_ids' => array( 'LACBP_1S', 'LACBP_5S', 'LACBP_20S' ),
				'type'         => 'plugin',
			);
			continue;
		}

		if ( 'boss-learndash/boss-learndash.php' == $plugin_file ) {
			$products['SLEARNER_BFLD'] = array(
				'path'         => $plugin_file,
				'id'           => 40,
				'software_ids' => array( 'SLEARNER_LD_UPGRADE', 'SLEARNER_LD_1S', 'SLEARNER_LD_5S', 'SLEARNER_LD_20S' ),
				'type'         => 'plugin',
			);

			continue;
		}

		if ( 'buddyboss-marketplace/buddyboss-marketplace.php' == $plugin_file ) {
			$products['SOCMP'] = array(
				'path'         => $plugin_file,
				'id'           => 195,
				'software_ids' => array( 'SOCMP_UPGRADE', 'SOCMP_1S', 'SOCMP_5S', 'SOCMP_20S' ),
				'type'         => 'plugin',
			);

			continue;
		}

		if ( 'bp-reorder-tabs/bp-reorder-tabs.php' == $plugin_file ) {
			$products['BP_REORDER_TABS'] = array(
				'path'         => $plugin_file,
				'id'           => 2,
				'software_ids' => array(),
			);

			continue;
		}

		if ( 'buddypress-member-type/buddyboss-bmt.php' == $plugin_file ) {
			// @todo: add code here
			$products['BPMEMTYPES'] = array(
				'path'         => $plugin_file,
				'id'           => 107,
				'software_ids' => array( 'BPMEMTYPES_1S', 'BPMEMTYPES_5S', 'BPMEMTYPES_20S' ),
				'type'         => 'plugin',
			);
			continue;
		}

		if ( 'buddyboss-platform-pro/buddyboss-platform-pro.php' == $plugin_file ) {
			// @todo: add code here
			$products['BB_PLATFORM_PRO'] = array(
				'path'          => $plugin_file,
				'id'            => 1341,
				'software_ids'  => array( 'BB_PLATFORM_PRO_1S', 'BB_PLATFORM_PRO_2S', 'BB_PLATFORM_PRO_5S', 'BB_PLATFORM_PRO_10S' ),
				'type'          => 'plugin',
				'releases_link' => 'https://www.buddyboss.com/resources/buddyboss-platform-pro-releases/',
			);
			continue;
		}
	}

	$all_themes = wp_get_themes();
	if ( empty( $all_themes ) ) {
		return $products;
	}

	/**
	 * themes - boss, onesocial
	 */
	foreach ( $all_themes as $theme_directory => $theme_details ) {
		if ( 'boss' == $theme_directory ) {
			$products['BOSS'] = array(
				'path'          => $theme_directory,
				'id'            => 44,
				'software_ids'  => array( 'BOSS_1S', 'BOSS_5S', 'BOSS_20S' ),
				'type'          => 'theme',
				'releases_link' => 'https://www.buddyboss.com/release-notes/boss-theme-',
			);

			continue;
		}

		if ( 'onesocial' == $theme_directory ) {
			$products['ONESOCIAL'] = array(
				'path'          => $theme_directory,
				'id'            => 170,
				'software_ids'  => array( 'ONESOCIAL_1S', 'ONESOCIAL_5S', 'ONESOCIAL_20S' ),
				'type'          => 'theme',
				'releases_link' => 'https://www.buddyboss.com/release-notes/onesocial-',
			);

			continue;
		}

		if ( 'buddyboss-theme' == $theme_directory ) {
			$products['BB_THEME'] = array(
				'path'          => $theme_directory,
				'id'            => 867,
				'software_ids'  => array( 'BB_THEME_1S', 'BB_THEME_2S', 'BB_THEME_5S', 'BB_THEME_10S' ),
				'type'          => 'theme',
				'releases_link' => 'https://www.buddyboss.com/resources/buddyboss-theme-releases/',
			);
			continue;
		}
	}

	return $products;
}
add_filter( 'bboss_updatable_products', 'bbupdater_register_updatable_products', 11 );

/**
 * Get hidden license key
 *
 * @since 1.4.1
 * @param $license
 *
 * @return string|string[]
 */
function bblicenses_get_hidden_license_key( $license ) {
	$start  = 5;
	$length = mb_strlen( $license ) - $start - 5;

	$mask_string = preg_replace( '/\S/', '*', $license );
	$mask_string = mb_substr( $mask_string, $start, $length );
	$license     = substr_replace( $license, $mask_string, $start, $length );

	return $license;
}
