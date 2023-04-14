<?php

if ( ! function_exists( 'buddyboss_theme_get_option' ) ) {

	/**
	 * Get BuddyBoss_Theme options
	 *
	 * @param string $id Option ID.
	 * @param string $param Option type.
	 * @param bool   $default default value.
	 *
	 * @return $output False on failure, Option.
	 */
	function buddyboss_theme_get_option( $id, $param = null, $default = false ) {

		global $buddyboss_theme_options;

		if ( in_array( $id, array( 'buddyboss_profile_cover_default', 'buddyboss_group_cover_default' ), true ) && function_exists( 'buddypress' ) && defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION, '1.8.5', '>' ) ) {

			$object_dir = 'members';
			if ( 'buddyboss_group_cover_default' === $id ) {
				$object_dir = 'groups';
			}

			$cover_image_url = bp_attachments_get_attachment(
				'url',
				array(
					'object_dir' => $object_dir,
					'item_id'    => 'custom',
				)
			);

			if ( ! empty( $cover_image_url ) ) {

				list( $width, $height ) = wp_getimagesize( $cover_image_url );
				$file_basename          = wp_basename( $cover_image_url );
				$file_ext               = wp_check_filetype( $cover_image_url )['ext'];
				$filename               = str_replace( '.' . $file_ext, '', $file_basename );

				// Prepare array for default cover image.
				$cover_array = array(
					'url'                    => $cover_image_url,
					'id'                     => 0,
					'height'                 => $height,
					'width'                  => $width,
					'thumbnail'              => '',
					'title'                  => $filename,
					'forum_background_large' => '',
					'caption'                => '',
					'alt'                    => '',
					'description'            => '',
				);

				if ( ! empty( $param ) && is_string( $param ) && isset( $cover_array[ $param ] ) ) {
					return $cover_array[ $param ];
				}

				return $cover_array;
			}

			return (bool) $default;

		} elseif ( in_array( $id, array( 'buddyboss_profile_cover_width', 'buddyboss_profile_cover_height', 'buddyboss_group_cover_width', 'buddyboss_group_cover_height' ), true ) ) {

			if ( 'buddyboss_profile_cover_width' === $id ) {
				return function_exists( 'bb_get_profile_cover_image_width' ) ? bb_get_profile_cover_image_width() : get_option( 'bb-pro-cover-profile-width', $default );
			} elseif ( 'buddyboss_profile_cover_height' === $id ) {
				return function_exists( 'bb_get_profile_cover_image_height' ) ? bb_get_profile_cover_image_height() : get_option( 'bb-pro-cover-profile-height', $default );
			} elseif ( 'buddyboss_group_cover_width' === $id ) {
				return function_exists( 'bb_get_group_cover_image_width' ) ? bb_get_group_cover_image_width() : get_option( 'bb-pro-cover-group-width', $default );
			} elseif ( 'buddyboss_group_cover_height' === $id ) {
				return function_exists( 'bb_get_group_cover_image_height' ) ? bb_get_group_cover_image_height() : get_option( 'bb-pro-cover-group-height', $default );
			}
		}

		/* Check if options are set */
		if ( ! isset( $buddyboss_theme_options ) ) {
			$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
		}

		/* Check if array subscript exist in options */
		if ( empty( $buddyboss_theme_options[ $id ] ) ) {
			if ( array_key_exists( $id, $buddyboss_theme_options ) ) {
				return false;
			} else {
				// Return true if default passed to true and key not exists into the buddyboss_theme_options array.
				return (bool) $default;
			}
		}

		/**
		 * If $param exists,  then
		 * 1. It should be 'string'.
		 * 2. '$buddyboss_theme_options[ $id ]' should be array.
		 * 3. '$param' array key exists.
		 */
		if ( ! empty( $param ) && is_string( $param ) && ( ! is_array( $buddyboss_theme_options[ $id ] ) || ! array_key_exists( $param, $buddyboss_theme_options[ $id ] ) ) ) {
			return false;
		}

		return empty( $param ) ? $buddyboss_theme_options[ $id ] : $buddyboss_theme_options[ $id ][ $param ];
	}
}
