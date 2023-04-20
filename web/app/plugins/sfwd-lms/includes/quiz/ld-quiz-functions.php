<?php
/**
 * LearnDash Quiz and Question related functions.
 *
 * @since 2.6.0
 * @package LearnDash\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the quiz ID from the pro quiz ID.
 *
 * @todo   purpose of this function and how quiz pro id's relate to quizzes
 *
 * @global wpdb  $wpdb                     WordPress database abstraction object.
 * @global array $learndash_shortcode_atts LearnDash global shortcode attributes.
 *
 * @since 2.6.0
 *
 * @param int $quiz_pro_id Optional. Pro quiz ID. Default 0.
 *
 * @return int Quiz post ID.
 */
function learndash_get_quiz_id_by_pro_quiz_id( $quiz_pro_id = 0 ) {
	global $wpdb;

	global $learndash_shortcode_atts;

	static $quiz_post_ids = array();

	if ( empty( $quiz_pro_id ) ) {
		return;
	}
	$quiz_pro_id = absint( $quiz_pro_id );

	if ( ( isset( $quiz_post_ids[ $quiz_pro_id ] ) ) && ( ! empty( $quiz_post_ids[ $quiz_pro_id ] ) ) ) {
		return $quiz_post_ids[ $quiz_pro_id ];
	} else {
		$quiz_post_ids[ $quiz_pro_id ] = false;

		global $learndash_shortcode_atts;
		if ( ! empty( $learndash_shortcode_atts ) ) {
			foreach ( array_reverse( $learndash_shortcode_atts ) as $shortcode_tag => $shortcode_atts ) {
				if ( in_array( $shortcode_tag, array( 'LDAdvQuiz', 'ld_quiz' ), true ) ) {
					if ( ( isset( $shortcode_atts['quiz_post_id'] ) ) && ( ! empty( $shortcode_atts['quiz_post_id'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz_post_id'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					} elseif ( ( isset( $shortcode_atts['quiz_id'] ) ) && ( ! empty( $shortcode_atts['quiz_id'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz_id'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					} elseif ( ( isset( $shortcode_atts['quiz'] ) ) && ( ! empty( $shortcode_atts['quiz'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					}
				}
			}
		}

		// Before we run all the queries we check the global $post and see if we are showing a Quiz Post.
		$queried_object = get_queried_object();
		if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $queried_object->post_type ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = absint( $queried_object->ID );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		$sql_str      = $wpdb->prepare(
			'SELECT post_id FROM ' . $wpdb->postmeta . ' as postmeta INNER JOIN ' . $wpdb->posts . ' as posts ON posts.ID=postmeta.post_id
			WHERE posts.post_type = %s AND posts.post_status = %s AND postmeta.meta_key = %s',
			'sfwd-quiz',
			'publish',
			'quiz_pro_id_' . absint( $quiz_pro_id )
		);
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = absint( $quiz_post_id );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		$sql_str      = $wpdb->prepare(
			'SELECT post_id FROM ' . $wpdb->postmeta . ' as postmeta INNER JOIN ' . $wpdb->posts . ' as posts ON posts.ID=postmeta.post_id
			WHERE posts.post_type = %s AND posts.post_status = %s AND meta_key = %s AND meta_value = %d',
			'sfwd-quiz',
			'publish',
			'quiz_pro_id',
			absint( $quiz_pro_id )
		);
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			update_post_meta( absint( $quiz_post_id ), 'quiz_pro_id_' . absint( $quiz_pro_id ), absint( $quiz_pro_id ) );
			$quiz_post_ids[ $quiz_pro_id ] = absint( $quiz_post_id );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		// Because we seem to have a mix of int and string values when these are serialized the format to look for end up being somewhat kludge-y.
		$quiz_pro_id_str = sprintf( '%s', absint( $quiz_pro_id ) );
		$quiz_pro_id_len = strlen( $quiz_pro_id_str );

		$like_i = 'sfwd-quiz_quiz_pro";i:' . absint( $quiz_pro_id ) . ';';
		$like_s = '"sfwd-quiz_quiz_pro";s:' . $quiz_pro_id_len . ':"' . $quiz_pro_id_str . '"';

		// Using REGEX because it is slightly faster then OR on text fields pattern search.
		$sql_str      = $wpdb->prepare( 'SELECT post_id FROM ' . $wpdb->postmeta . ' as postmeta INNER JOIN ' . $wpdb->posts . " as posts ON posts.ID=postmeta.post_id WHERE posts.post_type = %s AND posts.post_status = %s AND postmeta.meta_key=%s AND postmeta.meta_value REGEXP '" . $like_i . '|' . $like_s . "'", 'sfwd-quiz', 'publish', '_sfwd-quiz' );
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			$quiz_post_id = absint( $quiz_post_id );
			update_post_meta( $quiz_post_id, 'quiz_pro_id_' . absint( $quiz_pro_id ), absint( $quiz_pro_id ) );
			update_post_meta( $quiz_post_id, 'quiz_pro_id', absint( $quiz_pro_id ) );
			$quiz_post_ids[ $quiz_pro_id ] = $quiz_post_id;
			return $quiz_post_ids[ $quiz_pro_id ];
		}
	}
	return null;
}

/**
 * Gets the question post ID from the pro quiz question ID.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.6.0
 *
 * @param int $question_pro_id Optional. ProQuiz question id. Default 0.
 *
 * @return int|void Question post ID.
 */
function learndash_get_question_post_by_pro_id( $question_pro_id = 0 ) {
	global $wpdb;

	if ( empty( $question_pro_id ) ) {
		return;
	}

	$question_pro_args = array(
		'post_type'      => learndash_get_post_type_slug( 'question' ),
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => 'question_pro_id',
				'value'   => $question_pro_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
		),
	);

	$question_pro_query = new WP_Query( $question_pro_args );
	if ( ( is_a( $question_pro_query, 'WP_Query' ) ) && ( property_exists( $question_pro_query, 'posts' ) ) ) {
		if ( ( ! empty( $question_pro_query->posts ) ) && ( isset( $question_pro_query->posts[0] ) ) ) {
			return $question_pro_query->posts[0];
		}
	}
}

/**
 * Marks a quiz question dirty when the question post is trashed or untrashed.
 *
 * Fires on `transition_post_status` hook.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.6.0
 *
 * @param string  $new_status New post_status value.
 * @param string  $old_status Old post_status value.
 * @param WP_Post $post       The `WP_Post` object instance.
 */
function learndash_transition_quiz_question_post_status( $new_status, $old_status, $post ) {
	global $wpdb;

	if ( $new_status !== $old_status ) {
		if ( ( ! empty( $post ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, array( 'sfwd-question' ), true ) ) === true ) {

			$sql_str  = 'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id = ' . $post->ID . " AND (meta_key = 'quiz_id' OR meta_key LIKE 'ld_quiz_%')";
			$quiz_ids = $wpdb->get_col( $sql_str );
			if ( ! empty( $quiz_ids ) ) {
				$quiz_ids = array_unique( $quiz_ids );
				foreach ( $quiz_ids as $quiz_id ) {
					learndash_set_quiz_questions_dirty( $quiz_id );
				}
			}
		}
	}
}
add_action( 'transition_post_status', 'learndash_transition_quiz_question_post_status', 10, 3 );


/**
 * Sets the 'dirty' flag for the quiz.
 *
 * This 'dirty' flag is used to trigger the Quiz logic to reload the questions
 * via queries instead of using the stored questions post meta. This generally
 * means something changed with the questions.
 *
 * @since 2.6.0
 *
 * @param int $quiz_id Optional. Quiz ID to set dirty flag. Default 0.
 */
function learndash_set_quiz_questions_dirty( $quiz_id = 0 ) {
	if ( ! empty( $quiz_id ) ) {
		$quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $quiz_id ) );
		if ( is_a( $quiz_questions_object, 'LDLMS_Quiz_Questions' ) ) {
			$quiz_questions_object->set_questions_dirty();
		}
	}
}

/**
 * Marks all the quizzes associated with the given question as dirty.
 *
 * @since 2.6.0
 *
 * @param int $question_post_id Optional. Question Post ID. Default 0.
 */
function learndash_set_question_quizzes_dirty( $question_post_id = 0 ) {
	$question_post_id = absint( $question_post_id );
	if ( ! empty( $question_post_id ) ) {
		$question_quiz_ids = learndash_get_quizzes_for_question( $question_post_id, true );
		if ( ! empty( $question_quiz_ids ) ) {
			foreach ( $question_quiz_ids as $question_quiz_id => $quiz_title ) {
				learndash_set_quiz_questions_dirty( $question_quiz_id );
			}
		}
	}
}

/**
 * Adds a WPProQuiz question to mirror a Question post (sfwd-question).
 *
 * @since 2.6.0
 *
 * @param int   $question_pro_id Optional. Post ID of Question (sfwd-question). Default 0.
 * @param array $post_data       Optional. Post Data containing post_title and post_content. Default empty array.
 *
 * @return int The new pro question ID generated from question post.
 */
function learndash_update_pro_question( $question_pro_id = 0, $post_data = array() ) {
	$question_pro_id = absint( $question_pro_id );

	$question_mapper = new WpProQuiz_Model_QuestionMapper();

	if ( isset( $post_data['action'] ) ) {
		switch ( $post_data['action'] ) {
			case 'editpost':
				$proquiz_controller_question = new WpProQuiz_Controller_Question();
				$question_model              = $proquiz_controller_question->getPostQuestionModel( 0, $question_pro_id );
				break;

			case 'new_step':
				$proquiz_controller_question = new WpProQuiz_Controller_Question();
				$question_model              = $proquiz_controller_question->getPostQuestionModel( 0, $question_pro_id );
				break;

			case 'edit_title':
				$question_model = $question_mapper->fetchById( absint( $question_pro_id ) );
				break;

			default:
				break;

		}
	}

	if ( ( isset( $question_model ) ) && ( is_a( $question_model, 'WpProQuiz_Model_Question' ) ) ) {
		if ( ( isset( $post_data['post_type'] ) ) && ( learndash_get_post_type_slug( 'question' ) === $post_data['post_type'] ) ) {
			if ( isset( $post_data['post_title'] ) ) {
				$question_model->setTitle( $post_data['post_title'] );
			}
			if ( isset( $post_data['post_content'] ) ) {
				$question_model->setQuestion( $post_data['post_content'] );
			}

			if ( ( isset( $post_data['post_ID'] ) ) && ( ! empty( $post_data['post_ID'] ) ) ) {
				$quiz_post_id = learndash_get_setting( $post_data['post_ID'], 'quiz' );
				if ( ! empty( $quiz_post_id ) ) {
					$quiz_post_id = absint( $quiz_post_id );
					$quiz_pro_id  = learndash_get_setting( $quiz_post_id, 'quiz_pro' );
					if ( ! empty( $quiz_pro_id ) ) {
						$question_model->setQuizId( $quiz_pro_id );
					}
				}
			}
		}

		$question = $question_mapper->save( $question_model, true );

		learndash_update_question_template( $question, $post_data );

		// After the save we check the question ID in case WPProQuiz changed it.
		$question_pro_id = $question->getId();
		return $question_pro_id;
	}
	return null;
}

/**
 * Handles the question save template logic.
 *
 * @since 2.6.0
 *
 * @param WpProQuiz_Model_Question|null $question  Optional. The `WpProQuiz_Model_Question` instance. Default null.
 * @param array                         $post_data Optional. An array of global HTTP post data. Default empty array.
 *
 * @return mixed on success WpProQuiz_Model_Template instance.
 */
function learndash_update_question_template( $question = null, $post_data = array() ) {
	if ( ( ! empty( $post_data ) ) && ( ! empty( $question ) ) ) {
		$template_mapper = new WpProQuiz_Model_TemplateMapper();
		if ( ( isset( $post_data['templateName'] ) ) && ( ! empty( $post_data['templateName'] ) ) ) {
			$template = new WpProQuiz_Model_Template();
			$template->setType( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION );
			$template->setName( trim( $post_data['templateName'] ) );
		} elseif ( ( isset( $post_data['templateSaveList'] ) ) && ( ! empty( $post_data['templateSaveList'] ) ) ) {
			$template = $template_mapper->fetchById( absint( $post_data['templateSaveList'] ), false );
		}

		if ( ( isset( $template ) ) && ( is_a( $template, 'WpProQuiz_Model_Template' ) ) ) {
			$template->setData(
				array(
					'question' => $question,
				)
			);

			return $template_mapper->save( $template );
		}
	}
}

/**
 * Gets an array of Quiz IDs where the question is used.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.6.0
 *
 * @param int     $question_post_id Optional. Question Post ID. Default 0.
 * @param boolean $return_flat_array Optional. Default is false and will return primary and secondary sub-array sets. Default false.
 *
 * @return array An array of quiz post IDs.
 */
function learndash_get_quizzes_for_question( $question_post_id = 0, $return_flat_array = false ) {
	global $wpdb;

	$quiz_ids = array();

	if ( true !== $return_flat_array ) {
		$course_ids['primary']   = array();
		$course_ids['secondary'] = array();
	}

	if ( ! empty( $question_post_id ) ) {
		$sql_str          = $wpdb->prepare(
			'SELECT postmeta.meta_value as quiz_id, posts.post_title as quiz_title FROM ' . $wpdb->postmeta . ' AS postmeta
				INNER JOIN ' . $wpdb->posts . ' AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = ' . $question_post_id . ' AND postmeta.meta_key LIKE %s ORDER BY quiz_title ASC',
			'quiz_id'
		);
		$quiz_ids_primary = $wpdb->get_results( $sql_str );
		if ( ! empty( $quiz_ids_primary ) ) {
			foreach ( $quiz_ids_primary as $quiz_set ) {
				if ( true === $return_flat_array ) {
					$quiz_ids[ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
				} else {
					$quiz_ids['primary'][ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
				}
			}
		}

		$sql_str            = 'SELECT postmeta.meta_value as quiz_id, posts.post_title as quiz_title FROM ' . $wpdb->postmeta . ' AS postmeta
			INNER JOIN ' . $wpdb->posts . ' AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = ' . $question_post_id . " AND postmeta.meta_key LIKE 'ld_quiz_%' ORDER BY quiz_title ASC";
		$quiz_ids_secondary = $wpdb->get_results( $sql_str );
		if ( ! empty( $quiz_ids_secondary ) ) {
			foreach ( $quiz_ids_secondary as $quiz_set ) {
				if ( true === $return_flat_array ) {
					if ( ! isset( $quiz_ids[ $quiz_set->quiz_id ] ) ) {
						$quiz_ids[ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
					}
				} else {
					if ( ( ! isset( $quiz_ids['primary'][ $quiz_set->quiz_id ] ) ) && ( ! isset( $quiz_ids['secondary'][ $quiz_set->quiz_id ] ) ) ) {
						$quiz_ids['secondary'][ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
					}
				}
			}
		}

		return $quiz_ids;
	}
	return array();
}

/**
 * Gets the quiz ID from a post ID.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.6.0
 *
 * @param int $id Post ID. Optional. Defaults to the global post object. Default null.
 *
 * @return int|false Returns Quiz ID if found otherwise false.
 */
function learndash_get_quiz_id( $id = null ) {
	global $post;

	if ( is_object( $id ) && $id->ID ) {
		$p  = $id;
		$id = $p->ID;
	} elseif ( is_numeric( $id ) ) {
		$p = get_post( $id );
	}

	if ( empty( $id ) ) {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			if ( is_admin() ) {
				global $parent_file, $post_type, $pagenow;
				if (
					( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) )
					|| ( ! in_array( $post_type, array( 'sfwd-question' ), true ) )
				) {
					return false;
				}
			} elseif ( ! is_single() || is_home() ) {
				return false;
			}
		}

		if ( ( $post ) && ( $post instanceof WP_Post ) ) {
			$id = $post->ID;
			$p  = $post;
		} else {
			return false;
		}
	}

	if ( empty( $p->ID ) ) {
		return 0;
	}

	if ( learndash_get_post_type_slug( 'quiz' ) === $p->post_type ) {
		return $p->ID;
	}

	if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
		return absint( $_GET['quiz_id'] );
	} elseif ( ( isset( $_GET['quiz'] ) ) && ( ! empty( $_GET['quiz'] ) ) ) {
		return absint( $_GET['quiz'] );
	} elseif ( ( isset( $_POST['quiz_id'] ) ) && ( ! empty( $_POST['quiz_id'] ) ) ) {
		return absint( $_POST['quiz_id'] );
	} elseif ( ( isset( $_POST['quiz'] ) ) && ( ! empty( $_POST['quiz'] ) ) ) {
		return intval( $_POST['quiz'] );
	} elseif ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
		if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( intval( $_GET['post'] ) ) ) {
			return intval( $_GET['post'] );
		}
	}

	return (int) get_post_meta( $id, 'quiz_id', true );
}


/**
 * Prints content for the quiz navigation meta box for admin.
 *
 * @global string $typenow
 *
 * @since 2.6.0
 */
function learndash_quiz_navigation_admin_box_content() {
	global $typenow;

	$quiz_id      = 0;
	$current_post = false;

	if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
		$quiz_id      = learndash_get_quiz_id( absint( $_GET['post'] ) );
		$current_post = get_post( intval( $_GET['post'] ) );
	}

	if ( ( empty( $quiz_id ) ) && ( isset( $_GET['quiz_id'] ) ) ) {
		$quiz_id = absint( $_GET['quiz_id'] );
	}

	if ( ! empty( $quiz_id ) ) {

		$instance                        = array();
		$instance['show_widget_wrapper'] = true;
		$instance['quiz_id']             = $quiz_id;
		$instance['current_question_id'] = 0;
		$instance['current_type']        = $typenow;

		$question_query_args               = array();
		$question_query_args['pagination'] = 'true';
		$question_query_args['paged']      = 1;

		if ( ( is_a( $current_post, 'WP_Post' ) ) && ( in_array( $current_post->post_type, array( 'sfwd-quiz', 'sfwd-question' ), true ) ) ) {
			if ( in_array( $current_post->post_type, array( 'sfwd-question' ), true ) ) {
				$instance['current_question_id'] = $current_post->ID;
			}
		}

		learndash_quiz_navigation_admin( $quiz_id, $instance, $question_query_args );

		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) == 'yes' ) {
			learndash_quiz_switcher_admin( $quiz_id );
		}
	} else {
		echo sprintf(
			// translators: placeholders: Questions.
			esc_html_x( 'No associated %s', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
	}
}


/**
 * Gets the list of questions associated with the quiz.
 *
 * @since 2.6.0
 *
 * @param integer $quiz_id Optional. The Quiz Post ID. Default 0.
 *
 * @return array An array of quiz question (sfwd-question) post ids.
 */
function learndash_get_quiz_questions( $quiz_id = 0 ) {
	if ( ! empty( $quiz_id ) ) {
		$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $quiz_id ) );
		if ( $ld_quiz_questions_object ) {
			$ld_quiz_questions = $ld_quiz_questions_object->get_questions();
			return $ld_quiz_questions;
		}
	}
	return array();
}

