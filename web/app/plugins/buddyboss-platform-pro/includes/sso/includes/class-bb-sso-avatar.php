<?php
/**
 * Class BB_SSO_Avatar
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

class BB_SSO_Avatar {

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Avatar The single instance of the BB_SSO_Avatar class.
	 */
	public function __construct() {
		add_action( 'bb_sso_update_avatar', array( $this, 'update_avatar' ), 10, 3 );
	}

	/**
	 * Constructor function to initialize the class.
	 *
	 * This function hooks into various WordPress and BuddyPress actions and filters to manage
	 * avatar uploads and ensure proper handling of avatars based on the SSO provider's data.
	 *
	 * @since 2.6.30
	 */
	public static function get_instance() {
		static $inst = null;
		if ( null === $inst ) {
			$inst = new self();
		}

		return $inst;
	}

	/**
	 * Updates the user's avatar based on the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @param BB_SSO_Provider $provider   The SSO provider object.
	 * @param int             $user_id    The user ID.
	 * @param string          $avatar_url The URL of the avatar image.
	 */
	public function update_avatar( $provider, $user_id, $avatar_url ) {
		if ( 'twitter' !== $provider->get_id() && ! bb_enable_additional_sso_profile_picture() ) {
			return;
		}

		if ( ! empty( $avatar_url ) ) {

			// upload user avatar for BuddyPress - bp_displayed_user_avatar() function.
			if ( class_exists( 'BuddyPress', false ) ) {
				if ( ! empty( $avatar_url ) ) {
					$extension = 'jpg';
					if ( preg_match( '/\.(jpg|jpeg|gif|png)/', $avatar_url, $match ) ) {
						$extension = $match[1];
					}

					require_once ABSPATH . '/wp-admin/includes/file.php';
					$avatar_temp_path = download_url( $avatar_url );

					if ( ! is_wp_error( $avatar_temp_path ) ) {
						if ( ! function_exists( 'xprofile_avatar_upload_dir' ) ) {
							$bp_members_functions_path = buddypress()->plugin_dir . '/bp-members/bp-members-functions.php';
							if ( file_exists( $bp_members_functions_path ) ) {
								require_once $bp_members_functions_path;
							}
						}

						if ( function_exists( 'xprofile_avatar_upload_dir' ) ) {
							$path_info = xprofile_avatar_upload_dir( 'avatars', $user_id );

							if ( wp_mkdir_p( $path_info['path'] ) ) {
								$av_dir = opendir( $path_info['path'] . '/' );
								if ( $av_dir ) {
									$has_avatar = false;
									while ( true ) {
										$avatar_file = readdir( $av_dir );
										if ( false === $avatar_file ) {
											break;
										}
										if ( preg_match( '/-bpfull/', $avatar_file ) || preg_match( '/-bpthumb/', $avatar_file ) ) {
											$has_avatar = true;
											break;
										}
									}
									if ( ! $has_avatar ) {
										$bp_full_filename = wp_unique_filename( $path_info['path'], uniqid() . "-bpfull.{$extension}" );
										$bp_thumb_filename = wp_unique_filename( $path_info['path'], uniqid() . "-bpthumb.{$extension}" );
										copy( $avatar_temp_path, $path_info['path'] . '/' . $bp_full_filename );
										rename( $avatar_temp_path, $path_info['path'] . '/' . $bp_thumb_filename );
									}
									closedir( $av_dir );
								}
							}
						}
					}
				}
			}
		}
	}
}

BB_SSO_Avatar::get_instance();
