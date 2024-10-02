<?php
/**
 * Job Alerts e-mail sending.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

use WP_Job_Manager\Stats;
use WP_Job_Manager_Alerts\Emails\Job_Alert_Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Job_Manager_Alerts\Notifier class.
 */
class Notifier {

	public const SCHEDULE_HOOK_NAME = 'job-manager-alert';

	use Singleton;

	/**
	 * Store current alert frequency for queries
	 *
	 * @var string
	 */
	private static $current_alert_frequency = 'daily';

	/**
	 * Constructor
	 *
	 * @access private since 3.0.0
	 */
	public function __construct() {
		add_action( 'job-manager-alert', [ $this, 'job_manager_alert' ], 10, 2 );
		add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
		add_filter( 'job_manager_email_notifications', [ $this, 'register_emails' ] );
		add_action( 'job-manager-alert-check-reschedule', [ $this, 'check_reschedule_events' ] );
		add_action( 'transition_post_status', [ $this, 'maybe_update_schedule' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'clear_schedule' ] );

		if ( false === wp_next_scheduled( 'job-manager-alert-check-reschedule' ) ) {
			wp_schedule_event( time(), 'daily', 'job-manager-alert-check-reschedule' );
		}

	}

	/**
	 * Apply the brand color for emails.
	 *
	 * @access private
	 *
	 * @param array $style_vars Variables used in email style generation.
	 */
	public function apply_brand_color( $style_vars ) {
		$brand_color = Settings::instance()->get_the_brand_color();
		if ( ! empty( $brand_color ) ) {
			$style_vars['color_link']        = $brand_color;
			$style_vars['color_button']      = $brand_color;
			$style_vars['color_button_text'] = '#FFF';
		}

		return $style_vars;
	}

	/**
	 * Register e-mails.
	 *
	 * @access private
	 *
	 * @param array $emails List of registered e-mail notifications.
	 *
	 * @return array
	 */
	public function register_emails( $emails ) {
		$emails[] = \WP_Job_Manager_Alerts\Emails\Job_Alert_Email::class;
		$emails[] = \WP_Job_Manager_Alerts\Emails\Confirmation_Email::class;

		return $emails;

	}