/**
 * Prints the quiz navigation admin template for the widget.
 *
 * @since 2.6.0
 *
 * @param int   $quiz_id             Optional. Quiz Post ID. Default 0.
 * @param array $instance            Optional. An array of widget instance settings. Default empty array.
 * @param array $question_query_args Optional. An array of query arguments for pagination etc. Default empty array.
 */
function learndash_quiz_navigation_admin( $quiz_id = 0, $instance = array(), $question_query_args = array() ) {
	if ( empty( $quiz_id ) ) {
		return;
	}

	$quiz = get_post( absint( $quiz_id ) );
	if ( ( ! $quiz ) || ( ! is_a( $quiz, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'quiz' ) !== $quiz->post_type ) ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = 0;
	}

	$instance['nonce'] = wp_create_nonce( 'ld_quiz_navigation_admin_pager_nonce_' . $quiz->ID . '_' . get_current_user_id() );

	$quiz_navigation_admin_pager = array();
	global $quiz_navigation_admin_pager;

	$question_start_idx = 0;

	$question_list = learndash_get_quiz_questions( $quiz_id );
	if ( ! empty( $question_list ) ) {
		$quiz_questions_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'question_num' );
		if ( ( $quiz_questions_per_page > 0 ) && ( count( $question_list ) > $quiz_questions_per_page ) ) {
			$quiz_navigation_admin_pager['per_page']    = absint( $quiz_questions_per_page );
			$quiz_navigation_admin_pager['total_items'] = count( $question_list );

			$questions_page_chunks                      = array_chunk( $question_list, $quiz_navigation_admin_pager['per_page'], true );
			$quiz_navigation_admin_pager['total_pages'] = count( $questions_page_chunks );

			$quiz_navigation_admin_pager['paged'] = 1;
			if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
				$quiz_navigation_admin_pager['paged'] = absint( $_POST['paged'] );
			} else {
				foreach ( $questions_page_chunks as $paged_idx => $paged_set ) {
					if ( isset( $paged_set[ $instance['current_question_id'] ] ) ) {
						$quiz_navigation_admin_pager['paged'] = $paged_idx + 1;
						break;
					}
				}
			}

			$chunks_paged = $quiz_navigation_admin_pager['paged'] - 1;
			if ( isset( $questions_page_chunks[ $chunks_paged ] ) ) {
				$question_list = $questions_page_chunks[ $chunks_paged ];
			} else {
				$question_list = $questions_page_chunks[0];
			}
		}
	} else {
		echo sprintf(
			// translators: placeholders: Questions.
			esc_html_x( 'No associated %s', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
	}

	SFWD_LMS::get_template(
		'quiz_navigation_admin',
		array(
			'user_id'        => $user_id,
			'quiz_id'        => $quiz_id,
			'widget'         => $instance,
			'questions_list' => $question_list,
		),
		true
	);
}

/**
 * Prints the quiz switcher within the quiz questions in the admin meta box.
 *
 * @since 2.6.0
 *
 * @param int $quiz_id The Quiz Post ID.
 */
function learndash_quiz_switcher_admin( $quiz_id ) {
	$template_file = SFWD_LMS::get_template(
		'quiz_navigation_switcher_admin',
		array(),
		null,
		true
	);

	if ( ! empty( $template_file ) ) {
		include $template_file;
	}
}

/**
 * Handles the AJAX pagination for the quiz questions navigation.
 *
 * @since 2.6.0
 */
function learndash_wp_ajax_ld_quiz_navigation_admin_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {
		if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		} else {
			$paged = 1;
		}

		if ( ( isset( $_POST['widget_data'] ) ) && ( ! empty( $_POST['widget_data'] ) ) ) {
			$widget_data = $_POST['widget_data'];
		} else {
			$widget_data = array();
		}

		if ( ( isset( $widget_data['quiz_id'] ) ) && ( ! empty( $widget_data['quiz_id'] ) ) ) {
			$quiz_id = intval( $widget_data['quiz_id'] );
		} else {
			$quiz_id = 0;
		}

		if ( ( ! empty( $quiz_id ) ) && ( ! empty( $widget_data ) ) ) {
			if ( ( isset( $_POST['widget_data']['nonce'] ) ) && ( ! empty( $_POST['widget_data']['nonce'] ) ) && ( wp_verify_nonce( $_POST['widget_data']['nonce'], 'ld_quiz_navigation_admin_pager_nonce_' . $quiz_id . '_' . get_current_user_id() ) ) ) {
				$questions_query_args               = array();
				$questions_query_args['pagination'] = 'true';
				$questions_query_args['paged']      = $paged;
				$widget_data['show_widget_wrapper'] = false;
				$level                              = ob_get_level();
				ob_start();
				learndash_quiz_navigation_admin( $quiz_id, $widget_data, $questions_query_args );
				$reply_data['content'] = learndash_ob_get_clean( $level );
			}
		}
	}

	echo wp_json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_quiz_navigation_admin_pager', 'learndash_wp_ajax_ld_quiz_navigation_admin_pager' );


