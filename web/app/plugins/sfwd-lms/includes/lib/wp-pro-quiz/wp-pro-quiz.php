<?php
/*
Plugin Name: WP-Pro-Quiz
Plugin URI: http://wordpress.org/extend/plugins/wp-pro-quiz
Description: A powerful and beautiful quiz plugin for WordPress.
Version: 0.28
Author: Julius Fischer
Author URI: http://www.it-gecko.de
Text Domain: wp-pro-quiz
Domain Path: /languages
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @ignore
 */
define( 'WPPROQUIZ_VERSION', '0.28' );

/**
 * @ignore
 */
define( 'WPPROQUIZ_PATH', dirname( __FILE__ ) );

/**
 * @ignore
 */
define( 'WPPROQUIZ_URL', plugins_url( '', __FILE__ ) );

/**
 * @ignore
 */
define( 'WPPROQUIZ_FILE', __FILE__ );

$wpproquiz_upload_dir = wp_upload_dir();

/**
 * @ignore
 */
define( 'WPPROQUIZ_CAPTCHA_DIR', $wpproquiz_upload_dir['basedir'] . '/wp_pro_quiz_captcha' );

/**
 * @ignore
 */
define( 'WPPROQUIZ_CAPTCHA_URL', $wpproquiz_upload_dir['baseurl'] . '/wp_pro_quiz_captcha' );

spl_autoload_register( 'wpProQuiz_autoload' );

add_action( 'plugins_loaded', 'wpProQuiz_pluginLoaded' );

if ( is_admin() ) {
	new WpProQuiz_Controller_Admin();
} else {
	new WpProQuiz_Controller_Front();
}

/**
 * Handles the wp pro quiz class autoloading.
 *
 * Callback for `spl_autoload_register` function.
 *
 * @param string $class Class name.
 */
function wpProQuiz_autoload( $class ) {
	$c = explode( '_', $class );

	if ( false === $c || count( $c ) != 3 || 'WpProQuiz' !== $c[0] ) {
		return;
	}

	$dir = '';

	switch ( $c[1] ) {
		case 'View':
			$dir = 'view';
			break;
		case 'Model':
			$dir = 'model';
			break;
		case 'Helper':
			$dir = 'helper';
			break;
		case 'Controller':
			$dir = 'controller';
			break;
		case 'Plugin':
			$dir = 'plugin';
			break;
		default:
			return;
	}

	if ( file_exists( WPPROQUIZ_PATH . '/lib/' . $dir . '/' . $class . '.php' ) ) {
		include_once WPPROQUIZ_PATH . '/lib/' . $dir . '/' . $class . '.php';
	}
}

/**
 * Runs the wp pro quiz upgrade after the plugins are loaded.
 *
 * Fires on `plugins_loaded` hook.
 */
function wpProQuiz_pluginLoaded() {

	if ( get_option( 'wpProQuiz_version' ) !== WPPROQUIZ_VERSION ) {
		WpProQuiz_Helper_Upgrade::upgrade();
	}
}

/**
 * Formats the quiz cloze type answers into an array to be used when comparing responses.
 *
 * The function is copied from `WpProQuiz_View_FrontQuiz` class.
 *
 * @since 2.5.0
 *
 * @param string  $answer_text      Answer text.
 * @param boolean $convert_to_lower Optional. Whether to convert anwser to lowercase. Default true.
 *
 * @return array An array of cloze question data.
 */
