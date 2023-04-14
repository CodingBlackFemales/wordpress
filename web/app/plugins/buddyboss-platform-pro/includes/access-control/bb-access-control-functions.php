<?php
/**
 * Memberships helpers
 *
 * @package BuddyBossPro
 * @since   1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the access control path.
 *
 * @param string $path path of access control.
 *
 * @since 1.1.0
 *
 * @return string path.
 */
function bb_access_control_path( $path = '' ) {
	$bb_platform_pro = bb_platform_pro();

	return trailingslashit( $bb_platform_pro->access_control_dir ) . trim( $path, '/\\' );
}

/**
 * Return the access control url.
 *
 * @param string $path url of access control.
 *
 * @since 1.1.0
 *
 * @return string url.
 */
function bb_access_control_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->access_control_url ) . trim( $path, '/\\' );
}

/**
 * Function will return list of posts of given post type.
 *
 * @param string $post_type post type name.
 *
 * @since 1.1.0
 *
 * @return array list of posts of given post type.
 */
function bb_access_control_get_posts( $post_type ) {
	global $wpdb;

	$results = array();

	if ( empty( $results ) ) {
		$query   = $wpdb->prepare( "SELECT posts.ID as 'id', posts.post_title as 'text' FROM {$wpdb->posts} posts WHERE posts.post_type = %s AND posts.post_status = %s", $post_type, 'publish' );
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
	}

	foreach ( $results as $key => $csm ) {
		$results[ $key ]['default'] = false;
	}

	/**
	 * Filter which will return the posts of given post type.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_get_posts', $results, $post_type );
}

/**
 * Function will return list of gamipress post type.
 *
 * @param string $post_type post type name.
 *
 * @since 1.1.0
 *
 * @return array list of posts of gamipress post type.
 */
function bb_access_control_gamipress_get_posts( $post_type ) {
	global $wpdb;

	$results = array();

	if ( empty( $results ) ) {
		$query   = $wpdb->prepare( "SELECT * FROM {$wpdb->posts} posts WHERE posts.post_type = %s AND posts.post_status = %s ", $post_type, 'publish' );
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
	}

	foreach ( $results as $key => $csm ) {
		$results[ $key ]['default'] = false;
	}

	/**
	 * Filter which will return the gamipress posts.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_gamipress_get_posts', $results, $post_type );
}

/**
 * Function will return the create group field settings data.
 *
 * @since 1.1.0
 *
 * @return array create group settings data.
 */
function bb_access_control_create_group_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_create_group_key(), $default );

	/**
	 * Filter which will return the create group settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_create_group_settings', $access_control_data );
}

/**
 * Function will return the upload document field settings data.
 *
 * @since 1.1.0
 *
 * @return array upload document settings data.
 */
function bb_access_control_upload_document_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_upload_document_key(), $default );

	/**
	 * Filter which will return the documents settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_upload_document_settings', $access_control_data );
}

/**
 * Function will return the upload photos field settings settings data.
 *
 * @since 1.1.0
 *
 * @return array upload photos settings data.
 */
function bb_access_control_upload_photos_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_upload_media_key(), $default );

	/**
	 * Filter which will return the photos settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_upload_photos_settings', $access_control_data );
}

/**
 * Function will return the upload videos field settings settings data.
 *
 * @since 1.1.4
 *
 * @return array upload videos settings data.
 */
function bb_access_control_upload_videos_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_upload_video_key(), $default );

	/**
	 * Filter which will return the videos settings.
	 *
	 * @since 1.1.4
	 */
	return apply_filters( 'bb_access_control_upload_videos_settings', $access_control_data );
}

/**
 * Function will return the friends field settings settings data.
 *
 * @since 1.1.0
 *
 * @return array friends settings data.
 */
