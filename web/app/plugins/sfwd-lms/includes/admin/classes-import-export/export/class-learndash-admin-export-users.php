<?php
/**
 * LearnDash Admin Export Users.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export_Chunkable' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Users' ) &&
	! class_exists( 'Learndash_Admin_Export_Users' )
) {
	/**
	 * Class LearnDash Admin Export Users.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Users extends Learndash_Admin_Export_Chunkable {
		use Learndash_Admin_Import_Export_Users;

		const PROGRESS_META_KEYS = array(
			'_sfwd-course_progress',
			'_sfwd-quizzes',
			'course_points',
		);

		const COURSE_META_KEYS_REGEX = array(
			'course_(\d*)_access_from',
			'learndash_course_expired_(\d*)',
			'course_completed_(\d*)',
			'completed_(\d*)',
		);

		const GROUP_META_KEYS_REGEX = array(
			'learndash_group_leaders_(\d*)',
			'learndash_group_users_(\d*)',
		);

		/**
		 * Regular expression for meta keys that we don't need to export.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $ignored_meta_keys_regex;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param bool                                $with_progress   The flag to identify if we need to export progress.
		 * @param bool                                $groups_exported The flag to identify if we need to export groups meta.
		 * @param Learndash_Admin_Export_File_Handler $file_handler    File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger          Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			bool $with_progress,
			bool $groups_exported,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->with_progress           = $with_progress;
			$this->ignored_meta_keys_regex = implode(
				'|',
				array_merge(
					! $with_progress ? self::COURSE_META_KEYS_REGEX : array(),
					! $groups_exported ? self::GROUP_META_KEYS_REGEX : array()
				)
			);

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns data to export by chunks.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			/** Users @var WP_User[] $users Users. */
			$users = get_users(
				array(
					'fields' => 'all',
					'number' => $this->get_chunk_size_rows(),
					'offset' => $this->offset_rows,
				)
			);

			if ( empty( $users ) ) {
				return '';
			}

			$result = '';

			foreach ( $users as $user ) {
				$wp_user         = (array) $user->data;
				$wp_user['role'] = ! empty( $user->roles ) ? $user->roles[0] : '';
				unset( $wp_user['user_url'], $wp_user['user_activation_key'] );

				$user_data = array(
					'wp_user'      => $wp_user,
					'wp_user_meta' => $this->get_user_metadata( $user->ID ),
				);

				/**
				 * Filters the user object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $user_data User object.
				 *
				 * @return array User object.
				 */
				$user_data = apply_filters( 'learndash_export_user_object', $user_data );

				$result .= wp_json_encode( $user_data ) . PHP_EOL;
			}

			$this->increment_offset_rows();

			return $result;
		}

		/**
		 * Returns user's metadata.
		 *
		 * @since 4.3.0
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return array The list of post meta.
		 */
		protected function get_user_metadata( int $user_id ): array {
			$user_meta = get_user_meta( $user_id );

			if ( ! is_array( $user_meta ) ) {
				return array();
			}

			if ( ! $this->with_progress ) {
				$user_meta = array_diff_key( $user_meta, array_flip( self::PROGRESS_META_KEYS ) );
			}

			foreach ( $user_meta as $meta_key => &$meta_values ) {
				if (
					! empty( $this->ignored_meta_keys_regex ) &&
					1 === preg_match( "/$this->ignored_meta_keys_regex/", $meta_key )
				) {
					unset( $user_meta[ $meta_key ] );

					continue;
				}

				$meta_values = array_map( 'maybe_unserialize', $meta_values );
			}

			return $user_meta;
		}
	}
}
