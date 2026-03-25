<?php
/**
 * Poll helper functions.
 *
 * @package BuddyBossPro
 * @since   2.6.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the poll path.
 *
 * @since 2.6.00
 *
 * @param string $path path of poll.
 *
 * @return string path.
 */
function bb_polls_path( $path = '' ) {
	$bb_platform_pro = bb_platform_pro();

	return trailingslashit( $bb_platform_pro->polls_dir ) . trim( $path, '/\\' );
}

/**
 * Return the poll url.
 *
 * @since 2.6.00
 *
 * @param string $path url of poll.
 *
 * @return string url.
 */
function bb_polls_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->polls_url ) . trim( $path, '/\\' );
}

/**
 * Function to load the instance of the class BB_Polls.
 *
 * @since 2.6.00
 *
 * @return object
 */
function bb_load_polls() {
	if ( class_exists( 'BB_Polls' ) ) {
		return BB_Polls::instance();
	}

	return new stdClass();
}

/**
 * Function to check a poll allow or not based on required dependencies.
 *
 * @since 2.6.00
 *
 * @return bool
 */
function bb_poll_check_dependency() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, bb_platform_poll_version(), '<' ) ||
		! bp_is_active( 'activity' ) ||
		! is_user_logged_in()
	) {
		return false;
	}

	return true;
}

/**
 * Check whether activity polls are enabled.
 *
 * @since 2.6.00
 *
 * @param bool $retval true Activity polls always are enabled for admin.
 *
 * @return bool true if activity polls are enabled, otherwise false.
 */
function bb_is_enabled_activity_post_polls( $retval = true ) {

	// Return false if platform pro has not valid license.
	if ( bb_pro_should_lock_features() ) {
		return false;
	}

	if ( true === $retval && bp_current_user_can( 'administrator' ) ) {
		return true;
	}

	return (bool) bp_get_option( '_bb_enable_activity_post_polls', false );
}

/**
 * Check whether user can create polls for activity or not.
 *
 * @since 2.6.00
 *
 * @param array $args Array of Arguments.
 *
 * @return bool true if user can create polls, otherwise false.
 */
function bb_can_user_create_poll_activity( $args = array() ) {
	if ( ! bb_poll_check_dependency() ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_loggedin_user_id(),
			'object'   => '',
			'group_id' => 0,
		)
	);

	$retval = false;
	if (
		bp_is_active( 'groups' ) &&
		(
			'group' === $r['object'] ||
			bp_is_group()
		)
	) {
		$group_id = 'group' === $r['object'] && ! empty( $r['group_id'] ) ? $r['group_id'] : bp_get_current_group_id();
		$is_admin = groups_is_user_admin( $r['user_id'], $group_id );
		$is_mod   = groups_is_user_mod( $r['user_id'], $group_id );
		if (
			bb_is_enabled_activity_post_polls( false ) &&
			( $is_admin || $is_mod )
		) {
			$retval = true;
		}
	} elseif ( bp_user_can( $r['user_id'], 'administrator' ) && bb_is_enabled_activity_post_polls() ) {
		$retval = true;
	}

	/**
	 * Filters whether user can create polls for activity.
	 *
	 * @since 2.6.00
	 *
	 * @param bool  $retval Return value for polls.
	 * @param array $args   Array of Arguments.
	 */
	return apply_filters( 'bb_can_user_create_poll_activity', $retval, $args );
}

/**
 * Function to check a poll allows multiple options.
 *
 * @since 2.6.00
 *
 * @param object $get_poll Poll data.
 *
 * @return bool
 */
function bb_poll_allow_multiple_options( $get_poll ) {

	if ( empty( $get_poll ) ) {
		return false;
	}

	if ( isset( $get_poll->settings ) ) {
		$settings = $get_poll->settings;

		return (bool) $settings['allow_multiple_options'] ?? false;
	}

	return false;
}

/**
 * Function to check a poll allows adding option.
 *
 * @since 2.6.00
 *
 * @param object $get_poll Poll data.
 *
 * @return bool
 */
