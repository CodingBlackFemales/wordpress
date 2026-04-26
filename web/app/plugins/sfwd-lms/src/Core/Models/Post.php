<?php
/**
 * This class provides the easy way to operate a post.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use WP_Post;
use WP_User;
use LearnDash_Custom_Label;

/**
 * Post model class.
 *
 * @since 4.6.0
 */
abstract class Post extends Model {
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
				function ( array $meta_values ) {
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
	 * Returns a post type key.
	 *
	 * @since 4.21.0
	 *
	 * @return LDLMS_Post_Types::*|string
	 */
	public function get_post_type_key(): string {
		return LDLMS_Post_Types::get_post_type_key( $this->get_post_type() );
	}

	/**
	 * Returns a post type label.
	 *
	 * @since 4.24.0
	 *
	 * @param bool $is_lowercase Whether to return the label in lowercase. Default is false.
	 *
	 * @return string
	 */
	public function get_post_type_label( bool $is_lowercase = false ): string {
		$post_type_key = $this->get_post_type_key();

		if ( $is_lowercase ) {
			return LearnDash_Custom_Label::label_to_lower( $post_type_key );
		}

		return LearnDash_Custom_Label::get_label( $post_type_key );
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
	 * Returns a post edit link.
	 *
	 * @since 4.19.0
	 *
	 * @return string
	 */
	public function get_edit_post_link(): string {
		return (string) get_edit_post_link( $this->post );
	}

	/**
	 * Returns a post delete link.
	 *
	 * @since 4.19.0
	 *
	 * @return string
	 */
	public function get_delete_post_link(): string {
		return (string) get_delete_post_link( $this->post );
	}

	/**
	 * Returns a post author ID.
	 *
	 * @since 4.24.0
	 *
	 * @return int
	 */
	public function get_post_author_id(): int {
		return Cast::to_int( $this->post->post_author );
	}

	/**
	 * Returns a post content.
	 *
	 * @since 4.6.0
	 * @since 4.21.0 Added $raw parameter.
	 *
	 * @param bool $raw Whether to return raw content or not. Default is false.
	 *
	 * @return string
	 */
	public function get_content( bool $raw = false ): string {
		$content = get_the_content( null, false, $this->post );

		if ( ! $raw ) {
			return apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- It's a WordPress core filter.
		}

		return $content;
	}

	/**
	 * Returns the number of comments for a post.
	 *
	 * @since 4.24.0
	 *
	 * @return int
	 */
	public function get_comments_number(): int {
		return Cast::to_int( get_comments_number( $this->post ) );
	}

	/**
	 * Returns whether the post comments are open.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function comments_open(): bool {
		return comments_open( $this->post );
	}

	/**
	 * Returns the comments link.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_comments_link(): string {
		return (string) get_comments_link( $this->post );
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
			return [];
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
				'post_status' => [
					'publish',
					'future',
					'draft',
					'pending',
					'private',
					'inherit',
					'trash',
				],
			)
		);

		return self::map_posts_to_models( $posts );
	}

	/**
	 * Returns first child.
	 *
	 * @since 4.19.0
	 *
	 * @return static
	 */
	public function get_first_child(): ?self {
		if ( ! $this->is_parent() ) {
			return null;
		}

		/**
		 * Posts.
		 *
		 * @var WP_Post[] $posts
		 */
		$posts = get_children(
			[
				'post_parent' => $this->post->ID,
				'post_type'   => static::get_allowed_post_types(),
				'numberposts' => 1,
				'post_status' => [
					'publish',
					'future',
					'draft',
					'pending',
					'private',
					'inherit',
					'trash',
				],
			]
		);

		if ( empty( $posts ) ) {
			return null;
		}

		$posts = array_values( $posts );

		return static::create_from_post( $posts[0] );
	}

	/**
	 * Sets a post meta value.
	 *
	 * @since 4.25.0
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 *
	 * @return void
	 */
	public function set_meta( string $key, $value ): void {
		update_post_meta( $this->post->ID, $key, $value );

		$this->setAttribute( $key, $value );
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

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		try {
			$model = self::create_from_post( $post );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return $model;
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
	 * It's more efficient to use the getAttribute method as the attributes are cached.
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
		 * @return mixed Setting value.
		 */
		return apply_filters(
			'learndash_model_setting',
			learndash_get_setting( $this->get_id(), $setting_key ),
			$setting_key,
			$this
		);
	}

	/**
	 * Returns model (post) settings.
	 *
	 * It's more efficient to use the getAttributes method as the attributes are cached.
	 *
	 * @since 4.24.0
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$settings = learndash_get_setting( $this->get_id() );

		// Extra check to be safe.
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		/**
		 * Filters model settings.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string,mixed>  $settings Settings.
		 * @param Post   $model         Model.
		 *
		 * @return array<string,mixed> Settings.
		 */
		return apply_filters(
			'learndash_model_settings',
			$settings,
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

	/**
	 * Returns a WP_User object or a user ID mapped according to the given user.
	 *
	 * @since 4.8.0
	 *
	 * @param WP_User|int|null $user          The user ID or WP_User. If null or empty, the current user is used if $supports_null is false.
	 * @param bool             $supports_null Whether to return null if the given user is null or empty.
	 *
	 * @return ( $supports_null is true ? WP_User|int|null : WP_User|int )
	 */
	protected function map_user( $user, bool $supports_null = false ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$user_id = Cast::to_int( $user );

		return $user_id > 0
			? $user_id
			: ( $supports_null ? null : wp_get_current_user() );
	}
}
