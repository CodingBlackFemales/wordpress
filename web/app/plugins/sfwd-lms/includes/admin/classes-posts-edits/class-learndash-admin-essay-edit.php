<?php
/**
 * LearnDash Admin Essay Edit.
 *
 * @since 3.2.3
 * @package LearnDash\Essay\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore subbmitdiv .

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Essay_Edit' ) ) ) {

	/**
	 * Class LearnDash Admin Essay Edit.
	 *
	 * @since 3.2.3
	 * @uses Learndash_Admin_Post_Edit
	 */
	class Learndash_Admin_Essay_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * Public constructor for class.
		 *
		 * @since 3.2.3
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'essay' );

			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 *
		 * @since 3.2.3
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {

				parent::on_load();

				$this->remove_subbmitdiv_metabox(); // cspell:disable-line.

				add_meta_box(
					'learndash_essay_upload_div',
					esc_html__( 'Essay Upload', 'learndash' ),
					array( $this, 'essay_upload_meta_box' ),
					learndash_get_post_type_slug( 'essay' ),
					'normal',
					'high'
				);

				add_meta_box(
					'learndash_essay_status_div',
					esc_html__( 'Essay Grading Status', 'learndash' ),
					array( $this, 'essay_grading_meta_box' ),
					learndash_get_post_type_slug( 'essay' ),
					'side',
					'core'
				);
			}
		}

		/**
		 * Prints the essay grading metabox content.
		 *
		 * Copied/modified version of submitdiv from core.
		 *
		 * @since 3.2.3
		 *
		 * @param WP_Post $essay The `WP_Post` essay object.
		 */
		public function essay_grading_meta_box( $essay ) {
			$post_type        = $essay->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish      = current_user_can( $post_type_object->cap->publish_posts );
			$quiz_id          = get_post_meta( $essay->ID, 'quiz_id', true );
			$question_id      = get_post_meta( $essay->ID, 'question_id', true );
			$question         = null;

			if ( ! empty( $quiz_id ) ) {
				$question_mapper = new WpProQuiz_Model_QuestionMapper();
				$question        = $question_mapper->fetchById( intval( $question_id ), null );
			}

			if ( $question && is_a( $question, 'WpProQuiz_Model_Question' ) ) {
				$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question->getId(), $essay );
			}

			wp_nonce_field( 'learndash-essay-grading-nonce-' . $essay->ID, 'learndash-essay-grading-nonce' );

			?>
			<div class="submitbox" id="submitpost">
				<div id="minor-publishing">
					<div id="misc-publishing-actions">
						<div class="misc-pub-section misc-pub-post-status">
							<?php if ( 'not_graded' == $essay->post_status || 'graded' == $essay->post_status || $can_publish ) : ?>

								<div id="post-status-select">
									<select name='post_status' id='post_status'>
										<option <?php selected( $essay->post_status, 'not_graded' ); ?>
											value='not_graded'><?php esc_html_e( 'Not Graded', 'learndash' ); ?></option>
										<option <?php selected( $essay->post_status, 'graded' ); ?>
											value='graded'><?php esc_html_e( 'Graded', 'learndash' ); ?></option>
									</select>
								</div>

							<?php endif; ?>
						</div>

						<div class="misc-pub-section">
							<?php if ( $question && is_a( $question, 'WpProQuiz_Model_Question' ) ) : ?>
								<p>
									<?php // translators: placeholder: question. ?>
									<strong><?php echo sprintf( esc_html_x( 'Essay %s', 'placeholder: question', 'learndash' ), esc_html( learndash_get_custom_label( 'question' ) ) ); ?>:</strong> <?php echo wp_kses_post( $question->getQuestion() ); ?>
									<?php
										$test_url          = admin_url( 'admin.php' );
										$question_edit_url = '';
									if ( ( true === learndash_is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
										$question_post_id = learndash_get_question_post_by_pro_id( $question->getId() );
										if ( ! empty( $question_post_id ) ) {
											$question_edit_url = get_edit_post_link( $question_post_id );
										}
									}

									if ( empty( $question_edit_url ) ) {
										$question_edit_url = add_query_arg(
											array(
												'page'    => 'ldAdvQuiz',
												'module'  => 'question',
												'action'  => 'addEdit',
												'quiz_id' => $quiz_id,
												'questionId' => $question->getId(),
											),
											admin_url( 'admin.php' )
										);
									}
									?>
									<span>(<a href="<?php echo esc_url( $question_edit_url ); ?>"><?php esc_html_e( 'Edit', 'learndash' ); ?></a>)</span>
								</p>
								<p><strong><?php esc_html_e( 'Points available', 'learndash' ); ?>:</strong> <?php echo esc_html( $question->getPoints() ); ?></p>
								<p>
									<strong><?php esc_html_e( 'Points awarded', 'learndash' ); ?>:</strong>
									<input name="points_awarded" type="number" min="0" max="<?php echo esc_attr( $question->getPoints() ); ?>" value="<?php echo esc_attr( $submitted_essay_data['points_awarded'] ); ?>">
									<input name="original_points_awarded" type="hidden" value="<?php echo esc_attr( $submitted_essay_data['points_awarded'] ); ?>">
								</p>
								<input name="quiz_id" type="hidden" value="<?php echo esc_attr( $quiz_id ); ?>">
								<input name="question_id" type="hidden" value="<?php echo esc_attr( $question->getId() ); ?>">
							<?php else : ?>
								<p> <?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
									// translators: placeholder: question.
									esc_html_x( 'We could not find the essay %s for this response', 'placeholder: question', 'learndash' ),
									esc_html( learndash_get_custom_label_lower( 'question' ) )
								) ?> </p> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
							<?php endif; ?>
						</div>

						<div class="misc-pub-section">
							<?php
								$essay_quiz_post_id = get_post_meta( $essay->ID, 'quiz_post_id', true );
							if ( empty( $essay_quiz_post_id ) ) {

								$essay_quiz_query_args = array(
									'post_type'    => 'sfwd-quiz',
									'post_status'  => 'publish',
									'meta_key'     => 'quiz_pro_id_' . intval( $quiz_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
									'meta_value'   => intval( $quiz_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
									'meta_compare' => '=',
									'fields'       => 'ids',
									'orderby'      => 'title',
									'order'        => 'ASC',
								);

								$essay_quiz_query = new WP_Query( $essay_quiz_query_args );
								if ( count( $essay_quiz_query->posts ) > 1 ) {
									?>
										<p>
										<strong>
										<?php
										// translators: placeholder: Quiz.
										printf( esc_html_x( 'Essay %s', 'placeholder: Quiz.', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
										?>
										:</strong>
										<select name="essay_quiz_post_id">
											<option value=""><?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
												// translators: placeholder: Quiz.
												esc_html_x( 'No %s', 'placeholder: Quiz', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
											); ?> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
											</option>
											<?php
											foreach ( $essay_quiz_query->posts as $quiz_post_id ) {
												?>
												<option value="<?php echo absint( $quiz_post_id ); ?>"><?php echo wp_kses_post( get_the_title( $quiz_post_id ) ); ?></option>
												<?php
											}
											?>
										</select>
										</p>
										<?php

								} else {
									$essay_quiz_post_id = $essay_quiz_query->posts[0];
								}
							}

							if ( ! empty( $essay_quiz_post_id ) ) {
								$essay_quiz_edit_link = get_edit_post_link( $essay_quiz_post_id );
								?>
									<p><strong><?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
										// translators: placeholder: Quiz.
										esc_html_x( 'Essay %s', 'placeholder: Quiz', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									); ?>: </strong><?php echo wp_kses_post( get_the_title( $essay_quiz_post_id ) ); // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
									<?php if ( ! empty( $essay_quiz_edit_link ) ) { ?>
											<span>(<a href="<?php echo esc_url( $essay_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span>
									<?php } ?>
									</p>
								<?php

								$essay_quiz_course_id = get_post_meta( $essay_quiz_post_id, 'course_id', true );
								if ( ! empty( $essay_quiz_course_id ) ) {
									$course_quiz_edit_link = get_edit_post_link( $essay_quiz_course_id );
									?>
										<p><strong><?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
											// translators: placeholder: Course.
											esc_html_x( 'Essay %s', 'placeholder: Course', 'learndash' ),
											LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
										); ?>: </strong><?php echo wp_kses_post( get_the_title( $essay_quiz_course_id ) ); // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
										<?php if ( ! empty( $course_quiz_edit_link ) ) { ?>
												<span>(<a href="<?php echo esc_url( $course_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span>
										<?php } ?>
										</p>
									<?php

									$essay_quiz_lesson_id = get_post_meta( $essay_quiz_post_id, 'lesson_id', true );
									if ( ! empty( $essay_quiz_lesson_id ) ) {
										$lesson_quiz_edit_link = get_edit_post_link( $essay_quiz_lesson_id );
										?>
											<p><strong><?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
												// translators: placeholder: Lesson.
												esc_html_x( 'Essay %s', 'placeholder: Lesson', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'lesson' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
											); ?>: </strong><?php echo wp_kses_post( get_the_title( $essay_quiz_lesson_id ) ); // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
											<?php if ( ! empty( $lesson_quiz_edit_link ) ) { ?>
													<span>(<a href="<?php echo esc_url( $lesson_quiz_edit_link ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)</span>
											<?php } ?>
											</p>
										<?php
									}
								}
							}
							?>
						</div>
						<?php
						// translators: Publish box date format, see https://secure.php.net/date.
						$date_format = esc_html__( 'M j, Y @ H:i', 'learndash' );
						if ( 0 != $essay->ID ) {
							$date = date_i18n( $date_format, strtotime( $essay->post_date ) );
						} else {
							$date = '';
						}

						if ( ( $can_publish ) && ( ! empty( $date ) ) ) : // Contributors don't get to choose the date of publish.
							?>
							<div class="misc-pub-section curtime misc-pub-curtime">
								<span id="timestamp">
									<?php
									echo wp_kses_post(
										sprintf(
											// translators: placeholder: Essay submit date.
											_x( 'Submitted on: <b>%s</b>', 'placeholder: Essay submit date', 'learndash' ),
											esc_html( $date )
										)
									);
									?>
								</span>
							</div>
						<?php endif; ?>

						<?php
						/** This action is documented in https://developer.wordpress.org/reference/hooks/post_submitbox_misc_actions/ */
							do_action( 'post_submitbox_misc_actions' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
						?>
					</div>
					<div class="clear"></div>
				</div>
				<div id="major-publishing-actions">
					<?php
					/** This action is documented in https://developer.wordpress.org/reference/hooks/post_submitbox_start/ */
					do_action( 'post_submitbox_start' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
					?>
					<div id="delete-action">
						<?php
						if ( current_user_can( 'delete_post', $essay->ID ) ) :
							if ( ! EMPTY_TRASH_DAYS ) :
								$delete_text = esc_html__( 'Delete Permanently', 'learndash' );
							else :
								$delete_text = esc_html__( 'Move to Trash', 'learndash' );
							endif;
							?>
							<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $essay->ID ); ?>"><?php echo esc_html( $delete_text ); ?></a>
							<?php
						endif;
						?>
					</div>
					<div id="publishing-action">
						<span class="spinner"></span>
						<?php if ( $can_publish ) : ?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'learndash' ); ?>"/>
							<?php submit_button( esc_html__( 'Update', 'learndash' ), 'primary button-large', 'submit', false ); ?>
						<?php endif; ?>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<?php
		}

		/**
		 * Prints the essay upload metabox content.
		 *
		 * @since 3.2.3
		 *
		 * @param WP_Post $essay The `WP_Post` essay object.
		 */
		public function essay_upload_meta_box( $essay ) {
			$upload = get_post_meta( $essay->ID, 'upload', true );
			if ( ! empty( $upload ) ) {
				printf( '<a target="_blank" href="%1$s">%s</a>', esc_url( $upload ) );
			} else {
				printf(
					// translators: placeholder: question.
					esc_html_x( 'Upload was not provided for this %s', 'placeholder: question', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'question' ) )
				);
			}
		}

		/**
		 * Removes the default submitdiv meta box from the essay post type in the admin edit screen.
		 *
		 * Fires on `admin_menu` hook.
		 *
		 * @since 3.2.3
		 */
		protected function remove_subbmitdiv_metabox() {
			remove_meta_box( 'submitdiv', learndash_get_post_type_slug( 'essay' ), 'side' );
		}

		/**
		 * Save metabox handler function.
		 *
		 * @since 3.2.3
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

			$this->save_essay_status_metabox_data( $post_id, $post, $update );
		}

		/**
		 * Updates the user's essay and quiz data on post save.
		 *
		 * Fires on `save_post_sfwd-essays` hook.
		 *
		 * @since 3.2.3
		 *
		 * @param int     $essay_id ID of the essay to be saved.
		 * @param WP_Post $essay    The `WP_Post` essay object.
		 * @param boolean $update   Whether this is an existing post being updated or not.
		 */
		public function save_essay_status_metabox_data( $essay_id, $essay, $update ) {

			if ( ( ! isset( $_POST['learndash-essay-grading-nonce'] ) ) || ( empty( $_POST['learndash-essay-grading-nonce'] ) ) || ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash-essay-grading-nonce'] ) ), 'learndash-essay-grading-nonce-' . $essay_id ) ) ) {
				return;
			}

			if ( ! isset( $_POST['question_id'] ) || empty( $_POST['question_id'] ) ) {
				return;
			}

			$quiz_id     = absint( $_POST['quiz_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$question_id = absint( $_POST['question_id'] );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

			$submitted_essay = learndash_get_submitted_essay_data( $quiz_id, $question_id, $essay );

			if ( ( isset( $_POST['essay_quiz_post_id'] ) ) && ( ! empty( $_POST['essay_quiz_post_id'] ) ) ) {
				$essay_quiz_post_id = intval( $_POST['essay_quiz_post_id'] );

				update_post_meta( $essay_id, 'quiz_post_id', $essay_quiz_post_id );
			}

			$quiz_score_difference = 0;
			if ( isset( $_POST['post_status'] ) ) {
				if ( ( $_POST['post_status'] != $submitted_essay['status'] ) ) {
					if ( 'graded' === $_POST['post_status'] ) {
						$quiz_score_difference = 1;
					} elseif ( 'not_graded' === $_POST['post_status'] ) {
						$quiz_score_difference = -1;
					}
				}
			}

			$submitted_essay['status']         = sanitize_text_field( wp_unslash( $_POST['post_status'] ) );
			$submitted_essay['points_awarded'] = intval( $_POST['points_awarded'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

			/**
			 * Filters essay status data.
			 *
			 * @since 2.2.0
			 *
			 * @param array $essay_data An array of essay status data.
			 */
			$submitted_essay = apply_filters( 'learndash_essay_status_data', $submitted_essay );
			learndash_update_submitted_essay_data( $quiz_id, $question_id, $essay, $submitted_essay );

			$original_points_awarded = isset( $_POST['original_points_awarded'] ) ? intval( $_POST['original_points_awarded'] ) : null;
			$points_awarded          = isset( $_POST['points_awarded'] ) ? intval( $_POST['points_awarded'] ) : null;

			if ( ! is_null( $original_points_awarded ) && ! is_null( $points_awarded ) ) {
				if ( $points_awarded > $original_points_awarded ) {
					$points_awarded_difference = intval( $points_awarded ) - intval( $original_points_awarded );
				} else {
					$points_awarded_difference = ( intval( $original_points_awarded ) - intval( $points_awarded ) ) * -1;
				}

				$updated_scoring = array(
					'updated_question_score'    => $points_awarded,
					'points_awarded_difference' => $points_awarded_difference,
					'score_difference'          => $quiz_score_difference,
				);

				/**
				 * Filters updated essay scoring data before saving it to post meta.
				 *
				 * @since 2.2.0
				 *
				 * @param array $updated_scoring An array of updated essay score data.
				 */
				$updated_scoring = apply_filters( 'learndash_updated_essay_scoring', $updated_scoring );
				learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring, $essay );

				/**
				 * Fires after all the quiz data is updated.
				 *
				 * @since 2.2.0
				 *
				 * @param int     $quiz_id         Quiz ID.
				 * @param int     $question_id     Question_id.
				 * @param array   $updated_scoring An array of updated essay score data.
				 * @param WP_Post $essay           WP_Post object for essay.
				 */
				do_action( 'learndash_essay_all_quiz_data_updated', $quiz_id, $question_id, $updated_scoring, $essay );
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Essay_Edit();

/**
 * Check if Group Leader can edit Essay.
 *
 * @since 3.4.0
 *
 * parameters documented in /wp-includes/class-wp-user.php
 */
// phpcs:ignore Squiz.Commenting.FunctionComment
function learndash_group_leader_can_edit_essay_filter( $allcaps, $cap, $args, $user ) {
	global $pagenow, $typenow;

	if ( ( 'post.php' !== $pagenow ) && ( 'post-new.php' !== $pagenow ) ) {
		return $allcaps;
	}

	if ( learndash_get_post_type_slug( 'essay' ) !== $typenow ) {
		return $allcaps;
	}

	if ( ! in_array( 'edit_others_essays', $cap, true ) ) {
		return $allcaps;
	}

	if ( ( ! isset( $args[2] ) ) || ( empty( $args[2] ) ) ) {
		return $allcaps;
	}
	$post_id = absint( $args[2] );

	$post = get_post( $post_id );
	if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'essay' ) !== $post->post_type ) ) {
		return $allcaps;
	}

	if ( ( ! isset( $args[1] ) ) || ( empty( $args[1] ) ) || ( ! learndash_is_group_leader_user( $args[1] ) ) ) {
		return $allcaps;
	}
	$gl_user_id = absint( $args[1] );

	$course_id = get_post_meta( $post_id, 'course_id', true );
	$course_id = absint( $course_id );

	if ( ! learndash_check_group_leader_course_user_intersect( $gl_user_id, (int) $post->post_author, $course_id ) ) {
		foreach ( $cap as $cap_slug ) {
			$allcaps[ $cap_slug ] = false;
		}
	}
	return $allcaps;
}

add_action(
	'init',
	function () {
		if ( learndash_is_group_leader_user() ) {
			add_filter( 'user_has_cap', 'learndash_group_leader_can_edit_essay_filter', 10, 4 );
		}
	},
	10
);