function learndash_question_cloze_fetch_data( $answer_text, $convert_to_lower = true ) {

	/**
	 * Filters the value of quiz question answer before processing.
	 *
	 * @param string $answer  The quiz question anser text.
	 * @param string $context The context of type of question.
	 */
	$answer_text = apply_filters( 'learndash_quiz_question_answer_preprocess', $answer_text, 'cloze' );

	preg_match_all( '#\{(.*?)\}#im', $answer_text, $matches, PREG_SET_ORDER );

	$data = array();

	foreach ( $matches as $k => $v ) {
		$text          = $v[1];
		$points        = array();
		$rowText       = array();
		$multiTextData = array();
		$len           = array();

		if ( preg_match_all( '#\[(.*?)\]#im', $text, $multiTextMatches ) ) {
			foreach ( $multiTextMatches[1] as $multiText ) {
				$item_points = 1;
				if ( strpos( $multiText, '|' ) !== false ) {
					list( $multiText, $item_points ) = explode( '|', $multiText );
				}
				$multiText_clean = trim( html_entity_decode( $multiText, ENT_QUOTES ) );

				$item_points = absint( $item_points );
				if ( ! $item_points ) {
					$item_points = 1;
				}

				/**
				 * Filters whether to convert quiz question cloze to lowercase or not.
				 *
				 * @param boolean $conver_to_lower Whether to convert quiz question cloze to lower case.
				 */
				if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', $convert_to_lower ) ) {
					if ( function_exists( 'mb_strtolower' ) ) {
						$x = mb_strtolower( $multiText_clean );
					} else {
						$x = strtolower( $multiText_clean );
					}
				} else {
					$x = $multiText_clean;
				}

				$len[]           = strlen( $x );
				$multiTextData[] = $x;
				$rowText[]       = $multiText;
				$points[]        = $item_points;
			}
		} elseif ( strpos( $text, '|' ) !== false ) {
			list( $multiText, $item_points ) = explode( '|', $text );

			$multiText_clean = trim( html_entity_decode( $multiText, ENT_QUOTES ) );
			$item_points     = absint( $item_points );

			/**
			 * Filters whether to convert quiz question cloze to lowercase or not.
			 *
			 * @param boolean $conver_to_lower Whether to convert quiz question cloze to lower case.
			 */
			if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', $convert_to_lower ) ) {
				if ( function_exists( 'mb_strtolower' ) ) {
					$x = mb_strtolower( $multiText_clean );
				} else {
					$x = strtolower( $multiText_clean );
				}
			} else {
				$x = $multiText_clean;
			}

			$len[]           = strlen( $x );
			$multiTextData[] = $x;
			$rowText[]       = $multiText;
			$points[]        = $item_points;

		} else {
			$item_points = ! empty( $v[2] ) ? (int) $v[2] : 1;
			$text_clean  = trim( html_entity_decode( $text, ENT_QUOTES ) );
			/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
			if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', $convert_to_lower ) ) {
				if ( function_exists( 'mb_strtolower' ) ) {
					$x = mb_strtolower( trim( html_entity_decode( $text_clean, ENT_QUOTES ) ) );
				} else {
					$x = strtolower( trim( html_entity_decode( $text_clean, ENT_QUOTES ) ) );
				}
			} else {
				$x = $text_clean;
			}

			$len[]           = strlen( $x );
			$multiTextData[] = $x;
			$rowText[]       = $text;
			$points[]        = $item_points;
		}

		if ( ! isset( $data['replace'] ) ) {
			$data['replace'] = $answer_text;
		}
		$input_size = absint( max( $len ) );
		if ( $input_size < 1 ) {
			$input_size = 1;
		}

		$input_max = $input_size + 5;

		$a  = '<span class="wpProQuiz_cloze"><input autocomplete="off" data-wordlen="' . absint( $input_size ) . '" type="text" value="" size="' . absint( $input_size ) . '" maxlength="' . absint( $input_max ) . '"> ';

		$a .= '<span class="wpProQuiz_clozeCorrect" style="display: none;"></span></span>';

		$replace_key = '@@wpProQuizCloze-' . $k . '@@';

		$data['correct'][]            = $multiTextData;
		$data['points'][]             = $points;
		$data['data'][ $replace_key ] = $a;

		$pos = strpos( $data['replace'], $v[0] );
		if ( false !== $pos ) {
    		$data['replace'] = substr_replace( $data['replace'], $replace_key, $pos, strlen($v[0] ) );
		}
	}

	/**
	 * Filters the value of quiz question answer after processing.
	 *
	 * @param string $answer  The quiz question anser text.
	 * @param string $context The context of type of question.
	 */
	$data['replace'] = apply_filters( 'learndash_quiz_question_answer_postprocess', $data['replace'], 'cloze' );

	return $data;
}


function learndash_question_cloze_prepare_output( $cloze_data = array() ) {
	$cloze_output = '';

	if ( ! isset( $cloze_data['data'] ) ) {
		$cloze_data['data'] = [];
	}

	if ( ! isset( $cloze_data['replace'] ) ) {
		$cloze_data['replace'] = [];
	}

	if ( ( ! empty( $cloze_data['replace'] ) ) && ( ! empty( $cloze_data['data'] ) ) ) {
		$cloze_data['replace'] = wpautop( $cloze_data['replace'] );
		$cloze_data['replace'] = sanitize_post_field( 'post_content', $cloze_data['replace'], 0, 'display' );
		$cloze_data['replace'] = do_shortcode( $cloze_data['replace'] );

		$cloze_output = str_replace( array_keys( $cloze_data['data'] ), array_values( $cloze_data['data'] ), $cloze_data['replace'] );
	}

	return $cloze_output;
}

