<?php

/**
 * Related Posts Helper Functions
 */

namespace BuddyBossTheme;

if ( ! class_exists( '\BuddyBossTheme\RelatedPostsHelper' ) ) {

	class RelatedPostsHelper {

		protected $_is_active = false;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'after_switch_theme', array( $this, 'crp_create_index' ) );
		}

		public function set_active() {
			$this->_is_active = true;
		}

		public function is_active() {
			return $this->_is_active;
		}

		public function crp_create_index() {
			global $wpdb;

			$wpdb->hide_errors();
			$mysql_server_type    = $wpdb->db_server_info();
			$mysql_server_version = $wpdb->db_version();

			$is_mariadb = false;
			if ( stristr( $mysql_server_type, 'mariadb' ) ) {
				$is_mariadb = true;

				// Account for MariaDB version being prefixed with '5.5.5-' on older PHP 8.0.15 versions.
				if ( '5.5.5' === $mysql_server_version && PHP_VERSION_ID < 80016 ) {
					// Strip the '5.5.5-' prefix and set the version to the correct value.
					$mysql_server_type    = preg_replace( '/^5\.5\.5-(.*)/', '$1', $mysql_server_type );
					$mysql_server_version = preg_replace( '/[^0-9.].*/', '', $mysql_server_type );
				}
			}

			if ( $is_mariadb && version_compare( 10.3, $mysql_server_version, '<=' ) ) {
				$table_engine = 'InnoDB';
			} elseif ( ! $is_mariadb && version_compare( 5.6, $mysql_server_version, '<=' ) ) {
				$table_engine = 'InnoDB';
			} else {
				$table_engine = 'MyISAM';
			}

			$current_engine = $wpdb->get_row(
				"
				SELECT engine FROM INFORMATION_SCHEMA.TABLES
				WHERE table_schema=DATABASE()
				AND table_name = '{$wpdb->posts}'
			"
			);

			if ( isset( $current_engine->engine ) && $current_engine->engine !== $table_engine ) {
				$wpdb->query( "ALTER TABLE {$wpdb->posts} ENGINE = {$table_engine};" ); // WPCS: unprepared SQL OK.
			}

			if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related'" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related (post_title, post_content);" );
			}
			if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_title'" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related_title (post_title);" );
			}
			if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_content'" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related_content (post_content);" );
			}

			$wpdb->show_errors();
		}

		public function get_related_posts( $args = array() ) {
			global $wpdb, $post;

			$fields       = '';
			$where        = '';
			$orderby      = '';
			$limits       = '';
			$match_fields = '';

			$limit_num = buddyboss_theme_get_option( 'blog_related_posts_limit' );
			$limit     = ! empty( $limit_num ) ? $limit_num : 5;

			$defaults = array(
				'postid'        => false,
				'limit'         => $limit,
				'offset'        => 0,
				'match_content' => true,
			);

			$args = wp_parse_args( $args, $defaults );

			$source_post = ( empty( $args['postid'] ) ) ? $post : get_post( $args['postid'] );

			$limit  = ! empty( $args['limit'] ) ? $args['limit'] : $limit;
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;

			$post_types = (array) $source_post->post_type;

			$match_fields         = array(
				'post_title',
			);
			$match_fields_content = array(
				$source_post->post_title,
			);
			if ( $args['match_content'] ) {
				$match_fields[] = 'post_content';

				$content                = $source_post->post_content;
				$output                 = strip_tags( strip_shortcodes( $content ) );
				$output                 = wp_trim_words( $output, 500 );
				$match_fields_content[] = $output;
			}
			$match_fields = implode( ',', $match_fields );
			$stuff        = implode( ' ', $match_fields_content );

			$time_difference = get_option( 'gmt_offset' );
			$now             = gmdate( 'Y-m-d H:i:s', ( time() + ( $time_difference * 3600 ) ) );

			if ( is_int( $source_post->ID ) ) {

				$fields = " $wpdb->posts.ID ";

				$match = $wpdb->prepare( ' AND MATCH (' . $match_fields . ") AGAINST ('%s') ", $stuff );

				$now_clause = $wpdb->prepare( " AND $wpdb->posts.post_date < '%s' ", $now );

				$where  = $match;
				$where .= $now_clause;
				$where .= " AND $wpdb->posts.post_status = 'publish' ";
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID != %d ", $source_post->ID );

				$where .= " AND $wpdb->posts.post_type IN ('" . join( "', '", $post_types ) . "') ";

				$limits .= $wpdb->prepare( ' LIMIT %d, %d ', $offset, $limit );

				if ( ! empty( $orderby ) ) {
					$orderby = 'ORDER BY ' . $orderby;
				}

				$sql = "SELECT DISTINCT $fields FROM $wpdb->posts WHERE 1=1 $where $orderby $limits";

				$results = $wpdb->get_results( $sql );

			} else {
				$results = false;
			}
			return $results;
		}

	}

}
