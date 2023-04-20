<?php
/**
 * LearnDash Admin Export Posts.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export_Chunkable' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Posts' ) &&
	interface_exists( 'Learndash_Admin_Export_Has_Media' ) &&
	! class_exists( 'Learndash_Admin_Export_Posts' )
) {
	/**
	 * Class LearnDash Admin Export Posts.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Posts extends Learndash_Admin_Export_Chunkable implements Learndash_Admin_Export_Has_Media {
		use Learndash_Admin_Import_Export_Posts;

		const EXCLUDED_META_KEYS = array(
			'_wp_old_slug',
			'_wp_old_date',
			'_edit_lock',
			'_edit_last',
		);

		/**
		 * Additional query args to filter posts.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		protected $additional_query_args = array();

		/**
		 * Post type taxonomies.
		 *
		 * @since 4.3.0
		 *
		 * @var string[]
		 */
		private $taxonomy_names;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $post_type    Post type.
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $post_type,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->post_type      = $post_type;
			$this->taxonomy_names = get_object_taxonomies( $post_type );

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns data to export by chunks.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			if (
				isset( $this->additional_query_args['include'] ) &&
				empty( $this->additional_query_args['include'] )
			) {
				return '';
			}

			$query_args = array_merge(
				array(
					'post_type'      => $this->post_type,
					'post_status'    => 'any',
					'posts_per_page' => $this->get_chunk_size_rows(), // phpcs:ignore WordPress.WP.PostsPerPage
					'offset'         => $this->offset_rows,
				),
				$this->additional_query_args
			);

			/**
			 * Filters export post query args.
			 *
			 * @since 4.3.0
			 *
			 * @param array $query_args Query args.
			 *
			 * @return array Query args.
			 */
			$query_args = apply_filters( 'learndash_export_post_query_args', $query_args );

			$posts = get_posts( $query_args );

			if ( empty( $posts ) ) {
				return '';
			}

			$result = '';

			foreach ( $posts as $post ) {
				$wp_post = (array) $post;
				unset( $wp_post['guid'] );

				$post_data = array(
					'wp_post'           => $wp_post,
					'wp_post_permalink' => get_permalink( $post->ID ),
					'wp_post_meta'      => $this->get_post_metadata( $post ),
					'wp_post_terms'     => $this->get_post_terms( $post ),
				);

				/**
				 * Filters the post object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $post_data Post object.
				 *
				 * @return array Post object.
				 */
				$post_data = apply_filters( 'learndash_export_post_object', $post_data );

				$result .= wp_json_encode( $post_data ) . PHP_EOL;
			}

			$this->increment_offset_rows();

			return $result;
		}

		/**
		 * Returns media IDs.
		 *
		 * @since 4.3.0
		 *
		 * @return array.
		 */
		public function get_media(): array {
			if (
				isset( $this->additional_query_args['include'] ) &&
				empty( $this->additional_query_args['include'] )
			) {
				return array();
			}

			$query_args = array_merge(
				array(
					'post_type'      => $this->post_type,
					'post_status'    => 'any',
					'posts_per_page' => $this->get_chunk_size_media(), // phpcs:ignore WordPress.WP.PostsPerPage
					'offset'         => $this->offset_media,
				),
				$this->additional_query_args
			);

			/**
			 * Filters export post media query args.
			 *
			 * @since 4.3.0
			 *
			 * @param array $query_args Query args.
			 *
			 * @return array Query args.
			 */
			$query_args = apply_filters( 'learndash_export_post_media_query_args', $query_args );

			$posts = get_posts( $query_args );

			if ( empty( $posts ) ) {
				return array();
			}

			$result = array();

			foreach ( $posts as $post ) {
				$media_ids = array_merge(
					array(
						get_post_thumbnail_id( $post ),
					),
					$this->get_media_ids_from_string( $post->post_content ),
					$this->get_media_ids_from_string( $post->post_excerpt ),
					$this->get_special_media_ids( $post )
				);

				/**
				 * Filters post media ids to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array   $media_ids Array of media IDs to export.
				 * @param WP_Post $post      The WP_Post object.
				 */
				$media_ids = apply_filters( 'learndash_export_post_media_ids', $media_ids, $post );

				$result = array_merge(
					$result,
					array_values(
						array_filter( $media_ids )
					)
				);
			}

			$this->increment_offset_media();

			return $result;
		}

		/**
		 * Returns post's metadata.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post The post.
		 *
		 * @return array The list of post meta.
		 */
		protected function get_post_metadata( WP_Post $post ): array {
			$post_meta = get_post_meta( $post->ID );

			if ( ! is_array( $post_meta ) ) {
				return array();
			}

			$post_meta = array_diff_key(
				$post_meta,
				array_flip( $this->get_excluded_meta_keys( $post ) )
			);

			if ( empty( $post_meta ) ) {
				return array();
			}

			$result = array();

			foreach ( $post_meta as $meta_key => $meta_values ) {
				$result[ $meta_key ] = array_map( 'maybe_unserialize', $meta_values );
			}

			return $result;
		}

		/**
		 * Returns all post terms.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post The post.
		 *
		 * @return array Term IDs grouped by a taxonomy name.
		 */
		protected function get_post_terms( WP_Post $post ): array {
			$result = array_map(
				function(): array {
					return array();
				},
				array_flip( $this->taxonomy_names )
			);

			foreach ( $this->taxonomy_names as $taxonomy_name ) {
				$post_terms = get_the_terms( $post->ID, $taxonomy_name );

				if ( ! $post_terms || is_wp_error( $post_terms ) ) {
					continue;
				}

				$result[ $taxonomy_name ] = wp_list_pluck( $post_terms, 'term_id' );
			}

			return $result;
		}

		/**
		 * Returns a list of post meta keys that should be excluded from export.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post The post.
		 *
		 * @return array The list of meta keys that should be excluded from export.
		 */
		protected function get_excluded_meta_keys( WP_Post $post ): array {
			/**
			 * Filters the list of post meta keys that should be excluded from export.
			 *
			 * @since 4.3.0
			 *
			 * @param array   $excluded_keys The current meta keys blacklist.
			 * @param WP_Post $post          The post to be exported.
			 *
			 * @return array The meta keys blacklist array.
			 */
			return apply_filters(
				'learndash_export_post_excluded_meta_keys',
				self::EXCLUDED_META_KEYS,
				$post
			);
		}

		/**
		 * Returns media ids depending on the post type.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post Post.
		 *
		 * @return array
		 */
		protected function get_special_media_ids( WP_Post $post ): array {
			$fields = $this->get_learndash_fields_with_media();

			if ( empty( $fields ) ) {
				return array();
			}

			$special_media_ids = array();

			foreach ( $fields as $field ) {
				$special_media_ids = array_merge(
					$special_media_ids,
					$this->get_media_ids_from_string(
						learndash_get_setting( $post, $field )
					)
				);
			}

			return $special_media_ids;
		}
	}
}
