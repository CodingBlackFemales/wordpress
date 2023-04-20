<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Helper_Export {

	const WPPROQUIZ_EXPORT_VERSION = 4;

	public function export( $ids ) {
		$export = array();

		$export['version']                       = WPPROQUIZ_VERSION;
		$export['exportVersion']                 = self::WPPROQUIZ_EXPORT_VERSION;
		$export['ld_version']                    = LEARNDASH_VERSION;
		$export['LEARNDASH_SETTINGS_DB_VERSION'] = LEARNDASH_SETTINGS_DB_VERSION;
		$export['date']                          = time();

		$v    = str_pad( WPPROQUIZ_VERSION, 5, '0', STR_PAD_LEFT );
		$v   .= str_pad( self::WPPROQUIZ_EXPORT_VERSION, 5, '0', STR_PAD_LEFT );
		$code = 'WPQ' . $v;

		$export['master'] = $this->getQuizMaster( $ids );

		foreach ( $export['master'] as $master ) {
			$export['question'][ $master->getId() ]  = $this->getQuestion( $master );
			$export['forms'][ $master->getId() ]     = $this->getForms( $master->getId() );
			$export['post'][ $master->getId() ]      = $this->getPostContent( $master );
			$export['post_meta'][ $master->getId() ] = $this->getPostMeta( $master );
		}

		if ( ( defined( 'LEARNDASH_QUIZ_EXPORT_LEGACY' ) ) && ( true === LEARNDASH_QUIZ_EXPORT_LEGACY ) ) {
			return $code . base64_encode( serialize( $export ) );
		} else {
			if ( ( isset( $export['master'] ) ) && ( ! empty( $export['master'] ) ) ) {
				foreach ( $export['master'] as $q_idx => $quiz ) {
					if ( is_a( $quiz, 'WpProQuiz_Model_Quiz' ) ) {
						$export['master'][ $q_idx ] = $quiz->get_object_as_array();
					}
				}
			}

			if ( ( isset( $export['question'] ) ) && ( ! empty( $export['question'] ) ) ) {
				foreach ( $export['question'] as $quiz_id => $question_set ) {
					if ( ! empty( $question_set ) ) {
						foreach ( $question_set as $question_id => $question ) {
							if ( is_a( $question, 'WpProQuiz_Model_Question' ) ) {
								$question = $question->get_object_as_array();
								if ( ( isset( $question['_answerData'] ) ) && ( ! empty( $question['_answerData'] ) ) ) {
									foreach ( $question['_answerData'] as $a_idx => $answer ) {
										if ( is_a( $answer, 'WpProQuiz_Model_AnswerTypes' ) ) {
											$question['_answerData'][ $a_idx ] = $answer->get_object_as_array();
										}
									}
								}
								$export['question'][ $quiz_id ][ $question_id ] = $question;
							}
						}
					}
				}
			}

			if ( ( isset( $export['forms'] ) ) && ( ! empty( $export['forms'] ) ) ) {
				foreach ( $export['forms'] as $quiz_id => $form_set ) {
					if ( ! empty( $form_set ) ) {
						foreach ( $form_set as $form_id => $form ) {
							if ( is_a( $form, 'WpProQuiz_Model_Form' ) ) {
								$export['forms'][ $quiz_id ][ $form_id ] = $form->get_object_as_array();
							}
						}
					}
				}
			}

			return $code . base64_encode( wp_json_encode( $export ) );
		}
	}

	private function getQuizMaster( $ids = array() ) {
		$r = array();
		if ( ! empty( $ids ) ) {
			$m = new WpProQuiz_Model_QuizMapper();
			foreach ( $ids as $quiz_post_id ) {
				$quiz_post_id = absint( $quiz_post_id );
				if ( ! empty( $quiz_post_id ) ) {
					$quiz_pro_id = learndash_get_setting( $quiz_post_id, 'quiz_pro' );
					if ( ! empty( $quiz_pro_id ) ) {
						$master = $m->fetch( $quiz_pro_id );
						if ( ( $master ) && ( is_a( $master, 'WpProQuiz_Model_Quiz' ) ) && ( $master->getId() > 0 ) ) {
							$master->setPostId( $quiz_post_id );
							$r[] = $master;
						}
					}
				}
			}
		}

		return $r;
	}

	public function getQuestion( $quiz_pro ) {
		if ( ( ! empty( $quiz_pro ) ) && ( is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) ) {
			$m = new WpProQuiz_Model_QuestionMapper();
			return $m->fetchAll( $quiz_pro );
		}
	}

	public function getPostContent( $quiz_pro ) {
		if ( ( ! empty( $quiz_pro ) ) && ( is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) ) {
			$quiz_post_id = $quiz_pro->getPostId();
			if ( ! empty( $quiz_post_id ) ) {
				$post_export_keys = array( 'post_title', 'post_content' );

				/**
				 * Filters list of post keys to be exported.
				 *
				 * @param array $post_export_keys An array of post export keys.
				 * @param int   $quiz_post_id     Quiz post ID.
				 */
				$post_export_keys = apply_filters( 'learndash_quiz_export_post_keys', $post_export_keys, $quiz_post_id );
				if ( ! empty( $post_export_keys ) ) {
					$quiz_post      = get_post( $quiz_post_id, ARRAY_A );
					$quiz_post_keys = array();
					foreach ( $post_export_keys as $export_key ) {
						if ( isset( $quiz_post[ $export_key ] ) ) {
							$quiz_post_keys[ $export_key ] = $quiz_post[ $export_key ];
						}
					}
					return $quiz_post_keys;
				}
			}
		}
	}

	public function getPostMeta( $quiz_pro ) {
		if ( ( ! empty( $quiz_pro ) ) && ( is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) ) {
			$quiz_post_id = $quiz_pro->getPostId();
			if ( ! empty( $quiz_post_id ) ) {
				$post_meta_export_keys = array( '_' . get_post_type( $quiz_post_id ), '_viewProfileStatistics', '_timeLimitCookie' );

				/**
				 * Filters quiz post meta keys to be imported.
				 *
				 * @param array $post_meta_keys An array of quiz post meta keys for export.
				 * @param int   $quiz_post_id   Quiz post ID.
				 */
				$post_meta_export_keys = apply_filters( 'learndash_quiz_export_post_meta_keys', $post_meta_export_keys, $quiz_post_id );

				$all_post_meta = get_post_meta( $quiz_post_id );
				if ( ! empty( $all_post_meta ) ) {
					foreach ( $all_post_meta as $_key => $_data ) {
						if ( ! in_array( $_key, $post_meta_export_keys ) ) {
							unset( $all_post_meta[ $_key ] );
						} else {
							if ( is_array( $_data ) ) {
								foreach ( $_data as $_idx => $_d ) {
									$all_post_meta[ $_key ][ $_idx ] = maybe_unserialize( $_d );
								}
							} else {
								$all_post_meta[ $_key ] = maybe_unserialize( $_data );
							}
						}
					}
				}
				return $all_post_meta;
			}
		}
	}

	private function getForms( $quizId ) {
		$formMapper = new WpProQuiz_Model_FormMapper();

		return $formMapper->fetch( $quizId );
	}
}
