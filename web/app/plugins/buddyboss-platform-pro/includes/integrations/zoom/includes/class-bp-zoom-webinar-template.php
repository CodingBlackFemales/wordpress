<?php
/**
 * BuddyPress Zoom Webinar Template loop class.
 *
 * @package BuddyBoss\Webinar
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main webinar template loop class.
 *
 * Responsible for loading a group of webinar into a loop for display.
 *
 * @since 1.0.9
 */
class BP_Zoom_Webinar_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $current_webinar = -1;

	/**
	 * The webinar count.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $webinar_count;

	/**
	 * The total webinar count.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $total_webinar_count;

	/**
	 * Array of webinar located by the query.
	 *
	 * @since 1.0.9
	 * @var array
	 */
	public $webinars;

	/**
	 * The webinar object currently being iterated on.
	 *
	 * @since 1.0.9
	 * @var object
	 */
	public $webinar;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.0.9
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for webinar pagination. Default: 'acpage'.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since 1.0.9
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 1.0.9
	 * @var string
	 */
	public $pag_links;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Zoom_Webinar::get()}.
	 *
	 * @since 1.0.9
	 *
	 * @see BP_Zoom_Webinar::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Zoom_Webinar::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of webinar IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 *     @type int|bool    $per_page         Default: 20.
	 * }
	 */
	public function __construct( $args ) {

		$defaults = array(
			'page'          => 1,
			'per_page'      => 20,
			'page_arg'      => 'acpage',
			'max'           => false,
			'fields'        => 'all',
			'count_total'   => false,
			'sort'          => false,
			'live'          => false,
			'order_by'      => false,
			'include'       => false,
			'exclude'       => false,
			'search_terms'  => false,
			'group_id'      => false,
			'webinar_id'    => false,
			'activity_id'   => false,
			'user'          => false,
			'since'         => false,
			'from'          => false,
			'recorded'      => false,
			'recurring'     => false,
			'hide_sitewide' => false,
			'zoom_type'     => false,
			'meta_query'    => false,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Fetch specific webinar items based on ID's.
		if ( ! empty( $include ) ) {
			$this->webinars = bp_zoom_webinar_get_specific(
				array(
					'webinar_ids'   => explode( ',', $include ),
					'max'           => $max,
					'count_total'   => $count_total,
					'page'          => $this->pag_page,
					'per_page'      => $this->pag_num,
					'sort'          => $sort,
					'live'          => $live,
					'order_by'      => $order_by,
					'group_id'      => $group_id,
					'webinar_id'    => $webinar_id,
					'since'         => $since,
					'from'          => $from,
					'recorded'      => $recorded,
					'recurring'     => $recurring,
					'meta_query'    => $meta_query,
					'hide_sitewide' => $hide_sitewide,
					'zoom_type'     => $zoom_type,
				)
			);

			// Fetch all activity items.
		} else {
			$this->webinars = bp_zoom_webinar_get(
				array(
					'max'           => $max,
					'count_total'   => $count_total,
					'per_page'      => $this->pag_num,
					'page'          => $this->pag_page,
					'sort'          => $sort,
					'live'          => $live,
					'order_by'      => $order_by,
					'search_terms'  => $search_terms,
					'exclude'       => $exclude,
					'group_id'      => $group_id,
					'webinar_id'    => $webinar_id,
					'activity_id'   => $activity_id,
					'user'          => $user,
					'since'         => $since,
					'from'          => $from,
					'recorded'      => $recorded,
					'recurring'     => $recurring,
					'meta_query'    => $meta_query,
					'hide_sitewide' => $hide_sitewide,
					'zoom_type'     => $zoom_type,
				)
			);
		}

		// The total_webinar_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->webinars['total'] ) ) {
			if ( ! $max || $max >= (int) $this->webinars['total'] ) {
				$this->total_webinar_count = (int) $this->webinars['total'];
			} else {
				$this->total_webinar_count = (int) $max;
			}
		}

		$this->has_more_items = $this->webinars['has_more_items'];

		$this->webinars = $this->webinars['webinars'];

		if ( $max ) {
			if ( $max >= count( $this->webinars ) ) {
				$this->webinar_count = count( $this->webinars );
			} else {
				$this->webinar_count = (int) $max;
			}
		} else {
			$this->webinar_count = count( $this->webinars );
		}

		if ( (int) $this->total_webinar_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_webinar_count / (int) $this->pag_num ),
					'current'   => (int) $this->pag_page,
					'prev_text' => __( '&larr;', 'buddyboss-pro' ),
					'next_text' => __( '&rarr;', 'buddyboss-pro' ),
					'mid_size'  => 1,
					'add_args'  => array(),
				)
			);
		}
	}

	/**
	 * Whether there are webinar items available in the loop.
	 *
	 * @since 1.0.9
	 *
	 * @see bp_has_zoom_webinars()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_webinar() {
		if ( $this->webinar_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next webinar item and iterate index.
	 *
	 * @since 1.0.9
	 *
	 * @return object The next webinar item to iterate over.
	 */
	public function next_webinar() {
		$this->current_webinar++;
		$this->webinar = $this->webinars[ $this->current_webinar ];

		return $this->webinar;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.0.9
	 */
	public function rewind_webinars() {
		$this->current_webinar = -1;
		if ( $this->webinar_count > 0 ) {
			$this->webinar = $this->webinars[0];
		}
	}

	/**
	 * Whether there are webinar items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_zoom_webinar()} as part of the while loop
	 * that controls iteration inside the webinar loop, eg:
	 *     while ( bp_zoom_webinar() ) { ...
	 *
	 * @since 1.0.9
	 *
	 * @see bp_zoom_webinar()
	 *
	 * @return bool True if there are more webinar items to show,
	 *              otherwise false.
	 */
	public function user_webinars() {
		if ( ( $this->current_webinar + 1 ) < $this->webinar_count ) {
			return true;
		} elseif ( ( $this->current_webinar + 1 ) === $this->webinar_count ) {

			/**
			 * Fires right before the rewinding of webinar posts.
			 *
			 * @since 1.0.9
			 */
			do_action( 'bp_zoom_webinar_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_webinars();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current webinar item inside the loop.
	 *
	 * Used by {@link bp_the_zoom_webinar()} to set up the current webinar item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current webinar item.
	 *
	 * @since 1.0.9
	 *
	 * @see bp_the_zoom_webinar()
	 */
	public function the_webinar() {

		$this->in_the_loop = true;
		$this->webinar     = $this->next_webinar();

		if ( is_array( $this->webinar ) ) {
			$this->webinar = (object) $this->webinar;
		}

		// Loop has just started.
		if ( 0 === $this->current_webinar ) {

			/**
			 * Fires if the current webinar item is the first in the activity loop.
			 *
			 * @since 1.0.9
			 */
			do_action( 'bp_zoom_webinar_loop_start' );
		}
	}
}
