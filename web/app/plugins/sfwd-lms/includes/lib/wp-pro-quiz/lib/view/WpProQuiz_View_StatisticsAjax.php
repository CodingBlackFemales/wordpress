<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_View_StatisticsAjax extends WpProQuiz_View_View {

	public function getHistoryTable() {
		ob_start();

		$this->showHistoryTable();

		$content = ob_get_contents();

		ob_end_clean();

		/**
		 * Filters the quiz statistics history table HTML output.
		 *
		 * @since 2.4.2
		 *
		 * @param string $history_content The History table HTML output.
		 */
		return apply_filters( 'ld_getHistoryTable', $content, array( $this ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	public function showHistoryTable() {
		?>

		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Username', 'learndash' ); ?></th>
					<th scope="col" style="width: 200px;"><?php esc_html_e( 'Date', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Correct', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Incorrect', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Points', 'learndash' ); ?></th>
					<th scope="col" style="width: 60px;"><?php esc_html_e( 'Results', 'learndash' ); ?></th>
				</tr>
			</thead>
			<tbody id="wpProQuiz_statistics_form_data">
				<?php if ( ! count( $this->historyModel ) ) { ?>
				<tr>
					<td colspan="6" style="text-align: center; font-weight: bold; padding: 10px;"><?php esc_html_e( 'No data available', 'learndash' ); ?></td>
				</tr>
				<?php } else { ?>
					<?php foreach ( $this->historyModel as $model ) { ?>
				<tr>
					<th>
						<a href="#" class="user_statistic" data-ref_id="<?php echo absint( $model->getStatisticRefId() ); ?>"><?php echo esc_html( $model->getUserName() ); ?></a>

						<div class="row-actions">
							<span>
								<a style="color: red;" class="wpProQuiz_delete" href="#"><?php esc_html_e( 'Delete', 'learndash' ); ?></a>
							</span>
						</div>

					</th>
					<th><?php echo esc_html( $model->getFormatTime() ); ?></th>
					<th style="color: green;"><?php echo esc_html( $model->getFormatCorrect() ); ?></th>
					<th style="color: red;"><?php echo esc_html( $model->getFormatIncorrect() ); ?></th>
					<th><?php echo esc_html( $model->getPoints() ); ?></th>
					<th style="font-weight: bold;"><?php echo esc_html( $model->getResult() ); ?>%</th>
				</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>

		<?php
	}

	public function getUserTable() {
		ob_start();

		$this->showUserTable();

		$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}

	public function showUserTable() {
		$filepath = SFWD_LMS::get_template( 'learndash_quiz_statistics.css', null, null, true );
		if ( file_exists( $filepath ) ) {
			?>
			<style type="text/css"><?php include $filepath; ?></style>
			<?php
		}

		if ( ( isset( $_POST['data']['quizId'] ) ) && ( ! empty( $_POST['data']['quizId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$quizMapper = new WpProQuiz_Model_QuizMapper();
			$quiz       = $quizMapper->fetch( intval( $_POST['data']['quizId'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			return;
		}
		?>
		<h2>
		<?php
		// translators: placeholder: user name.
		printf( esc_html_x( 'User statistics: %s', 'placeholder: user name', 'learndash' ), esc_html( $this->userName ) );
		?>
		</h2>
		<?php if ( $this->avg ) { ?>
		<h2>
			<?php
			echo date_i18n( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' ),
				$this->statisticModel->getMinCreateTime()
			);
			?>
			-
			<?php
			echo date_i18n( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' ),
				$this->statisticModel->getMaxCreateTime()
			);
			?>
		</h2>
		<?php } else { ?>
		<h2>
			<?php
			echo WpProQuiz_Helper_Until::convertTime( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$this->statisticModel->getCreateTime(),
				LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' )
			);
			?>
		</h2>
		<?php } ?>

		<?php $this->formTable(); ?>

		<table class="wp-list-table widefat" style="margin-top: 20px;">
			<thead>
				<tr>
					<th scope="col" style="width: 50px;"></th>
					<th scope="col"><?php esc_html_e( 'Question', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Points', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Correct', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Incorrect', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Hints used', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Time', 'learndash' ); ?> <span style="font-size: x-small;">(hh:mm:ss)</span></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Points scored', 'learndash' ); ?></th>
					<th scope="col" style="width: 95px;"><?php esc_html_e( 'Results', 'learndash' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$gCorrect   = 0;
					$gIncorrect = 0;
					$gHintCount = 0;
					$gPoints    = 0;
					$gGPoints   = 0;
					$gTime      = 0;

				foreach ( $this->userStatistic as $cat ) {
					$cCorrect   = 0;
					$cIncorrect = 0;
					$cHintCount = 0;
					$cPoints    = 0;
					$cGPoints   = 0;
					$cTime      = 0;
					?>
				<tr class="categoryTr">
					<th colspan="9">
						<span><?php esc_html_e( 'Category', 'learndash' ); ?>:</span>
						<span style="font-weight: bold;"><?php echo esc_html( $cat['categoryName'] ); ?></span>
					</th>
				</tr>
					<?php
					$index = 1;
					foreach ( $cat['questions'] as $q ) {

						$q['questionShowMsgs'] = ! $quiz->isHideAnswerMessageBox();

						/**
						 * Filters quiz question statistics data.
						 *
						 * @param array                $question_data  An array of question statistics data.
						 * @param WpProQuiz_Model_Quiz $quiz           Quiz model object.
						 * @param array                $http_post_data An array of global http post data.
						 */
						$q = apply_filters( 'learndash_question_statistics_data', $q, $quiz, $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
						if ( ( empty( $q ) ) || ( ! is_array( $q ) ) ) {
							continue;
						}

						$sum = $q['correct'] + $q['incorrect'];

						$cPoints    += $q['points'];
						$cGPoints   += $q['gPoints'];
						$cCorrect   += $q['correct'];
						$cIncorrect += $q['incorrect'];
						$cHintCount += $q['hintCount'];
						$cTime      += $q['time'];
						?>
				<tr>
					<th><?php echo esc_html( $index++ ); ?></th>
					<th>
						<?php
						if ( ! $this->avg && null !== $q['statistcAnswerData'] ) {
							/**
							 * Changed above logic which removes all shortcodes and HTML tags. This is better served as a filter.
							 * @since 2.4.0
							*/

							/**
							 * Filters quiz statistics question name.
							 *
							 * @param string $question_name  The question name content.
							 * @param array  $question_data  An array of question statistics data.
							 * @param array  $http_post_data An array of global http post data.
							 */
							$q['questionName'] = apply_filters( 'learndash_quiz_statistics_questionName', $q['questionName'], $q, $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
							if ( ! empty( $q['questionName'] ) ) {
								$q['questionName'] = do_shortcode( $q['questionName'] );
							}
							if ( ! empty( $q['questionName'] ) ) {
								echo wpautop( $q['questionName'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
							<a href="#" class="statistic_data"><?php esc_html_e( '(view)', 'learndash' ); ?></a>
							<?php

						} else {
							/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_View_StatisticsAjax.php */
							$q['questionName'] = apply_filters( 'learndash_quiz_statistics_questionName', $q['questionName'], $q, $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
							if ( ! empty( $q['questionName'] ) ) {
								$q['questionName'] = do_shortcode( $q['questionName'] );
							}
							if ( ! empty( $q['questionName'] ) ) {
								echo wpautop( $q['questionName'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
						?>
					</th>
					<th><?php echo esc_html( $q['gPoints'] ); ?></th>
					<th style="color: green;">
						<?php
						echo $q['correct']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						if ( $sum ) {
							echo ' (' . round( 100 * $q['correct'] / $sum, 2 ) . '%)'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo ' (' . round( $sum, 2 ) . '%)'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
							</th>
					<th style="color: red;">
						<?php
						echo $q['incorrect']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						if ( $sum ) {
							echo ' (' . round( 100 * $q['incorrect'] / $sum, 2 ) . '%)'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo ' (' . round( $sum, 2 ) . '%)'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
						</th>
					<th><?php echo $q['hintCount']; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo WpProQuiz_Helper_Until::convertToTimeString( $q['time'] ); ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo $q['points']; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th>
						<?php
						if ( ( isset( $q['result'] ) ) && ( ! empty( $q['result'] ) ) ) {
							echo $q['result']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</th>
				</tr>
						<?php if ( ! $this->avg && null !== $q['statistcAnswerData'] ) { ?>

					<tr style="display: none;">
						<th colspan="9">
							<?php
							$this->showUserAnswer( $q['questionAnswerData'], $q['statistcAnswerData'], $q['answerType'], $q['questionId'], $quiz );

							/**
							 * Filters whether to show quiz statistics feedback messages.
							 *
							 * @param boolean $show_messages  Whether to show feedback messages.
							 * @param array   $question_data  An array of question statistics data.
							 * @param array   $http_post_data An array of global http post data.
							 */
							$show_messages = apply_filters( 'learndash_quiz_statistics_show_feedback_messages', $q['questionShowMsgs'], $q, $quiz, $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing
							if ( $show_messages ) {
								$answerText = '';
								if ( true == $q['correct'] ) {
									if ( ! isset( $q['questionCorrectMsg'] ) ) {
										$q['questionCorrectMsg'] = '';
									}

									/**
									 * Filters quiz statistics question correct message.
									 *
									 * @param string $correct_message Question correct message.
									 * @param array  $question_data   An array of question statistics data.
									 * @param array  $http_post       An array of global http post data.
									 */
									$q['questionCorrectMsg'] = apply_filters( 'learndash_quiz_statistics_questionCorrectMsg', $q['questionCorrectMsg'], $q, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
									if ( ! empty( $q['questionCorrectMsg'] ) ) {
										$q['questionCorrectMsg'] = do_shortcode( $q['questionCorrectMsg'] );
									}
									if ( ! empty( $q['questionCorrectMsg'] ) ) {
										$answerText = wpautop( $q['questionCorrectMsg'] );
									}
								} elseif ( true == $q['incorrect'] ) {
									if ( ! isset( $q['questionIncorrectMsg'] ) ) {
										$q['questionIncorrectMsg'] = '';
									}

									/**
									 * Filters quiz statistics question incorrect message.
									 *
									 * @param string $incorrect_message Question incorrect message.
									 * @param array  $question_data   An array of question statistics data.
									 * @param array  $http_post       An array of global http post data.
									 */
									$q['questionIncorrectMsg'] = apply_filters( 'learndash_quiz_statistics_questionIncorrectMsg', $q['questionIncorrectMsg'], $q, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
									if ( ! empty( $q['questionIncorrectMsg'] ) ) {
										$q['questionIncorrectMsg'] = do_shortcode( $q['questionIncorrectMsg'] );
									}
									if ( ! empty( $q['questionIncorrectMsg'] ) ) {
										$answerText = wpautop( $q['questionIncorrectMsg'] );
									}
								}

								if ( ! empty( $answerText ) ) {
									?>
									<div class="wpProQuiz_response" style=""><?php echo $answerText; ?></div> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php
								}
							}
							?>
						</th>
					</tr>

							<?php
						}
					}

					$sum    = $cCorrect + $cIncorrect;
					$result = round( ( 100 * $cPoints / $cGPoints ), 2 ) . '%';
					?>
				<tr class="categoryTr" id="wpProQuiz_ctr_222">
					<th colspan="2">
						<span><?php esc_html_e( 'Sub-Total: ', 'learndash' ); ?></span>
					</th>
					<th><?php echo esc_html( $cGPoints ); ?></th>
					<th style="color: green;"><?php echo $cCorrect . ' (' . round( 100 * $cCorrect / $sum, 2 ) . '%)'; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th style="color: red;"><?php echo $cIncorrect . ' (' . round( 100 * $cIncorrect / $sum, 2 ) . '%)'; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo $cHintCount; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo WpProQuiz_Helper_Until::convertToTimeString( $cTime ); ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo $cPoints; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th style="font-weight: bold;"><?php echo $result; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</tr>

				<tr>
					<th colspan="9"></th>
				</tr>
					<?php
					$gPoints    += $cPoints;
					$gGPoints   += $cGPoints;
					$gCorrect   += $cCorrect;
					$gIncorrect += $cIncorrect;
					$gHintCount += $cHintCount;
					$gTime      += $cTime;

				}
				?>
			</tbody>
				<?php
					$sum    = $gCorrect + $gIncorrect;
					$result = round( ( 100 * $gPoints / $gGPoints ), 2 ) . '%';
				?>
			<tfoot>
				<tr id="wpProQuiz_tr_0">
					<th></th>
					<th><?php esc_html_e( 'Total', 'learndash' ); ?></th>
					<th><?php echo $gGPoints; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th style="color: green;"><?php echo $gCorrect . ' (' . round( 100 * $gCorrect / $sum, 2 ) . '%)'; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th style="color: red;"><?php echo $gIncorrect . ' (' . round( 100 * $gIncorrect / $sum, 2 ) . '%)'; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo $gHintCount; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo WpProQuiz_Helper_Until::convertToTimeString( $gTime ); ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th><?php echo $gPoints; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<th style="font-weight: bold;"><?php echo $result; ?></th> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</tr>
			</tfoot>
		</table>

		<div style="margin-top: 10px;">
			<div style="float: left;">
				<a class="button-secondary wpProQuiz_update" href="#"><?php esc_html_e( 'Refresh', 'learndash' ); ?></a>
			</div>
			<div style="float: right;">
				<?php if ( current_user_can( 'wpProQuiz_reset_statistics' ) ) { ?>
					<a class="button-secondary" href="#" id="wpProQuiz_resetUserStatistic"><?php esc_html_e( 'Reset statistics', 'learndash' ); ?></a>
				<?php } ?>
			</div>
			<div style="clear: both;"></div>
		</div>
		<?php
	}

	private function showUserAnswer( $qAnswerData, $sAnswerData, $anserType, $questionId, $quiz ) {
		$matrix = array();

		if ( 'matrix_sort_answer' == $anserType ) {
			foreach ( $qAnswerData as $k => $v ) {
				$matrix[ $k ][] = $k;

				foreach ( $qAnswerData as $k2 => $v2 ) {
					if ( $k != $k2 ) {
						if ( $v->getAnswer() == $v2->getAnswer() ) {
							$matrix[ $k ][] = $k2;
						} elseif ( $v->getSortString() == $v2->getSortString() ) {
							$matrix[ $k ][] = $k2;
						}
					}
				}
			}
		}
		?>
		<ul class="wpProQuiz_questionList">
			<?php
			$count_answer_data = count( $qAnswerData );
			for ( $i = 0; $i < $count_answer_data; $i++ ) {
				$answerText = $qAnswerData[ $i ]->isHtml() ? $qAnswerData[ $i ]->getAnswer() : esc_html( $qAnswerData[ $i ]->getAnswer() );
				$answerText = do_shortcode( $answerText );
				$correct    = '';
				?>
				<?php
				if ( 'single' === $anserType || 'multiple' === $anserType ) {
					if ( ! $quiz->isDisabledAnswerMark() ) {
						if ( $qAnswerData[ $i ]->isCorrect() ) {
							$correct = 'wpProQuiz_answerCorrect';
						} elseif ( isset( $sAnswerData[ $i ] ) && $sAnswerData[ $i ] ) {
							$correct = 'wpProQuiz_answerIncorrect';
						}
					} else {
						$correct = '';
					}
					?>
				<li class="<?php echo esc_attr( $correct ); ?>">
					<label>
						<input disabled="disabled" type="<?php echo 'single' === $anserType ? 'radio' : 'checkbox'; ?>"
							<?php
							if ( isset( $sAnswerData[ $i ] ) ) {
								echo $sAnswerData[ $i ] ? 'checked="checked"' : ''; }
							?>
							>
						<?php echo $answerText; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</label>
				</li>
					<?php
				} elseif ( 'free_answer' === $anserType ) {
					$questionData = learndash_question_free_get_answer_data( $qAnswerData[ $i ] );

					if ( ! $quiz->isDisabledAnswerMark() ) {
						$userResponse_filtered = '';
						if ( isset( $sAnswerData[0] ) ) {
							$userResponse_filtered = stripslashes( trim( $sAnswerData[0] ) );
						}

						$correct_answer = false;
						if ( ( ! empty( $questionData['correct'] ) ) && ( '' !== $userResponse_filtered ) ) {
							foreach ( $questionData['correct'] as $questionData_correct ) {

								$questionData_correct_filtered = stripslashes( trim( $questionData_correct ) );

								/** This filter is documented in includes/quiz/ld-quiz-pro.php */
								if ( apply_filters( 'learndash_quiz_question_free_answers_to_lowercase', true, null ) ) {
									if ( function_exists( 'mb_strtolower' ) ) {
										$userResponse_filtered         = mb_strtolower( $userResponse_filtered );
										$questionData_correct_filtered = mb_strtolower( $questionData_correct_filtered );
									} else {
										$userResponse_filtered         = strtolower( $userResponse_filtered );
										$questionData_correct_filtered = strtolower( $questionData_correct_filtered );
									}
								}

								if ( $userResponse_filtered == $questionData_correct_filtered ) {
									$correct_answer = true;
									break;
								}
							}
						}
						if ( true === $correct_answer ) {
							$correct = 'wpProQuiz_answerCorrect';
						} else {
							$correct = 'wpProQuiz_answerIncorrect';
						}
					} else {
						$correct = '';
					}
					?>
				<li class="<?php echo esc_attr( $correct ); ?>">
					<label>
						<input type="text" disabled="disabled" style="width: 300px; padding: 5px;margin-bottom: 5px;"
							value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
							if ( ( isset( $sAnswerData[0] ) ) && ( '' !== $sAnswerData[0] ) ) {
								echo esc_attr( $sAnswerData[0] );
							}
							?>"> <?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>
					</label>
					<br>
					<?php esc_html_e( 'Correct', 'learndash' ); ?>:
					<?php
					if ( ( isset( $questionData['correct'] ) ) && ( ! empty( $questionData['correct'] ) ) ) {
						foreach ( $questionData['correct'] as $idx => $t_ans ) {
							$questionData['correct'][ $idx ] = esc_attr( $t_ans );
						}
						echo implode( ', ', $questionData['correct'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</li>
					<?php
				} elseif ( 'sort_answer' === $anserType ) {
					if ( ! $quiz->isDisabledAnswerMark() ) {
						$correct = 'wpProQuiz_answerIncorrect';
					} else {
						$correct = '';
					}
					$sortText = '';

					if ( isset( $sAnswerData[ $i ] ) && isset( $qAnswerData[ $sAnswerData[ $i ] ] ) ) {
						if ( $sAnswerData[ $i ] == $i ) {
							if ( ! $quiz->isDisabledAnswerMark() ) {
								$correct = 'wpProQuiz_answerCorrect';
							} else {
								$correct = '';
							}
						}
						$v        = $qAnswerData[ $sAnswerData[ $i ] ];
						$sortText = $v->isHtml() ? $v->getAnswer() : esc_html( $v->getAnswer() );
					}
					?>
				<li class="<?php echo esc_attr( $correct ); ?>">
					<div class="wpProQuiz_sortable">
						<?php echo do_shortcode( $sortText ); ?>
					</div>
				</li>
					<?php
				} elseif ( 'matrix_sort_answer' == $anserType ) {
					if ( ! $quiz->isDisabledAnswerMark() ) {
						$correct = 'wpProQuiz_answerIncorrect';
					} else {
						$correct = '';
					}
					$sortText = '';

					if ( isset( $sAnswerData[ $i ] ) && isset( $qAnswerData[ $sAnswerData[ $i ] ] ) ) {
						if ( in_array( $sAnswerData[ $i ], $matrix[ $i ], true ) ) {
							if ( ! $quiz->isDisabledAnswerMark() ) {
								$correct = 'wpProQuiz_answerCorrect';
							} else {
								$correct = '';
							}
						}

						$v        = $qAnswerData[ $sAnswerData[ $i ] ];
						$sortText = $v->isSortStringHtml() ? $v->getSortString() : esc_html( $v->getSortString() );
					}

					?>
				<li>
					<table>
						<tbody>
							<tr class="wpProQuiz_mextrixTr">
								<td width="20%">
									<div class="wpProQuiz_maxtrixSortText"><?php echo do_shortcode( $answerText ); ?></div>
								</td>
								<td width="80%">
									<ul class="wpProQuiz_maxtrixSortCriterion <?php echo esc_attr( $correct ); ?>">
										<li class="wpProQuiz_sortStringItem" data-pos="0" style="box-shadow: 0px 0px; cursor: auto;">
											<?php echo do_shortcode( $sortText ); ?>
										</li>
									</ul>
								</td>
							</tr>
						</tbody>
					</table>
				</li>
					<?php
				} elseif ( 'cloze_answer' == $anserType ) {
					$cloze_data   = $this->fetchCloze( $qAnswerData[ $i ]->getAnswer(), $sAnswerData, $questionId );
					$cloze_output = learndash_question_cloze_prepare_output( $cloze_data );

					echo $cloze_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				} elseif ( 'assessment_answer' == $anserType ) {
					$assessment = $this->fetchAssessment( $qAnswerData[ $i ]->getAnswer(), $sAnswerData );

					/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
					$assessment = apply_filters( 'learndash_quiz_question_answer_postprocess', $assessment, 'assessment' );
					$assessment = do_shortcode( $assessment );
					echo $assessment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} elseif ( 'essay' == $anserType ) {
					if ( ( ! isset( $sAnswerData['graded_id'] ) ) || ( empty( $sAnswerData['graded_id'] ) ) ) {
						// Due to a bug on LD v2.4.3 the essay file user answer data was not saved. So we need to lookup
						// the essay post ID from the user quiz meta.

						$statisticRefId = $this->statisticModel->getStatisticRefId();
						$quizId         = $this->statisticModel->getQuizId();
						$userId         = $this->statisticModel->getUserId();

						if ( ( ! empty( $userId ) ) && ( ! empty( $quizId ) ) && ( ! empty( $statisticRefId ) ) ) {
							$user_quizzes = get_user_meta( $userId, '_sfwd-quizzes', true );
							if ( ! empty( $user_quizzes ) ) {
								foreach ( $user_quizzes as $user_quiz ) {

									if ( ( isset( $user_quiz['pro_quizid'] ) ) && ( $user_quiz['pro_quizid'] == $quizId ) && ( isset( $user_quiz['statistic_ref_id'] ) ) && ( $user_quiz['statistic_ref_id'] == $statisticRefId ) ) {
										if ( isset( $user_quiz['graded'][ $questionId ] ) ) {
											if ( ( isset( $user_quiz['graded'][ $questionId ]['post_id'] ) ) && ( ! empty( $user_quiz['graded'][ $questionId ]['post_id'] ) ) ) {
												$sAnswerData = array( 'graded_id' => $user_quiz['graded'][ $questionId ]['post_id'] );

												// Once we have the correct post_id we update the quiz statistics for next time.
												global $wpdb;
												$update_ret = $wpdb->update(
													LDLMS_DB::get_table_name( 'quiz_statistic' ),
													array( 'answer_data' => wp_json_encode( $sAnswerData ) ),
													array(
														'statistic_ref_id' => $statisticRefId,
														'question_id' => $questionId,
													),
													array( '%s' ),
													array( '%d', '%d' )
												);

												break;
											}
										}
									}
								}
							}
						}
					}

					if ( ( isset( $sAnswerData['graded_id'] ) ) && ( ! empty( $sAnswerData['graded_id'] ) ) ) {

						$essay_post = get_post( $sAnswerData['graded_id'] );
						if ( $essay_post instanceof WP_Post ) {
							?>
							<li class="<?php echo esc_attr( $correct ); ?>">
								<div class="wpProQuiz_sortable">
									<?php
									if ( 'graded' == $essay_post->post_status ) {
										esc_html_e( 'Status: Graded', 'learndash' );
									} else {
										esc_html_e( 'Status: Not Graded', 'learndash' );
									}

									if ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) || ( get_current_user_id() == $essay_post->post_author ) ) {
										?>
											(<a target="_blank" href="<?php echo esc_url( get_permalink( $sAnswerData['graded_id'] ) ); ?>"><?php esc_html_e( 'view', 'learndash' ); ?></a>)
											<?php
									}

									if ( current_user_can( 'edit_post', $sAnswerData['graded_id'] ) ) {
										?>
											(<a target="_blank" href="<?php echo esc_url( get_edit_post_link( $sAnswerData['graded_id'] ) ); ?>"><?php esc_html_e( 'edit', 'learndash' ); ?></a>)
											<?php
									}
									?>
								</div>
							</li>
							<?php
						}
					} else {
						?>
						<li class="<?php echo esc_attr( $correct ); ?>">
							<div class="wpProQuiz_sortable">
							<?php echo esc_html__( 'Essay not submitted', 'learndash' ); ?>
							</div>
						</li>
						<?php
					}
				}
				?>

			<?php } ?>
		</ul>
		<?php
	}
	private $_assessmetTemp = array();

	private function assessmentCallback( $t ) {
		$a = array_shift( $this->_assessmetTemp );

		return null === $a ? '' : $a;
	}

	private function fetchAssessment( $answerText, $answerData ) {

		$assessment_data = learndash_question_assessment_fetch_data( $answerText, 0, 0 );
		if ( ( isset( $assessment_data['correct'] ) ) && ( is_array( $assessment_data['correct'] ) ) && ( ! empty( $assessment_data['correct'] ) ) ) {
			$user_ans_idx = absint( $answerData[0] ) - 1;

			$a = '';
			foreach ( $assessment_data['correct'] as $ans_idx => $ans_label ) {
				$a .= '<label><input type="radio" disabled="disabled" ' . checked( $ans_idx, $user_ans_idx, false ) . '>' . esc_html( $ans_label ) . '</label>';
			}

			$ans_idx                                 = 0;
			$replace_key                             = '@@wpProQuizAssessment-' . $ans_idx . '@@';
			$assessment_data['data'][ $replace_key ] = $a;

			return learndash_question_assessment_prepare_output( $assessment_data );
		}
	}

	private $_clozeTemp = array();

	private function fetchCloze( $answer_text, $answerData, $question_pro_id = 0 ) {
		$data = array();

		$question_cloze_data = learndash_question_cloze_fetch_data( $answer_text );
		$question_model      = fetchQuestionModel( $question_pro_id );

		$answerData_check = array_map( 'trim', $answerData );

		/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
		if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', true ) ) {
			if ( function_exists( 'mb_strtolower' ) ) {
				$answerData_check = array_map( 'mb_strtolower', $answerData_check );
			} else {
				$answerData_check = array_map( 'strtolower', $answerData_check );
			}
		}

		foreach ( $question_cloze_data['correct'] as $correct_key => $correct_set ) {
			$correct_class    = 'wpProQuiz_answerIncorrect';
			$correct_value    = '---';
			$points           = array();
			$answers_row_text = '';

			if ( ( is_array( $correct_set ) ) && ( ! empty( $correct_set ) ) ) {
				if ( ( isset( $answerData_check[ $correct_key ] ) ) && ( ! empty( $answerData_check[ $correct_key ] ) ) ) {
					$correct_value = $answerData_check[ $correct_key ];
					$answer_idx    = array_search( $answerData_check[ $correct_key ], $correct_set, true );
					if ( false !== $answer_idx ) {
						$correct_class = 'wpProQuiz_answerCorrect';

						if ( isset( $question_cloze_data['points'][ $correct_key ][ $answer_idx ] ) ) {
							$points = $question_cloze_data['points'][ $correct_key ][ $answer_idx ];
						}
					}
				}

				foreach ( $correct_set as $correct_set_idx => $correct_set_value ) {
					$correct_set_text = '';

					$correct_set_text = '"' . $correct_set_value . '"';
					if ( $question_model->isAnswerPointsActivated() ) {
						if ( isset( $question_cloze_data['points'][ $correct_key ][ $correct_set_idx ] ) ) {
							$points = $question_cloze_data['points'][ $correct_key ][ $correct_set_idx ];

							$correct_set_text .= ' <span class="wpProQuiz_cloze_answerPoints">' . sprintf(
								// translators: placeholder points.
								_n( '- %dpt', '- %dpts', absint( $points ), 'learndash' ),
								number_format_i18n( $points )
							) . '</span>';
						}
					}

					if ( ! empty( $answers_row_text ) ) {
						$answers_row_text .= ', ';
					}
					$answers_row_text .= $correct_set_text;
				}
			}

			$a  = '<span class="wpProQuiz_cloze ' . $correct_class . '">' . esc_html( $correct_value ) . '</span> ';
			$a .= '<span class="wpProQuiz_answers wpProQuiz_answers_cloze">(' . $answers_row_text . ')</span>';

			$replace_key = '@@wpProQuizCloze-' . $correct_key . '@@';

			$data['correct'][]            = $correct_value;
			$data['points'][]             = $points;
			$data['data'][ $replace_key ] = $a;
		}

		if ( isset( $question_cloze_data['replace'] ) ) {
			$data['replace'] = $question_cloze_data['replace'];
		}

		return $data;
	}

	private function clozeCallback( $t ) {
		$a = array_shift( $this->_clozeTemp );

		return null === $a ? '' : $a;
	}

	private function formTable() {
		if ( null === $this->forms || null === $this->statisticModel ) {
			return;
		}

		$formData = $this->statisticModel->getFormData();

		if ( null === $formData ) {
			return;
		}

		?>

		<div id="wpProQuiz_form_box">
			<div id="poststuff">
				<div class="postbox">
					<h3 class="hndle"><?php esc_html_e( 'Custom fields', 'learndash' ); ?></h3>
					<div class="inside">
						<table>
							<tbody>
								<?php
								foreach ( $this->forms as $form ) {
									if ( ! isset( $formData[ $form->getFormId() ] ) ) {
										continue;
									}

									$str = $formData[ $form->getFormId() ];
									?>
									<tr>
										<td style="padding: 5px;"><?php echo esc_html( $form->getFieldname() ); ?></td>
										<td>
											<?php
											switch ( $form->getType() ) {
												case WpProQuiz_Model_Form::FORM_TYPE_TEXT:
												case WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA:
												case WpProQuiz_Model_Form::FORM_TYPE_EMAIL:
												case WpProQuiz_Model_Form::FORM_TYPE_NUMBER:
												case WpProQuiz_Model_Form::FORM_TYPE_RADIO:
												case WpProQuiz_Model_Form::FORM_TYPE_SELECT:
													echo esc_html( $str );
													break;
												case WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX:
													echo '1' == $str ? esc_html__( 'ticked', 'learndash' ) : esc_html__( 'not ticked', 'learndash' );
													break;
												case WpProQuiz_Model_Form::FORM_TYPE_YES_NO:
													echo 1 == $str ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' );
													break;
												case WpProQuiz_Model_Form::FORM_TYPE_DATE:
													echo date_format( date_create( $str ), get_option( 'date_format' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													break;
											}
											?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function getOverviewTable() {
		ob_start();

		$this->showOverviewTable();

		$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}

	public function showOverviewTable() {
		?>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'User', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Points', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Correct', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Incorrect', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Hints used', 'learndash' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Time', 'learndash' ); ?> <span style="font-size: x-small;">(hh:mm:ss)</span></th>
					<th scope="col" style="width: 60px;"><?php esc_html_e( 'Results', 'learndash' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! count( $this->statisticModel ) ) { ?>
				<tr>
					<td colspan="7" style="text-align: center; font-weight: bold; padding: 10px;"><?php esc_html_e( 'No data available', 'learndash' ); ?></td>
				</tr>
				<?php } else { ?>

					<?php
					foreach ( $this->statisticModel as $model ) {
						/** @var WpProQuiz_Model_StatisticOverview  $model **/
						$sum = $model->getCorrectCount() + $model->getIncorrectCount();

						if ( ! $model->getUserId() ) {
							$model->setUserName( __( 'Anonymous', 'learndash' ) );
						}

						if ( $sum ) {
							$points    = $model->getPoints();
							$correct   = $model->getCorrectCount() . ' (' . round( 100 * $model->getCorrectCount() / $sum, 2 ) . '%)';
							$incorrect = $model->getIncorrectCount() . ' (' . round( 100 * $model->getIncorrectCount() / $sum, 2 ) . '%)';
							$hintCount = $model->getHintCount();
							$time      = WpProQuiz_Helper_Until::convertToTimeString( $model->getQuestionTime() );
							$result    = round( ( 100 * $points / $model->getGPoints() ), 2 ) . '%';
						} else {
							$result    = '---';
							$time      = '---';
							$hintCount = '---';
							$incorrect = '---';
							$correct   = '---';
							$points    = '---';
						}

						?>

				<tr>
					<th>
						<?php if ( $sum ) { ?>
						<a href="#" class="user_statistic" data-user_id="<?php echo absint( $model->getUserId() ); ?>"><?php echo esc_html( $model->getUserName() ); ?></a>
							<?php
						} else {
							echo esc_html( $model->getUserName() );
						}
						?>

						<div <?php echo $sum ? 'class="row-actions"' : 'style="visibility: hidden;"'; ?>>
							<span>
								<a style="color: red;" class="wpProQuiz_delete" href="#"><?php esc_html_e( 'Delete', 'learndash' ); ?></a>
							</span>
						</div>

					</th>
					<th><?php echo esc_html( $points ); ?></th>
					<th style="color: green;"><?php echo esc_html( $correct ); ?></th>
					<th style="color: red;"><?php echo esc_html( $incorrect ); ?></th>
					<th><?php echo esc_html( $hintCount ); ?></th>
					<th><?php echo esc_html( $time ); ?></th>
					<th style="font-weight: bold;"><?php echo esc_html( $result ); ?></th>
				</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>

		<?php
	}
}