function bb_access_control_friends_settings() {
	$access_control_data = bp_get_option( bb_access_control_friends_key(), array() );

	/**
	 * Filter which will return the connections settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_friends_settings', $access_control_data );
}

/**
 * Function will return the send message field settings data.
 *
 * @since 1.1.0
 *
 * @return array send message settings data.
 */
function bb_access_control_send_messages_settings() {
	$access_control_data = bp_get_option( bb_access_control_send_message_key(), array() );

	/**
	 * Filter which will return the message settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_send_messages_settings', $access_control_data );
}

/**
 * Function will return the create activity field settings data.
 *
 * @since 1.1.0
 *
 * @return array upload document settings data.
 */
function bb_access_control_create_activity_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_create_activity_key(), $default );

	/**
	 * Filter which will return the key for the activity access control data.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_create_activity_settings', $access_control_data );
}

/**
 * Function will return the join group field settings data.
 *
 * @since 1.1.0
 *
 * @return array join group settings data.
 */
function bb_access_control_join_group_settings() {
	$default = array(
		'access-control-type'           => '',
		'plugin-access-control-type'    => '',
		'gamipress-access-control-type' => '',
		'access-control-options'        => array(),
	);

	$access_control_data = bp_get_option( bb_access_control_join_group_key(), $default );

	/**
	 * Filter which will return the key for the group access control data.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_join_group_settings', $access_control_data );
}

/**
 * Function will return the create group field settings key.
 *
 * @return string create group key.
 */
function bb_access_control_create_group_key() {

	/**
	 * Filter which will return the key for the create group access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_create_group_key', 'bb-access-control-create-groups' );
}

/**
 * Function will return the create activity field settings key.
 *
 * @since 1.1.0
 *
 * @return string create activity key.
 */
function bb_access_control_create_activity_key() {

	/**
	 * Filter which will return the key for the activity access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_create_activity_key', 'bb-access-control-create-activity' );
}

/**
 * Function will return the upload media field settings key.
 *
 * @since 1.1.0
 *
 * @return string upload media key.
 */
function bb_access_control_upload_media_key() {

	/**
	 * Filter which will return the key for the upload media access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_upload_media_key', 'bb-access-control-upload-media' );
}

/**
 * Function will return the upload video field settings key.
 *
 * @since 1.1.4
 *
 * @return string upload video key.
 */
function bb_access_control_upload_video_key() {

	/**
	 * Filter which will return the key for the upload video access control settings.
	 *
	 * @since 1.1.4
	 */
	return apply_filters( 'bb_access_control_upload_video_key', 'bb-access-control-upload-video' );
}

/**
 * Function will return the friends field settings key.
 *
 * @since 1.1.0
 *
 * @return string friends key.
 */
function bb_access_control_friends_key() {

	/**
	 * Filter which will return the key for the connections access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_friends_key', 'bb-access-control-friends' );
}

/**
 * Function will return the send message field settings key.
 *
 * @since 1.1.0
 *
 * @return string send message key.
 */
function bb_access_control_send_message_key() {

	/**
	 * Filter which will return the key for the send message access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_send_message_key', 'bb-access-control-send-message' );
}

/**
 * Function will return the upload document field settings key.
 *
 * @since 1.1.0
 *
 * @return string upload document key.
 */
function bb_access_control_upload_document_key() {

	/**
	 * Filter which will return the key for the upload document access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_upload_document_key', 'bb-access-control-upload-document' );
}

/**
 * Function will return the join group field settings key.
 *
 * @since 1.1.0
 *
 * @return string join group key.
 */
function bb_access_control_join_group_key() {

	/**
	 * Filter which will return the key for the join group access control settings.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_join_group_key', 'bb-access-control-join-groups' );
}

/**
 * Function will show the error or feedback message.
 *
 * @param string $message message to be display.
 * @param string $type message type.
 *
 * @since 1.1.0
 */
function bb_access_control_display_feedback( $message = '', $type = '' ) {

	?>
	<div class="bp-messages-feedback">
		<div class="bp-feedback <?php echo esc_attr( $type ); ?>">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	</div>
	<?php

}

/**
 * Do not save the settings if user didn't selected the proper settings.
 *
 * @param array $new_value selected value.
 * @param array $old_value old value.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bb_access_control_single_settings_save( $new_value, $old_value ) {

	if ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && empty( $new_value['access-control-type'] ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'gamipress' === $new_value['access-control-type'] && ( empty( $new_value['gamipress-access-control-type'] ) || ! array_key_exists( 'access-control-options', $new_value ) ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'membership' === $new_value['access-control-type'] && ( empty( $new_value['plugin-access-control-type'] ) || ! array_key_exists( 'access-control-options', $new_value ) ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && ! array_key_exists( 'access-control-options', $new_value ) ) {
		$new_value = '';
	}
	return $new_value;
}
add_filter( 'pre_update_option_' . bb_access_control_create_group_key(), 'bb_access_control_single_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_create_activity_key(), 'bb_access_control_single_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_upload_media_key(), 'bb_access_control_single_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_upload_video_key(), 'bb_access_control_single_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_upload_document_key(), 'bb_access_control_single_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_join_group_key(), 'bb_access_control_single_settings_save', 10, 2 );

/**
 * Do not save the settings if user didn't selected the proper settings.
 *
 * @param array $new_value selected value.
 * @param array $old_value old value.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bb_access_control_threaded_settings_save( $new_value, $old_value ) {

	if ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && empty( $new_value['access-control-type'] ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'gamipress' === $new_value['access-control-type'] && ( empty( $new_value['gamipress-access-control-type'] ) || ! array_key_exists( 'access-control-options', $new_value ) ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'gamipress' === $new_value['access-control-type'] && ( ! empty( $new_value['gamipress-access-control-type'] ) && array_key_exists( 'access-control-options', $new_value ) && ! empty( $new_value['access-control-options'] ) ) ) {
		foreach ( $new_value['access-control-options'] as $option ) {
			$key = 'access-control-' . $option . '-options';
			if ( empty( $new_value[ $key ] ) ) {
				$new_value = '';
				break;
			}
		}
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'membership' === $new_value['access-control-type'] && ( empty( $new_value['plugin-access-control-type'] ) || ! array_key_exists( 'access-control-options', $new_value ) ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && 'membership' === $new_value['access-control-type'] && ( ! empty( $new_value['plugin-access-control-type'] ) && array_key_exists( 'access-control-options', $new_value ) && ! empty( $new_value['access-control-options'] ) ) ) {
		foreach ( $new_value['access-control-options'] as $option ) {
			$key = 'access-control-' . $option . '-options';
			if ( empty( $new_value[ $key ] ) ) {
				$new_value = '';
				break;
			}
		}
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && ! array_key_exists( 'access-control-options', $new_value ) ) {
		$new_value = '';
	} elseif ( isset( $new_value ) && isset( $new_value['access-control-type'] ) && ! empty( $new_value['access-control-type'] ) && array_key_exists( 'access-control-options', $new_value ) && ! empty( $new_value['access-control-options'] ) ) {
		foreach ( $new_value['access-control-options'] as $option ) {
			$key = 'access-control-' . $option . '-options';
			if ( empty( $new_value[ $key ] ) ) {
				$new_value = '';
				break;
			}
		}
	}
	return $new_value;
}
add_filter( 'pre_update_option_' . bb_access_control_friends_key(), 'bb_access_control_threaded_settings_save', 10, 2 );
add_filter( 'pre_update_option_' . bb_access_control_send_message_key(), 'bb_access_control_threaded_settings_save', 10, 2 );

/**
 * Flatten the array.
 *
 * @param array $array Source array.
 *
 * @since 1.1.0
 * @return array Flattened array.
 */
function bb_access_control_array_flatten( $array ) {
	if ( ! is_array( $array ) ) {
		return false;
	}
	$result = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$result = array_merge( $result, bb_access_control_array_flatten( $value ) );
		} else {
			$result[] = $value;
		}
	}

	return $result;
}

/**
 * Link to Access Control tutorial
 *
 * @since 1.1.1
 */
function bb_admin_access_control_setting_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url( // phpcs:ignore
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 121813,
				),
				'admin.php'
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
	</p>

	<?php
}
