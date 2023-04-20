<?php
/**
 * LearnDash Settings Section for logs.
 *
 * @since 4.5.0
 *
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Section_Logs' ) && class_exists( 'LearnDash_Settings_Section' ) ) {
	/**
	 * Class LearnDash Settings Section for logs.
	 *
	 * @since 4.5.0
	 */
	class LearnDash_Settings_Section_Logs extends LearnDash_Settings_Section {
		const AJAX_ACTION_NAME_GET_LOG_CONTENT    = 'get_log_content';
		const AJAX_ACTION_NAME_DELETE_LOG_CONTENT = 'delete_log_content';

		/**
		 * Protected constructor for class.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_logs';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_logs';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'learndash_logs';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Logs', 'learndash' );

			add_action( 'admin_notices', array( $this, 'add_notices' ) );

			add_filter( 'learndash_admin_settings_data', array( $this, 'modify_frontend_settings' ) );

			add_action( 'wp_ajax_' . self::AJAX_ACTION_NAME_GET_LOG_CONTENT, array( $this, 'get_log_content' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION_NAME_DELETE_LOG_CONTENT, array( $this, 'delete_log_content' ) );

			parent::__construct();
		}

		/**
		 * Returns log content.
		 *
		 * @since 4.5.0
		 *
		 *  @return void Json response.
		 */
		public function get_log_content(): void {
			if (
				empty( $_POST['name'] ) ||
				empty( $_POST['nonce'] ) ||
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
					self::AJAX_ACTION_NAME_GET_LOG_CONTENT
				)
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Cheating?', 'learndash' ),
					)
				);
			}

			$logger = Learndash_Logger::get_instance(
				sanitize_text_field( wp_unslash( $_POST['name'] ) )
			);

			if ( ! $logger ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Log not found.', 'learndash' ),
					)
				);
			}

			wp_send_json_success(
				array(
					'content' => wp_kses_post( $logger->get_content() ),
				)
			);
		}

		/**
		 * Returns log content.
		 *
		 * @since 4.5.0
		 *
		 *  @return void Json response.
		 */
		public function delete_log_content(): void {
			if (
				empty( $_POST['name'] ) ||
				empty( $_POST['nonce'] ) ||
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
					self::AJAX_ACTION_NAME_DELETE_LOG_CONTENT
				)
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Cheating?', 'learndash' ),
					)
				);
			}

			$logger = Learndash_Logger::get_instance(
				sanitize_text_field( wp_unslash( $_POST['name'] ) )
			);

			if ( ! $logger ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Log not found.', 'learndash' ),
					)
				);
			}

			wp_send_json_success(
				array(
					'message' => $logger->delete_content()
						? __( 'Log content deleted.', 'learndash' )
						: __( 'Log content not deleted.', 'learndash' ),
				)
			);
		}

		/**
		 * Adds files protection notices.
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

			$instructions = Learndash_Logger::get_file_protection_instructions();
			?>
			<div class="notice notice-info is-dismissible" style="<?php echo esc_attr( ! empty( $instructions ) ? '' : 'display: none' ); ?>;">
				<?php
				if ( ! empty( $instructions ) ) {
					echo '<p>' . wp_kses_post( $instructions ) . '</p>';
				}
				?>
			</div>
			<?php
		}

		/**
		 * Adds data to settings object.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $data Script data array to be sent out to browser.
		 *
		 * @return array{
		 *     logs:array{
		 *         actions:array<string, array{name:string,nonce:string}>
		 *     }
		 * }
		 */
		public function modify_frontend_settings( array $data ): array {
			$data['logs'] = array(
				'actions' => array(
					'get_log_content'    => array(
						'name'  => self::AJAX_ACTION_NAME_GET_LOG_CONTENT,
						'nonce' => wp_create_nonce( self::AJAX_ACTION_NAME_GET_LOG_CONTENT ),
					),
					'delete_log_content' => array(
						'name'  => self::AJAX_ACTION_NAME_DELETE_LOG_CONTENT,
						'nonce' => wp_create_nonce( self::AJAX_ACTION_NAME_DELETE_LOG_CONTENT ),
					),
				),
			);

			return $data;
		}

		/**
		 * Initializes the settings fields.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function load_settings_fields(): void {
			$this->setting_option_fields = array();

			foreach ( Learndash_Logger::get_select_list() as $name => $label ) {
				$this->setting_option_fields[ $name ] = array(
					'name'    => $name,
					'type'    => 'checkbox-switch',
					'label'   => $label,
					'value'   => $this->setting_option_values[ $name ] ?? '',
					'options' => array(
						'yes' => '',
						''    => '',
					),
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Shows logs.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function show_meta_box(): void {
			?>
			<div id="learndash-options-logs" class="sfwd_options">
				<h4>
					<?php esc_html_e( 'Choose a log to view', 'learndash' ); ?>
				</h4>

				<select id="learndash-options-logs-select" autocomplete="off">
					<option></option>
					<?php foreach ( Learndash_Logger::get_select_list() as $name => $label ) : ?>
						<option value="<?php echo esc_attr( $name ); ?>">
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<div id="learndash-options-logs-list">
					<?php
					foreach ( Learndash_Logger::get_select_list() as $name => $label ) :
						$logger = Learndash_Logger::get_instance( $name );

						if ( ! $logger ) { // Should never occur, but safety first.
							continue;
						}
						?>
						<div data-name="<?php echo esc_attr( $name ); ?>" class="learndash-options-logs-list-item" style="display: none;">
							<h4>
								<?php echo esc_html( $label ); ?> <?php esc_html_e( 'log', 'learndash' ); ?>
							</h4>

							<textarea class="learndash-options-logs-list-item-content" readonly></textarea>

							<a
								class="button button-primary learndash-options-logs-download"
								style="<?php echo esc_attr( $logger->log_exists() ? '' : 'display: none' ); ?>"
								href="<?php echo esc_url( $logger->get_download_url() ); ?>"
							>
								<?php esc_html_e( 'Download', 'learndash' ); ?>
							</a>

							<a class="button learndash-options-logs-refresh" href="#">
								<?php esc_html_e( 'Refresh', 'learndash' ); ?>
							</a>

							<a class="button learndash-options-logs-delete" href="#" style="float: right">
								<?php esc_html_e( 'Delete', 'learndash' ); ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>

				<hr style="margin-top: 25px;"/>

				<h4>
					<?php esc_html_e( 'What do you want to log?', 'learndash' ); ?>
				</h4>
			</div>
			<?php
			parent::show_meta_box();
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	array( LearnDash_Settings_Section_Logs::class, 'add_section_instance' ),
	11
);