/**
 * Syncs the question pro fields with the question post.
 *
 * @since 2.6.0
 *
 * @param int                          $question_post_id Optional. The `WP_Post` Question ID. Default 0.
 * @param WpProQuiz_Model_Question|int $question_pro_id  Optional. WpProQuiz_Model_Question object or ID. Default 0.
 */
function learndash_proquiz_sync_question_fields( $question_post_id = 0, $question_pro_id = 0 ) {

	if ( ( empty( $question_post_id ) ) || ( empty( $question_pro_id ) ) ) {
		return;
	}

	if ( is_a( $question_pro_id, 'WpProQuiz_Model_Question' ) ) {
		$question_pro = $question_pro_id;
	} else {
		$question_pro_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro        = $question_pro_mapper->fetch( absint( $question_pro_id ) );
	}

	if ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) {

		update_post_meta( $question_post_id, 'question_points', intval( $question_pro->getPoints() ) );
		update_post_meta( $question_post_id, 'question_type', $question_pro->getAnswerType() );
		update_post_meta( $question_post_id, 'question_pro_id', intval( $question_pro->getId() ) );
		update_post_meta( $question_post_id, 'question_pro_category', intval( $question_pro->getCategoryId() ) );
	}
}

/**
 * Syncs the `WPProQuiz` question category with the LearnDash question taxonomy.
 *
 * @since 2.6.0
 *
 * @param int                          $question_post_id Optional. The `WP_Post` Question ID. Default 0.
 * @param WpProQuiz_Model_Question|int $question_pro_id  Optional. The `WpProQuiz_Model_Question` object or ID. Default 0.
 *
 * @return array An array of question category terms or newly created category term.
 */
