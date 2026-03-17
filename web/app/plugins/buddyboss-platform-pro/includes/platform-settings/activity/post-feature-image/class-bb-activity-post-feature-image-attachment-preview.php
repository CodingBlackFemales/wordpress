<?php
/**
 * BuddyBoss Activity Post Feature Image Attachment Preview Handler.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Platform Settings/Activity/Post Feature Image
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Post Feature Image Attachment Preview Handler Class.
 *
 * Handles URL routing and template handling for attachment previews during upload.
 *
 * @since 2.9.0
 */
class BB_Activity_Post_Feature_Image_Attachment_Preview {

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
	 * Setup actions for attachment preview functionality.
	 *
	 * @since 2.9.0
	 */
	private function setup_actions() {
		// Attachment feature image preview.
		add_action( 'init', array( $this, 'bb_setup_attachment_feature_image_preview_rewrite_rules' ), 99 );
		add_filter( 'query_vars', array( $this, 'bb_attachment_post_feature_image_add_query_vars' ) );
		add_action( 'template_include', array( $this, 'bb_attachment_post_feature_image_handle_preview_template' ), PHP_INT_MAX );
	}

	/**
	 * Setup rewrite rules for attachment preview.
	 *
	 * @since 2.9.0
	 */
	public function bb_setup_attachment_feature_image_preview_rewrite_rules() {
		add_rewrite_rule(
			'bb-attachment-feature-image-preview/([^/]+)/([^/]+)/?$',
			'index.php?activity-feature-image-attachment-id=$matches[1]&attachment_hash=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'bb-attachment-feature-image-preview/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?activity-feature-image-attachment-id=$matches[1]&attachment_hash=$matches[2]&size=$matches[3]',
			'top'
		);
	}

	/**
	 * Add query variables for attachment preview.
	 *
	 * @since 2.9.0
	 *
	 * @param array $query_vars Array of query variables.
	 *
	 * @return array
	 */
	public function bb_attachment_post_feature_image_add_query_vars( $query_vars ) {
		$query_vars[] = 'activity-feature-image-attachment-id';
		$query_vars[] = 'attachment_hash';
		$query_vars[] = 'size';
		return $query_vars;
	}

	/**
	 * Handle attachment preview template.
	 *
	 * @since 2.9.0
	 *
	 * @param string $template Template path to include.
	 *
	 * @return string
	 */
	public function bb_attachment_post_feature_image_handle_preview_template( $template ) {
		$attachment_id = get_query_var( 'activity-feature-image-attachment-id' );
		$hash          = get_query_var( 'attachment_hash' );
		$size          = get_query_var( 'size' );

		if ( ! empty( $attachment_id ) && ! empty( $hash ) ) {
			// Validate the hash using wp_hash.
			if ( ! $this->feature_image_instance->bb_validate_attachment_hash( $attachment_id, $hash ) ) {
				wp_die(
					esc_html__( 'Invalid security token.', 'buddyboss-pro' ),
					esc_html__( 'Security Error', 'buddyboss-pro' ),
					array( 'response' => 403 )
				);
			}

			/**
			 * Hooks to perform any action before the template load.
			 *
			 * @since 2.9.0
			 */
			do_action( 'bb_setup_attachment_post_feature_image_preview_template' );

			$preview_template = $this->bb_get_template_path() . '/preview/attachment.php';

			return $preview_template;
		}

		return $template;
	}

	/**
	 * Preview feature image attachment.
	 *
	 * @since 2.9.0
	 *
	 * @param string $path         File path.
	 * @param int    $attachment_id Attachment ID.
	 */
	public function bb_preview_attachment( $path, $attachment_id ) {
		$buffer = 102400;

		$stream = fopen( $path, 'rb' );
		if ( ! $stream ) {
			die( 'Could not open stream for reading' );
		}

		ob_get_clean();
		$type = get_post_mime_type( $attachment_id );
		header( "Content-Type: $type" );
		header( 'Cache-Control: max-age=2592000, public' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 2592000 ) . ' GMT' );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', @filemtime( $path ) ) . ' GMT' );
		$start = 0;
		$size  = filesize( $path );
		$end   = $size - 1;
		header( 'Accept-Ranges: 0-' . $end );

		if ( isset( $_SERVER['HTTP_RANGE'] ) ) {

			$c_start = $start;
			$c_end   = $end;

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			list( , $range ) = explode( '=', $_SERVER['HTTP_RANGE'], 2 );
			if ( strpos( $range, ',' ) !== false ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $start-$end/$size" );
				exit;
			}
			if ( '-' === $range ) {
				$c_start = $size - substr( $range, 1 );
			} else {
				$range   = explode( '-', $range );
				$c_start = $range[0];

				$c_end = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? $range[1] : $c_end;
			}
			$c_end = ( $c_end > $end ) ? $end : $c_end;
			if ( $c_start > $c_end || $c_start > $size - 1 || $c_end >= $size ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $start-$end/$size" );
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1;
			fseek( $stream, $start );
			header( 'HTTP/1.1 206 Partial Content' );
			header( 'Content-Length: ' . $length );
			header( "Content-Range: bytes $start-$end/" . $size );
		} else {
			header( 'Content-Length: ' . $size );
		}

		$i = $start;
		set_time_limit( 0 );
		while ( ! feof( $stream ) && $i <= $end ) {
			$bytes_to_read = $buffer;
			if ( ( $i + $bytes_to_read ) > $end ) {
				$bytes_to_read = $end - $i + 1;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			$data = fread( $stream, $bytes_to_read );
			echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			flush();
			$i += $bytes_to_read;
		}

		fclose( $stream );
		exit;
	}

	/**
	 * Get a template directory path.
	 *
	 * @since 2.9.0
	 *
	 * @return string
	 */
	private function bb_get_template_path() {
		return bb_platform_pro()->platform_settings_dir . '/activity/post-feature-image/templates';
	}
}
