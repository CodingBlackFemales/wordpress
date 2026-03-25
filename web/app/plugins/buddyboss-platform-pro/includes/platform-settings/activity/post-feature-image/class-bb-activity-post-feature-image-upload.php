<?php
/**
 * BuddyBoss Activity Post Feature Image Upload Handler.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Platform Settings/Activity/Post Feature Image
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Post Feature Image Upload Handler Class.
 *
 * Handles file upload processing for feature images.
 *
 * @since 2.9.0
 */
class BB_Activity_Post_Feature_Image_Upload {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.9.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Reference to the main feature image instance.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_Activity_Post_Feature_Image
	 */
	private $feature_image_instance;

	/**
	 * Get the instance of this class.
	 *
	 * @since 2.9.0
	 *
	 * @param BB_Activity_Post_Feature_Image $feature_image_instance Main feature image instance.
	 *
	 * @return self Instance.
	 */
	public static function instance( $feature_image_instance ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $feature_image_instance );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.9.0
	 *
	 * @param BB_Activity_Post_Feature_Image $feature_image_instance Main feature image instance.
	 */
	private function __construct( $feature_image_instance ) {
		$this->feature_image_instance = $feature_image_instance;
		$this->setup_actions();
	}

	/**
	 * Setup actions for upload functionality.
	 *
	 * @since 2.9.0
	 */
	private function setup_actions() {
		// AJAX upload handler.
		add_action( 'wp_ajax_activity_post_feature_image_upload', array( $this, 'bb_handle_ajax_upload' ) );
		add_action( 'wp_ajax_activity_post_feature_image_delete', array( $this, 'bb_handle_ajax_delete' ) );
		add_action( 'wp_ajax_activity_post_feature_image_crop_replace', array( $this, 'bb_handle_ajax_crop_replace' ) );
	}

	/**
	 * Handle AJAX upload requests.
	 *
	 * @since 2.9.0
	 */
	public function bb_handle_ajax_upload() {
		$response = array(
			'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss-pro' ),
		);

		if ( bb_pro_should_lock_features() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_loggedin_user_id() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_is_post_request() ) {
			wp_send_json_error( $response, 500 );
		}

