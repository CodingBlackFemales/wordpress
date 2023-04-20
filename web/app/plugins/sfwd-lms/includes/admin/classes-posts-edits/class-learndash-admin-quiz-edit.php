<?php
/**
 * LearnDash Admin Quiz Edit.
 *
 * @since 2.4.0
 * @package LearnDash\Quiz\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Quiz_Edit' ) ) ) {

	/**
	 * Class LearnDash Admin Quiz Edit.
	 *
	 * @since 2.4.0
	 * @uses Learndash_Admin_Post_Edit
	 */
	class Learndash_Admin_Quiz_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * WPProQuiz Quiz instance.
		 * This is used to bridge the WPProQuiz to WP systems.
		 *
		 * @var object $pro_quiz_edit WPProQuiz instance.
		 */
		private $pro_quiz_edit = null;


		/**
		 * Quiz Builder instance.
		 *
		 * @var Learndash_Admin_Metabox_Quiz_Builder
		 */
		protected $quiz_builder = null;

		/**
		 * Object level flag to contain setting is Quiz Builder
		 * is to be used.
		 *
		 * @var boolean $use_quiz_builder
		 */
		private $use_quiz_builder = false;

		/**
		 * Common array set within init_quiz_edit and used by other class functions.
		 *
		 * @var array $_get;
		 */
		private $_get = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Common array set within init_quiz_edit and used by other class functions.
		 *
		 * @var array $_post;
		 */
		private $_post = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'quiz' );

			parent::__construct();
		}

		/**
		 * Initialize the ProQuiz Quiz being edited.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function init_quiz_edit( $post ) {
			if ( ! is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$quiz_pro_id = (int) learndash_get_setting( $post->ID, 'quiz_pro' );

				$this->_post = array( '1' );
				$this->_get  = array(
					'action'  => 'getEdit',
					'quizId'  => $quiz_pro_id,
					'post_id' => $post->ID,
				);

				if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->_get['templateLoad']   = 'yes';
					$this->_get['templateLoadId'] = absint( $_GET['templateLoadId'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				$pro_quiz            = new WpProQuiz_Controller_Quiz();
				$this->pro_quiz_edit = $pro_quiz->route(
					$this->_get,
					$this->_post
				);
			}
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 *
		 * @since 2.4.0
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {

				/** This filter is documented in includes/class-ld-semper-fi-module.php */
				if ( ! apply_filters( 'learndash_settings_metaboxes_legacy_quiz', LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ, $this->post_type ) ) {
					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-access-settings.php';

					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-progress-settings.php';

					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-display-content.php';

					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-results-display-content-options.php';

					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-admin-data-handling-settings.php';
				}

				parent::on_load();

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) == 'yes' ) {
					$this->use_quiz_builder = true;

					/**
					 * Filters whether to show quiz builder metabox or not.
					 *
					 * @since 2.6.0
					 *
					 * @param boolean $show_course_builder Whether to show quiz builder or not.
					 */
					if ( apply_filters( 'learndash_show_quiz_builder', $this->use_quiz_builder ) === true ) {
						$this->quiz_builder = Learndash_Admin_Metabox_Quiz_Builder::add_instance();
						$this->quiz_builder->builder_on_load();
					}
				}

				add_filter( 'learndash_header_data', 'LearnDash\Admin\QuizBuilderHelpers\get_quiz_data', 100 );
			}
		}

		/**
		 * Save metabox handler function.
		 *
		 * @since 2.6.0
		 *
		 * @param integer $post_id Post ID Question being edited.
		 * @param object  $post WP_Post Question being edited.
		 * @param boolean $update If update true, else false.
		 */
		public function save_post( $post_id = 0, $post = null, $update = false ) {
			if ( ! $this->post_type_check( $post ) ) {
				return false;
			}

			if ( ! parent::save_post( $post_id, $post, $update ) ) {
				return false;
			}

			// Check the Quiz custom fields to see if they need to be reformatted.
			if ( ( isset( $_POST['ld-quiz-custom-fields-nonce'] ) ) && ( ! empty( $_POST['ld-quiz-custom-fields-nonce'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ld-quiz-custom-fields-nonce'] ) ), 'ld-quiz-custom-fields-nonce' ) ) {

				if ( isset( $_POST['form'] ) ) {
					$form = $_POST['form']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					if ( ( is_array( $form ) ) && ( isset( $form[0] ) ) && ( 1 === count( $form[0] ) ) ) {
						$form_items = array();
						$form_item  = array();
						foreach ( $form as $form_ele ) {
							foreach ( $form_ele as $form_ele_name => $form_ele_value ) {
								if ( 'fieldname' === $form_ele_name ) {
									if ( ! empty( $form_item ) ) {
										$form_items[] = $form_item;
									}
									$form_item = array();
								}
								$form_item[ $form_ele_name ] = $form_ele_value;
							}
						}
						if ( ! empty( $form_item ) ) {
							$form_items[] = $form_item;
						}
						$form_item     = array();
						$_POST['form'] = $form_items;
					}
				}
			}

			$this->init_quiz_edit( $post );

			/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-quiz-edit.php */
			if ( apply_filters( 'learndash_show_quiz_builder', $this->use_quiz_builder ) === true ) {
				$this->quiz_builder = Learndash_Admin_Metabox_Quiz_Builder::add_instance();
				$this->quiz_builder->save_course_builder( $post_id, $post, $update );
			}

			if ( ( isset( $_POST['ld-course-primary-set-nonce'] ) ) && ( ! empty( $_POST['ld-course-primary-set-nonce'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ld-course-primary-set-nonce'] ) ), 'ld-course-primary-set-nonce' ) ) {
				if ( isset( $_POST['ld-course-primary-set'] ) ) {
					$course_primary = absint( $_POST['ld-course-primary-set'] );
					if ( ! empty( $course_primary ) ) {
						learndash_set_primary_course_for_step( $post_id, $course_primary );
					}
				}
			}

			if ( ! empty( $this->_metaboxes ) ) {
				foreach ( $this->_metaboxes as $_metaboxes_instance ) {
					$settings_fields = array();
					$settings_fields = $_metaboxes_instance->get_post_settings_field_updates( $post_id, $post, $update );
					$_metaboxes_instance->save_post_meta_box( $post_id, $post, $update, $settings_fields );
					$_metaboxes_instance->save_fields_to_post( $this->pro_quiz_edit, $settings_fields );
				}
			}

			$quiz_id  = absint( learndash_get_setting( $post_id, 'quiz_pro' ) );
			$pro_quiz = new WpProQuiz_Controller_Quiz();
			$pro_quiz->route(
				array(
					'action'  => 'addUpdateQuiz',
					'quizId'  => $quiz_id,
					'post_id' => $post_id,
				)
			);
		}

		/**
		 * Register metaboxes for Quiz edit.
		 *
		 * @since 2.4.0
		 *
		 * @param string $post_type Post Type being edited.
		 * @param object $post      WP_Post Post being edited.
		 */
		public function add_metaboxes( $post_type = '', $post = null ) {
			global $learndash_metaboxes;

			if ( $this->post_type_check( $post_type ) ) {
				parent::add_metaboxes( $post_type, $post );

				/**
				 * Filters whether to disable advanced quiz or not.
				 *
				 * @since 2.1.0
				 *
				 * @param boolean $disable_advanced_quiz Whether to disable advanced quiz.
				 * @param int     $post_id               Post ID.
				 */
				if ( apply_filters( 'learndash_disable_advance_quiz', false, $post->ID ) ) {
					return;
				}

				/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-quiz-edit.php */
				if ( true === apply_filters( 'learndash_show_quiz_builder', $this->use_quiz_builder ) ) {
					$data_settings = learndash_data_upgrades_setting( 'pro-quiz-questions' );

					$quiz_questions_data_upgrade_link = '';
					if ( ( isset( $data_settings['last_run'] ) ) && ( ! empty( $data_settings['last_run'] ) ) ) {
						$admin_url = admin_url( 'admin.php?page=learndash_data_upgrades' );
						$admin_url = add_query_arg( 'quiz_id', $post->ID, $admin_url );
					}

					add_meta_box(
						'learndash_quiz_builder',
						sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'LearnDash %s Builder', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						) . $quiz_questions_data_upgrade_link,
						array( $this->quiz_builder, 'show_builder_box' ),
						$this->post_type,
						'normal',
						'high'
					);
				}

				/** This filter is documented in includes/class-ld-semper-fi-module.php */
				if ( apply_filters( 'learndash_settings_metaboxes_legacy_quiz', LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ, $this->post_type ) ) {
					add_meta_box(
						'learndash_quiz_advanced_aggregated',
						// translators: placeholder: Quiz.
						sprintf( esc_html_x( 'LearnDash %s Advanced Settings', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
						array( $this, 'quiz_advanced_page_box_advanced_settings' ),
						$this->post_type,
						'normal',
						'high'
					);
				}
			}
		}

		/**
		 * Shows the Quiz Settings metabox.
		 *
		 * @since 3.0.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_advanced_page_box_advanced_settings( $post ) {

			// Advanced Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Advanced Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_advanced_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Question Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz, Question.
					esc_html_x( 'LearnDash %1$s %2$s Settings', 'placeholder: Quiz, Question', 'learndash' ),
					learndash_get_custom_label( 'quiz' ),
					learndash_get_custom_label( 'question' )
				)
			);
			$this->quiz_question_options_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Result Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Result Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_result_options_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Mode Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Mode Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_mode_options_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Result Text Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Result Text Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_custom_result_text_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Template Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Template Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_templates_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Leaderboard Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Leaderboard Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_leaderboard_options_page_box( $post );

			$this->quiz_advanced_close_wrapper();

			$this->quiz_advanced_hr();

			// Custom Fields Settings.
			$this->quiz_advanced_open_wrapper();

			$this->quiz_advanced_section_header(
				sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'LearnDash %s Custom Fields Settings', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				)
			);
			$this->quiz_custom_fields_options_page_box( $post );

			$this->quiz_advanced_close_wrapper();
		}

		/**
		 * Display a horizontal separator.
		 *
		 * @since 3.0.0
		 */
		public function quiz_advanced_hr() {
			?>
			<hr>
			<?php
		}

		/**
		 * Open a wrapper.
		 *
		 * @since 3.0.0
		 */
		public function quiz_advanced_open_wrapper() {
			?>
			<div class="ld-quiz-advanced-setting">
			<?php
		}

		/**
		 * Close a wrapper.
		 *
		 * @since 3.0.0
		 */
		public function quiz_advanced_close_wrapper() {
			?>
			</div>
			<?php
		}

		/**
		 * Shows the Quiz Advanced metabox.
		 *
		 * @since 2.4.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_advanced_page_box( $post ) {

			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->show_advanced( $this->_get );
			}
		}

		/**
		 * Display section header.
		 *
		 * @since 3.0.0
		 *
		 * @param string $title The title to be displayed.
		 */
		public function quiz_advanced_section_header( $title ) {
			?>
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php
		}

		/**
		 * Shows the Quiz Templates metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_templates_page_box( $post ) {

			$template_mapper = new WpProQuiz_Model_TemplateMapper();
			$templates       = $template_mapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );

			$template_loaded_id = '';
			if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$template_loaded_id = intval( $_GET['templateLoadId'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			?>
			<div class="wrap wpProQuiz_quizEdit">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Use Template', 'learndash' ); ?>
							</th>
							<td>
								<select id="templateLoadId" name="templateLoadId">
									<?php
									if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) && ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										$template_url = remove_query_arg( 'templateLoadId' );
										echo '<option value="' . esc_url( $template_url ) . '">' . sprintf(
											// translators: Quiz Title.
											esc_html_x( 'Revert: %s', 'placeholder: Quiz Title', 'learndash' ),
											wp_kses_post( get_the_title( absint( $_GET['post'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										) . '</option>';
									} else {
										echo '<option value="">' . esc_html__( 'Select a Template to load', 'learndash' ) . '</option>';
									}

									foreach ( $templates as $template ) {
										$template_url = add_query_arg( 'templateLoadId', absint( $template->getTemplateId() ) );
										echo '<option ' . selected( $template_loaded_id, $template->getTemplateId() ) . ' value="' . esc_url( $template_url ) . '">' . esc_html( $template->getName() ) . '</option>';
									}
									?>
								</select><br />
								<input type="submit" name="templateLoad" value="<?php esc_html_e( 'load template', 'learndash' ); ?>" class="button-primary">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Save as Template', 'learndash' ); ?>
							</th>
							<td>
								<select name="templateSaveList">
									<option value=""><?php esc_html_e( 'Select a templates to save or new', 'learndash' ); ?></option>
									<option value="0">=== <?php esc_html_e( 'Create new template', 'learndash' ); ?> === </option>
									<?php
									foreach ( $templates as $template ) {
										echo '<option value="' . absint( $template->getTemplateId() ), '">' . esc_html( $template->getName() ) . '</option>';
									}
									?>
								</select><br />
								<input type="text" placeholder="<?php esc_html_e( 'new template name', 'learndash' ); ?>" class="regular-text" name="templateName">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Shows the Quiz Question Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_question_options_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->questionOptions();
			}
		}

		/**
		 * Shows the Quiz Result Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_result_options_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->resultOptions();
			}
		}

		/**
		 * Shows the Quiz Mode Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_mode_options_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->quizMode();
			}
		}

		/**
		 * Shows the Quiz Leaderboard Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_leaderboard_options_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->leaderboardOptions();
			}
		}

		/**
		 * Shows the Quiz Custom Fields Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_custom_fields_options_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->form();
			}
		}

		/**
		 * Shows the Quiz Result Text  metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function quiz_custom_result_text_page_box( $post ) {
			$this->init_quiz_edit( $post );
			if ( is_a( $this->pro_quiz_edit, 'WpProQuiz_View_QuizEdit' ) ) {
				$this->pro_quiz_edit->resultText();
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Quiz_Edit();
