<?php
/**
 * Database functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Create necessary table
 */
function learndash_notifications_create_db_table() {
	global $wpdb;
	$current_version = '1.2';
	$db_version      = get_option( 'ld_notifications_db_version' );

	if ( $db_version === false || version_compare( $current_version, $db_version, '!=' ) === true ) {
		$table_name      = "{$wpdb->prefix}ld_notifications_delayed_emails";
		$charset_collate = $wpdb->get_charset_collate();

		if ( strpos( $wpdb->charset, 'utf8' ) === false ) {
			$charset         = 'utf8mb4';
			$collate         = 'utf8mb4_unicode_ci';
			$charset_collate = "DEFAULT CHARACTER SET {$charset} COLLATE {$collate}";
		}

		$sql = "CREATE TABLE $table_name (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(500) NOT NULL,
			message text NOT NULL,
			recipient varchar(2000) NOT NULL,
			shortcode_data varchar(1000),
			sent_on varchar(20) NOT NULL,
			bcc varchar(2000),
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'ld_notifications_db_version', $current_version );
	}
}
// for unit test
$hook = php_sapi_name() === 'cli' ? 'init' : 'admin_init';
add_action( $hook, 'learndash_notifications_create_db_table' );


/**
 * Delete delayed emails when a user is unenrolled from a course
 *
 * @param int    $user_id    ID of a user
 * @param int    $course_id  ID of a course
 * @param string $user_id    Course access list
 * @param bool   $user_id    True if user unenrolled|false otherwise
 */
function learndash_notifications_delete_delayed_email_by_unenrolled_course( $user_id, $course_id, $access_list, $remove ) {
	if ( $remove ) {
		learndash_notifications_delete_delayed_email_by_user_id_course_id( $user_id, $course_id );
	}}

add_action( 'learndash_update_course_access', 'learndash_notifications_delete_delayed_email_by_unenrolled_course', 10, 4 );


/**
 * Delete delayed emails when a user is deleted
 *
 * @param int $user_id ID of a user
 */
function learndash_notifications_delete_delayed_email_by_deleted_user( $user_id, $reassign_id ) {
	learndash_notifications_delete_delayed_email_by_user_id( $user_id );
}

add_action( 'deleted_user', 'learndash_notifications_delete_delayed_email_by_deleted_user', 10, 2 );

/**
 * Delete delayed emails in DB by a key available in column 'shortcode_data'
 *
 * @param string $key   Key available in shortcode_data column
 * @param string $value Value being searched to be deleted
 */
function learndash_notifications_delete_delayed_emails_by( $key, $value ) {
	learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
		[
			$key => $value,
		]
	);
}

/**
 * Delete delayed emails using recipient email address
 *
 * @param string $email_address  Email address of recipient
 */
function learndash_notifications_delete_delayed_emails_by_email( $email_address ) {
	global $wpdb;

	$sql = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE recipient LIKE '%%%s%%'", $wpdb->esc_like( $email_address ) );

	return $wpdb->query( $sql );
}

/**
 * Delete delayed emails using user ID
 *
 * @param int $user_id   ID of a user
 */
function learndash_notifications_delete_delayed_email_by_user_id( $user_id ) {
	learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
		[
			'user_id' => $user_id,
		]
	);
}

/**
 * Delete delayed emails using user ID and course ID
 *
 * @param int $user_id   ID of a user
 * @param int $course_id ID of a course
 */
function learndash_notifications_delete_delayed_email_by_user_id_course_id( $user_id, $course_id ) {
	learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
		[
			'user_id'   => $user_id,
			'course_id' => $course_id,
		]
	);
}

function learndash_notifications_delete_delayed_emails_by_user_id_lesson_id( $user_id, $lesson_id ) {
	learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
		[
			'user_id'   => $user_id,
			'lesson_id' => $lesson_id,
		]
	);
}

function learndash_notifications_delete_delayed_email_by_id( $id ) {
	global $wpdb;

	$wpdb->delete(
		"{$wpdb->prefix}ld_notifications_delayed_emails",
		[ 'id' => $id ],
		[ '%d' ]
	);
}

/**
 * Get all delayed emails stored in database
 *
 * @param array $where SELECT condition taken from shortcode_data column
 * @return array All delayed emails existing in database
 */
function learndash_notifications_get_all_delayed_emails( $where = [] ) {
	global $wpdb;

	$sql = "SELECT * FROM {$wpdb->prefix}ld_notifications_delayed_emails";

	if ( ! empty( $where ) ) {
		$sql .= ' WHERE ';

		$total = count( $where );
		$count = 0;
		foreach ( $where as $key => $value ) {
			++$count;

			$key   = sanitize_text_field( $key );
			$value = sanitize_text_field( $value );

			$pattern = "$key\".{0,6}\"?$value(;|\"|\')";
			$sql    .= "shortcode_data REGEXP '$pattern'";

			if ( $count < $total ) {
				$sql .= ' AND ';
			}
		}
	}

	return $wpdb->get_results( $sql, ARRAY_A );
}

