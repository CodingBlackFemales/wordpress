<?php
/**
 * Handles all server side logic for the ld-exam Gutenberg Block.
 *
 * @package LearnDash
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Exam' ) ) ) {
	/**
	 * Class for handling LearnDash Exam Block
	 */
	class LearnDash_Gutenberg_Block_Exam extends LearnDash_Gutenberg_Block {

		/**
		 * Array of sub_blocks used within the Exam Question block.
		 *
		 * @var array.
		 */
		private $sub_blocks = array();

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->block_slug   = 'ld-exam';
			$this->self_closing = false;

			$this->block_attributes = array(
				'ld_version' => array(
					'type' => 'string',
				),
			);

			$this->sub_blocks = array(
				$this->block_base . '/ld-exam-question',
				$this->block_base . '/ld-question-description',
				$this->block_base . '/ld-question-answers-block',
				$this->block_base . '/ld-correct-answer-message-block',
				$this->block_base . '/ld-incorrect-answer-message-block',
			);

			$this->init();

			add_filter( 'pre_render_block', array( $this, 'pre_render_block' ), 30, 3 );
		}

		/**
		 * Pre-Render filter for Block.
		 *
		 * We hook into the pre_render_block filter to prevent the default exam blocks
		 * from being rendered by WP.
		 *
		 * @since 4.0.0
		 *
		 * @param string|null   $content      The pre-rendered content. Default null.
		 * @param array         $parsed_block The block being rendered.
		 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
		 */
		public function pre_render_block( $content, $parsed_block = array(), $parent_block = null ) {
			if ( ( isset( $parsed_block['blockName'] ) ) && ( $this->block_base . '/' . $this->block_slug === $parsed_block['blockName'] ) ) {
				$current_post_id   = (int) get_the_ID();
				$current_post_type = get_post_type( $current_post_id );

				if ( learndash_get_post_type_slug( 'exam' ) === $current_post_type ) {
					$atts = array(
						'exam_id'   => $current_post_id,
						'course_id' => 0,
						'user_id'   => get_current_user_id(),
					);

					$course_id_show = (int) learndash_get_setting( $current_post_id, 'exam_challenge_course_show' );
					if ( ! empty( $course_id_show ) ) {
						$atts['course_id'] = $course_id_show;
					}

					$ld_exam_object = LDLMS_Factory_Post::exam( $atts['exam_id'], $atts );
					if ( ( $ld_exam_object ) && ( is_a( $ld_exam_object, 'LDLMS_Model_Exam' ) ) ) {
						$ld_exam_object->process_exam_submit();
						$content = $ld_exam_object->get_front_content();
					}
				} else {
					$content = '';
				}
			}

			return $content;
		}

		/**
		 * Register Block for Gutenberg
		 *
		 * @since 4.0.0
		 */
		public function register_blocks() {
			// Call our parent to register the block.
			parent::register_blocks();

			/**
			 * Register some internal blocks.
			 */
			register_block_type(
				$this->block_base . '/ld-exam-question',
				array(
					'render_callback' => array( $this, 'render_block_exam_question' ),
					'attributes'      => $this->block_attributes,
				)
			);

			register_block_type(
				$this->block_base . '/ld-question-description',
				array(
					'render_callback' => array( $this, 'render_block_exam_question_description' ),
					'attributes'      => $this->block_attributes,
				)
			);

			register_block_type(
				$this->block_base . '/ld-question-answers-block',
				array(
					'render_callback' => array( $this, 'render_block_exam_question_answers' ),
					'attributes'      => $this->block_attributes,
				)
			);

			register_block_type(
				$this->block_base . '/ld-correct-answer-message-block',
				array(
					'render_callback' => array( $this, 'render_block_exam_question_correct_message' ),
					'attributes'      => $this->block_attributes,
				)
			);

			register_block_type(
				$this->block_base . '/ld-incorrect-answer-message-block',
				array(
					'render_callback' => array( $this, 'render_block_exam_question_incorrect_message' ),
					'attributes'      => $this->block_attributes,
				)
			);
		}

		/**
		 * Render Exam Question Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			return '';
		}

		/**
		 * Render Exam Question Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block_exam_question( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes['learndash_question_content'] = $block_content;

			$block_content = SFWD_LMS::get_template(
				'exam/partials/exam_question_row.php',
				$block_attributes
			);

			return $block_content;
		}

		/**
		 * Render Exam Question Description Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block_exam_question_description( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes['learndash_question_description'] = $block_content;

			$block_content = SFWD_LMS::get_template(
				'exam/partials/exam_question_description.php',
				$block_attributes
			);

			return $block_content;
		}

		/**
		 * Render Exam Question Answers Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block_exam_question_answers( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			if ( ! isset( $block_attributes['learndash_question_answers'] ) ) {
				if ( isset( $block_attributes['answers'] ) ) {
					$block_attributes['learndash_question_answers'] = $block_attributes['answers'];
					unset( $block_attributes['answers'] );
				}
			}

			$block_content = SFWD_LMS::get_template(
				'exam/partials/exam_question_answers.php',
				$block_attributes
			);

			return $block_content;
		}

		/**
		 * Render Exam Question Correct Message Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block_exam_question_correct_message( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes['learndash_question_correct_message'] = $block_content;

			$block_content = SFWD_LMS::get_template(
				'exam/partials/exam_question_correct_message.php',
				$block_attributes
			);

			return $block_content;
		}

		/**
		 * Render Exam Question Incorrect Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block_exam_question_incorrect_message( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes['learndash_question_incorrect_message'] = $block_content;

			$block_content = SFWD_LMS::get_template(
				'exam/partials/exam_question_incorrect_message.php',
				$block_attributes
			);

			return $block_content;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Exam();
