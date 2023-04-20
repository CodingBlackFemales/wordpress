<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Helper_Import {

	private $_content = null;
	private $_error   = false;
	private $_user_id = 0;

	public $import_post_id                  = 0;
	public $import_questions_old_to_new_ids = array();

	public function setImportFileUpload( $file ) {
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			$this->setError( __( 'File was not uploaded', 'learndash' ) );
			return false;
		}

		$this->_content = file_get_contents( $file['tmp_name'] );

		return $this->checkCode();
	}

	/**
	 * Resets default values.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->_user_id                        = 0;
		$this->_content                        = null;
		$this->_error                          = false;
		$this->import_post_id                  = 0;
		$this->import_questions_old_to_new_ids = array();
	}

	public function setImportString( $str ) {
		$this->_content = $str;

		return $this->checkCode();
	}

	private function setError( $str ) {
		$this->_error = $str;
	}

	public function getError() {
		return $this->_error;
	}

	/**
	 * Set the user that runs an import.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function setUserID( int $user_id ): void {
		$this->_user_id = $user_id;
	}

	private function checkCode() {
		$code = substr( $this->_content, 0, 13 );

		$c  = substr( $code, 0, 3 );
		$v1 = substr( $code, 3, 5 );
		$v2 = substr( $code, 8, 5 );

		if ( 'WPQ' !== $c ) {
			$this->setError( __( 'File have wrong format', 'learndash' ) );
			return false;
		}

		if ( $v2 < 3 ) {
			$this->setError( __( 'File is not compatible with the current version', 'learndash' ) );
			return false;
		}

		return true;
	}

	public function getContent() {
		return $this->_content;
	}

	public function getImportData() {

		if ( null === $this->_content ) {
			$this->setError( __( 'File cannot be processed', 'learndash' ) );
			return false;
		}

		$data = substr( $this->_content, 13 );

		$b = base64_decode( $data );

		if ( null === $b ) {
			$this->setError( __( 'File cannot be processed', 'learndash' ) );
			return false;
		}

		$check = $this->saveUnserialize( $b, $o );

		if ( false === $check || ! is_array( $o ) ) {
			$this->setError( __( 'File cannot be processed', 'learndash' ) );
			return false;
		}

		unset( $b );

		return $o;
	}

	public function saveImport( $ids = false ) {
		$data = $this->getImportData();

		if ( false === $data ) {
			return false;
		}

		switch ( $data['exportVersion'] ) {
			case '3':
			case '4':
				return $this->importData( $data, $ids, $data['exportVersion'] );
		}

		return false;
	}

	private function importData( $o, $ids = false, $version = '1' ) {
		$user_id = $this->_user_id > 0 ? $this->_user_id : get_current_user_id();

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		foreach ( $o['master'] as $master ) {
			if ( get_class( $master ) !== 'WpProQuiz_Model_Quiz' ) {
				continue;
			}

			$oldId = $master->getId();

			if ( false !== $ids ) {
				if ( ! in_array( $oldId, $ids ) ) {
					continue;
				}
			}

			$master->setId( 0 );
			$master->setPostId( 0 );

			if ( 3 == $version ) {
				if ( $master->isQuestionOnSinglePage() ) {
					$master->setQuizModus( WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE );
				} elseif ( $master->isCheckAnswer() ) {
					$master->setQuizModus( WpProQuiz_Model_Quiz::QUIZ_MODUS_CHECK );
				} elseif ( $master->isBackButton() ) {
					$master->setQuizModus( WpProQuiz_Model_Quiz::QUIZ_MODUS_BACK_BUTTON );
				} else {
					$master->setQuizModus( WpProQuiz_Model_Quiz::QUIZ_MODUS_NORMAL );
				}
			}

			$quizMapper->save( $master );

			$quiz_insert_data = array(
				'post_type'   => learndash_get_post_type_slug( 'quiz' ),
				'post_title'  => $master->getName(),
				'post_status' => 'publish',
				'post_author' => $user_id,
			);

			if ( ( isset( $o['post'][ $oldId ] ) ) && ( ! empty( $o['post'][ $oldId ] ) ) ) {
				$post_import_keys = array( 'post_title', 'post_content' );

				/**
				 * Filters list of post keys to be imported.
				 *
				 * @param array $post_export_keys An array of post import keys.
				 */
				$post_import_keys = apply_filters( 'learndash_quiz_import_post_keys', $post_import_keys );
				if ( ! empty( $post_import_keys ) ) {
					foreach ( $post_import_keys as $import_key ) {
						if ( isset( $o['post'][ $oldId ][ $import_key ] ) ) {
							$quiz_insert_data[ $import_key ] = $o['post'][ $oldId ][ $import_key ];
						}
					}
				}
			}

			/**
			 * Filters quiz post import data.
			 *
			 * @param array $quiz_insert_data An array of quiz data to be imported.
			 * @param mixed $format_code      Quiz import file format code.
			 */
			$quiz_insert_data = apply_filters( 'learndash_quiz_import_post_data', $quiz_insert_data, 'wpq' );
			$quiz_post_id     = wp_insert_post( $quiz_insert_data );

			if ( ! empty( $quiz_post_id ) ) {
				$this->import_post_id = $quiz_post_id;

				$post_meta_import_keys = array( '_' . get_post_type( $quiz_post_id ), '_viewProfileStatistics', '_timeLimitCookie' );

				/**
				 * Filters quiz post meta keys to be imported.
				 *
				 * @param array $post_meta_keys An array of quiz post meta keys for import.
				 */
				$post_meta_import_keys = apply_filters( 'learndash_quiz_import_post_meta_keys', $post_meta_import_keys );
				if ( ! empty( $post_meta_import_keys ) ) {
					if ( ( isset( $o['post_meta'][ $oldId ] ) ) && ( ! empty( $o['post_meta'][ $oldId ] ) ) ) {
						foreach ( $o['post_meta'][ $oldId ] as $_key => $_key_data ) {
							if ( ( ! empty( $_key ) ) && ( ! empty( $_key_data ) ) && ( in_array( $_key, $post_meta_import_keys, true ) ) ) {
								foreach ( $_key_data as $_data_set ) {
									if ( ( defined( 'LEARNDASH_QUIZ_EXPORT_LEGACY' ) ) && ( true === LEARNDASH_QUIZ_EXPORT_LEGACY ) ) {
										$_data_set = maybe_unserialize( $_data_set );
									}
									update_post_meta( $quiz_post_id, $_key, $_data_set );
								}
							}
						}
					}
				}

				learndash_update_setting( $quiz_post_id, 'quiz_pro', $master->getId() );
				$master->setPostId( $quiz_post_id );

				if ( $master->isStatisticsOn() ) {
					learndash_update_setting( $quiz_post_id, 'statisticsOn', '1' );
				} else {
					learndash_update_setting( $quiz_post_id, 'statisticsOn', '0' );
				}
			}

			if ( ( isset( $o['forms'] ) ) && ( isset( $o['forms'][ $oldId ] ) ) && ( is_array( $o['forms'][ $oldId ] ) ) && ( ! empty( $o['forms'][ $oldId ] ) ) ) {
				foreach ( $o['forms'][ $oldId ] as $form ) {
					/** @var WpProQuiz_Model_Form $form */
					$form->setFormId( 0 );
					$form->setQuizId( $master->getId() );
				}

				$formMapper->update( $o['forms'][ $oldId ] );
			}

			$question_idx   = 0;
			$quiz_questions = array();
			if ( ( isset( $o['question'][ $oldId ] ) ) && ( is_array( $o['question'][ $oldId ] ) ) && ( ! empty( $o['question'][ $oldId ] ) ) ) {
				foreach ( $o['question'][ $oldId ] as $question ) {
					if ( get_class( $question ) !== 'WpProQuiz_Model_Question' ) {
						continue;
					}

					$old_question_post_id = $question->getQuestionPostId();

					$question->setQuizId( $master->getId() );
					$question->setId( 0 );

					$pro_category_id   = $question->getCategoryId();
					$pro_category_name = $question->getCategoryName();
					if ( ! empty( $pro_category_name ) ) {
						$categoryMapper = new WpProQuiz_Model_CategoryMapper();
						$category       = $categoryMapper->fetchByName( $pro_category_name );
						$categoryId     = $category->getCategoryId();
						if ( ( ! empty( $categoryId ) ) && ( absint( $pro_category_id ) !== absint( $categoryId ) ) ) {
							$question->setCategoryId( $category->getCategoryId() );
							$question->setCategoryName( $category->getCategoryName() );
						} else {
							$category->setCategoryName( $question->getCategoryName() );
							$category = $categoryMapper->save( $category );
							$question->setCategoryId( $category->getCategoryId() );
							$question->setCategoryName( $category->getCategoryName() );
						}
					}

					$question_idx++;
					$question->setSort( $question_idx );
					$question = $questionMapper->save( $question );

					$question_post_array = array(
						'post_type'    => learndash_get_post_type_slug( 'question' ),
						'post_title'   => $question->getTitle(),
						'post_content' => $question->getQuestion(),
						'post_status'  => 'publish',
						'post_author'  => $user_id,
						'menu_order'   => $question_idx,
					);
					$question_post_array = wp_slash( $question_post_array );
					$question_post_id    = wp_insert_post( $question_post_array );

					if ( ! empty( $question_post_id ) ) {
						$this->import_questions_old_to_new_ids[ $old_question_post_id ] = $question_post_id;

						update_post_meta( $question_post_id, 'points', absint( $question->getPoints() ) );
						update_post_meta( $question_post_id, 'question_type', $question->getAnswerType() );
						update_post_meta( $question_post_id, 'question_pro_id', absint( $question->getId() ) );

						learndash_update_setting( $question_post_id, 'quiz', $quiz_post_id );
						add_post_meta( $question_post_id, 'ld_quiz_id', $quiz_post_id );

						$quiz_questions[ $question_post_id ] = absint( $question->getId() );
					}

					Learndash_Admin_Import::clear_wpdb_query_cache();
				}
			}

			if ( ! empty( $quiz_questions ) ) {
				update_post_meta( $quiz_post_id, 'ld_quiz_questions', $quiz_questions );
			}
		}

		return true;
	}

	private function saveUnserialize( $str, &$into ) {
		if ( ( defined( 'LEARNDASH_QUIZ_EXPORT_LEGACY' ) ) && ( true === LEARNDASH_QUIZ_EXPORT_LEGACY ) ) {
			$into = @unserialize( $str );
			return false !== $into || rtrim( $str ) === serialize( false );
		} else {
			$import = json_decode( $str, true );
			$import = learndash_array_sanitize_keys_and_values( $import );
			if ( ( is_array( $import ) ) && ( ! empty( $import ) ) ) {
				if ( ( isset( $import['master'] ) ) && ( ! empty( $import['master'] ) ) ) {
					foreach ( $import['master'] as $q_idx => $quiz_array ) {
						if ( is_array( $quiz_array ) ) {
							$quiz_pro = new WpProQuiz_Model_Quiz();
							$quiz_pro->set_array_to_object( $quiz_array );
							$import['master'][ $q_idx ] = $quiz_pro;
						}
					}
				}

				if ( ( isset( $import['question'] ) ) && ( ! empty( $import['question'] ) ) ) {
					foreach ( $import['question'] as $quiz_id => $question_set ) {
						if ( ! empty( $question_set ) ) {
							foreach ( $question_set as $question_id => $question_array ) {
								if ( is_array( $question_array ) ) {
									$question_pro = new WpProQuiz_Model_Question();
									$question_pro->set_array_to_object( $question_array );
									$import['question'][ $quiz_id ][ $question_id ] = $question_pro;
								}
							}
						}
					}
				}

				if ( ( isset( $import['forms'] ) ) && ( ! empty( $import['forms'] ) ) ) {
					foreach ( $import['forms'] as $quiz_id => $form_set ) {
						if ( ! empty( $form_set ) ) {
							foreach ( $form_set as $form_id => $form_array ) {
								if ( is_array( $form_array ) ) {
									$form_pro = new WpProQuiz_Model_Form();
									$form_pro->set_array_to_object( $form_array );
									$import['forms'][ $quiz_id ][ $form_id ] = $form_pro;
								}
							}
						}
					}
				}

				$into = $import;
				return true;
			}
		}
	}
}
