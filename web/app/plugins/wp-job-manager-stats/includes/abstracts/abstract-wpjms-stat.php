<?php
/**
 * Stats Base Class
 *
 * @since 2.0.0
 */

/**
 * Abstract WPJMS_Stat class.
 *
 * @abstract
 *
 * @since 2.0.0
 */
abstract class WPJMS_Stat {

	/**
	 * Post Types
	 *
	 * @var array $post_types Array of post types to log.
	 * @since 2.0.0
	 */
	public $post_types = array( 'job_listing' );

	/**
	 * Stats ID
	 *
	 * @var string $stat_id Stat ID.
	 * @since 2.0.0
	 */
	public $stat_id = '';

	/**
	 * Stat Label
	 *
	 * @var string $stat_label Stat label.
	 * @since 2.0.0
	 */
	public $stat_label = '';

	/**
	 * Cookie ID
	 *
	 * @var string $cookie_id Cookie ID.
	 * @since 2.0.0
	 */
	public $cookie_id = 'wp_job_manager_stats';

	/**
	 * Cookie Name
	 *
	 * @var string $cookie_name Cookie Name.
	 * @since 2.0.0
	 */
	public $cookie_name = '';

	/**
	 * Hook
	 *
	 * @var string $hook WordPress Hook
	 * @since 2.0.0
	 */
	public $hook = 'wp';

	/**
	 * Is AJAX Enabled
	 *
	 * @var bool $is_ajax Is AJAX enabled.
	 * @since 2.0.0
	 */
	public $is_ajax = false;

	/**
	 * Track/Log author activity of listing
	 *
	 * @var bool $log_author True to also log author activity.
	 * @since 2.0.0
	 */
	public $log_author = false;

	/**
	 * Is tracking unique/Cookie needed.
	 *
	 * @var bool $is_ajax Is AJAX enabled.
	 * @since 2.0.0
	 */
	public $check_cookie = false;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		// Register Stat.
		add_filter( 'wpjms_stats', array( $this, 'register_stat' ) );

		// Add initial stat value.
		add_action( 'wp_insert_post', array( $this, 'wp_insert_post' ), 10, 3 );

