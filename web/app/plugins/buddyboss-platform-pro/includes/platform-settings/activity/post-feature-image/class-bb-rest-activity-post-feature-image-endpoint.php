<?php
/**
 * BB REST: BB_REST_Activity_Post_Feature_Image_Endpoint class
 *
 * @package BuddyBossPro
 * @since 2.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity Post Feature Image endpoints.
 *
 * @since 2.9.0
 */
class BB_REST_Activity_Post_Feature_Image_Endpoint extends WP_REST_Controller {

	/**
	 * Allow batch.
	 *
	 * @since 2.9.0
	 *
	 * @var true[] $allow_batch
	 */
	protected $allow_batch = array( 'v1' => true );

	/**
	 * Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		$this->component = 'activity';
		$this->feature   = 'featured-image';
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = $this->component . '/' . $this->feature;

		// Add filter to hide feature images from Media Library.
		add_filter( 'bp_rest_platform_settings', array( $this, 'bb_rest_activity_post_feature_image_platform_settings' ), 10, 1 );

		add_filter( 'bp_rest_activity_create_item_query_arguments', array( $this, 'bb_rest_activity_query_arguments' ), 99, 3 );
		$this->bb_rest_activity_post_feature_image_support();
	}

	/**
	 * Filter REST platform settings to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param array $settings Platform settings.
	 *
	 * @return array Modified platform settings.
	 */
	public function bb_rest_activity_post_feature_image_platform_settings( $settings ) {
		$settings['bb_enable_activity_post_feature_image'] = bb_pro_activity_post_feature_image_instance()->bb_is_enabled() && ! bb_pro_should_lock_features();
		return $settings;
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.9.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upload',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_item' ),
					'permission_callback' => array( $this, 'upload_item_permissions_check' ),
					'args'                => array(
						'group_id' => array(
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'allow_batch' => $this->allow_batch,
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upload/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'id'          => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'activity_id' => array(
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'id'          => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'activity_id' => array(
							'type'              => 'integer',
							'required'          => false,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'allow_batch' => $this->allow_batch,
			)
		);
	}

	/**
	 * Upload a feature image.
	 *
	 * @since          2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/activity/featured-image/upload Upload Feature Image
	 * @apiName        UploadActivityPostFeatureImage
	 * @apiGroup       Activity
	 * @apiDescription Upload an activity post feature image.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} file The file to upload.
	 * @apiParam {String} group_id The group ID.
	 */
	public function upload_item( $request ) {
		$feature_instance = bb_pro_activity_post_feature_image_instance();
		$file_data        = $request->get_file_params();
		$file_data        = ! empty( $file_data['file'] ) ? $file_data['file'] : null;
		$file_data_error  = $feature_instance->get_upload_handler()->bb_validate_file_data( $file_data );
		if ( ! empty( $file_data_error ) && is_array( $file_data_error ) ) {
			return new WP_Error(
				$file_data_error['code'],
				$file_data_error['message'],
				array( 'status' => $file_data_error['status'] )
			);
		}

		// Check user access.
		$group_id = absint( $request->get_param( 'group_id' ) );
		if (
			! $feature_instance->bb_user_has_access_feature_image(
				array(
					'user_id'  => bp_loggedin_user_id(),
					'group_id' => $group_id,
					'object'   => ! empty( $group_id ) ? 'group' : '',
				)
			)
		) {
			return new WP_Error(
				'bb_rest_no_access',
				esc_html__( 'You do not have permission to upload feature image.', 'buddyboss-pro' ),
				array( 'status' => 403 )
			);
		}

		// Get upload handler.
		$upload_handler = $feature_instance->get_upload_handler();
		if ( ! $upload_handler ) {
			return new WP_Error(
				'bb_rest_upload_handler_missing',
				__( 'Upload handler not available.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		add_filter( 'upload_dir', array( $upload_handler, 'bb_get_upload_dir' ) );
		try {
			$result = $upload_handler->bb_handle_upload( $file_data );
		} finally {
			remove_filter( 'upload_dir', array( $upload_handler, 'bb_get_upload_dir' ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );

		/**
		 * Fires after a feature image is uploaded via REST API.
		 *
		 * @since 2.9.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bb_rest_activity_post_feature_image_uploaded', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to upload activity post feature image.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function upload_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bb_rest_authorization_required',
			esc_html__( 'Sorry, you are not allowed to upload feature image.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$feature_instance  = bb_pro_activity_post_feature_image_instance();
			$file_data         = $request->get_file_params();
			$file_data         = ! empty( $file_data['file'] ) ? $file_data['file'] : null;
			$group_id          = $request->get_param( 'group_id' );
			$can_upload        = $feature_instance->bb_user_has_access_feature_image(
				array(
					'user_id'  => bp_loggedin_user_id(),
					'group_id' => $group_id,
					'object'   => ! empty( $group_id ) ? 'group' : '',
				)
			);
			$validation_result = $feature_instance->get_upload_handler()->bb_validate_file_data( $file_data );
			if ( ! $feature_instance ) {
				$retval = new WP_Error(
					'bb_rest_activity_post_feature_image_instance_not_found',
					__( 'Feature instance not found.', 'buddyboss-pro' ),
					array( 'status' => 500 )
				);
			} elseif ( empty( $file_data ) ) {
				$retval = new WP_Error(
					'bb_rest_activity_post_feature_image_file_required',
					__( 'File is required.', 'buddyboss-pro' ),
					array( 'status' => 400 )
				);
			} elseif ( ! empty( $validation_result ) && is_array( $validation_result ) ) {
				$retval = new WP_Error(
					$validation_result['code'],
					$validation_result['message'],
					array( 'status' => $validation_result['status'] )
				);
			} elseif ( ! $can_upload ) {
				$retval = new WP_Error(
					'bb_rest_no_access',
					esc_html__( 'You do not have permission to upload a feature image.', 'buddyboss-pro' ),
					array( 'status' => 403 )
				);
			} elseif ( $can_upload ) {
				$retval = true;
			}
		}

		return $retval;
	}

	/**
	 * Delete uploaded feature image from activity uploaded.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {DELETE} /wp-json/buddyboss/v1/activity/featured-image/upload/:id Delete Uploaded Media Attachment.
	 * @apiName        DeleteActivityUploadedMediaAttachment
	 * @apiGroup       Activity
	 * @apiDescription Delete uploaded activity post feature image.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [id] A unique numeric ID for the media attachment.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the activity.
	 */
	public function delete_item( $request ) {
		$attachment_id = $request->get_param( 'id' );

		if ( empty( $attachment_id ) ) {
			return new WP_Error(
				'bb_rest_activity_post_feature_image_attachment_invalid_id',
				__( 'Missing or invalid attachment ID.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		}

		$activity_id      = ! empty( $request->get_param( 'activity_id' ) ) ? $request->get_param( 'activity_id' ) : 0;
		$feature_instance = function_exists( 'bb_pro_activity_post_feature_image_instance' ) ? bb_pro_activity_post_feature_image_instance() : null;
		if ( ! $feature_instance ) {
			return new WP_Error(
				'bb_rest_activity_post_feature_image_attachment_delete_failed',
				__( 'Feature instance not found.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}
		$result = $feature_instance->get_upload_handler()->bb_handle_delete_feature_image( $attachment_id, $activity_id );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'bb_rest_activity_post_feature_image_attachment_delete_failed',
				$result->get_error_message(),
				array( 'status' => $result->get_error_code() )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => array(
					'id' => $attachment_id,
				),
			)
		);

		/**
		 * Fires after a feature image is deleted via the REST API.
		 *
		 * @since 2.9.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bb_rest_activity_post_feature_image_deleted', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete uploaded activity post feature image.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bb_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this feature image.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$feature_instance = function_exists( 'bb_pro_activity_post_feature_image_instance' ) ? bb_pro_activity_post_feature_image_instance() : null;

			if ( ! $feature_instance ) {
				$retval = new WP_Error(
					'bb_rest_activity_post_feature_image_attachment_delete_failed',
					__( 'Feature instance not found.', 'buddyboss-pro' ),
					array( 'status' => 500 )
				);
			} else {
				$attachment_id = $request->get_param( 'id' );
				$activity_id   = ! empty( $request->get_param( 'activity_id' ) ) ? $request->get_param( 'activity_id' ) : 0;

				$retval = $feature_instance->bb_user_can_perform_feature_image_action(
					array(
						'action'        => 'delete',
						'attachment_id' => $attachment_id,
						'activity_id'   => $activity_id,
					)
				);

				if ( is_array( $retval ) ) {
					if ( isset( $retval['can_delete'] ) ) {
						$retval = $retval['can_delete'];
					} else {
						$retval = new WP_Error(
							$retval['code'],
							$retval['message'],
							array( 'status' => $retval['status'] )
						);
					}
				}
			}
		}

		/**
		 * Filter the feature image `delete_attachment_item` permissions check.
		 *
		 * @since 2.9.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_activity_post_feature_image_delete_attachment_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a feature image.
	 *
	 * @since          2.9.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/activity/featured-image/upload/:id Update Feature Image
	 * @apiName        UpdateActivityPostFeatureImage
	 * @apiGroup       Activity
	 * @apiDescription Update an activity post feature image.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} file The file to update.
	 * @apiParam {String} id The attachment ID.
	 * @apiParam {String} activity_id The activity ID.
	 * @apiParam {String} group_id The group ID.
	 */
	public function update_item( $request ) {
		$feature_instance = bb_pro_activity_post_feature_image_instance();
		$attachment_id    = $request->get_param( 'id' );
		$file_data        = $request->get_file_params();
		$file_data        = ! empty( $file_data['file'] ) ? $file_data['file'] : null;
		$group_id         = $request->get_param( 'group_id' );
		if ( empty( $attachment_id ) || empty( $file_data ) ) {
			return new WP_Error(
				'bb_rest_activity_post_feature_image_attachment_invalid_id',
				__( 'Missing or invalid attachment ID.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get upload handler.
		$upload_handler = $feature_instance->get_upload_handler();
		if ( ! $upload_handler ) {
			return new WP_Error(
				'bb_rest_upload_handler_missing',
				__( 'Upload handler not available.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		// Use the new REST-specific file replacement method.
		$result = $upload_handler->bb_handle_file_replace(
			array(
				'attachment_id' => $attachment_id,
				'file_data'     => $file_data,
				'group_id'      => $group_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );

		/**
		 * Fires after a feature image is updated via the REST API.
		 *
		 * @since 2.9.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bb_rest_activity_post_feature_image_updated', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to update a feature image.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bb_rest_authorization_required',
			esc_html__( 'Sorry, you are not allowed to upload feature image.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval           = true;
			$feature_instance = function_exists( 'bb_pro_activity_post_feature_image_instance' ) ? bb_pro_activity_post_feature_image_instance() : null;
			$file_data        = $request->get_file_params();
			$file_data        = ! empty( $file_data['file'] ) ? $file_data['file'] : null;

			if ( ! $feature_instance ) {
				$retval = new WP_Error(
					'bb_rest_activity_post_feature_image_attachment_delete_failed',
					__( 'Feature instance not found.', 'buddyboss-pro' ),
					array( 'status' => 500 )
				);
			} elseif ( empty( $file_data ) ) {
				$retval = new WP_Error(
					'bb_rest_activity_post_feature_image_file_required',
					__( 'File is required.', 'buddyboss-pro' ),
					array( 'status' => 400 )
				);
			} else {
				$attachment_id = $request->get_param( 'id' );
				$activity_id   = ! empty( $request->get_param( 'activity_id' ) ) ? $request->get_param( 'activity_id' ) : 0;
				$group_id      = $request->get_param( 'group_id' );

				$retval = $feature_instance->bb_user_can_perform_feature_image_action(
					array(
						'action'        => 'edit',
						'attachment_id' => $attachment_id,
						'activity_id'   => $activity_id,
						'group_id'      => $group_id,
					)
				);

				if ( is_array( $retval ) ) {
					if ( isset( $retval['can_edit'] ) ) {
						$retval = $retval['can_edit'];
					} else {
						$retval = new WP_Error(
							$retval['code'],
							$retval['message'],
							array( 'status' => $retval['status'] )
						);
					}
				}
			}
		}

		return $retval;
	}

	/**
	 * Get the item schema for REST API.
	 *
	 * @since 2.9.0
	 *
	 * @return array Item schema.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => __( 'Activity Post Feature Image', 'buddyboss-pro' ),
			'type'       => 'object',
			'properties' => array(
				'id'                       => array(
					'description' => __( 'Unique identifier for the feature image.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'format'      => 'int64',
					'context'     => array( 'view', 'edit' ),
				),
				'thumb'                    => array(
					'description' => __( 'Thumbnail URL for the feature image.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'medium'                   => array(
					'description' => __( 'Medium URL for the feature image.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'url'                      => array(
					'description' => __( 'URL for the feature image.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'name'                     => array(
					'description' => __( 'Name for the feature image.', 'buddyboss-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'can_edit_feature_image'   => array(
					'description' => __( 'Can edit feature image.', 'buddyboss-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
				),
				'can_delete_feature_image' => array(
					'description' => __( 'Can delete feature image.', 'buddyboss-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
				),
			),
		);

		/**
		 * Filter the item schema for the REST API.
		 *
		 * @since 2.9.0
		 *
		 * @param array  $schema    Item schema data.
		 * @param string $component The component name.
		 * @param string $feature   The feature name.
		 */
		return apply_filters( 'bb_rest_activity_post_feature_image_item_schema', $schema, $this->component, $this->feature );
	}

	/**
	 * Filter REST activity post feature image support.
	 *
	 * @since 2.9.0
	 *
	 * @param array  $args   Query arguments.
	 * @param string $method HTTP method.
	 *
	 * @return array Modified query arguments.
	 */
	public function bb_rest_activity_query_arguments( $args, $method ) {
		$args['bb_activity_post_feature_image_id'] = array(
			'description'       => __( 'Activity post feature image id.', 'buddyboss-pro' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $args;
	}

	/**
	 * Filter REST activity post feature image support.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public function bb_rest_activity_post_feature_image_support() {
		// Register the feature image data field (read-only).
		bp_rest_register_field(
			'activity',
			'bb_activity_post_feature_image',
			array(
				'get_callback' => array( $this, 'bb_activity_post_feature_image_get_rest_field_callback' ),
				'schema'       => array(
					'description' => 'Activity Post Feature Image Data.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Register the feature image ID field (for updates).
		bp_rest_register_field(
			'activity',
			'bb_activity_post_feature_image_id',
			array(
				'update_callback' => array( $this, 'bb_activity_post_feature_image_id_update_rest_field_callback' ),
				'schema'          => array(
					'description' => 'Activity Post Feature Image ID.',
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * The function to use to get the feature image data of the activity REST Field.
	 *
	 * @since 2.9.0
	 *
	 * @param array $activity The activity data.
	 *
	 * @return array|false Feature image data or false if not found.
	 */
	public function bb_activity_post_feature_image_get_rest_field_callback( $activity ) {
		if ( empty( $activity['id'] ) ) {
			return false;
		}

		if ( ! function_exists( 'bb_pro_activity_post_feature_image_instance' ) ) {
			return false;
		}

		$feature_instance = bb_pro_activity_post_feature_image_instance();
		if ( ! method_exists( $feature_instance, 'bb_get_feature_image_data' ) ) {
			return false;
		}

		$activity_feature_image = $feature_instance->bb_get_feature_image_data( $activity['id'] );
		if ( ! empty( $activity_feature_image ) ) {
			$can_edit   = $feature_instance->bb_user_can_perform_feature_image_action(
				array(
					'action'        => 'edit',
					'attachment_id' => $activity_feature_image['id'],
					'activity_id'   => $activity['id'],
				)
			);
			$can_delete = $feature_instance->bb_user_can_perform_feature_image_action(
				array(
					'action'        => 'delete',
					'attachment_id' => $activity_feature_image['id'],
					'activity_id'   => $activity['id'],
				)
			);
			$activity_feature_image['can_edit_feature_image']   = isset( $can_edit['can_edit'] ) ? $can_edit['can_edit'] : false;
			$activity_feature_image['can_delete_feature_image'] = isset( $can_delete['can_delete'] ) ? $can_delete['can_delete'] : false;
		}

		return $activity_feature_image;
	}

	/**
	 * The function to use to update the feature image id's value of the activity REST Field.
	 *
	 * @since 2.9.0
	 *
	 * @param BP_Activity_Activity $object     The BuddyPress component's object that was just created/updated during the request.
	 * @param mixed                $value      The value of the REST Field to save.
	 * @param string               $attribute  The REST Field key used into the REST response.
	 *
	 * @return bool|void
	 */
	protected function bb_activity_post_feature_image_id_update_rest_field_callback( $object, $value, $attribute ) {
		// $object is the feature image ID (integer)
		// $value is the activity object (stdClass)
		// $attribute is the field name

		if ( empty( $object ) || empty( $value ) ) {
			return false;
		}

		// Get the feature image ID from $object.
		$feature_image_id = (int) $object;

		// Get the activity ID from $value.
		$activity_id = $value->id;
		if ( empty( $activity_id ) ) {
			return false;
		}

		// Get the feature image.
		$get_feature_image = get_post( $feature_image_id );
		if ( empty( $get_feature_image ) ) {
			return false;
		}

		// Validate the attachment.
		if ( ! function_exists( 'bb_pro_activity_post_feature_image_instance' ) ) {
			return false;
		}

		$feature_instance = bb_pro_activity_post_feature_image_instance();
		if ( ! method_exists( $feature_instance, 'bb_store_activity_feature_image' ) ) {
			return false;
		}

		if ( is_numeric( $feature_image_id ) && $feature_image_id > 0 ) {
			$feature_image_id = (int) $feature_image_id;

			if ( bp_is_active( 'groups' ) && ! empty( $value->item_id ) && 'groups' === $value->component && 'activity_update' === $value->type ) {
				$group = groups_get_group( $value->item_id );
				if ( empty( $group ) ) {
					return new WP_Error(
						'bb_rest_activity_post_feature_image_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss-pro' ),
						array( 'status' => 404 )
					);
				}

				$existing_feature_image_id = bp_activity_get_meta( $activity_id, '_bb_activity_post_feature_image', true );
				if ( ! $feature_instance->bb_is_feature_enabled() ) {
					return new WP_Error( 'bb_rest_access_denied', __( 'You cannot update a feature image.', 'buddyboss-pro' ), array( 'status' => 403 ) );
				} elseif (
					empty( $existing_feature_image_id ) ||
					(int) $existing_feature_image_id !== (int) $feature_image_id
				) {
					if (
						! groups_is_user_admin( bp_loggedin_user_id(), $value->item_id ) &&
						! groups_is_user_mod( bp_loggedin_user_id(), $value->item_id )
					) {
						return new WP_Error( 'bb_rest_group_feature_image_access_denied', __( 'You cannot update a feature image for this group.', 'buddyboss-pro' ), array( 'status' => 403 ) );
					}
				}
			} elseif ( ! bp_current_user_can( 'administrator' ) ) {
				return new WP_Error( 'bb_rest_access_denied', __( 'You cannot update a feature image.', 'buddyboss-pro' ), array( 'status' => 403 ) );
			}
		}

		// Store the feature image.
		$_POST['bb_activity_post_feature_image_id'] = $feature_image_id;
		$feature_instance->bb_store_activity_feature_image( $value );

		return true;
	}
}
