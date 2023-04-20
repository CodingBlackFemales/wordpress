<?php
/**
 * LearnDash REST API Questions Gutenberg Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type question (ld_question).
 *
 * This class extends the WP_REST_Posts_Controller class.
 *
 * @since 4.0.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Exams_Questions_Gutenberg_Controller' ) ) && ( class_exists( 'WP_REST_Posts_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API Questions Gutenberg Controller.
	 *
	 * @since 4.0.0
	 * @uses WP_REST_Posts_Controller
	 */
	class LD_REST_Exams_Questions_Gutenberg_Controller extends WP_REST_Posts_Controller {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
		// ToDo: Better error handling.

		/**
		 * Current Post Extra Rest Fields
		 *
		 * @var array $fields.
		 */
		protected $fields = array();

		/**
		 * Public constructor for class
		 *
		 * @since 4.0.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'exam_question' );
			}

			$this->post_type = $post_type;

			parent::__construct( $this->post_type );
			$this->register_fields();
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 4.0.0
		 */
		protected function register_fields() {
			$this->fields = array(
				'question_block_content' => array(
					'schema'       => array(
						'field_key' => 'question_block_content',
						'type'      => 'string',
						'required'  => false,
						'default'   => '',
						'context'   => array( 'view', 'edit' ),
					),
					'get_callback' => array( $this, 'get_rest_settings_field_value' ),
				),
			);

			foreach ( $this->fields as $field_key => $field_args ) {
				register_rest_field(
					$this->post_type,
					$field_key,
					$field_args
				);
			}
		}

		/**
		 * Get REST Setting Field value.
		 *
		 * @since 4.0.0
		 *
		 * @param array           $postdata   Post data array.
		 * @param string          $field_name Field Name for $postdata value.
		 * @param WP_REST_Request $request    Request object.
		 * @param string          $post_type  Post Type for request.
		 * @return null|array REST field value.
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			if ( ! $postdata['content']['raw'] || ! isset( $this->fields[ $field_name ] ) ) {
				return null;
			}

			switch ( $field_name ) {
				case 'question_block_content':
					$blocks = parse_blocks( $postdata['content']['raw'] );
					if ( ! empty( $blocks[0] ) ) {
						$blocks[0]['innerHTML']    = null;
						$blocks[0]['innerContent'] = null;
						return $blocks;
					}
					return null;
				default:
					return null;
			}
		}

		/**
		 * Retrieves a single post (ld_question).
		 *
		 * @since 4.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			try {
				$response = parent::get_item( $request );

				if ( 'edit' !== $request['context'] || empty( $request['id'] ) ) {
					return $response;
				}

				$response->data['content']['raw'] = $this->get_ld_question_block_content( $request['id'] );
				return $response;
			} catch ( Exception $e ) {
				return new WP_Error(
					'rest_get-ld_question',
					__( 'Error while trying to get the item', 'learndash' ),
					array( 'status' => 400 )
				);
			}
		}

		/**
		 * Block content from ld_question.
		 *
		 * @since 4.0.0
		 *
		 * @param int $post_id Post id.
		 * @return string
		 */
		private function get_ld_question_block_content( $post_id ) {
			$post           = $this->get_post( $post_id );
			$answer_meta    = get_post_meta( $post_id, 'answer', true );
			$answer         = array(
				'answer' => $answer_meta ?? array(),
			);
			$question_title = $post->post_title;

			return serialize_block(
				array(
					'blockName'    => 'learndash/ld-exam-question',
					'innerContent' => array( $post->post_content ),
					'attrs'        => array(
						'question_title' => $question_title,
						'answer'         => $answer,
					),
				)
			);
		}

		/**
		 * Updates a single post.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_item( $request ) {
			$block_ld_question = $this->get_block_ld_question( $request );
			if ( is_wp_error( $block_ld_question ) ) {
				return $block_ld_question;
			}

			$update_item_response = parent::update_item( $request );
			if ( is_wp_error( $update_item_response ) ) {
				return $update_item_response;
			}

			$update_question_item = $this->update_question_item( $request['id'], $block_ld_question, $request['status'] );
			if ( is_wp_error( $update_question_item ) ) {
				return $update_question_item;
			}

			return $update_item_response;
		}

		/**
		 * Updates a single ld_question post.
		 *
		 * @since 4.0.0
		 *
		 * @param int    $post_id Post id.
		 * @param array  $block   Block.
		 * @param string $status  Post status.
		 * @return int|WP_Error Response int on success, or WP_Error object on failure.
		 */
		private function update_question_item( $post_id, $block, $status = 'publish' ) {
			// ToDo: Check why is changing the $status to draft even when is not requested.
			try {
				$attrs        = $block['attrs'];
				$inner_blocks = serialize_blocks( $block['innerBlocks'] );
				$question     = array_merge( $attrs, array( 'inner_blocks_content' => $inner_blocks ) );

				if ( empty( $question['question_title'] ) ) {
					return new WP_Error(
						'rest_post-ld_question',
						__( 'Missing question_title', 'learndash' ),
						array( 'status' => 400 )
					);
				}

				$post_args = array(
					'ID'           => $post_id,
					'post_title'   => $question['question_title'],
					'post_type'    => 'ld_question',
					'post_content' => $question['inner_blocks_content'],
					'post_status'  => $status,
					'meta_input'   => $question['answer'],
				);

				return wp_insert_post( $post_args );
			} catch ( Exception $e ) {
				return new WP_Error(
					'rest_post-ld_question',
					__( 'Error', 'learndash' ),
					array( 'status' => 400 )
				);
			}
		}

		/**
		 * Get learndash/ld-question block from a ld_question post.
		 *
		 * @since 4.0.0
		 *
		 * @param WP_REST_Request $request WP_REST_Request object.
		 * @return array|WP_Error Response array on success, or WP_Error object on failure.
		 */
		private function get_block_ld_question( $request ) {
			if ( empty( $request['id'] ) || empty( $request['content'] ) ) {
				return new WP_Error(
					'rest_post-ld_question',
					__( 'Missing content', 'learndash' ),
					array( 'status' => 400 )
				);
			}
			$block = parse_blocks( trim( $request['content'] ) );
			if ( empty( $block[0] ) || 'learndash/ld-exam-question' !== $block[0]['blockName'] ) {
				return new WP_Error(
					'rest_post-ld_question',
					__( 'Missing learndash/ld-exam-question block', 'learndash' ),
					array( 'status' => 400 )
				);
			}

			return $block[0];
		}

		// End of functions.
	}
}