		// Trigger.
		if ( $this->is_ajax ) {
			add_action( "wp_ajax_wpjms_stat_{$this->stat_id}", array( $this, 'update_stat_ajax' ) );
			add_action( "wp_ajax_nopriv_wpjms_stat_{$this->stat_id}", array( $this, 'update_stat_ajax' ) );
		} else {
			add_action( $this->hook, array( $this, 'trigger' ) );
		}
	}

	/**
	 * Register Stat.
	 *
	 * @since 2.0.0
	 *
	 * @param array $stats List of active stats.
	 */
	public function register_stat( $stats ) {
		$stats[ $this->stat_id ] = array(
			'id'     => $this->stat_id,
			'label'  => $this->stat_label,
		);
		return $stats;
	}

	/**
	 * Check if tracking is needed for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function check( $post_id ) {
		// Get post, if not valid, bail.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Check post type.
		if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
			return false;
		}

		// Do not track listing author.
		if ( ! $this->log_author && is_user_logged_in() && $post->post_author && get_current_user_id() === $post->post_author ) {
			return false;
		}

		// If log by cookie.
		if ( $this->check_cookie ) {

			// Bail, already logged.
			if ( in_array( $post_id, $this->get_cookie() ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Trigger Stat.
	 * Soft deprecated, it's best to use AJAX for logging stat.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function trigger( $post_id = null ) {
		// Get post ID.
		$post_id = $post_id ? intval( $post_id ) : intval( get_queried_object_id() );

		// Bail if tracking is not needed.
		if ( ! $this->check( $post_id ) ) {
			return;
		}

		// Update stat.
		$this->update_stat_value( $post_id );
	}

	/**
	 * AJAX Callback.
	 *
	 * @since 2.7.0
	 */
	public function update_stat_ajax() {
		$request = stripslashes_deep( $_POST );

		// Get Post ID.
		$post_id = intval( $request['post_id'] );

		// Check if tracking needed.
		if ( $this->check( $post_id ) ) {

			// Update stat.
			$updated = $this->update_stat_value( $post_id );
			if ( $updated ) {

				// Success.
				$data = array(
					'stat'    => $this->stat_id,
					'post_id' => $post_id,
					'result'  => 'stat_updated',
				);
				if ( $this->check_cookie ) {
					$data['cookie'] = $this->get_cookie();
				}
				wp_send_json_success( $data );
			}
		}

		// Fail.
		$data = array(
			'stat'   => $this->stat_id,
			'post_id' => $post_id,
			'result' => 'stat_update_fail',
		);
		if ( $this->check_cookie ) {
			$data['cookie'] = $this->get_cookie();
		}
		wp_send_json_error( $data );
	}

	/**
	 * Update Stat.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return int|false The number of rows updated, or false on error/fail.
	 */
	public function update_stat_value( $post_id ) {
		$updated = wpjms_update_stat_value( $post_id, $this->stat_id );

		// Success.
		if ( $updated ) {

			// Update cookie if needed.
			if ( $this->check_cookie ) {
				$this->add_cookie( $post_id );
			}

			// Update total.
			$this->update_post_stat_total( $post_id );
		}

		return $updated;
	}

	/**
	 * Update Post Stats Total Data.
	 * This data is only updated on daily basis.
	 * Data is useful for posts query based on stats data.
	 *
	 * @since 2.4.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function update_post_stat_total( $post_id ) {
		// Get today's date.
		$today = intval( date( 'Ymd' ) ); // YYYYMMDD.

		// Last updated stat value.
		$last_updated = intval( get_post_meta( $post_id, '_wpjms_' . $this->stat_id . '_last_updated', true ) );

		// If not yet updated today, update it.
		if ( $today !== $last_updated ) {

			// Add updated day.
			update_post_meta( $post_id, '_wpjms_' . $this->stat_id . '_last_updated', intval( $today ) );

			// Get stats total, and add it in post meta.
			$total = wpjms_get_stats_total( $post_id, $this->stat_id );
			if ( $total ) {
				update_post_meta( $post_id, '_wpjms_' . $this->stat_id . '_total', intval( $total ) );
			}
		}
	}

	/**
	 * Get Cookie
	 * this will return array of post ids of set cookie.
	 *
	 * @return array
	 */
	public function get_cookie() {
		$cookie_id = $this->cookie_id;
		$cookie_name = $this->cookie_name ? $this->cookie_name : $this->stat_id;
		$cookie_value = array();
		if ( isset( $_COOKIE[ $cookie_id ] ) && ! empty( $_COOKIE[ $cookie_id ] ) ) {
			$stats_cookie_value = json_decode( stripslashes( $_COOKIE[ $cookie_id ] ), true );
			if ( isset( $stats_cookie_value[ $cookie_name ] ) && is_array( $stats_cookie_value[ $cookie_name ] ) ) {
				$cookie_value = $stats_cookie_value[ $cookie_name ];
			}
		}
		return $cookie_value;
	}

	/**
	 * Add Post ID in Stat Cookie.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function add_cookie( $post_id ) {
		$post_id = intval( $post_id );
		$expiration  = intval( apply_filters( $this->stat_id . '_cookie_expiration', DAY_IN_SECONDS ) );
		$cookie_id = $this->cookie_id;
		$cookie_name = $this->cookie_name ? $this->cookie_name : $this->stat_id;
		$stats_cookie_value = array();
		if ( isset( $_COOKIE[ $cookie_id ] ) && ! empty( $_COOKIE[ $cookie_id ] ) ) {
			$stats_cookie_value = json_decode( stripslashes( $_COOKIE[ $cookie_id ] ), true );
		}
		$stats_cookie_value[ $cookie_name ][ $post_id ] = $post_id;
		setcookie( $cookie_id, json_encode( $stats_cookie_value ), time() + $expiration );
	}

	/**
	 * Add 0 to Stats on Listing Creation.
	 *
	 * @since 2.6.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post Object
	 * @param bool    $update  Whether this is an existing post being updated or not.
	 */
	public function wp_insert_post( $post_id, $post, $update ) {
		if ( in_array( $post->post_type, $this->post_types, true ) && ! get_post_meta( $post_id, '_wpjms_' . $this->stat_id . '_total', true ) ) {
			update_post_meta( $post_id, '_wpjms_' . $this->stat_id . '_total', 0 );
		}
	}
}
