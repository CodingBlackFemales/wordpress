<?php
/**
 * BuddyBoss Pro Polls.
 *
 * @since   2.6.00
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp poll class.
 *
 * @since 2.6.00
 */
class BB_Polls {

	/**
	 * Class instance.
	 *
	 * @since  2.6.00
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Unique ID for the poll.
	 *
	 * @since 2.6.00
	 * @var string poll.
	 */
	public $id = 'polls';

	/**
	 * Poll table name.
	 *
	 * @since  2.6.00
	 * @access public
	 * @var string
	 */
	public static $poll_table = '';

	/**
	 * Poll options table name.
	 *
	 * @since  2.6.00
	 * @access public
	 * @var string
	 */
	public static $poll_options_table = '';

	/**
	 * Poll votes table name.
	 *
	 * @since  2.6.00
	 * @access public
	 * @var string
	 */
	public static $poll_votes_table = '';

	/**
	 * Poll types.
	 *
	 * @since 2.6.00
	 * @var array
	 */
	private $registered_poll_types = array();

	/**
	 * Cache group for poll options.
	 *
	 * @since  2.6.00
	 * @access public
	 * @var string
	 */
	public static $po_cache_group = 'bb_poll_options';

	/**
	 * Cache group for poll votes.
	 *
	 * @since  2.6.00
	 * @access public
	 * @var string
	 */
	public static $pv_cache_group = 'bb_poll_votes';

