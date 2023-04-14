<?php
/**
 * BuddyPages_User_Pages Class File.
 *
 * @package BuddyPagesUserPages
 * @subpackage BuddyPages
 * @author WebDevStudios
 * @since 1.0.0
 */

/**
 * Main initiation class.
 *
 * @internal
 *
 * @since 1.0.0
 */
class BuddyPages_User_Pages {

	/**
	 * Parent plugin class.
	 *
	 * @var object
	 * @since 1.0.0
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param object $plugin this class.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'bp_setup_nav', array( $this, 'add_profile_pages' ), 999 );
		add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), 999 );
	}

	/**
	 * Add published pages to profile.
	 *
	 * @since 1.0.0
	 */
	public function add_profile_pages() {

		$displayed_userid = bp_displayed_user_id();
		$pages            = $this->get_user_pages( $displayed_userid, true, false );
		$can_access       = buddypages_has_access();

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		if ( ! empty( $pages->posts ) ) {

			$draft_title = __( 'post status is draft', 'buddypages' );

			foreach ( $pages->posts as $post ) {

				$post_name    = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
				$edit_link    = trailingslashit( bp_core_get_user_domain( $post->post_author ) . $post_name );
				$add_new_link = trailingslashit( bp_core_get_user_domain( $displayed_userid ) . 'settings/pages/' );

				$draft   = ( 'draft' === $post->post_status ) ? ' (draft)' : '';
				$post_in = buddypages_post_in( $post->ID );
				$edit    = buddypages_can_edit( $post->post_author, $displayed_userid, $post ) ? esc_attr__( 'Edit', 'buddypages' ) : '';
				$tab     = in_array( $post_in, array( 'profile', 'all-users' ), true ) ? $post->post_title . $draft : '';

				// To conditionally hide some items, we will utilize a suffix on the item_css_id
				$suffix = '';

				// Set the tab value to the BuddyPage title/status regardless of $post_in, only for edit action.
				if ( 'edit' === bp_current_action() ) {
					$tab = $post->post_title . $draft;

					$suffix = '-user-page';
					if ( false !== strpos( $post_in, 'group-' ) ) {
						$suffix = '-group-page';
					}
				}



				if ( 'publish' === $post->post_status || $can_access ) {

					bp_core_new_nav_item(
						array(
							'name'                => $tab,
							'slug'                => $post_name,
							'default_subnav_slug' => $post_name,
							'position'            => 50,
							'screen_function'     => 'buddypages_user_nav_item_screen',
							'item_css_id'         => 'buddypage-' . $post_name . $suffix,
						)
					);

				}

				if ( $edit ) {
					bp_core_new_subnav_item(
						array(
							'name'            => $edit,
							'slug'            => 'edit',
							'parent_url'      => $edit_link,
							'parent_slug'     => $post_name,
							'position'        => 50,
							'screen_function' => 'buddypages_user_nav_item_screen',
							'item_css_id'     => 'buddypage' . $post_name,
							'user_has_access' => $can_access,
						)
					);

					bp_core_new_subnav_item(
						array(
							'name'            => esc_html__( 'Add New Page', 'buddypages' ),
							'slug'            => 'new',
							'parent_url'      => $add_new_link,
							'parent_slug'     => $post_name,
							'position'        => 51,
							'screen_function' => 'buddypages_user_nav_item_screen',
						)
					);
				}
			}
		}

		bp_core_new_subnav_item(
			array(
				'name'            => esc_attr__( 'Pages', 'buddypages' ),
				'slug'            => 'pages',
				'parent_url'      => trailingslashit( $user_domain . 'settings' ),
				'parent_slug'     => 'settings',
				'position'        => 50,
				'screen_function' => 'buddypages_user_nav_item_screen',
				'item_css_id'     => 'buddypage-new',
				'user_has_access' => $can_access,
			)
		);

	}

	/**
	 * Add pages admin menu item.
	 *
	 * @since 1.0.0
	 */
	public function setup_admin_bar() {
		global $wp_admin_bar;

		// Bail if this is an ajax request.
		if ( defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Menus for logged in user.
		if ( is_user_logged_in() && function_exists( 'bp_get_settings_slug' ) ) {

			// Setup the logged in user variables.
			$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

			$wp_admin_bar->add_menu( array(
				'parent' => 'my-account-settings',
				'id'     => 'my-account-buddypages-pages',
				'title'  => esc_attr__( 'Pages', 'buddypages' ),
				'href'   => trailingslashit( $settings_link . 'pages' ),
				'meta'   => array( 'class' => 'ab-sub-secondary' ),
			) );
		}
	}

	/**
	 * Query for user pages.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id       Displayed user id.
	 * @param boolean $is_meta_query To query with meta.
	 * @param boolean $is_profile    To query author.
	 * @return object WP_Query
	 */
	public function get_user_pages( $user_id, $is_meta_query = false, $is_profile = false ) {

		if ( 'edit' === bp_current_action() ) {
			$is_meta_query = $is_profile ? false : true;
		}

		$query2 = array();

		$query1 = new WP_Query( array(
			'fields'         => 'ids',
			'author'         => $user_id,
			'post_type'      => 'buddypages',
			'posts_per_page' => - 1,
			'post_status'    => array( 'draft', 'publish' ),
		) );

		$query2Args = array(
			'fields'         => 'ids',
			'post_type'      => 'buddypages',
			'posts_per_page' => - 1,
			'post_status'    => array( 'draft', 'publish' ),
		);

		if ( $is_profile ) {
			$query2Args['author'] = $user_id;
		}

		if ( $is_meta_query  ) {

			$query2Args['meta_query'] = array(
				array(
					'key'     => 'post_in',
					'value'   => array( 'all-users' ),
					'compare' => 'IN',
				),
			);

			$query2 = new WP_Query( $query2Args );
			$query2 = $query2->posts;
		}

		$pages_ids = array_merge( $query1->posts, $query2 );

		if ( empty( $pages_ids ) ) {
			return false;
		}

		$args = array(
			'post_type'   => 'buddypages',
			'post__in'    => $pages_ids,
			'post_status' => array( 'draft', 'publish' ),
		);

		$args = apply_filters( 'get_user_pages_args', $args );

		$query = new WP_Query( $args );
		return $query;
	}
}
