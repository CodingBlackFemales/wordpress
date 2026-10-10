<?php
require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );

class Revisionary_Deletion_List_Table extends WP_Posts_List_Table {
	private $post_types = [];

	public function __construct($args = []) {
		global $revisionary;

		parent::__construct([
			'plural' => 'posts',
			'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
		]);

		if ( isset( $args['post_types'] ) )
			$this->post_types = $args['post_types'];
		else
			$this->post_types = array_keys($revisionary->enabled_post_types);
		
		$omit_types = ['forum', 'topic', 'reply'];
		$this->post_types = array_diff( $this->post_types, $omit_types );

		add_filter('manage_revisionary-deletion_columns', [$this, 'rvy_pending_list_register_columns']);

		add_action('manage_posts_custom_column', [$this, 'rvy_pending_custom_col'], 10, 2);
		add_action('manage_pages_custom_column', [$this, 'rvy_pending_custom_col'], 10, 2);

		if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
			// Don't allow MA to change revision author display. Authors taxonomy storage is only for application to published post.
			global $multiple_authors_addon;
			remove_action('the_post', [$multiple_authors_addon, 'fix_post'], 10);
		}
	}

	function do_query( $q = false ) {
		global $wp_query;
		
		if ( false === $q ) $q = $_GET;													//phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if (isset($q['type']) && in_array( $q['type'], $this->post_types ) ) {
			$q['post_type'] = $q['type'];
		} else {
			$q['post_type'] = $this->post_types;
		}

		$q['posts_per_page'] = -1;														// phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page

		if (isset($q['m']) && strlen($q['m']) == 6) {
			$q['date_query'] = [
				'column' => 'post_modified',
				'year' => substr($q['m'], 0, 4),
				'month' => substr($q['m'], 4)
			];

			unset($q['m']);
		}

		if (!isset( $q['orderby'])) {
			$q['orderby'] = 'modified';
		}

		if (!isset( $q['order'] )) {
			$q['order'] = 'DESC';
		}

		// use Pages setting
		$q['posts_per_page'] = (int) get_user_option("edit_page_per_page");
		
		if ( empty( $qr['posts_per_page'] ) || $q['posts_per_page'] < 1 )
			$q['posts_per_page'] = 20;

		add_filter('posts_clauses_request', [$this, 'fltPostsClausesRequest'], 10, 3);

		if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
			remove_action('pre_get_posts', ['MultipleAuthors\\Classes\\Query', 'action_pre_get_posts']);
			remove_filter('posts_where', ['MultipleAuthors\\Classes\\Query', 'filter_posts_where'], 10, 2);
			remove_filter('posts_join', ['MultipleAuthors\\Classes\\Query', 'filter_posts_join'], 10, 2);
			remove_filter('posts_groupby', ['MultipleAuthors\\Classes\\Query', 'filter_posts_groupby'], 10, 2);
		}

		if (!empty($_REQUEST['s'])) {															//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$q['s'] = sanitize_text_field(wp_unslash($_REQUEST['s']));										//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$wp_query = new WP_Query($q);															//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		do_action('revisionary_deletion_queue_done');

		// prevent default display of all revisions
		if (!$wp_query->posts) {
			$wp_query->posts = [true];
		}

		remove_filter('posts_clauses_request', [$this, 'fltPostsClausesRequest'], 10, 3);
	}

	function fltPostsClausesRequest( $clauses, $_wp_query, $args = []) {
		global $wpdb;

		$deletion_requests = (array) get_option('rvy_deletion_requests');

		$clauses['join'] = "INNER JOIN $wpdb->postmeta req_date ON req_date.meta_key = '_rvy_deletion_request_date' AND req_date.post_id = $wpdb->posts.ID";

		$id_csv = implode("','", array_map('intval', (array) $deletion_requests));
		$clauses['where'] .= " AND $wpdb->posts.ID IN ('$id_csv')";

		return $clauses;
	}

	function rvy_pending_list_register_columns( $columns ) {
		$arr = [
			'cb' => '<input type="checkbox" />', 
			'title' => __('Post', 'revisionary'), 
			'post_type' => esc_html__('Post Type', 'revisionary'), 
			'author' => __('Requested By', 'revisionary'),
			'date' => __('Request Date', 'revisionary'),
		];

		return $arr;
	}

	function rvy_pending_custom_col( $column_name, $post_id ) {
		if ( ! $post = get_post( $post_id ) )
			return;
		
		$request_url = add_query_arg($_REQUEST,rvy_admin_url('admin.php?page=revisionary-q'));	//phpcs:ignore WordPress.Security.NonceVerification.Recommended

		switch ($column_name) {
			case 'post_type':
				$post_type = get_post_field('post_type', $post_id);

				if ( $type_obj = get_post_type_object( $post_type ) ) {
					$link = add_query_arg('type', $type_obj->name, $request_url);
					echo "<a href='" . esc_url($link) . "'>" . esc_html($type_obj->labels->singular_name) . "</a>";
				} else {
					echo esc_html("($post_type)");
				}

				break;
		} // end switch
	}

	public function prepare_items() {
		global $wp_query, $per_page;

		$this->do_query();
		
		//  use Pages setting
		$per_page = $this->get_items_per_page( 'edit_page_per_page' );								//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$total_items = $wp_query->found_posts;

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page' => $per_page
		]);
	}

	public function no_items() {
		$post_type = 'page';
		
		if (isset($_REQUEST['post_status']) && 'trash' === sanitize_key($_REQUEST['post_status']))	//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo esc_html(get_post_type_object( $post_type )->labels->not_found_in_trash);
		else
			echo esc_html(get_post_type_object( $post_type )->labels->not_found);
	}

	/**
	 *
	 * @return array
	 */
	protected function get_views() {
		return [];
	}

	/**
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = [];

		$actions['clear_deletion_request'] = esc_html__('Clear Request');
		$actions['apply_deletion'] = esc_html__('Delete Permanently');

		return $actions;
	}

	protected function categories_dropdown( $post_type ) {
	}

	
	protected function extra_tablenav( $which ) {
?>
		<div class="alignleft actions">
		</div>
<?php
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	/**
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'title'    => 'title',
			'post_type' => 'post_type',
			'author'	=> 'author',
			'date'     => 'req_date.meta_value',
		];
	}
	
	// Overriding parent class method here to make column sort link double as filter clearance (todo: jQuery?)
	public function print_column_headers( $with_id = true ) {		
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$_SERVER['REQUEST_URI'] = (isset($_SERVER['REQUEST_URI'])) 				//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		? str_replace('&&', '&', wp_unslash($_SERVER['REQUEST_URI'])) 			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		: '';

		if (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])) {
			$current_url = set_url_scheme( esc_url(esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) . esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) ));
			$current_url = remove_query_arg( 'paged', $current_url );
		} else {
			$current_url = '';
		}

		if ( isset( $_GET['orderby'] ) ) {										//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_orderby = sanitize_key($_GET['orderby']);					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$current_orderby = '';
		}

		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . esc_html__( 'Select All' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				if ('req_date.meta_value' == $orderby) {
					$orderby = 'date';
				}

				if ( $current_orderby === $orderby ) {
					$order   = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'col' : '';
			$id    = $with_id ? $column_key : '';

			if ( ! empty( $class ) ) {
				$class = join( ' ', $class );
			}

			echo "<" . esc_attr($tag) . " scope='" . esc_attr($scope) . "' id='" . esc_attr($id) . "' class='" . esc_attr($class) . "'>";
			
			$current_url = str_replace('#038;', '&', $current_url);
			$current_url = remove_query_arg('orderby', $current_url);
			$current_url = remove_query_arg('order', $current_url);

			if ( isset( $sortable[ $column_key ] ) ) {
				$remove_arg = ('post_type' == $column_key) ? 'type' : $column_key;

				// kevinB modification: make column sort links double as filter clearance
				// (If results are already filtered by column, first header click clears the filter, second click applies sorting)

				if (!empty($_REQUEST[$remove_arg])) {							//phpcs:ignore WordPress.Security.NonceVerification.Recommended

					// use post status and post type column headers to reset filter, but not for sorting
					$_url = remove_query_arg($remove_arg, $current_url);

					echo '<a href="' . esc_url($_url) . '"><span>' . esc_html($column_display_name) . '</span><span class="sorting-indicator"></span></a>';
				} else {
					echo '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . esc_html($column_display_name) . '</span><span class="sorting-indicator"></span></a>';
				}
			}

			echo "</" . esc_attr($tag) .">";
		}
	}

	public function column_title( $post, $simple_link = false ) {
		$can_edit_post = current_user_can( 'edit_post', $post->ID) || $simple_link;

		echo "<strong>";

		$title = _draft_or_post_title($post);

		if ( $can_edit_post && $post->post_status != 'trash' && $edit_link = get_edit_post_link( $post->ID )) {
			printf(
				'<a class="row-title" href="%s" aria-label="%s">%s%s</a>',
				$edit_link,														//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				/* translators: %s: post title */
				esc_attr( sprintf( esc_html__( '&#8220;%s&#8221; (Edit)' ), $title ) ),
				'',
				esc_attr($title)
			);
		} else {
			echo esc_html($title);
		}

		echo "</strong>\n";
	}

	public function column_date( $post ) {
		if (!$time = get_post_meta($post->ID, '_rvy_deletion_request_date', true)) {
			return;
		}	
		
		$date_str = gmdate( 'Y-m-d H:i:s', $time + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ));

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			$h_time = sprintf( esc_html__( '%s ago' ), human_time_diff( $time ) );
		} else {
			$h_time = gmdate( esc_html__( 'Y/m/d g:i a', 'revisionary' ), strtotime($date_str) );
			$h_time = str_replace( ' am', '&nbsp;am', $h_time );
			$h_time = str_replace( ' pm', '&nbsp;pm', $h_time );
			$h_time = str_replace( ' ', '<br />', $h_time );
		}

		echo '<abbr title="' 
		. esc_attr($date_str) . '">' 
		. apply_filters( 'post_date_column_time', $h_time, $post, 'date' ) 		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		. '</abbr>';
	}
	
	protected function apply_edit_link( $url, $label ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html($label)
		);
	}

	public function column_author( $post ) {
		// Just track single post_author for revision. Authors taxonomy is applied to revise

		$request_url = add_query_arg($_REQUEST, rvy_admin_url('admin.php?page=revisionary-q'));		//phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = ['author' => get_the_author_meta( 'ID' )];
		$this->apply_edit_link( add_query_arg('author', $args['author'], $request_url), get_the_author() );
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @since 4.3.0
	 * @access protected
	 *
	 * @param object $post        Post being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions output for posts.
	 */

	protected function handle_row_actions( $post, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$post_type_object = get_post_type_object($post->post_type);

		$can_edit_post    = current_user_can( 'edit_post', $post->ID );

		$actions          = array();
		$title            = _draft_or_post_title();

		if ( is_post_type_viewable( $post_type_object ) ) {
			$status_obj = get_post_status_object($post->post_status);

			if (!empty($status_obj->public) || !empty($status_obj->private)) {
				$actions['view'] = sprintf(
					'<a href="%1$s" rel="bookmark" title="%2$s" aria-label="%2$s">%3$s</a>',
					get_permalink( $post->ID ),
					/* translators: %s: post title */
					esc_attr( esc_html__( 'View published post', 'revisionary' ) ),
					esc_html__( 'View' )
				);
			} else {
				$actions['view'] = sprintf(
					'<a href="%1$s" rel="bookmark" title="%2$s" aria-label="%2$s">%3$s</a>',
					get_preview_post_link( $post->ID ),
					/* translators: %s: post title */
					esc_attr( esc_html__( 'View published post', 'revisionary' ) ),
					esc_html_x( 'Preview', 'verb' )
				);
			}
		}

		if ( current_user_can( 'delete_post', $post->ID ) ) {
			$redirect_arg = ( ! empty($_REQUEST['rvy_redirect']) ) 					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? "&rvy_redirect=" . esc_url_raw(wp_unslash($_REQUEST['rvy_redirect'])) 			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';
			
			$url = wp_nonce_url(rvy_admin_url("admin.php?page=rvy-revisions&amp;post={$post->ID}&amp;action=clear_deletion_request$redirect_arg"), 'deletion-requests');
			$actions['clear_deletion_request'] = "<a href='" . esc_url($url) . "'>" . esc_html__('Clear Request') . '</a>';

			$redirect_arg = ( ! empty($_REQUEST['rvy_redirect']) ) 					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? "&rvy_redirect=" . esc_url_raw(wp_unslash($_REQUEST['rvy_redirect'])) 			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';
			
			$url = wp_nonce_url(rvy_admin_url("admin.php?page=rvy-revisions&amp;post={$post->ID}&amp;action=apply_deletion$redirect_arg"), 'deletion-requests');
			$actions['apply_deletion'] = "<a href='" . esc_url($url) . "'>" . esc_html__('Delete') . '</a>';
		}

		$actions = apply_filters('revisionary_deletion_queue_row_actions', $actions, $post);

		return $this->row_actions( $actions );
	}

	// override default nonce field
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-revision-queue' );
		}
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
			<?php
		endif;
		$this->extra_tablenav( $which );

		if (!empty($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = str_replace('#038;', '&', esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
		}

		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
		<?php
	}
}
