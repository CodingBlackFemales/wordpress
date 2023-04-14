<?php
/**
 * BuddyPages_Group_Pages Class File.
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
class BuddyPages_Group_Pages {

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
	 * @param object $plugin This class.
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
		add_action( 'bp_setup_nav', array( $this, 'add_group_pages' ), 999 );
	}

	/**
	 * Add published pages to profile.
	 *
	 * @since 1.0.0
	 */
	public function add_group_pages() {

		$bp = buddypress();

		if ( ! bp_is_group() ) {
			return;
		}

		$pages = $this->get_group_pages( 'group-' . $bp->groups->current_group->id, true );

		if ( empty( $pages->posts ) ) {
			return;
		}

		$draft_title = __( 'post status is draft', 'buddypages' );
		$can_access  = buddypages_has_access();

		foreach ( $pages->posts as $post ) {
			// Need to reset this one each iteration. Title and access don't change.
			$draft = '';
			if ( 'draft' === $post->post_status ) {
				$draft = ' (draft)';
			}

			if ( 'publish' === $post->post_status || $can_access ) {

				bp_core_new_subnav_item(
					array(
						'name'            => $post->post_title . $draft,
						'slug'            => $post->post_name,
						'parent_slug'     => $bp->groups->current_group->slug,
						'parent_url'      => bp_get_group_permalink( $bp->groups->current_group ),
						'position'        => 11,
						'item_css_id'     => 'nav-pages',
						'screen_function' => 'buddypages_group_nav_item_screen',
						'user_has_access' => true,
					)
				);
			}
		}

	}

	/**
	 * Query for group pages.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $group_id displayed group id.
	 * @param boolean $is_meta_query to query with meta.
	 * @return object WP_Query
	 */
	public function get_group_pages( $group_id, $is_meta_query = false ) {

		$args = array(
			'post_type'   => 'buddypages',
			'post_status' => array( 'publish' ),
		);

		if ( $is_meta_query ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'post_in',
					'value'   => array( $group_id, 'all-groups' ),
					'compare' => 'IN',
				),
			);
		}

		/**
		 * Filters the get group pages arguments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Array of arguments for group pages query.
		 */
		$args = apply_filters( 'get_group_pages_args', $args );

		$query = new WP_Query( $args );
		return $query;
	}
}
