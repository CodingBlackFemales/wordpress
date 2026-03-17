<?php
/**
 * Topic helper functions.
 *
 * @since   2.7.40
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the topic path.
 *
 * @since 2.7.40
 *
 * @param string $path path of topic.
 *
 * @return string path.
 */
function bb_topics_path( $path = '' ) {
	$bb_platform_pro = bb_platform_pro();

	return trailingslashit( $bb_platform_pro->topics_dir ) . trim( $path, '/\\' );
}

/**
 * Return the topic URL.
 *
 * @since 2.7.40
 *
 * @param string $path URL of topic.
 *
 * @return string url.
 */
function bb_topics_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->topics_url ) . trim( $path, '/\\' );
}

/**
 * Function to load the instance of the class BB_Topics.
 *
 * @since 2.7.40
 *
 * @return object
 */
function bb_load_topics() {
	if ( class_exists( 'BB_Topics' ) ) {
		return BB_Topics::instance();
	}

	return new stdClass();
}

/**
 * Function to check whether a topic is allowed or not based on required dependencies.
 *
 * @since 2.7.40
 *
 * @return bool
 */
function bb_topics_check_dependency() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, bb_platform_topics_version(), '<' ) ||
		(
			! bp_is_active( 'groups' ) &&
			! bp_is_active( 'activity' )
		) ||
		! is_user_logged_in()
	) {
		return false;
	}

	return true;
}

/**
 * Determine who can manage the topics tab for the manage group.
 *
 * @since 2.7.40
 *
 * @return bool
 */
function bb_group_activity_topics_manage_tab() {
	if ( ! bb_is_enabled_group_activity_topics() ) {
		return false;
	}
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	return true;
}

/**
 * Filter whether to enable group activity topics.
 *
 * @since 2.7.40
 *
 * @param bool $enable_group_topics Whether to enable group topics.
 *
 * @return bool
 */
function bb_is_enabled_group_activity_topics( $enable_group_topics = false ) {

	/**
	 * Filters whether to enable group activity topics.
	 *
	 * @since 2.7.40
	 *
	 * @param bool $enable_group_topics Whether to enable group topics.
	 */
	return (bool) apply_filters( 'bb_is_enabled_group_activity_topics', bp_get_option( 'bb-enable-group-activity-topics', $enable_group_topics ) );
}

/**
 * Get the group activity topic options.
 *
 * @since 2.7.40
 *
 * @param string $group_topic_options The global group topic options.
 *
 * @return string
 */
function bb_get_group_activity_topic_options( $group_topic_options = 'only_from_activity_topics' ) {

	/**
	 * Filters the group activity topic options.
	 *
	 * @since 2.7.40
	 *
	 * @param string $group_topic_options The global group topic options.
	 */
	return apply_filters( 'bb_get_group_activity_topic_options', bp_get_option( 'bb-group-activity-topics-options', $group_topic_options ) );
}

/**
 * Filter the permission types for the group activity topic.
 *
 * @since 2.7.40
 *
 * @param string $existing_permission_type The existing permission type.
 *
 * @return array Array of group permission types.
 */
function bb_group_activity_topic_permission_type( $existing_permission_type = '' ) {
	$permission_types = apply_filters(
		/**
		 * Filters the permission types for the group activity topic.
		 *
		 * @since 2.7.40
		 *
		 * @param array $permission_types Array of group permission types.
		 */
		'bb_group_activity_topic_permission_type',
		array(
			'members' => __( 'All group members', 'buddyboss-pro' ),
			'mods'    => __( 'Organizers and Moderators Only', 'buddyboss-pro' ),
			'admins'  => __( 'Organizers Only', 'buddyboss-pro' ),
		)
	);

	if ( ! empty( $existing_permission_type ) && isset( $permission_types[ $existing_permission_type ] ) ) {
		return array( $existing_permission_type => $permission_types[ $existing_permission_type ] );
	}

	return $permission_types;
}

/**
 * Function to fetch group activity topics.
 *
 * @since 2.7.40
 *
 * @param array $args The arguments array.
 *
 * @return array Array of group activity topics.
 */
function bb_get_group_activity_topics( $args = array() ) {

	if ( ! bp_is_active( 'groups' ) ) {
		return array();
	}

	$r = bp_parse_args(
		$args,
		array(
			'item_id'   => bp_get_current_group_id(),
			'item_type' => 'groups',
			'fields'    => 'name,slug,topic_id',
		)
	);

	$user_id = bp_loggedin_user_id();

	$r['permission_type'] = array();
	// Set topic visibility permissions based on user role.
	if ( ! empty( $r['can_post'] ) ) {
		// Group admins can only see member topics.
		if ( groups_is_user_admin( $user_id, $r['item_id'] ) ) {
			// Group moderators can see both mod and member topics.
			$r['permission_type'] = array( 'admins', 'mods', 'members' );
		} elseif ( groups_is_user_mod( $user_id, $r['item_id'] ) ) {
			// Group moderators can see both mod and member topics.
			$r['permission_type'] = array( 'mods', 'members' );
		} elseif ( groups_is_user_member( $user_id, $r['item_id'] ) ) {
			$r['permission_type'] = array( 'members' );
		}

		if ( empty( $r['permission_type'] ) ) {
			return array();
		}
	}

	$cache_key   = 'bb_activity_topics_' . md5( maybe_serialize( $r ) );
	$topic_cache = wp_cache_get( $cache_key, 'bb_activity_topics' );
	if ( false !== $topic_cache ) {
		return $topic_cache;
	}

	$topic_lists = bb_topics_manager_instance()->bb_get_topics( $r );

	if ( ! empty( $r['count_total'] ) ) {
		$topic_lists = ! empty( $topic_lists ) ? $topic_lists : array();
	} else {
		$topic_lists = ! empty( $topic_lists['topics'] ) ? $topic_lists['topics'] : array();
	}

	wp_cache_set( $cache_key, $topic_lists, 'bb_activity_topics' );

	return ! empty( $topic_lists ) ? $topic_lists : array();
}

/**
 * Check if a user can post to a group activity topic.
 *
 * @since 2.7.40
 *
 * @param int $user_id  The user ID.
 * @param int $group_id The group ID.
 * @param int $topic_id The topic ID.
 *
 * @return bool True if the user can post to the topic, false otherwise.
 */
function bb_can_user_post_to_group_activity_topic( $user_id, $group_id, $topic_id ) {

	if ( ! bp_is_active( 'groups' ) ) {
		return false;
	}

	// Get a topic permission type.
	$topic_permission = bb_topics_manager_instance()->bb_get_topic_permission_type(
		array(
			'topic_id'  => $topic_id,
			'item_id'   => $group_id,
			'item_type' => 'groups',
		)
	);

	// Group admin can post to any topic.
	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		return true;
	}

	// Group moderator can post to mod and member topics.
	if ( groups_is_user_mod( $user_id, $group_id ) ) {
		return in_array( $topic_permission, array( 'mods', 'members' ), true );
	}

	// Regular members can only post to member topics.
	if ( groups_is_user_member( $user_id, $group_id ) ) {
		return 'members' === $topic_permission;
	}

	// Non-members can't post.
	return false;
}
