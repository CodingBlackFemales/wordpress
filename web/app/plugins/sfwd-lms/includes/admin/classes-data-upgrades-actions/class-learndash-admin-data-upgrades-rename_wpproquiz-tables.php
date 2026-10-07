<?php
/**
 * LearnDash Data Upgrades for WPProQuiz DB Table rename.
 *
 * @since 2.6.0
 * @package LearnDash\Data_Upgrades
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_Rename_WPProQuiz_Tables' ) ) ) {

	/**
	 *  Class LearnDash Data Upgrades for WPProQuiz DB Table rename.
	 *
	 * @since 2.6.0
	 * @uses Learndash_Admin_Data_Upgrades
	 */
	class Learndash_Admin_Data_Upgrades_Rename_WPProQuiz_Tables extends Learndash_Admin_Data_Upgrades {

		/**
		 * Settings array
		 *
		 * @var array $settings
		 */
		protected $settings = null;

		/**
		 * Tables base
		 *
		 * @var array
		 */
		public $tables_base = array();

		/**
		 * Tables lists
		 *
		 * @var array
		 */
		public $tables_lists = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 2.6.0
		 */
		protected function __construct() {
			$this->data_slug = 'rename-wpproquiz-tables';
			parent::__construct();
			parent::register_upgrade_action();
		}

		/**
		 * Init settings
		 *
		 * @since 3.1.0
		 */
		public function init_settings() {
			$this->settings = $this->get_data_settings( $this->data_slug );

			$_data_settings_changed = false;
			if ( ! isset( $this->settings['prefixes'] ) ) {
				$ld_prior_version = $this->get_data_settings( 'prior_version' );

				/**
				 * On a new install we have do do a secondary check. The ld prior version logic was only added
				 * in LD 2.3.0. If this prior to that we need to ensure we don't treat it as a 'new' install'.
				 */
				if ( ( empty( $ld_prior_version ) ) || ( '0.0.0.0' === $ld_prior_version ) || ( 'new' === $ld_prior_version ) ) {
					// Check if we can find the WPProQuiz tables.
					$this->settings['prefixes'] = array(
						'current' => LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT,
						'alt'     => LEARNDASH_LMS_DATABASE_PREFIX_SUB,
					);
					$this->build_tables_lists();
					$legacy_proquiz_tables = $this->check_tables_lists( $this->tables_lists['wp_'] );

					// If we find the WPProQuiz table with the legacy prefix we set the show upgrade to allow future prefix changes.
					if ( ( isset( $legacy_proquiz_tables['quiz_master']['exists'] ) ) && ( true === $legacy_proquiz_tables['quiz_master']['exists'] ) ) {
						$this->settings['show_upgrade'] = true;
					} else {
						// If we don't find the legacy WPProQuiz table we assume this is a new install and don't show the upgrade section.
						$this->settings['show_upgrade'] = false;
						$this->settings['prefixes']     = array(
							'current' => LEARNDASH_LMS_DATABASE_PREFIX_SUB,
							'alt'     => LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT,
						);
					}

					$_data_settings_changed = true;

				} else {
					$_data_settings_changed         = true;
					$this->settings['show_upgrade'] = true;
					$this->settings['prefixes']     = array(
						'current' => LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT,
						'alt'     => LEARNDASH_LMS_DATABASE_PREFIX_SUB,
					);
				}
			}
			if ( ! isset( $this->settings['show_upgrade'] ) ) {
				$_data_settings_changed         = true;
				$this->settings['show_upgrade'] = true;
			}

			if ( true === $_data_settings_changed ) {
				$this->set_data_settings( $this->data_slug, $this->settings );
			}

			return $this->settings;
		}


		/**
		 * Show data upgrade row for this instance.
		 *
		 * @since 2.6.0
		 */
		public function show_upgrade_action() {
			global $wpdb;

			$this->init_settings();

			/**
			 * Filters whether to show upgrade section or not.
			 *
			 * @since 3.1.4
			 *
			 * @param boolean $show_upgrades Whether to show upgrades section or not.
			 * @param string  $data_slug    Data Slug used to identify each instance.
			 */
			if ( true !== apply_filters( 'learndash_show_upgrade_section', $this->settings['show_upgrade'], $this->data_slug ) ) {
				return;
			}
			$show_upgrade = $this->settings['show_upgrade'];

			$this->build_tables_lists();

			if ( ( ! isset( $this->tables_lists[ $this->settings['prefixes']['current'] ] ) ) || ( ! isset( $this->tables_lists[ $this->settings['prefixes']['alt'] ] ) ) ) {
				$show_upgrade = false;
			}

			$this->tables_lists[ $this->settings['prefixes']['current'] ] = $this->check_tables_lists( $this->tables_lists[ $this->settings['prefixes']['current'] ] );
			foreach ( $this->tables_lists[ $this->settings['prefixes']['current'] ] as $table_key => $table_set ) {
				if ( true !== $table_set['exists'] ) {
					$show_upgrade = false;
					break;
				}
			}

			// Check that tables for the current prefix are NOT present.
			$this->tables_lists[ $this->settings['prefixes']['alt'] ] = $this->check_tables_lists( $this->tables_lists[ $this->settings['prefixes']['alt'] ] );
			foreach ( $this->tables_lists[ $this->settings['prefixes']['alt'] ] as $table_key => $table_set ) {
				if ( true === $table_set['exists'] ) {
					$show_upgrade = false;
					break;
				}
			}
			?>
			<tr id="learndash-data-upgrades-container-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-container">
				<td class="learndash-data-upgrades-button-container">
					<button class="learndash-data-upgrades-button button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ); ?>" data-slug="<?php echo esc_attr( $this->data_slug ); ?>">
					<?php
						esc_html_e( 'Upgrade', 'learndash' );
					?>
					</button>
					<button style="display:none" class="learndash-data-upgrades-button-<?php echo esc_attr( $this->data_slug ); ?>-reload button button-secondary">
					<?php
						esc_html_e( 'Reload', 'learndash' );
					?>
					</button>
				</td>
				<td class="learndash-data-upgrades-status-container">
					<span class="learndash-data-upgrades-name">
					<?php
						esc_html_e( 'Rename WPProQuiz DB Tables', 'learndash' );
					?>
					</span>
					<p>
					<?php
						echo wp_kses_post(
							__( 'This upgrade will rename the existing WPProQuiz database tables to a new name inline with LearnDash standards.<br /><strong>It is recommended you set your site to maintenance mode before performing this upgrade.</strong>', 'learndash' )
						);
					?>
					</p>
					<p id="learndash-data-upgrades-<?php echo esc_attr( $this->data_slug ); ?>-prefix" class="learndash-data-upgrades-prefix">
						<input type="radio" id="learndash-data-upgrades-prefix-wp" name="learndash-data-upgrades-prefix" data-current-prefix="<?php echo esc_attr( $this->settings['prefixes']['current'] ); ?>" value="<?php echo esc_attr( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT ); ?>" <?php checked( $this->settings['prefixes']['current'], LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT ); ?> />
						<label
						<?php
						if ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT === $this->settings['prefixes']['current'] ) {
							echo ' style=" font-weight: bold; " ';
						}
						?>
						for="learndash-data-upgrades-prefix-wp">
						<?php
						// translators: placeholders: default prefix, example table name.
						echo sprintf( esc_html_x( '%1$s - Legacy ProQuiz table prefix. Ex. %2$s', 'placeholders: default prefix, example table name', 'learndash' ), 'wp_', '<code>' . esc_html( $wpdb->prefix . LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT ) . 'pro_quiz_question</code>' );
						?>
						</label><br />

						<input type="radio" id="learndash-data-upgrades-prefix-learndash" name="learndash-data-upgrades-prefix" data-current-prefix="<?php echo esc_attr( $this->settings['prefixes']['current'] ); ?>" value="<?php echo esc_attr( LEARNDASH_LMS_DATABASE_PREFIX_SUB ); ?>" <?php checked( $this->settings['prefixes']['current'], LEARNDASH_LMS_DATABASE_PREFIX_SUB ); ?> />
						<label
						<?php
						if ( LEARNDASH_LMS_DATABASE_PREFIX_SUB === $this->settings['prefixes']['current'] ) {
							echo ' style=" font-weight: bold; " ';
						}
						?>
						for="learndash-data-upgrades-prefix-learndash">
						<?php
						// translators: placeholders: learndash prefix, example table name.
						echo sprintf( esc_html_x( '%1$s - LearnDash ProQuiz table prefix. Ex. %2$s', 'placeholders: learndash prefix, example table name', 'learndash' ), 'learndash_', '<code>' . esc_html( $wpdb->prefix . LEARNDASH_LMS_DATABASE_PREFIX_SUB ) . 'pro_quiz_question</code>' );
						?>
						</label>
					</p>
					<p><a href="#" class="learndash-data-upgrades-show-tables"><?php esc_html_e( 'show tables', 'learndash' ); ?></a></p>

					<div id="learndash-data-upgrades-<?php echo esc_attr( $this->data_slug ); ?>-show-tables-list" style="display:none;">

						<table id="tables-list-<?php echo esc_attr( $this->settings['prefixes']['alt'] ); ?>">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Source Tables', 'learndash' ); ?></th>
								<th></th>
								<th><?php esc_html_e( 'Destination Tables', 'learndash' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ( $this->tables_lists[ $this->settings['prefixes']['current'] ] as $table_key => $table_set_current ) {
							if ( isset( $this->tables_lists[ $this->settings['prefixes']['alt'] ][ $table_key ] ) ) {
								$table_set_alt = $this->tables_lists[ $this->settings['prefixes']['alt'] ][ $table_key ];
							}

							if ( ( isset( $table_set_alt['exists'] ) ) && ( true !== $table_set_alt['exists'] ) ) {
								$table_set_alt_style = ' style="color:green;" ';
							} else {
								$table_set_alt_style = ' style="color:red;" ';
							}

							?>
								<tr><td>
									<?php
									echo esc_html( $table_set_current['name'] );
									if ( true !== $table_set_current['exists'] ) {
										echo ' <span style="color:red;">(missing)</span>';
									}
									?>
									</td><td>=></td><td>
									<?php
									echo esc_html( $table_set_alt['name'] ); // @phpstan-ignore-line
									if ( true === $table_set_alt['exists'] ) { // @phpstan-ignore-line
										echo ' <span style="color:red;">(exists)</span>';
									}
									?>
									</td></tr>
								<?php
						}
						?>
						<tbody>
						</table>
						<table id="tables-list-<?php echo esc_attr( $this->settings['prefixes']['current'] ); ?>" style="display:none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Source Tables', 'learndash' ); ?></th>
								<th></th>
								<th><?php esc_html_e( 'Destination Tables', 'learndash' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ( $this->tables_lists[ $this->settings['prefixes']['alt'] ] as $table_key => $table_set_current ) {
							if ( isset( $this->tables_lists[ $this->settings['prefixes']['current'] ][ $table_key ] ) ) {
								$table_set_alt = $this->tables_lists[ $this->settings['prefixes']['current'] ][ $table_key ];
							}

							if ( ( isset( $table_set_alt['exists'] ) ) && ( true !== $table_set_alt['exists'] ) ) {
								$table_set_alt_style = ' style="color:green;" ';
							} else {
								$table_set_alt_style = ' style="color:red;" ';
							}

							?>
								<tr><td>
									<?php
									echo esc_html( $table_set_current['name'] );
									if ( true !== $table_set_current['exists'] ) {
										echo ' <span style="color:red;">(missing)</span>';
									}
									?>
									</td><td>=></td><td>
									<?php
									echo esc_html( $table_set_alt['name'] ); // @phpstan-ignore-line
									if ( true === $table_set_alt['exists'] ) { // @phpstan-ignore-line
										echo ' <span style="color:red;">(exists)</span>';
									}
									?>
									</td></tr>
								<?php
						}
						?>
						<tbody>
						</table>

					</div>

					<p class="description"><?php echo esc_html( $this->get_last_run_info() ); ?></p>

					<?php
					$progress_style       = 'display:none;';
					$progress_meter_style = '';
					$progress_label       = '';
					$progress_slug        = '';

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
		 * Check table lists
		 *
		 * @since 3.1.0
		 *
		 * @param array $table_list Array of table names to check.
		 *
		 * @return array $table_list
		 */
		public function check_tables_lists( $table_list = array() ) {

			if ( ! empty( $table_list ) ) {
				$return_status = true;

				foreach ( $table_list as $table_key => &$table_set ) {
					if ( isset( $table_set['checked'] ) ) {
						continue;
					}
					if ( ! isset( $table_set['name'] ) ) {
						continue;
					}

					$table_set['exists']  = $this->check_table_exists( $table_set['name'] );
					$table_set['checked'] = time();
				}

				return $table_list;
			}

			return array();
		}

		/**
		 * Check single table exists
		 *
		 * @since 3.1.0
		 *
		 * @param string $table_name Single table name to check.
		 *
		 * @return boolean
		 */
		public function check_table_exists( $table_name = '' ) {
			global $wpdb;

			if ( ! empty( $table_name ) ) {
				$db_table_name = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $table_name ) . "'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $db_table_name == $table_name ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Build tables list
		 *
		 * @since 3.1.0
		 */
		public function build_tables_lists() {
			global $wpdb;

			$this->tables_base = LDLMS_DB::get_tables_base( 'wpproquiz' );

			foreach ( $this->tables_base as $table_key => $table_name ) {
				foreach ( $this->settings['prefixes'] as $table_sub_prefix ) {
					if ( ! isset( $this->tables_lists[ $table_sub_prefix ] ) ) {
						$this->tables_lists[ $table_sub_prefix ] = array();
					}
					$this->tables_lists[ $table_sub_prefix ][ $table_key ] = array(
						'name' => $wpdb->prefix . $table_sub_prefix . 'pro_quiz_' . $table_name,
					);
				}
			}
		}

		/**
		 * Class method for the AJAX update logic
		 * This function will determine what users need to be converted. Then the course and quiz functions
		 * will be called to convert each individual user data set.
		 *
		 * @since 2.6.0
		 *
		 * @param  array $data Post data from AJAX call.
		 *
		 * @return array $data Post data from AJAX call.
		 */
		public function process_upgrade_action( $data = array() ) {
			global $wpdb;

			$this->init_process_times();

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( wp_verify_nonce( $data['nonce'], 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ) {
					$this->init_settings();

					$this->transient_key = $this->data_slug;

					if ( ( isset( $data['init'] ) ) && ( true == $data['init'] ) ) {
						unset( $data['init'] );

						/**
						 * Transient_data is used to store the local server state information and will
						 * saved in a transient type options variable.
						 */
						$this->transient_data                     = array();
						$this->transient_data['proquiz_prefix']   = esc_attr( $data['proquiz_prefix'] );
						$this->transient_data['result_count']     = 0;
						$this->transient_data['current_item']     = array();
						$this->transient_data['progress_started'] = time();
						$this->transient_data['progress_user']    = get_current_user_id();
						$this->transient_data['completed_items']  = array();

						$this->query_items();
						$this->set_option_cache( $this->transient_key, $this->transient_data );
					} else {

						$this->transient_data = $this->get_transient( $this->transient_key );

						if ( ( isset( $this->transient_data['process_items'] ) ) && ( ! empty( $this->transient_data['process_items'] ) ) ) {
							foreach ( $this->transient_data['process_items'] as $old_table => $new_table ) {
								$item_complete = $this->rename_wpproquiz_table( $old_table, $new_table );
								if ( false !== $item_complete ) {
									if ( ! isset( $this->transient_data['completed_items'] ) ) {
										$this->transient_data['completed_items'] = array();
									}

									unset( $this->transient_data['process_items'][ $old_table ] );
									$this->transient_data['result_count'] = (int) $this->transient_data['result_count'] + 1;
								} else {
									exit();
								}

								$this->set_option_cache( $this->transient_key, $this->transient_data );

								if ( $this->out_of_timer() ) {
									break;
								}
							}
						}
					}
				}
			}

			$data = $this->build_progress_output( $data );

			// If we are at 100% then we update the internal data settings so other parts of LD know the upgrade has been run.
			if ( ( isset( $data['progress_percent'] ) ) && ( 100 == $data['progress_percent'] ) ) {

				$data['completed_items'] = $this->transient_data['completed_items'];

				if ( LEARNDASH_LMS_DATABASE_PREFIX_SUB === $this->transient_data['proquiz_prefix'] ) {
					$data['prefixes'] = array(
						'current' => LEARNDASH_LMS_DATABASE_PREFIX_SUB,
						'alt'     => LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT,
					);

				} elseif ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT === $this->transient_data['proquiz_prefix'] ) {
					$data['prefixes'] = array(
						'current' => LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT,
						'alt'     => LEARNDASH_LMS_DATABASE_PREFIX_SUB,
					);
				}

				$this->set_last_run_info( $data );

				$data['last_run_info'] = $this->get_last_run_info();

				$this->remove_transient( $this->transient_key );
			}

			return $data;
		}

		/**
		 * Common function to query needed items.
		 *
		 * @since 2.6.0
		 */
		protected function query_items() {
			$process_tables = array();

			$this->build_tables_lists();

			foreach ( $this->settings['prefixes'] as $prefix_key => $prefix ) {
				if ( ( isset( $this->tables_lists[ $prefix ] ) ) && ( ! empty( $this->tables_lists[ $prefix ] ) ) ) {
					$this->tables_lists[ $prefix ] = $this->check_tables_lists( $this->tables_lists[ $prefix ] );
				}
			}

			if ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT === $this->transient_data['proquiz_prefix'] ) {
				$dest_prefix   = LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT;
				$source_prefix = LEARNDASH_LMS_DATABASE_PREFIX_SUB;
			} elseif ( LEARNDASH_LMS_DATABASE_PREFIX_SUB === $this->transient_data['proquiz_prefix'] ) {
				$dest_prefix   = LEARNDASH_LMS_DATABASE_PREFIX_SUB;
				$source_prefix = LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT;
			}

			foreach ( $this->tables_lists[ $source_prefix ] as $source_table_key => $source_table_set ) { // @phpstan-ignore-line
				if ( ( isset( $source_table_set['exists'] ) ) && ( true === $source_table_set['exists'] ) ) {
					if ( ( isset( $this->tables_lists[ $dest_prefix ][ $source_table_key ]['exists'] ) ) && ( true !== $this->tables_lists[ $dest_prefix ][ $source_table_key ]['exists'] ) ) { // @phpstan-ignore-line
						$process_tables[ $source_table_set['name'] ] = $this->tables_lists[ $dest_prefix ][ $source_table_key ]['name']; // @phpstan-ignore-line
					}
				}
			}

			$this->transient_data['total_count']   = count( $process_tables );
			$this->transient_data['process_items'] = $process_tables;
		}

		/**
		 * Common function to build the returned data progress output.
		 *
		 * @since 2.6.0
		 *
		 * @param array $data Array of existing data elements.
		 *
		 * @return array or data.
		 */
		protected function build_progress_output( $data = array() ) {
			if ( isset( $this->transient_data['result_count'] ) ) {
				$data['result_count'] = intval( $this->transient_data['result_count'] );
			} else {
				$data['result_count'] = 0;
			}

			if ( isset( $this->transient_data['total_count'] ) ) {
				$data['total_count'] = intval( $this->transient_data['total_count'] );
			} else {
				$data['total_count'] = 0;
			}

			if ( ! empty( $data['total_count'] ) ) {
				$data['progress_percent'] = ( $data['result_count'] / $data['total_count'] ) * 100;
			} else {
				$data['progress_percent'] = 100;
			}

			if ( 100 == $data['progress_percent'] ) {
					$progress_status       = __( 'Complete', 'learndash' );
					$data['progress_slug'] = 'complete';
			} else {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$progress_status       = __( 'In Progress', 'learndash' );
					$data['progress_slug'] = 'in-progress';
				} else {
					$progress_status       = __( 'Incomplete', 'learndash' );
					$data['progress_slug'] = 'in-complete';
				}
			}

			$data['progress_label'] = sprintf(
				// translators: placeholders: result count, total count.
				esc_html_x( '%1$s: %2$d of %3$d ProQuiz Table', 'placeholders: progress status, result count, total count', 'learndash' ),
				$progress_status,
				$data['result_count'],
				$data['total_count']
			);

			return $data;
		}

		/**
		 * Convert WPProQuiz Database to new name
		 *
		 * @since 3.1.0
		 *
		 * @param string $old_table Old table name.
		 * @param string $new_table New table name.
		 *
		 * @return mixed New table name (string) if complete, false if not.
		 */
		private function rename_wpproquiz_table( $old_table = '', $new_table = '' ) {
			global $wpdb;

			$ret_rename = $wpdb->query( sprintf( 'ALTER TABLE `%s` RENAME `%s`', esc_attr( $old_table ), esc_attr( $new_table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

			if ( ( $this->check_table_exists( $new_table ) ) && ( ! $this->check_table_exists( $old_table ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_data_upgrades_init',
	function() {
		Learndash_Admin_Data_Upgrades_Rename_WPProQuiz_Tables::add_instance();
	}
);
