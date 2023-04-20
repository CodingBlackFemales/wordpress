<?php
/**
 * LearnDash Admin Import Posts.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Posts' ) &&
	! class_exists( 'Learndash_Admin_Import_Posts' )
) {
	/**
	 * Class LearnDash Admin Import Posts.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Posts extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Posts;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $post_type    Post Type.
		 * @param int                                 $user_id      User ID. All posts are attached to this user.
		 * @param string                              $home_url     The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $post_type,
			int $user_id,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->post_type = $post_type;
			$this->user_id   = $user_id;

			parent::__construct( $home_url, $file_handler, $logger );
		}

		/**
		 * Imports Posts.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			foreach ( $this->get_file_lines() as $item ) {
				/**
				 * Item.
				 *
				 * @phpstan-var array{
				 *     wp_post: array{ID: int, post_author: int},
				 *     wp_post_meta: array<string, mixed>,
				 *     wp_post_terms: array<string, int[]>,
				 *     wp_post_permalink: string
				 * } $item
				 *
				 * @var array $item Item.
				 */

				$this->processed_items_count++;

				$is_quiz = in_array(
					$this->post_type,
					array(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUESTION ),
					),
					true
				);

				$post_id = $is_quiz
					? self::get_new_post_id_by_old_post_id( $item['wp_post']['ID'] )
					: wp_insert_post(
						$this->map_post_data( $item['wp_post'] )
					);

				if ( empty( $post_id ) ) {
					continue;
				}

				$this->imported_items_count++;

				if ( $is_quiz ) {
					update_post_meta(
						$post_id,
						Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID,
						$item['wp_post']['post_author']
					);
				}

				$new_post = get_post( $post_id );

				$this->update_content_after_insertion( $new_post, $item['wp_post_permalink'] );
				$this->update_post_meta( $new_post, $item['wp_post_meta'], $is_quiz );
				$this->update_meta_after_insertion( $new_post );
				$this->update_post_terms( $new_post->ID, $item['wp_post_terms'] );

				// remove course steps metadata.
				if ( $this->post_type === LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) ) {
					delete_post_meta( $new_post->ID, 'ld_course_steps' );
					delete_post_meta( $new_post->ID, '_ld_course_steps_count' );
				}

				Learndash_Admin_Import::clear_wpdb_query_cache();
			}
		}

		/**
		 * Maps post data to insert.
		 *
		 * @since 4.3.0
		 *
		 * @param array $data Exported data.
		 *
		 * @return array
		 */
		protected function map_post_data( array $data ): array {
			$old_id = intval( $data['ID'] );
			unset( $data['ID'] );

			$post_data = $data;

			$post_data['meta_input']  = array(
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID => $old_id,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID => $post_data['post_author'],
			);
			$post_data['post_author'] = $this->user_id;

			// fields with media.
			$post_data['post_content'] = $this->replace_media_from_content( $post_data['post_content'] );
			$post_data['post_excerpt'] = $this->replace_media_from_content( $post_data['post_excerpt'] );

			return $post_data;
		}

		/**
		 * Updates post content after post is created.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post               The post object.
		 * @param string  $previous_permalink Previous permalink.
		 *
		 * @return void
		 */
		protected function update_content_after_insertion( WP_Post $post, string $previous_permalink ): void {
			// Update the permalink.
			$current_permalink = get_permalink( $post );
			$new_post_content  = str_replace( $previous_permalink, $current_permalink, $post->post_content );
			$new_post_excerpt  = str_replace( $previous_permalink, $current_permalink, $post->post_excerpt );

			// Update the home url.
			$new_post_content = str_replace( $this->home_url_previous, $this->home_url_current, $new_post_content );
			$new_post_excerpt = str_replace( $this->home_url_previous, $this->home_url_current, $new_post_excerpt );

			$args = array(
				'ID'           => $post->ID,
				'post_content' => $new_post_content,
				'post_excerpt' => $new_post_excerpt,
			);

			wp_update_post( $args );
		}

		/**
		 * Updates post meta after post is created.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post The post object.
		 *
		 * @return void
		 */
		protected function update_meta_after_insertion( WP_Post $post ): void {
			// update LD settings with media.
			foreach ( $this->get_learndash_fields_with_media() as $field ) {
				$field_value = learndash_get_setting( $post, $field );

				if ( ! empty( $field_value ) ) {
					learndash_update_setting(
						$post,
						$field,
						$this->replace_media_from_content( $field_value )
					);
				}
			}
		}

		/**
		 * Updates post's meta.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Post $post    The post object.
		 * @param array   $metas   Array of metas.
		 * @param bool    $is_quiz Whether the post is part of a quiz import.
		 *
		 * @return void
		 */
		protected function update_post_meta( WP_Post $post, array $metas, bool $is_quiz ): void {
			$quiz_meta_keys_list = $this->get_quiz_meta_keys_to_update();

			foreach ( $metas as $meta_key => $meta_values ) {
				if ( $is_quiz && ! isset( $quiz_meta_keys_list[ $meta_key ] ) ) {
					continue;
				}

				foreach ( $meta_values as $meta_value ) {
					update_post_meta(
						$post->ID,
						$this->map_meta_key( $meta_key, $post->ID ),
						$this->map_meta_value( $meta_value, $meta_key )
					);
				}
			}
		}


		/**
		 * Returns quiz meta keys that should be updated.
		 *
		 * @since 4.3.0
		 *
		 * @return array Array of quiz meta keys that should be updated. [meta_key => true]
		 */
		private function get_quiz_meta_keys_to_update(): array {
			return array(
				'_thumbnail_id'   => true,
				'course_id'       => true,
				'lesson_id'       => true,
				'_ld_certificate' => true,
			);
		}

		/**
		 * Maps meta key.
		 *
		 * @since 4.3.0
		 *
		 * @param string  $meta_key Meta key.
		 * @param integer $post_id  Post ID.
		 *
		 * @return string Meta key.
		 */
		protected function map_meta_key( string $meta_key, int $post_id ): string {
			if (
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) === $this->post_type &&
				1 === preg_match( '/learndash_group_users_(\d*)/', $meta_key )
			) {
				$meta_key = "learndash_group_users_$post_id";
			}

			return $meta_key;
		}

		/**
		 * Maps meta value.
		 *
		 * @since 4.3.0
		 *
		 * @param mixed  $meta_value Meta value.
		 * @param string $meta_key   Meta key.
		 *
		 * @return mixed Meta value.
		 */
		protected function map_meta_value( $meta_value, string $meta_key ) {
			if ( '_thumbnail_id' === $meta_key ) {
				$meta_value = $this->get_new_post_id_by_old_post_id( (int) $meta_value );
			}

			return $meta_value;
		}

		/**
		 * Updates post's terms.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $post_id Post ID.
		 * @param array $terms   Term ids grouped by taxonomies.
		 *
		 * @return void
		 */
		protected function update_post_terms( int $post_id, array $terms ): void {
			foreach ( $terms as $taxonomy_name => $term_ids ) {
				if ( empty( $term_ids ) ) {
					continue;
				}

				$new_term_ids = get_terms(
					array(
						'taxonomy'   => $taxonomy_name,
						'fields'     => 'ids',
						'meta_key'   => Learndash_Admin_Import::META_KEY_IMPORTED_FROM_TERM_ID,
						'meta_value' => $term_ids,
						'hide_empty' => false,
					)
				);

				if ( ! is_array( $new_term_ids ) || empty( $new_term_ids ) ) {
					continue;
				}

				wp_set_post_terms( $post_id, $new_term_ids, $taxonomy_name );
			}
		}
	}
}
