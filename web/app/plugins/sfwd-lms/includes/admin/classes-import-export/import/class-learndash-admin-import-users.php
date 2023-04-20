<?php
/**
 * LearnDash Admin Import Users.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_User_Activity' ) &&
	! class_exists( 'Learndash_Admin_Import_Export_Users' )
) {
	/**
	 * Class LearnDash Admin Export Users.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Users extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Users;

		const META_KEY_COURSE_PROGRESS = '_sfwd-course_progress';

		const META_KEYS_WITH_DB_PREFIX = array( 'capabilities', 'user_level' );

		const REGEXP_POST_ID = '(\d*)';

		const META_KEYS_COURSE_REGEXP = array(
			'course_' . self::REGEXP_POST_ID . '_access_from',
			'learndash_course_expired_' . self::REGEXP_POST_ID,
			'course_completed_' . self::REGEXP_POST_ID,
			'completed_' . self::REGEXP_POST_ID,
		);

		const META_KEYS_GROUP_REGEXP = array(
			'learndash_group_leaders_' . self::REGEXP_POST_ID,
			'learndash_group_users_' . self::REGEXP_POST_ID,
		);

		/**
		 * Exported DB prefix.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $db_prefix_previous;

		/**
		 * Current DB prefix.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $db_prefix_current;

		/**
		 * Meta keys that are mapped with regular expressions.
		 *
		 * @since 4.3.0
		 *
		 * @var string[]
		 */
		private $meta_keys_with_regexps;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $previous_db_prefix The exported DB prefix.
		 * @param bool                                $with_progress      The flag to identify if we need to process progress metas.
		 * @param string                              $home_url           The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler       File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger             Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $previous_db_prefix,
			bool $with_progress,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			global $wpdb;

			$this->db_prefix_previous = $previous_db_prefix;
			$this->db_prefix_current  = $wpdb->prefix;

			$this->with_progress = $with_progress;

			$this->meta_keys_with_regexps = $this->with_progress
				? array_merge( self::META_KEYS_GROUP_REGEXP, self::META_KEYS_COURSE_REGEXP )
				: self::META_KEYS_GROUP_REGEXP;

			parent::__construct( $home_url, $file_handler, $logger );
		}

		/**
		 * Imports users. Existing users are skipped.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			foreach ( $this->get_file_lines() as $item ) {
				$this->processed_items_count++;

				$user_id = $this->find_user_id_by_login_or_email(
					$item['wp_user']['user_login'],
					$item['wp_user']['user_email']
				);

				if ( is_null( $user_id ) ) {
					$this->create_user( $item );
				}

				Learndash_Admin_Import::clear_wpdb_query_cache();
			}
		}

		/**
		 * Creates a user.
		 *
		 * @since 4.3.0
		 *
		 * @param array $data User data.
		 *
		 * @return void
		 */
		protected function create_user( array $data ): void {
			$user_id = wp_insert_user(
				$this->map_user_data( $data['wp_user'] )
			);

			if ( is_wp_error( $user_id ) ) {
				return;
			}

			$this->imported_items_count++;

			$this->update_user_password_with_hash( $user_id, $data['wp_user']['user_pass'] );
			$this->update_user_meta( $user_id, $data['wp_user_meta'] );
		}

		/**
		 * Finds a user by login or email.
		 *
		 * @since 4.3.0
		 *
		 * @param string $login User login.
		 * @param string $email User email.
		 *
		 * @return int|null User ID or NULL.
		 */
		protected function find_user_id_by_login_or_email( string $login, string $email ): ?int {
			$user = get_user_by( 'login', $login );

			if ( $user ) {
				return $user->ID;
			}

			$user = get_user_by( 'email', $email );

			if ( $user ) {
				return $user->ID;
			}

			return null;
		}

		/**
		 * Maps user data to insert.
		 *
		 * @since 4.3.0
		 *
		 * @param array $data Exported data.
		 *
		 * @return array
		 */
		protected function map_user_data( array $data ): array {
			$old_id = intval( $data['ID'] );
			unset( $data['ID'] );

			$user_data               = $data;
			$user_data['meta_input'] = array(
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID => $old_id,
			);

			return $user_data;
		}

		/**
		 * Updates user's password with hash.
		 *
		 * @since 4.3.0
		 *
		 * @param int    $user_id       User ID.
		 * @param string $password_hash Password Hash.
		 *
		 * @return void
		 */
		protected function update_user_password_with_hash( int $user_id, string $password_hash ): void {
			global $wpdb;

			$wpdb->update(
				$wpdb->users,
				array(
					'user_pass'           => $password_hash,
					'user_activation_key' => '',
				),
				array(
					'ID' => $user_id,
				)
			);

			clean_user_cache( $user_id );
		}

		/**
		 * Updates user's meta.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $user_id User ID.
		 * @param array $metas   Metas.
		 *
		 * @return void
		 */
		protected function update_user_meta( int $user_id, array $metas ): void {
			foreach ( $metas as $meta_key => $meta_values ) {
				$this->process_prefixed_meta( $meta_key );
				$this->process_regexp_meta( $meta_key, $meta_values );
				$this->process_progress_meta( $meta_key, $meta_values );

				foreach ( $meta_values as $meta_value ) {
					update_user_meta( $user_id, $meta_key, $meta_value );
				}
			}
		}

		/**
		 * Processes metas with regexps.
		 *
		 * @since 4.3.0
		 *
		 * @param string $meta_key    Meta key.
		 * @param array  $meta_values Meta value.
		 *
		 * @return void
		 */
		protected function process_regexp_meta( string &$meta_key, array &$meta_values ): void {
			foreach ( $this->meta_keys_with_regexps as $meta_key_regexp ) {
				if ( 1 === preg_match( "/$meta_key_regexp/", $meta_key, $post_ids ) ) {
					$new_post_id = $this->get_new_post_id_by_old_post_id(
						intval( $post_ids[1] )
					);

					$meta_key = str_replace(
						self::REGEXP_POST_ID,
						(string) $new_post_id,
						$meta_key_regexp
					);

					$meta_values = in_array( $meta_key_regexp, self::META_KEYS_GROUP_REGEXP, true )
						? array( $new_post_id )
						: $meta_values;

					break;
				}
			}
		}

		/**
		 * Processes metas with a db prefix.
		 *
		 * @since 4.3.0
		 *
		 * @param string $meta_key Meta key.
		 *
		 * @return void
		 */
		protected function process_prefixed_meta( string &$meta_key ): void {
			if ( $this->db_prefix_previous === $this->db_prefix_current ) {
				return;
			}

			foreach ( self::META_KEYS_WITH_DB_PREFIX as $meta_key_with_db_prefix ) {
				if ( $this->db_prefix_previous . $meta_key_with_db_prefix === $meta_key ) {
					$meta_key = $this->db_prefix_current . $meta_key_with_db_prefix;
					break;
				}
			}
		}

		/**
		 * Processes metas related to progress.
		 *
		 * @since 4.3.0
		 *
		 * @param string $meta_key    Meta key.
		 * @param array  $meta_values Meta value.
		 *
		 * @return void
		 */
		protected function process_progress_meta( string $meta_key, array &$meta_values ): void {
			if ( ! $this->with_progress || self::META_KEY_COURSE_PROGRESS !== $meta_key ) {
				return;
			}

			$meta_values = array(
				$this->map_course_progress_meta_value( $meta_values[0] ),
			);
		}

		/**
		 * Maps the course progress meta values with new post ids.
		 *
		 * @since 4.3.0
		 *
		 * @param array $meta_value Meta value.
		 *
		 * @return array Meta value.
		 */
		protected function map_course_progress_meta_value( array $meta_value ): array {
			$result = array();

			foreach ( $meta_value as $course_id => $course_progress ) {
				$course_progress['last_id'] = $this->get_new_post_id_by_old_post_id(
					$course_progress['last_id']
				);

				if ( is_null( $course_progress['last_id'] ) ) {
					continue;
				}

				$old_lesson_id_new_lesson_id_hash = array();

				// Map new ids for lessons.
				$lessons = array();
				foreach ( $course_progress['lessons'] as $lesson_id => $value ) {
					$new_lesson_id = $this->get_new_post_id_by_old_post_id( $lesson_id );

					if ( is_null( $new_lesson_id ) ) {
						continue 2; // Skip the course progress processing.
					}

					$lessons[ $new_lesson_id ]                      = $value;
					$old_lesson_id_new_lesson_id_hash[ $lesson_id ] = $new_lesson_id;
				}

				// Map new ids for topics.
				$topics = array();
				foreach ( $course_progress['topics'] as $lesson_id => $lesson_topics ) {
					$new_lesson_id            = $old_lesson_id_new_lesson_id_hash[ $lesson_id ];
					$topics[ $new_lesson_id ] = array();

					foreach ( $lesson_topics as $topic_id => $value ) {
						$new_topic_id = $this->get_new_post_id_by_old_post_id( $topic_id );

						if ( is_null( $new_topic_id ) ) {
							continue 3; // Skip the course progress processing.
						}

						$topics[ $new_lesson_id ][ $new_topic_id ] = $value;
					}
				}

				$course_progress['lessons'] = $lessons;
				$course_progress['topics']  = $topics;

				$result[ $this->get_new_post_id_by_old_post_id( $course_id ) ] = $course_progress;
			}

			return $result;
		}
	}
}