function learndash_proquiz_sync_question_category( $question_post_id = 0, $question_pro_id = 0 ) {

	if ( ( empty( $question_post_id ) ) || ( empty( $question_pro_id ) ) ) {
		return;
	}

	if ( is_a( $question_pro_id, 'WpProQuiz_Model_Question' ) ) {
		$question_pro = $question_pro_id;
	} else {
		$question_pro_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro        = $question_pro_mapper->fetch( absint( $question_pro_id ) );
	}

	if ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) {

		// Sync the Question category with the LD Question Category.
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'ld_question_category' ) == 'yes' ) {
			$question_pro_category_id   = $question_pro->getCategoryId();
			$question_pro_category_name = $question_pro->getCategoryName();
			if ( ( ! empty( $question_pro_category_id ) ) && ( ! empty( $question_pro_category_name ) ) ) {
				$category_query_args = array(
					'taxonomy'   => array( 'ld_question_category' ),
					'hide_empty' => false,
					'name'       => $question_pro_category_name,
				);
				$category_terms      = get_terms( $category_query_args );
				if ( ! is_wp_error( $category_terms ) ) {
					if ( ! empty( $category_terms ) ) {
						foreach ( $category_terms as $category_term ) {
							wp_set_object_terms( $question_post_id, $category_term->term_id, 'ld_question_category' );
						}
						return $category_terms;

					} else {
						$new_term = wp_insert_term( $question_pro_category_name, 'ld_question_category' );
						if ( isset( $new_term['term_id'] ) ) {
							add_term_meta( absint( $new_term['term_id'] ), 'category_pro_id', $question_pro_category_id );
							wp_set_object_terms( $question_post_id, intval( $new_term['term_id'] ), 'ld_question_category' );

							return $new_term;
						}
					}
				}
			}
		}
	}
	return array();
}

