<?php
/**
 * Feature Image Preview.
 *
 * @since   2.9.0
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$encoded_activity_id   = ! empty( get_query_var( 'bb-activity-id' ) ) ? get_query_var( 'bb-activity-id' ) : '';
$encoded_attachment_id = ! empty( get_query_var( 'bb-activity-post-feature-image-attachment-id' ) ) ? get_query_var( 'bb-activity-post-feature-image-attachment-id' ) : '';
$decode_activity_id    = ! empty( $encoded_activity_id ) ? base64_decode( $encoded_activity_id ) : '';
$decode_attachment_id  = ! empty( $encoded_attachment_id ) ? base64_decode( $encoded_attachment_id ) : '';
$explode_activity_id   = explode( 'forbidden_', $decode_activity_id );
$explode_attachment_id = explode( 'forbidden_', $decode_attachment_id );
$activity_id           = ! empty( $explode_activity_id[1] ) ? $explode_activity_id[1] : 0;
$attachment_id         = ! empty( $explode_attachment_id[1] ) ? $explode_attachment_id[1] : 0;

$silence_is_golden = __( 'Silence is golden.', 'buddyboss-pro' );
$security_error    = __( 'Security Error', 'buddyboss-pro' );
if (
	empty( $activity_id ) &&
	empty( $attachment_id )
) {
	wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
}

$hash            = get_query_var( 'activity_hash' );
$size            = ( ! empty( get_query_var( 'size' ) ) ? get_query_var( 'size' ) : '' );
$upload_dir      = function_exists( 'bp_upload_dir' ) ? bp_upload_dir() : wp_upload_dir();
$upload_dir      = $upload_dir['basedir'];
$output_file_src = '';

if ( empty( $hash ) ) {
	wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
}

$feature_image_instance = BB_Activity_Post_Feature_Image::instance();
$valid_hash             = $feature_image_instance->bb_validate_attachment_hash( $encoded_attachment_id, $hash, $encoded_activity_id );

if ( ! $valid_hash ) {
	wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
}

$attachment_id = (int) $attachment_id;
$can_view      = false;
if ( ! empty( $activity_id ) ) {
	$original_activity = new BP_Activity_Activity( $activity_id );
	$can_view          = bp_activity_user_can_read( $original_activity, bp_loggedin_user_id() );
}
$attached_file_info = pathinfo( get_attached_file( $attachment_id ) );

if ( $can_view && wp_attachment_is_image( $attachment_id ) ) {

	$file_type = get_post_mime_type( $attachment_id );
	$file      = image_get_intermediate_size( $attachment_id, $size );
	$file_path = $attached_file_info['dirname'];

	// Helper function to validate file path security.
	$validate_file_path = function ( $file_path, $file_name, $upload_dir ) {
		$file_path_org   = $file_path . '/' . basename( $file_name );
		$real_file_path  = realpath( $file_path_org );
		$real_upload_dir = realpath( $upload_dir );

		if ( false === $real_file_path || strpos( $real_file_path, $real_upload_dir ) !== 0 ) {
			return false;
		}
		return $file_path_org;
	};

	// Helper function to get fallback path.
	$get_fallback_path = function ( $attachment_id, $upload_dir ) {
		$file = image_get_intermediate_size( $attachment_id, 'full' );
		if ( $file && ! empty( $file['path'] ) ) {
			return $upload_dir . '/' . $file['path'];
		}
		return bb_core_scaled_attachment_path( $attachment_id );
	};

	// Helper function to regenerate thumbnails and get file.
	$regenerate_and_get_file = function ( $attachment_id, $size ) use ( $feature_image_instance ) {
		$feature_image_instance->bb_regenerate_attachment_thumbnails( $attachment_id );
		return image_get_intermediate_size( $attachment_id, $size );
	};

	// Main logic for getting file path.
	if ( '' !== $size && $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {

		$output_file_src = $validate_file_path( $file_path, $file['file'], $upload_dir );
		if ( false === $output_file_src ) {
			wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
		}

		if ( ! file_exists( $output_file_src ) ) {
			$file = $regenerate_and_get_file( $attachment_id, $size );

			if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
				$output_file_src = $validate_file_path( $file_path, $file['file'], $upload_dir );
				if ( false === $output_file_src ) {
					wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
				}
			} else {
				$output_file_src = $get_fallback_path( $attachment_id, $upload_dir );
			}
		}
	} elseif ( ! $file ) {

		$file = $regenerate_and_get_file( $attachment_id, $size );

		if ( $file && ! empty( $file['path'] ) ) {
			$output_file_src = $upload_dir . '/' . $file['path'];
		} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {

			$output_file_src = get_attached_file( $attachment_id );

			if ( ! file_exists( $output_file_src ) ) {
				$file = $regenerate_and_get_file( $attachment_id, $size );
			}

			if ( $file && ! empty( $file['path'] ) ) {
				$output_file_src = $upload_dir . '/' . $file['path'];
			} else {
				$output_file_src = $get_fallback_path( $attachment_id, $upload_dir );
			}
		} else {
			$output_file_src = $get_fallback_path( $attachment_id, $upload_dir );
		}
	} else {
		$output_file_src = $get_fallback_path( $attachment_id, $upload_dir );
	}

	// Final fallback check.
	if ( ! file_exists( $output_file_src ) ) {
		$output_file_src = $get_fallback_path( $attachment_id, $upload_dir );
	}

	if ( ! file_exists( $output_file_src ) ) {
		wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
	}

	// Clear all output buffer.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	header( "Content-Type: $file_type" );
	header( 'Cache-Control: max-age=2592000, public' );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 2592000 ) . ' GMT' );
	readfile( "$output_file_src" );

} else {
	wp_die( esc_html( $silence_is_golden ), esc_html( $security_error ), array( 'response' => 403 ) );
}
