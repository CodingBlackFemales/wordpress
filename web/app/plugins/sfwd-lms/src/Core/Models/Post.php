<?php
/**
 * This class provides the easy way to operate a post.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Models;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Traits\Memoizable;
use WP_Post;

/**
 * Post model class.
 *
 * @since 4.6.0
 */
abstract class Post extends Model {
	use Memoizable;

	/**
	 * Post.
	 *
	 * @since 4.5.0
	 *
	 * @var WP_Post
	 */
	protected $post;

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

			$model->fill( $attributes );
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
	 * Returns a post type.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return $this->post->post_type;
	}

	/**
	 * Returns whether a post type is the same as the given one.
	 *
	 * @since 4.7.0
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	public function is_post_type( string $post_type ): bool {
		return $post_type === $this->post->post_type;
	}

	/**
	 * Returns whether a post type is the same as the given one (compared by key).
	 *
	 * @since 4.7.0
	 *
	 * @param LDLMS_Post_Types::* $post_type_key Post type key.
	 *
	 * @return bool
	 */
	public function is_post_type_by_key( $post_type_key ): bool {
		return $post_type_key === LDLMS_Post_Types::get_post_type_key( $this->post->post_type );
	}

	/**
	 * Returns a post title.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_title(): string {
		return get_the_title( $this->post );
	}

	/**
	 * Returns a post permalink.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		return (string) get_permalink( $this->post );
	}

	/**
	 * Returns a post content.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_content(): string {
		return get_the_content( null, false, $this->post );
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
				'orderby'     => 'post__in',
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
	 * Finds by meta.
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
	 * Returns a model (post) setting.
	 *
	 * @since 4.6.0
	 *
	 * @param string $setting_key Setting key.
	 *
	 * @return mixed
	 */
	public function get_setting( string $setting_key ) {
		/**
		 * Filters model setting.
		 *
		 * @since 4.6.0
		 *
		 * @param mixed  $setting_value Setting value.
		 * @param string $setting_key   Setting value.
		 * @param Post   $model         Model.
		 *
		 * @return bool True if it's a parent model, false otherwise.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_setting',
			$this->memoize(
				function() use ( $setting_key ) {
					return learndash_get_setting( $this->get_id(), $setting_key );
				}
			),
			$setting_key,
			$this
		);
	}

	/**
	 * Returns model (post) settings.
	 *
	 * @since 4.6.0
	 *
	 * @return mixed[]
	 */
	public function get_settings(): array {
		/**
		 * Filters model settings.
		 *
		 * @since 4.6.0
		 *
		 * @param mixed[] $settings Settings.
		 * @param Post    $model    Model.
		 *
		 * @return mixed[] Settings.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_settings',
			$this->memoize(
				function(): array {
					return (array) learndash_get_setting( $this->get_post() );
				}
			),
			$this
		);
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
	 * Creates a list of models from posts.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post[] $posts Posts.
	 *
	 * @return static[]
	 */
	public static function create_many_from_posts( array $posts ): array {
		return self::map_posts_to_models( $posts );
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