function bb_poll_allow_new_options( $get_poll ) {

	if ( empty( $get_poll ) ) {
		return false;
	}

	if ( isset( $get_poll->settings ) ) {
		$settings = $get_poll->settings;

		return (bool) $settings['allow_new_option'] ?? false;
	}

	return false;
}

/**
 * Function to get a poll duration.
 *
 * @since 2.6.00
 *
 * @param object $get_poll Poll data.
 *
 * @return int
 */
function bb_poll_get_duration( $get_poll ) {

	if ( empty( $get_poll ) ) {
		return false;
	}

	if ( isset( $get_poll->settings ) ) {
		$settings = $get_poll->settings;

		return (int) $settings['duration'] ?? 3;
	}

	return false;
}

/**
 * Function to get a poll id from activity meta.
 *
 * @since 2.6.00
 *
 * @param int $activity_id Activity id.
 *
 * @return int
 */
function bb_poll_get_activity_meta_poll_id( $activity_id ) {

	$activity_metas = bb_activity_get_metadata( $activity_id );

	return ! empty( $activity_metas['bb_poll_id'][0] ) ? (int) $activity_metas['bb_poll_id'][0] : 0;
}

/**
 * Function to display a poll in the email.
 *
 * @since 2.6.00
 *
 * @param int    $object_id Activity id.
 * @param int    $poll_id   Poll id.
 * @param array  $tokens    Tokens.
 * @param string $type      Type.
 *
 * @return void
 */
function get_email_poll( $object_id, $poll_id, $tokens, $type = 'activity' ) {
	$poll     = bb_load_polls()->bb_get_poll( $poll_id );
	$question = ! empty( $poll->question ) ? $poll->question : '';
	?>
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tbody>
		<tr>
			<td style="padding: 10px 24px 26px 0;">
				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tbody>
					<tr>
						<td style="padding: 0 0 15px 0;">
							<p style="font-size: 18px; color: #1E2132;font-weight: 500;margin: 0;"><?php echo esc_html( $question ); ?></p>
						</td>
					</tr>
					<tr>
						<td style="background-color: #ffffff;padding: 25px 0;text-align:center; border-radius: 6px;">
							<a style="text-decoration: none;display: inline-block; text-align:center;" href="<?php echo esc_url( bp_activity_get_permalink( $object_id ) ); ?>">
								<img style="border-radius: 50%; max-width: 96px; object-fit: cover; display: block; margin: 0 auto;" src="<?php echo esc_url( bb_polls_url() . '/assets/images/poll-email-icon.png' ); ?>" alt="<?php echo esc_attr( $question ); ?>" />
							</a>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
	<?php
	unset( $poll, $question );
}

/**
 * Update votes for a poll if the setting is disabled to disallow multiple options.
 *
 * @param array $args {
 * Array of arguments.
 *
 * @type int  'poll_id'                The poll ID.
 * @type bool 'allow_multiple_options' The new setting for allowing multiple options.
 * }
 */
function bb_update_votes_after_disable_allow_multiple_options( $args = array() ) {

	if ( ! isset( $args['poll_id'], $args['allow_multiple_options'] ) ) {
		return;
	}

	$poll_id               = (int) $args['poll_id'];
	$allow_multiple_answer = (bool) $args['allow_multiple_options'];
	$existing_poll         = bb_load_polls()->bb_get_poll( $poll_id );

	// Ensure the poll exists.
	if ( ! $existing_poll ) {
		return;
	}

	$existing_allow_multiple_answer = bb_poll_allow_multiple_options( $existing_poll );

	if ( true === $existing_allow_multiple_answer && false === $allow_multiple_answer ) {
		// Remove all votes except the latest one.
		global $wpdb;
		$table_name = bp_core_get_table_prefix() . 'bb_poll_votes';

		// Prepare and execute the query.
		$sql = "DELETE t1 FROM $table_name t1
			    LEFT JOIN (
			        SELECT MAX(id) AS id
			        FROM $table_name
			        WHERE poll_id = %d
			        GROUP BY user_id
			    ) t2 ON t1.id = t2.id
		    	WHERE t2.id IS NULL AND t1.poll_id = %d;";

		$wpdb->query( $wpdb->prepare( $sql, $poll_id, $poll_id ) );
	}
}
