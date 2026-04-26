<?php
/**
 * LearnDash REST API V2 Exams Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type exams (ld-exam).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 4.0.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Exams_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Exams Post Controller.
	 *
	 * @since 4.0.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Exams_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 4.0.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'exam' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'exams' );
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 4.0.0
		 */
		protected function register_fields() {
			$this->register_fields_metabox();

			do_action( 'learndash_rest_register_fields', $this->post_type, $this );
		}

		/**
		 * Gets public schema.
		 *
		 * @since 4.0.0
		 *
		 * @return array
		 */
		public function get_public_item_schema() {

			$schema = parent::get_public_item_schema();

			$schema['title'] = 'exam';

			return $schema;
		}

		/**
		 * Checks if a given request has access to read posts.
		 * We override this to implement our own permissions check.
		 *
		 * @since 4.10.3
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			}

			return new WP_Error(
				'ld_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		/**
		 * Checks if a given request has access to read a post.
		 * We override this to implement our own permissions check.
		 *
		 * @since 4.10.3
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
		 */
		public function get_item_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			}

			return new WP_Error(
				'ld_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
	}
}
