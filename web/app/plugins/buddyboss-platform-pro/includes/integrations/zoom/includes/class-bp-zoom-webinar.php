<?php
/**
 * BuddyBoss Zoom Webinar
 *
 * @package BuddyBossPro/Integration/Zoom
 * @since 1.0.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss zoom webinar.
 * Instance methods are available for creating/editing an webinar,
 * static methods for querying webinar.
 *
 * @since 1.0.9
 */
class BP_Zoom_Webinar {

	/** Properties ************************************************************/

	/**
	 * ID of the webinar item.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $id;

	/**
	 * Group ID of the webinar item.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $group_id;

	/**
	 * Activity ID of the webinar item.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $activity_id;

	/**
	 * Site User ID of the webinar item.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $user_id;

	/**
	 * Title of the webinar item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $title;

	/**
	 * Description of the webinar item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $description;

	/**
	 * Host ID of the webinar item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $host_id;

	/**
	 * Timezone of the webinar item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $timezone;

	/**
	 * Password of the webinar item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $password;

	/**
	 * Duration of the webinar item.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $duration;

	/**
	 * Host video of the webinar item.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $host_video;

	/**
	 * Panelists video of the webinar item.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $panelists_video;

	/**
	 * Webinar authetication.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $meeting_authentication;

	/**
	 * Recurring webinar.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $recurring;

	/**
	 * Auto recording of the media item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $auto_recording;

	/**
	 * Alternative host ids of the media item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $alternative_host_ids;

	/**
	 * Zoom webinar id of the media item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $webinar_id;

	/**
	 * Zoom webinar start date in utc of the media item.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $start_date_utc;

	/**
	 * Whether the webinar should be hidden in sitewide.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $hide_sitewide = 0;

	/**
	 * Parent of the webinar.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $parent;

	/**
	 * Type of the webinar or webinar.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $type;

	/**
	 * Type of the webinar occurrence or webinar occurrence.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $zoom_type;

	/**
	 * Practice session for webinar.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $practice_session;

	/**
	 * On-demand webinar.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $on_demand;

	/**
	 * Alert.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $alert;

	/**
	 * Error holder.
	 *
	 * @since 1.0.9
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Whether the webinar is past or not.
	 *
	 * @since 1.0.9
	 *
	 * @var boolean
	 */
	public $is_past = false;

	/**
	 * Whether the webinar is live or not.
	 *
	 * @since 1.0.9
	 *
	 * @var boolean
	 */
	public $is_live = false;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since 1.0.9
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since 1.0.9
	 *
	 * @param int|bool $id Optional. The ID of a specific webinar item.
	 */
	public function __construct( $id = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific webinar item.
	 *
	 * @since 1.0.9
	 */
	public function populate() {
		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_webinar' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_zoom_webinars WHERE id = %d", $this->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			wp_cache_set( $this->id, $row, 'bp_webinar' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		$start_date_utc = new DateTime( $row->start_date_utc, new DateTimeZone( 'UTC' ) );
		$start_date_utc->modify( '+' . $row->duration . ' minutes' );
		$start_date_utc = $start_date_utc->format( 'U' );

		if ( strtotime( wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) ) ) > $start_date_utc ) {
			$this->is_past = true;
		}

		$status = bp_zoom_webinar_get_meta( $this->id, 'webinar_status', true );
		if ( 'started' === $status ) {
			$this->is_live = true;
		}

