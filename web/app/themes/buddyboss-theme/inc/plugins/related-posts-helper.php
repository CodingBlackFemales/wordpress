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

				// Due to limit in FTS phrase or proximity search depending on engine like in InnoDB,
				// So restricted and truncated the search string.
				$excerpt_length         = apply_filters( 'bb_related_posts_excerpt_length', 100 );
				$output                 = self::get_the_excerpt( $source_post, $excerpt_length );
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

		/**
		 * Function to create an excerpt for the post.
		 *
		 * @since 2.6.40
		 *
		 * @param int|\WP_Post $post            Post ID or WP_Post instance.
		 * @param int|string   $excerpt_length  Length of the excerpt in words.
		 *
		 * @return string Excerpt
		 */
		public static function get_the_excerpt( $post, $excerpt_length = 0 ) {
			$post = get_post( $post );
			if ( empty( $post ) ) {
				return '';
			}

			$content = $post->post_content;
			$output  = strip_shortcodes( $content );
			$output  = wp_strip_all_tags( $output, true );
			$output  = self::strip_stopwords( $output );

			/**
			 * Filters excerpt generated before it is trimmed.
			 *
			 * @since 2.6.40
			 *
			 * @param string   $output         Formatted excerpt.
			 * @param \WP_Post $post           Source Post instance.
			 * @param int      $excerpt_length Length of the excerpt.
			 * @param string   $content        Content that is used to create the excerpt.
			 */
			$output = apply_filters( 'bb_related_posts_excerpt_pre_trim', $output, $post, $excerpt_length, $content );

			if ( 0 === (int) $excerpt_length || 100 < (int) $excerpt_length ) {
				$excerpt_length = 100;
			}

			if ( $excerpt_length > 0 ) {
				$output = wp_trim_words( $output, $excerpt_length );
			}

			if ( post_password_required( $post ) ) {
				$output = __( 'There is no excerpt because this is a protected post.', 'buddyboss-theme' );
			}

			/**
			 * Filters generated excerpt.
			 *
			 * @since 2.6.40
			 *
			 * @param string   $output         Formatted excerpt.
			 * @param \WP_Post $post           Source Post instance.
			 * @param int      $excerpt_length Length of the excerpt.
			 */
			return apply_filters( 'bb_related_posts_excerpt', $output, $post, $excerpt_length );
		}

		/**
		 * Strip stopwords from a text.
		 *
		 * @since 2.6.40
		 *
		 * @param string|array $subject The string or an array with strings to search and replace. .
		 * @param string|array $search  The pattern to search for. It can be either a string or an array with strings.
		 * @param string|array $replace The string or an array with strings to replace.
		 *
		 * @return string Filtered string
		 */
		public static function strip_stopwords( $subject = '', $search = '', $replace = '' ) {

			$pattern = array();
			if ( empty( $search ) ) {
				$get_search_stopwords = new \ReflectionMethod( 'WP_Query', 'get_search_stopwords' );
				$get_search_stopwords->setAccessible( true );
				$search = $get_search_stopwords->invoke( new \WP_Query() );

				array_push( $search, 'from', 'where' );
			}

			foreach ( (array) $search as $s ) {
				$pattern[] = '/\b' . $s . '\b/ui';
			}
			$output = preg_replace( $pattern, $replace, $subject );
			$output = preg_replace( '/\b[a-z\-]\b/i', '', $output );
			$output = preg_replace( '/\s+/', ' ', $output );

			return $output;
		}
	}

}
