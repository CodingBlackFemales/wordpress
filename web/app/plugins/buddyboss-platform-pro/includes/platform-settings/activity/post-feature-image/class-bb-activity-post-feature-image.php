<?php
/**
 * BuddyBoss Activity Post Feature Image.
 *
 * @since   2.9.0
 * @package BuddyBossPro/Platform Settings/Activity/Post Feature Image
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bb activity post feature image class.
 *
 * @since 2.9.0
 */
class BB_Activity_Post_Feature_Image {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.9.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Upload handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_Activity_Post_Feature_Image_Upload
	 */
	private $upload_handler;

	/**
	 * REST endpoint handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_REST_Activity_Post_Feature_Image_Endpoint
	 */
	private $rest_endpoint_handler;

	/**
	 * Attachment preview handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_Activity_Post_Feature_Image_Attachment_Preview
	 */
	private $attachment_preview_handler;

	/**
	 * Activity preview handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @var BB_Activity_Post_Feature_Image_Preview
	 */
	private $activity_preview_handler;

	/**
	 * Feature configuration
	 *
	 * @since 2.9.0
	 *
	 * @var array
	 */
	private $config = array(
		'feature_name'  => 'activity-post-feature-image',
		'option_key'    => 'bb_enable_activity_post_feature_image',
		'upload_dir'    => 'bb_activity_post_feature_images',
		'allowed_mimes' => array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
		),
		'image_sizes'   => array(
			'bb-activity-post-feature-image' => array(
				'width'  => 2400,
				'height' => 2400,
				'crop'   => false,
			),
		),
	);

	/**
	 * Get the instance of this class.
	 *
	 * @since 2.9.0
	 *
	 * @return object Instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Activity Feature Image Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		$this->load_preview_classes();
		$this->setup_actions();
	}

	/**
	 * Load preview classes and create instances.
	 *
	 * @since 2.9.0
	 */
	private function load_preview_classes() {
		// Load and initialize upload class.
		require_once __DIR__ . '/class-bb-activity-post-feature-image-upload.php';
		$this->upload_handler = BB_Activity_Post_Feature_Image_Upload::instance( $this );

		// Load and initialize attachment preview class.
		require_once __DIR__ . '/class-bb-activity-post-feature-image-attachment-preview.php';
		$this->attachment_preview_handler = BB_Activity_Post_Feature_Image_Attachment_Preview::instance( $this );

		// Load and initialize activity preview class.
		require_once __DIR__ . '/class-bb-activity-post-feature-image-preview.php';
		$this->activity_preview_handler = BB_Activity_Post_Feature_Image_Preview::instance( $this );

		// Load and initialise REST endpoint class.
		require_once __DIR__ . '/class-bb-rest-activity-post-feature-image-endpoint.php';
		$this->rest_endpoint_handler = new BB_REST_Activity_Post_Feature_Image_Endpoint();
	}

	/**
	 * Setup actions for activity feature image functionality.
	 *
	 * @since 2.9.0
	 */
	private function setup_actions() {
		add_action( 'bp_enqueue_scripts', array( $this, 'bb_enqueue_assets' ) );
		add_action( 'bb_activity_header_after', array( $this, 'bb_render_feature_image_button' ) );
		add_filter( 'bp_nouveau_prepare_group_for_js', array( $this, 'bb_prepare_group_for_js' ), 10, 2 );
		add_filter( 'bp_messages_js_template_parts', array( $this, 'bb_add_js_templates' ) );
		bp_register_template_stack( array( $this, 'bb_get_template_path' ) );
		add_filter( 'redirect_canonical', array( $this, 'bb_remove_specific_trailing_slash' ), 9999 );
		add_action( 'bp_activity_after_save', array( $this, 'bb_store_activity_feature_image' ), 10, 2 );
		add_filter( 'bp_activity_get_edit_data', array( $this, 'bb_activity_post_feature_image_get_edit_activity_data' ), 10, 1 );
		add_filter( 'posts_join', array( $this, 'bb_activity_post_feature_image_query_posts_join' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'bb_activity_post_feature_image_query_posts_where' ), 10, 2 );
		add_filter( 'rest_attachment_query', array( $this, 'bb_activity_post_feature_image_rest_attachment_query' ), 999 );
		add_filter( 'rest_prepare_attachment', array( $this, 'bb_activity_post_feature_image_rest_prepare_attachment' ), 999, 2 );
		add_filter( 'oembed_request_post_id', array( $this, 'bb_activity_post_feature_image_oembed_request_post_id' ), 999 );
		add_action( 'bp_rest_api_init', array( $this, 'bb_rest_api_init' ) );
		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_localize_js_strings' ), 11 );
		add_action( 'bp_activity_after_delete', array( $this, 'bb_activity_remove_feature_image' ), 10, 1 );

		// Set up orphan cleanup cron job.
		$this->bb_setup_orphan_cleanup_cron_job();
	}

	/**
	 * Enqueue CSS and JavaScript assets.
	 *
	 * @since 2.9.0
	 */
	public function bb_enqueue_assets() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		$version = bb_platform_pro()->version;

		$css_file = 'bb-activity-post-feature-image';

		if (
			function_exists( 'bb_is_readylaunch_enabled' ) &&
			bb_is_readylaunch_enabled() &&
			class_exists( 'BB_Readylaunch' ) &&
			bb_load_readylaunch()->bb_is_readylaunch_enabled_for_page()
		) {
			$css_file = 'bb-rl-activity-post-feature-image';
		}

		wp_enqueue_style(
			'bb-activity-post-feature-image',
			$this->bb_get_asset_url( 'css/' . $css_file . $rtl_css . $min . '.css' ),
			array(),
			$version
		);

		if ( ! wp_script_is( 'bp-media-dropzone' ) ) {
			wp_enqueue_script( 'bp-media-dropzone' );
		}

		// Prepare script dependencies.
		$script_dependencies = array( 'jquery', 'bp-nouveau-activity-post-form' );

		if ( ! wp_script_is( 'bb-cropper-js' ) ) {
			wp_enqueue_script( 'bb-cropper-js' );
		}
		if ( ! wp_style_is( 'bb-cropper-css' ) ) {
			wp_enqueue_style( 'bb-cropper-css' );
		}
		$script_dependencies[] = 'bb-cropper-js';

		wp_enqueue_script(
			'bb-activity-post-feature-image',
			$this->bb_get_asset_url( 'js/bb-activity-post-feature-image' . $min . '.js' ),
			$script_dependencies,
			$version,
			true
		);
	}

	/**
	 * Render feature image button in activity header.
	 *
	 * @since 2.9.0
	 */
	public function bb_render_feature_image_button() {
		if ( bb_pro_should_lock_features() ) {
			return;
		}

		$icon_class = 'bb-icon-l bb-icon-image';

		if (
			function_exists( 'bb_is_readylaunch_enabled' ) &&
			bb_is_readylaunch_enabled() &&
			class_exists( 'BB_Readylaunch' ) &&
			bb_load_readylaunch()->bb_is_readylaunch_enabled_for_page()
		) {
			$icon_class = 'bb-icons-rl-image';
		}

		?>
		<div class="bb-activity-post-feature-image-button <?php echo ! $this->bb_user_has_access_feature_image() ? esc_attr( 'bp-hide' ) : ''; ?>">
			<a href="#"
				id="bb-activity-post-feature-image-control"
				class="bb-activity-post-feature-image-ctrl"
				aria-label="<?php esc_attr_e( 'Set feature Image', 'buddyboss-pro' ); ?>">
				<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
			</a>
		</div>
		<?php
	}

	/**
	 * Prepare group for JS.
	 *
	 * @since 2.9.0
	 *
	 * @param array  $args Array of group data.
	 * @param object $item The group object.
	 *
	 * @return array
	 */
	public function bb_prepare_group_for_js( $args, $item ) {
		$allow_feature_image = $this->bb_can_user_upload_in_group(
			array(
				'user_id'  => bp_loggedin_user_id(),
				'object'   => 'group',
				'group_id' => $item->id,
			)
		);
		if ( $allow_feature_image ) {
			$is_admin = groups_is_user_admin( bp_loggedin_user_id(), $item->id );
			$is_mod   = groups_is_user_mod( bp_loggedin_user_id(), $item->id );
			if ( $is_admin || $is_mod ) {
				$args['allow_feature_image'] = 'enabled';
			}
		}

		return $args;
	}

	/**
	 * Add JS templates to template parts.
	 *
	 * @since 2.9.0
	 *
	 * @param array $templates Array of template parts.
	 *
	 * @return array
	 */
	public function bb_add_js_templates( $templates ) {
		$templates[] = 'parts/bb-activity-post-feature-image-form';

		return $templates;
	}

	/**
	 * Get a template directory path.
	 *
	 * @since 2.9.0
	 *
	 * @return string
	 */
	public function bb_get_template_path() {
		return bb_platform_pro()->platform_settings_dir . '/activity/post-feature-image/templates';
	}

	/**
	 * Remove specific trailing slash.
	 *
	 * @since 2.9.0
	 *
	 * @param string $redirect_url The redirect URL.
	 *
	 * @return string The modified redirect URL.
	 */
	public function bb_remove_specific_trailing_slash( $redirect_url ) {
		if (
			false !== strpos( $redirect_url, 'bb-activity-post-feature-image-preview' ) ||
			false !== strpos( $redirect_url, 'bb-attachment-feature-image-preview' )
		) {
			$redirect_url = untrailingslashit( $redirect_url );
		}

		return $redirect_url;
	}

	/**
	 * Check if activity supports feature images based on component and type.
	 *
	 * @since 2.9.0
	 *
	 * @param object $activity The activity object.
	 *
	 * @return bool True if activity supports feature images, false otherwise.
	 */
	public function bb_activity_supports_feature_image( $activity ) {
		if ( empty( $activity ) || ! is_object( $activity ) ) {
			return false;
		}

		$allowed_components   = array( 'groups', 'activity' );
		$is_component_allowed = isset( $activity->component ) && in_array( $activity->component, $allowed_components, true );

		/**
		 * Filter to allow/disallow feature images for specific activity components.
		 *
		 * @since 2.9.0
		 *
		 * @param bool   $is_component_allowed Whether the component allows feature images.
		 * @param string $component            The activity component.
		 * @param object $activity             The activity object.
		 */
		$is_component_allowed = apply_filters( 'bb_activity_post_feature_image_component_allowed', $is_component_allowed, $activity->component, $activity );

		if ( ! $is_component_allowed ) {
			return false;
		}

		$allowed_types   = array( 'activity_update' );
		$is_type_allowed = isset( $activity->type ) && in_array( $activity->type, $allowed_types, true );

		/**
		 * Filter to allow/disallow feature images for specific activity types.
		 *
		 * @since 2.9.0
		 *
		 * @param bool   $is_type_allowed Whether the activity type allows feature images.
		 * @param string $type            The activity type.
		 * @param object $activity        The activity object.
		 */
		$is_type_allowed = apply_filters( 'bb_activity_post_feature_image_type_allowed', $is_type_allowed, $activity->type, $activity );

		return $is_type_allowed;
	}

	/**
	 * Store activity feature image.
	 *
	 * @since 2.9.0
	 *
	 * @param BP_Activity_Activity $activity The activity object.
	 *
	 * @return bool
	 */
	public function bb_store_activity_feature_image( $activity ) {
		global $bp_activity_edit;

		if ( empty( $activity->id ) ) {
			return false;
		}

		if ( ! $this->bb_activity_supports_feature_image( $activity ) ) {
			return false;
		}

		$feature_image_nonce = ! empty( $_POST['bb_activity_post_feature_image_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_post_feature_image_nonce'] ) ) : '';
		$feature_image       = ! empty( $_POST['bb_activity_post_feature_image_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_post_feature_image_id'] ) ) : '';

		// Edit activity, delete feature image id and updated post then delete feature image id and meta for activity.
		if ( empty( $feature_image_nonce ) && empty( $feature_image ) ) {

			// Delete feature image id and meta for activity if empty feature image id in request.
			if (
				! empty( $activity->id ) &&
				(
					(
						$bp_activity_edit && isset( $_POST['edit'] )
					)
				)
			) {
				$old_feature_image_id = bp_activity_get_meta( $activity->id, '_bb_activity_post_feature_image', true );
				if ( ! empty( $old_feature_image_id ) ) {
					wp_delete_attachment( $old_feature_image_id );
					bp_activity_delete_meta( $activity->id, '_bb_activity_post_feature_image' );
				}
			}

			return false;
		}

		// Validate the feature image nonce.
		if ( ! empty( $feature_image_nonce ) && ! wp_verify_nonce( $feature_image_nonce, 'activity_post_feature_image_save' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'buddyboss-pro' ), esc_html__( 'Security Error', 'buddyboss-pro' ), array( 'response' => 403 ) );
		}

		if ( ! empty( $feature_image ) ) {
			$object  = ! empty( $activity->component ) ? sanitize_text_field( wp_unslash( $activity->component ) ) : '';
			$item_id = ! empty( $activity->item_id ) ? absint( $activity->item_id ) : 0;

			if (
				! $this->bb_user_has_access_feature_image(
					array(
						'object'   => 'groups' === $object ? 'group' : '',
						'group_id' => $item_id,
					)
				)
			) {
				return false;
			}

			$validate_attachment = $this->bb_validate_attachment_by_id( $feature_image, $activity->id );
			if ( ! empty( $validate_attachment ) && is_array( $validate_attachment ) ) {
				return false; // Validation failed, don't store.
			}

			$old_feature_image_id = bp_activity_get_meta( $activity->id, '_bb_activity_post_feature_image', true );
			if ( ! empty( $old_feature_image_id ) && $old_feature_image_id !== $feature_image ) {
				wp_delete_attachment( $old_feature_image_id );
			}

			bp_activity_update_meta( $activity->id, '_bb_activity_post_feature_image', $feature_image );
			update_post_meta( $feature_image, 'bb_activity_post_feature_image_saved', 1 );
			update_post_meta( $feature_image, 'bb_activity_id', $activity->id );
		}
	}

	/**
	 * Get edit activity data.
	 *
	 * @since 2.9.0
	 *
	 * @param array $activity Activity data.
	 *
	 * @return array Activity data.
	 */
	public function bb_activity_post_feature_image_get_edit_activity_data( $activity ) {
		if ( ! empty( $activity['id'] ) ) {
			$activity_obj = new BP_Activity_Activity( $activity['id'] );

			if ( ! $this->bb_activity_supports_feature_image( $activity_obj ) ) {
				return $activity;
			}

			$feature_image_data = array();
			$group_id           = ! empty( $activity['group_id'] ) ? $activity['group_id'] : 0;
			$edit_args          = array(
				'action'      => 'edit',
				'object'      => 'groups' === $activity['object'] ? 'group' : '',
				'group_id'    => $group_id,
				'activity_id' => $activity['id'],
			);

			$feature_image_attachment_data = $this->bb_get_feature_image_data( $activity['id'] );
			if ( ! empty( $feature_image_attachment_data ) ) {
				$feature_image_data = $this->bb_get_feature_image_data( $activity['id'] );
				if ( ! empty( $feature_image_data ) && ! empty( $feature_image_data['id'] ) ) {
					$edit_args['attachment_id'] = $feature_image_data['id'];

					$can_delete = $this->bb_user_can_perform_feature_image_action(
						array(
							'action'        => 'delete',
							'attachment_id' => $feature_image_data['id'],
							'activity_id'   => $activity['id'],
						)
					);

					$feature_image_data['can_delete_feature_image'] = isset( $can_delete['can_delete'] ) ? $can_delete['can_delete'] : false;
				}
			}

			$can_edit                                     = $this->bb_user_can_perform_feature_image_action( $edit_args );
			$feature_image_data['can_edit_feature_image'] = isset( $can_edit['can_edit'] ) ? $can_edit['can_edit'] : false;
			$activity['bb_activity_post_feature_image']   = $feature_image_data;
		}

		return $activity;
	}

	/**
	 * Filter attachments query posts join SQL to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param string   $join     The JOIN clause of the query.
	 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return string Modified join statement.
	 */
	public function bb_activity_post_feature_image_query_posts_join( $join, $wp_query ) {
		global $wpdb;

		if ( isset( $wp_query->query_vars['post_type'] ) && 'attachment' === $wp_query->query_vars['post_type'] ) {
			$join .= " LEFT JOIN {$wpdb->postmeta} AS bb_fi_mt ON ({$wpdb->posts}.ID = bb_fi_mt.post_id AND bb_fi_mt.meta_key = 'bb_activity_post_feature_image_upload')";
		}

		return $join;
	}

	/**
	 * Filter attachments query posts where SQL to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param string   $where    The WHERE clause of the query.
	 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return string The modified WHERE clause with feature image exclusion logic.
	 */
	public function bb_activity_post_feature_image_query_posts_where( $where, $wp_query ) {
		global $wpdb;

		if ( isset( $wp_query->query_vars['post_type'] ) && 'attachment' === $wp_query->query_vars['post_type'] ) {
			$where .= " AND ( bb_fi_mt.post_id IS NULL OR {$wpdb->posts}.post_parent != 0 )";
		}

		return $where;
	}

	/**
	 * Filter REST attachment query to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array Modified query arguments.
	 */
	public function bb_activity_post_feature_image_rest_attachment_query( $args ) {
		$meta_query = ( array_key_exists( 'meta_query', $args ) ? $args['meta_query'] : array() );

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['meta_query'] = array(
			array(
				'key'     => 'bb_activity_post_feature_image_upload',
				'compare' => 'NOT EXISTS',
			),
		);

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'][] = $meta_query;
		}

		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		return $args;
	}

	/**
	 * Filter REST attachment prepares to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     The original attachment post.
	 *
	 * @return WP_REST_Response
	 */
	public function bb_activity_post_feature_image_rest_prepare_attachment( $response, $post ) {
		$activity_post_feature_image_meta = get_post_meta( $post->ID, 'bb_activity_post_feature_image_upload', true );
		if ( empty( $activity_post_feature_image_meta ) ) {
			return $response;
		}

		$data = $response->get_data();
		if (
			array_key_exists( 'media_type', $data ) &&
			(
				! is_user_logged_in()
				|| ! current_user_can( 'edit_post', $post->ID )
			)
		) {
			$response = array();
		}

		return $response;
	}

	/**
	 * Filter oembed request post ID to hide feature images from Media Library.
	 *
	 * @since 2.9.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int Modified post ID.
	 */
	public function bb_activity_post_feature_image_oembed_request_post_id( $post_id ) {
		// Only process if post_id is valid and greater than 0
		if ( empty( $post_id ) || $post_id <= 0 ) {
			return $post_id;
		}

		$activity_post_feature_image_meta = get_post_meta( $post_id, 'bb_activity_post_feature_image_upload', true );

		// If this post doesn't have the feature image meta, return original post_id (don't interfere)
		if ( empty( $activity_post_feature_image_meta ) ) {
			return $post_id;
		}

		// If post has feature image meta but user doesn't have permission, return 0 to hide it
		if (
			! is_user_logged_in()
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return 0;
		}

		// User has permission, return original post_id
		return $post_id;
	}

	/**
	 * Initialize REST API endpoints.
	 *
	 * @since 2.9.0
	 */
	public function bb_rest_api_init() {
		if ( ! empty( $this->rest_endpoint_handler ) ) {
			$this->rest_endpoint_handler->register_routes();
		}
	}

	/**
	 * Localize JavaScript strings.
	 *
	 * @since 2.9.0
	 *
	 * @param array $params Array of localized strings.
	 *
	 * @return array
	 */
	public function bb_localize_js_strings( $params ) {
		if ( empty( $params['activity']['params'] ) ) {
			return $params;
		}
		$feature_image_params = array(
			'can_upload_post_feature_image' => $this->bb_user_has_access_feature_image(),
			'nonce'                         => array(
				'save'         => wp_create_nonce( 'activity_post_feature_image_save' ),
				'upload'       => wp_create_nonce( 'activity_post_feature_image_upload' ),
				'delete'       => wp_create_nonce( 'activity_post_feature_image_delete' ),
				'crop_replace' => wp_create_nonce( 'activity_post_feature_image_crop_replace' ),
			),
			'config'                        => array_merge(
				$this->config,
				array(
					'max_upload_size' => $this->bb_get_max_upload_size(),
					'max_file'        => 1,
				)
			),
			'strings'                       => array(
				'upload_failed'                => __( 'Upload failed', 'buddyboss-pro' ),
				'reposition_crop'              => __( 'Reposition photo', 'buddyboss-pro' ),
				'reposition_crop_image'        => __( 'Reposition and Crop Image', 'buddyboss-pro' ),
				'invalid_media_type'           => __( 'Unable to upload the file', 'buddyboss-pro' ),
				'connection_lost_error'        => __( 'Connection lost with the server.', 'buddyboss-pro' ),
				'crop_operation_failed'        => __( 'Crop operation failed:', 'buddyboss-pro' ),
				'failed_to_save_cropped_image' => __( 'Failed to save cropped image. Please try again.', 'buddyboss-pro' ),
				'error_dc'                     => __( 'Error destroying cropper:', 'buddyboss-pro' ),
				'error_dc_during_cleanup'      => __( 'Error destroying cropper during cleanup:', 'buddyboss-pro' ),
				'error_destroying_dropzone'    => __( 'Error destroying dropzone:', 'buddyboss-pro' ),
				'invalid_thumbnail_url'        => __( 'Invalid thumbnail URL, skipping thumbnail creation:', 'buddyboss-pro' ),
			),
		);

		if ( bp_is_active( 'groups' ) && bp_is_group() ) {
			$feature_image_params['group_id'] = bp_get_current_group_id();
		}

		$params['activity']['params']['post_feature_image'] = $feature_image_params;

		return $params;
	}

	/**
	 * Remove feature image from activity.
	 *
	 * @since 2.9.0
	 *
	 * @param array $activities Array of activity objects.
	 */
	public function bb_activity_remove_feature_image( $activities ) {
		$activity_ids = wp_parse_id_list( wp_list_pluck( $activities, 'id' ) );

		if ( empty( $activity_ids ) ) {
			return;
		}

		foreach ( $activity_ids as $activity_id ) {
			$feature_image_data = $this->bb_get_feature_image_data( $activity_id );
			if ( ! empty( $feature_image_data ) && ! empty( $feature_image_data['id'] ) ) {
				wp_delete_attachment( (int) $feature_image_data['id'], true );
			}
		}
	}

	/**
	 * Set up orphan cleanup cron job.
	 *
	 * @since 2.9.0
	 */
	public function bb_setup_orphan_cleanup_cron_job() {
		if ( ! wp_next_scheduled( 'bb_activity_post_feature_image_delete_orphaned_attachments_hook' ) ) {
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'bb_activity_post_feature_image_delete_orphaned_attachments_hook' );
		}

		add_action( 'bb_activity_post_feature_image_delete_orphaned_attachments_hook', array( $this, 'bb_delete_orphaned_feature_image_attachments' ) );
	}

	/**
	 * Delete orphaned feature image attachments.
	 *
	 * @since 2.9.0
	 */
	public function bb_delete_orphaned_feature_image_attachments() {
		global $wpdb;
		$six_hours_ago_timestamp = strtotime( '-6 hours', time() );
		$six_hours_ago           = gmdate( 'Y-m-d H:i:s', $six_hours_ago_timestamp );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$media_wp_query_posts = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT DISTINCT p.ID
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} mt_saved ON ( p.ID = mt_saved.post_id AND mt_saved.meta_key = %s AND mt_saved.meta_value = %s )
					LEFT JOIN {$wpdb->postmeta} mt_draft ON ( p.ID = mt_draft.post_id AND mt_draft.meta_key = %s )
					WHERE p.post_type = %s 
						AND p.post_status = %s 
						AND p.post_date_gmt < %s
						AND ( mt_draft.post_id IS NULL OR mt_draft.meta_value = %s )
					ORDER BY p.post_date DESC
					LIMIT 100
			",
				'bb_activity_post_feature_image_saved',
				'0',
				'bb_activity_post_feature_image_draft',
				'attachment',
				'inherit',
				$six_hours_ago,
				'1'
			)
		);

		if ( ! empty( $media_wp_query_posts ) ) {
			foreach ( $media_wp_query_posts as $post_id ) {
				wp_delete_attachment( $post_id, true );
			}
		}
	}

	/**
	 * Get upload handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @return BB_Activity_Post_Feature_Image_Upload
	 */
	public function get_upload_handler() {
		return $this->upload_handler;
	}

	/**
	 * Get attachment preview handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @return BB_Activity_Post_Feature_Image_Attachment_Preview
	 */
	public function get_attachment_preview_handler() {
		return $this->attachment_preview_handler;
	}

	/**
	 * Get activity preview handler instance.
	 *
	 * @since 2.9.0
	 *
	 * @return BB_Activity_Post_Feature_Image_Preview
	 */
	public function get_activity_preview_handler() {
		return $this->activity_preview_handler;
	}

	/**
	 * Get configuration.
	 *
	 * @since 2.9.0
	 *
	 * @param string|null $key Configuration key.
	 *
	 * @return array|mixed|null
	 */
	public function bb_get_config( $key = null ) {
		if ( $key ) {
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : null;
		}

		return $this->config;
	}

	/**
	 * Update configuration.
	 *
	 * @since 2.9.0
	 *
	 * @param string $key   Configuration key.
	 * @param mixed  $value Configuration value.
	 */
	public function bb_update_config( $key, $value ) {
		$this->config[ $key ] = $value;
	}

	/**
	 * Get feature image size configurations.
	 *
	 * @since 2.9.0
	 *
	 * @return array {
	 *     Array of image size configurations.
	 *
	 * @type array $size_name {
	 *                        Image size configuration.
	 *
	 * @type int   $width     Image width in pixels.
	 * @type int   $height    Image height in pixels.
	 * @type bool  $crop      Whether to crop the image to exact dimensions.
	 *                        }
	 *                        }
	 */
	public function bb_get_image_sizes() {

		/**
		 * Filters the image sizes for activity post feature image.
		 *
		 * @since 2.9.0
		 *
		 * @param array $image_sizes Array of image size configurations.
		 */
		return (array) apply_filters( 'bb_activity_post_feature_image_add_image_sizes', $this->config['image_sizes'] );
	}

	/**
	 * Get allowed MIME types for feature image uploads.
	 *
	 * @since 2.9.0
	 *
	 * @return array Array of allowed MIME types for feature image uploads.
	 */
	public function bb_get_allowed_mimes() {

		/**
		 * Filters the allowed MIME types for feature image uploads.
		 *
		 * @since 2.9.0
		 *
		 * @param array $allowed_mimes Array of allowed MIME types.
		 */
		return apply_filters( 'bb_activity_post_feature_image_allowed_mimes', $this->config['allowed_mimes'] );
	}

	/**
	 * Validate attachment hash.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $hash          Hash to validate.
	 * @param int    $activity_id   Activity ID.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function bb_validate_attachment_hash( $attachment_id, $hash, $activity_id = 0 ) {
		if ( empty( $attachment_id ) || empty( $hash ) ) {
			return false;
		}

		$decode_activity_id    = ! empty( $activity_id ) ? base64_decode( $activity_id ) : '';
		$decode_attachment_id  = ! empty( $attachment_id ) ? base64_decode( $attachment_id ) : '';
		$explode_activity_id   = explode( 'forbidden_', $decode_activity_id );
		$explode_attachment_id = explode( 'forbidden_', $decode_attachment_id );
		$activity_id           = ! empty( $explode_activity_id[1] ) ? $explode_activity_id[1] : 0;
		$attachment_id         = ! empty( $explode_attachment_id[1] ) ? $explode_attachment_id[1] : 0;

		$expected_hash = $this->bb_generate_attachment_hash( $attachment_id, $activity_id );

		return ! empty( $expected_hash ) && ( function_exists( 'hash_equals' ) ? hash_equals( $expected_hash, $hash ) : $expected_hash === $hash );
	}

	/**
	 * Generate secure hash for attachment preview.
	 *
	 * @since 2.9.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $activity_id   Activity ID.
	 *
	 * @return string Secure hash.
	 */
	public function bb_generate_attachment_hash( $attachment_id, $activity_id = 0 ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return '';
		}

		$data_parts = array(
			'bb_attachment_feature_image_preview', // Action identifier prefix.
			$attachment_id,
			$attachment->post_date,
			wp_salt( 'auth' ),
			wp_salt( 'secure_auth' ),
		);

		if ( ! empty( $attachment_id ) && ! empty( $activity_id ) ) {
			$data_parts[] = 'bb_activity_post_feature_image_preview';
			$data_parts[] = $activity_id;
		}

		$data = implode( '|', $data_parts );

		return wp_hash( $data );
	}

	/**
	 * Validate attachment by attachment ID.
	 *
	 * @since 2.9.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $activity_id   Activity ID.
	 *
	 * @return bool|array True if attachment is valid, array of error data if invalid.
	 */
	public function bb_validate_attachment_by_id( $attachment_id, $activity_id = 0 ) {
		$attachment_post = get_post( $attachment_id );
		if ( empty( $attachment_post ) || 'attachment' !== $attachment_post->post_type ) {
			return $this->create_error_response(
				'bb_rest_activity_feature_image_invalid_attachment',
				__( 'Invalid attachment.', 'buddyboss-pro' ),
				400
			);
		}

		if ( empty( $activity_id ) && bp_loggedin_user_id() !== (int) $attachment_post->post_author ) {
			return $this->create_error_response(
				'bb_rest_no_access',
				__( 'You are not allowed to use this attachment.', 'buddyboss-pro' ),
				403
			);
		}

		if ( ! empty( $activity_id ) ) {
			$attachment_meta = get_post_meta( $attachment_id, 'bb_activity_post_feature_image_upload', true );
			if ( empty( $attachment_meta ) ) {
				return $this->create_error_response(
					'bb_rest_activity_feature_image_invalid_attachment_id',
					__( 'Please select a valid attachment id for feature image.', 'buddyboss-pro' ),
					400
				);
			}
		}
		return true;
	}

	/**
	 * Get asset URL.
	 *
	 * @since 2.9.0
	 *
	 * @param string $path Asset path.
	 *
	 * @return string
	 */
	private function bb_get_asset_url( $path ) {
		return bb_platform_pro()->platform_settings_url . '/activity/post-feature-image/assets/' . $path;
	}

	/**
	 * Get maximum upload size for feature images.
	 *
	 * @since 2.9.0
	 *
	 * @return int Maximum upload size in bytes.
	 */
	public function bb_get_max_upload_size() {

		$max_size        = bp_core_upload_max_size();
		$max_upload_size = $this->bb_format_size_units( $max_size, false, 'MB' );

		/**
		 * Filters the maximum upload size for feature images.
		 *
		 * @since 2.9.0
		 *
		 * @param int $max_upload_size Maximum upload size in bytes.
		 */
		return apply_filters( 'bb_activity_post_feature_image_max_upload_size', $max_upload_size );
	}

	/**
	 * Format file size units.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $bytes       Bytes.
	 * @param bool   $post_string Post string.
	 * @param string $type        Type.
	 *
	 * @return string
	 */
	public function bb_format_size_units( $bytes, $post_string = false, $type = 'bytes' ) {

		if ( $bytes > 0 ) {
			if ( 'GB' === $type && ! $post_string ) {
				return $bytes / 1073741824;
			} elseif ( 'MB' === $type && ! $post_string ) {
				return $bytes / 1048576;
			} elseif ( 'KB' === $type && ! $post_string ) {
				return $bytes / 1024;
			}
		}

		if ( $bytes >= 1073741824 ) {
			$bytes = ( $bytes / 1073741824 ) . ( $post_string ? ' GB' : '' );
		} elseif ( $bytes >= 1048576 ) {
			$bytes = ( $bytes / 1048576 ) . ( $post_string ? ' MB' : '' );
		} elseif ( $bytes >= 1024 ) {
			$bytes = ( $bytes / 1024 ) . ( $post_string ? ' KB' : '' );
		} elseif ( $bytes > 1 ) {
			$bytes = $bytes . ( $post_string ? ' bytes' : '' );
		} elseif ( 1 === $bytes ) {
			$bytes = $bytes . ( $post_string ? ' byte' : '' );
		} else {
			$bytes = '0' . ( $post_string ? ' bytes' : '' );
		}

		return $bytes;
	}

	/**
	 * Regenerate attachment thumbnails for feature images.
	 *
	 * @since 2.9.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function bb_regenerate_attachment_thumbnails( $attachment_id ) {
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
	}

	/**
	 * Check whether a user can upload feature image for activity or not.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args     {
	 *                        Optional. Array of arguments to check permissions.
	 *
	 * @type int    $user_id  User ID to check permissions for. Default: current user ID.
	 * @type string $object   Object type to check permissions for. Default: empty string.
	 * @type int    $group_id Group ID for group-specific permissions. Default: 0.
	 *                        }
	 *
	 * @return bool True if a user can upload feature images, false otherwise.
	 */
	public function bb_user_has_access_feature_image( $args = array() ) {
		if ( ! $this->bb_check_dependency() ) {
			return false;
		}

		$r = bp_parse_args(
			$args,
			array(
				'user_id'  => bp_loggedin_user_id(),
				'object'   => '',
				'group_id' => 0,
			)
		);

		$retval = false;

		if ( $this->bb_is_groups_context( $r ) ) {
			$retval = $this->bb_can_user_upload_in_group( $r );
		} elseif ( bp_user_can( $r['user_id'], 'administrator' ) ) {
			$retval = true;
		}

		/**
		 * Filters whether user can upload feature image for activity.
		 *
		 * @since 2.9.0
		 *
		 * @param bool  $retval Return value for feature image.
		 * @param array $r      Array of arguments.
		 */
		return apply_filters( 'bb_can_user_upload_post_feature_image', $retval, $r );
	}

	/**
	 * Function to check an activity post feature image allow or not based on required dependencies.
	 *
	 * @since 2.9.0
	 *
	 * @return bool
	 */
	public function bb_check_dependency() {
		if (
			! defined( 'BP_PLATFORM_VERSION' ) ||
			version_compare( BP_PLATFORM_VERSION, bb_platform_activity_post_feature_image_version(), '<' ) ||
			! bp_is_active( 'activity' ) ||
			! is_user_logged_in()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if we're in a group context.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Parsed arguments.
	 *
	 * @return bool True if in groups context, false otherwise.
	 */
	private function bb_is_groups_context( $args ) {
		return bp_is_active( 'groups' ) &&
				(
					'group' === $args['object'] ||
					bp_is_group()
				);
	}

	/**
	 * Check if a user can upload feature image in group context.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Parsed arguments.
	 *
	 * @return bool True if a user can upload in a group, false otherwise.
	 */
	private function bb_can_user_upload_in_group( $args ) {
		if ( ! $this->bb_is_feature_enabled( false ) ) {
			return false;
		}

		$group_id = $this->bb_get_group_id_from_args( $args );

		if ( empty( $group_id ) ) {
			return false;
		}

		return groups_is_user_admin( $args['user_id'], $group_id ) || groups_is_user_mod( $args['user_id'], $group_id );
	}

	/**
	 * Checks if activity post feature image is enabled.
	 *
	 * @since 2.9.0
	 *
	 * @param bool $retval Optional. Fallback value if not found in the database.
	 *                     Default: false.
	 *
	 * @return bool Is activity post feature image enabled or not
	 */
	public function bb_is_feature_enabled( $retval = false ) {

		/**
		 * Filters whether activity post feature image is enabled or not.
		 *
		 * @since 2.9.0
		 *
		 * @param bool $value Whether or not the activity post feature image is enabled or not.
		 */
		return (bool) apply_filters( 'bb_activity_post_feature_image_enable', bp_get_option( $this->config['option_key'], $retval ) );
	}

	/**
	 * Get group ID from arguments.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Parsed arguments.
	 *
	 * @return int Group ID.
	 */
	private function bb_get_group_id_from_args( $args ) {
		if ( 'group' === $args['object'] && ! empty( $args['group_id'] ) ) {
			return $args['group_id'];
		}

		return bp_get_current_group_id();
	}

	/**
	 * Check if the feature is enabled.
	 *
	 * @since 2.9.0
	 *
	 * @param bool $retval Optional. Default value. Default: false.
	 *
	 * @return bool
	 */
	public function bb_is_enabled( $retval = false ) {
		/**
		 * Filter to enable/disable activity post feature image.
		 *
		 * @since 2.9.0
		 *
		 * @param bool $retval Optional. Default value. Default: false.
		 */
		return (bool) apply_filters( 'bb_activity_post_feature_image_enable', bp_get_option( $this->config['option_key'], $retval ) );
	}

	/**
	 * Get feature image data.
	 *
	 * @since 2.9.0
	 *
	 * @param int $activity_id Activity ID.
	 *
	 * @return array Feature image data.
	 */
	public function bb_get_feature_image_data( $activity_id ) {
		$attachment_id = bp_activity_get_meta( $activity_id, '_bb_activity_post_feature_image' );
		if ( empty( $attachment_id ) ) {
			return array();
		}

		// Get full attachment post data.
		$attachment_post = get_post( $attachment_id );
		if ( empty( $attachment_post ) || 'attachment' !== $attachment_post->post_type ) {
			return array();
		}

		$feature_image_url = $this->bb_get_preview_image_url( $activity_id, $attachment_id );
		$thumbnail_url     = $this->bb_get_preview_image_url( $activity_id, $attachment_id, 'thumbnail' );
		$medium_url        = $this->bb_get_preview_image_url( $activity_id, $attachment_id, 'medium' );

		// Check if the image has been cropped.
		$is_cropped = (bool) get_post_meta( $attachment_id, 'bb_activity_post_feature_image_cropped', true );

		$return_attachment_data = array(
			'id'       => $attachment_id,
			'title'    => $attachment_post->post_title,
			'url'      => $feature_image_url,
			'position' => 'center', // Position functionality removed.
			'thumb'    => $thumbnail_url,
			'medium'   => $medium_url,
			'cropped'  => $is_cropped,
		);

		/**
		 * Filters the feature image data.
		 *
		 * @since 2.9.0
		 *
		 * @param array $data Feature image data.
		 * @param int   $attachment_id Attachment ID.
		 * @param int   $activity_id   Activity ID.
		 */
		return apply_filters( 'bb_activity_post_feature_image_data', $return_attachment_data, $attachment_id, $activity_id );
	}

	/**
	 * Get preview image URL.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $activity_id   Activity ID.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 * @param bool   $generate      Whether to generate the image.
	 *
	 * @return string Preview image URL.
	 */
	public function bb_get_preview_image_url( $activity_id, $attachment_id, $size = 'bb-activity-post-feature-image', $generate = true ) {
		$attachment_url = '';
		$attachment     = get_post( $attachment_id );
		if ( ! empty( $attachment ) ) {
			$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
		}
		$hash           = $this->bb_generate_attachment_hash( $attachment_id, $activity_id );
		$attachment_url = ! empty( $attachment_url ) ? home_url( '/' ) . 'bb-activity-post-feature-image-preview/' . base64_encode( 'forbidden_' . $activity_id ) . '/' . base64_encode( 'forbidden_' . $attachment_id ) . '/' . $hash . '/' . $size : '';

		/**
		 * Filters the preview image URL.
		 *
		 * @since 2.9.0
		 *
		 * @param string $attachment_url Preview image URL.
		 * @param int    $activity_id   Activity ID.
		 * @param int    $attachment_id Attachment ID.
		 * @param string $size          Image size.
		 * @param bool   $generate      Whether to generate the image.
		 */
		$attachment_url = apply_filters( 'bb_activity_post_feature_image_preview_image_url', $attachment_url, $activity_id, $attachment_id, $size, $generate );

		return $attachment_url;
	}

	/**
	 * Create error responses.
	 *
	 * @since 2.9.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return array Error response array.
	 */
	public function create_error_response( $code, $message, $status = 400 ) {
		return array(
			'code'    => $code,
			'message' => $message,
			'status'  => $status,
		);
	}

	/**
	 * Get activity ID by attachment ID.
	 *
	 * @since 2.9.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return int Activity ID. Returns 0 if not found or invalid input.
	 */
	public function bb_get_activity_id_by_attachment_id( $attachment_id ) {
		// Validate input parameter.
		if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
			return 0;
		}

		$attachment_id = absint( $attachment_id );
		$activity_id   = get_post_meta( $attachment_id, 'bb_activity_id', true );

		// Return 0 if no activity ID found or if it's not a valid integer.
		if ( empty( $activity_id ) || ! is_numeric( $activity_id ) ) {
			return 0;
		}

		return absint( $activity_id );
	}

	/**
	 * Check if the user can perform a feature image action.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return array|WP_Error True if can perform, false otherwise.
	 */
	public function bb_user_can_perform_feature_image_action( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'action'        => 'edit',
				'attachment_id' => 0,
				'activity_id'   => 0,
				'user_id'       => bp_loggedin_user_id(),
				'group_id'      => 0,
			)
		);

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return $this->create_error_response(
				'bb_rest_authorization_required',
				__( 'Sorry, you are not allowed to perform this action.', 'buddyboss-pro' ),
				rest_authorization_required_code()
			);
		}

		$action        = $r['action'];
		$user_id       = (int) $r['user_id'];
		$attachment_id = (int) $r['attachment_id'];
		$activity_id   = (int) $r['activity_id'];
		$group_id      = (int) $r['group_id'];

		// Get activity ID if not provided.
		if ( empty( $activity_id ) && ! empty( $attachment_id ) ) {
			$activity_id = $this->bb_get_activity_id_by_attachment_id( $attachment_id );
		}

		// Validate basic inputs.
		$validation_error = $this->bb_validate_permission_inputs( $attachment_id, $activity_id );
		if ( $validation_error ) {
			return $validation_error;
		}

		// Check permissions based on context.
		if ( ! empty( $group_id ) ) {
			return $this->bb_check_group_permissions( $group_id, $user_id, $action );
		}

		if ( ! empty( $activity_id ) ) {
			return $this->bb_check_activity_permissions( $activity_id, $user_id, $action );
		}

		if ( ! empty( $attachment_id ) ) {
			return $this->bb_check_attachment_permissions( $attachment_id, $user_id, $action );
		}

		return $this->create_error_response(
			'bb_rest_invalid_request',
			__( 'Invalid request: No valid attachment, activity, or group context provided.', 'buddyboss-pro' ),
			400
		);
	}

	/**
	 * Validate permission check inputs.
	 *
	 * @since 2.9.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $activity_id   Activity ID.
	 *
	 * @return array|false Error response or false if valid.
	 */
	private function bb_validate_permission_inputs( $attachment_id, $activity_id ) {

		if ( empty( $attachment_id ) && empty( $activity_id ) ) {
			return $this->create_error_response(
				'bb_rest_activity_post_feature_image_attachment_invalid_id',
				__( 'Invalid attachment ID.', 'buddyboss-pro' ),
				400
			);
		}

		// If we have an attachment, validate it.
		if ( ! empty( $attachment_id ) ) {
			$validate_attachment = $this->bb_validate_attachment_by_id( $attachment_id, $activity_id );
			if ( ! empty( $validate_attachment ) && is_array( $validate_attachment ) ) {
				return $this->create_error_response(
					$validate_attachment['code'],
					$validate_attachment['message'],
					$validate_attachment['status']
				);
			}
		}

		// If we have an activity, validate it.
		if ( ! empty( $activity_id ) ) {
			$activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $activity ) || ! is_object( $activity ) || empty( (int) $activity->id ) ) {
				return $this->create_error_response(
					'bb_rest_invalid_activity_id',
					__( 'Invalid activity ID.', 'buddyboss-pro' ),
					404
				);
			}
		}

		return false; // No validation errors.
	}

	/**
	 * Check group-based permissions.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $group_id Group ID.
	 * @param int    $user_id  User ID.
	 * @param string $action   Action being performed.
	 *
	 * @return array|WP_Error Permission result.
	 */
	private function bb_check_group_permissions( $group_id, $user_id, $action ) {
		$group = groups_get_group( $group_id );
		if ( ! $group ) {
			return $this->create_error_response(
				'bp_rest_activity_post_feature_image_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss-pro' ),
				404
			);
		}

		// Check if user has access to feature images in this group.
		if ( ! $this->bb_user_has_access_feature_image(
			array(
				'user_id'  => $user_id,
				'object'   => 'group',
				'group_id' => $group_id,
			)
		) ) {
			$error_message = sprintf(
			/* translators: %s: action */
				__( 'Sorry, you are not allowed to %s this feature image.', 'buddyboss-pro' ),
				$action
			);

			return $this->create_error_response(
				'bb_rest_no_access',
				$error_message,
				403
			);
		}

		return array( 'can_' . $action => true );
	}

	/**
	 * Check activity-based permissions.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $activity_id Activity ID.
	 * @param int    $user_id     User ID.
	 * @param string $action      Action being performed.
	 *
	 * @return array|WP_Error Permission result.
	 */
	private function bb_check_activity_permissions( $activity_id, $user_id, $action ) {
		$activity = new BP_Activity_Activity( $activity_id );

		// Check user activity permissions.
		if ( 'activity' === $activity->component ) {
			if ( $user_id !== (int) $activity->user_id ) {
				$error_message = sprintf(
				/* translators: %s: action */
					__( 'Sorry, you are not allowed to %s this feature image.', 'buddyboss-pro' ),
					$action
				);

				return $this->create_error_response(
					'bb_rest_no_access',
					$error_message,
					403
				);
			}

			return array( 'can_' . $action => true );
		}

		// Check group activity permissions.
		if ( 'groups' === $activity->component ) {
			return $this->bb_check_group_activity_permissions( $activity, $user_id, $action );
		}

		return array( 'can_' . $action => true );
	}

	/**
	 * Check group activity permissions.
	 *
	 * @since 2.9.0
	 *
	 * @param object $activity Activity object.
	 * @param int    $user_id  User ID.
	 * @param string $action   Action being performed.
	 *
	 * @return array|WP_Error Permission result.
	 */
	private function bb_check_group_activity_permissions( $activity, $user_id, $action ) {
		$group_id = $activity->item_id;
		$group    = groups_get_group( $group_id );

		if ( ! $group ) {
			return $this->create_error_response(
				'bp_rest_activity_post_feature_image_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss-pro' ),
				404
			);
		}

		// Check edit permissions.
		if ( 'edit' === $action ) {
			if ( ! $this->bb_user_has_access_feature_image(
				array(
					'user_id'  => $user_id,
					'object'   => 'group',
					'group_id' => $group_id,
				)
			) ) {
				return $this->create_error_response(
					'bb_rest_no_access',
					__( 'Sorry, you are not allowed to edit this feature image.', 'buddyboss-pro' ),
					403
				);
			}
		}

		// Check delete permissions.
		if ( 'delete' === $action ) {
			$can_delete = $this->bb_can_delete_group_activity_feature_image( $activity, $user_id, $group_id );
			if ( ! $can_delete ) {
				return $this->create_error_response(
					'bb_rest_no_access',
					__( 'Sorry, you are not allowed to delete this feature image.', 'buddyboss-pro' ),
					403
				);
			}
		}

		return array( 'can_' . $action => true );
	}

	/**
	 * Check if user can delete feature image from group activity.
	 *
	 * @since 2.9.0
	 *
	 * @param object $activity Activity object.
	 * @param int    $user_id  User ID.
	 * @param int    $group_id Group ID.
	 *
	 * @return bool True if can delete, false otherwise.
	 */
	private function bb_can_delete_group_activity_feature_image( $activity, $user_id, $group_id ) {
		// Activity creator can always delete.
		if ( $user_id === (int) $activity->user_id ) {
			return true;
		}

		// Site administrators can delete.
		if ( bp_current_user_can( 'administrator' ) ) {
			return true;
		}

		// Group administrators can delete.
		if ( groups_is_user_admin( bp_loggedin_user_id(), $group_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check attachment-based permissions.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param int    $user_id       User ID.
	 * @param string $action        Action being performed.
	 *
	 * @return array|WP_Error Permission result.
	 */
	private function bb_check_attachment_permissions( $attachment_id, $user_id, $action ) {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return $this->create_error_response(
				'bb_rest_activity_post_feature_image_attachment_invalid_id',
				__( 'Invalid attachment ID.', 'buddyboss-pro' ),
				404
			);
		}

		// Check if user is the attachment author.
		if ( $user_id !== (int) $attachment->post_author ) {
			$error_message = sprintf(
			/* translators: %s: action */
				__( 'Sorry, you are not allowed to %s this feature image.', 'buddyboss-pro' ),
				$action
			);

			return $this->create_error_response(
				'bb_rest_no_access',
				$error_message,
				403
			);
		}

		return array( 'can_' . $action => true );
	}
}
