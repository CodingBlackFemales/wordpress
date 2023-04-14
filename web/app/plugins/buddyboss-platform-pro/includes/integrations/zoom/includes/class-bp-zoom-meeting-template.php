<?php
/**
 * BuddyPress Zoom Meeting Template loop class.
 *
 * @package BuddyBoss\Meeting
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main meeting template loop class.
 *
 * Responsible for loading a group of meeting into a loop for display.
 *
 * @since 1.0.0
 */
class BP_Zoom_Meeting_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $current_meeting = -1;

	/**
	 * The meeting count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $meeting_count;

	/**
	 * The total meeting count.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $total_meeting_count;

	/**
	 * Array of meeting located by the query.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $meetings;

	/**
	 * The meeting object currently being iterated on.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $meeting;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for meeting pagination. Default: 'acpage'.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Zoom_Meeting::get()}.
	 *
	 * @since 1.0.0
	 *
	 * @see BP_Zoom_Meeting::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Zoom_Meeting::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of meeting IDs to
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
			'meeting_id'    => false,
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

		// Fetch specific meeting items based on ID's.
		if ( ! empty( $include ) ) {
			$this->meetings = bp_zoom_meeting_get_specific(
				array(
					'meeting_ids'   => explode( ',', $include ),
					'max'           => $max,
					'count_total'   => $count_total,
					'page'          => $this->pag_page,
					'per_page'      => $this->pag_num,
					'sort'          => $sort,
					'live'          => $live,
					'order_by'      => $order_by,
					'group_id'      => $group_id,
					'meeting_id'    => $meeting_id,
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
			$this->meetings = bp_zoom_meeting_get(
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
					'meeting_id'    => $meeting_id,
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

		// The total_meeting_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->meetings['total'] ) ) {
			if ( ! $max || $max >= (int) $this->meetings['total'] ) {
				$this->total_meeting_count = (int) $this->meetings['total'];
			} else {
				$this->total_meeting_count = (int) $max;
			}
		}

		$this->has_more_items = $this->meetings['has_more_items'];

		$this->meetings = $this->meetings['meetings'];

		if ( $max ) {
			if ( $max >= count( $this->meetings ) ) {
				$this->meeting_count = count( $this->meetings );
			} else {
				$this->meeting_count = (int) $max;
			}
		} else {
			$this->meeting_count = count( $this->meetings );
		}

		if ( (int) $this->total_meeting_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_meeting_count / (int) $this->pag_num ),
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
	 * Whether there are meeting items available in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_has_zoom_meetings()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_meeting() {
		if ( $this->meeting_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next meeting item and iterate index.
	 *
	 * @since 1.0.0
	 *
	 * @return object The next meeting item to iterate over.
	 */
	public function next_meeting() {
		$this->current_meeting++;
		$this->meeting = $this->meetings[ $this->current_meeting ];

		return $this->meeting;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.0.0
	 */
	public function rewind_meetings() {
		$this->current_meeting = -1;
		if ( $this->meeting_count > 0 ) {
			$this->meeting = $this->meetings[0];
		}
	}

	/**
	 * Whether there are meeting items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_zoom_meeting()} as part of the while loop
	 * that controls iteration inside the meeting loop, eg:
	 *     while ( bp_zoom_meeting() ) { ...
	 *
	 * @since 1.0.0
	 *
	 * @see bp_zoom_meeting()
	 *
	 * @return bool True if there are more meeting items to show,
	 *              otherwise false.
	 */
	public function user_meetings() {
		if ( ( $this->current_meeting + 1 ) < $this->meeting_count ) {
			return true;
		} elseif ( ( $this->current_meeting + 1 ) === $this->meeting_count ) {

			/**
			 * Fires right before the rewinding of meeting posts.
			 *
			 * @since 1.0.0
			 */
			do_action( 'bp_zoom_meeting_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_meetings();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current meeting item inside the loop.
	 *
	 * Used by {@link bp_the_zoom_meeting()} to set up the current meeting item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current meeting item.
	 *
	 * @since 1.0.0
	 *
	 * @see bp_the_zoom_meeting()
	 */
	public function the_meeting() {

		$this->in_the_loop = true;
		$this->meeting     = $this->next_meeting();

		if ( is_array( $this->meeting ) ) {
			$this->meeting = (object) $this->meeting;
		}

		// Loop has just started.
		if ( 0 === $this->current_meeting ) {

			/**
			 * Fires if the current meeting item is the first in the activity loop.
			 *
			 * @since 1.0.0
			 */
			do_action( 'bp_zoom_meeting_loop_start' );
		}
	}
}
