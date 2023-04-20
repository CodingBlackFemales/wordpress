<?php
/**
 * LearnDash class for displaying the course wizard.
 *
 * @package    LearnDash
 * @since      4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Course_Wizard' ) ) {
	/**
	 * Course wizard class.
	 */
	class LearnDash_Course_Wizard {

		const PLAYLIST_PROCESS_SERVER_ENDPOINT   = 'https://licensing.learndash.com/services/wp-json/learndash-playlist-parser/v1';
		const PLAYLIST_PROCESS_SERVER_SSL_VERIFY = true; // false only for local testing.

		const HANDLE = 'learndash-course-wizard';

		const LICENSE_KEY       = 'nss_plugin_license_sfwd_lms';
		const LICENSE_EMAIL_KEY = 'nss_plugin_license_email_sfwd_lms';

		const STEP_URL_PROCESS   = 'ld_cw_process';
		const STEP_COURSE_CONFIG = 'ld_cw_config';

		/**
		 * Init the course wizard registering WP hooks
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_footer-edit.php', array( $this, 'add_wizard_button' ) );

			add_action( 'admin_post_ld_course_wizard_playlist_process', array( $this, 'process_url_action' ) );
			add_action( 'admin_post_ld_course_wizard_create_course', array( $this, 'create_course_action' ) );
		}

		/**
		 * Add the course wizard button to the course list table
		 */
		public function add_wizard_button() {
			$screen = get_current_screen();
			if ( is_object( $screen ) && 'edit-' . learndash_get_post_type_slug( 'course' ) === $screen->id ) {
				?>
				<script>
					window.onload = function() {
						var settingsElem = document.getElementsByClassName("ld-global-header-new-settings");
						var newEntityElem =  document.getElementsByClassName("global-new-entity-button");
						if (settingsElem.length === 1 && newEntityElem.length === 1) {
							var wizardLink = document.createElement('a');
							var wizardText = document.createTextNode('<?php esc_html_e( 'Create from Video Playlist', 'learndash' ); ?>');
							wizardLink.setAttribute('href', "<?php echo esc_url( admin_url( 'admin.php?page=' . self::HANDLE ) ); ?>");
							wizardLink.setAttribute('class', 'global-new-entity-button');
							wizardLink.setAttribute('style', 'margin-right: 10px;');
							wizardLink.appendChild(wizardText);

							// add element
							settingsElem[0].insertBefore(wizardLink, newEntityElem[0]);
						}
					};
				</script>
				<?php
			}
		}

		/**
		 * Register the admin menu for the course wizard
		 */
		public function register_menu() {
			add_menu_page(
				__( 'Course Creation Wizard', 'learndash' ),
				__( 'Course Creation Wizard', 'learndash' ),
				LEARNDASH_ADMIN_CAPABILITY_CHECK,
				self::HANDLE,
				array( $this, 'render' )
			);

			// hide the admin menu item, the page stays available.
			remove_menu_page( self::HANDLE );
		}

		/**
		 * Register the script
		 */
		public function enqueue_scripts() {
			$screen = get_current_screen();
			if ( is_object( $screen ) && 'toplevel_page_' . self::HANDLE === $screen->id ) {
				wp_register_style(
					'ld-tailwindcss',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/css/ld-tailwind.css',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN
				);
				wp_enqueue_style( 'ld-tailwindcss' );
				wp_style_add_data( 'ld-tailwindcss', 'rtl', 'replace' );

				wp_register_script(
					self::HANDLE,
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-course-wizard' . learndash_min_asset() . '.js',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true
				);

				wp_localize_script(
					self::HANDLE,
					'ldCourseWizard',
					array(
						'valid_recurring_paypal_day_max'   => learndash_billing_cycle_field_frequency_max( 'D' ),
						'valid_recurring_paypal_week_max'  => learndash_billing_cycle_field_frequency_max( 'W' ),
						'valid_recurring_paypal_month_max' => learndash_billing_cycle_field_frequency_max( 'M' ),
						'valid_recurring_paypal_year_max'  => learndash_billing_cycle_field_frequency_max( 'Y' ),
						'buttons'                          => array(
							'youtube' => array(
								'label'     => __( 'Load data from YouTube', 'learndash' ),
								'img_class' => 'ld-w-8 ld-inline ld-mr-1',
								'img_src'   => esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/youtube_icon.png' ),
								'img_alt'   => esc_attr(
									__( 'YouTube icon', 'learndash' )
								),
							),
							'vimeo'   => array(
								'label'     => __( 'Load data from Vimeo', 'learndash' ),
								'img_class' => 'ld-w-6 ld-inline ld-mr-1',
								'img_src'   => esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/vimeo_icon.png' ),
								'img_alt'   => esc_attr(
									__( 'Vimeo icon', 'learndash' )
								),
							),
							'wistia'  => array(
								'label'     => __( 'Load data from Wistia', 'learndash' ),
								'img_class' => 'ld-w-7 ld-inline',
								'img_src'   => esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/wistia_icon.png' ),
								'img_alt'   => esc_attr(
									__( 'Wistia icon', 'learndash' )
								),
							),
							'default' => array(
								'label' => __( 'Load', 'learndash' ),
							),
						),
					)
				);
				wp_enqueue_script( self::HANDLE );
			}
		}

		/**
		 * Process the playlist URL
		 */
		public function process_url_action() {
			if ( ! isset( $_REQUEST['ld_course_wizard_playlist_process'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ld_course_wizard_playlist_process'] ) ), 'ld_course_wizard_playlist_process' ) ) {
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) ); // wrong call.
			}

			$playlist_url = isset( $_REQUEST['playlist_url'] ) ? esc_url_raw( wp_unslash( $_REQUEST['playlist_url'] ) ) : '';
			if ( empty( $playlist_url ) ) {
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) ); // no playlist URL.
			}

			// process the URL.
			$this->process_url( $playlist_url );
		}

		/**
		 * Create the course
		 */
		public function create_course_action() {
			if ( ! isset( $_REQUEST['ld_course_wizard_create_course'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ld_course_wizard_create_course'] ) ), 'ld_course_wizard_create_course' ) ) {
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) ); // wrong call.
			}

			$playlist_url = isset( $_REQUEST['playlist_url'] ) ? esc_url_raw( wp_unslash( $_REQUEST['playlist_url'] ) ) : '';
			if ( empty( $playlist_url ) ) {
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) ); // no playlist URL.
			}

			$transient_name = $this->get_processing_transient_key( $playlist_url );
			$transient_data = get_transient( $transient_name );
			if ( empty( $transient_data['playlist_data'] ) ) {
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) ); // no transient data.
			}

			// create the course.
			$this->create_course_from_playlist(
				$transient_data['playlist_data'],
				isset( $_REQUEST['course_price_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['course_price_type'] ) ) : '',
				isset( $_REQUEST['course_disable_lesson_progression'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['course_disable_lesson_progression'] ) ) : '',
				isset( $_REQUEST['course_price'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['course_price'] ) ) : '',
				isset( $_REQUEST['course_price_billing_number'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['course_price_billing_number'] ) ) : '',
				isset( $_REQUEST['course_price_billing_interval'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['course_price_billing_interval'] ) ) : ''
			);

			// delete the transient and redirect to the course page.
			$this->delete_processing_data( $playlist_url );
			learndash_safe_redirect( admin_url( 'edit.php?post_type=' . learndash_get_post_type_slug( 'course' ) ) );
		}


		/**
		 * Call the API to process the playlist URL.
		 *
		 * @param string $playlist_url The playlist URL.
		 */
		public function process_url( $playlist_url ) {
			// reset the transient.
			$this->delete_processing_data( $playlist_url );
			$return_url = admin_url( 'admin.php?page=' . self::HANDLE . '&u=' . rawurlencode( $playlist_url ) );

			// request server to process the playlist url.
			$args = array(
				'sslverify' => self::PLAYLIST_PROCESS_SERVER_SSL_VERIFY,
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
				'body'      => wp_json_encode(
					array(
						'playlist_url'  => rawurlencode( $playlist_url ),
						'license_email' => get_option( self::LICENSE_EMAIL_KEY ),
						'license_key'   => get_option( self::LICENSE_KEY ),
						'return_url'    => rawurlencode( $return_url ),
					)
				),
			);

			$request = wp_remote_post( self::PLAYLIST_PROCESS_SERVER_ENDPOINT . '/process_url', $args );
			$body    = json_decode( wp_remote_retrieve_body( $request ) );

			if ( ! $body || ! empty( $body->message ) ) {
				$this->update_processing_data( $playlist_url, 'error_message', ! empty( $body->message ) ? $body->message : __( 'Error on access LearnDash service. Please try it again in a few minutes.', 'learndash' ) );
				learndash_safe_redirect( $return_url );
			}

			// next step: redirect to OAuth URL.
			$this->update_processing_data( $playlist_url, 'error_message', null );
			wp_redirect( $body->process_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}

		/**
		 * Get the transient key for the processing data.
		 *
		 * @param string $playlist_url Playlist URL.
		 * @return string Transient name.
		 */
		private function get_processing_transient_key( $playlist_url ) {
			return 'ld_cw_' . md5( $playlist_url );
		}

		/**
		 * Update the temporary processing data
		 *
		 * @param  string $playlist_url Playlist URL.
		 * @param  string $key         Key to update.
		 * @param  mixed  $value       Value to update.
		 */
		private function update_processing_data( $playlist_url, $key, $value ) {
			$transient_name = $this->get_processing_transient_key( $playlist_url );
			$transient_data = get_transient( $transient_name );
			if ( false === $transient_data ) {
				$transient_data = array();
			}
			$transient_data[ $key ] = $value;
			set_transient( $transient_name, $transient_data, DAY_IN_SECONDS );
		}

		/**
		 * Delete the temporary processing data
		 *
		 * @param  string $playlist_url Playlist URL.
		 */
		private function delete_processing_data( $playlist_url ) {
			$transient_name = $this->get_processing_transient_key( $playlist_url );
			delete_transient( $transient_name );
		}

		/**
		 * Get the temporary processing data, analyzing the current processing step.
		 */
		private function get_processing_data() {
			$process_data = array(
				'current_step'      => self::STEP_URL_PROCESS,
				'can_create_course' => false,
				'error_message'     => null,
				'playlist_data'     => null,
				'playlist_url'      => null,
				'try_again_url'     => null,
			);

			// check if playlist URL is set.
			$playlist_url = isset( $_GET['u'] ) ? esc_url_raw( wp_unslash( $_GET['u'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $playlist_url ) ) {
				return $process_data;
			}

			// get the transient data.
			$transient_name = $this->get_processing_transient_key( $playlist_url );
			$transient_data = get_transient( $transient_name );
			if ( false !== $transient_data ) {
				// in case of error, we need to delete the transient.
				if ( isset( $transient_data['error_message'] ) && ! empty( $transient_data['error_message'] ) ) {
					$this->delete_processing_data( $playlist_url );
					$process_data['error_message'] = $transient_data['error_message'];
				} elseif ( ! isset( $transient_data['playlist_data'] ) || empty( $transient_data['playlist_data'] ) ) {
					// we need to get the playlist data only if we are not loaded it yet.
					$url_data = $this->get_playlist_data( $playlist_url );

					// check if it's an error.
					if ( is_string( $url_data ) ) {
						$process_data['error_message'] = $url_data;
						$this->update_processing_data( $playlist_url, 'error_message', $process_data['error_message'] );
					} else {
						$process_data['playlist_data'] = $url_data;
						$this->update_processing_data( $playlist_url, 'playlist_data', $process_data['playlist_data'] );
					}
				} else {
					// we already have the data. Then, only update the current data.
					$process_data['playlist_data'] = $transient_data['playlist_data'];
				}
			} else {
				if ( ! isset( $_GET['refresh'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$process_data['error_message'] = __( 'Request expired. Please try it again.', 'learndash' );
				}
			}

			// define control data.
			if ( ! empty( $process_data['error_message'] ) || ! empty( $process_data['playlist_data'] ) ) {
				$process_data['current_step'] = self::STEP_COURSE_CONFIG;
			}
			$process_data['can_create_course'] = empty( $process_data['error_message'] ) && ! empty( $process_data['playlist_data'] );
			$process_data['playlist_url']      = $playlist_url;
			$process_data['try_again_url']     = add_query_arg(
				array(
					'u'       => $playlist_url,
					'refresh' => 1,
				),
				admin_url( 'admin.php?page=' . self::HANDLE )
			);

			return $process_data;
		}

		/**
		 * Get the playlist data.
		 *
		 * @param  string $playlist_url Playlist URL.
		 * @return array|string Playlist data or error message.
		 */
		private function get_playlist_data( $playlist_url ) {
			$encoded_playlist_url = rawurlencode( $playlist_url );

			$request = wp_remote_get(
				add_query_arg(
					array(
						'playlist_url'  => $encoded_playlist_url,
						'license_email' => get_option( self::LICENSE_EMAIL_KEY ),
						'license_key'   => get_option( self::LICENSE_KEY ),
						'return_url'    => rawurlencode( admin_url( 'admin.php?page=' . self::HANDLE . '&u=' . rawurlencode( $encoded_playlist_url ) ) ),
					),
					self::PLAYLIST_PROCESS_SERVER_ENDPOINT . '/url_data'
				),
				array(
					'sslverify' => self::PLAYLIST_PROCESS_SERVER_SSL_VERIFY,
				)
			);

			$body = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! $body || ! empty( $body->message ) ) {
				return isset( $body->message ) ? $body->message : __( 'Error on access LearnDash service. Please try it again in a few minutes.', 'learndash' );
			}
			return $body->playlist_data;
		}

		/**
		 * Render the course wizard page.
		 */
		public function render() {
			// get the current processing data.
			$process_data = $this->get_processing_data();
			?>
		<div class="ld-container ld-mx-auto">
			<div class="ld-flex ld-flex-wrap ld-flex-col ld-items-center">
				<div class="ld-flex ld-mt-6">
					<p class="ld-text-4xl">
						<?php
						echo sprintf(
							// translators: course.
							esc_html_x(
								'Create a %s from a video playlist.',
								'placeholder: course',
								'learndash'
							),
							esc_html( LearnDash_Custom_Label::label_to_lower( 'course' ) )
						);
						?>
					</p>
				</div>
				<div class="ld-flex ld-mt-2">
					<p class="ld-text-xl">
						<?php
						echo sprintf(
							// translators: course.
							esc_html_x(
								'You can use a YouTube Playlist, a Vimeo Showcase or a Wistia Project URL to create a LearnDash %s in a few minutes.',
								'placeholder: course',
								'learndash'
							),
							esc_html( LearnDash_Custom_Label::label_to_lower( 'course' ) )
						);
						?>
					</p>
				</div>

				<div class="ld-flex ld-flex-wrap ld-flex-col ld-items-center ld-mt-10 ld-w-full">
					<?php
					if ( ! learndash_is_learndash_license_valid() ) {
						?>
						<div class="notice notice-error">
							<p><?php echo esc_html__( 'Please activate your license to use this feature.', 'learndash' ); ?></p>
						</div>
						<?php
					} else {
						if ( self::STEP_URL_PROCESS === $process_data['current_step'] ) {
							$this->render_url_process_step( $process_data );
						} elseif ( self::STEP_COURSE_CONFIG === $process_data['current_step'] ) {
							$this->render_course_config_step( $process_data );
						}
					}
					?>
				</div>
				<div class="ld-flex ld-mt-8">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . learndash_get_post_type_slug( 'course' ) ) ); ?>" class="button">
						<?php
						echo sprintf(
							// translators: course.
							esc_html_x(
								'Back to %s list',
								'placeholder: course',
								'learndash'
							),
							esc_html( LearnDash_Custom_Label::label_to_lower( 'course' ) )
						);
						?>

					</a>
					<?php if ( $process_data['can_create_course'] ) { ?>
						<form id="ld_cw_create_course_form" method="post" action="<?php echo esc_url_raw( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'ld_course_wizard_create_course', 'ld_course_wizard_create_course' ); ?>
								<input type="hidden" name="action" value="ld_course_wizard_create_course"/>
								<input type="hidden" name="playlist_url" value="<?php echo esc_url( $process_data['playlist_url'] ); ?>"/>
								<button id="ld_cw_create_course_btn" style="margin-left: 10px" type="button" class="button button-primary"><?php echo esc_html__( 'Create the course', 'learndash' ); ?></button>
						</form>
					<?php } ?>
				</div>
			</div>
		</div>
					<?php
		}

		/**
		 * Render the URL process step.
		 *
		 * @param  array $process_data Processing data.
		 */
		private function render_url_process_step( $process_data ) {
			?>
			<div class="ld-flex ld-flex-wrap ld-flex-col ld-bg-white ld-box-content ld-w-1/2 ld-p-8 ld-rounded-md ld-shadow-lg" id="ld-course-wizard_<?php echo esc_attr( self::STEP_URL_PROCESS ); ?>">
				<form method="post" action="<?php echo esc_url_raw( admin_url( 'admin-post.php' ) ); ?>" class="ld-flex">
					<?php wp_nonce_field( 'ld_course_wizard_playlist_process', 'ld_course_wizard_playlist_process' ); ?>
					<input type="hidden" name="action" value="ld_course_wizard_playlist_process"/>
					<input placeholder="<?php echo esc_attr__( 'Enter playlist URL', 'learndash' ); ?>" id="ld_cw_playlist_url" class="ld-w-full ld-mr-3" type="url" required name="playlist_url" value="<?php echo esc_url( $process_data['playlist_url'] ); ?>" required />
					<button id="ld_cw_load_data_button" class="ld-rounded-md ld-bg-blue-600 ld-text-white ld-py-3 ld-px-4 ld-font-medium" type="submit">
						<span><?php echo esc_html__( 'Load', 'learndash' ); ?></span>
					</button>
				</form>
			</div>
			<?php
		}

		/**
		 * Render the course config step.
		 *
		 * @param  array $process_data Processing data.
		 */
		private function render_course_config_step( $process_data ) {
			?>
			<div class="ld-flex ld-flex-wrap ld-flex-col ld-bg-white ld-box-content ld-p-8 ld-border-4" id="ld-course-wizard_<?php echo esc_attr( self::STEP_COURSE_CONFIG ); ?>">
				<?php if ( ! empty( $process_data['error_message'] ) ) { ?>
					<div class="w-full ld-flex ld-bg-red-100 ld-py-5 ld-px-6 ld-mb-4 ld-text-base ld-text-red-700 ld-mb-3">
						<?php echo esc_html( $process_data['error_message'] ); ?>
					</div>
					<a class="button button-primary" href="<?php echo esc_url( $process_data['try_again_url'] ); ?>"><?php echo esc_html__( 'Try it again', 'learndash' ); ?></a>
				<?php } else { ?>
					<div class="w-full ld-flex ld-bg-green-100 ld-py-5 ld-px-6 ld-mb-4 ld-text-base ld-text-green-700 ld-mb-3">
						<?php echo esc_html( $this->generate_course_creation_message( $process_data ) ); ?>
					</div>

					<div class="w-full ld-flex ld-flex-wrap ld-flex-col ld-py-5 ld-mb-3">
						<div class="w-full ld-flex">
							<p class="ld-text-xl"><?php echo esc_html__( 'How users will gain access to the course?', 'learndash' ); ?></p>
						</div>
						<div class="w-full ld-flex ld-mt-2">
							<fieldset>
								<div>
									<input type="radio" id="ld_cw_course_price_type_open" name="ld_cw_course_price_type" value="open" checked="checked">
									<label class="ld-font-bold" for="ld_cw_course_price_type_open">
										<?php echo esc_html__( 'Open', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'The course is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'learndash' ); ?></p>
								</div>

								<div>
									<input type="radio" id="ld_cw_course_price_type_free" name="ld_cw_course_price_type" value="free">
									<label class="ld-font-bold" for="ld_cw_course_price_type_free">
										<?php echo esc_html__( 'Free', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'The course is protected. Registration and enrollment are required in order to access the content.', 'learndash' ); ?></p>
								</div>

								<div>
									<input type="radio" id="ld_cw_course_price_type_buy_now" name="ld_cw_course_price_type" value="paynow">
									<label class="ld-font-bold" for="ld_cw_course_price_type_buy_now">
										<?php echo esc_html__( 'Buy now', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'The course is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the course (one-time fee) in order to gain access.', 'learndash' ); ?></p>
									<div id="ld_cw_paynow_div" class="ld-ml-6 ld-py-4 ld-border-l-4" style="display: none">
										<div class="ld-ml-2">
											<label class="ld-mr-10" for="ld_cw_course_price_type_paynow_price">
												<?php echo esc_html__( 'Course Price', 'learndash' ); ?>
											</label>
											<input type="text" id="ld_cw_course_price_type_paynow_price" name="ld_cw_course_price_type_paynow_price" value="">
										</div>
									</div>
								</div>

								<div>
									<input type="radio" id="ld_cw_course_price_type_subscribe" name="ld_cw_course_price_type" value="subscribe">
									<label class="ld-font-bold" for="ld_cw_course_price_type_subscribe">
										<?php echo esc_html__( 'Recurring', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'The course is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the course (recurring fee) in order to gain access.', 'learndash' ); ?></p>
									<div id="ld_cw_subscribe_div" class="ld-ml-6 ld-py-4 ld-border-l-4" style="display: none">
										<div class="ld-ml-2">
											<label class="ld-mr-10" for="ld_cw_course_price_type_subscribe_price">
												<?php echo esc_html__( 'Course Price', 'learndash' ); ?>
											</label>
											<input type="text" id="ld_cw_course_price_type_subscribe_price" name="ld_cw_course_price_type_subscribe_price" value="">
										</div>
										<div class="ld-mt-4 ld-ml-2">
											<span class="ld-mr-10">
												<?php echo esc_html__( 'Billing Cycle', 'learndash' ); ?>
											</span>
											<input size=5 type="number" id="ld_cw_course_price_billing_number" min=0 max=0 name="ld_cw_course_price_billing_number" value="">
											<select id="ld_cw_course_price_billing_interval" name="ld_cw_course_price_billing_interval" value="">
												<option value=""><?php echo esc_html__( 'select interval', 'learndash' ); ?></option>
												<option value="D"><?php echo esc_html__( 'day(s)', 'learndash' ); ?></option>
												<option value="W"><?php echo esc_html__( 'week(s)', 'learndash' ); ?></option>
												<option value="M"><?php echo esc_html__( 'month(s)', 'learndash' ); ?></option>
												<option value="Y"><?php echo esc_html__( 'year(s)', 'learndash' ); ?></option>
											</select>
										</div>
									</div>
								</div>

								<div>
									<input type="radio" id="ld_cw_course_price_type_closed" name="ld_cw_course_price_type" value="closed">
									<label class="ld-font-bold" for="ld_cw_course_price_type_closed">
										<?php echo esc_html__( 'Closed', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'The course can only be accessed through admin enrollment (manual), group enrollment, or integration (shopping cart or membership) enrollment.', 'learndash' ); ?></p>
								</div>
							</fieldset>
						</div>
					</div>

					<div class="w-full ld-flex ld-flex-wrap ld-flex-col">
						<div class="w-full ld-flex">
							<p class="ld-text-xl"><?php echo esc_html__( 'How users will interact with the content?', 'learndash' ); ?></p>
						</div>
						<div class="w-full ld-flex ld-mt-2">
							<fieldset>
								<div>
									<input type="radio" id="ld_cw_course_progression_linear" name="ld_cw_course_progression" value="" checked="checked">
									<label class="ld-font-bold" for="ld_cw_course_progression_linear">
										<?php echo esc_html__( 'Linear form', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'Requires the user to progress through the course in the designated step sequence.', 'learndash' ); ?></p>
								</div>
								<div>
									<input type="radio" id="ld_cw_course_progression_free" name="ld_cw_course_progression" value="on">
									<label class="ld-font-bold" for="ld_cw_course_progression_free">
										<?php echo esc_html__( 'Free form', 'learndash' ); ?>
									</label>
									<p class="ld-ml-6 ld-mb-3"><?php esc_html_e( 'Allows the user to move freely through the course without following the designated step sequence.', 'learndash' ); ?></p>
								</div>
							</fieldset>
						</div>
					</div>
				<?php } ?>
			</div>
					<?php
		}

		/**
		 * Generate the course creation message based on the playlist_data
		 *
		 * @param  array $process_data Processing data.
		 * @return string|false The course generation message or false if playlist_data is empty.
		 */
		private function generate_course_creation_message( $process_data ) {
			if ( empty( $process_data['playlist_data'] ) ) {
				return false;
			}

			$course_name        = $process_data['playlist_data']->playlist_title;
			$course_qty_lessons = $process_data['playlist_data']->playlist_count;

			return sprintf(
				// translators: placeholders: course name, lessons qty description.
				esc_html_x( 'The course "%1$s" will be created with %2$s.', 'placeholders: course name, lessons qty description', 'learndash' ),
				$course_name,
				// translators: placeholders: number of lessons.
				sprintf( _n( '%s lesson', '%s lessons', $course_qty_lessons, 'learndash' ), $course_qty_lessons )
			);

		}

		/**
		 * Create a course based on the playlist.
		 *
		 * @param object $playlist_data - Playlist data. {
		 *   { @type string $playlist_title Playlist title.
		 *     @type string $playlist_description Playlist description.
		 *     @type int $playlist_count Playlist count.
		 *     @type array $playlist_items {
		 *       @type string $video_title Video title.
		 *       @type string $video_description Video description.
		 *       @type string $video_id Video id.
		 *       @type string $video_url Video url.
		 *     } Playlist items.
		 *   }
		 * @param string $course_price_type - Course price type.
		 * @param string $course_disable_lesson_progression - Course disable lesson progression.
		 * @param string $course_price - Course price, only for paynow and subscribe.
		 * @param string $course_price_billing_number - Course price billing cycle number, only for subscribe.
		 * @param string $course_price_billing_interval - Course price billing cycle interval, only for subscribe.
		 * @return int - Course post id
		 */
		private function create_course_from_playlist( $playlist_data, $course_price_type, $course_disable_lesson_progression, $course_price = null, $course_price_billing_number = null, $course_price_billing_interval = null ) {
			$course_post = array(
				'post_title'   => sanitize_text_field( $playlist_data->playlist_title ),
				'post_content' => sanitize_text_field( $playlist_data->playlist_description ),
				'post_type'    => learndash_get_post_type_slug( 'course' ),
				'post_status'  => 'publish',
			);
			$course_id   = wp_insert_post( $course_post );

			$shared_steps = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes';

			// lesson data.
			foreach ( $playlist_data->playlist_items as $video_data ) {
				$lesson_post = array(
					'post_title'   => sanitize_text_field( $video_data->video_title ),
					'post_content' => sanitize_text_field( $video_data->video_description ),
					'post_type'    => learndash_get_post_type_slug( 'lesson' ),
					'post_status'  => 'publish',
					'meta_input'   => array(
						'course_id' => $course_id,
					),
				);
				$lesson_id   = wp_insert_post( $lesson_post );
				learndash_update_setting( $lesson_id, 'lesson_video_enabled', 'on' );
				learndash_update_setting( $lesson_id, 'lesson_video_url', sanitize_text_field( $video_data->video_url ) );
				if ( ! $shared_steps ) {
					learndash_update_setting( $lesson_id, 'course', $course_id );
				}
			}

			learndash_update_setting( $course_id, 'course_price_type', sanitize_text_field( $course_price_type ) );
			learndash_update_setting( $course_id, 'course_disable_lesson_progression', sanitize_text_field( $course_disable_lesson_progression ) );
			if ( 'paynow' === $course_price_type || 'subscribe' === $course_price_type ) {
				learndash_update_setting( $course_id, 'course_price', sanitize_text_field( $course_price ) );
				if ( 'subscribe' === $course_price_type ) {
					learndash_update_setting( $course_id, 'course_price_billing_p3', sanitize_text_field( $course_price_billing_number ) );
					learndash_update_setting( $course_id, 'course_price_billing_t3', sanitize_text_field( $course_price_billing_interval ) );
				}
			}

			return $course_id;
		}
	}

}