	/**
	 * Polls Constructor.
	 *
	 * @since 2.6.00
	 */
	public function __construct() {
		$bp_prefix = bp_core_get_table_prefix();
		// Poll table.
		self::$poll_table = $bp_prefix . 'bb_polls';
		// Poll options table.
		self::$poll_options_table = $bp_prefix . 'bb_poll_options';
		// Poll votes table.
		self::$poll_votes_table = $bp_prefix . 'bb_poll_votes';

		if ( get_option( 'bb_polls_table_create_on_activation' ) ) {
			delete_option( 'bb_polls_table_create_on_activation' );
			$this->create_table();
		}

		// Register a poll activity item type.
		$this->bb_register_poll_type(
			'activity',
			array(
				'validate_callback' => array( $this, 'bb_validate_poll_activity_request' ),
			)
		);

		// Include the code.
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Get the instance of the class.
	 *
	 * @since 2.6.00
	 *
	 * @return BB_Polls
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Create the table.
	 *
	 * @since 2.6.00
	 * @return void
	 */
	public function create_table() {
		$sql             = array();
		$wpdb            = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();

		$poll_table_name = self::$poll_table;
		$poll_has_table  = $wpdb->query( $wpdb->prepare( 'show tables like %s', $poll_table_name ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$poll_options_table_name = self::$poll_options_table;
		$poll_options_has_table  = $wpdb->query( $wpdb->prepare( 'show tables like %s', $poll_options_table_name ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$poll_votes_table_name = self::$poll_votes_table;
		$poll_votes_has_table  = $wpdb->query( $wpdb->prepare( 'show tables like %s', $poll_votes_table_name ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $poll_has_table ) ) {
			$sql[] = "CREATE TABLE $poll_table_name (
		                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		                item_id bigint(20) DEFAULT 0,
		                item_type varchar(20) NOT NULL,
		                secondary_item_id bigint(20) DEFAULT 0,
		                user_id bigint(20) NOT NULL,
		                question varchar(150) NOT NULL,
		                settings longtext NULL,
		                date_recorded datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		                date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		                vote_disabled_date datetime NULL,
		                status varchar(20) DEFAULT 'draft' NOT NULL,
		                KEY item_id (item_id),
		                KEY item_type (item_type),
		                KEY secondary_item_id (secondary_item_id),
		                KEY user_id (user_id),
		                KEY date_recorded (date_recorded),
		                KEY date_updated (date_updated),
		                KEY status (status)
            	) $charset_collate";
		}

		if ( empty( $poll_options_has_table ) ) {
			$sql[] = "CREATE TABLE $poll_options_table_name (
		                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		                poll_id bigint(20) NOT NULL,
		                user_id bigint(20) NOT NULL,
		                option_title varchar(150) NOT NULL,
		                option_order bigint(2) NULL,
		                date_recorded datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		                date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		                KEY poll_id (poll_id),
		                KEY user_id (user_id),
		                KEY option_title (option_title),
		                KEY option_order (option_order),
		                KEY date_recorded (date_recorded),
		                KEY date_updated (date_updated)
            		) $charset_collate";
		}

		if ( empty( $poll_votes_has_table ) ) {
			$sql[] = "CREATE TABLE $poll_votes_table_name (
			            id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			            poll_id bigint(20) NOT NULL,
			            option_id bigint(20) NOT NULL,
			            user_id bigint(20) NOT NULL,
			            date_recorded datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			            KEY poll_id (poll_id),
			            KEY option_id (option_id),
			            KEY user_id (user_id),
			            KEY date_recorded (date_recorded)
		            ) $charset_collate";
		}

		if ( ! empty( $sql ) ) {
			// Ensure that dbDelta() is defined.
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $sql );

			// Check if the cron job is not already scheduled.
			$is_scheduled = wp_next_scheduled( 'bb_daily_draft_cleanup_event' );

			// WP datetime.
			$final_date         = date_i18n( 'Y-m-d H:i:s', strtotime( 'today 23:30' ) );
			$local_datetime     = date_create( $final_date, wp_timezone() );
			$schedule_timestamp = $local_datetime->getTimestamp();

			if ( ! $is_scheduled ) {
				wp_schedule_event( $schedule_timestamp, 'daily', 'bb_daily_draft_cleanup_event' );
			}
		}
	}

	/**
	 * Setup actions for poll.
	 *
	 * @since 2.6.00
	 */
	public function setup_actions() {
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_script' ) );

		// Add the JS templates for polls.
		add_filter( 'bp_messages_js_template_parts', array( $this, 'bb_add_polls_js_templates' ) );

		// Register the template for polls.
		bp_register_template_stack( array( $this, 'bb_register_polls_template' ) );

		add_action( 'bp_setup_cache_groups', array( $this, 'setup_cache_groups' ) );

		add_action( 'bb_daily_draft_cleanup_event', array( $this, 'bb_clear_old_draft_polls' ) );

		add_action( 'bp_rest_api_init', array( $this, 'bb_rest_api_init' ) );
	}

	/**
	 * Enqueue related scripts and styles.
	 *
	 * @since 2.6.00
	 */
	public function enqueue_script() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';

		wp_enqueue_script(
			'bb-poll-script',
			bb_polls_url( '/assets/js/bb-poll' . $min . '.js' ),
			array(
				'bp-nouveau',
				'wp-util',
				'wp-backbone',
				'jquery',
				'jquery-ui-sortable',
			),
			bb_platform_pro()->version,
			true
		);
		$css_prefix =
			function_exists( 'bb_is_readylaunch_enabled' ) &&
			bb_is_readylaunch_enabled() &&
			class_exists( 'BB_Readylaunch' ) &&
			bb_load_readylaunch()->bb_is_readylaunch_enabled_for_page()
			? 'bb-rl-' : 'bb-';
		wp_enqueue_style( 'bb-polls-style', bb_polls_url( '/assets/css/' . $css_prefix . 'polls' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );

		wp_localize_script(
			'bb-poll-script',
			'bbPollsVars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => array(
					'add_poll_nonce'           => wp_create_nonce( 'bb_add_poll_nonce' ),
					'add_poll_option_nonce'    => wp_create_nonce( 'bb_add_poll_option_nonce' ),
					'remove_poll_option_nonce' => wp_create_nonce( 'bb_remove_poll_option_nonce' ),
					'add_poll_vote_nonce'      => wp_create_nonce( 'bb_add_poll_vote_nonce' ),
					'poll_vote_state_nonce'    => wp_create_nonce( 'bb_poll_vote_state_nonce' ),
					'remove_poll_nonce'        => wp_create_nonce( 'bb_remove_poll_nonce' ),
				),
			)
		);
	}

	/**
	 * Includes files.
	 *
	 * @since 2.6.00
	 *
	 * @param array $includes list of the files.
	 */
	public function includes( $includes = array() ) {

		$bb_platform_pro = bb_platform_pro();
		$slashed_path    = trailingslashit( $bb_platform_pro->polls_dir );

		$includes = array(
			'cache',
			'functions',
			'filters',
			'actions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			if ( empty( $this->bb_poll_check_has_licence() ) ) {
				if ( in_array( $file, array( 'filters', 'rest-filters' ), true ) ) {
					continue;
				}
			}

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with an extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( @is_file( $slashed_path . $path ) ) {
					require $slashed_path . $path;
					break;
				}
			}

			unset( $paths );
		}
		unset( $includes );
	}

	/**
	 * Function to return the default value if no licence.
	 *
	 * @since 2.6.00
	 *
	 * @param bool $has_access Whether it has access.
	 *
	 * @return bool Return the default.
	 */
	protected function bb_poll_check_has_licence( $has_access = true ) {

		if ( bb_pro_should_lock_features() ) {
			return false;
		}

		return $has_access;

	}

	/**
	 * Register a template path for Polls.
	 *
	 * @since 2.6.00
	 *
	 * @return string Template path.
	 */
	public function bb_register_polls_template() {
		return bb_polls_path( '/templates' );
	}

	/**
	 * Add Js template path for Polls.
	 *
	 * @since 2.6.00
	 *
	 * @param array $templates Array of template paths to filter.
	 *
	 * @return array Array of template paths.
	 */
	public function bb_add_polls_js_templates( $templates ) {

		$templates[] = 'parts/bb-activity-poll-form';
		$templates[] = 'parts/bb-activity-poll-view';
		$templates[] = 'parts/bb-activity-poll-state';
		$templates[] = 'parts/bb-activity-poll-entry';

		return $templates;
	}

	/**
	 * Function to clear old draft polls.
	 *
	 * @since 2.6.00
	 */
	public function bb_clear_old_draft_polls() {
		global $wpdb;

		$current_time = current_time( 'mysql', 1 );

		// Calculated the timestamp for 6 hours ago based on the current time in UTC.
		$six_hours_ago      = strtotime( $current_time ) - ( 6 * HOUR_IN_SECONDS );
		$six_hours_ago_date = gmdate( 'Y-m-d H:i:s', $six_hours_ago );

		// Get poll IDs that are older than 6 hours.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$get_poll = $wpdb->get_col(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SELECT id FROM ' . self::$poll_table . ' WHERE status=%s AND date_recorded < %s',
				'draft',
				$six_hours_ago_date
			)
		);

		if ( ! empty( $get_poll ) ) {
			// Parse poll IDs into an array of integers.
			$poll_ids = wp_parse_id_list( $get_poll );

			// Query for poll deletion.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'DELETE FROM ' . self::$poll_table . ' WHERE id IN (' . implode( ', ', array_fill( 0, count( $poll_ids ), '%d' ) ) . ')',
					...$poll_ids
				)
			);

			// Query for poll options deletion.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'DELETE FROM ' . self::$poll_options_table . ' WHERE poll_id IN (' . implode( ', ', array_fill( 0, count( $poll_ids ), '%d' ) ) . ')',
					...$poll_ids
				)
			);

			if ( function_exists( 'wp_cache_flush_group' ) ) {
				wp_cache_flush_group( 'bb_poll' );
				wp_cache_flush_group( 'bb_poll_options' );
				wp_cache_flush_group( 'bb_poll_votes' );
			} else {
				wp_cache_flush();
			}
		}

		unset( $get_poll, $poll_ids );
	}

	/**
	 * Setup cache.
	 *
	 * @since 2.6.00
	 */
	public function setup_cache_groups() {
		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bb_polls',
				'bb_poll_options',
				'bb_poll_votes',
			)
		);
	}

	/**
	 * Register a poll type.
	 *
	 * @since 2.6.00
	 *
	 * @param string $type Item Type.
	 * @param array  $args Array of arguments.
	 *
	 * @return void
	 */
	public function bb_register_poll_type( $type, $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'poll_type'         => $type,
				'validate_callback' => '',
			)
		);

		if (
			empty( $r['poll_type'] ) ||
			empty( $r['validate_callback'] ) ||
			isset( $this->registered_poll_types[ $r['poll_type'] ] ) ||
			! preg_match( '/^[a-zA-Z0-9_-]+$/', $r['poll_type'] )
		) {
			return;
		}

		$this->registered_poll_types[ $r['poll_type'] ] = array(
			'poll_type'         => $r['poll_type'],
			'validate_callback' => $r['validate_callback'],
		);

		unset( $r );
	}

	/**
	 * Validate callback for a poll item type.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|WP_Error|array
	 */
	public function bb_validate_poll_activity_request( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'item_type' => '',
				'item_id'   => '',
			)
		);

		$valid_item_ids = array();

		if (
			! bp_is_active( 'activity' ) ||
			(
				bp_is_active( 'group' ) &&
				! bb_is_enabled_activity_post_polls( false )
			)
		) {
			unset( $r );

			return $valid_item_ids;
		}

		$activities_ids = array();
		if ( ! empty( $r['item_id'] ) && 'activity' === $r['item_type'] ) {
			$activities = BP_Activity_Activity::get(
				array(
					'per_page'    => 0,
					'fields'      => 'ids',
					'show_hidden' => true, // Support hide_sitewide as true like document activity.
					'in'          => ! is_array( $r['item_id'] ) ? array( $r['item_id'] ) : $r['item_id'],
				),
			);

			if ( ! empty( $activities['activities'] ) ) {
				$activities_ids = $activities['activities'];
			}
		}

		unset( $r );

		return $activities_ids;
	}

	/**
	 * Get registered poll item types.
	 *
	 * @since 2.6.00
	 *
	 * @return mixed|null
	 */
	public function bb_get_registered_poll_item_types() {
		return apply_filters( 'bb_registered_poll_types', $this->registered_poll_types );
	}

	/**
	 * Function to get the poll data.
	 *
	 * @since 2.6.00
	 *
	 * @param int $id Poll id. Default is 0.
	 *
	 * @return object|null
	 */
	public function bb_get_poll( $id = 0 ) {
		if ( empty( $id ) ) {
			return null;
		}

		global $wpdb;

		$poll_data = wp_cache_get( $id, 'bb_poll' );

		if ( false === $poll_data ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$poll_data = $wpdb->get_row(
				$wpdb->prepare(
				// phpcs:ignore
					'SELECT * FROM ' . self::$poll_table . ' WHERE id = %d',
					$id
				)
			);

			wp_cache_set( $id, $poll_data, 'bb_poll' );
		}

		if ( ! empty( $poll_data ) ) {
			$poll_data->id                = (int) $poll_data->id;
			$poll_data->item_id           = (int) $poll_data->item_id;
			$poll_data->secondary_item_id = (int) $poll_data->secondary_item_id;
			$poll_data->user_id           = (int) $poll_data->user_id;
			$settings                     = maybe_unserialize( $poll_data->settings );
			$poll_data->settings          = array(
				'allow_multiple_options' => (bool) $settings['allow_multiple_options'],
				'allow_new_option'       => (bool) $settings['allow_new_option'],
				'duration'               => (int) $settings['duration'],
			);
		}

		return $poll_data;
	}

	/**
	 * Function to update the poll data.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments.
	 *
	 * @type int    $id                Poll id.
	 * @type string $question          Poll question.
	 * @type int    $item_id           Item id.
	 * @type string $item_type         Item type.
	 * @type int    $secondary_item_id Secondary item id.
	 * @type int    $user_id           User id.
	 * @type array  $settings          Poll settings.
	 * @type string $date_recorded     Date recorded.
	 * @type string $date_updated      Date updated.
	 * @type string $status            Status.
	 * @type string $error_type        Error type.
	 * }
	 * @return object|false|WP_Error
	 */
	public function bb_update_poll( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'                => 0,
				'question'          => '',
				'item_id'           => 0,
				'item_type'         => '',
				'secondary_item_id' => 0,
				'user_id'           => bp_loggedin_user_id(),
				'settings'          => array(),
				'date_recorded'     => bp_core_current_time(),
				'date_updated'      => bp_core_current_time(),
				'status'            => 'draft',
				'error_type'        => 'bool',
			)
		);

		// Poll need question.
		if ( empty( $r['item_id'] ) && empty( $r['question'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_empty_question', __( 'The question is required to add poll.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		}

		if ( ! empty( $r['item_id'] ) ) {
			$all_registered_poll_types = $this->bb_get_registered_poll_item_types();

			if (
				empty( $all_registered_poll_types ) ||
				! isset( $all_registered_poll_types[ $r['item_type'] ] ) ||
				empty( $all_registered_poll_types[ $r['item_type'] ]['validate_callback'] ) ||
				! is_callable( $all_registered_poll_types[ $r['item_type'] ]['validate_callback'] )
			) {
				if ( 'wp_error' === $r['error_type'] ) {
					unset( $r, $all_registered_poll_types );

					return new WP_Error(
						'bb_poll_invalid_item_type',
						__( 'The item type is invalid.', 'buddyboss-pro' )
					);
				}
				unset( $r, $all_registered_poll_types );

				return false;
			} else {
				$validate_callback = $all_registered_poll_types[ $r['item_type'] ]['validate_callback'];
				$validate_callback = call_user_func( $validate_callback, $r );
				if ( empty( $validate_callback ) ) {
					$r['item_id'] = 0;
				} else {
					$r['item_id'] = current( $validate_callback );
				}
			}
		}

		$get_poll = ! empty( $r['id'] ) ? $this->bb_get_poll( (int) $r['id'] ) : '';

		if ( $get_poll ) {
			$sql = $wpdb->prepare(
			// phpcs:ignore
				'UPDATE ' . self::$poll_table . ' SET
				item_id = %d,
				item_type = %s,
				secondary_item_id = %d,
				user_id = %d,
				question = %s,
				settings = %s,
				date_updated = %s,
				vote_disabled_date = %s,
				status = %s
				WHERE
				id = %d
				',
				! empty( $r['item_id'] ) ? $r['item_id'] : $get_poll->item_id,
				! empty( $r['item_type'] ) ? $r['item_type'] : $get_poll->item_type,
				! empty( $r['secondary_item_id'] ) ? $r['secondary_item_id'] : $get_poll->secondary_item_id,
				! empty( $r['user_id'] ) ? $r['user_id'] : $get_poll->user_id,
				! empty( $r['question'] ) ? $r['question'] : $get_poll->question,
				! empty( $r['settings'] ) ? maybe_serialize( $r['settings'] ) : maybe_serialize( $get_poll->settings ),
				! empty( $r['date_updated'] ) ? $r['date_updated'] : $get_poll->date_updated,
				! empty( $r['vote_disabled_date'] ) ? $r['vote_disabled_date'] : $get_poll->vote_disabled_date,
				! empty( $r['status'] ) ? $r['status'] : $get_poll->status,
				$get_poll->id
			);
		} else {
			$sql = $wpdb->prepare(
				// phpcs:ignore
				'INSERT INTO ' . self::$poll_table . ' (
						item_id,
						item_type,
						secondary_item_id,
						user_id,
						question,
						settings,
						date_recorded,
						date_updated,
						vote_disabled_date,
						status
					) VALUES (
						%d, %s, %d, %d, %s, %s, %s, %s, %s, %s
					)',
				(int) $r['item_id'],
				$r['item_type'],
				(int) $r['secondary_item_id'],
				(int) $r['user_id'],
				$r['question'],
				maybe_serialize( $r['settings'] ),
				$r['date_recorded'],
				$r['date_updated'],
				'',
				$r['status']
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false === $wpdb->query( $sql ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $get_poll, $sql );

				return new WP_Error( 'bb_poll_cannot_add', __( 'There is an error while adding the poll.', 'buddyboss-pro' ) );
			} else {
				unset( $r, $get_poll, $sql );

				return false;
			}
		}

		$poll_id = $wpdb->insert_id;
		if ( $get_poll ) {
			$poll_id = $get_poll->id;
		}
		unset( $get_poll, $sql );

		/**
		 * Fires after the added poll.
		 *
		 * @since 2.6.00
		 *
		 * @param int   $poll_id Poll id.
		 * @param array $r       Array of parsed arguments.
		 */
		do_action( 'bb_poll_after_add_poll', $poll_id, $r );

		unset( $r );

		return $this->bb_get_poll( $poll_id );
	}

	/**
	 * Function to remove the poll.
	 *
	 * @since 2.6.00
	 *
	 * @param int $id Poll id.
	 *
	 * @return bool
	 */
	public function bb_remove_poll( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return false;
		}

		/**
		 * Fires before the remove poll.
		 *
		 * @since 2.6.00
		 *
		 * @param int $id Poll id.
		 */
		do_action( 'bb_poll_before_remove_poll', $id );

		// Fetch a poll being deleted, so we can perform more actions.
		// phpcs:ignore
		$get_poll = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . self::$poll_table . ' WHERE id = %d', $id ) );

		// Attempt to delete poll from the database.
		$deleted = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::$poll_table . ' WHERE id = %d', $id ) ); // phpcs:ignore

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Fires after the remove poll.
		 *
		 * @since 2.6.00
		 *
		 * @param int|false $deleted  The number of rows deleted, or false on error.
		 * @param object    $get_poll Poll data.
		 */
		do_action( 'bb_poll_after_remove_poll', $deleted, $get_poll );

		$this->bb_remove_poll_options( array( 'poll_id' => $id ) );

		unset( $get_poll );

		return $deleted;
	}

	/**
	 * Function to get the poll options.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments. All items are optional.
	 *
	 * @type int    $id           Poll option id.
	 * @type int    $poll_id      Poll id.
	 * @type string $option_title Option title.
	 * @type string $order_by     Order By.
	 * @type string $order        Order of the poll options. Default is 'ASC'.
	 * @type string $fields       Fields to include. Default is 'all'. Possible values are:
	 * }
	 * @return false|array|WP_Error
	 */
	public function bb_get_poll_options( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'           => 0,
				'poll_id'      => 0,
				'option_title' => '',
				'order_by'     => 'id',
				'order'        => 'ASC',
				'fields'       => 'all',  // Fields to include.
				'error_type'   => 'bool',
			)
		);

		// Option need poll id.
		if ( empty( $r['poll_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_option_empty_poll_id', __( 'The Poll ID is required to get poll option.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		}

		// Select conditions.
		$select_sql = 'SELECT po.id';

		$from_sql = ' FROM ' . self::$poll_options_table . ' po';

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Sorting.
		$sort = bp_esc_sql_order( $r['order'] );
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		$order_by = 'po.' . $r['order_by'];

		// poll_id.
		$where_conditions[] = $wpdb->prepare( 'po.poll_id = %d', $r['poll_id'] );

		// id.
		$id_condition = '';
		if ( ! empty( $r['id'] ) ) {
			$id_condition = $wpdb->prepare( 'po.id = %d', $r['id'] );
		}

		// option_title.
		$option_title_condition = '';
		if ( ! empty( $r['option_title'] ) ) {
			$option_title_condition = $wpdb->prepare( 'po.option_title = %s', $r['option_title'] );
		}

		// Combine id and option_title conditions using OR.
		$optional_conditions = array();
		if ( ! empty( $id_condition ) ) {
			$optional_conditions[] = $id_condition;
		}
		if ( ! empty( $option_title_condition ) ) {
			$optional_conditions[] = $option_title_condition;
		}
		if ( ! empty( $optional_conditions ) ) {
			$where_conditions[] = '(' . join( ' OR ', $optional_conditions ) . ')';
		}

		/**
		 * Filters the MySQL WHERE conditions for the poll option get sql method.
		 *
		 * @since 2.6.00
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bb_get_poll_options_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		$where_sql = '';
		if ( ! empty( $where_conditions ) ) {
			// Join the where conditions together.
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the poll option query.
		 *
		 * @since 2.6.00
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bb_get_poll_options_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Query first for poll IDs.
		$poll_options_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}";

		/**
		 * Filters the poll options data MySQL statement.
		 *
		 * @since 2.6.00
		 *
		 * @param string $poll_options_sql MySQL's statement used to query for poll options.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$poll_options_sql = apply_filters( 'bb_get_poll_options_sql', $poll_options_sql, $r );

		$cached = bp_core_get_incremented_cache( $poll_options_sql, self::$po_cache_group );
		if ( false === $cached ) {
			$poll_option_ids = $wpdb->get_col( $poll_options_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $poll_options_sql, self::$po_cache_group, $poll_option_ids );
		} else {
			$poll_option_ids = $cached;
		}

		if ( 'id' === $r['fields'] ) {
			// We only want the IDs.
			$poll_option_data = array_map( 'intval', $poll_option_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $poll_option_ids, self::$po_cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				// phpcs:ignore
				$queried_data = $wpdb->get_results( 'SELECT * FROM ' . self::$poll_options_table . " WHERE id IN ({$uncached_ids_sql}) ORDER BY option_order ASC", ARRAY_A );

				foreach ( (array) $queried_data as $podata ) {
					wp_cache_set( $podata['id'], $podata, self::$po_cache_group );
				}
			}

			$poll_option_data = array();
			foreach ( $poll_option_ids as $id ) {
				$poll_options = wp_cache_get( $id, self::$po_cache_group );
				if ( ! empty( $poll_options ) ) {
					$poll_options['id']           = (int) $poll_options['id'];
					$poll_options['poll_id']      = (int) $poll_options['poll_id'];
					$poll_options['user_id']      = (int) $poll_options['user_id'];
					$poll_options['option_order'] = (int) $poll_options['option_order'];
					$poll_options['total_votes']  = $this->bb_get_poll_option_vote_count(
						array(
							'poll_id'   => $poll_options['poll_id'],
							'option_id' => $poll_options['id'],
						)
					);
					$poll_options['user_data']    = array(
						'username'    => bp_core_get_user_displayname( $poll_options['user_id'] ),
						'user_domain' => bp_core_get_user_domain( $poll_options['user_id'] ),
					);
					// Check if the user has voted for this option.
					$get_poll_vote               = bb_load_polls()->bb_get_poll_votes(
						array(
							'poll_id'   => $poll_options['poll_id'],
							'option_id' => $poll_options['id'],
							'user_id'   => bp_loggedin_user_id(),
							'fields'    => 'id',
						)
					);
					$vote                        = ! empty( $get_poll_vote['poll_votes'] ) ? current( $get_poll_vote['poll_votes'] ) : 0;
					$poll_options['is_selected'] = ! empty( $vote );
					$poll_options['vote_id']     = $vote;
					$poll_option_data[]          = $poll_options;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				$poll_option_data = array_unique( array_column( $poll_option_data, $r['fields'] ) );
			}
		}

		$result = apply_filters( 'bb_get_all_options', $poll_option_data, $r );

		unset( $poll_option_data, $poll_options_sql, $select_sql, $from_sql, $where_conditions, $where_sql, $order_by, $sort, $optional_conditions, $id_condition, $option_title_condition );

		return $result;
	}

	/**
	 * Function to update poll option data.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments.
	 *
	 * @type int    $id            Poll option id.
	 * @type int    $poll_id       Poll id.
	 * @type int    $user_id       User id.
	 * @type string $option_title  Option title.
	 * @type int    $option_order  Option order.
	 * @type string $date_recorded Date recorded.
	 * @type string $date_updated  Date updated.
	 * @type string $error_type    Error type.
	 * }
	 *
	 * @return array|false|WP_Error
	 */
	public function bb_update_poll_option( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'            => 0,
				'poll_id'       => 0,
				'user_id'       => false,
				'option_title'  => '',
				'option_order'  => 0,
				'date_recorded' => bp_core_current_time(),
				'date_updated'  => bp_core_current_time(),
				'error_type'    => 'bool',
			)
		);

		// Option need Poll ID.
		if ( empty( $r['poll_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_empty_poll_id', __( 'The Poll id is required to update poll option.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
			// Option need Option title.
		} elseif ( empty( $r['option_title'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_empty_option_title', __( 'The Option title is required to update poll option.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		} elseif ( empty( $r['user_id'] ) ) {
			// Option need User ID.
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_empty_user_id', __( 'Invalid User ID.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		}

		$get_poll_options = $this->bb_get_poll_options( $r );

		// Check if update is necessary.
		$title_changed = false;
		$order_changed = false;
		if ( ! empty( $get_poll_options ) && ! empty( $get_poll_options[0] ) ) {
			$current_poll_options = current( $get_poll_options );
			$option_title         = $current_poll_options['option_title'];
			$option_order         = $current_poll_options['option_order'];
			if ( $option_title !== $r['option_title'] ) {
				$title_changed = true;
			}
			if ( (int) $option_order !== (int) $r['option_order'] ) {
				$order_changed = true;
			}
		} else {
			// If no current option found, it's an insert operation.
			$title_changed = true;
			$order_changed = true;
		}

		if ( $title_changed || $order_changed ) {
			if ( ! empty( $get_poll_options ) && ! empty( $get_poll_options[0] ) ) {
				$query = 'UPDATE ' . self::$poll_options_table . '
					SET
					option_title = %s,
					option_order = %d,
					date_updated = %s';

				// Initialize parameters array with values to be updated.
				$params = array(
					$r['option_title'],
					$r['option_order'],
					$r['date_updated'],
				);

				// Update user_id only if the title changed.
				if ( $title_changed ) {
					$query   .= ', user_id = %d';
					$params[] = bp_loggedin_user_id();
				}

				// Construct WHERE clause conditionally.
				$where = array();
				if ( ! empty( $r['id'] ) && ! empty( $r['poll_id'] ) ) {
					$where[]  = 'id = %d';
					$where[]  = 'poll_id = %d';
					$params[] = $r['id'];
					$params[] = $r['poll_id'];
				} elseif ( ! empty( $r['poll_id'] ) ) {
					$where[]  = 'poll_id = %d';
					$params[] = $r['poll_id'];
				}

				// Append the WHERE clause if any condition is added.
				if ( ! empty( $where ) ) {
					$query .= ' WHERE ' . implode( ' AND ', $where );
				}

				// Prepare the final query with the parameters.
				$sql = $wpdb->prepare(
				// phpcs:ignore
					$query,
					$params
				);

			} else {
				$sql = $wpdb->prepare(
				// phpcs:ignore
					'INSERT INTO ' . self::$poll_options_table . ' (
						poll_id,
						user_id,
						option_title,
						option_order,
						date_recorded,
						date_updated
					) VALUES (
						%d, %d, %s, %d, %s, %s
					)',
					(int) $r['poll_id'],
					(int) $r['user_id'],
					$r['option_title'],
					$r['option_order'],
					$r['date_recorded'],
					$r['date_updated']
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( false === $wpdb->query( $sql ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					unset( $r, $get_poll_options, $sql, $params, $query, $where );

					return new WP_Error( 'bb_poll_option_cannot_add', __( 'There is an error while adding the poll options.', 'buddyboss-pro' ) );
				} else {
					unset( $r, $get_poll_options, $sql, $params, $query, $where );

					return false;
				}
			}

			$option_id = $wpdb->insert_id;
			if ( ! empty( $get_poll_options ) && ! empty( $get_poll_options[0] ) ) {
				$current_poll_option = current( $get_poll_options );
				$option_id           = $current_poll_option['id'];
			}
		} else {
			// No update needed, return the current option data.
			$option_id = $r['id'];
		}

		unset( $get_poll_options );

		/**
		 * Fires after the added poll option.
		 *
		 * @since 2.6.00
		 *
		 * @param int   $option_id Poll option id.
		 * @param array $r         Array of parsed arguments.
		 */
		do_action( 'bb_poll_after_add_poll_option', $option_id, $r );

		$poll_option_data = $this->bb_get_poll_options(
			array(
				'id'      => $option_id,
				'poll_id' => $r['poll_id'],
			)
		);

		unset( $r );

		return $poll_option_data;
	}

	/**
	 * Function to remove the poll options.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments.
	 *
	 * @type int    $id         Poll option id.
	 * @type int    $poll_id    Poll id.
	 * @type string $error_type Error type.
	 * }
	 * @return bool|WP_Error
	 */
	public function bb_remove_poll_options( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'         => 0,
				'poll_id'    => 0,
				'error_type' => 'bool',
			)
		);

		/**
		 * Fires before the remove poll options.
		 *
		 * @since 2.6.00
		 *
		 * @param array $r Args of poll options.
		 */
		do_action( 'bb_poll_before_remove_poll_options', $r );

		$where_args = array();

		if ( ! empty( $r['id'] ) ) {
			$where_args['id'] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		if ( ! empty( $r['poll_id'] ) ) {
			$where_args['poll_id'] = $wpdb->prepare( 'poll_id = %d', $r['poll_id'] );
		}

		if ( empty( $where_args ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $where_args );

				return new WP_Error(
					'bb_poll_remove_option_invalid_argument',
					__( 'Invalid request.', 'buddyboss-pro' )
				);
			}
			unset( $r, $where_args );

			return false;
		}

		// Join the where arguments for querying.
		$where_sql = ' WHERE ' . join( ' AND ', $where_args );

		// Fetch all poll options being deleted, so we can perform more actions.
		// phpcs:ignore
		$poll_options = $wpdb->get_col( 'SELECT id FROM ' . self::$poll_options_table . " {$where_sql}" );

		// Attempt to delete poll options from the database.
		$deleted = $wpdb->query( 'DELETE FROM ' . self::$poll_options_table . " {$where_sql}" ); // phpcs:ignore

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $where_args, $where_sql, $poll_options );

				return new WP_Error(
					'bb_poll_remove_poll_options_invalid_request',
					__( 'Unable to removing the poll options.', 'buddyboss-pro' )
				);
			}

			unset( $r, $where_args, $where_sql, $poll_options );

			return false;
		}

		unset( $where_args, $where_sql );

		/**
		 * Fires after the remove poll option.
		 *
		 * @since 2.6.00
		 *
		 * @param int|false $deleted      The number of rows deleted, or false on error.
		 * @param array     $r            Args of poll options.
		 * @param object    $poll_options Poll options data.
		 */
		do_action( 'bb_poll_after_remove_poll_options', $deleted, $r, $poll_options );

		$this->bb_remove_poll_votes(
			array(
				'poll_id'   => $r['poll_id'],
				'option_id' => $r['id'],
			)
		);

		unset( $r, $poll_options );

		return $deleted;
	}

	/**
	 * Function to get the poll votes.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments. All items are optional.
	 *
	 * @type int    $id          Poll vote id.
	 * @type int    $poll_id     Poll id.
	 * @type int    $option_id   Option id.
	 * @type int    $user_id     User id.
	 * @type int    $per_page    Results per page. Default is 20.
	 *                           Use -1 to return all results.
	 * @type int    $paged       Page number. Default is 1.
	 * @type bool   $count_total Whether to count total results. Default is false.
	 *                           If true, the function will return an array with 'total' and 'poll_votes'.
	 *                           If false, the function will return only the 'poll_votes'.
	 * @type string $fields      Fields to include. Default is 'all'. Possible values are:
	 * @type string $error_type  Error type.
	 * }
	 * @return false|WP_Error|array
	 */
	public function bb_get_poll_votes( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'          => 0,
				'poll_id'     => 0,
				'option_id'   => 0,
				'user_id'     => false,
				'per_page'    => 20,    // Results per page.
				'paged'       => 1,     // Page 1 without a per_page will result in no pagination.
				'order_by'    => 'id',
				'order'       => 'ASC',
				'count_total' => false,
				'fields'      => 'all', // Fields to include.
				'error_type'  => 'bool',
			)
		);

		// Vote need Poll ID.
		if ( empty( $r['poll_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_option_vote_empty_poll_id', __( 'The Poll ID is required to get poll vote.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		}

		// Vote need Option ID.
		$get_poll = bb_load_polls()->bb_get_poll( (int) $r['poll_id'] );
		if ( ! empty( $get_poll ) && bb_poll_allow_multiple_options( $get_poll ) && empty( $r['option_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_option_vote_empty_option_id', __( 'The Option ID is required to get poll vote.', 'buddyboss-pro' ) );
			}
			unset( $r );

			return false;
		}

		// Select conditions.
		$select_sql = 'SELECT pv.id';

		$from_sql = ' FROM ' . self::$poll_votes_table . ' pv';

		// Where conditions.
		$where_conditions = array();

		// Sorting.
		$sort = bp_esc_sql_order( $r['order'] );
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		$order_by = 'pv.' . $r['order_by'];

		// id.
		if ( ! empty( $r['id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.id = %d', $r['id'] );
		}

		// poll_id.
		if ( ! empty( $r['poll_id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.poll_id = %d', $r['poll_id'] );
		}

		// option_id.
		if ( ! empty( $r['option_id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.option_id = %d', $r['option_id'] );
		}

		// user_id.
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.user_id = %d', $r['user_id'] );
		}

		/**
		 * Filters the MySQL WHERE conditions for the poll votes get sql method.
		 *
		 * @since 2.6.00
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at the point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bb_get_poll_votes_where_conditions', $where_conditions, $r, $select_sql, $from_sql );

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		// Sanitize page and per_page parameters.
		$page       = absint( $r['paged'] );
		$per_page   = absint( $r['per_page'] );
		$pagination = '';
		if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
			$start_val = intval( ( $page - 1 ) * $per_page );
			if ( ! empty( $where_conditions['before'] ) ) {
				$start_val = 0;
				unset( $where_conditions['before'] );
			}
			$pagination = $wpdb->prepare( 'LIMIT %d, %d', $start_val, intval( $per_page ) );
		}

		// Query first for poll vote IDs.
		$poll_votes_sql = "{$select_sql} {$from_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

		$retval = array(
			'poll_votes' => null,
			'total'      => null,
		);

		/**
		 * Filters the poll votes data MySQL statement.
		 *
		 * @since 2.6.00
		 *
		 * @param string $poll_votes_sql MySQL's statement used to query for poll votes.
		 * @param array  $r              Array of arguments passed into method.
		 */
		$poll_votes_sql = apply_filters( 'bb_get_poll_votes_sql', $poll_votes_sql, $r );

		$cached = bp_core_get_incremented_cache( $poll_votes_sql, self::$pv_cache_group );
		if ( false === $cached ) {
			$poll_votes_ids = $wpdb->get_col( $poll_votes_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $poll_votes_sql, self::$pv_cache_group, $poll_votes_ids );
		} else {
			$poll_votes_ids = $cached;
		}

		if ( 'id' === $r['fields'] ) {
			// We only want the IDs.
			$poll_votes_data = array_map( 'intval', $poll_votes_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $poll_votes_ids, self::$pv_cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				// phpcs:ignore
				$queried_data = $wpdb->get_results( 'SELECT * FROM ' . self::$poll_votes_table . " WHERE id IN ({$uncached_ids_sql})", ARRAY_A );

				foreach ( (array) $queried_data as $pvdata ) {
					wp_cache_set( $pvdata['id'], $pvdata, self::$pv_cache_group );
				}
			}

			$poll_votes_data = array();
			foreach ( $poll_votes_ids as $id ) {
				$poll_votes = wp_cache_get( $id, self::$pv_cache_group );
				if ( ! empty( $poll_votes ) ) {
					$poll_votes_data[] = $poll_votes;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				$poll_votes_data = array_unique( array_column( $poll_votes_data, $r['fields'] ) );
			}
		}

		$retval['poll_votes'] = $poll_votes_data;

		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total poll votes MySQL statement.
			 *
			 * @since 2.6.00
			 *
			 * @param string $value     MySQL's statement used to query for total poll votes.
			 * @param string $where_sql MySQL WHERE statement portion.
			 */
			$total_poll_votes_sql = apply_filters( 'bb_total_poll_votes_sql', 'SELECT count(DISTINCT pv.id) FROM ' . self::$poll_votes_table . ' pv ' . $where_sql, $where_sql );
			$cached               = bp_core_get_incremented_cache( $total_poll_votes_sql, self::$pv_cache_group );
			if ( false === $cached ) {
				// phpcs:ignore
				$total_poll_votes = $wpdb->get_var( $total_poll_votes_sql );
				bp_core_set_incremented_cache( $total_poll_votes_sql, self::$pv_cache_group, $total_poll_votes );
			} else {
				$total_poll_votes = $cached;
			}

			$retval['total'] = $total_poll_votes;
		}

		unset( $r, $select_sql, $from_sql, $where_conditions, $where_sql, $pagination, $poll_votes_sql, $cached, $poll_votes_ids, $uncached_ids, $uncached_ids_sql, $queried_data, $poll_votes_data );

		return $retval;
	}

	/**
	 * Function to update the poll votes.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments.
	 *
	 * @type int    $id            Poll vote id.
	 * @type int    $poll_id       Poll id.
	 * @type int    $option_id     Option id.
	 * @type int    $user_id       User id.
	 * @type string $date_recorded Date recorded.
	 * @type string $error_type    Error type.
	 * }
	 * @return array|false|WP_Error
	 */
	public function update_poll_votes( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'            => 0,
				'poll_id'       => 0,
				'option_id'     => 0,
				'user_id'       => bp_loggedin_user_id(),
				'date_recorded' => bp_core_current_time(),
				'error_type'    => 'bool',
			)
		);

		// Vote need Poll ID.
		if ( empty( $r['poll_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_vote_empty_poll_id', __( 'The Poll ID is required to update poll vote.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
			// Vote need Option ID.
		} elseif ( empty( $r['option_id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_vote_empty_option_id', __( 'The Option ID is required to update poll vote.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		} elseif ( empty( $r['user_id'] ) ) {
			// Vote need User ID.
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_poll_vote_empty_user_id', __( 'Invalid User ID.', 'buddyboss-pro' ) );
			}

			unset( $r );

			return false;
		}

		// Copy all $r to $get_vote_args.
		$get_vote_args = $r;
		// For select a multiple option, we should not need to check if the user has already voted for the poll option.
		// That's why need to pass option_id is 0.
		$get_poll       = bb_load_polls()->bb_get_poll( (int) $r['poll_id'] );
		$allow_multiple = bb_poll_allow_multiple_options( $get_poll );
		if ( ! $allow_multiple ) {
			$get_vote_args['option_id'] = 0;
		}
		$get_poll_votes = $this->bb_get_poll_votes( $get_vote_args );

		if ( ! empty( $get_poll_votes ) && ! empty( $get_poll_votes['poll_votes'][0] ) ) {
			$poll_votes = current( $get_poll_votes['poll_votes'] );

			bb_load_polls()->bb_remove_poll_votes(
				array(
					'poll_id'   => $r['poll_id'],
					'option_id' => $r['option_id'],
					'user_id'   => bp_loggedin_user_id(),
				)
			);

			$sql = $wpdb->prepare(
				// phpcs:ignore
				'UPDATE ' . self::$poll_votes_table . ' SET
				poll_id = %d,
				option_id = %d,
				user_id = %d,
				date_recorded = %s
				WHERE
				id = %d
				',
				(int) $r['poll_id'],
				(int) $r['option_id'],
				(int) $r['user_id'],
				! empty( $r['date_recorded'] ) ? $r['date_recorded'] : $poll_votes['date_recorded'],
				$poll_votes['id']
			);
		} else {
			$sql = $wpdb->prepare(
				// phpcs:ignore
				'INSERT INTO ' . self::$poll_votes_table . ' (
						poll_id,
						option_id,
						user_id,
						date_recorded
					) VALUES (
						%d, %d, %d, %s
					)',
				(int) $r['poll_id'],
				(int) $r['option_id'],
				(int) $r['user_id'],
				$r['date_recorded']
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false === $wpdb->query( $sql ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $get_vote_args, $get_poll, $allow_multiple, $get_poll_votes, $poll_votes, $sql );

				return new WP_Error( 'bb_poll_votes_cannot_add', __( 'There is an error while adding the poll votes.', 'buddyboss-pro' ) );
			} else {
				unset( $r, $get_vote_args, $get_poll, $allow_multiple, $get_poll_votes, $poll_votes, $sql );

				return false;
			}
		}

		$poll_vote_id = $wpdb->insert_id;
		if ( ! empty( $get_poll_votes ) && ! empty( $get_poll_votes['poll_votes'][0] ) ) {
			$current_poll_vote = current( $get_poll_votes['poll_votes'] );
			$poll_vote_id      = $current_poll_vote['id'];
		}

		/**
		 * Fires after the added poll vote.
		 *
		 * @since 2.6.00
		 *
		 * @param int   $poll_vote_id Poll vote id.
		 * @param array $r            Array of parsed arguments.
		 */
		do_action( 'bb_poll_after_add_poll_vote', $poll_vote_id, $r );

		$result = $this->bb_get_poll_votes(
			array(
				'id'        => $poll_vote_id,
				'poll_id'   => $r['poll_id'],
				'option_id' => $r['option_id'],
			)
		);

		unset( $r, $get_vote_args, $get_poll, $allow_multiple, $get_poll_votes, $poll_votes, $sql );

		return $result;
	}

	/**
	 * Function to remove the poll votes.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args {
	 * An array of arguments.
	 *
	 * @type int    $id         Poll vote id.
	 * @type int    $poll_id    Poll id.
	 * @type int    $option_id  Option id.
	 * @type int    $user_id    User id.
	 * @type string $error_type Error type.
	 * }
	 * @return bool|WP_Error
	 */
	public function bb_remove_poll_votes( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'id'         => 0,
				'poll_id'    => 0,
				'option_id'  => 0,
				'user_id'    => false,
				'error_type' => 'bool',
			)
		);

		/**
		 * Fires before the remove poll votes.
		 *
		 * @since 2.6.00
		 *
		 * @param array $r Args of poll votes.
		 */
		do_action( 'bb_poll_before_remove_poll_votes', $r );

		$where_args = array();

		if ( ! empty( $r['id'] ) ) {
			$where_args['id'] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		if ( ! empty( $r['poll_id'] ) ) {
			$where_args['poll_id'] = $wpdb->prepare( 'poll_id = %d', $r['poll_id'] );
		}

		if ( ! empty( $r['option_id'] ) ) {
			$where_args['option_id'] = $wpdb->prepare( 'option_id = %d', $r['option_id'] );
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_args['user_id'] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		if ( empty( $where_args ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $where_args );

				return new WP_Error(
					'bb_poll_remove_vote_invalid_argument',
					__( 'Invalid request.', 'buddyboss-pro' )
				);
			}

			unset( $r, $where_args );

			return false;
		}

		// Join the where arguments for querying.
		$where_sql = ' WHERE ' . join( ' AND ', $where_args );

		// Fetch all poll votes being deleted, so we can perform more actions.
		// phpcs:ignore
		$poll_votes = $wpdb->get_col( 'SELECT id FROM ' . self::$poll_votes_table . " {$where_sql}" );

		// Attempt to delete poll options from the database.
		$deleted = $wpdb->query( 'DELETE FROM ' . self::$poll_votes_table . " {$where_sql}" ); // phpcs:ignore

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r, $where_args, $poll_votes, $where_sql );

				return new WP_Error(
					'bb_poll_remove_poll_votes_invalid_request',
					__( 'Unable to removing the poll votes.', 'buddyboss-pro' )
				);
			}
			unset( $r, $where_args, $poll_votes, $where_sql );

			return false;
		}

		/**
		 * Fires after the remove poll vote.
		 *
		 * @since 2.6.00
		 *
		 * @param int|false $deleted    The number of rows deleted, or false on error.
		 * @param array     $r          Args of poll votes.
		 * @param object    $poll_votes Poll votes data.
		 */
		do_action( 'bb_poll_after_remove_poll_votes', $deleted, $r, $poll_votes );

		unset( $r, $where_args, $where_sql, $poll_votes );

		return $deleted;
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @since 2.6.00
	 */
	public function bb_rest_api_init() {
		if ( class_exists( 'BB_REST_Poll_Endpoint' ) ) {
			$controller = new BB_REST_Poll_Endpoint();
			$controller->register_routes();
		}
		if ( class_exists( 'BB_REST_Poll_Option_Endpoint' ) ) {
			$controller = new BB_REST_Poll_Option_Endpoint();
			$controller->register_routes();
		}
		if ( class_exists( 'BB_REST_Poll_Option_Vote_Endpoint' ) ) {
			$controller = new BB_REST_Poll_Option_Vote_Endpoint();
			$controller->register_routes();
		}
	}

	/**
	 * Function to get the count of poll votes.
	 *
	 * @since 2.6.00
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return int
	 */
	public function bb_get_poll_option_vote_count( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'poll_id'   => 0, // Poll ID.
				'option_id' => 0, // Poll option ID.
			),
			'bb_get_poll_option_vote_count'
		);

		$sql = 'SELECT COUNT(*) AS vote_count FROM ' . self::$poll_votes_table . ' as pv';

		// Where conditions.
		$where_conditions = array();

		// poll_id.
		if ( ! empty( $r['poll_id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.poll_id = %d', $r['poll_id'] );
		}

		// option_id.
		if ( ! empty( $r['option_id'] ) ) {
			$where_conditions[] = $wpdb->prepare( 'pv.option_id = %d', $r['option_id'] );
		}

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		$sql .= " {$where_sql}";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$vote_count = $wpdb->get_var( $sql );

		// Return the vote count.
		return intval( $vote_count );
	}
}
