<?php
/**
 * LearnDash Settings Section for Import/Export.
 *
 * @since 4.3.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Settings_Section' ) && ! class_exists( 'LearnDash_Settings_Section_Import_Export' ) ) {
	/**
	 * Class LearnDash Settings Section for Import Export.
	 *
	 * @since 4.3.0
	 */
	class LearnDash_Settings_Section_Import_Export extends LearnDash_Settings_Section {
		/**
		 * Export file handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Export_File_Handler
		 */
		private $export_file_handler;

		/**
		 * Protected constructor for class.
		 *
		 * @since 4.3.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_import_export';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_import_export';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_import_export';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Import / Export', 'learndash' );

			parent::__construct();

			$this->export_file_handler = new Learndash_Admin_Export_File_Handler();

			add_action( 'admin_notices', array( $this, 'add_notices' ) );

			add_filter( 'learndash_admin_settings_data', array( $this, 'learndash_admin_settings_data' ), 30 );

			add_filter(
				'learndash_admin_settings_advanced_sections_with_hidden_metaboxes',
				function( array $section_keys ) {
					$section_keys[] = $this->settings_section_key;

					return $section_keys;
				}
			);
		}

		/**
		 * Add script data to array.
		 *
		 * @since 4.3.0
		 *
		 * @param array $script_data Script data array to be sent out to browser.
		 *
		 * @return array $script_data
		 */
		public function learndash_admin_settings_data( array $script_data = array() ): array {
			$script_data['import_file_size_limit'] = wp_max_upload_size();

			$script_data['import_file_empty'] = esc_html__( 'Please select a valid file to import.', 'learndash' );

			$script_data['import_file_size_limit_exceeded'] = sprintf(
				// translators: placeholder: max file size.
				__(
					'The file you are trying to upload is too large. Max size allowed: %s',
					'learndash'
				),
				size_format( $script_data['import_file_size_limit'] )
			);

			$script_data['in_progress_label'] = esc_html__( 'in progress', 'learndash' );
			$script_data['uploading_label']   = esc_html__( 'Uploading...', 'learndash' );

			return $script_data;
		}

		/**
		 * Adds notices.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function add_notices(): void {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['section-advanced'] ) || $this->settings_section_key !== $_GET['section-advanced'] ) {
				return;
			}
			?>
				<div
					id="learndash-export-success"
					class="notice notice-success"
					style="<?php echo esc_attr( $this->export_file_handler->zip_archive_exists() ? '' : 'display: none' ); ?>;"
				>
					<p>
						<?php
						echo esc_html(
							sprintf(
								// translators: %s: date and time.
								__( 'Export archive created on %s.', 'learndash' ),
								$this->export_file_handler->get_zip_archive_time_created()
							)
						);
						?>
						<a href="<?php echo esc_url( $this->export_file_handler->get_zip_archive_url() ); ?>">
							<?php esc_html_e( 'Download', 'learndash' ); ?>
						</a>
					</p>
				</div>

				<div id="learndash-export-info" class="notice notice-info is-dismissible" style="display: none">
					<p>
						<?php esc_html_e( 'Export is in the processing queue. Please reload this page to see the export status.', 'learndash' ); ?>
					</p>
				</div>

				<div id="learndash-export-error" class="notice notice-error is-dismissible" style="display: none;">
					<p></p>
				</div>

				<div id="learndash-import-info" class="notice notice-info is-dismissible" style="display: none">
					<p>
						<?php esc_html_e( 'Import is in the processing queue. Please reload this page to see the import status.', 'learndash' ); ?>
					</p>
				</div>

				<div id="learndash-import-error" class="notice notice-error is-dismissible" style="display: none;">
					<p></p>
				</div>
			<?php
		}

		/**
		 * Returns settings array.
		 *
		 * @since 4.3.0
		 *
		 * @return array
		 */
		protected function get_settings(): array {
			$settings = array();

			foreach ( LDLMS_Post_Types::get_post_types() as $post_type ) {
				$post_type_key = LDLMS_Post_Types::get_post_type_key( $post_type );

				$setting = array(
					'is_post_type' => true,
					'label'        => LearnDash_Custom_Label::get_label( $post_type_key ),
					'value'        => $post_type,
					'items'        => array(
						array(
							'label' => __( 'Posts', 'learndash' ),
							'value' => 'post_types',
						),
					),
				);

				if ( isset( Learndash_Admin_Export_Post_Type_Settings::POST_TYPE_SETTING_SECTIONS[ $post_type_key ] ) ) {
					$setting['items'][] = array(
						'label' => __( 'Settings', 'learndash' ),
						'value' => 'post_type_settings',
					);
				}

				$settings[] = $setting;
			}

			$settings[] = array(
				'is_post_type' => false,
				'label'        => __( 'User', 'learndash' ),
				'value'        => 'users',
				'items'        => array(
					array(
						'label' => __( 'Profiles', 'learndash' ),
						'value' => 'profiles',
					),
					array(
						'label' => __( 'Progress', 'learndash' ),
						'value' => 'progress',
					),
				),
			);

			$settings[] = array(
				'is_post_type' => false,
				'label'        => __( 'Other', 'learndash' ),
				'value'        => 'other',
				'items'        => array(
					array(
						'label' => __( 'Global Settings', 'learndash' ),
						'value' => 'settings',
					),
				),
			);

			/**
			 * Filters export settings.
			 *
			 * @since 4.3.0
			 *
			 * @param array $settings Export settings.
			 *
			 * @return array Export settings.
			 */
			return apply_filters( 'learndash_export_settings', $settings );
		}

		/**
		 * Shows settings content.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function show_meta_box(): void {
			$export_is_in_progress = Learndash_Admin_Action_Scheduler::is_task_in_progress(
				Learndash_Admin_Import_Export::SCHEDULER_EXPORT_GROUP_NAME,
				Learndash_Admin_Export_Handler::SCHEDULER_ACTION_NAME
			);
			$import_is_in_progress = Learndash_Admin_Action_Scheduler::is_task_in_progress(
				Learndash_Admin_Import_Export::SCHEDULER_IMPORT_GROUP_NAME,
				Learndash_Admin_Import_Handler::SCHEDULER_ACTION_NAME
			);
			?>
			<div id="learndash-import-export" class="sfwd_options">
				<div>
					<h4>
						<?php esc_html_e( 'What do you want to export?', 'learndash' ); ?>
					</h4>

					<div
						id="learndash-export-container"
						data-action-name="<?php echo esc_attr( Learndash_Admin_Export_Handler::AJAX_ACTION_NAME ); ?>"
						data-action-nonce="<?php echo esc_attr( wp_create_nonce( Learndash_Admin_Export_Handler::AJAX_ACTION_NAME ) ); ?>"
					>
						<div>
							<input
								type="radio"
								id="learndash-export-all"
								name="export_type"
								value="<?php echo esc_attr( Learndash_Admin_Export_Handler::TYPE_ALL ); ?>"
								class="learndash-export-type"
								autocomplete="off"
								checked
							/>
							<label for="learndash-export-all">
								<?php esc_html_e( 'Everything', 'learndash' ); ?>
							</label>

							<input
								type="radio"
								id="learndash-export-selected"
								name="export_type"
								value="<?php echo esc_attr( Learndash_Admin_Export_Handler::TYPE_SELECTED ); ?>"
								class="learndash-export-type"
								autocomplete="off"
							/>
							<label for="learndash-export-selected">
								<?php esc_html_e( 'I want to select', 'learndash' ); ?>
							</label>
						</div>

						<div class="learndash-export-items" style="display: none">
							<?php foreach ( $this->get_settings() as $setting ) : ?>
								<div class="learndash-export-item">
									<label class="learndash-export-item-group-label">
										<?php echo esc_html( $setting['label'] ); ?>
									</label>

									<?php foreach ( $setting['items'] as $sub_setting ) : ?>
										<input
											type="checkbox"
											name="<?php echo esc_attr( $setting['is_post_type'] ? $sub_setting['value'] : $setting['value'] ); ?>"
											value="<?php echo esc_attr( $setting['is_post_type'] ? $setting['value'] : $sub_setting['value'] ); ?>"
											class="learndash-export-input"
											id="learndash-export-item-<?php echo esc_attr( $setting['value'] . '-' . $sub_setting['value'] ); ?>"
											autocomplete="off"
										/>
										<label
											class="learndash-export-item-label"
											for="learndash-export-item-<?php echo esc_attr( $setting['value'] . '-' . $sub_setting['value'] ); ?>"
										>
											<?php echo esc_html( $sub_setting['label'] ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							<?php endforeach; ?>
						</div>

						<button
							id="learndash-export-button"
							class="button button-primary"
							<?php echo esc_attr( $export_is_in_progress ? 'disabled' : '' ); ?>
						>
							<?php $export_is_in_progress ? esc_html_e( 'Export in progress', 'learndash' ) : esc_html_e( 'Export', 'learndash' ); ?>
						</button>
					</div>
				</div>

				<div
					id="learndash-import-container"
					data-action-name="<?php echo esc_attr( Learndash_Admin_Import_Handler::AJAX_ACTION_NAME ); ?>"
					data-action-nonce="<?php echo esc_attr( wp_create_nonce( Learndash_Admin_Import_Handler::AJAX_ACTION_NAME ) ); ?>"
				>
					<h4>
						<?php esc_html_e( 'What do you want to import?', 'learndash' ); ?>
					</h4>

					<div>
						<div>
							<input autocomplete="off" type="file" id="learndash-import-file" accept=".zip">
						</div>

						<button
							id="learndash-import-button"
							class="button button-primary"
							<?php echo esc_attr( $import_is_in_progress ? 'disabled' : '' ); ?>
						>
							<?php $import_is_in_progress ? esc_html_e( 'Import in progress', 'learndash' ) : esc_html_e( 'Import', 'learndash' ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	array( LearnDash_Settings_Section_Import_Export::class, 'add_section_instance' ),
	9
);
