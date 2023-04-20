<?php
/**
 * LearnDash Admin Question Edit.
 *
 * @since 2.6.0
 * @package LearnDash\Question\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Question_Edit' ) ) ) {

	/**
	 * Class LearnDash Admin Question Edit.
	 *
	 * @since 2.6.0
	 * @uses Learndash_Admin_Post_Edit
	 */
	class Learndash_Admin_Question_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * WPProQuiz Question instance.
		 * This is used to bridge the WPProQuiz to WP systems.
		 *
		 * @var object $pro_question_edit WPProQuiz instance.
		 */
		private $pro_question_edit = null;

		/**
		 * WPProQuiz View QuestionEdit instance.
		 *
		 * @var WpProQuiz_View_QuestionEdit
		 */
		private $view = null;

		/**
		 * Public constructor for class.
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'question' );

			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 *
		 * @since 2.6.0
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {
				parent::on_load();

				wp_enqueue_script( 'media-upload' );
				wp_enqueue_script( 'thickbox' );

				$wpproquiz_controller_admin = new WpProQuiz_Controller_Admin();
				$wpproquiz_controller_admin->enqueueScript();

				add_action( 'admin_footer', array( $this, 'admin_footer' ) );
			}
		}

		/**
		 * Hook into the WP admin footer logic to add custom JavaScript to replace the default page title.
		 *
		 * @since 2.6.0
		 */
		public function admin_footer() {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}
			if ( isset( $_GET['quiz_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$quiz_id = absint( $_GET['quiz_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! empty( $quiz_id ) ) {
					$quizzes_url = add_query_arg( 'post_type', learndash_get_post_type_slug( 'quiz' ), admin_url( 'edit.php' ) );

					$new_title     = '<a href="' . $quizzes_url . '">' . LearnDash_Custom_Label::get_label( 'quizzes' ) . '</a> &gt; <a href="' . get_edit_post_link( $quiz_id ) . '">' . get_the_title( $quiz_id ) . '</a> ';
					$post_new_file = add_query_arg(
						array(
							'post_type' => $post_type,
							'quiz_id'   => $quiz_id,
						),
						'post-new.php'
					);
					$add_new_url   = admin_url( $post_new_file );
					?>
					<script>
						jQuery( function() {
							jQuery('h1.wp-heading-inline').html('<?php echo wp_kses_post( $new_title ); ?>');
							jQuery('a.page-title-action').attr( 'href', '<?php echo esc_url( $add_new_url ); ?>' );
						});
					</script>
					<?php
				}
			}
		}

		/**
		 * Filter the SFWD display options logic to set a default value for the Quiz.
		 *
		 * @since 2.6.0
		 *
		 * @param array $options Array of settings values for the current post_type.
		 *
		 * @return array of $options.
		 */
		public function display_options( $options = array() ) {
			global $pagenow;

			if ( 'post-new.php' === $pagenow ) {
				if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$quiz_id                               = absint( $_GET['quiz_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$options[ $this->post_type . '_quiz' ] = $quiz_id;
				}
			}
			return $options;
		}

		/** This function is documented in includes/admin/class-learndash-admin-posts-edit.php */
		// phpcs:ignore Squiz.Commenting.FunctionComment
		public function edit_post_post_type( $post_id = 0, $post = null ) {
			$post_id = absint( $post_id );
			if ( ! $this->post_type_check( $post ) ) {
				return false;
			}

			if ( ! empty( $post_id ) ) {
				$question_pro_id = (int) get_Post_meta( $post_id, 'question_pro_id', true );
				if ( ! empty( $question_pro_id ) ) {
					$question_mapper = new WpProQuiz_Model_QuestionMapper();
					$question_pro    = $question_mapper->fetch( $question_pro_id );
					if ( ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) && ( $question_pro_id === $question_pro->getId() ) ) {
						$question_pro->setTitle( $post->post_title );
						$question_pro = $question_mapper->save( $question_pro );
					}
				}
			}
		}

		/**
		 * Initialize the ProQuiz Question being edited.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function init_question_edit( $post ) {
			global $pagenow;

			if ( 'post-new.php' === $pagenow ) {
				add_filter( $this->post_type . '_display_options', array( $this, 'display_options' ) );
			}

			if ( ! is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_pro_id = (int) get_post_meta( $post->ID, 'question_pro_id', true );

				$question_mapper = new WpProQuiz_Model_QuestionMapper();
				if ( ! empty( $question_pro_id ) ) {
					$this->pro_question_edit = $question_mapper->fetch( $question_pro_id );

					if ( ( $this->pro_question_edit ) && is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
						$post->post_title   = $this->pro_question_edit->getTitle();
						$post->post_content = $this->pro_question_edit->getQuestion();
					}
				} else {
					$this->pro_question_edit = $question_mapper->fetch( null );
				}

				if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$template_mapper = new WpProQuiz_Model_TemplateMapper();
					$template        = $template_mapper->fetchById( absint( $_GET['templateLoadId'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ( $template ) && ( is_a( $template, 'WpProQuiz_Model_Template' ) ) ) {
						$data = $template->getData();
						if ( null !== $data ) {
							$data['question']->setId( $this->pro_question_edit->getId() );
							$data['question']->setQuizId( $this->pro_question_edit->getQuizId() );

							$this->pro_question_edit = $data['question'];
							$post->post_title        = $this->pro_question_edit->getTitle();
							$post->post_content      = $this->pro_question_edit->getQuestion();
						}
					}
				}
			}
		}

		/**
		 * Save Question handler function.
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

			$post_data = $this->clear_request_data( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$question_pro_id = get_post_meta( $post_id, 'question_pro_id', true );
			if ( ! empty( $question_pro_id ) ) {
				$question_pro_id = absint( $question_pro_id );
			} else {
				$question_pro_id = 0;
			}

			$question_pro_id_new = learndash_update_pro_question( $question_pro_id, $post_data );
			if ( ( ! empty( $question_pro_id_new ) ) && ( ( absint( $question_pro_id_new ) ) !== ( absint( $question_pro_id ) ) ) ) {
				update_post_meta( $post_id, 'question_pro_id', absint( $question_pro_id_new ) );
				learndash_set_question_quizzes_dirty( $post_id );
			}
			learndash_proquiz_sync_question_fields( $post_id, $question_pro_id_new );
			learndash_proquiz_sync_question_category( $post_id, $question_pro_id_new );
		}

		/**
		 * Register metaboxes for Question editing
		 *
		 * @since 2.6.0
		 *
		 * @param string $post_type Post Type being edited.
		 * @param object $post      WP_Post Post being edited.
		 */
		public function add_metaboxes( $post_type = '', $post = null ) {
			if ( $this->post_type_check( $post_type ) ) {
				parent::add_metaboxes( $post_type, $post );

				$this->init_question_edit( $post );

				add_meta_box(
					'learndash_question_category_proquiz',
					sprintf(
						// translators: placeholders: Question.
						esc_html_x( '%s Category', 'placeholders: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					) . ' ' . esc_html__( '(optional)', 'learndash' ),
					array( $this, 'question_category_proquiz_page_box' ),
					$this->post_type,
					'side',
					'high'
				);

				add_meta_box(
					'learndash_question_type',
					esc_html__( 'Answer type', 'learndash' ),
					array( $this, 'question_type_page_box' ),
					$this->post_type,
					'side',
					'default'
				);

				add_meta_box(
					'learndash_question_points',
					esc_html__( 'Points', 'learndash' ) . ' ' . esc_html__( '(required)', 'learndash' ),
					array( $this, 'question_points_page_box' ),
					$this->post_type,
					'side',
					'default'
				);

				add_meta_box(
					'learndash_question_answers',
					esc_html__( 'Answers', 'learndash' ) . ' ' . esc_html__( '(required)', 'learndash' ),
					array( $this, 'question_answers_page_box' ),
					$this->post_type,
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_question_single_choice_options',
					esc_html__( 'Single choice options', 'learndash' ) . ' ' . esc_html__( '(optional)', 'learndash' ),
					array( $this, 'question_single_choice_options' ),
					$this->post_type,
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_question_message_correct_answer',
					esc_html__( 'Message with the correct answer', 'learndash' ) . ' ' . esc_html__( '(optional)', 'learndash' ),
					array( $this, 'question_message_correct_answer_page_box' ),
					$this->post_type,
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_question_message_incorrect_answer',
					esc_html__( 'Message with the incorrect answer', 'learndash' ) . ' ' . esc_html__( '(optional)', 'learndash' ),
					array( $this, 'question_message_incorrect_answer_page_box' ),
					$this->post_type,
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_question_hint',
					esc_html__( 'Hint', 'learndash' ) . ' ' . esc_html__( '(optional)', 'learndash' ),
					array( $this, 'question_hint_page_box' ),
					$this->post_type,
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_question_template',
					esc_html__( 'Template', 'learndash' ),
					array( $this, 'question_template_page_box' ),
					$this->post_type,
					'normal',
					'high'
				);

				global $wp_meta_boxes;
				if ( isset( $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_answers'] ) ) {
					$learndash_question_answers = $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_answers'];
					unset( $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_answers'] );
				} else {
					$learndash_question_answers = null;
				}

				if ( isset( $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_single_choice_options'] ) ) {
					$learndash_question_single_choice_options = $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_single_choice_options'];
					unset( $wp_meta_boxes[ $this->post_type ]['normal']['high']['learndash_question_single_choice_options'] );
				} else {
					$learndash_question_single_choice_options = null;
				}

				$question_metaboxes_new = array();
				if ( ! is_null( $learndash_question_answers ) ) {
					$question_metaboxes_new = array_merge(
						$question_metaboxes_new,
						array( 'learndash_question_answers' => $learndash_question_answers )
					);
				}
				if ( ! is_null( $learndash_question_single_choice_options ) ) {
					$question_metaboxes_new = array_merge(
						$question_metaboxes_new,
						array( 'learndash_question_single_choice_options' => $learndash_question_single_choice_options )
					);
				}

				if ( ! empty( $question_metaboxes_new ) ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$wp_meta_boxes[ $this->post_type ]['normal']['high'] = array_merge(
						$question_metaboxes_new,
						$wp_meta_boxes[ $this->post_type ]['normal']['high']
					);
				}

				/**
				 * Check if the editor is classic or new Gutenberg Block editor and hide non-important metaboxes
				 */
				if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
					$user_closed_postboxes = get_user_meta( get_current_user_id(), 'closedpostboxes_' . $this->post_type, true );
					if ( ( is_string( $user_closed_postboxes ) ) && ( '' === $user_closed_postboxes ) ) {
						if ( ( function_exists( 'use_block_editor_for_post' ) ) && ( use_block_editor_for_post( $post ) ) ) {
							$all_postboxes = array(
								'sfwd-question',
								'learndash_question_single_choice_options',
								'learndash_question_message_correct_answer',
								'learndash_question_message_incorrect_answer',
								'learndash_question_hint',
								'learndash_question_template',
							);

						} else {
							$all_postboxes = array(
								'learndash_question_message_correct_answer',
								'learndash_question_message_incorrect_answer',
								'learndash_question_hint',
								'learndash_question_template',
							);
						}
						update_user_meta( get_current_user_id(), 'closedpostboxes_' . $this->post_type, $all_postboxes );
					}
				}
			}
		}

		/**
		 * Shows the Question Types metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_type_page_box( $post ) {
			global $learndash_question_types;

			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_type = $this->pro_question_edit->getAnswerType();
			} else {
				$question_type = 'single';
			}
			?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html__( 'Answer Type', 'learndash' ); ?></legend>
				<ul>
				<?php
				foreach ( $learndash_question_types as $q_type => $q_label ) {
					?>
					<li><input id="learndash-question-type-<?php echo esc_attr( $q_type ); ?>" type="radio" name="answerType" value="<?php echo esc_attr( $q_type ); ?>" <?php checked( $q_type, $question_type ); ?> />
					<label for="learndash-question-type-<?php echo esc_attr( $q_type ); ?>" ><?php echo esc_attr( $q_label ); ?></label></li>
					<?php
				}
				?>
				</ul>
			</fieldset>
			<?php
		}

		/**
		 * Shows the Question Category metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_category_proquiz_page_box( $post ) {
			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_category_id = $this->pro_question_edit->getCategoryId();
			} else {
				$question_category_id = 0;
			}

			$category_mapper = new WpProQuiz_Model_CategoryMapper();
			$all_categories  = $category_mapper->fetchAll();
			?>
			<div id="wpProQuiz_nonce" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpProQuiz_nonce' ) ); ?>" style="display:none;"></div>
			<p class="description">
				<?php esc_html_e( 'You can assign classify category for a question. Categories are e.g. visible in statistics function.', 'learndash' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'You can manage categories in global settings.', 'learndash' ); ?>
			</p>
			<div>
				<select name="category">
					<option value="-1">--- <?php esc_html_e( 'Create new category', 'learndash' ); ?> ----</option>
					<option value="0" <?php echo $this->pro_question_edit->getCategoryId() == 0 ? 'selected="selected"' : ''; ?>>--- <?php esc_html_e( 'No category', 'learndash' ); ?> ---</option>
					<?php
					foreach ( $all_categories as $cat ) {
						echo '<option ' . selected( $question_category_id, $cat->getCategoryId(), false ) . ' value="' . esc_attr( $cat->getCategoryId() ) . '">' . esc_html( stripslashes( $cat->getCategoryName() ) ) . '</option>';
					}
					?>
				</select>
			</div>
			<div style="display: none;" id="categoryAddBox">
				<h4><?php esc_html_e( 'Create new category', 'learndash' ); ?></h4>
				<input type="text" name="categoryAdd" value="">
				<input type="button" class="button-secondary" name="" id="categoryAddBtn" value="<?php esc_html_e( 'Create', 'learndash' ); ?>">
			</div>
			<div id="categoryMsgBox" style="display:none; padding: 5px; border: 1px solid rgb(160, 160, 160); background-color: rgb(255, 255, 168); font-weight: bold; margin: 5px; ">
				Category
			</div>
			<?php
		}


		/**
		 * Shows the Question Points metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_points_page_box( $post ) {
			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_points = $this->pro_question_edit->getPoints();
			} else {
				$question_points = 1;
			}

			?>
			<p class="description">
				<?php
					printf(
						// translators: placeholder: question.
						esc_html_x( 'Points for this %s (Standard is 1 point)', 'placeholder: question', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'question' ) )
					)
				?>
			</p>
			<label>
				<input name="points" class="small-text" value="<?php echo intval( $question_points ); ?>" type="number" min="1"> <?php esc_html_e( 'Points', 'learndash' ); ?>
			</label>
			<p class="description">
				<?php
					printf(
						// translators: placeholder: question.
						esc_html_x( 'These points will be rewarded, only if the user chooses the %s correctly', 'placeholder: question', 'learndash' ),
						esc_html( learndash_get_custom_label( 'question' ) )
					)
				?>
			</p>

			<div style="margin-top: 10px;" id="wpProQuiz_answerPointsActivated">
				<label>
					<input name="answerPointsActivated" type="checkbox" value="1" <?php checked( '1', $this->pro_question_edit->isAnswerPointsActivated() ); ?>>
					<?php esc_html_e( 'Different points for each answer', 'learndash' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'If you enable this option, you can enter different points for every answer.', 'learndash' ); ?>
				</p>
			</div>
			<div style="margin-top: 10px;" id="wpProQuiz_showPointsBox">
				<label>
					<input name="showPointsInBox" value="1" type="checkbox" <?php checked( '1', $this->pro_question_edit->isShowPointsInBox() ); ?>>
					<?php esc_html_e( 'Show reached points in the correct- and incorrect message?', 'learndash' ); ?>
				</label>
			</div>
			<?php
		}

		/**
		 * Shows the Question Correct Answer Message metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_message_correct_answer_page_box( $post ) {

			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_correct_same_text = checked( '1', $this->pro_question_edit->isCorrectSameText(), false );
				$question_correct_message   = $this->pro_question_edit->getCorrectMsg();
			} else {
				$question_correct_same_text = '';
				$question_correct_message   = '';
			}

			?>
			<p class="description">
				<?php esc_html_e( 'This text will be visible if answered correctly. It can be used as explanation for complex questions. The message "Right" or "Wrong" is always displayed automatically.', 'learndash' ); ?>
			</p>
			<div class="learndash-same-answer-text" style="padding-top: 10px; padding-bottom: 10px;">
				<label for="wpProQuiz_correctSameText">
					<?php esc_html_e( 'Same text for correct- and incorrect-message?', 'learndash' ); ?>
					<input type="checkbox" name="correctSameText" id="wpProQuiz_correctSameText" value="1" <?php echo esc_html( $question_correct_same_text ); ?>>
				</label>
			</div>
			<?php
			wp_editor( $question_correct_message, 'correctMsg', array( 'textarea_rows' => 3 ) );
		}


		/**
		 * Shows the Question Incorrect Answer Message metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_message_incorrect_answer_page_box( $post ) {

			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_incorrect_message = $this->pro_question_edit->getIncorrectMsg();
			} else {
				$question_incorrect_message = '';
			}

			?>
			<div style="padding-top: 10px; padding-bottom: 10px;"></div>
			<p class="description">
				<?php esc_html_e( 'This text will be visible if answered incorrectly. It can be used as explanation for complex questions. The message "Right" or "Wrong" is always displayed automatically.', 'learndash' ); ?>
			</p>
			<?php
			wp_editor( $question_incorrect_message, 'incorrectMsg', array( 'textarea_rows' => 3 ) );
		}

		/**
		 * Shows the Question Hint metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_hint_page_box( $post ) {
			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$question_hint_enabled = checked( '1', $this->pro_question_edit->isTipEnabled(), false );
				$question_hint_message = $this->pro_question_edit->getTipMsg();
			} else {
				$question_hint_enabled = '';
				$question_hint_message = '';
			}
			?>
			<p class="description">
				<?php esc_html_e( 'Here you can enter solution hint.', 'learndash' ); ?>
			</p>
			<div style="padding-top: 10px; padding-bottom: 10px;">
				<label for="wpProQuiz_tip">
					<?php esc_html_e( 'Activate hint for this question?', 'learndash' ); ?>
					<input type="checkbox" name="tipEnabled" id="wpProQuiz_tip" value="1" <?php echo esc_html( $question_hint_enabled ); ?>>
				</label>
			</div>
			<div id="wpProQuiz_tipBox">
				<?php wp_editor( $question_hint_message, 'tipMsg', array( 'textarea_rows' => 3 ) ); ?>
			</div>
			<?php
		}

		/**
		 * Shows the Single Choice Question Options metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_single_choice_options( $post ) {
			?>
			<p class="description">
				<?php echo wp_kses_post( __( 'If "Different points for each answer" is activated, you can activate a special mode.<br> This changes the calculation of the points', 'learndash' ) ); ?>
			</p>
			<label>
				<input type="checkbox" name="answerPointsDiffModusActivated" value="1" <?php checked( '1', $this->pro_question_edit->isAnswerPointsDiffModusActivated() ); ?>>
				<?php esc_html_e( 'Different points - modus 2 activate', 'learndash' ); ?>
			</label>
			<br><br>
			<p class="description">
				<?php esc_html_e( 'Disables the distinction between correct and incorrect.', 'learndash' ); ?><br>
			</p>
			<label>
				<input type="checkbox" name=disableCorrect value="1" <?php checked( '1', $this->pro_question_edit->isDisableCorrect() ); ?>>
				<?php esc_html_e( 'Disable correct and incorrect', 'learndash' ); ?>
			</label>

			<div style="padding-top: 20px;">
				<a href="#" id="clickPointDia"><?php esc_html_e( 'Explanation of points calculation', 'learndash' ); ?></a>
				<?php $this->answerPointDia(); ?>
			</div>
			<?php
		}

		/**
		 * Shows the Question Answers metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_answers_page_box( $post ) {
			$proquiz_controller_question = new WpProQuiz_Controller_Question();

			if ( is_a( $this->pro_question_edit, 'WpProQuiz_Model_Question' ) ) {
				$pro_question_data = $proquiz_controller_question->setAnswerObject( $this->pro_question_edit );
			} else {
				$pro_question_data = $proquiz_controller_question->setAnswerObject();
			}

			$this->view = new WpProQuiz_View_QuestionEdit();

			?>
			<div class="inside answer_felder">
				<div class="free_answer">
					<?php $this->view->freeChoice( $pro_question_data['free_answer'] ); ?>
				</div>
				<div class="sort_answer">
					<p class="description">
						<?php esc_html_e( 'Please sort the answers in right order with the "Move" - Button. The answers will be displayed randomly.', 'learndash' ); ?>
					</p>
					<ul class="answerList">
						<?php $this->view->sortingChoice( $pro_question_data['sort_answer'] ); ?>
					</ul>
					<input type="button" class="button-primary addAnswer" data-default-value="<?php echo esc_attr( (string) LEARNDASH_LMS_DEFAULT_ANSWER_POINTS ); ?>" value="<?php esc_html_e( 'Add new answer', 'learndash' ); ?>">
				</div>
				<div class="classic_answer">
					<ul class="answerList">
						<?php $this->view->singleMultiCoice( $pro_question_data['classic_answer'] ); // cspell:disable-line. ?>
					</ul>
					<input type="button" class="button-primary addAnswer" data-default-value="<?php echo esc_attr( (string) LEARNDASH_LMS_DEFAULT_ANSWER_POINTS ); ?>" value="<?php esc_html_e( 'Add new answer', 'learndash' ); ?>">
				</div>
				<div class="matrix_sort_answer">
					<p class="description">
						<?php esc_html_e( 'In this mode, Sort Elements must be assigned to their corresponding Criterion.', 'learndash' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Each Sort Element must be unique, and only one-to-one associations are supported.', 'learndash' ); ?>
					</p>
					<br>
					<label>
						<?php esc_html_e( 'Percentage width of criteria table column:', 'learndash' ); ?>
						<?php $msacw_value = $this->pro_question_edit->getMatrixSortAnswerCriteriaWidth() > 0 ? $this->pro_question_edit->getMatrixSortAnswerCriteriaWidth() : 20; ?>
						<input type="number" min="1" max="99" step="1" name="matrixSortAnswerCriteriaWidth" value="<?php echo esc_attr( $msacw_value ); ?>">%
					</label>
					<p class="description">
						<?php esc_html_e( 'Allows adjustment of the left column\'s width, and the right column will auto-fill the rest of the available space. Increase this to allow accommodate longer criterion text. Defaults to 20%.', 'learndash' ); ?>
					</p>
					<br>
					<ul class="answerList">
						<?php $this->view->matrixSortingChoice( $pro_question_data['matrix_sort_answer'] ); ?>
					</ul>
					<input type="button" class="button-primary addAnswer" data-default-value="<?php echo esc_attr( (string) LEARNDASH_LMS_DEFAULT_ANSWER_POINTS ); ?>" value="<?php esc_html_e( 'Add new answer', 'learndash' ); ?>">
				</div>
				<div class="cloze_answer">
					<?php $this->view->clozeChoice( $pro_question_data['cloze_answer'] ); ?>
				</div>
				<div class="assessment_answer">
					<?php $this->view->assessmentChoice( $pro_question_data['assessment_answer'] ); ?>
				</div>
				<div class="essay">
					<?php $this->view->essayChoice( $pro_question_data['essay'] ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Shows the Question Template metabox.
		 *
		 * @since 2.6.0
		 *
		 * @param object $post WP_Post Question being edited.
		 */
		public function question_template_page_box( $post ) {

			$template_mapper = new WpProQuiz_Model_TemplateMapper();
			$templates       = $template_mapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION, false );

			$template_loaded_id = '';
			if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$template_loaded_id = intval( $_GET['templateLoadId'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			?>
			<div class="wrap wpProQuiz_questionEdit">
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
											// translators: Question Title.
											esc_html_x( 'Revert: %s', 'placeholder: Question Title', 'learndash' ),
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
		 * Display special answer points diagram.
		 *
		 * @since 2.6.0
		 */
		private function answerPointDia() {
			?>
			<style>
				.pointDia td {
					border: 1px solid #9E9E9E;
					padding: 8px;
			}
			</style>
			<table style="border-collapse: collapse; display: none; margin-top: 10px;" class="pointDia">
			<tr>
				<th>
					<?php esc_html_e( '"Different points for each answer" enabled', 'learndash' ); ?><br>
					<?php esc_html_e( '"Different points - mode 2" disable', 'learndash' ); ?>
				</th>
				<th>
					<?php esc_html_e( '"Different points for each answer" enabled', 'learndash' ); ?><br>
					<?php esc_html_e( '"Different points - mode 2" enabled', 'learndash' ); ?>
				</th>
			</tr>
			<tr>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'Question - Single Choice - 3 Answers - Diff points mode
						A=3 Points [correct]
						B=2 Points [incorrect]
						C=1 Point [incorrect]

						= 6 Points',
						'learndash'
					)
				);
				?>
				</td>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'Question - Single Choice - 3 Answers - Modus 2

						A=3 Points [correct]
						B=2 Points [incorrect]
						C=1 Point [incorrect]

						= 3 Points',
						'learndash'
					)
				);
				?>
				</td>
			</tr>
			<tr>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 1: ~~~

						A=checked
						B=unchecked
						C=unchecked

						Result:
						A=correct and checked (correct) = 3 Points
						B=incorrect and unchecked (correct) = 2 Points
						C=incorrect and unchecked (correct) = 1 Points

						= 6 / 6 Points 100%',
						'learndash'
					)
				);
				?>
				</td>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 1: ~~~

						A=checked
						B=unchecked
						C=unchecked

						Result:
						A=checked = 3 Points
						B=unchecked = 0 Points
						C=unchecked = 0 Points

						= 3 / 3 Points 100%',
						'learndash'
					)
				);
				?>
				</td>
			</tr>
			<tr>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 2: ~~~

						A=unchecked
						B=checked
						C=unchecked

						Result:
						A=correct and unchecked (incorrect) = 0 Points
						B=incorrect and checked (incorrect) = 0 Points
						C=incorrect and unchecked (correct) = 1 Points

						= 1 / 6 Points 16.67%',
						'learndash'
					)
				);
				?>
				</td>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 2: ~~~

						A=unchecked
						B=checked
						C=unchecked

						Result:
						A=unchecked = 0 Points
						B=checked = 2 Points
						C=unchecked = 0 Points

						= 2 / 3 Points 66,67%',
						'learndash'
					)
				);
				?>
				</td>
			</tr>
			<tr>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 3: ~~~

						A=unchecked
						B=unchecked
						C=checked

						Result:
						A=correct and unchecked (incorrect) = 0 Points
						B=incorrect and unchecked (correct) = 2 Points
						C=incorrect and checked (incorrect) = 0 Points

						= 2 / 6 Points 33.33%',
						'learndash'
					)
				);
				?>
				</td>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 3: ~~~

						A=unchecked
						B=unchecked
						C=checked

						Result:
						A=unchecked = 0 Points
						B=unchecked = 0 Points
						C=checked = 1 Points

						= 1 / 3 Points 33,33%',
						'learndash'
					)
				);
				?>
				</td>
			</tr>
			<tr>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 4: ~~~

						A=unchecked
						B=unchecked
						C=unchecked

						Result:
						A=correct and unchecked (incorrect) = 0 Points
						B=incorrect and unchecked (correct) = 2 Points
						C=incorrect and unchecked (correct) = 1 Points

						= 3 / 6 Points 50%',
						'learndash'
					)
				);
				?>
				</td>
				<td>
				<?php
				echo nl2br(
					esc_html__(
						'~~~ User 4: ~~~

						A=unchecked
						B=unchecked
						C=unchecked

						Result:
						A=unchecked = 0 Points
						B=unchecked = 0 Points
						C=unchecked = 0 Points

						= 0 / 3 Points 0%',
						'learndash'
					)
				);
				?>
				</td>
			</tr>
			</table>
			<?php
		}

		// End of functions.
	}
}
new Learndash_Admin_Question_Edit();