/**
 * Gets all the quiz post IDs from the quiz pro ID.
 *
 * This is similar to the function learndash_get_quiz_id_by_pro_quiz_id() but returns
 * an array instead of a single post ID.
 *
 * @since 2.6.0
 *
 * @param int $quiz_pro_id Optional. The ID of `WPProQuiz` Quiz. Default 0.
 *
 * @return array An array of quiz post IDs.
 */
function learndash_get_quiz_post_ids( $quiz_pro_id = 0 ) {
	static $quiz_post_ids = array();
	$quiz_pro_id          = absint( $quiz_pro_id );
	if ( ! empty( $quiz_pro_id ) ) {
		if ( ! isset( $quiz_post_ids[ $quiz_pro_id ] ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = array();

			if ( ! empty( $quiz_pro_id ) ) {
				$quiz_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'quiz' ),
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'meta_query'     => array(
						array(
							'key'     => 'quiz_pro_id',
							'value'   => absint( $quiz_pro_id ),
							'compare' => '=',
						),
					),
				);

				$quiz_query = new WP_Query( $quiz_query_args );
				if ( ( $quiz_query instanceof WP_Query ) && ( property_exists( $quiz_query, 'posts' ) ) ) {
					$quiz_post_ids[ $quiz_pro_id ] = array_merge( $quiz_post_ids[ $quiz_pro_id ], $quiz_query->posts );
				}
			}
		}

		return $quiz_post_ids[ $quiz_pro_id ];
	}
	return array();
}