	/**
	 * Get alert schedules.
	 *
	 * @return array
	 */
	public static function get_alert_schedules() {
		$schedules = [];

		$schedules['daily'] = [
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Daily', 'wp-job-manager-alerts' ),
		];

		$schedules['weekly'] = [
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Weekly', 'wp-job-manager-alerts' ),
		];

		$schedules['fortnightly'] = [
			'interval' => WEEK_IN_SECONDS * 2,
			'display'  => __( 'Fortnightly', 'wp-job-manager-alerts' ),
		];

		$schedules['monthly'] = [
			'interval' => MONTH_IN_SECONDS,
			'display'  => __( 'Monthly', 'wp-job-manager-alerts' ),
		];

		return apply_filters( 'job_manager_alerts_alert_schedules', $schedules );
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_cron_schedules( array $schedules ) {
		return array_merge( $schedules, self::get_alert_schedules() );
	}

	/**
	 * Send and update an alert.
	 *
	 * @param int  $alert_id Alert ID.
	 * @param bool $force Ignore alert frequency and disabled status.
	 */
	public function job_manager_alert( $alert_id, $force = false ) {

		$alert = Alert::load( $alert_id );

		if ( ! $alert || ( ! $alert->is_enabled() && ! $force ) ) {
			return;
		}

		$this->send_alert_email( $alert, $force );

		$this->maybe_expire_alert( $alert );

		$alert->increase_send_count();
	}

	/**
	 * Format and send the job alert e-mail.
	 *
	 * @param Alert $alert Job Alert.
	 * @param bool  $force Ignore alert frequency and disabled status.
	 *
	 * @return void
	 */
	private function send_alert_email( Alert $alert, bool $force = false ) {

		add_filter( 'job_manager_email_style_vars', [ $this, 'apply_brand_color' ] );

		$user = $alert->get_user();
		$jobs = $alert->get_matching_jobs( $force );

		if ( ! $jobs->found_posts && get_option( 'job_manager_alerts_matches_only' ) ) {
			return;
		}

		Emails\Job_Alert_Email::send( $alert, $jobs, $user );

		Alert_Stats::log_jobs_sent( $jobs );
	}

	/**
	 * Disable the alert if it's expiration date has passed.
	 *
	 * @param Alert $alert Job alert.
	 *
	 * @return void
	 */
	private function maybe_expire_alert( $alert ) {
		$expiration = $alert->get_expiration_date( false );

		if ( ! empty( $expiration ) && time() > $expiration ) {
			$alert->disable();
		}
	}


	/**
	 * Checks alerts for their corresponding scheduled event and reschedules if missing.
	 */
	public function check_reschedule_events() {
		$alert_posts = new \WP_Query(
			[
				'post_type'      => 'job_alert',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			]
		);

		foreach ( $alert_posts->posts as $post ) {
			if ( false === wp_next_scheduled( 'job-manager-alert', [ $post->ID ] ) ) {
				$alert_frequency = get_post_meta( $post->ID, 'alert_frequency', true );

				// Use the created time to distribute the events again, starting tomorrow.
				$created = strtotime( $post->post_date );
				$next    = strtotime( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' ' . gmdate( 'G:i:s', $created ) );

				wp_schedule_event( $next, $alert_frequency, 'job-manager-alert', [ $post->ID ] );
			}
		}
	}

	/**
	 * Update schedule when alert status is changed.
	 *
	 * @access private
	 *
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function maybe_update_schedule( $new_status, $old_status, $post ) {

		$alert = Alert::load( $post->ID );

		if ( $alert && $new_status !== $old_status ) {
			$this->update_schedule( $alert );
		}

	}

	/**
	 * Schedule the alert e-mails based on the alert frequency.
	 *
	 * @since 3.1.1
	 *
	 * @param int|\WP_Post|Alert $alert The alert.
	 */
	public function update_schedule( $alert ) {

		$alert = Alert::load( $alert );

		if ( ! $alert ) {
			return;
		}

		$this->clear_schedule( $alert );

		if ( ! $alert->is_enabled() ) {
			return;
		}

		// Schedule new alert
		$schedule = $alert->get_schedule();

		if ( ! empty( $schedule ) ) {
			$next = strtotime( '+' . $schedule['interval'] . ' seconds' );
		} else {
			$next = strtotime( '+1 day' );
		}

		wp_schedule_event( $next, $alert->frequency, self::SCHEDULE_HOOK_NAME, [ $alert->ID ] );
	}

	/**
	 * Clear the schedule for this alert.
	 *
	 * @since 3.1.1
	 *
	 * @param int|\WP_Post|Alert $alert The alert.
	 */
	public function clear_schedule( $alert ) {
		$alert = Alert::load( $alert );

		if ( ! $alert ) {
			return;
		}

		wp_clear_scheduled_hook( self::SCHEDULE_HOOK_NAME, [ $alert->ID ] );
	}

	/**
	 * Get time of next email scheduled.
	 *
	 * @since 3.1.1
	 *
	 * @param int|\WP_Post|Alert $alert The alert.
	 *
	 * @return int|false Time of next email, or false if no email is scheduled.
	 */
	public function get_next_scheduled( $alert ) {

		$alert = Alert::load( $alert );

		if ( ! $alert ) {
			return false;
		}

		$scheduled = wp_next_scheduled( self::SCHEDULE_HOOK_NAME, [ $alert->ID ] );

		$date = $scheduled ? $scheduled + (int) get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS : false;

		return $date;
	}

	/**
	 * Match jobs to an alert.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param Alert|\WP_Post $alert The job alert.
	 * @param bool           $force Ignore alert frequency and cache.
	 *
	 * @return false|\WP_Query
	 */
	public static function get_matching_jobs( $alert, $force ) {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Alert::get_matching_jobs()' );
		if ( $alert instanceof \WP_Post ) {
			$alert = Alert::load( $alert->ID );
		}

		return $alert->get_matching_jobs( $force );
	}

	/**
	 * This disables the job manager's listings cache during Alerts queries. We override the `WHERE` statement in the
	 * query so we don't want to spoil the cache.
	 *
	 * @deprecated 3.0.0
	 *
	 * @return bool
	 */
	public static function disable_job_manager_cache() {
		_deprecated_function( __METHOD__, '3.0.0' );

		return false;
	}

	/**
	 * Filter WP_Query to only return posts since the last e-mail, based on the alert frequency.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param string $where SQL query.
	 *
	 * @return string
	 */
	public static function filter_alert_frequency( $where = '' ) {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\AlertJobsQuery::filter_alert_frequency()' );

		return $where;
	}

	/**
	 * Format the e-mail as plaintext, filling placeholder tags in the content template.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param \WP_Post  $alert
	 * @param \WP_User  $user
	 * @param \WP_Query $jobs
	 *
	 * @return string
	 */
	public function format_email( $alert, $user, $jobs ) {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Emails\Job_Alert_Email::get_plain_content()' );

		$email = new Emails\Job_Alert_Email(
			[
				'alert' => $alert,
				'jobs'  => $jobs,
				'user'  => $user,
			],
			[]
		);

		return $email->get_plain_content();
	}

	/**
	 * Set the "From Name" header of the e-mail.
	 *
	 * @deprecated 3.0.0
	 */
	public function mail_from_name() {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Emails\Job_Alert_Email::get_from_name()' );

		return ( new Job_Alert_Email( [], [] ) )->get_from_name();
	}

	/**
	 * Set the "From Email" header of the e-mail.
	 *
	 * @deprecated 3.0.0
	 *
	 * @return string
	 */
	public function mail_from_email() {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Emails\Job_Alert_Email::get_from_email()' );

		return ( new Job_Alert_Email( [], [] ) )->get_from_email();
	}
}
