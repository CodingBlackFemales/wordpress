<?php
/**
 * This class provides the easy way to operate a post.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Model' ) ) {
	/**
	 * Model class.
	 *
	 * @since 4.5.0
	 */
	abstract class Learndash_Model {
		/**
		 * Post.
		 *
		 * @since 4.5.0
		 *
		 * @var WP_Post
		 */
		protected $post;

		/**
		 * Mapped post meta fields.
		 *
		 * @since 4.5.0
		 *
		 * @var array<string,mixed>
		 */
		protected $attributes = array();

		/**
		 * Constructor.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		final protected function __construct() {
			// Disallow overriding the constructor in child classes and instantiating.
		}

		/**
		 * Returns allowed post types.
		 *
		 * @since 4.5.0
		 *
		 * @return string[]
		 */
		abstract public static function get_allowed_post_types(): array;

		/**
		 * Creates a model from a post.
		 *
		 * @since 4.5.0
		 *
		 * @param WP_Post $post Post.
		 *
		 * @throws InvalidArgumentException If the post has a wrong post type.
		 *
		 * @return static
		 */
		public static function create_from_post( WP_Post $post ): self {
			$model = new static();

			$model->set_post( $post );

			// Map post meta fields to attributes.

			$post_meta = get_post_meta( $post->ID );

			if ( is_array( $post_meta ) && ! empty( $post_meta ) ) {
				$attributes = array_map(
					function( array $meta_values ) {
						$meta_values = array_map(
							function ( $meta_value ) {
								return maybe_unserialize( $meta_value );
							},
							$meta_values
						);

						return count( $meta_values ) > 1 ? $meta_values : $meta_values[0];
					},
					$post_meta
				);

				$model->set_attributes( $attributes );
			}

			return $model;
		}

		/**
		 * Returns a post ID.
		 *
		 * @since 4.5.0
		 *
		 * @return int
		 */
		public function get_id(): int {
			return $this->post->ID;
		}

		/**
		 * Returns a post property.
		 *
		 * @since 4.5.0
		 *
		 * @return WP_Post
		 */
		public function get_post(): WP_Post {
			return $this->post;
		}

		/**
		 * Returns true if an attribute exists. Otherwise, false.
		 *
		 * @since 4.5.0
		 *
		 * @param string $attribute_name Attribute name.
		 *
		 * @return bool
		 */
		public function has_attribute( string $attribute_name ): bool {
			return isset( $this->attributes[ $attribute_name ] );
		}

		/**
		 * Removes an attribute.
		 *
		 * @since 4.5.0
		 *
		 * @param string $attribute_name Attribute name.
		 *
		 * @return void
		 */
		public function remove_attribute( string $attribute_name ): void {
			unset( $this->attributes[ $attribute_name ] );
		}

		/**
		 * Removes all attributes.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function clear_attributes(): void {
			$this->attributes = array();
		}

		/**
		 * Returns all attributes.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,mixed>
		 */
		public function get_attributes(): array {
			return $this->attributes;
		}

		/**
		 * Returns an attribute value or null if not found.
		 *
		 * @since 4.5.0
		 *
		 * @param string $attribute_name Attribute name.
		 * @param mixed  $default        Default value.
		 *
		 * @return mixed
		 */
		public function get_attribute( string $attribute_name, $default = null ) {
			return $this->attributes[ $attribute_name ] ?? $default;
		}

		/**
		 * Returns an attribute value or null if not found.
		 *
		 * @since 4.5.0
		 *
		 * @param string $attribute_name  Attribute name.
		 * @param mixed  $attribute_value Attribute value.
		 *
		 * @return void
		 */
		public function set_attribute( string $attribute_name, $attribute_value ): void {
			$this->attributes[ $attribute_name ] = $attribute_value;
		}

		/**
		 * Sets multiple attributes.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $attributes Attributes. Keys are attribute names, values are attribute values.
		 *
		 * @return void
		 */
		public function set_attributes( array $attributes ): void {
			foreach ( $attributes as $attribute_name => $attribute_value ) {
				$this->set_attribute( (string) $attribute_name, $attribute_value );
			}
		}

		/**
		 * Returns true if it's a parent post, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		public function is_parent(): bool {
			/**
			 * Filters whether a model is a parent.
			 *
			 * @since 4.5.0
			 *
			 * @param bool    $is_parent True if it's a parent model, false otherwise.
			 * @param WP_Post $post      Post.
			 *
			 * @return bool True if it's a parent model, false otherwise.
			 */
			return apply_filters(
				'learndash_model_is_parent',
				0 === $this->post->post_parent,
				$this->post
			);
		}

		/**
		 * Returns a parent model.
		 *
		 * @since 4.5.0
		 *
		 * @return static|null
		 */
		public function get_parent(): ?self {
			if ( $this->is_parent() ) {
				return null;
			}

			return static::find( $this->post->post_parent );
		}

		/**
		 * Returns child models.
		 *
		 * @since 4.5.0
		 *
		 * @return static[]
		 */
		public function get_children(): array {
			if ( ! $this->is_parent() ) {
				return array();
			}

			/**
			 * Posts.
			 *
			 * @var WP_Post[] $posts
			 */
			$posts = get_children(
				array(
					'post_parent' => $this->post->ID,
					'post_type'   => static::get_allowed_post_types(),
					'numberposts' => -1,
				)
			);

			return self::map_posts_to_models( $posts );
		}

		/**
		 * Finds by ids.
		 *
		 * @since 4.5.0
		 *
		 * @param int[] $post_ids Post IDs.
		 *
		 * @return static[]
		 */
		public static function find_many( array $post_ids ): array {
			if ( empty( $post_ids ) ) {
				return array();
			}

			$posts = get_posts(
				array(
					'post__in'    => wp_parse_id_list( $post_ids ),
					'post_type'   => static::get_allowed_post_types(),
					'post_status' => 'any',
					'numberposts' => -1,
				)
			);

			return self::map_posts_to_models( $posts );
		}

		/**
		 * Finds by id.
		 *
		 * @since 4.5.0
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return static|null
		 */
		public static function find( int $post_id ): ?self {
			if ( $post_id <= 0 ) {
				return null;
			}

			$models = self::find_many( array( $post_id ) );

			return ! empty( $models ) ? $models[0] : null;
		}

		/**
		 * Finds by meta
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $meta Meta list. Keys are meta keys, values are meta values.
		 *
		 * @return static[]
		 */
		public static function find_many_by_meta( array $meta = array() ): array {
			if ( empty( $meta ) ) {
				return array();
			}

			$meta_query = array();
			foreach ( $meta as $key => $value ) {
				$meta_query[] = array(
					'key'   => $key,
					'value' => $value,
				);
			}

			$posts = get_posts(
				array(
					'post_type'   => static::get_allowed_post_types(),
					'post_status' => 'any',
					'numberposts' => -1,
					'meta_query'  => $meta_query,
				)
			);

			return self::map_posts_to_models( $posts );
		}

		/**
		 * Maps posts into models.
		 *
		 * @since 4.5.0
		 *
		 * @param WP_Post[] $posts Posts.
		 *
		 * @return static[]
		 */
		protected static function map_posts_to_models( array $posts ): array {
			$posts = array_filter(
				$posts,
				function ( $post ) {
					return $post instanceof WP_Post;
				}
			);

			if ( empty( $posts ) ) {
				return array();
			}

			$models = array();

			foreach ( $posts as $post ) {
				try {
					$models[] = static::create_from_post( $post );
				} catch ( InvalidArgumentException $e ) { // @phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Do nothing.
				}
			}

			return $models;
		}

		/**
		 * Sets a post property.
		 *
		 * @since 4.5.0
		 *
		 * @param WP_Post $post Post.
		 *
		 * @throws InvalidArgumentException If the post has a wrong post type.
		 *
		 * @return void
		 */
		private function set_post( WP_Post $post ): void {
			$allowed_post_types = static::get_allowed_post_types();

			/**
			 * Filters model allowed post types.
			 *
			 * @since 4.5.0
			 *
			 * @param string[] $allowed_post_types Allowed post types.
			 * @param string   $model_class        Model class.
			 * @param WP_Post  $post               Post.
			 *
			 * @return string[] Allowed post types.
			 */
			$allowed_post_types = apply_filters(
				'learndash_model_allowed_post_types',
				$allowed_post_types,
				static::class,
				$post
			);

			if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
				throw new InvalidArgumentException(
					sprintf(
						'Invalid post type "%s". Allowed post types: "%s".',
						$post->post_type,
						implode( ', ', $allowed_post_types )
					)
				);
			}

			$this->post = $post;
		}
	}
}
