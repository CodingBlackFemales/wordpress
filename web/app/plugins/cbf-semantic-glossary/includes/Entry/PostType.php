<?php
/**
 * Register the glossary entry post type.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PostType.
 *
 * Entries are global (shared by every post that references them), so editing
 * one changes many posts at once. They have their own capability type (see
 * Capabilities), so who may create, edit and publish them is decided per role;
 * by default that is Editors and above.
 */
final class PostType {

	/**
	 * Post type name.
	 */
	const NAME = 'glossary_term';

	/**
	 * REST base for the core post endpoints.
	 */
	const REST_BASE = 'glossary-terms';

	/**
	 * Meta key holding the ordered name pairs.
	 */
	const FORMS_META = '_glossary_forms';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_block_editor' ), 10, 2 );

		// Entries saved or deleted by any route invalidate the per-request cache.
		add_action( 'save_post_' . self::NAME, array( Repository::class, 'flush' ) );
		add_action( 'deleted_post', array( Repository::class, 'flush' ) );
	}


	/**
	 * Register the post type and its meta.
	 */
	public static function register(): void {
		register_post_type( self::NAME, self::args() );

		register_post_meta(
			self::NAME,
			self::FORMS_META,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( Forms::class, 'normalise' ),
				'auth_callback'     => fn ( $allowed, $key, $post_id ) => current_user_can( 'edit_post', $post_id ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'term' => array( 'type' => 'string' ),
								'abbr' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}


	/**
	 * Entries are edited on a purpose-built classic screen, not in blocks.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        Post type.
	 */
	public static function use_block_editor( bool $use_block_editor, string $post_type ): bool {
		return $post_type === self::NAME ? false : $use_block_editor;
	}


	/**
	 * Post type arguments.
	 *
	 * @return array<string, mixed>
	 */
	private static function args(): array {
		return array(
			'labels'           => self::labels(),
			'description'      => __( 'Terms defined once and referenced from any post.', 'cbf-semantic-glossary' ),
			'public'           => false,
			'show_ui'          => true,
			'show_in_menu'     => true,
			'show_in_rest'     => true,
			'rest_base'        => self::REST_BASE,
			'menu_icon'        => 'dashicons-book-alt',
			'menu_position'    => 25,
			'supports'         => array( 'custom-fields', 'revisions' ),
			'capability_type'  => Capabilities::TYPE,
			'map_meta_cap'     => true,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'delete_with_user' => false,
		);
	}


	/**
	 * Labels.
	 *
	 * @return array<string, string>
	 */
	private static function labels(): array {
		return array(
			'name'                  => _x( 'Glossary', 'post type general name', 'cbf-semantic-glossary' ),
			'singular_name'         => _x( 'Term', 'post type singular name', 'cbf-semantic-glossary' ),
			'menu_name'             => _x( 'Glossary', 'admin menu', 'cbf-semantic-glossary' ),
			'all_items'             => __( 'All Terms', 'cbf-semantic-glossary' ),
			'add_new'               => __( 'Add New', 'cbf-semantic-glossary' ),
			'add_new_item'          => __( 'Add New Term', 'cbf-semantic-glossary' ),
			'edit_item'             => __( 'Edit Term', 'cbf-semantic-glossary' ),
			'new_item'              => __( 'New Term', 'cbf-semantic-glossary' ),
			'view_item'             => __( 'View Term', 'cbf-semantic-glossary' ),
			'search_items'          => __( 'Search Terms', 'cbf-semantic-glossary' ),
			'not_found'             => __( 'No terms found.', 'cbf-semantic-glossary' ),
			'not_found_in_trash'    => __( 'No terms found in Trash.', 'cbf-semantic-glossary' ),
			'item_published'        => __( 'Term published.', 'cbf-semantic-glossary' ),
			'item_updated'          => __( 'Term updated.', 'cbf-semantic-glossary' ),
			'filter_items_list'     => __( 'Filter terms list', 'cbf-semantic-glossary' ),
			'items_list_navigation' => __( 'Terms list navigation', 'cbf-semantic-glossary' ),
			'items_list'            => __( 'Terms list', 'cbf-semantic-glossary' ),
		);
	}
}