/**
 * Gets the `WPProQuiz` Question row column fields.
 *
 * @since 2.6.0
 * @since 3.3.0 Corrected function name
 *
 * @param int          $question_pro_id Optional. The `WPProQuiz` Question ID. Default 0.
 * @param string|array $fields           Optional. An array or comma delimited string of fields to return. Default null.
 *
 * @return array An array of WPProQuiz question field values.
 */
function learndash_get_question_pro_fields( $question_pro_id = 0, $fields = null ) {
	$values = array();

	if ( ( ! empty( $question_pro_id ) ) && ( ! empty( $fields ) ) ) {
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		if ( is_array( $fields ) ) {
			$fields = array_map( 'trim', $fields );
		}

		$question_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro    = $question_mapper->fetch( $question_pro_id );

		foreach ( $fields as $field ) {
			$function = 'get' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
			if ( method_exists( $question_pro, $function ) ) {
				$values[ $field ] = $question_pro->$function();
			} else {
				$values[ $field ] = null;
			}
		}

		return $values;
	}

	return $values;
}

/**
 * Gets the `WPProQuiz` Quiz row column fields.
 *
 * @since 2.6.0
 * @since 3.3.0 Corrected function name
 *
 * @param int          $quiz_pro_id Optional. The `WPProQuiz` Question ID. Default 0.
 * @param string|array $fields       Optional. An array or comma delimited string of fields to return. Default null.
 *
 * @return array An array of `WPProQuiz` quiz field values.
 */
function learndash_get_quiz_pro_fields( $quiz_pro_id = 0, $fields = null ) {
	$values = array();

	if ( ( ! empty( $quiz_pro_id ) ) && ( ! empty( $fields ) ) ) {
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		if ( is_array( $fields ) ) {
			$fields = array_map( 'trim', $fields );
		}

		$quiz_mapper = new WpProQuiz_Model_QuizMapper();
		$quiz_pro    = $quiz_mapper->fetch( $quiz_pro_id );

		foreach ( $fields as $field ) {
			$function = 'get' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
			if ( method_exists( $quiz_pro, $function ) ) {
				$values[ $field ] = $quiz_pro->$function();
			} else {
				$values[ $field ] = null;
			}
		}

		return $values;
	}

	return $values;
}

/**
 * Gets the primary quiz post ID from a pro quiz ID.
 *
 * This function accepts a list of Quiz posts. It is assumed quiz posts
 * all share the same ProQuiz Quiz ID. This function will determine which
 * is the 'primary' quiz post. If one is not found the first in the array
 * will be set as the primary.
 *
 * @since 2.6.0
 *
 * @param int     $quiz_pro_id Optional. The ProQuiz Quiz ID. Default 0.
 * @param boolean $set_first    Optional. If true will take first quiz post found and used as primary. Default true.
 *
 * @return int The primary quiz post ID.
 */
function learndash_get_quiz_primary_shared( $quiz_pro_id = 0, $set_first = true ) {
	static $quiz_primary_post_ids = array();

	$quiz_pro_id = absint( $quiz_pro_id );
	if ( ! empty( $quiz_pro_id ) ) {
		if ( ( ! isset( $quiz_primary_post_ids[ $quiz_pro_id ] ) ) || ( empty( $quiz_primary_post_ids[ $quiz_pro_id ] ) ) ) {
			$quiz_primary_post_ids[ $quiz_pro_id ] = 0;
			$quiz_post_ids                         = learndash_get_quiz_post_ids( $quiz_pro_id );
			if ( ! empty( $quiz_post_ids ) ) {

				$quiz_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'quiz' ),
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'post__in'       => $quiz_post_ids,
					'meta_query'     => array(
						array(
							'key'     => 'quiz_pro_primary_' . $quiz_pro_id,
							'compare' => 'EXISTS',
						),
					),
				);

				$quiz_query = new WP_Query( $quiz_query_args );
				if ( ( is_a( $quiz_query, 'WP_Query' ) ) && ( property_exists( $quiz_query, 'posts' ) ) && ( ! empty( $quiz_query->posts ) ) ) {
					$quiz_primary_post_ids[ $quiz_pro_id ] = $quiz_query->posts[0];
					if ( count( $quiz_query->posts ) > 1 ) {
						foreach ( $quiz_query->posts as $quiz_post_idx => $quiz_post_id ) {
							if ( 0 !== $quiz_post_idx ) {
								delete_post_meta( $quiz_post_id, 'quiz_pro_primary_' . $quiz_pro_id );
							}
						}
					}
				} else {
					if ( true === $set_first ) {
						$quiz_primary_post_ids[ $quiz_pro_id ] = $quiz_post_ids[0];
						update_post_meta( $quiz_primary_post_ids[ $quiz_pro_id ], 'quiz_pro_primary_' . $quiz_pro_id, $quiz_pro_id );
					}
				}
			}
		}

		return $quiz_primary_post_ids[ $quiz_pro_id ];
	}
	return null;
}

