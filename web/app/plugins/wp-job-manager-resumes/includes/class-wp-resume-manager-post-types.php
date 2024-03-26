<?php
/**
 * File containing the WP_Resume_Manager_Post_Types.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Post_Types class.
 */
class WP_Resume_Manager_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ], 0 );
		add_action( 'init', [ $this, 'register_meta_fields' ] );
	}

	/**
	 * Sets up actions related to the resume post type.
	 */
	public function init_post_types() {
		add_action( 'wp', [ $this, 'download_resume_handler' ] );
		add_action( 'wp', [ $this, 'maybe_add_yoast_filters' ] );
		add_filter( 'admin_head', [ $this, 'admin_head' ] );
		add_filter( 'the_title', [ $this, 'resume_title' ], 10, 2 );
		add_filter( 'single_post_title', [ $this, 'resume_title' ], 10, 2 );
		add_filter( 'the_content', [ $this, 'resume_content' ] );
		if ( resume_manager_discourage_resume_search_indexing() ) {
			add_action( 'wp_head', [ $this, 'add_no_robots' ], 0 );
		}

		add_filter( 'the_resume_description', 'wptexturize' );
		add_filter( 'the_resume_description', 'convert_smilies' );
		add_filter( 'the_resume_description', 'convert_chars' );
		add_filter( 'the_resume_description', 'wpautop' );
		add_filter( 'the_resume_description', 'shortcode_unautop' );
		add_filter( 'the_resume_description', 'prepend_attachment' );

		// Allow for oEmbeds to work on Resume content.
		if ( ! empty( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'the_resume_description', [ $GLOBALS['wp_embed'], 'run_shortcode' ], 8 );
			add_filter( 'the_resume_description', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
		}

		add_action( 'resume_manager_contact_details', [ $this, 'contact_details_email' ] );

		add_action( 'pending_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'preview_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'draft_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'auto-draft_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'hidden_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'expired_to_publish', [ $this, 'setup_autohide_cron' ] );
		add_action( 'save_post', [ $this, 'setup_autohide_cron' ] );
		add_action( 'auto-hide-resume', [ $this, 'hide_resume' ] );

		add_action( 'update_post_meta', [ $this, 'maybe_update_menu_order' ], 10, 4 );
		add_filter( 'wp_insert_post_data', [ $this, 'fix_post_name' ], 10, 2 );
		add_action( 'pending_payment_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'pending_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'preview_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'auto-draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'expired_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'resume_manager_check_for_expired_resumes', [ $this, 'check_for_expired_resumes' ] );

		add_action( 'save_post', [ $this, 'flush_get_resume_listings_cache' ] );
		add_action( 'delete_post', [ $this, 'flush_get_resume_listings_cache' ] );
		add_action( 'trash_post', [ $this, 'flush_get_resume_listings_cache' ] );

		add_action( 'save_post_resume', [ $this, 'save_postmeta' ] );

		add_action( 'resume_manager_my_resume_do_action', [ $this, 'resume_manager_my_resume_do_action' ] );

	}

	/**
	 * Flush the cache
	 */
	public function flush_get_resume_listings_cache( $post_id ) {
		if ( 'resume' === get_post_type( $post_id ) ) {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
		}
	}

	/**
	 * Flush the cache
	 */
	public function resume_manager_my_resume_do_action( $action ) {
		WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
	}

	/**
	 * register_post_types function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_post_types() {

		if ( post_type_exists( 'resume' ) ) {
			return;
		}

		$admin_capability = 'manage_resumes';

		/**
		 * Taxonomies
		 */
		if ( get_option( 'resume_manager_enable_categories' ) ) {
			$singular = __( 'Resume Category', 'wp-job-manager-resumes' );
			$plural   = __( 'Resume Categories', 'wp-job-manager-resumes' );

			if ( current_theme_supports( 'resume-manager-templates' ) ) {
				$rewrite = [
					'slug'         => _x( 'resume-category', 'Resume category slug - resave permalinks after changing this', 'wp-job-manager-resumes' ),
					'with_front'   => false,
					'hierarchical' => false,
				];
			} else {
				$rewrite = false;
			}

			register_taxonomy(
				'resume_category',
				[ 'resume' ],
				[
					'hierarchical'          => true,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => $plural,
					'labels'                => [
						'name'              => $plural,
						'singular_name'     => $singular,
						'search_items'      => sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
						'all_items'         => sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
						'parent_item'       => sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager-resumes' ), $singular ),
						'edit_item'         => sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
						'update_item'       => sprintf( __( 'Update %s', 'wp-job-manager-resumes' ), $singular ),
						'add_new_item'      => sprintf( __( 'Add New %s', 'wp-job-manager-resumes' ), $singular ),
						'new_item_name'     => sprintf( __( 'New %s Name', 'wp-job-manager-resumes' ), $singular ),
					],
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => [
						'manage_terms' => $admin_capability,
						'edit_terms'   => $admin_capability,
						'delete_terms' => $admin_capability,
						'assign_terms' => $admin_capability,
					],
					'rewrite'               => $rewrite,
					'show_in_rest'          => true,
					'rest_base'             => 'resume-categories',
				]
			);
		}

		if ( get_option( 'resume_manager_enable_skills' ) ) {
			$singular = __( 'Candidate Skill', 'wp-job-manager-resumes' );
			$plural   = __( 'Candidate Skills', 'wp-job-manager-resumes' );

			if ( current_theme_supports( 'resume-manager-templates' ) ) {
				$rewrite = [
					'slug'         => _x( 'resume-skill', 'Resume skill slug - resave permalinks after changing this', 'wp-job-manager-resumes' ),
					'with_front'   => false,
					'hierarchical' => false,
				];
			} else {
				$rewrite = false;
			}

			register_taxonomy(
				'resume_skill',
				[ 'resume' ],
				[
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'label'                 => $plural,
					'labels'                => [
						'name'              => $plural,
						'singular_name'     => $singular,
						'search_items'      => sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
						'all_items'         => sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
						'parent_item'       => sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager-resumes' ), $singular ),
						'edit_item'         => sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
						'update_item'       => sprintf( __( 'Update %s', 'wp-job-manager-resumes' ), $singular ),
						'add_new_item'      => sprintf( __( 'Add New %s', 'wp-job-manager-resumes' ), $singular ),
						'new_item_name'     => sprintf( __( 'New %s Name', 'wp-job-manager-resumes' ), $singular ),
					],
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => [
						'manage_terms' => $admin_capability,
						'edit_terms'   => $admin_capability,
						'delete_terms' => $admin_capability,
						'assign_terms' => $admin_capability,
					],
					'rewrite'               => $rewrite,
					'show_in_rest'          => true,
					'rest_base'             => 'resume-skill',
				]
			);
		}

		/**
		 * Post types
		 */
		$singular = __( 'Resume', 'wp-job-manager-resumes' );
		$plural   = __( 'Resumes', 'wp-job-manager-resumes' );

		if ( current_theme_supports( 'resume-manager-templates' ) ) {
			$has_archive = _x( 'resumes', 'Post type archive slug - resave permalinks after changing this', 'wp-job-manager-resumes' );
		} else {
			$has_archive = false;
		}

		$rewrite = [
			'slug'       => _x( 'resume', 'Resume permalink - resave permalinks after changing this', 'wp-job-manager-resumes' ),
			'with_front' => false,
			'feeds'      => false,
			'pages'      => false,
		];

		register_post_type(
			'resume',
			apply_filters(
				'register_post_type_resume',
				[
					'labels'                => [
						'name'               => $plural,
						'singular_name'      => $singular,
						'menu_name'          => $plural,
						'all_items'          => sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
						'add_new'            => __( 'Add New', 'wp-job-manager-resumes' ),
						'add_new_item'       => sprintf( __( 'Add %s', 'wp-job-manager-resumes' ), $singular ),
						'edit'               => __( 'Edit', 'wp-job-manager-resumes' ),
						'edit_item'          => sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
						'new_item'           => sprintf( __( 'New %s', 'wp-job-manager-resumes' ), $singular ),
						'view'               => sprintf( __( 'View %s', 'wp-job-manager-resumes' ), $singular ),
						'view_item'          => sprintf( __( 'View %s', 'wp-job-manager-resumes' ), $singular ),
						'search_items'       => sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
						'not_found'          => sprintf( __( 'No %s found', 'wp-job-manager-resumes' ), $plural ),
						'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'wp-job-manager-resumes' ), $plural ),
						'parent'             => sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular ),
					],
					'description'           => __( 'This is where you can create and manage user resumes.', 'wp-job-manager-resumes' ),
					'public'                => true,
					// Hide the UI when the plugin is secretly disabled because WPJM core isn't activated.
					'show_ui'               => class_exists( 'WP_Job_Manager' ),
					'capability_type'       => 'post',
					'capabilities'          => [
						'publish_posts'       => $admin_capability,
						'edit_posts'          => $admin_capability,
						'edit_others_posts'   => $admin_capability,
						'delete_posts'        => $admin_capability,
						'delete_others_posts' => $admin_capability,
						'read_private_posts'  => $admin_capability,
						'edit_post'           => $admin_capability,
						'delete_post'         => $admin_capability,
						'read_post'           => $admin_capability,
					],
					'publicly_queryable'    => true,
					'exclude_from_search'   => true,
					'hierarchical'          => false,
					'rewrite'               => $rewrite,
					'query_var'             => true,
					'supports'              => [ 'title', 'editor', 'custom-fields', 'author' ],
					'has_archive'           => $has_archive,
					'show_in_nav_menus'     => false,
					'delete_with_user'      => true,
					'menu_position'         => 32,
					'show_in_rest'          => true,
					'rest_base'             => 'resumes',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
				]
			)
		);

		add_filter( 'wp_sitemaps_post_types', [ $this, 'disable_sitemap' ] );

		register_post_status(
			'hidden',
			[
				'label'                     => _x( 'Hidden', 'post status', 'wp-job-manager-resumes' ),
				'public'                    => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>', 'wp-job-manager-resumes' ),
			]
		);
	}

	/**
	 * Filters the resume post type from sitemap.
	 *
	 * @since 1.18.2
	 * @access private
	 *
	 * @param array $post_types  The public post types.
	 *
	 * @return array The filtered post types.
	 */
	public function disable_sitemap( $post_types ) {
		unset( $post_types['resume'] );
		return $post_types;
	}

	public function download_resume_handler() {
		global $post, $is_IE;

		if ( empty( $_GET['download-resume'] ) ) {
			return;
		}

		$resume_id = absint( $_GET['download-resume'] );

		if ( $resume_id && resume_manager_user_can_view_resume( $resume_id ) && apply_filters( 'resume_manager_user_can_download_resume_file', true, $resume_id ) ) {
			$files = get_resume_attachments( $resume_id );

			if ( empty( $files['attachments'] ) ) {
				// This should never happen as the link to download the resume does not appear when there are no files.
				return;
			}

			$file_id   = ! empty( $_GET['file-id'] ) ? absint( $_GET['file-id'] ) : 0;
			$file_path = $files['attachments'][ $file_id ];

			$file_extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );
			$ctype          = 'application/force-download';

			foreach ( get_allowed_mime_types() as $mime => $type ) {
				$mimes = explode( '|', $mime );
				if ( in_array( $file_extension, $mimes ) ) {
					$ctype = $type;
					break;
				}
			}

			// Start setting headers.
			if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
				@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- No output wanted during download.
			}

			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}

			@session_write_close();
			@ini_set( 'zlib.output_compression', 'Off' );
			/**
			 * Prevents errors, for example: transfer closed with 3 bytes remaining to read
			 */
			@ob_end_clean(); // Clear the output buffer

			if ( ob_get_level() ) {

				$levels = ob_get_level();

				for ( $i = 0; $i < $levels; $i++ ) {
					@ob_end_clean(); // Zip corruption fix
				}
			}

			if ( $is_IE && is_ssl() ) {
				// IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
				header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
				header( 'Cache-Control: private' );
			} else {
				nocache_headers();
			}

			$filename = basename( $file_path );

			if ( strstr( $filename, '?' ) ) {
				$filename = current( explode( '?', $filename ) );
			}

			header( 'X-Robots-Tag: noindex, nofollow', true );
			header( 'Content-Type: ' . $ctype );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
			header( 'Content-Transfer-Encoding: binary' );

			if ( $size = @filesize( $file_path ) ) {
				header( 'Content-Length: ' . $size );
			}

			$this->readfile_chunked( $file_path ) or wp_die( __( 'File not found', 'wp-job-manager-resumes' ) . ' <a href="' . esc_url( home_url() ) . '" class="wc-forward">' . __( 'Go to homepage', 'wp-job-manager-resumes' ) . '</a>' );

			exit;
		}
	}

	/**
	 * readfile_chunked
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param    string $file
	 * @param    bool   $retbytes return bytes of file
	 * @return bool|int
	 * @todo Meaning of the return value? Last return is status of fclose?
	 */
	public static function readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$buffer    = '';
		$cnt       = 0;

		$handle = @fopen( $file, 'r' );
		if ( $handle === false ) {
			return false;
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			@ob_flush();
			@flush();

			if ( $retbytes ) {
				$cnt += strlen( $buffer );
			}
		}

		$status = fclose( $handle );

		if ( $retbytes && $status ) {
			return $cnt;
		}

		return $status;
	}

	/**
	 * Don't include resume name in Yoast page markup if the user isn't able to view the resume.
	 */
	public function maybe_add_yoast_filters() {
		$post = get_post();

		if ( ! is_null( $post ) ) {
			if ( 'resume' === get_post_type( $post->ID ) && ! resume_manager_user_can_view_resume_name( $post->ID ) ) {
				add_filter( 'wpseo_title', '__return_empty_string' );
				add_filter( 'wpseo_opengraph_title', '__return_empty_string' );
				add_filter( 'wpseo_schema_graph_pieces', 'remove_webpage_from_schema', 11, 2 );
			}
		}
	}

	/**
	 * Change label
	 */
	public function admin_head() {
		global $menu;

		$plural        = __( 'Resumes', 'wp-job-manager-resumes' );
		$count_resumes = wp_count_posts( 'resume', 'readable' );

		foreach ( $menu as $key => $menu_item ) {
			if ( strpos( $menu_item[0], $plural ) === 0 ) {
				if ( $resume_count = $count_resumes->pending ) {
					$menu[ $key ][0] .= " <span class='awaiting-mod update-plugins count-$resume_count'><span class='pending-count'>" . number_format_i18n( $count_resumes->pending ) . '</span></span>';
				}
				break;
			}
		}
	}

	/**
	 * Adds robots `noindex` meta tag to discourage search indexing.
	 */
	public function add_no_robots() {
		if ( ! is_single() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || 'resume' !== $post->post_type ) {
			return;
		}

		if ( function_exists( 'wp_robots_no_robots' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		} else {
			wp_no_robots();
		}
	}

	/**
	 * Hide resume titles from users without access
	 *
	 * @param  string $title
	 * @param  int    $post_or_id
	 * @return string
	 */
	public function resume_title( $title, $post_or_id = null ) {
		if ( $post_or_id && 'resume' === get_post_type( $post_or_id ) && ! resume_manager_user_can_view_resume_name( $post_or_id ) ) {
			$title_parts    = explode( ' ', $title );
			$hidden_title[] = array_shift( $title_parts );
			foreach ( $title_parts as $title_part ) {
				$hidden_title[] = str_repeat( '*', strlen( $title_part ) );
			}
			return apply_filters( 'resume_manager_hidden_resume_title', implode( ' ', $hidden_title ), $title, $post_or_id );
		}
		return $title;
	}

	/**
	 * Add extra content when showing resumes
	 */
	public function resume_content( $content ) {
		global $post;

		if ( ! is_singular( 'resume' ) || ! in_the_loop() ) {
			return $content;
		}

		remove_filter( 'the_content', [ $this, 'resume_content' ] );

		if ( $post->post_type == 'resume' ) {
			ob_start();

			get_job_manager_template_part( 'content-single', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );

			$content = ob_get_clean();
		}

		add_filter( 'the_content', [ $this, 'resume_content' ] );

		return $content;
	}

	/**
	 * The application content when the application method is an email
	 */
	public function contact_details_email() {
		global $post;

		$email   = get_post_meta( $post->ID, '_candidate_email', true );
		$subject = sprintf( __( 'Contact via the resume for "%1$s" on %2$s', 'wp-job-manager-resumes' ), single_post_title( '', false ), home_url() );

		get_job_manager_template(
			'contact-details-email.php',
			[
				'email'   => $email,
				'subject' => $subject,
			],
			'wp-job-manager-resumes',
			RESUME_MANAGER_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * Setup event to hide a resume after X days
	 *
	 * @param  object $post
	 */
	public function setup_autohide_cron( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}
		if ( $post->post_type !== 'resume' ) {
			return;
		}

		add_post_meta( $post->ID, '_featured', 0, true );
		wp_clear_scheduled_hook( 'auto-hide-resume', [ $post->ID ] );

		$resume_manager_autohide = get_option( 'resume_manager_autohide' );

		if ( $resume_manager_autohide ) {
			wp_schedule_single_event( strtotime( "+{$resume_manager_autohide} day" ), 'auto-hide-resume', [ $post->ID ] );
		}
	}

	/**
	 * Hide a resume
	 *
	 * @param  int
	 */
	public function hide_resume( $resume_id ) {
		$resume = get_post( $resume_id );
		if ( $resume->post_status === 'publish' ) {
			$update_resume = [
				'ID'          => $resume_id,
				'post_status' => 'hidden',
			];
			wp_update_post( $update_resume );
			wp_clear_scheduled_hook( 'auto-hide-resume', [ $resume_id ] );
		}
	}

	/**
	 * Maybe set menu_order if the featured status of a resume is changed
	 */
	public function maybe_update_menu_order( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_featured' !== $meta_key || 'resume' !== get_post_type( $object_id ) ) {
			return;
		}
		global $wpdb;

		if ( '1' == $_meta_value ) {
			$wpdb->update( $wpdb->posts, [ 'menu_order' => -1 ], [ 'ID' => $object_id ] );
		} else {
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => 0 ],
				[
					'ID'         => $object_id,
					'menu_order' => -1,
				]
			);
		}

		clean_post_cache( $object_id );
	}

	/**
	 * Fix post name when wp_update_post changes it
	 *
	 * @param  array $data
	 * @return array
	 */
	public function fix_post_name( $data, $postarr ) {
		if ( 'resume' === $data['post_type'] && 'pending' === $data['post_status'] && ! current_user_can( 'publish_posts' ) ) {
			$data['post_name'] = $postarr['post_name'];
		}
		 return $data;
	}

	/**
	 * Typo -.-
	 */
	public function set_expirey( $post ) {
		$this->set_expiry( $post );
	}

	/**
	 * Set expirey date when resume status changes
	 */
	public function set_expiry( $post ) {
		if ( $post->post_type !== 'resume' ) {
			return;
		}

		// See if it is already set
		if ( metadata_exists( 'post', $post->ID, '_resume_expires' ) ) {
			$expires = get_post_meta( $post->ID, '_resume_expires', true );
			if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				update_post_meta( $post->ID, '_resume_expires', '' );
				$_POST['_resume_expires'] = '';
			}
			return;
		}

		// No metadata set so we can generate an expiry date
		// See if the user has set the expiry manually:
		if ( ! empty( $_POST['_resume_expires'] ) ) {
			update_post_meta( $post->ID, '_resume_expires', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST['_resume_expires'] ) ) ) );

			// No manual setting? Lets generate a date
		} else {
			$expires = calculate_resume_expiry( $post->ID );
			update_post_meta( $post->ID, '_resume_expires', $expires );

			// In case we are saving a post, ensure post data is updated so the field is not overridden
			if ( isset( $_POST['_resume_expires'] ) ) {
				$_POST['_resume_expires'] = $expires;
			}
		}
	}

	/**
	 * Expire resumes
	 */
	public function check_for_expired_resumes() {
		global $wpdb;

		// Change status to expired
		$resume_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_resume_expires'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'resume'
		",
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);

		if ( $resume_ids ) {
			foreach ( $resume_ids as $resume_id ) {
				$data                = [];
				$data['ID']          = $resume_id;
				$data['post_status'] = 'expired';
				wp_update_post( $data );
			}
		}

		// Delete old expired resumes
		if ( apply_filters( 'resume_manager_delete_expired_resumes', true ) ) {
			$resume_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'resume'
				AND posts.post_modified < %s
				AND posts.post_status = 'expired'
			",
					date( 'Y-m-d', strtotime( '-' . apply_filters( 'resume_manager_delete_expired_resumes_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
				)
			);

			if ( $resume_ids ) {
				foreach ( $resume_ids as $resume_id ) {
					wp_trash_post( $resume_id );
				}
			}
		}
	}

	/**
	 * Registers resume meta fields.
	 */
	public function register_meta_fields() {
		$fields = self::get_resume_fields();

		foreach ( $fields as $meta_key => $field ) {
			register_meta(
				'post',
				$meta_key,
				[
					'type'              => $field['data_type'],
					'show_in_rest'      => $field['show_in_rest'],
					'description'       => $field['label'],
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => $field['auth_edit_callback'],
					'single'            => true,
					'object_subtype'    => 'resume',
				]
			);
		}
	}

	/**
	 * Returns configuration for custom fields on Resume posts.
	 *
	 * @return array See `resume_manager_resume_fields` filter for more documentation.
	 */
	public static function get_resume_fields() {
		$default_field = [
			'label'              => null,
			'placeholder'        => null,
			'description'        => null,
			'priority'           => 10,
			'value'              => null,
			'default'            => null,
			'classes'            => [],
			'type'               => 'text',
			'data_type'          => 'string',
			'show_in_admin'      => true,
			'show_in_rest'       => false,
			'auth_edit_callback' => [ __CLASS__, 'auth_check_can_edit_resumes' ],
			'auth_view_callback' => null,
			'sanitize_callback'  => [ __CLASS__, 'sanitize_meta_field_based_on_input_type' ],
		];

		$fields = [
			'_candidate_title'    => [
				'label'         => __( 'Professional Title', 'wp-job-manager-resumes' ),
				'placeholder'   => '',
				'description'   => '',
				'priority'      => 1,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_candidate_email'    => [
				'label'         => __( 'Contact Email', 'wp-job-manager-resumes' ),
				'placeholder'   => __( 'you@yourdomain.com', 'wp-job-manager-resumes' ),
				'description'   => '',
				'priority'      => 2,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_candidate_location' => [
				'label'         => __( 'Candidate Location', 'wp-job-manager-resumes' ),
				'placeholder'   => __( 'e.g. "London, UK", "New York", "Houston, TX"', 'wp-job-manager-resumes' ),
				'description'   => '',
				'priority'      => 3,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_candidate_photo'    => [
				'label'         => __( 'Photo', 'wp-job-manager-resumes' ),
				'placeholder'   => __( 'URL to the candidate photo', 'wp-job-manager-resumes' ),
				'type'          => 'file',
				'priority'      => 4,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_candidate_video'    => [
				'label'             => __( 'Video', 'wp-job-manager-resumes' ),
				'placeholder'       => __( 'URL to the candidate video', 'wp-job-manager-resumes' ),
				'type'              => 'text',
				'priority'          => 5,
				'data_type'         => 'string',
				'show_in_admin'     => true,
				'show_in_rest'      => true,
				'sanitize_callback' => [ 'WP_Job_Manager_Post_Types', 'sanitize_meta_field_url' ],
			],
			'_resume_file'        => [
				'label'         => __( 'Resume File', 'wp-job-manager-resumes' ),
				'placeholder'   => __( 'URL to the candidate\'s resume file', 'wp-job-manager-resumes' ),
				'type'          => 'file',
				'priority'      => 6,
				'data_type'     => 'string',
				'show_in_admin' => true,
				'show_in_rest'  => true,
			],
			'_featured'           => [
				'label'              => __( 'Feature this Resume?', 'wp-job-manager-resumes' ),
				'type'               => 'checkbox',
				'description'        => __( 'Featured resumes will be sticky during searches, and can be styled differently.', 'wp-job-manager-resumes' ),
				'priority'           => 7,
				'data_type'          => 'integer',
				'show_in_admin'      => true,
				'show_in_rest'       => true,
				'auth_edit_callback' => [ __CLASS__, 'auth_check_can_manage_resumes' ],
			],
			'_resume_expires'     => [
				'label'              => __( 'Expires', 'wp-job-manager-resumes' ),
				'placeholder'        => __( 'yyyy-mm-dd', 'wp-job-manager-resumes' ),
				'priority'           => 8,
				'data_type'          => 'string',
				'show_in_admin'      => true,
				'show_in_rest'       => true,
				'auth_edit_callback' => [ __CLASS__, 'auth_check_can_manage_resumes' ],
				'auth_view_callback' => [ __CLASS__, 'auth_check_can_edit_resumes' ],
				'sanitize_callback'  => [ 'WP_Job_Manager_Post_Types', 'sanitize_meta_field_date' ],
			],
		];

		if ( ! get_option( 'resume_manager_enable_resume_upload' ) ) {
			unset( $fields['_resume_file'] );
		}

		/**
		 * Filters resume data fields.
		 *
		 * For the REST API, do not pass fields you don't want to be visible to the current visitor when `show_in_rest`
		 * is `true`. To add values and other data when generating the WP admin form, use filter
		 * `resume_manager_resume_wp_admin_fields` which should have `$post_id` in context.
		 *
		 * @since @@version
		 *
		 * @param array    $fields  {
		 *     Resume meta fields for REST API and WP admin. Associative array with meta key as the index.
		 *     All fields except for `$label` are optional and have working defaults.
		 *
		 *     @type array $meta_key {
		 *         @type string        $label              Label to show for field. Used in: WP Admin; REST API.
		 *         @type string        $placeholder        Placeholder to show in empty form fields. Used in: WP Admin.
		 *         @type string        $description        Longer description to shown below form field.
		 *                                                 Used in: WP Admin.
		 *         @type array         $classes            Classes to apply to form input field. Used in: WP Admin.
		 *         @type int           $priority           Field placement priority for WP admin. Lower is first.
		 *                                                 Used in: WP Admin (Default: 10).
		 *         @type string        $value              Override standard retrieval of meta value in form field.
		 *                                                 Used in: WP Admin.
		 *         @type string        $default            Default value on form field if no other value is set for
		 *                                                 field. Used in: WP Admin.
		 *         @type string        $type               Type of form field to render. Used in: WP Admin
		 *                                                 (Default: 'text').
		 *         @type string        $data_type          Data type to cast to. Options: 'string', 'boolean',
		 *                                                 'integer', 'number'.  Used in: REST API. (    *                                                 Default: 'string').
		 *         @type bool|callable $show_in_admin      Whether field should be displayed in WP admin. Can be
		 *                                                 callable that returns boolean. Used in: WP Admin
		 *                                                 (Default: true).
		 *         @type bool|array    $show_in_rest       Whether data associated with this meta key can put in REST
		 *                                                 API response for resumes. Can be used to pass REST API
		 *                                                 arguments in `show_in_rest` parameter. Used in: REST API
		 *                                                 (Default: false).
		 *         @type callable      $auth_edit_callback {
		 *             Decides if specific user can edit the meta key. Used in: WP Admin; REST API.
		 *             Defaults to callable that limits to those who can edit specific the resume (also limited
		 *             by relevant endpoints).
		 *
		 *             @see WP core filter `auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}`.
		 *             @since @@version
		 *
		 *             @param bool   $allowed   Whether the user can add the object meta. Default false.
		 *             @param string $meta_key  The meta key.
		 *             @param int    $object_id Post ID for Resume.
		 *             @param int    $user_id   User ID.
		 *
		 *             @return bool
		 *         }
		 *         @type callable      $auth_view_callback {
		 *             Decides if specific user can view value of the meta key. Used in: REST API.
		 *             Defaults to visible to all (if shown in REST API, which by default is false).
		 *
		 *             @see WPJM method `WP_Resume_Manager_REST_API::prepare_resume()`.
		 *             @since @@version
		 *
		 *             @param bool   $allowed   Whether the user can add the object meta. Default false.
		 *             @param string $meta_key  The meta key.
		 *             @param int    $object_id Post ID for Resume.
		 *             @param int    $user_id   User ID.
		 *
		 *             @return bool
		 *         }
		 *         @type callable      $sanitize_callback  {
		 *             Sanitizes the meta value before saving to database. Used in: WP Admin; REST API; Frontend.
		 *             Defaults to callable that sanitizes based on the field type.
		 *
		 *             @see WP core filter `auth_{$object_type}_meta_{$meta_key}_for_{$object_subtype}`
		 *             @since @@version
		 *
		 *             @param mixed  $meta_value Value of meta field that needs sanitization.
		 *             @param string $meta_key   Meta key that is being sanitized.
		 *
		 *             @return mixed
		 *         }
		 *     }
		 * }
		 */
		$fields = apply_filters( 'resume_manager_resume_fields', $fields );

		// Ensure default fields are set.
		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = array_merge( $default_field, $field );
		}

		return $fields;
	}

	/**
	 * Checks if user can manage resumes.
	 *
	 * @param bool   $allowed   Whether the user can edit the resume meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   Resume's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the resume meta.
	 */
	public static function auth_check_can_manage_resumes( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		return $user->has_cap( 'manage_resumes' );
	}

	/**
	 * Checks if user can edit resumes.
	 *
	 * @param bool   $allowed   Whether the user can edit the resume meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   Resume's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the resume meta.
	 */
	public static function auth_check_can_edit_resumes( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		if ( empty( $post_id ) ) {
			return current_user_can( 'edit_posts' );
		}

		return resume_manager_user_can_edit_resume( $post_id );
	}

	/**
	 * Checks if user can edit other's resumes.
	 *
	 * @param bool   $allowed   Whether the user can edit the resume meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $post_id   Resume's post ID.
	 * @param int    $user_id   User ID.
	 *
	 * @return bool Whether the user can edit the resume meta.
	 */
	public static function auth_check_can_edit_others_resumes( $allowed, $meta_key, $post_id, $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return false;
		}

		return $user->has_cap( 'edit_others_posts' );
	}

	/**
	 * Sanitize meta fields based on input type.
	 *
	 * @param mixed  $meta_value Value of meta field that needs sanitization.
	 * @param string $meta_key   Meta key that is being sanitized.
	 * @return mixed
	 */
	public static function sanitize_meta_field_based_on_input_type( $meta_value, $meta_key ) {
		$fields = self::get_resume_fields();

		if ( is_string( $meta_value ) ) {
			$meta_value = trim( $meta_value );
		}

		$type = 'text';
		if ( isset( $fields[ $meta_key ] ) ) {
			$type = $fields[ $meta_key ]['type'];
		}

		if ( 'textarea' === $type || 'wp_editor' === $type ) {
			return wp_kses_post( wp_unslash( $meta_value ) );
		}

		if ( 'checkbox' === $type ) {
			if ( $meta_value && '0' !== $meta_value ) {
				return 1;
			}

			return 0;
		}

		if ( is_array( $meta_value ) ) {
			return array_filter( array_map( 'sanitize_text_field', $meta_value ) );
		}

		return sanitize_text_field( $meta_value );
	}

	/**
	 * Save Resume Skills to post meta.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_postmeta( $post_id ) {
		if ( is_admin() ) {
			$term_list  = wp_get_post_terms( $post_id, 'resume_skill' );
			$term_names = wp_list_pluck( $term_list, 'name' );
			$new_terms  = implode( ',', $term_names );
			update_post_meta( $post_id, '_resume_skills', $new_terms );
		}
	}
}