		$group_id = ! empty( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		if (
			! $this->feature_image_instance->bb_user_has_access_feature_image(
				array(
					'group_id' => $group_id,
					'object'   => ! empty( $group_id ) ? 'group' : '',
				)
			)
		) {
			wp_send_json_error( $response, 500 );
		}

		$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'activity_post_feature_image_upload' ) ) {
			wp_send_json_error( $response, 403 );
		}

		$file_data       = isset( $_FILES['file'] ) ? $_FILES['file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file_data_error = $this->bb_validate_file_data( $file_data );
		if ( ! empty( $file_data_error ) && is_array( $file_data_error ) ) {
			wp_send_json_error( $file_data_error, $file_data_error['status'] );
		}

		$result = $this->bb_handle_upload( $file_data );

		if ( is_wp_error( $result ) ) {
			$response['feedback'] = $result->get_error_message();
			wp_send_json_error( $response, $result->get_error_code() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle AJAX delete requests.
	 *
	 * @since 2.9.0
	 */
	public function bb_handle_ajax_delete() {
		$response = array(
			'feedback' => sprintf(
				'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss-pro' )
			),
		);

		if ( bb_pro_should_lock_features() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_loggedin_user_id() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_is_post_request() ) {
			wp_send_json_error( $response, 500 );
		}

		// Use default nonce.
		$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
		$check = 'activity_post_feature_image_delete';

		// Nonce check!
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
			wp_send_json_error( $response );
		}

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		if ( empty( $id ) ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'Please provide attachment id to delete.', 'buddyboss-pro' )
			);
			wp_send_json_error( $response );
		}
		$activity_id = ! empty( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;

		$result = $this->bb_handle_delete_feature_image( $id, $activity_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), $result->get_error_code() );
		}

		wp_send_json_success();
	}

	/**
	 * Handle delete feature image.
	 *
	 * @since 2.9.0
	 *
	 * @param int $id The attachment ID.
	 * @param int $activity_id The activity ID.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_handle_delete_feature_image( $id, $activity_id ) {
		$can_delete = $this->feature_image_instance->bb_user_can_perform_feature_image_action(
			array(
				'action'        => 'delete',
				'attachment_id' => $id,
				'activity_id'   => $activity_id,
			)
		);

		if ( ! isset( $can_delete['can_delete'] ) ) {
			return new WP_Error(
				$can_delete['code'],
				$can_delete['message'],
				array( 'status' => $can_delete['status'] )
			);
		}

		// delete attachment with its meta.
		$deleted = wp_delete_attachment( $id, true );

		if ( ! $deleted ) {
			return new WP_Error(
				'bb_pro_invalid_feature_image_attachment',
				__( 'Could not delete the attachment.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Handle AJAX crop feature image requests.
	 *
	 * @since 2.9.0
	 */
	public function bb_handle_ajax_crop_replace() {
		$response = array(
			'feedback' => __( 'There was a problem when trying to crop and replace this image.', 'buddyboss-pro' ),
		);

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'activity_post_feature_image_crop_replace' ) ) {
			wp_send_json_error( $response, 500 );
		}

		if ( bb_pro_should_lock_features() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_loggedin_user_id() ) {
			wp_send_json_error( $response, 500 );
		}

		if ( ! bp_is_post_request() ) {
			wp_send_json_error( $response, 500 );
		}

		$group_id = bb_filter_input_string( INPUT_POST, 'group_id' );
		if (
			! $this->feature_image_instance->bb_user_has_access_feature_image(
				array(
					'group_id' => $group_id,
					'object'   => ! empty( $group_id ) ? 'group' : '',
				)
			)
		) {
			wp_send_json_error( $response, 500 );
		}

		$attachment_id = isset( $_POST['postid'] ) ? intval( $_POST['postid'] ) : 0;
		if ( empty( $attachment_id ) ) {
			wp_send_json_error( $response, 400 );
		}

		// Check if a cropped image file was uploaded.
		if ( ! isset( $_FILES['file'] ) || UPLOAD_ERR_OK !== $_FILES['file']['error'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_send_json_error( $response, 400 );
		}

		// Use the common file replacement method.
		$result = $this->bb_handle_file_replace(
			array(
				'attachment_id' => $attachment_id,
				'file_data'     => $_FILES['file'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'group_id'      => $group_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			$response['feedback'] = $result->get_error_message();
			wp_send_json_error( $response, $result->get_error_code() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle file replacement for attachments.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args The arguments for the file replacement.
	 *
	 * @return array|WP_Error Array of attachment data on success, WP_Error on failure.
	 */
	public function bb_handle_file_replace( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'attachment_id' => 0,
				'file_data'     => null,
				'group_id'      => 0,
			)
		);

		$attachment_id = $r['attachment_id'];
		$file_data     = $r['file_data'];

		if ( empty( $attachment_id ) || empty( $file_data ) ) {
			return new WP_Error(
				'bb_pro_invalid_attachment_id',
				__( 'Invalid attachment ID.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		$validation_result = $this->bb_validate_file_replace_prerequisites( $args );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		$attachment_file = get_attached_file( $attachment_id );
		if ( ! $attachment_file || ! file_exists( $attachment_file ) ) {
			return new WP_Error(
				'bb_pro_attachment_not_found',
				__( 'Attachment file not found.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		$upload_dir        = function_exists( 'bp_upload_dir' ) ? bp_upload_dir() : wp_upload_dir();
		$normalized_file   = wp_normalize_path( realpath( $attachment_file ) );
		$normalized_upload = wp_normalize_path( $upload_dir['basedir'] );
		if ( false === $normalized_file || 0 !== strpos( $normalized_file, $normalized_upload ) ) {
			return new WP_Error(
				'bb_pro_invalid_path',
				__( 'Invalid file path detected.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}
		$expected_basename = basename( $attachment_file );
		$actual_basename   = basename( $normalized_file );
		if ( $expected_basename !== $actual_basename ) {
			return new WP_Error(
				'bb_pro_path_traversal_invalid',
				__( 'Path traversal invalid.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * Fires before the feature image crop and replace handler.
		 *
		 * @since 2.9.0
		 *
		 * @param int $attachment_id The attachment ID.
		 */
		do_action( 'bb_before_activity_post_feature_image_crop_replace_handler', $attachment_id );

		$replace_result = $this->bb_perform_atomic_file_replacement( $attachment_id, $file_data, $attachment_file );
		if ( is_wp_error( $replace_result ) ) {
			return $replace_result;
		}

		/**
		 * Fires after the feature image crop and replace handler.
		 *
		 * @since 2.9.0
		 *
		 * @param int $attachment_id The attachment ID.
		 */
		do_action( 'bb_after_activity_post_feature_image_crop_replace_handler', $attachment_id );

		$config          = $this->feature_image_instance->bb_get_image_sizes();
		$image_size_name = array_keys( $config )[0];
		$hash            = $this->feature_image_instance->bb_generate_attachment_hash( $attachment_id );

		$result = array(
			'id'      => (int) $attachment_id,
			'thumb'   => home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . $hash . '/thumbnail',
			'medium'  => home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . $hash,
			'url'     => home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . $hash . '/' . $image_size_name,
			'cropped' => true,
		);

		return $result;
	}

	/**
	 * Validate prerequisites for file replacement.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args The arguments for the file replacement.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function bb_validate_file_replace_prerequisites( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'attachment_id' => 0,
				'file_data'     => null,
				'group_id'      => 0,
			)
		);

		$attachment_id = $r['attachment_id'];
		$file_data     = $r['file_data'];
		$group_id      = $r['group_id'];

		if ( empty( $attachment_id ) || empty( $file_data ) ) {
			return new WP_Error(
				'bb_pro_invalid_arguments',
				__( 'Invalid arguments.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		$response = array(
			'feedback' => __( 'There was a problem when trying to replace this image.', 'buddyboss-pro' ),
		);

		if ( bb_pro_should_lock_features() ) {
			return new WP_Error(
				'bb_pro_license_invalid',
				$response['feedback'],
				array( 'status' => 500 )
			);
		}

		if ( ! bp_loggedin_user_id() ) {
			return new WP_Error(
				'bb_pro_not_logged_in',
				$response['feedback'],
				array( 'status' => 500 )
			);
		}

		if (
			! $this->feature_image_instance->bb_user_has_access_feature_image(
				array(
					'group_id' => $group_id,
					'object'   => ! empty( $group_id ) ? 'group' : '',
				)
			)
		) {
			return new WP_Error(
				'bb_pro_access_denied',
				$response['feedback'],
				array( 'status' => 500 )
			);
		}

		if ( empty( $attachment_id ) ) {
			return new WP_Error(
				'bb_pro_invalid_attachment_id',
				__( 'Invalid attachment ID.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		$attachment_post = get_post( $attachment_id );
		if ( ! $attachment_post || 'attachment' !== $attachment_post->post_type ) {
			return new WP_Error(
				'bb_pro_invalid_attachment',
				__( 'Invalid attachment.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $file_data ) ) {
			return new WP_Error(
				'bb_pro_no_file_data',
				__( 'No file data provided.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		if ( ! isset( $file_data['error'] ) || UPLOAD_ERR_OK !== $file_data['error'] ) {
			return new WP_Error(
				'bb_pro_upload_error',
				__( 'File upload failed.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		$validate_file = $this->bb_validate_file_data( $file_data );
		if ( ! empty( $validate_file ) && is_array( $validate_file ) ) {
			return new WP_Error(
				$validate_file['code'],
				$validate_file['message'],
				array( 'status' => $validate_file['status'] )
			);
		}

		return true;
	}

	/**
	 * Perform atomic file replacement with rollback capabilities.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $attachment_id   The attachment ID.
	 * @param array  $file_data       The uploaded file data.
	 * @param string $attachment_file The path to the attachment file.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function bb_perform_atomic_file_replacement( $attachment_id, $file_data, $attachment_file ) {
		// Initialize WP_Filesystem.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$backup_file = null;
		$temp_file   = null;

		// Create a backup of the original file.
		$backup_file = $attachment_file . '.backup.' . time();
		if ( ! copy( $attachment_file, $backup_file ) ) {
			return new WP_Error(
				'bb_pro_backup_failed',
				__( 'Failed to create backup of original image.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		// Create a temporary file in the same directory for atomic replacement.
		$temp_file = $attachment_file . '.tmp.' . time();

		// Move uploaded file to temporary location first.
		if ( ! $wp_filesystem->move( $file_data['tmp_name'], $temp_file ) ) {
			if ( ! wp_is_writable( dirname( $temp_file ) ) ) {
				return new WP_Error(
					'directory_not_writable',
					__( 'Upload directory is not writable.', 'buddyboss-pro' ),
					array( 'status' => 500 )
				);
			}
			// Cleanup backup file.
			if ( file_exists( $backup_file ) ) {
				wp_delete_file( $backup_file );
			}

			return new WP_Error(
				'bb_pro_upload_failed',
				__( 'Failed to process the uploaded image.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		// Verify the temporary file is valid before proceeding.
		if ( ! file_exists( $temp_file ) || filesize( $temp_file ) === 0 ) {
			// Cleanup files.
			if ( file_exists( $temp_file ) ) {
				wp_delete_file( $temp_file );
			}
			if ( file_exists( $backup_file ) ) {
				wp_delete_file( $backup_file );
			}

			return new WP_Error(
				'bb_pro_invalid_file',
				__( 'Uploaded image file is invalid or empty.', 'buddyboss-pro' ),
				array( 'status' => 400 )
			);
		}

		// Get original file metadata before replacement.
		$original_metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $wp_filesystem->move( $temp_file, $attachment_file, true ) ) {
			if ( ! wp_is_writable( dirname( $attachment_file ) ) ) {
				return new WP_Error(
					'directory_not_writable',
					__( 'Upload directory is not writable.', 'buddyboss-pro' ),
					array( 'status' => 500 )
				);
			}
			// Cleanup files and restore backup.
			if ( file_exists( $temp_file ) ) {
				wp_delete_file( $temp_file );
			}
			if ( file_exists( $backup_file ) ) {
				$wp_filesystem->copy( $backup_file, $attachment_file, true );
				wp_delete_file( $backup_file );
			}

			return new WP_Error(
				'bb_pro_replace_failed',
				__( 'Failed to replace the original image.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		// Clean up old attachment files AFTER successful replacement.
		$this->bb_cleanup_old_attachment_files( $attachment_id, $original_metadata, $attachment_file );

		// Regenerate attachment metadata.
		$metadata = wp_generate_attachment_metadata( $attachment_id, $attachment_file );

		if ( is_wp_error( $metadata ) ) {
			// Rollback: restore original file and metadata.
			if ( file_exists( $backup_file ) ) {
				$wp_filesystem->copy( $backup_file, $attachment_file, true );
				if ( $original_metadata ) {
					wp_update_attachment_metadata( $attachment_id, $original_metadata );
				}
			}
			// Cleanup backup file.
			if ( file_exists( $backup_file ) ) {
				wp_delete_file( $backup_file );
			}

			return new WP_Error(
				'bb_pro_metadata_failed',
				__( 'Failed to generate new image metadata.', 'buddyboss-pro' ),
				array( 'status' => 500 )
			);
		}

		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Mark the attachment as cropped so we can hide the reposition button.
		update_post_meta( $attachment_id, 'bb_activity_post_feature_image_cropped', 1 );

		if ( file_exists( $backup_file ) ) {
			wp_delete_file( $backup_file );
		}

		return true;
	}

	/**
	 * Clean up old attachment files including all generated sizes.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $attachment_id   The attachment ID.
	 * @param array  $metadata        The original attachment metadata.
	 * @param string $attachment_file The main attachment file path.
	 *
	 * @return void
	 */
	private function bb_cleanup_old_attachment_files( $attachment_id, $metadata, $attachment_file ) {
		if ( empty( $metadata ) || ! is_array( $metadata ) ) {
			return;
		}

		$file_path = dirname( $attachment_file );

		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				if ( isset( $size_data['file'] ) ) {
					$size_file = trailingslashit( $file_path ) . $size_data['file'];
					$this->bb_delete_feature_image_attachment( $size_file );
				}
			}
		}

		if ( isset( $metadata['original_image'] ) ) {
			$original_file = trailingslashit( $file_path ) . $metadata['original_image'];
			$this->bb_delete_feature_image_attachment( $original_file );
		}

		if ( function_exists( 'wp_get_webp_info' ) && isset( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				if ( isset( $size_data['file'] ) ) {
					$webp_file = trailingslashit( $file_path ) . pathinfo( $size_data['file'], PATHINFO_FILENAME ) . '.webp';
					$this->bb_delete_feature_image_attachment( $webp_file );
				}
			}

			$main_webp = trailingslashit( $file_path ) . pathinfo( basename( $attachment_file ), PATHINFO_FILENAME ) . '.webp';
			$this->bb_delete_feature_image_attachment( $main_webp );
		}

		/**
		 * Allow additional cleanup of old attachment files.
		 *
		 * @since 2.9.0
		 *
		 * @param int    $attachment_id   The attachment ID.
		 * @param array  $metadata        The original attachment metadata.
		 * @param string $attachment_file The main attachment file path.
		 * @param string $file_path       The directory path containing the files.
		 */
		do_action( 'bb_cleanup_old_attachment_files', $attachment_id, $metadata, $attachment_file, $file_path );
	}

	/**
	 * Delete a feature image attachment.
	 *
	 * @since 2.9.0
	 *
	 * @param string $file_path The file path to delete.
	 *
	 * @return void
	 */
	private function bb_delete_feature_image_attachment( $file_path ) {
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return;
		}

		$upload_dir  = function_exists( 'bp_upload_dir' ) ? bp_upload_dir() : wp_upload_dir();
		$upload_path = wp_normalize_path( $upload_dir['basedir'] );

		$normalized_file = wp_normalize_path( realpath( $file_path ) );

		if ( false === $normalized_file || 0 !== strpos( $normalized_file, $upload_path ) ) {
			return;
		}
		$expected_basename = basename( $file_path );
		$actual_basename   = basename( $normalized_file );

		if ( $expected_basename !== $actual_basename ) {
			return;
		}

		wp_delete_file( $file_path );
	}

	/**
	 * Validate the file data.
	 *
	 * @since 2.9.0
	 *
	 * @param array $file_data The data of the uploaded file.
	 *
	 * @return array|bool Array of error data if the file data is invalid, true if the file data is valid.
	 */
	public function bb_validate_file_data( $file_data = null ) {
		if ( ! $file_data ) {
			return $this->feature_image_instance->create_error_response(
				'upload_error',
				__( 'No file was uploaded.', 'buddyboss-pro' ),
				400
			);
		}

		if ( ! isset( $file_data['tmp_name'] ) || ! isset( $file_data['name'] ) ) {
			return $this->feature_image_instance->create_error_response(
				'upload_error',
				__( 'Invalid file data provided.', 'buddyboss-pro' ),
				400
			);
		}

		if ( ! is_uploaded_file( $file_data['tmp_name'] ) ) {
			return $this->feature_image_instance->create_error_response(
				'upload_error',
				__( 'File upload failed or file was not uploaded properly.', 'buddyboss-pro' ),
				400
			);
		}

		$file_info = wp_check_filetype_and_ext( $file_data['tmp_name'], $file_data['name'] );
		if ( ! $file_info['type'] || ! in_array( $file_info['type'], $this->feature_image_instance->bb_get_allowed_mimes(), true ) ) {
			return $this->feature_image_instance->create_error_response(
				'bb_rest_upload_file_type_not_allowed',
				__( 'File type not allowed. Please upload a valid image file (JPG, PNG, GIF, BMP).', 'buddyboss-pro' ),
				400
			);
		}

		$file_size = isset( $file_data['size'] ) ? $file_data['size'] : 0;
		if ( empty( $file_size ) ) {
			return $this->feature_image_instance->create_error_response(
				'bb_rest_upload_file_size_empty',
				__( 'File size is empty.', 'buddyboss-pro' ),
				400
			);
		}

		$file_size_unit = $this->feature_image_instance->bb_format_size_units( $file_size, false, 'MB' );
		$max_size_mb    = $this->feature_image_instance->bb_get_max_upload_size();
		if ( $file_size_unit > $max_size_mb ) {
			return $this->feature_image_instance->create_error_response(
				'bb_rest_upload_file_too_large',
				sprintf(
					/* translators: %s: Maximum file size in MB */
					__( 'File size exceeds the maximum allowed size of %s MB.', 'buddyboss-pro' ),
					$max_size_mb
				),
				400
			);
		}

		return true;
	}

	/**
	 * Handle the feature image upload process.
	 *
	 * @since 2.9.0
	 *
	 * @param array $file_data The data of the uploaded file.
	 *
	 * @return array|WP_Error Upload result or error.
	 */
	public function bb_handle_upload( $file_data ) {
		/**
		 * Make sure user is logged in
		 */
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'Please login in order to upload feature image.', 'buddyboss-pro' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires before the feature image upload handler.
		 *
		 * @since 2.9.0
		 */
		do_action( 'bb_before_activity_post_feature_image_upload_load_handler' );

		$attachment = $this->bb_handle_upload_process( $file_data );

		/**
		 * Fires after the feature image upload handler.
		 *
		 * @since 2.9.0
		 */
		do_action( 'bb_after_activity_post_feature_image_upload_load_handler' );

		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}

		/**
		 * Hook feature image upload.
		 *
		 * @since 2.9.0
		 *
		 * @param mixed $attachment attachment
		 */
		do_action( 'bb_activity_post_feature_image_upload', $attachment );

		$name            = $attachment->post_title;
		$config          = $this->feature_image_instance->bb_get_image_sizes();
		$image_size_name = array_keys( $config )[0];

		// Generate a feature image attachment preview link with secure hash.
		$hash                 = $this->feature_image_instance->bb_generate_attachment_hash( $attachment->ID );
		$attachment_medium    = home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment->ID ) . '/' . $hash;
		$attachment_thumb_url = home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment->ID ) . '/' . $hash . '/thumbnail';
		$attachment_url       = home_url( '/' ) . 'bb-attachment-feature-image-preview/' . base64_encode( 'forbidden_' . $attachment->ID ) . '/' . $hash . '/' . $image_size_name;

		$result = array(
			'id'     => (int) $attachment->ID,
			'thumb'  => $attachment_thumb_url,
			'medium' => $attachment_medium,
			'url'    => untrailingslashit( $attachment_url ),
			'name'   => esc_attr( $name ),
		);

		return $result;
	}

	/**
	 * Handle the feature image upload process.
	 *
	 * @since 2.9.0
	 *
	 * @param array $file_data The data of the uploaded file.
	 *
	 * @return WP_Post|WP_Error WP_Post object of the created attachment on success,
	 *                          WP_Error object on failure.
	 */
	private function bb_handle_upload_process( $file_data ) {
		/**
		 * Include required files.
		 */
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}

		// Add upload filters.
		$this->bb_add_upload_filters();

		// Register image sizes.
		$this->bb_register_image_sizes();

		$aid = $this->bb_process_upload(
			$file_data,
			0,
			array(),
			array(
				'test_form'            => false,
				'upload_error_strings' => array(
					false,
					sprintf(
					/* translators: %d: Maximum upload size in MB */
						__( 'The uploaded file exceeds %d MB', 'buddyboss-pro' ),
						$this->feature_image_instance->bb_get_max_upload_size()
					),
					__( 'The uploaded file was only partially uploaded.', 'buddyboss-pro' ),
					__( 'No file was uploaded.', 'buddyboss-pro' ),
					'',
					__( 'Missing a temporary folder.', 'buddyboss-pro' ),
					__( 'Failed to write file to disk.', 'buddyboss-pro' ),
					__( 'File upload stopped by extension.', 'buddyboss-pro' ),
				),
			)
		);

		// Deregister image sizes.
		$this->bb_deregister_image_sizes();

		// Remove upload filters.
		$this->bb_remove_upload_filters();

		// If has wp error then throw it.
		if ( is_wp_error( $aid ) ) {
			return $aid;
		}

		// Image rotation fix.
		do_action( 'bb_activity_post_feature_image_attachment_uploaded', $aid );

		$attachment = get_post( $aid );

		if ( ! empty( $attachment ) ) {
			update_post_meta( $attachment->ID, 'bb_activity_post_feature_image_upload', true );
			update_post_meta( $attachment->ID, 'bb_activity_post_feature_image_saved', '0' );

			return $attachment;
		}

		return new WP_Error( 'error_uploading', __( 'Error while uploading feature image.', 'buddyboss-pro' ), array( 'status' => 500 ) );
	}

	/**
	 * Handle the actual file upload for feature images.
	 *
	 * @since 2.9.0
	 *
	 * @param array $file_data The data of the uploaded file.
	 * @param int   $post_id   The post ID to attach the file to.
	 * @param array $post_data Optional. Array of post data to override default values.
	 * @param array $overrides Optional. Array of upload overrides.
	 *
	 * @return int|WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	private function bb_process_upload( $file_data, $post_id, $post_data = array(), $overrides = array( 'test_form' => false ) ) {
		$time = current_time( 'mysql' );
		$post = get_post( $post_id );

		if ( $post ) {
			// The post date doesn't usually matter for pages, so don't backdate this upload.
			if ( 'page' !== $post->post_type && substr( $post->post_date, 0, 4 ) > 0 ) {
				$time = $post->post_date;
			}
		}

		$file_data_error = $this->bb_validate_file_data( $file_data );
		if ( ! empty( $file_data_error ) && is_array( $file_data_error ) ) {
			return new WP_Error(
				$file_data_error['code'],
				$file_data_error['message'],
				array( 'status' => $file_data_error['status'] )
			);
		}

		$file = wp_handle_upload( $file_data, $overrides, $time );
		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'upload_error', $file['error'] );
		}

		$name = isset( $file_data['name'] ) ? sanitize_text_field( wp_unslash( $file_data['name'] ) ) : '';
		$ext  = pathinfo( $name, PATHINFO_EXTENSION );
		$name = wp_basename( $name, ".$ext" );

		$url       = $file['url'];
		$type      = $file['type'];
		$file_path = $file['file'];
		$title     = sanitize_text_field( $name );
		$content   = '';
		$excerpt   = '';

		if ( str_starts_with( $type, 'image/' ) ) {
			$image_meta = wp_read_image_metadata( $file_path );

			if ( $image_meta ) {
				if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
					$title = $image_meta['title'];
				}

				if ( trim( $image_meta['caption'] ) ) {
					$excerpt = $image_meta['caption'];
				}
			}
		}

		// Construct the attachment array.
		$attachment = array_merge(
			array(
				'post_mime_type' => $type,
				'guid'           => $url,
				'post_parent'    => $post_id,
				'post_title'     => $title,
				'post_content'   => $content,
				'post_excerpt'   => $excerpt,
			),
			$post_data
		);

		// This should never be set as it would then overwrite an existing attachment.
		unset( $attachment['ID'] );

		// Save the data.
		$attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id, true );
		if ( ! is_wp_error( $attachment_id ) ) {
			/*
			 * Set a custom header with the attachment_id.
			 * Used by the browser/client to resume creating image sub-sizes after a PHP fatal error.
			 */
			if ( ! headers_sent() ) {
				header( 'X-WP-Upload-Attachment-ID: ' . $attachment_id );
			}

			/*
			 * The image sub-sizes are created during wp_generate_attachment_metadata().
			 * This is generally slow and may cause timeouts or out of memory errors.
			 */
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_path ) );
		}

		return $attachment_id;
	}

	/**
	 * Set custom upload directory for feature images.
	 *
	 * @since 2.9.0
	 *
	 * @param array $pathdata Upload path data.
	 *
	 * @return array Modified upload path data with custom directory structure.
	 */
	public function bb_get_upload_dir( $pathdata ) {
		if (
			bb_is_rest() ||
			(
				isset( $_POST['action'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'activity_post_feature_image_upload' === $_POST['action'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		) {
			$config = $this->feature_image_instance->bb_get_config();
			// Check if our custom directory is already in the path to prevent duplication.
			if ( strpos( $pathdata['path'], '/' . $config['upload_dir'] ) === false ) {
				if ( empty( $pathdata['subdir'] ) ) {
					$pathdata['path']   = $pathdata['path'] . '/' . $config['upload_dir'];
					$pathdata['url']    = $pathdata['url'] . '/' . $config['upload_dir'];
					$pathdata['subdir'] = '/' . $config['upload_dir'];
				} else {
					// Insert our custom directory before the existing subdir.
					$pathdata['path']   = str_replace( $pathdata['subdir'], '/' . $config['upload_dir'] . $pathdata['subdir'], $pathdata['path'] );
					$pathdata['url']    = str_replace( $pathdata['subdir'], '/' . $config['upload_dir'] . $pathdata['subdir'], $pathdata['url'] );
					$pathdata['subdir'] = '/' . $config['upload_dir'] . $pathdata['subdir'];
				}
			}
		}

		return $pathdata;
	}

	/**
	 * Register custom image sizes for feature images.
	 *
	 * @since 2.9.0
	 */
	public function bb_register_image_sizes() {
		$image_sizes = $this->feature_image_instance->bb_get_image_sizes();

		if ( ! empty( $image_sizes ) ) {
			foreach ( $image_sizes as $name => $image_size ) {
				if ( ! empty( $image_size['width'] ) && ! empty( $image_size['height'] ) && 0 < (int) $image_size['width'] && 0 < $image_size['height'] ) {
					add_image_size( sanitize_key( $name ), $image_size['width'], $image_size['height'], ( isset( $image_size['crop'] ) ? $image_size['crop'] : false ) );
				}
			}
		}
	}

	/**
	 * Remove default WordPress image sizes for feature images.
	 *
	 * @since 2.9.0
	 *
	 * @param array $sizes Array of image sizes to be generated.
	 *
	 * @return array Filtered array containing only custom feature image sizes.
	 */
	public function bb_remove_default_image_sizes( $sizes ) {
		$image_sizes = $this->feature_image_instance->bb_get_image_sizes();
		$size_names  = array_keys( $image_sizes );

		foreach ( $size_names as $size_name ) {
			if ( isset( $sizes[ $size_name ] ) ) {
				return array(
					$size_name  => $sizes[ $size_name ],
					'thumbnail' => $sizes['thumbnail'],
				);
			}
		}

		return array();
	}

	/**
	 * Deregister custom image sizes for feature images.
	 *
	 * @since 2.9.0
	 */
	public function bb_deregister_image_sizes() {
		$image_sizes = $this->feature_image_instance->bb_get_image_sizes();

		if ( ! empty( $image_sizes ) ) {
			foreach ( $image_sizes as $name => $image_size ) {
				remove_image_size( sanitize_key( $name ) );
			}
		}
	}

	/**
	 * Add custom upload filters for feature image processing.
	 *
	 * @since 2.9.0
	 */
	public function bb_add_upload_filters() {
		add_filter( 'upload_dir', array( $this, 'bb_get_upload_dir' ) );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'bb_remove_default_image_sizes' ) );
		add_filter( 'upload_mimes', array( $this->feature_image_instance, 'bb_get_allowed_mimes' ), 9, 1 );
		add_filter( 'big_image_size_threshold', '__return_false' );
	}

	/**
	 * Remove custom upload filters for feature image processing.
	 *
	 * @since 2.9.0
	 */
	public function bb_remove_upload_filters() {
		remove_filter( 'upload_dir', array( $this, 'bb_get_upload_dir' ) );
		remove_filter( 'intermediate_image_sizes_advanced', array( $this, 'bb_remove_default_image_sizes' ) );
		remove_filter( 'upload_mimes', array( $this->feature_image_instance, 'bb_get_allowed_mimes' ), 9 );
		remove_filter( 'big_image_size_threshold', '__return_false' );
	}
}
