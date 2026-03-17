<?php
/**
 * BuddyBoss Activity Post Feature Image Preview Handler.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Platform Settings/Activity/Post Feature Image
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Post Feature Image Preview Handler Class.
 *
 * Handles URL routing and template handling for activity feature image previews.
 *
 * @since 2.9.0
 */
class BB_Activity_Post_Feature_Image_Preview {

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
	 * Setup actions for activity preview functionality.
	 *
	 * @since 2.9.0
	 */
	private function setup_actions() {
		// Activity feature image preview.
		add_action( 'init', array( $this, 'bb_setup_activity_feature_image_preview_rewrite_rules' ), 99 );
		add_filter( 'query_vars', array( $this, 'bb_activity_post_feature_image_add_query_vars' ) );
		add_action( 'template_include', array( $this, 'bb_activity_post_feature_image_handle_preview_template' ), PHP_INT_MAX );
	}

	/**
	 * Setup activity feature image preview rewrite rules.
	 *
	 * @since 2.9.0
	 */
	public function bb_setup_activity_feature_image_preview_rewrite_rules() {
		// Basic rule with activity ID, attachment ID, hash.
		add_rewrite_rule(
			'bb-activity-post-feature-image-preview/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?bb-activity-id=$matches[1]&bb-activity-post-feature-image-attachment-id=$matches[2]&activity_hash=$matches[3]',
			'top'
		);

		// Rule with activity ID, attachment ID, hash, and size.
		add_rewrite_rule(
			'bb-activity-post-feature-image-preview/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?bb-activity-id=$matches[1]&bb-activity-post-feature-image-attachment-id=$matches[2]&activity_hash=$matches[3]&size=$matches[4]',
			'top'
		);
	}

	/**
	 * Setup query variable for activity preview.
	 *
	 * @since 2.9.0
	 *
	 * @param array $query_vars Array of query variables.
	 *
	 * @return array
	 */
	public function bb_activity_post_feature_image_add_query_vars( $query_vars ) {
		$query_vars[] = 'bb-activity-id';
		$query_vars[] = 'bb-activity-post-feature-image-attachment-id';
		$query_vars[] = 'activity_hash';
		$query_vars[] = 'size';

		return $query_vars;
	}

	/**
	 * Handle activity feature image preview template with wp_hash verification.
	 *
	 * @since 2.9.0
	 *
	 * @param string $template Template path to include.
	 *
	 * @return string
	 */
	public function bb_activity_post_feature_image_handle_preview_template( $template ) {
		$activity_id   = get_query_var( 'bb-activity-id' );
		$attachment_id = get_query_var( 'bb-activity-post-feature-image-attachment-id' );
		$hash          = get_query_var( 'activity_hash' );

		if ( empty( $hash ) || empty( $activity_id ) || empty( $attachment_id ) ) {
			return $template;
		}

		// Validate the hash using wp_hash.
		if ( ! $this->feature_image_instance->bb_validate_attachment_hash( $attachment_id, $hash, $activity_id ) ) {
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
		do_action( 'bb_setup_template_for_activity_post_feature_image_preview' );

		$preview_template = $this->bb_get_template_path() . '/preview/preview.php';

		return $preview_template;
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
