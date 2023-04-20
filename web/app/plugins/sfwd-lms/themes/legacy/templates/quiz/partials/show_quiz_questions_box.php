<?php
/**
 * Show Quiz Questions Box
 *
 * Available Variables:
 *
 * @var object $quiz_view      WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz           WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int    $question_count Number of Question to display.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- we are inside of a template
$global_points = 0;
$json          = array();
$cat_points    = array();
?>
<div style="display: none;" class="wpProQuiz_quiz">
	<ol class="wpProQuiz_list">
		<?php
		$index = 0;
		foreach ( $quiz_view->question as $question ) {
			$index ++;
			$answer_array = $question->getAnswerData();

			$global_points += $question->getPoints();


			$json[ $question->getId() ]['type']             = $question->getAnswerType();
			$json[ $question->getId() ]['id']               = (int) $question->getId();
			$json[ $question->getId() ]['question_post_id'] = (int) $question->getQuestionPostId();
			$json[ $question->getId() ]['catId']            = (int) $question->getCategoryId(); // cspell:disable-line.

			if ( $question->isAnswerPointsActivated() && $question->isAnswerPointsDiffModusActivated() && $question->isDisableCorrect() ) {
				$json[ $question->getId() ]['disCorrect'] = (int) $question->isDisableCorrect();
			}

			if ( ! isset( $cat_points[ $question->getCategoryId() ] ) ) {
				$cat_points[ $question->getCategoryId() ] = 0;
			}

			$cat_points[ $question->getCategoryId() ] += $question->getPoints();

			if ( ! $question->isAnswerPointsActivated() ) {
				$json[ $question->getId() ]['points'] = $question->getPoints();
			}

			if ( $question->isAnswerPointsActivated() && $question->isAnswerPointsDiffModusActivated() ) {
				$json[ $question->getId() ]['diffMode'] = 1;
			}

			$question_meta = array(
				'type'             => $question->getAnswerType(),
				'question_pro_id'  => $question->getId(),
				'question_post_id' => $question->getQuestionPostId(),
			);

			?>
			<li class="wpProQuiz_listItem" style="display: none;" data-type="<?php echo esc_attr( $question->getAnswerType() ); ?>" data-question-meta="<?php echo htmlspecialchars( wp_json_encode( $question_meta ) ); ?>">
				<div class="wpProQuiz_question_page" <?php $quiz_view->isDisplayNone( $quiz->getQuizModus() != WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE && ! $quiz->isHideQuestionPositionOverview() ); ?> >
				<?php
					echo wp_kses_post(
						SFWD_LMS::get_template(
							'learndash_quiz_messages',
							array(
								'quiz_post_id' => $quiz->getID(),
								'context'      => 'quiz_question_list_2_message',
								'message'      => sprintf(
									// translators: placeholder: question, question number, questions total.
									esc_html_x( '%1$s %2$s of %3$s', 'placeholder: question, question number, questions total', 'learndash' ),
									learndash_get_custom_label( 'question' ),
									'<span>' . $index . '</span>',
									'<span>' . $question_count . '</span>'
								),
								'placeholders' => array( $index, $question_count ),
							)
						)
					);
				?>
				</div>
				<h5 style="<?php echo $quiz->isHideQuestionNumbering() ? 'display: none;' : 'display: inline-block;'; ?>" class="wpProQuiz_header">
					<?php
						echo wp_kses_post(
							SFWD_LMS::get_template(
								'learndash_quiz_messages',
								array(
									'quiz_post_id' => $quiz->getID(),
									'context'      => 'quiz_question_list_1_message',
									'message'      => '<span>' . $index . '</span>. ' . esc_html__( 'Question', 'learndash' ),
									'placeholders' => array( $index ),
								)
							)
						);
					?>

				</h5>

				<?php if ( $quiz->isShowPoints() ) { ?>
					<span
						style="font-weight: bold; float: right;">
						<?php
						echo wp_kses_post(
							SFWD_LMS::get_template(
								'learndash_quiz_messages',
								array(
									'quiz_post_id' => $quiz->getID(),
									'context'      => 'quiz_question_points_message',
									// translators: placeholder: total quiz points.
									'message'      => sprintf( esc_html_x( '%s point(s)', 'placeholder: total quiz points', 'learndash' ), '<span>' . $question->getPoints() . '</span>' ),
									'placeholders' => array( $question->getPoints() ),
								)
							)
						);

						?>
						</span>
					<div style="clear: both;"></div>
				<?php } ?>

				<?php if ( $question->getCategoryId() && $quiz->isShowCategory() ) { ?>
					<div style="font-weight: bold; padding-top: 5px;">
						<?php
							echo wp_kses_post(
								SFWD_LMS::get_template(
									'learndash_quiz_messages',
									array(
										'quiz_post_id' => $quiz->getID(),
										'context'      => 'quiz_question_category_message',
										// translators: placeholder: Quiz Category.
										'message'      => sprintf( esc_html_x( 'Category: %s', 'placeholder: Quiz Category', 'learndash' ), '<span>' . esc_html( $question->getCategoryName() ) . '</span>' ),
										'placeholders' => array( esc_html( $question->getCategoryName() ) ),
									)
								)
							);
						?>
					</div>
				<?php } ?>
				<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<?php
							$wpproquiz_question_text = $question->getQuestion();
							$wpproquiz_question_text = sanitize_post_field( 'post_content', $wpproquiz_question_text, 0, 'display' );
							$wpproquiz_question_text = wpautop( $wpproquiz_question_text );
							global $wp_embed;
							$wpproquiz_question_text = $wp_embed->run_shortcode( $wpproquiz_question_text );
							$wpproquiz_question_text = do_shortcode( $wpproquiz_question_text );
							echo $wpproquiz_question_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to allow HTML / shortcode output
						?>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

					<?php
					/**
					 * Matrix Sort Answer
					 */
					?>
					<?php if ( $question->getAnswerType() === 'matrix_sort_answer' ) { ?>
						<div class="wpProQuiz_matrixSortString">
							<h5 class="wpProQuiz_header">
							<?php
							echo wp_kses_post(
								SFWD_LMS::get_template(
									'learndash_quiz_messages',
									array(
										'quiz_post_id' => $quiz->getID(),
										'context'      => 'quiz_question_sort_elements_header',
										'message'      => esc_html__( 'Sort elements', 'learndash' ),
									)
								)
							);
							?>
							</h5>
							<ul class="wpProQuiz_sortStringList">
							<?php
							$answer_array_new_matrix = array();
							foreach ( $answer_array as $q_idx => $q ) {
								$datapos                             = LD_QuizPro::datapos( $question->getId(), $q_idx );
								$answer_array_new_matrix[ $datapos ] = $q;
							}

							$matrix = array();
							foreach ( $answer_array as $k => $v ) {
								$matrix[ $k ][] = $k;

								foreach ( $answer_array as $k2 => $v2 ) {
									if ( $k != $k2 ) {
										if ( $v->getAnswer() == $v2->getAnswer() ) {
											$matrix[ $k ][] = $k2;
										} elseif ( $v->getSortString() == $v2->getSortString() ) {
											$matrix[ $k ][] = $k2;
										}
									}
								}
							}

							foreach ( $answer_array as $k => $v ) {
								?>
								<li class="wpProQuiz_sortStringItem" data-pos="<?php echo esc_attr( $k ); ?>">
								<?php echo $v->isSortStringHtml() ? do_shortcode( nl2br( $v->getSortString() ) ) : esc_html( $v->getSortString() ); ?>
								</li>
								<?php
							}

								$answer_array = $answer_array_new_matrix;
							?>
							</ul>
							<div style="clear: both;"></div>
						</div>
					<?php } ?>

					<?php
					/**
					 * Print questions in a list for all other answer types
					 */
					?>
					<ul class="wpProQuiz_questionList" data-question_id="<?php echo esc_attr( $question->getId() ); ?>"
						data-type="<?php echo esc_attr( $question->getAnswerType() ); ?>">
						<?php
						if ( $question->getAnswerType() === 'sort_answer' ) {
							$answer_array_new = array();
							foreach ( $answer_array as $q_idx => $q ) {
								$datapos                      = LD_QuizPro::datapos( $question->getId(), $q_idx );
								$answer_array_new[ $datapos ] = $q;
							}
							$answer_array = $answer_array_new;

							if ( $question->getAnswerType() === 'sort_answer' ) {
								$answer_array_org_keys = array_keys( $answer_array );

								/**
								 * Do this while the answer keys match. I just don't trust shuffle to always
								 * return something other than the original.
								 */
								$random_tries = 0;
								while ( true ) {
									// Backup so we don't get stuck because some plugin rewrote a function we are using.
									++$random_tries;

									$answer_array_randon_keys = $answer_array_org_keys;
									shuffle( $answer_array_randon_keys );
									$answer_array_keys_diff = array_diff_assoc( $answer_array_org_keys, $answer_array_randon_keys );

									// If the diff array is not empty or we have reaches enough tries, abort.
									if ( ( ! empty( $answer_array_keys_diff ) ) || ( $random_tries > 10 ) ) {
										break;
									}
								}

								$answer_array_new = array();
								foreach ( $answer_array_randon_keys as $q_idx ) {
									if ( isset( $answer_array[ $q_idx ] ) ) {
										$answer_array_new[ $q_idx ] = $answer_array[ $q_idx ];
									}
								}
								$answer_array = $answer_array_new;
							}
						}

						$answer_index = 0;
						if ( is_array( $answer_array ) ) {
							foreach ( $answer_array as $v_idx => $v ) {
								$answer_text = $v->isHtml() ? do_shortcode( nl2br( $v->getAnswer() ) ) : esc_html( $v->getAnswer() );

								if ( '' == $answer_text && ! $v->isGraded() ) {
									continue;
								}

								if ( $question->isAnswerPointsActivated() ) {
									$json[ $question->getId() ]['points'][] = $v->getPoints();
								}

								$datapos = $answer_index;
								if ( $question->getAnswerType() === 'sort_answer' || $question->getAnswerType() === 'matrix_sort_answer' ) {
									$datapos = $v_idx; // LD_QuizPro::datapos( $question->getId(), $answer_index );
								}
								?>

								<li class="wpProQuiz_questionListItem" data-pos="<?php echo esc_attr( $datapos ); ?>">
									<?php
									/**
									 *  Single/Multiple
									 */
									if ( $question->getAnswerType() === 'single' || $question->getAnswerType() === 'multiple' ) {
										$json[ $question->getId() ]['correct'][] = (int) $v->isCorrect();
										?>
										<span <?php echo $quiz->isNumberedAnswer() ? '' : 'style="display:none;"'; ?>></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="<?php echo $question->getAnswerType() === 'single' ? 'radio' : 'checkbox'; ?>"
													name="question_<?php echo esc_attr( $quiz->getId() ); ?>_<?php echo esc_attr( $question->getId() ); ?>"
													value="<?php echo esc_attr( ( $answer_index + 1 ) ); ?>"> <?php echo $answer_text; ?>
										</label>

										<?php
										/**
										 *  Sort Answer
										 */
									} elseif ( $question->getAnswerType() === 'sort_answer' ) {
										$json[ $question->getId() ]['correct'][] = (int) $answer_index;
										?>
										<div class="wpProQuiz_sortable">
											<?php echo $answer_text; ?>
										</div>

										<?php
										/**
										 *  Free Answer
										 */
									} elseif ( $question->getAnswerType() === 'free_answer' ) {
										$question_answer_data = learndash_question_free_get_answer_data( $v, $question );
										if ( ( is_array( $question_answer_data ) ) && ( ! empty( $question_answer_data ) ) ) {
											$json[ $question->getId() ] = array_merge( $json[ $question->getId() ], $question_answer_data );
										}
										?>
										<label>
											<input class="wpProQuiz_questionInput" type="text" autocomplete="off"
													name="question_<?php echo esc_attr( $quiz->getId() ); ?>_<?php echo esc_attr( $question->getId() ); ?>"
													style="width: 300px;">
											<span class="wpProQuiz_freeCorrect" style="display:none"></span>
										</label>

										<?php
										/**
										 *  Matrix Sort Answer
										 */
									} elseif ( $question->getAnswerType() === 'matrix_sort_answer' ) {
										$json[ $question->getId() ]['correct'][] = (int) $answer_index;
										$msacw_value                             = $question->getMatrixSortAnswerCriteriaWidth() > 0 ? $question->getMatrixSortAnswerCriteriaWidth() : 20;
										?>
										<table>
											<tbody>
											<tr class="wpProQuiz_mextrixTr">
												<td width="<?php echo esc_attr( $msacw_value ); ?>%">
													<div
														class="wpProQuiz_maxtrixSortText"><?php echo $answer_text; ?></div>
												</td>
												<td width="<?php echo esc_attr( 100 - $msacw_value ); ?>%">
													<ul class="wpProQuiz_maxtrixSortCriterion"></ul>
												</td>
											</tr>
											</tbody>
										</table>

										<?php
										/**
										 *  Cloze Answer
										 */
									} elseif ( $question->getAnswerType() === 'cloze_answer' ) {
										$cloze_data   = learndash_question_cloze_fetch_data( $v->getAnswer() );
										$cloze_output = learndash_question_cloze_prepare_output( $cloze_data );
										echo $cloze_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

										$json[ $question->getId() ]['correct'] = isset( $cloze_data['correct'] ) ? $cloze_data['correct'] : [];

										if ( $question->isAnswerPointsActivated() ) {
											$json[ $question->getId() ]['points'] = $cloze_data['points'];
										}

										/**
										 *  Assessment answer
										 */
									} elseif ( $question->getAnswerType() === 'assessment_answer' ) {

										$assessment_data = learndash_question_assessment_fetch_data( $v->getAnswer(), $quiz->getId(), $question->getId() );

										$json[ $question->getId() ]['correct'] = isset( $assessment_data['correct'] ) ? $assessment_data['correct'] : [];

										if ( $question->isAnswerPointsActivated() ) {
											$json[ $question->getId() ]['points'] = $assessment_data['points'];
										}

										$assessment_output = learndash_question_assessment_prepare_output( $assessment_data );
										echo $assessment_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML / Shortcodes

										/**
										 * Essay answer
										 */
									} elseif ( $question->getAnswerType() === 'essay' ) {
										if ( $v->getGradedType() === 'text' ) :
											?>
											<textarea class="wpProQuiz_questionEssay" rows="10" cols="40"
												name="question_<?php echo esc_attr( $quiz->getId() ); ?>_<?php echo esc_attr( $question->getId() ); ?>"
												id="wpProQuiz_questionEssay_question_<?php echo esc_attr( $quiz->getId() ); ?>_<?php echo esc_attr( $question->getId() ); ?>"
												cols="30" autocomplete="off"
												rows="10"
												placeholder="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
													SFWD_LMS::get_template(
														'learndash_quiz_messages',
														array(
															'quiz_post_id' => $quiz->getID(),
															'context'      => 'quiz_essay_question_textarea_placeholder_message',
															'message'      => esc_html__( 'Type your response here', 'learndash' ),
														)
													)
												); ?>"></textarea> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
											<?php elseif ( $v->getGradedType() === 'upload' ) : ?>
												<?php
													echo wp_kses_post(
														SFWD_LMS::get_template(
															'learndash_quiz_messages',
															array(
																'quiz_post_id' => $quiz->getID(),
																'context'      => 'quiz_essay_question_upload_answer_message',
																'message'      => '<p>' . esc_html__( 'Upload your answer to this question.', 'learndash' ) . '</p>',
															)
														)
													);
												?>
												<form enctype="multipart/form-data" method="post" name="uploadEssay">
													<input type='file' name='uploadEssay[]' id='uploadEssay_<?php echo esc_attr( $question->getId() ); ?>' size='35' class='wpProQuiz_upload_essay' />
													<input type="submit" id='uploadEssaySubmit_<?php echo esc_attr( $question->getId() ); ?>' value="<?php esc_html_e( 'Upload', 'learndash' ); ?>" />
													<input type="hidden" id="_uploadEssay_nonce_<?php echo esc_attr( $question->getId() ); ?>" name="_uploadEssay_nonce" value="<?php echo esc_attr( wp_create_nonce( 'learndash-upload-essay-' . $question->getId() ) ); ?>" />
													<input type="hidden" class="uploadEssayFile" id='uploadEssayFile_<?php echo esc_attr( $question->getId() ); ?>' value="" />
												</form>
												<div id="uploadEssayMessage_<?php echo esc_attr( $question->getId() ); ?>" class="uploadEssayMessage"></div>
											<?php else : ?>
												<?php esc_html_e( 'Essay type not found', 'learndash' ); ?>
											<?php endif; ?>

											<p class="graded-disclaimer">
												<?php if ( 'graded-full' == $v->getGradingProgression() ) : ?>
													<?php
													echo wp_kses_post(
														SFWD_LMS::get_template(
															'learndash_quiz_messages',
															array(
																'quiz_post_id' => $quiz->getID(),
																'context'      => 'quiz_essay_question_graded_full_message',
																'message'      => esc_html__( 'This response will be awarded full points automatically, but it can be reviewed and adjusted after submission.', 'learndash' ),
															)
														)
													);
													?>
												<?php elseif ( 'not-graded-full' == $v->getGradingProgression() ) : ?>
													<?php
														echo wp_kses_post(
															SFWD_LMS::get_template(
																'learndash_quiz_messages',
																array(
																	'quiz_post_id' => $quiz->getID(),
																	'context'      => 'quiz_essay_question_not_graded_full_message',
																	'message'      => esc_html__( 'This response will be awarded full points automatically, but it will be reviewed and possibly adjusted after submission.', 'learndash' ),
																)
															)
														);
													?>
												<?php elseif ( 'not-graded-none' == $v->getGradingProgression() ) : ?>
													<?php
														echo wp_kses_post(
															SFWD_LMS::get_template(
																'learndash_quiz_messages',
																array(
																	'quiz_post_id' => $quiz->getID(),
																	'context'      => 'quiz_essay_question_not_graded_none_message',
																	'message'      => esc_html__( 'This response will be reviewed and graded after submission.', 'learndash' ),
																)
															)
														);
													?>
												<?php endif; ?>
											</p>
										<?php
									}

									?>
								</li>
								<?php
								$answer_index ++;
							}
						}
						?>
					</ul>
					<?php if ( $question->getAnswerType() === 'sort_answer' ) { ?>
						<div class="wpProQuiz_questionList_containers">
							<p><?php esc_html_e( 'View Answers', 'learndash' ); ?>: <input type="button" class="wpProQuiz_questionList_containers_view_student wpProQuiz_questionList_containers_view_active wpProQuiz_button2" value="<?php esc_html_e( 'Student', 'learndash' ); ?>"> <input type="button" class="wpProQuiz_questionList_containers_view_correct wpProQuiz_button2" value="<?php esc_html_e( 'Correct', 'learndash' ); ?>" /></p>
							<div class="wpProQuiz_questionList_container_student"></div>
							<div class="wpProQuiz_questionList_container_correct"></div>
						</div>
					<?php } ?>
				</div>
				<?php if ( ! $quiz->isHideAnswerMessageBox() ) { ?>
					<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
							<?php if ( $question->isShowPointsInBox() && $question->isAnswerPointsActivated() ) { ?>
								<div>
									<span class="wpProQuiz_response_correct_label" style="float: left;">
									<?php
										echo wp_kses_post(
											SFWD_LMS::get_template(
												'learndash_quiz_messages',
												array(
													'quiz_post_id' => $quiz->getID(),
													'context'      => 'quiz_question_answer_correct_message',
													'message'      => esc_html__( 'Correct', 'learndash' ),
												)
											)
										);
									?>
									</span>
									<span class="wpProQuiz_response_correct_points_label" style="float: right;">
										<?php echo esc_html( $question->getPoints() ) . ' / ' . esc_html( $question->getPoints() ); ?>
										<?php
										echo wp_kses_post(
											SFWD_LMS::get_template(
												'learndash_quiz_messages',
												array(
													'quiz_post_id' => $quiz->getID(),
													'context'      => 'quiz_question_answer_points_message',
													'message'      => esc_html__( 'Points', 'learndash' ),
												)
											)
										);
										?>
									</span>
									<div style="clear: both;"></div>
								</div>
							<?php } elseif ( 'essay' == $question->getAnswerType() ) { ?>
								<?php
								echo wp_kses_post(
									SFWD_LMS::get_template(
										'learndash_quiz_messages',
										array(
											'quiz_post_id' => $quiz->getID(),
											'context'      => 'quiz_essay_question_graded_review_message',
											'message'      => esc_html__( 'Grading can be reviewed and adjusted.', 'learndash' ),
										)
									)
								);
								?>
							<?php } else { ?>
								<span>
								<?php
								echo wp_kses_post(
									SFWD_LMS::get_template(
										'learndash_quiz_messages',
										array(
											'quiz_post_id' => $quiz->getID(),
											'context'      => 'quiz_question_answer_correct_message',
											'message'      => esc_html__( 'Correct', 'learndash' ),
										)
									)
								);
								?>
								</span>
							<?php } ?>
							<<?php echo esc_attr( LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE ); ?> class="wpProQuiz_AnswerMessage"></<?php echo esc_attr( LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE ); ?>>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
							<?php if ( $question->isShowPointsInBox() && $question->isAnswerPointsActivated() ) { ?>
								<div>
									<span style="float: left;">
										<?php
											echo wp_kses_post(
												SFWD_LMS::get_template(
													'learndash_quiz_messages',
													array(
														'quiz_post_id' => $quiz->getID(),
														'context'      => 'quiz_question_answer_incorrect_message',
														'message'      => esc_html__( 'Incorrect', 'learndash' ),
													)
												)
											);
										?>
									</span>
									<span style="float: right;"><span class="wpProQuiz_responsePoints"></span> / <?php echo esc_html( $question->getPoints() ); ?>
									<?php
										echo wp_kses_post(
											SFWD_LMS::get_template(
												'learndash_quiz_messages',
												array(
													'quiz_post_id' => $quiz->getID(),
													'context'      => 'quiz_question_answer_points_message',
													'message'      => esc_html__( 'Points', 'learndash' ),
												)
											)
										);
									?>
									</span>

									<div style="clear: both;"></div>
								</div>
							<?php } elseif ( 'essay' == $question->getAnswerType() ) { ?>
								<?php
								echo wp_kses_post(
									SFWD_LMS::get_template(
										'learndash_quiz_messages',
										array(
											'quiz_post_id' => $quiz->getID(),
											'context'      => 'quiz_essay_question_graded_review_message',
											'message'      => esc_html__( 'Grading can be reviewed and adjusted.', 'learndash' ),
										)
									)
								);
								?>
							<?php } else { ?>
								<span>
								<?php
								echo wp_kses_post(
									SFWD_LMS::get_template(
										'learndash_quiz_messages',
										array(
											'quiz_post_id' => $quiz->getID(),
											'context'      => 'quiz_question_answer_incorrect_message',
											'message'      => esc_html__( 'Incorrect', 'learndash' ),
										)
									)
								);
								?>
							</span>
							<?php } ?>
							<<?php echo esc_attr( LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE ); ?> class="wpProQuiz_AnswerMessage"></<?php echo esc_attr( LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE ); ?>>
						</div>
					</div>
				<?php } ?>

				<?php if ( $question->isTipEnabled() ) { ?>
					<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							<?php
								echo wp_kses_post(
									SFWD_LMS::get_template(
										'learndash_quiz_messages',
										array(
											'quiz_post_id' => $quiz->getID(),
											'context'      => 'quiz_hint_header',
											'message'      => esc_html__( 'Hint', 'learndash' ),
										)
									)
								);
							?>
							</h5>
							<?php
							$tip_message = apply_filters( 'comment_text', $question->getTipMsg(), null, null );
							global $wp_embed;
							$tip_message = $wp_embed->run_shortcode( $tip_message );
							echo do_shortcode( $tip_message );
							?>
						</div>
					</div>
				<?php } ?>

				<?php if ( $quiz->getQuizModus() == WpProQuiz_Model_Quiz::QUIZ_MODUS_CHECK && ! $quiz->isSkipQuestionDisabled() && $quiz->isShowReviewQuestion() ) { ?>
					<input type="button" name="skip" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
						SFWD_LMS::get_template(
							'learndash_quiz_messages',
							array(
								'quiz_post_id' => $quiz->getID(),
								'context'      => 'quiz_skip_button_label',
								// translators: placeholder: question.
								'message'      => sprintf( esc_html_x( 'Skip %s', 'placeholder: question', 'learndash' ), learndash_get_custom_label_lower( 'question' ) ),
							)
						)
					) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left; margin-right: 10px ;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } ?>
				<?php if ( ! is_rtl() ) { ?>
				<input type="button" name="back" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_back_button_label',
							'message'      => esc_html__( 'Back', 'learndash' ),
						)
					)
				) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } else { ?>
					<input type="button" name="next" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
						SFWD_LMS::get_template(
							'learndash_quiz_messages',
							array(
								'quiz_post_id' => $quiz->getID(),
								'context'      => 'quiz_next_button_label',
								'message'      => esc_html__( 'Next', 'learndash' ),
							)
						)
					) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } ?>
				<?php if ( $question->isTipEnabled() ) { ?>
					<input type="button" name="tip" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
						SFWD_LMS::get_template(
							'learndash_quiz_messages',
							array(
								'quiz_post_id' => $quiz->getID(),
								'context'      => 'quiz_hint_button_label',
								'message'      => esc_html__( 'Hint', 'learndash' ),
							)
						)
					) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } ?>
				<input type="button" name="check" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_check_button_label',
							'message'      => esc_html__( 'Check', 'learndash' ),
						)
					)
				) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php if ( ! is_rtl() ) { ?>
				<input type="button" name="next" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_next_button_label',
							'message'      => esc_html__( 'Next', 'learndash' ),
						)
					)
				) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } else { ?>
				<input type="button" name="back" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_back_button_label',
							'message'      => esc_html__( 'Back', 'learndash' ),
						)
					)
				) ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php } ?>
				<div style="clear: both;"></div>

				<?php if ( $quiz->getQuizModus() == WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE ) { ?>
					<div style="margin-bottom: 20px;"></div>
				<?php } ?>
			</li>

		<?php } ?>
	</ol>
	<?php if ( $quiz->getQuizModus() == WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE ) { ?>
		<div>
			<input type="button" name="wpProQuiz_pageLeft" data-text="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
				// translators: placeholder: page number.
				echo esc_html__( 'Page %d', 'learndash' );
			?>" style="float: left; display: none;" class="wpProQuiz_button wpProQuiz_QuestionButton"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
			<input type="button" name="wpProQuiz_pageRight" data-text="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
				// translators: placeholder: page number.
				echo esc_html__( 'Page %d', 'learndash' );
			?>" style="float: right; display: none;" class="wpProQuiz_button wpProQuiz_QuestionButton"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>

			<?php if ( $quiz->isShowReviewQuestion() && ! $quiz->isQuizSummaryHide() ) { ?>
				<input type="button" name="checkSingle" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_quiz_summary_button_label',
							'message'      => sprintf(
								// translators: placeholder: Quiz.
								esc_html_x( '%s Summary', 'Quiz Summary', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							),
						)
					)
				); ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
			<?php } else { ?>
				<input type="button" name="checkSingle" value="<?php echo wp_kses_post( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz->getID(),
							'context'      => 'quiz_finish_button_label',
							'message'      => sprintf(
								// translators: placeholder: Quiz.
								esc_html_x( 'Finish %s', 'placeholder: Quiz', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							),
						)
					)
				); ?>" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right;"> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
			<?php } ?>

			<div style="clear: both;"></div>
		</div>
	<?php } ?>
</div>
<?php
if ( empty( $global_points ) ) {
	$global_points = 1;
}
return array(
	'globalPoints' => $global_points,
	'json'         => $json,
	'catPoints'    => $cat_points,
);