function learndash_question_assessment_fetch_data( $answerText, $quizId = 0, $questionId = 0 ) {

	/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
	$answerText = apply_filters( 'learndash_quiz_question_answer_preprocess', $answerText, 'assessment' );

	$data = array(
		'correct'     => [],
		'points'      => [],
		'data'        => [],
		'replace'     => '',
		'answer_text' => '',
	);

	if ( ! empty( $answerText ) ) {
		$data['answer_text'] = $answerText;

		preg_match_all( '#\{(.*?)\}#im', $answerText, $matches );
		if ( ( isset( $matches[1] ) ) && ( ! empty( $matches[1] ) ) ) {
			foreach ( $matches[1] as $match_idx => $match ) {
				$a = '';

				preg_match_all( '#\[([^\|\]]+)(?:\|(\d+))?\]#im', $match, $m_values );

				if ( ( isset( $m_values[1] ) ) && ( ! empty( $m_values[1] ) ) ) {
					foreach ( $m_values[1] as $label_idx => $label ) {
						$data['correct'][ $label_idx ] = $label;

						if ( ( isset( $m_values[2][ $label_idx ] ) ) && ( '' !== $m_values[2][ $label_idx ] ) ) {
							$data['points'][ $label_idx ] = absint( $m_values[2][ $label_idx ] );
						} else {
							$data['points'][ $label_idx ] = $label_idx + 1;
						}

						$field_value = $label_idx + 1;

						/**
						 * Note: The 'data-index' value is purposely set to '0' for all answers.
						 * This si because in the wpProQuiz_front.js (3.5.0) in the function
						 * fetchAllAnswerData() the value is assigned to the data-index array
						 * position. And it needs to be in the '0' position.
						 */
						$a .= '<label><input type="radio" value="' . $field_value . '" name="question_' . $quizId . '_' . $questionId . '_' . "0" . '" class="wpProQuiz_questionInput" data-index="0">' . $data['correct'][ $label_idx ] . '</label>';
					}
				}

				$replace_key                  = '@@wpProQuizAssessment-' . $match_idx . '@@';
				$count                        = 1;
				$data['data'][ $replace_key ] = $a;
				$data['replace']              = preg_replace( '#\{(.*?)\}#im', $replace_key, $answerText, $count );
			}
		}
	}
	return $data;
}

function learndash_question_assessment_prepare_output( $assessment_data = array() ) {
	$assessment_output = '';

	if ( ! isset( $assessment_data['data'] ) ) {
		$assessment_data['data'] = [];
	}

	if ( ! isset( $assessment_data['replace'] ) ) {
		$assessment_data['replace'] = [];
	}

	if ( ( ! empty( $assessment_data['replace'] ) ) && ( ! empty( $assessment_data['data'] ) ) ) {
		$assessment = sanitize_post_field( 'post_content', $assessment_data['replace'], 0, 'display' );
		$assessment = wpautop( $assessment );
		$assessment = do_shortcode( $assessment );

		$assessment = str_replace( array_keys( $assessment_data['data'] ), array_values( $assessment_data['data'] ), $assessment );

		/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
		$assessment = apply_filters( 'learndash_quiz_question_answer_postprocess', $assessment, 'assessment' );
		$assessment = do_shortcode( $assessment );

		$assessment_output = $assessment;
	}

	return $assessment_output;
}


/**
 * Casts an instance of PHP stdClass to the type of given class name.
 *
 * This function will take an instance of a PHP stdClass and attempt to cast it to
 * the type of the specified $className parameter.
 * For example, we may pass 'Acme\Model\Product' as the $className.
 *
 * @param object $instance An instance of the stdClass to cast.
 * @param string $className The name of the class type to which we want to convert.
 *
 * @return mixed The instance after casting.
 */
function learndash_cast_WpProQuiz_Model_AnswerTypes( $instance, $className ) {
	return unserialize( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		sprintf(
			'O:%d:"%s"%s',
			\strlen( $className ),
			$className,
			strstr( strstr( serialize( $instance ), '"' ), ':' ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		)
	);
}

function learndash_question_free_get_answer_data( $data, $question = null ) {
	$question_data = array();

	$t = str_replace( "\r\n", "\n", $data->getAnswer() );
	$t = str_replace( "\r", "\n", $t );
	$t = explode( "\n", $t );

	foreach ( $t as $idx => $item ) {
		if ( strpos( $item, '|' ) !== false ) {
			list( $item_value, $item_points ) = explode( '|', $item );
		} else {
			$item_value  = trim( html_entity_decode( $item, ENT_QUOTES ) );
			$item_points = 1;
		}

		if ( '' == $item ) {
			unset( $t[ $idx ] );
			continue;
		}

		//$question_data['correct'][] = esc_attr( $item_value );
		$question_data['correct'][] = $item_value;
		$question_data['points'][]  = (int) $item_points;
	}

	return $question_data;
}
