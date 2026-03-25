<?php
/**
 * BuddyBoss Activity Schedule Classes.
 *
 * @since 2.5.20
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Schedule_Posts' ) ) {
	/**
	 * BuddyBoss Activity Schedule.
	 * Handles schedule posts.
	 *
	 * @since 2.5.20
	 */
	class BB_Schedule_Posts {
		/**
		 * The single instance of the class.
		 *
		 * @since 2.5.20
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Unique ID for the schedule posts.
		 *
		 * @since 2.5.20
		 *
		 * @var string schedule-posts.
		 */
		public $id = 'schedule-posts';

		/**
		 * Get the instance of this class.
		 *
		 * @since 2.5.20
		 *
		 * @return Controller|BB_Schedule_Posts|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since 2.5.20
		 */
		public function __construct() {

			// Include the code.
			$this->includes();
			$this->setup_actions();
		}

		/**
		 * Setup actions for schedule posts.
		 *
		 * @since 2.5.20
		 */
		public function setup_actions() {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_script' ) );
			add_action( 'bp_activity_after_save', array( $this, 'bb_register_schedule_activity' ), 999, 1 );
			add_action( 'bb_activity_publish', array( $this, 'bb_check_and_publish_scheduled_activity' ) );
			add_action( 'bp_nouveau_object_template_path', array( $this, 'bb_schedule_activity_object_template_path' ), 10, 2 );

			// Check if the activation transient exists.
			if ( get_option( '_bb_schedule_posts_cron_setup' ) ) {
				delete_option( '_bb_schedule_posts_cron_setup' );
				self::bb_create_activity_schedule_cron_event();
			}

			// Add the  JS templates for schedule posts.
			add_filter( 'bp_messages_js_template_parts', array( $this, 'bb_add_scheduled_posts_js_templates' ) );

			// Register the template for schedule posts.
			bp_register_template_stack( array( $this, 'bb_register_schedule_posts_template' ) );
		}

		/**
		 * Enqueue related scripts and styles.
		 *
		 * @since 2.5.20
		 */
		public function enqueue_script() {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';

			$css_prefix =
				function_exists( 'bb_is_readylaunch_enabled' ) &&
				bb_is_readylaunch_enabled() &&
				class_exists( 'BB_Readylaunch' ) &&
				bb_load_readylaunch()->bb_is_readylaunch_enabled_for_page()
				? 'bb-rl-' : 'bb-';
			wp_enqueue_style( 'bb-schedule-posts', bb_schedule_posts_url( '/assets/css/' . $css_prefix . 'schedule-posts' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );
			wp_enqueue_script( 'bb-schedule-posts', bb_schedule_posts_url( '/assets/js/bb-schedule-posts' . $min . '.js' ), array( 'bp-nouveau', 'wp-util', 'wp-backbone' ), bb_platform_pro()->version, true );
		}

		/**
		 * Includes files.
		 *
		 * @since 2.5.20
		 *
		 * @param array $includes list of the files.
		 */
		public function includes( $includes = array() ) {

			$slashed_path = trailingslashit( bb_platform_pro()->schedule_posts_dir );

			$includes = array(
				'cache',
				'functions',
				'filters',
				'actions',
			);

			// Loop through files to be included.
			foreach ( (array) $includes as $file ) {

				if ( empty( $this->bb_schedule_posts_check_has_licence() ) ) {
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
			}
		}

		/**
		 * Schedule activity publish event.
		 *
		 * @since 2.5.20
		 *
		 * @param array|object $activity The activity object or array.
		 */
		public function bb_register_schedule_activity( $activity ) {
			if (
				empty( $activity->id ) ||
				in_array( $activity->privacy, array( 'media', 'video', 'document' ), true ) ||
				! function_exists( 'bb_get_activity_scheduled_status' ) ||
				bb_get_activity_scheduled_status() !== $activity->status
			) {
				return;
			}

			self::bb_create_activity_schedule_cron_event();
		}

		/**
		 * Create activity schedule cron event if not exists.
		 *
		 * @since 2.5.20
		 */
		public static function bb_create_activity_schedule_cron_event() {
			if ( ! wp_next_scheduled( 'bb_activity_publish' ) ) {
				wp_schedule_event( time(), 'bb_schedule_1min', 'bb_activity_publish' );
			}
		}

		/**
		 * Get all the scheduled activities and publish it.
		 *
		 * @since 2.5.20
		 *
		 * @return void
		 */
		public function bb_check_and_publish_scheduled_activity() {
			global $wpdb;

			$bp_prefix        = bp_core_get_table_prefix();
			$current_time     = bp_core_current_time();
			$scheduled_status = function_exists( 'bb_get_activity_scheduled_status' ) ? bb_get_activity_scheduled_status() : 'scheduled';
			$published_status = function_exists( 'bb_get_activity_published_status' ) ? bb_get_activity_published_status() : 'published';

			// Get all activities that are scheduled and past due.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$activities = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM {$bp_prefix}bp_activity
					 WHERE type='activity_update' AND privacy NOT IN ( 'media', 'video', 'document' ) AND status = %s AND date_recorded <= %s",
					$scheduled_status,
					$current_time
				)
			);
			if ( ! empty( $activities ) ) {
				foreach ( $activities as $scheduled_activity ) {
					$activity = new BP_Activity_Activity( $scheduled_activity->id );
					if ( ! empty( $activity->id ) ) {

						// Removed action for handling saved link previews.
						remove_action( 'bp_activity_after_save', 'bp_activity_save_link_data', 2, 1 );

						// Publish the activity.
						$activity->status         = $published_status;
						$activity->title_required = false;
						$activity->save();

						add_action( 'bp_activity_after_save', 'bp_activity_save_link_data', 2, 1 );
						// Remove edited time from scheduled activities.
						bp_activity_delete_meta( $activity->id, '_is_edited' );

						$metas = bb_activity_get_metadata( $activity->id );

						// Publish the media.
						if ( ! empty( $metas['bp_media_ids'][0] ) ) {
							$media_ids = explode( ',', $metas['bp_media_ids'][0] );
							$this->bb_publish_schedule_activity_medias_and_documents( $media_ids );
						}

						// Publish the video.
						if ( ! empty( $metas['bp_video_ids'][0] ) ) {
							$video_ids = explode( ',', $metas['bp_video_ids'][0] );
							$this->bb_publish_schedule_activity_medias_and_documents( $video_ids, 'video' );
						}

						// Publish the document.
						if ( ! empty( $metas['bp_document_ids'][0] ) ) {
							$document_ids = explode( ',', $metas['bp_document_ids'][0] );
							$this->bb_publish_schedule_activity_medias_and_documents( $document_ids, 'document' );
						}

						// Send mentioned notifications.
						add_filter( 'bp_activity_at_name_do_notifications', '__return_true' );

						if ( ! empty( $activity->item_id ) ) {
							bb_group_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity->item_id, $activity->id );
							bb_subscription_send_subscribe_group_notifications( $activity->content, $activity->user_id, $activity->item_id, $activity->id );
						} else {
							bb_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity->id );
						}

						bb_activity_send_email_to_following_post( $activity->content, $activity->user_id, $activity->id );
					}
				}
			}
		}

		/**
		 * Publish scheduled activity media/video/document and their individual activities.
		 *
		 * @since 2.5.20
		 *
		 * @param array  $ids  Ids of media/video/document.
		 * @param string $type Media type : 'media', 'video', 'document'.
		 */
		public function bb_publish_schedule_activity_medias_and_documents( $ids, $type = 'media' ) {
			global $wpdb;

			if ( ! empty( $ids ) ) {
				$bp_prefix  = bp_core_get_table_prefix();
				$table_name = "{$bp_prefix}bp_media";
				if ( 'document' === $type ) {
					$table_name = "{$bp_prefix}bp_document";
				}

				// Check table exists.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
				if ( $table_exists ) {
					foreach ( $ids as $id ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET status = 'published' WHERE id = %d", $id ) );

						// Also update the individual medias/videos/document activity.
						if ( count( $ids ) > 1 ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$activity_id              = $wpdb->get_var( $wpdb->prepare( "SELECT activity_id FROM {$table_name} WHERE id = %d", $id ) );
							$activity                 = new BP_Activity_Activity( $activity_id );
							$activity->status         = bb_get_activity_published_status();
							$activity->title_required = false;
							$activity->save();
						}
					}
				}
			}
		}

		/**
		 * Function to return the default value if no licence.
		 *
		 * @since 2.5.20
		 *
		 * @param bool $has_access Whether has access.
		 *
		 * @return bool Return the default.
		 */
		protected function bb_schedule_posts_check_has_licence( $has_access = true ) {

			if ( bb_pro_should_lock_features() ) {
				return false;
			}

			return $has_access;
		}

		/**
		 * Register template path for scheduled posts.
		 *
		 * @since 2.5.20
		 *
		 * @return string Template path.
		 */
		public function bb_register_schedule_posts_template() {
			return bb_schedule_posts_path( '/templates' );
		}

		/**
		 * Add Js template path for scheduled posts.
		 *
		 * @since 2.5.20
		 *
		 * @param array $templates Array of template paths to filter.
		 *
		 * @return array Array of template paths.
		 */
		public function bb_add_scheduled_posts_js_templates( $templates ) {

			$templates[] = 'parts/bb-activity-schedule-post';
			$templates[] = 'parts/bb-activity-schedule-details';

			return $templates;
		}

		/**
		 * Filter the template path to support the schedule posts loop.
		 *
		 * @since 2.5.20
		 *
		 * @param string $template_path Template path to filter.
		 * @param string $object        Type of object.
		 *
		 * @return string template path.
		 */
		public function bb_schedule_activity_object_template_path( $template_path, $object ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$template = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';
			if ( 'activity' === $object && 'activity_schedule' === $template ) {
				$template_part = 'activity-schedule/activity-schedule-loop.php';
				$template_path = bp_locate_template( array( $template_part ), false );
			}
			return $template_path;
		}
	}
}