/**
 * Sorts the quiz results messages.
 *
 * @since 3.0.0
 *
 * @param array $messages Optional. An array of quiz result messages. Default empty array.
 *
 * @return array An array of sorted quiz result messages.
 */
function learndash_quiz_result_message_sort( $messages = array() ) {
	$sorted = array();
	if ( ( isset( $messages ) ) && ( ! empty( $messages ) ) ) {
		$activ_bypass = false;
		if ( ! isset( $messages['activ'] ) ) {
			$activ_bypass = true;
		}

		for ( $i = 0; $i < LEARNDASH_QUIZ_RESULT_MESSAGE_MAX; $i++ ) {

			if ( true === $activ_bypass ) {
				$activ = 1;
			} else {
				$activ = null;
				if ( isset( $messages['activ'][ $i ] ) ) {
					$activ = absint( $messages['activ'][ $i ] );
				}
			}

			$prozent = null;
			if ( isset( $messages['prozent'][ $i ] ) ) {
				$prozent = (float) str_replace( ',', '.', $messages['prozent'][ $i ] );
			}

			$text = null;
			if ( isset( $messages['text'][ $i ] ) ) {
				$text = $messages['text'][ $i ];
				if ( ! empty( $text ) ) {
					$text = wp_check_invalid_utf8( $text );
					if ( ! empty( $text ) ) {
						$text = sanitize_post_field( 'post_content', $text, 0, 'display' );
						$text = stripslashes( $text );
					}
				} else {
					$activ = null;
				}
			}

			if ( ( ! is_null( $activ ) ) && ( ! empty( $activ ) ) && ( ! is_null( $prozent ) ) && ( ! is_null( $text ) ) ) {
				if ( ! isset( $sorted[ $prozent ] ) ) {
					$sorted[ $prozent ] = array(
						'prozent' => $prozent,
						'activ'   => $activ,
						'text'    => $text,
					);
				}
			}
		}
	}

	if ( ! isset( $sorted[0] ) ) {
		$sorted[0] = array(
			'prozent' => 0,
			'activ'   => 1,
			'text'    => '',
		);
	}

	$result = array();
	if ( ! empty( $sorted ) ) {
		ksort( $sorted );

		foreach ( $sorted as $item ) {
			$result['text'][]    = $item['text'];
			$result['prozent'][] = $item['prozent'];
			$result['activ'][]   = $item['activ'];
		}
	}

	return $result;
}

/**
 * Get Quiz Repeats
 *
 * @since 3.2.3.4
 *
 * @param integer $quiz_post_id Quiz Post ID.
 */
function learndash_quiz_get_repeats( $quiz_post_id = 0 ) {
	$repeats = '';

	$quiz_post_id = absint( $quiz_post_id );
	if ( ! empty( $quiz_post_id ) ) {
		$quiz_settings = learndash_get_setting( $quiz_post_id );
		if ( ( isset( $quiz_settings['retry_restrictions'] ) ) && ( 'on' === $quiz_settings['retry_restrictions'] ) ) {
			$repeats = ( isset( $quiz_settings['repeats'] ) ) ? $quiz_settings['repeats'] : '';
		}
	}

	return $repeats;
}

/**
 * Convert Quiz Lock Cookie
 *
 * @since 3.2.3.4
 *
 * @param array $cookie_quiz Array of Quiz cookie data.
 */
function learndash_quiz_convert_lock_cookie( $cookie_quiz = null ) {
	if ( ! is_array( $cookie_quiz ) ) {
		$cookie_time = $cookie_quiz;
		$cookie_quiz = array(
			'time'  => $cookie_time,
			'count' => 1,
		);
	}

	if ( ! isset( $cookie_quiz['time'] ) ) {
		$cookie_quiz['time'] = 0;
	}

	if ( ! isset( $cookie_quiz['count'] ) ) {
		$cookie_quiz['count'] = 0;
	}

	return $cookie_quiz;
}

/**
 * Quiz Statistics History panel users selector AJAX (select2)
 *
 * This function handles AJAX requests to populate the Users filter
 * selector shown on the Quiz Statistics History panel.
 *
 * @since 3.5.0
 */