		$this->id                     = (int) $row->id;
		$this->group_id               = (int) $row->group_id;
		$this->activity_id            = (int) $row->activity_id;
		$this->user_id                = (int) $row->user_id;
		$this->title                  = $row->title;
		$this->description            = $row->description;
		$this->host_id                = $row->host_id;
		$this->timezone               = $row->timezone;
		$this->password               = $row->password;
		$this->duration               = $row->duration;
		$this->host_video             = (bool) $row->host_video;
		$this->panelists_video        = (bool) $row->panelists_video;
		$this->meeting_authentication = (bool) $row->meeting_authentication;
		$this->recurring              = (bool) $row->recurring;
		$this->auto_recording         = $row->auto_recording;
		$this->alternative_host_ids   = $row->alternative_host_ids;
		$this->webinar_id             = $row->webinar_id;
		$this->start_date_utc         = $row->start_date_utc;
		$this->hide_sitewide          = $row->hide_sitewide;
		$this->parent                 = $row->parent;
		$this->type                   = (int) $row->type;
		$this->zoom_type              = $row->zoom_type;
		$this->practice_session       = (bool) $row->practice_session;
		$this->on_demand              = (bool) $row->on_demand;
		$this->alert                  = (int) $row->alert;
	}

	/**
	 * Save the webinar item to the database.
	 *
	 * @since 1.0.9
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id                     = apply_filters_ref_array(
			'bp_zoom_webinar_id_before_save',
			array(
				$this->id,
				&$this,
			)
		);
		$this->group_id               = apply_filters_ref_array(
			'bp_zoom_webinar_group_id_before_save',
			array(
				$this->group_id,
				&$this,
			)
		);
		$this->activity_id            = apply_filters_ref_array(
			'bp_zoom_webinar_activity_id_before_save',
			array(
				$this->activity_id,
				&$this,
			)
		);
		$this->user_id                = apply_filters_ref_array(
			'bp_zoom_webinar_user_id_before_save',
			array(
				$this->user_id,
				&$this,
			)
		);
		$this->title                  = apply_filters_ref_array(
			'bp_zoom_webinar_title_before_save',
			array(
				$this->title,
				&$this,
			)
		);
		$this->description            = apply_filters_ref_array(
			'bp_zoom_webinar_description_before_save',
			array(
				$this->description,
				&$this,
			)
		);
		$this->host_id                = apply_filters_ref_array(
			'bp_zoom_webinar_host_id_before_save',
			array(
				$this->host_id,
				&$this,
			)
		);
		$this->timezone               = apply_filters_ref_array(
			'bp_zoom_webinar_timezone_before_save',
			array(
				$this->timezone,
				&$this,
			)
		);
		$this->password               = apply_filters_ref_array(
			'bp_zoom_webinar_password_before_save',
			array(
				$this->password,
				&$this,
			)
		);
		$this->duration               = apply_filters_ref_array(
			'bp_zoom_webinar_duration_before_save',
			array(
				$this->duration,
				&$this,
			)
		);
		$this->host_video             = apply_filters_ref_array(
			'bp_zoom_webinar_host_video_before_save',
			array(
				$this->host_video,
				&$this,
			)
		);
		$this->panelists_video        = apply_filters_ref_array(
			'bp_zoom_webinar_panelists_video_before_save',
			array(
				$this->panelists_video,
				&$this,
			)
		);
		$this->meeting_authentication = apply_filters_ref_array(
			'bp_zoom_webinar_meeting_authentication_before_save',
			array(
				$this->meeting_authentication,
				&$this,
			)
		);
		$this->recurring              = apply_filters_ref_array(
			'bp_zoom_webinar_recurring_before_save',
			array(
				$this->recurring,
				&$this,
			)
		);
		$this->auto_recording         = apply_filters_ref_array(
			'bp_zoom_webinar_auto_recording_before_save',
			array(
				$this->auto_recording,
				&$this,
			)
		);
		$this->alternative_host_ids   = apply_filters_ref_array(
			'bp_zoom_webinar_alternative_host_ids_before_save',
			array(
				$this->alternative_host_ids,
				&$this,
			)
		);
		$this->webinar_id             = apply_filters_ref_array(
			'bp_zoom_webinar_webinar_id_before_save',
			array(
				$this->webinar_id,
				&$this,
			)
		);
		$this->start_date_utc         = apply_filters_ref_array(
			'bp_zoom_webinar_start_date_utc_before_save',
			array(
				$this->start_date_utc,
				&$this,
			)
		);
		$this->hide_sitewide          = apply_filters_ref_array(
			'bp_zoom_webinar_hide_sitewide_before_save',
			array(
				$this->hide_sitewide,
				&$this,
			)
		);
		$this->parent                 = apply_filters_ref_array(
			'bp_zoom_webinar_parent_before_save',
			array(
				$this->parent,
				&$this,
			)
		);
		$this->type                   = apply_filters_ref_array(
			'bp_zoom_webinar_type_before_save',
			array(
				$this->type,
				&$this,
			)
		);
		$this->zoom_type              = apply_filters_ref_array(
			'bp_zoom_webinar_zoom_type_before_save',
			array(
				$this->zoom_type,
				&$this,
			)
		);
		$this->practice_session       = apply_filters_ref_array(
			'bp_zoom_webinar_practice_session_before_save',
			array(
				$this->practice_session,
				&$this,
			)
		);
		$this->on_demand              = apply_filters_ref_array(
			'bp_zoom_webinar_on_demand_before_save',
			array(
				$this->on_demand,
				&$this,
			)
		);
		$this->alert                  = apply_filters_ref_array(
			'bp_zoom_webinar_alert_before_save',
			array(
				$this->alert,
				&$this,
			)
		);

		$this->start_date_utc = mysql_to_rfc3339( $this->start_date_utc ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved

		/**
		 * Fires before the current webinar item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.9
		 *
		 * @param BP_Zoom_Webinar $this Current instance of the webinar item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_webinar_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->host_id ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				$this->errors->add( 'bp_zoom_webinar_missing_host_id' );

				return $this->errors;
			}
		}

		// If we have an existing ID, update the webinar item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_webinars SET group_id = %d, activity_id = %d, user_id = %d, host_id = %s, title = %s, description = %s, timezone = %s, password = %s, duration = %d, host_video = %d, panelists_video = %d, practice_session = %d, on_demand = %d, meeting_authentication = %d, recurring = %d, auto_recording = %s, alternative_host_ids = %s, webinar_id = %s, start_date_utc = %s, hide_sitewide = %d, parent = %s, type = %d, zoom_type = %s, alert = %d WHERE id = %d", $this->group_id, $this->activity_id, $this->user_id, $this->host_id, $this->title, $this->description, $this->timezone, $this->password, $this->duration, $this->host_video, $this->panelists_video, $this->practice_session, $this->on_demand, $this->meeting_authentication, $this->recurring, $this->auto_recording, $this->alternative_host_ids, $this->webinar_id, $this->start_date_utc, $this->hide_sitewide, $this->parent, $this->type, $this->zoom_type, $this->alert, $this->id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->table_prefix}bp_zoom_webinars (group_id, activity_id, user_id, host_id, title, description, timezone, password, duration, host_video, panelists_video, practice_session, on_demand, meeting_authentication, recurring, auto_recording, alternative_host_ids, webinar_id, start_date_utc, hide_sitewide, parent, type, zoom_type, alert ) VALUES (%d, %d, %d, %s, %s, %s, %s, %s, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s, %d, %s, %d, %s, %d )", $this->group_id, $this->activity_id, $this->user_id, $this->host_id, $this->title, $this->description, $this->timezone, $this->password, $this->duration, $this->host_video, $this->panelists_video, $this->practice_session, $this->on_demand, $this->meeting_authentication, $this->recurring, $this->auto_recording, $this->alternative_host_ids, $this->webinar_id, $this->start_date_utc, $this->hide_sitewide, $this->parent, $this->type, $this->zoom_type, $this->alert ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( false === $wpdb->query( $q ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return false;
		}

		// If this is a new webinar item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an webinar item has been saved to the database.
		 *
		 * @since 1.0.9
		 *
		 * @param BP_Zoom_Webinar $this Current instance of webinar item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_webinar_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get webinar items, as specified by parameters.
	 *
	 * @since 1.0.9
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 20.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Media fields to return. Pass 'ids' to get only the media IDs.
	 *                                           'all' returns full media objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of media IDs to exclude. Default: false.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total media items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located medias
	 *               - 'webinars' is an array of the located medias
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'page'          => 1,               // The current page.
				'per_page'      => 20,              // Media items per page.
				'max'           => false,           // Max number of items to return.
				'fields'        => 'all',           // Fields to include.
				'sort'          => 'DESC',          // ASC or DESC.
				'order_by'      => 'start_date_utc',    // Column to order by.
				'exclude'       => false,           // Array of ids to exclude.
				'in'            => false,           // Array of ids to limit query by (IN).
				'meta_query'    => false,           // Filter by webinarmeta.
				'search_terms'  => false,           // Terms to search by.
				'count_total'   => false,           // Whether or not to use count_total.
				'group_id'      => false,           // filter results by group id.
				'webinar_id'    => false,           // filter results by zoom webinar id.
				'activity_id'   => false,           // filter results by zoom activity id.
				'parent'        => false,           // filter results by zoom webinar parent id.
				'user'          => false,           // filter results by site user id.
				'since'         => false,           // return items since date.
				'from'          => false,           // return items from date.
				'recorded'      => false,           // return items which have recordings.
				'recurring'     => false,           // return items which is recurring.
				'hide_sitewide' => false,           // return items which is not hidden.
				'zoom_type'     => false,           // return items with webinar type.
				'live'          => false,           // return items with live webinar status.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->table_prefix}bp_zoom_webinars m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( '( m.title LIKE %s OR m.webinar_id LIKE %s OR m.description LIKE %s )', $search_terms_like, $search_terms_like, $search_terms_like );
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		if ( empty( $r['order_by'] ) ) {
			$r['order_by'] = 'start_date_utc';
		}

		$order_by = 'm.' . $r['order_by'];

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['webinar_id'] ) ) {
			$where_conditions['webinar'] = "m.webinar_id = '{$r['webinar_id']}'";
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_conditions['activity'] = "m.activity_id = {$r['activity_id']}";
		}

		if ( ! empty( $r['parent'] ) ) {
			$where_conditions['parent'] = "m.parent = '{$r['parent']}'";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "m.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['host_id'] ) ) {
			$where_conditions['host'] = "m.host_id = '{$r['host_id']}'";
		}

		// Hidden sitewide.
		if ( isset( $r['hide_sitewide'] ) ) {
			$hide_sitewide                     = (int) $r['hide_sitewide'];
			$where_conditions['hide_sitewide'] = "m.hide_sitewide = {$hide_sitewide}";
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "m.id NOT IN ({$exclude})";
		}

		// Filter by zoom webinar types.
		if ( ! empty( $r['zoom_type'] ) ) {
			if ( is_array( $r['zoom_type'] ) ) {
				$zoom_type                     = "'" . implode( "', '", wp_parse_slug_list( $r['zoom_type'] ) ) . "'";
				$where_conditions['zoom_type'] = "m.zoom_type IN ({$zoom_type})";
			} else {
				$where_conditions['zoom_type'] = "m.zoom_type = '{$r['zoom_type']}'";
			}
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "m.id IN ({$in})";

			// we want to disable limit query when include media ids.
			$r['page']     = false;
			$r['per_page'] = false;
		}

		if ( ! empty( $r['since'] ) && ! empty( $r['from'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_since_date = wp_date( 'Y-m-d H:i:s', strtotime( $r['since'] ), new DateTimeZone( 'UTC' ) );
			$translated_from_date  = wp_date( 'Y-m-d H:i:s', strtotime( $r['from'] ), new DateTimeZone( 'UTC' ) );
			if ( $translated_since_date === $r['since'] && $translated_from_date === $r['from'] ) {
				$where_conditions['date_filter'] = "( m.start_date_utc BETWEEN '{$translated_from_date}' AND '{$translated_since_date}' )";
			}
			$order_by = false;
		} elseif ( ! empty( $r['since'] ) && empty( $r['in'] ) && empty( $r['webinar_id'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = wp_date( 'Y-m-d H:i:s', strtotime( $r['since'] ), new DateTimeZone( 'UTC' ) );
			if ( $translated_date === $r['since'] ) {
				$where_conditions['date_filter'] = "DATE_ADD( m.start_date_utc, INTERVAL m.duration MINUTE ) > '{$translated_date}'";
			}
		} elseif ( ! empty( $r['from'] ) && empty( $r['in'] ) && empty( $r['webinar_id'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = wp_date( 'Y-m-d H:i:s', strtotime( $r['from'] ), new DateTimeZone( 'UTC' ) );
			if ( $translated_date === $r['from'] ) {
				$where_conditions['date_filter'] = "DATE_ADD( m.start_date_utc, INTERVAL m.duration MINUTE ) < '{$translated_date}'";

				// Past webinars doesnot include live webinars.
				$meta_not_live_query = array(
					'relation' => 'OR',
					array(
						'key'     => 'webinar_status',
						'value'   => 'started',
						'compare' => '!=',
					),
					array(
						'key'     => 'webinar_status',
						'compare' => 'NOT EXISTS',
					),
				);

				if ( empty( $r['meta_query'] ) ) {
					$r['meta_query'] = $meta_not_live_query;
				} else {
					$r['meta_query'] = array( $r['meta_query'], $meta_not_live_query );
				}
			}
		}

		if ( $r['recurring'] ) {
			$where_conditions['recurring'] = 'm.type = 8';
		}

		if ( $r['recorded'] ) {
			$meta_recorded_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'zoom_recording_count',
					'value'   => '0',
					'compare' => '>',
				),
			);
			if ( empty( $r['meta_query'] ) ) {
				$r['meta_query'] = $meta_recorded_query;
			} else {
				$r['meta_query'] = array( $r['meta_query'], $meta_recorded_query );
			}
		}

		if ( $r['live'] ) {
			if ( isset( $where_conditions['date_filter'] ) ) {
				unset( $where_conditions['date_filter'] );
			}
			$meta_live_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'webinar_status',
					'value'   => 'started',
					'compare' => '=',
				),
			);
			if ( empty( $r['meta_query'] ) ) {
				$r['meta_query'] = $meta_live_query;
			} else {
				$r['meta_query'] = array( $r['meta_query'], $meta_live_query );
			}
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the Webinar items get method.
		 *
		 * @since 1.0.9
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_zoom_webinar_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		if ( ! empty( $scope_query['sql'] ) ) {
			$where_sql = 'WHERE ( ' . join( ' AND ', $where_conditions ) . ' ) OR ( ' . $scope_query['sql'] . ' )';
		} else {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the main webinar query.
		 *
		 * @since 1.0.9
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_zoom_webinar_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'webinars'       => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for media IDs.
		$webinar_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql}";

		if ( ! empty( $order_by ) ) {
			$webinar_ids_sql .= ' ORDER BY ' . $order_by . ' ' . $sort;
		}

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$webinar_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged webinar MySQL statement.
		 *
		 * @since 1.0.9
		 *
		 * @param string $webinar_ids_sql    MySQL statement used to query for Webinar IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$webinar_ids_sql = apply_filters( 'bp_zoom_webinar_paged_webinars_sql', $webinar_ids_sql, $r );

		$cache_group = 'bp_webinar';

		$cached = bp_core_get_incremented_cache( $webinar_ids_sql, $cache_group );
		if ( false === $cached ) {
			$webinar_ids = $wpdb->get_col( $webinar_ids_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			bp_core_set_incremented_cache( $webinar_ids_sql, $cache_group, $webinar_ids );
		} else {
			$webinar_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $webinar_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $webinar_ids ) === $per_page + 1 ) {
			array_pop( $webinar_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$webinars = array_map( 'intval', $webinar_ids );
		} else {
			$webinars = self::get_webinar_data( $webinar_ids );
		}

		$retval['webinars'] = $webinars;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total webinar MySQL statement.
			 *
			 * @since 1.0.9
			 *
			 * @param string $value     MySQL statement used to query for total webinars.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_webinars_sql = apply_filters( 'bp_zoom_webinar_total_medias_sql', "SELECT count(DISTINCT m.id) FROM {$bp->table_prefix}bp_zoom_webinars m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached             = bp_core_get_incremented_cache( $total_webinars_sql, $cache_group );
			if ( false === $cached ) {
				$total_webinars = $wpdb->get_var( $total_webinars_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				bp_core_set_incremented_cache( $total_webinars_sql, $cache_group, $total_webinars );
			} else {
				$total_webinars = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_webinars > (int) $r['max'] ) {
					$total_webinars = $r['max'];
				}
			}

			$retval['total'] = $total_webinars;
		}

		return $retval;
	}

	/**
	 * Convert media IDs to webinar objects, as expected in template loop.
	 *
	 * @since 1.0.9
	 *
	 * @param array $webinar_ids Array of webinar IDs.
	 * @return array
	 */
	protected static function get_webinar_data( $webinar_ids = array() ) {
		global $wpdb;

		// Bail if no webinar ID's passed.
		if ( empty( $webinar_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$webinars     = array();
		$uncached_ids = bp_get_non_cached_ids( $webinar_ids, 'bp_webinar' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the webinar ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from webinar table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_webinars WHERE id IN ({$uncached_ids_sql})" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_webinar' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $webinar_ids as $webinar_id ) {
			// Integer casting.
			$webinar = wp_cache_get( $webinar_id, 'bp_webinar' );
			if ( ! empty( $webinar ) ) {

				$start_date_utc = new DateTime( $webinar->start_date_utc, new DateTimeZone( 'UTC' ) );
				$start_date_utc->modify( '+' . $webinar->duration . ' minutes' );
				$start_date_utc = $start_date_utc->format( 'U' );

				$webinar_is_past = false;
				if ( strtotime( wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) ) ) > $start_date_utc ) {
					$webinar_is_past = true;
				}

				$webinar->id                     = (int) $webinar->id;
				$webinar->group_id               = (int) $webinar->group_id;
				$webinar->activity_id            = (int) $webinar->activity_id;
				$webinar->user_id                = (int) $webinar->user_id;
				$webinar->duration               = (int) $webinar->duration;
				$webinar->host_video             = (bool) $webinar->host_video;
				$webinar->panelists_video        = (bool) $webinar->panelists_video;
				$webinar->practice_session       = (bool) $webinar->practice_session;
				$webinar->on_demand              = (bool) $webinar->on_demand;
				$webinar->meeting_authentication = (bool) $webinar->meeting_authentication;
				$webinar->recurring              = (bool) $webinar->recurring;
				$webinar->is_past                = (bool) $webinar_is_past;
				$webinar->alert                  = (int) $webinar->alert;

				$webinar->join_url     = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_join_url', true );
				$webinar->start_url    = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_start_url', true );
				$webinar->zoom_details = bp_zoom_webinar_get_meta( $webinar_id, 'zoom_details', true );
			}

			$webinars[] = $webinar;
		}

		return $webinars;
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Zoom_Webinar::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Zoom_Webinar::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since 1.0.9
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$webinar_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->webinarmeta.
			$wpdb->webinarmeta = bp_core_get_table_prefix() . 'bp_zoom_webinar_meta';

			$meta_sql = $webinar_meta_query->get_sql( 'webinar', 'm', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

	/**
	 * Delete webinar items from the database.
	 *
	 * To delete a specific webinar item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @param array $args {
	 *                    Arguments.
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $webinar_id           Optional. The webinar ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * }
	 *
	 * @return array|bool An array of deleted webinar IDs on success, false on failure.
	 * @since 1.0.9
	 */
	public static function delete( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'id'          => false,
				'webinar_id'  => false,
				'group_id'    => false,
				'activity_id' => false,
				'user_id'     => false,
				'parent'      => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// webinar ID.
		if ( ! empty( $r['webinar_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'webinar_id = %s', $r['webinar_id'] );
		}

		// group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
		}

		// activity ID.
		if ( ! empty( $r['activity_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'activity_id = %d', $r['activity_id'] );
		}

		// site user ID.
		if ( ! empty( $r['user_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		// parent webinar.
		if ( ! empty( $r['parent'] ) ) {
			$where_args[] = $wpdb->prepare( 'parent = %s', $r['parent'] );
		}

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all webinar being deleted so we can perform more actions.
		$webinars = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_webinars {$where_sql}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Action to allow intercepting webinar items to be deleted.
		 *
		 * @param array $webinars Array of webinar.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since 1.0.9
		 */
		do_action_ref_array( 'bp_zoom_webinar_before_delete', array( $webinars, $r ) );

		// Attempt to delete webinar from the database.
		$deleted = $wpdb->query( "DELETE FROM {$bp->table_prefix}bp_zoom_webinars {$where_sql}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting webinar items just deleted.
		 *
		 * @param array $webinars Array of webinar.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since 1.0.9
		 */
		do_action_ref_array( 'bp_zoom_webinar_after_delete', array( $webinars, $r ) );

		// Pluck the webinar IDs out of the $webinars array.
		$webinar_ids = wp_parse_id_list( wp_list_pluck( $webinars, 'id' ) );

		return $webinar_ids;
	}

	/**
	 * Get webinar by zoom webinar id.
	 *
	 * @param int $webinar_id Webinar ID.
	 * @param int $parent     Parent Webinar ID.
	 *
	 * @return array|object|void|null|bool
	 * @since 1.0.9
	 */
	public static function get_webinar_by_webinar_id( $webinar_id = 0, $parent = 0 ) {
		global $wpdb, $bp;

		if ( empty( $webinar_id ) ) {
			return false;
		}

		if ( 0 === $parent ) {
			$parent = '';
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_zoom_webinars WHERE webinar_id = %s AND parent = %s", $webinar_id, $parent ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}
}