function learndash_notifications_get_all_delayed_emails_by_recipient( $recipient ) {
	global $wpdb;

	$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE recipient LIKE '%%%s%%'", $wpdb->esc_like( $recipient ) );

	return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Save a delayed notification to delayed email table
 *
 * @param object $notification     Notification WP post object
 * @param array  $emails           List of recipient emails
 * @param int    $sent_on          UNIX timestamp when the email will be sent
 * @param array  $shortcode_data   List of array
 * @param array  $bcc              List of email addresses as bcc
 * @return bool                     True if success|false otherwise
 */
function learndash_notifications_save_delayed_email( $notification, $emails, $sent_on, $shortcode_data, $bcc = [], $update_where = [] ) {
	$n = $notification;

	return learndash_notifications_insert_delayed_email( $n->post_title, $n->post_content, $emails, $shortcode_data, $sent_on, $bcc, $update_where );
}

/**
 * Insert an email to delayed email table
 *
 * @param string $title            Title of email
 * @param string $message          Content of email
 * @param array  $message          Email addresses
 * @param array  $shortcode_data   List of array
 * @param int    $sent_on          UNIX timestamp when the email will be sent
 * @param array  $bcc              List of email addresses as bcc
 * @param array  $update_where     Where clauses for update operation
 * @return bool                     True if success|false otherwise
 */
function learndash_notifications_insert_delayed_email( $title, $message, $recipient, $shortcode_data, $sent_on, $bcc = [], $update_where = [] ) {
	global $wpdb;

	if ( empty( $recipient ) ) {
		return false;
	}

	$existing_record = learndash_notifications_db_select_count( $update_where );

	$data = [
		'title'          => $title,
		'message'        => $message,
		'recipient'      => maybe_serialize( $recipient ),
		'shortcode_data' => maybe_serialize( $shortcode_data ),
		'sent_on'        => $sent_on,
		'bcc'            => maybe_serialize( $bcc ),
	];

	$update = learndash_notifications_update_delayed_email( $data, $update_where );

	if ( 0 === $update && 0 == $existing_record ) {
		$insert = $wpdb->insert(
			"{$wpdb->prefix}ld_notifications_delayed_emails",
			$data,
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( $insert !== false ) {
			do_action( 'learndash_notifications_insert_delayed_email', $data );

			return true;
		} else {
			return false;
		}
	}
}

/**
 * Update existing delayed emails
 */
function learndash_notifications_update_delayed_email( $data = [], $where = [] ) {
	// Bail if data or where array empty
	if ( empty( $data ) || empty( $where ) ) {
		return 0;
	}

	global $wpdb;

	$sql = "UPDATE {$wpdb->prefix}ld_notifications_delayed_emails SET ";

	$data_count = 0;
	$data_total = count( $data );
	foreach ( $data as $key => $value ) {
		++$data_count;

		$key   = esc_sql( $key );
		$value = esc_sql( $value );

		$sql .= "{$key} = '{$value}'";

		if ( $data_count < $data_total ) {
			$sql .= ', ';
		}
	}

	$sql .= ' WHERE ';

	$total = count( $where );
	$count = 0;
	foreach ( $where as $key => $value ) {
		++$count;

		$key   = esc_sql( $key );
		$value = esc_sql( $value );

		$pattern = "$key\".{0,6}\"?$value(;|\"|\')";
		$sql    .= "shortcode_data REGEXP '$pattern'";

		if ( $count < $total ) {
			$sql .= ' AND ';
		}
	}

	$count = $wpdb->query( $sql );

	if ( false !== $count ) {
		if ( 0 !== $count ) {
			do_action( 'learndash_notifications_update_delayed_email', $data, $where, $count );
		}

		return $count;
	} else {
		return false;
	}
}

/**
 * Count existing record in DB
 *
 * @param array $where Array of where clause
 * @return int           Number of records in DB
 */
function learndash_notifications_db_select_count( $where = [] ) {
	if ( empty( $where ) ) {
		return 0;
	}

	global $wpdb;

	$sql = "SELECT COUNT(*)
		FROM {$wpdb->prefix}ld_notifications_delayed_emails
		WHERE ";

	$total = count( $where );
	$count = 0;
	foreach ( $where as $key => $value ) {
		++$count;

		$key   = sanitize_text_field( $key );
		$value = sanitize_text_field( $value );

		$pattern = "$key\".{0,6}\"?$value(;|\"|\')";
		$sql    .= "shortcode_data REGEXP '$pattern'";

		if ( $count < $total ) {
			$sql .= ' AND ';
		}
	}

	return $wpdb->get_var( $sql );
}

function learndash_notifications_delete_delayed_emails_by_shortcode_data_key( $key = '', $value = '' ) {
	global $wpdb;

	$sql = "DELETE FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE `shortcode_data` LIKE '%s'";

	$wpdb->query(
		$wpdb->prepare(
			$sql,
			"%$key\"_%_%_%_%$value%"
		)
	);
}

function learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key( $pair = [] ) {
	global $wpdb;

	$sql = "DELETE FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE ";

	$total = count( $pair );
	$count = 0;
	foreach ( $pair as $key => $value ) {
		++$count;

		$key   = esc_sql( $key );
		$value = esc_sql( $value );

		$pattern = "$key\".{0,6}\"?$value(;|\"|\')";
		$sql    .= "shortcode_data REGEXP '$pattern'";

		if ( $count < $total ) {
			$sql .= ' AND ';
		}
	}

	$wpdb->query( $sql );
}

function learndash_notifications_empty_db_table() {
	global $wpdb;
	$sql = "DELETE FROM {$wpdb->prefix}ld_notifications_delayed_emails";

	$count = $wpdb->query( $sql );

	do_action( 'learndash_notifications_empty_delayed_emails_table', $count );
}

/**
 * Delete delayed emails from DB if sent_on timestamp has passed current time
 *
 * Fired in cron.php
 */
function learndash_notifications_delete_delayed_emails() {
	global $wpdb;

	$timestamp = strtotime( '-2 hours' );

	$sql = "DELETE FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE `sent_on` <= {$timestamp}";

	$count = $wpdb->query( $sql );

	do_action( 'learndash_notifications_delete_delayed_emails', $count );
}