function learndash_quiz_statistics_users_select2() {
	$reply_data = array(
		'items'       => array(),
		'total_items' => 0,
		'total_pages' => 1,
	);

	if ( learndash_use_select2_lib_ajax_fetch() ) {
		if ( ( is_user_logged_in() ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
			if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-quiz-statistics-history' . get_current_user_id() ) ) ) {
				$user_query_args = array(
					'orderby'          => 'display_name',
					'order'            => 'ASC',
					'number'           => 10,
					'paged'            => 1,
					'suppress_filters' => true,
					'search_columns'   => array( 'ID', 'user_login', 'user_nicename', 'user_email' ),
				);

				if ( ( learndash_is_group_leader_user() ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
					$included_user_ids = array();
					if ( ( isset( $_POST['quiz_post_id'] ) ) && ( ! empty( $_POST['quiz_post_id'] ) ) ) {
						$included_user_ids = learndash_get_groups_leaders_users_for_course_step( absint( $_POST['quiz_post_id'] ) );
					}

					if ( ! empty( $included_user_ids ) ) {
						$user_query_args['include'] = $included_user_ids;
					} else {
						$user_query_args['include'] = array( 0 );
					}
				}

				if ( ( isset( $_POST['search'] ) ) && ( ! empty( $_POST['search'] ) ) ) {
					$user_query_args['search'] = '*' . esc_attr( $_POST['search'] ) . '*';
				}
				if ( ( isset( $_POST['page'] ) ) && ( ! empty( $_POST['page'] ) ) ) {
					$user_query_args['paged'] = absint( $_POST['page'] );
				}

				/**
				 * Filters quiz statistics user selector query arguments.
				 *
				 * @since 3.5.0
				 *
				 * @param array  $user_query_args An array of query arguments.
				 */
				$user_query_args = apply_filters( 'learndash_quiz_statistics_users_select2_query_args', $user_query_args );
				if ( ! empty( $user_query_args ) ) {
					$user_query = new WP_User_Query( $user_query_args );
					if ( ( $user_query ) && ( is_a( $user_query, 'WP_User_Query' ) ) ) {
						if ( learndash_is_admin_user() ) {
							if ( ( 1 === $user_query_args['paged'] ) && ( ( ! isset( $user_query_args['search'] ) ) ) || ( empty( $user_query_args['search'] ) ) ) {
								$reply_data['items'] = array(
									array(
										'id'       => 'filters_group',
										'text'     => esc_html__( 'Special Filters', 'learndash' ),
										'disabled' => 1,
									),
									array(
										'id'   => '-1',
										'text' => esc_html__( 'all users', 'learndash' ),
									),
									array(
										'id'   => '-2',
										'text' => esc_html__( 'only registered users', 'learndash' ),
									),
									array(
										'id'   => '-3',
										'text' => esc_html__( 'only anonymous users', 'learndash' ),
									),
									array(
										'id'       => 'users_group',
										'text'     => esc_html__( 'Users', 'learndash' ),
										'disabled' => 1,
									),
								);
							}
						}

						if ( property_exists( $user_query, 'results' ) ) {
							foreach ( $user_query->results as $user ) {
								$user_display_name = $user->display_name . ' ( ' . $user->user_email . ' )';

								/**
								 * Filters quiz statistics user selector display name.
								 *
								 * @since 3.5.0
								 *
								 * @param string $user_display_name Display name for selector option.
								 * @param object $user              WP_User instance.
								 */
								$user_display_name = apply_filters( 'learndash_quiz_statistics_users_select2_display_name', $user_display_name, $user );
								if ( ! empty( $user_display_name ) ) {
									$reply_data['items'][] = array(
										'id'   => $user->ID,
										'text' => $user_display_name,
									);
								}
							}
						}
						if ( property_exists( $user_query, 'total_users' ) ) {
							$reply_data['total_items'] = absint( $user_query->total_users );

							if ( property_exists( $user_query, 'query_vars' ) ) {
								if ( isset( $user_query->query_vars['number'] ) ) {
									$reply_data['total_pages'] = floor( absint( $user_query->total_users ) / absint( $user_query->query_vars['number'] ) );
								}
							}
						}
					}
				}

				/**
				 * Filters quiz statistics user selector reply data.
				 *
				 * @since 3.5.0
				 *
				 * @param array  $reply_data      An array of reply data.
				 * @param array  $user_query_args An array of query arguments.
				 */
				$reply_data = apply_filters( 'learndash_quiz_statistics_users_select2_reply_data', $reply_data, $user_query_args );
			}
		}
	}

	echo wp_json_encode( $reply_data );
	wp_die(); // this is required to terminate immediately and return a proper response.
}
add_action( 'wp_ajax_learndash_quiz_statistics_users_select2', 'learndash_quiz_statistics_users_select2' );

/**
 * Utility function to prepare Quiz Resume PHP array to JSON.
 *
 * @since 3.5.1.2
 * @uses `esc_js()`
 *
 * @param array $quiz_resume_data Quiz Resume array.
 */
function learndash_prepare_quiz_resume_data_to_js( $quiz_resume_data = array() ) {
	if ( ! empty( $quiz_resume_data ) ) {
		foreach ( $quiz_resume_data as $key => &$set ) {
			if ( 'formData' === substr( $key, 0, strlen( 'formData' ) ) ) { // Handle the form fields.
				if ( ( isset( $set['type'] ) ) && ( in_array( $set['type'], array( WpProQuiz_Model_Form::FORM_TYPE_TEXT, WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA ) ) ) ) {
					if ( ( isset( $set['value'] ) ) && ( is_string( $set['value'] ) ) && ( ! empty( $set['value'] ) ) ) {
						$set['value'] = esc_js( $set['value'] );
					}
				}
			} elseif ( isset( $set['type'] ) ) { // Handle the question fields.
				if ( ( isset( $set['value'] ) ) && ( ! empty( $set['value'] ) ) ) {
					if ( in_array( $set['type'], array( 'free_answer', 'essay', 'cloze_answer' ), true ) ) {
						if ( is_string( $set['value'] ) ) {
							$set['value'] = esc_js( $set['value'] );
						} elseif ( is_array( $set['value'] ) ) {
							foreach ( $set['value'] as $set_value_idx => &$set_value_value ) {
								if ( ( is_string( $set_value_value ) ) && ( ! empty( $set_value_value ) ) ) {
									$set_value_value = esc_js( $set_value_value );
								}
							}
						}
					}
				}
			} elseif ( 'checked' === substr( $key, 0, strlen( 'checked' ) ) ) {
				if ( ( isset( $set['e']['AnswerMessage'] ) ) && ( ! empty( $set['e']['AnswerMessage'] ) ) ) {
					if ( is_string( $set['e']['AnswerMessage'] ) ) {
						$set['e']['AnswerMessage'] = esc_js( $set['e']['AnswerMessage'] );
					}
				}
			}
		}
	}

	return $quiz_resume_data;
}
