<?php
/**
 * Job Alerts Admin functionality.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Job_Manager_Alerts\Admin class.
 *
 * @package WP_Job_Manager_Alerts
 */
class Admin {

	use Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_filter( 'manage_job_alert_posts_columns', [ $this, 'add_job_alert_columns' ] );
		add_action( 'manage_job_alert_posts_custom_column', [ $this, 'populate_job_alert_columns' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'modify_job_alert_post_state' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 20 );
		add_filter( 'quick_edit_show_taxonomy', [ $this, 'hide_taxonomy_from_quick_edit' ], 10, 3 );
		add_action( 'add_meta_boxes_job_alert', [ $this, 'hide_taxonomies_from_edit' ], 100 );
		add_filter( 'posts_search', [ $this, 'search_by_email_where' ], 10, 2 );
		add_filter( 'posts_join', [ $this, 'search_by_email_join' ], 10, 2 );

		Personal_Data_Handler::instance();
		Settings::instance();
		Alert_Migrator::instance();
	}

	/**
	 * Check if the query is for the job_alert screen.
	 *
	 * @param \WP_Query $query
	 *
	 * @return bool
	 */
	private function is_job_alert_admin_query( $query ) {
		return $query->is_main_query() && is_admin() && 'job_alert' === $query->get( 'post_type' );
	}

	/**
	 * Add WHERE terms to the job alert search to allow searching by author email address.
	 *
	 * @access private.
	 *
	 * @param string    $where SQL WHERE clauses.
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return string
	 */
	public function search_by_email_where( $where, $query ) {
		global $wpdb;

		$search = $query->get( 's' );
		if ( $this->is_job_alert_admin_query( $query ) && ! empty( $search ) ) {

			$condition = " {$wpdb->users}.user_email LIKE '%$search%' OR job_guest_user.post_title LIKE '%$search%'";

			if ( ! empty( $where ) ) {
				$where = preg_replace( '/^ AND /', '', $where );
				$where = " AND ( {$where} OR ( {$condition} ) )";
			} else {
				$where = " AND ( {$condition} )";
			}
		}

		return $where;
	}

	/**
	 * JOIN the users table for the job alert search to allow searching by author email address.
	 *
	 * @access private.
	 *
	 * @param string    $join SQL JOIN clauses.
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return string
	 */
	public function search_by_email_join( $join, $query ) {
		global $wpdb;

		if ( $this->is_job_alert_admin_query( $query ) && ! empty( $query->get( 's' ) ) ) {
			$join .= " LEFT JOIN {$wpdb->users} ON {$wpdb->posts}.post_author = {$wpdb->users}.ID";
			$join .= " LEFT JOIN {$wpdb->posts} job_guest_user ON {$wpdb->posts}.post_parent = job_guest_user.ID";
		}

		return $join;
	}

	/**
	 * Enqueue scripts for job alerts admin.
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, [ 'edit-job_alert', 'job_alert', 'job_listing_page_job-manager-settings' ], true ) ) {
			wp_enqueue_style( 'job_manager_alerts_admin_css', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/dist/css/admin.css', [], JOB_MANAGER_ALERTS_VERSION );
		}
	}

	/**
	 * Customize columns in the job alerts table.
	 *
	 * @access private
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_job_alert_columns( $columns ) {
		$new_columns = [
			'cb'         => $columns['cb'],
			'title'      => $columns['title'],
			'terms'      => __( 'Search Terms', 'wp-job-manager-alerts' ),
			'alert_user' => __( 'User', 'wp-job-manager-alerts' ),
		];
		unset( $columns['author'] );

		return array_merge( $new_columns, $columns );
	}

	/**
	 * Populate job alert columns.
	 *
	 * @access private
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function populate_job_alert_columns( $column, $post_id ) {

		switch ( $column ) {
			case 'terms':
				$terms_content = $this->get_terms_column( $post_id );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function above.
				echo $terms_content;

				break;
			case 'alert_user':
				$column_content = $this->get_user_column( $post_id );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the function above.
				echo $column_content;
				break;
			default:
				break;
		}
	}

	/**
	 * Display draft post state label as 'Disabled'.
	 *
	 * @access private
	 *
	 * @param array    $post_states Post states.
	 * @param \WP_Post $post Post object.
	 *
	 * @return array
	 */
	public function modify_job_alert_post_state( $post_states, $post ) {
		if ( 'job_alert' === $post->post_type && 'draft' === $post->post_status ) {
			$post_states = [ __( 'Disabled', 'wp-job-manager-alerts' ) ];
		}

		return $post_states;
	}

	/**
	 * Hide taxonomy meta boxes from the quick edit section.
	 *
	 * @access private
	 *
	 * @param bool   $show
	 * @param string $taxonomy
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function hide_taxonomy_from_quick_edit( $show, $taxonomy, $post_type ) {
		if ( 'job_alert' === $post_type && in_array(
			$taxonomy,
			[
				'job_listing_category',
				'job_listing_type',
				'job_listing_region',
				'job_listing_tag',
			],
			true
		) ) {
			$show = false;
		}

		return $show;
	}

	/**
	 * Hide taxonomy meta boxes from the edit screen.
	 *
	 * @access private
	 */
	public function hide_taxonomies_from_edit() {

		$post_type = 'job_alert';
		foreach ( [ 'job_listing_category', 'job_listing_type', 'job_listing_region', 'job_listing_tag' ] as $taxonomy ) {
			remove_meta_box( $taxonomy . 'div', $post_type, 'side' );
		}
	}

	/**
	 * Returns the content for the 'User' column in alerts.
	 *
	 * @param int $alert_id The alert id.
	 *
	 * @return string
	 */
	private function get_user_column( int $alert_id ) : string {
		global $wp_list_table;

		$alert_post = get_post( $alert_id );

		if ( 0 === (int) $alert_post->post_author ) {
			$alert_user = get_post( $alert_post->post_parent );

			if ( empty( $alert_user ) ) {
				return '';
			}

			$guest_email = $alert_user->post_title;

			$url_args = [
				's'           => $guest_email,
				'post_status' => 'all',
				'paged'       => 1,
				'post_type'   => 'job_alert',
			];

			$url = add_query_arg( $url_args, 'edit.php' );

			$column_content = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $url ),
				esc_html( $guest_email )
			);
		} else {
			$column_content = $wp_list_table->column_author( $alert_post ) ?? '';
		}

		return $column_content;
	}

	/**
	 * Returns the 'Search Terms' column content.
	 *
	 * @param int $alert_id The alert id.
	 *
	 * @return string
	 */
	private function get_terms_column( int $alert_id ): string {
		$terms = Post_Types::get_alert_search_term_names( $alert_id );

		if ( ! empty( $terms['keywords'][0] ) ) {
			$terms['keywords'][0] = sprintf( '"%s"', $terms['keywords'][0] );
		}

		$tags = [];
		foreach ( $terms as $taxonomy => $term_names ) {
			if ( ! empty( $term_names ) ) {
				foreach ( $term_names as $term ) {
					$tags[] = '<span class="jm-alert__term ' . esc_attr( $taxonomy ) . '">' . esc_html( $term ) . '</span>';
				}
			}
		}

		return implode( ' ', $tags );
	}
}
