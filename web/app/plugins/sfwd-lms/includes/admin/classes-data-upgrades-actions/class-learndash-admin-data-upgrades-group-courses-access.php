<?php
/**
 * LearnDash Data Upgrades for Group Courses Access.
 *
 * Creates 'access' activity records for users enrolled via groups.
 * This ensures group-enrolled users appear in LearnDash Reports.
 *
 * @since 5.0.1
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Data_Upgrades' ) ) {
	return;
}

if ( ! class_exists( 'Learndash_Admin_Data_Upgrades_Group_Courses_Access' ) ) {
	/**
	 * Class LearnDash Data Upgrades for Group Courses Access.
	 *
	 * @since 5.0.1
	 * @uses Learndash_Admin_Data_Upgrades
	 */
	class Learndash_Admin_Data_Upgrades_Group_Courses_Access extends Learndash_Admin_Data_Upgrades {
		/**
		 * Protected constructor for class.
		 *
		 * @since 5.0.1
		 */
		protected function __construct() {
			$this->data_slug = 'group-courses-access';
			parent::__construct();
			parent::register_upgrade_action();
		}

		/**
		 * Show data upgrade row for this instance.
		 *
		 * @since 5.0.1
		 *
		 * @return void
		 */
		public function show_upgrade_action() {
			?>
			<tr id="learndash-data-upgrades-container-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-container">
				<td class="learndash-data-upgrades-button-container">
					<button class="learndash-data-upgrades-button button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ); ?>" data-slug="<?php echo esc_attr( $this->data_slug ); ?>">
					<?php esc_html_e( 'Upgrade', 'learndash' ); ?>
					</button>
				</td>
				<td class="learndash-data-upgrades-status-container">
					<span class="learndash-data-upgrades-name">
					<?php
					printf(
						// translators: placeholder: Group, Course.
						esc_html_x( 'Upgrade %1$s %2$s Access Data', 'placeholder: Group, Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</span>
					<p>
					<?php
					printf(
						// translators: placeholder: group, courses.
						esc_html_x( 'This upgrade will create activity records for users enrolled in %1$s to ensure they appear in %2$s reports.', 'placeholder: group, courses', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'groups' ) ),
						esc_html( learndash_get_custom_label_lower( 'course' ) )
					);
					?>
					</p>
					<p>
					<?php
					printf(
						// translators: placeholders: group, course, group.
						esc_html_x( 'Note: New %1$s enrollments will automatically track the correct enrollment date. For existing enrollments, the enrollment date will be based on when the user joined the %1$s or when the %2$s was added to it, whichever is later.', 'placeholders: group, course, group', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'group' ) ),
						esc_html( learndash_get_custom_label_lower( 'course' ) )
					);
					?>
					</p>
					<p class="description"><?php echo esc_html( $this->get_last_run_info() ); ?></p>

					<?php
					$show_progress        = false;
					$this->transient_key  = $this->data_slug;
					$this->transient_data = (array) $this->get_transient( $this->transient_key );

					if ( ! empty( $this->transient_data ) ) {
						if ( isset( $this->transient_data['result_count'] ) ) {
							$this->transient_data['result_count'] = Cast::to_int( $this->transient_data['result_count'] );
						} else {
							$this->transient_data['result_count'] = 0;
						}

						if ( isset( $this->transient_data['total_count'] ) ) {
							$this->transient_data['total_count'] = Cast::to_int( $this->transient_data['total_count'] );
						} else {
							$this->transient_data['total_count'] = 0;
						}

						if (
							! empty( $this->transient_data['result_count'] )
							&& ! empty( $this->transient_data['total_count'] )
							&& $this->transient_data['result_count'] !== $this->transient_data['total_count']
						) {
							$show_progress = true;
							?>
							<p id="learndash-data-upgrades-continue-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-continue">
								<input type="checkbox" name="learndash-data-upgrades-continue" value="1" />
								<?php esc_html_e( 'Continue previous upgrade processing?', 'learndash' ); ?>
							</p>
							<?php
						}
					}

					$progress_style       = 'display:none;';
					$progress_meter_style = '';
					$progress_label       = '';
					$progress_slug        = '';

					if ( true === $show_progress ) {
						$progress_style = '';
						$data           = $this->transient_data;
						$data           = $this->build_progress_output( $data );

						if (
							isset( $data['progress_percent'] )
							&& ! empty( $data['progress_percent'] )
						) {
							$progress_meter_style = 'width: ' . $data['progress_percent'] . '%';
						}

						if (
							isset( $data['progress_label'] )
							&& ! empty( $data['progress_label'] )
						) {
							$progress_label = Cast::to_string( $data['progress_label'] );
						}

						if (
							isset( $data['progress_slug'] )
							&& ! empty( $data['progress_slug'] )
						) {
							$progress_slug = 'progress-label-' . $data['progress_slug'];
						}
					}
					?>
					<div style="<?php echo esc_attr( $progress_style ); ?>" class="meter learndash-data-upgrades-status">
						<div class="progress-meter">
							<span class="progress-meter-image" style="<?php echo esc_attr( $progress_meter_style ); ?>"></span>
						</div>
						<div class="progress-label <?php echo esc_attr( $progress_slug ); ?>"><?php echo esc_attr( $progress_label ); ?></div>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Class method for the AJAX update logic.
		 *
		 * @since 5.0.1
		 *
		 * @param array<string, mixed> $data Post data from AJAX call.
		 *
		 * @return array<string, mixed> $data Post data from AJAX call.
		 */
		public function process_upgrade_action( $data = [] ) {
			$this->init_process_times();

			if (
				! isset( $data['nonce'] )
				|| empty( $data['nonce'] )
			) {
				return $data;
			}

			if ( ! wp_verify_nonce( Cast::to_string( $data['nonce'] ), 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ) {
				return $data;
			}

			if ( ! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
				return $data;
			}

			$this->transient_key = $this->data_slug;

			// Initialize on first call.
			if (
				isset( $data['init'] )
				&& '1' === $data['init']
			) {
				unset( $data['init'] );

				if (
					! isset( $data['continue'] )
					|| 'true' !== $data['continue']
				) {
					$this->transient_data = [
						'result_count'              => 0,
						'total_count'               => 0,
						'progress_started'          => time(),
						'progress_user'             => get_current_user_id(),
						'process_groups'            => [],
						'current_group'             => null,
						'current_group_users'       => [],
						'current_group_user_offset' => 0,
					];

					$this->query_groups();
				} else {
					$this->transient_data = (array) $this->get_transient( $this->transient_key );
				}

				$this->set_option_cache( $this->transient_key, $this->transient_data );
			} else {
				// Continue processing.
				$this->transient_data = (array) $this->get_transient( $this->transient_key );

				if (
					empty( $this->transient_data['process_groups'] )
					&& empty( $this->transient_data['current_group'] )
				) {
					$this->query_groups();
				}

				// Process current group in batches if we have one.
				if ( ! empty( $this->transient_data['current_group'] ) ) {
					$group_complete = $this->process_group_batch( $this->transient_data['current_group'] );

					if ( $group_complete ) {
						// Group is complete, move to next.
						$this->transient_data['current_group']             = null;
						$this->transient_data['current_group_users']       = [];
						$this->transient_data['current_group_user_offset'] = 0;

						if ( ! isset( $this->transient_data['result_count'] ) ) {
							$this->transient_data['result_count'] = 0;
						}

						++$this->transient_data['result_count'];
					}

					$this->set_option_cache( $this->transient_key, $this->transient_data );

					if ( $this->out_of_timer() ) {
						return $this->build_progress_output( $data );
					}
				}

				// Process next group if available.
				if (
					! empty( $this->transient_data['process_groups'] )
					&& empty( $this->transient_data['current_group'] )
				) {
					foreach ( $this->transient_data['process_groups'] as $group_idx => $group_id ) {
						$group_id = intval( $group_id );

						// Initialize group processing.
						$this->transient_data['current_group']             = $group_id;
						$this->transient_data['current_group_users']       = learndash_get_groups_user_ids( $group_id );
						$this->transient_data['current_group_user_offset'] = 0;

						unset( $this->transient_data['process_groups'][ $group_idx ] );

						$this->set_option_cache( $this->transient_key, $this->transient_data );

						// Process first batch of this group.
						$group_complete = $this->process_group_batch( $group_id );

						if ( $group_complete ) {
							// Group completed in one batch, move to next.
							$this->transient_data['current_group']             = null;
							$this->transient_data['current_group_users']       = [];
							$this->transient_data['current_group_user_offset'] = 0;

							if ( ! isset( $this->transient_data['result_count'] ) ) {
								$this->transient_data['result_count'] = 0;
							}

							++$this->transient_data['result_count'];
						}

						$this->set_option_cache( $this->transient_key, $this->transient_data );

						if ( $this->out_of_timer() ) {
							break;
						}

						// If group is not complete, break to continue processing it next time.
						if ( ! $group_complete ) {
							break;
						}
					}
				}
			}

			$data = $this->build_progress_output( $data );

			// If complete, save the last run info.
			if (
				isset( $data['progress_percent'] )
				&& 100 === Cast::to_int( $data['progress_percent'] )
			) {
				$this->set_last_run_info( $data );
				$data['last_run_info'] = $this->get_last_run_info();
				$this->remove_transient( $this->transient_key );
			}

			return $data;
		}

		/**
		 * Query groups to process.
		 *
		 * @since 5.0.1
		 *
		 * @return void
		 */
		protected function query_groups() {
			if ( ! isset( $this->transient_data['paged'] ) ) {
				$this->transient_data['paged'] = 1;
			} else {
				++$this->transient_data['paged'];
			}

			$query_args = [
				'post_type'      => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
				'post_status'    => 'publish',
				'posts_per_page' => LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE,
				'paged'          => $this->transient_data['paged'],
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			];

			/** This filter is documented in includes/admin/classes-data-upgrades-actions/class-learndash-admin-data-upgrades-course-access-list-convert.php */
			$query_args = apply_filters( 'learndash_data_upgrade_query', $query_args, $this->data_slug );

			$query = new WP_Query( $query_args );

			$this->transient_data['total_count']    = $query->found_posts;
			$this->transient_data['process_groups'] = $query->posts;
		}

		/**
		 * Process a single group in batches - create activity records for user/course combinations.
		 *
		 * Processes users in batches to avoid memory and database query limits when groups
		 * have many users and/or courses.
		 *
		 * @since 5.0.1
		 *
		 * @param int $group_id Group ID.
		 *
		 * @return bool True if group processing is complete, false if more batches remain.
		 */
		protected function process_group_batch( int $group_id ): bool {
			// Get course IDs once per group (cached in transient if needed).
			if ( ! isset( $this->transient_data['current_group_courses'] ) ) {
				$course_ids                                    = learndash_group_enrolled_courses( $group_id );
				$this->transient_data['current_group_courses'] = $course_ids;
			} else {
				$course_ids = $this->transient_data['current_group_courses'];
			}

			if ( empty( $course_ids ) ) {
				return true; // No courses, group is complete.
			}

			// Get user IDs if not already loaded.
			if ( empty( $this->transient_data['current_group_users'] ) ) {
				$this->transient_data['current_group_users'] = learndash_get_groups_user_ids( $group_id );
			}

			$user_ids = $this->transient_data['current_group_users'];

			if ( empty( $user_ids ) ) {
				return true; // No users, group is complete.
			}

			// Determine batch size for users.
			/**
			 * Filters the batch size for processing users within a group during migration.
			 *
			 * @since 5.0.1
			 *
			 * @param int $batch_size Number of users to process per batch. Default 100.
			 * @param int $group_id    Group ID being processed.
			 *
			 * @return int Number of users to process per batch.
			 */
			$user_batch_size = (int) apply_filters( 'learndash_group_courses_access_user_batch_size', 100, $group_id );

			// Get current offset.
			$offset = isset( $this->transient_data['current_group_user_offset'] )
				? (int) $this->transient_data['current_group_user_offset']
				: 0;

			// Get batch of users.
			$user_batch = array_slice( $user_ids, $offset, $user_batch_size );

			if ( empty( $user_batch ) ) {
				// No more users to process, group is complete.
				unset( $this->transient_data['current_group_courses'] );
				return true;
			}

			// Process this batch of users with all courses.
			learndash_bulk_create_course_access_activities( $user_batch, $course_ids, $group_id );

			// Update offset for next batch.
			$this->transient_data['current_group_user_offset'] = $offset + count( $user_batch );

			// Check if we've processed all users.
			if ( $this->transient_data['current_group_user_offset'] >= count( $user_ids ) ) {
				// Group is complete, clean up transient data.
				unset( $this->transient_data['current_group_courses'] );
				return true;
			}

			// More batches remain.
			return false;
		}

		/**
		 * Build progress output for UI.
		 *
		 * @since 5.0.1
		 *
		 * @param array<string, mixed> $data Array of existing data elements.
		 *
		 * @return array<string, mixed> Data with progress info.
		 */
		protected function build_progress_output( $data = [] ) {
			$data['result_count'] = isset( $this->transient_data['result_count'] ) ? intval( $this->transient_data['result_count'] ) : 0;
			$data['total_count']  = isset( $this->transient_data['total_count'] ) ? intval( $this->transient_data['total_count'] ) : 0;

			if ( ! empty( $data['total_count'] ) ) {
				$data['progress_percent'] = ( $data['result_count'] / $data['total_count'] ) * 100;
			} else {
				// No groups to process means we're done.
				$data['progress_percent'] = 100;
			}

			if ( 100 === (int) $data['progress_percent'] ) {
				$progress_status       = __( 'Complete', 'learndash' );
				$data['progress_slug'] = 'complete';
			} elseif (
				defined( 'DOING_AJAX' )
				&& DOING_AJAX
			) {
				$progress_status       = __( 'In Progress', 'learndash' );
				$data['progress_slug'] = 'in-progress';
			} else {
				$progress_status       = __( 'Incomplete', 'learndash' );
				$data['progress_slug'] = 'in-complete';
			}

			$data['progress_label'] = sprintf(
				// translators: placeholders: progress status, result count, total count, Groups.
				esc_html_x( '%1$s: %2$d of %3$d %4$s', 'placeholders: progress status, result count, total count, Groups', 'learndash' ),
				$progress_status,
				$data['result_count'],
				$data['total_count'],
				LearnDash_Custom_Label::get_label( 'groups' )
			);

			return $data;
		}
	}
}

add_action(
	'learndash_data_upgrades_init',
	function () {
		Learndash_Admin_Data_Upgrades_Group_Courses_Access::add_instance();
	}
);
