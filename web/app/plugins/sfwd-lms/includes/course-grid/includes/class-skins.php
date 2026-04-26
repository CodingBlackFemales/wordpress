<?php
/**
 * Skins class.
 *
 * @since 4.21.4
 *
 * cSpell:ignore pkgd
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid;
use LearnDash\Course_Grid\Utilities;

/**
 * Skins class.
 *
 * @since 4.21.4
 */
class Skins {
	/**
	 * Registered skins.
	 *
	 * @since 4.21.4
	 *
	 * @var array<string, array>
	 */
	private $registered_skins;

	/**
	 * Registered cards.
	 *
	 * @since 4.21.4
	 *
	 * @var array<string, array>
	 */
	private $registered_cards;

	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		$this->register_skins();
		$this->register_cards();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_skin_assets' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_skin_assets' ] );
	}

	/**
	 * Gets default editor fields.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string>
	 */
	public function get_default_editor_fields() {
		/**
		 * Filters the default editor fields.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string> $fields Default editor fields.
		 *
		 * @return array<string> Filtered editor fields.
		 */
		return apply_filters(
			'learndash_course_grid_editor_fields',
			[
				'post_type',
				'per_page',
				'orderby',
				'order',
				'taxonomies',
				'thumbnail',
				'thumbnail_size',
				'ribbon',
				'content',
				'title',
				'title_clickable',
				'description',
				'description_char_max',
				'post_meta',
				'button',
				'pagination',
				'skin',
				'columns',
				'items_per_row',
				'grid_height_equal',
				'font_family_title',
				'font_family_description',
				'font_size_title',
				'font_size_description',
				'font_color_title',
				'font_color_description',
				'background_color_title',
				'background_color_description',
				// Misc
				'class_name',
				'id',
				// Filter
				'filter_search',
				'filter_taxonomies',
				'filter_price',
				'filter_price_min',
				'filter_price_max',
			]
		);
	}

	/**
	 * Registers skins.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_skins() {
		$this->registered_skins = [
			'grid'    => [
				'slug'    => 'grid',
				'label'   => __( 'Grid', 'learndash' ),
				'disable' => [
					'items_per_row',
				],
			],
			'masonry' => [
				'slug'                => 'masonry',
				'label'               => __( 'Masonry', 'learndash' ),
				'disable'             => [
					'items_per_row',
					'grid_height_equal',
				],
				'script_dependencies' => [
					'masonry' => [
						'url'     => LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'lib/masonry/masonry.pkgd.min.js',
						'version' => '4.2.2',
					],
				],
			],
			'list'    => [
				'slug'    => 'list',
				'label'   => __( 'List', 'learndash' ),
				'disable' => [
					'columns',
					'items_per_row',
					'grid_height_equal',
				],
			],
		];
	}

	/**
	 * Registers cards.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_cards() {
		$this->registered_cards = [
			'grid-1' => [
				'label'    => __( 'Grid 1', 'learndash' ),
				'skins'    => [ 'grid', 'masonry' ],
				'elements' => [
					'thumbnail',
					'ribbon',
					'content',
					'title',
					'icon',
					'post_meta',
				],
			],
			'grid-2' => [
				'label'    => __( 'Grid 2', 'learndash' ),
				'skins'    => [ 'grid', 'masonry' ],
				'elements' => [
					'thumbnail',
					'ribbon',
					'content',
					'title',
					'description',
					'post_meta',
					'button',
				],
			],
			'grid-3' => [
				'label'    => __( 'Grid 3', 'learndash' ),
				'skins'    => [ 'grid', 'masonry' ],
				'elements' => [
					'thumbnail',
					'content',
					'title',
					'description',
					'post_meta',
					'button',
				],
			],
			'list-1' => [
				'label'    => __( 'List 1', 'learndash' ),
				'skins'    => [ 'list' ],
				'elements' => [
					'thumbnail',
					'ribbon',
					'content',
					'title',
					'description',
					'post_meta',
					'icon',
					'button',
				],
			],
			'list-2' => [
				'label'    => __( 'List 2', 'learndash' ),
				'skins'    => [ 'list' ],
				'elements' => [
					'thumbnail',
					'ribbon',
					'content',
					'title',
					'description',
					'post_meta',
					'icon',
				],
			],
		];
	}

	/**
	 * Get registered skins.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, array>
	 */
	public function get_skins() {
		/**
		 * Filters the registered skins.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, array> $skins Registered skins.
		 *
		 * @return array<string, array> Filtered skins.
		 */
		return apply_filters( 'learndash_course_grid_skins', $this->registered_skins );
	}

	/**
	 * Gets a particular skin data.
	 *
	 * @since 4.21.4
	 *
	 * @param string $skin Skin slug.
	 *
	 * @return array<string, array>|false|mixed
	 */
	public function get_skin( $skin ) {
		$skin_details = $this->registered_skins[ $skin ] ?? false;

		/**
		 * Filters the skin details.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, array>|false $skin_details Skin details.
		 * @param string                     $skin         Skin slug.
		 *
		 * @return array<string, array>|false|mixed Filtered skin details.
		 */
		return apply_filters( 'learndash_course_grid_skin', $skin_details, $skin );
	}

	/**
	 * Get registered cards.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, array>
	 */
	public function get_cards() {
		/**
		 * Filters the registered cards.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, array> $cards Registered cards.
		 *
		 * @return array<string, array> Filtered cards.
		 */
		return apply_filters( 'learndash_course_grid_cards', $this->registered_cards );
	}

	/**
	 * Gets a particular card data.
	 *
	 * @since 4.21.4
	 *
	 * @param string $card Card slug.
	 *
	 * @return array<string, array>|false|mixed
	 */
	public function get_card( $card ) {
		$card_details = $this->registered_cards[ $card ] ?? false;

		/**
		 * Filters the card details.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, array>|false $card_details Card details.
		 * @param string                     $card         Card slug.
		 *
		 * @return array<string, array>|false|mixed Filtered card details.
		 */
		return apply_filters( 'learndash_course_grid_card', $card_details, $card );
	}

	/**
	 * Enqueues general assets.
	 *
	 * @since 4.21.4
	 *
	 * @param bool $enqueue_script Whether to enqueue the script or not.
	 *
	 * @return void
	 */
	public function enqueue_general_assets( $enqueue_script = true ) {
		$script      = LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'js/script.js';
		$script_file = LEARNDASH_COURSE_GRID_PLUGIN_ASSET_PATH . 'js/script.js';

		if ( file_exists( $script_file ) && $enqueue_script ) {
			wp_enqueue_script( 'learndash', $script, [], LEARNDASH_VERSION, true );

			wp_localize_script(
				'learndash',
				'LearnDash_Course_Grid',
				[
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => [
						'load_posts' => wp_create_nonce( 'ld_cg_load_posts' ),
					],
				]
			);
		}

		$style      = LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'css/style.css';
		$style_file = LEARNDASH_COURSE_GRID_PLUGIN_ASSET_PATH . 'css/style.css';

		if ( file_exists( $style_file ) ) {
			wp_enqueue_style( 'learndash', $style, [], LEARNDASH_VERSION, 'all' );
		}
	}

	/**
	 * Enqueues filter assets.
	 *
	 * @since 4.21.4
	 *
	 * @param bool $enqueue_script Whether to enqueue the script or not.
	 *
	 * @return void
	 */
	public function enqueue_filter_assets( $enqueue_script = true ) {
		$filter_style = Utilities::get_template_url( 'filter/style.css' );

		if ( $filter_style ) {
			wp_enqueue_style( 'learndash-course-grid-filter', $filter_style, [ 'dashicons' ], LEARNDASH_VERSION );
		}
	}

	/**
	 * Enqueues pagination assets.
	 *
	 * @since 4.21.4
	 *
	 * @param bool $enqueue_script Whether to enqueue the script or not.
	 *
	 * @return void
	 */
	public function enqueue_pagination_assets( $enqueue_script = true ) {
		$pagination_style = Utilities::get_pagination_style();

		if ( $pagination_style ) {
			wp_enqueue_style( 'learndash-course-grid-pagination', $pagination_style, [], LEARNDASH_VERSION );
		}
	}

	/**
	 * Parses block tags.
	 *
	 * @since 4.21.4
	 *
	 * @param string $content Post content.
	 *
	 * @return array[]|mixed
	 */
	public function parse_block_tags( $content ) {
		$block_tags = [];

		/**
		 * Extract JSON attributes from Gutenberg block comments. Will match on any block.
		 *
		 * Examples:
		 *
		 * <!-- wp:learndash/ld-course-grid {"skin":"grid","card":"grid-1"} -->
		 * <!-- wp:learndash/ld-course-grid {"columns":3,"skin":"masonry"} -->
		 * <!-- wp:learndash/ld-course-grid {"id":"my-grid","skin":"list"} -->
		 */
		preg_match( '/<!--.*?(\{.*?\}).*?-->/', $content, $matches );

		if ( ! empty( $matches[1] ) ) {
			$block_tags = json_decode( $matches[1], true );
		}

		return $block_tags;
	}

	/**
	 * Parses shortcode tags.
	 *
	 * @since 4.21.4
	 *
	 * @param string $content Post content.
	 *
	 * @return array[]|mixed
	 */
	public function parse_shortcode_tags( $content ) {
		/**
		 * Extract key-value pairs from shortcode attributes.
		 *
		 * Examples:
		 *
		 * [learndash_course_grid skin="grid" card="grid-1"]
		 * [learndash_course_grid columns=3 skin='masonry']
		 * [learndash_course_grid id="my-grid" skin="list" per_page=6]
		 */
		preg_match_all( '/\s(.*?)=(.*?)(?=\s|\])/', $content, $matches );

		$returned_matches = [];
		foreach ( $matches as $group => $match ) {
			foreach ( $match as $key => $value ) {
				$returned_matches[ $group ][ $key ] = trim( str_replace( [ '\'', '"' ], '', $value ) );
			}
		}

		return $returned_matches;
	}

	/**
	 * Parses content shortcodes.
	 *
	 * This method extracts and processes shortcodes from the provided content,
	 * identifying course grids, skins, and cards used in the content.
	 *
	 * @since 4.21.4
	 *
	 * @param string $content The content containing shortcodes.
	 * @param array  $args    Optional. Additional arguments for parsing.
	 *
	 * @return array<string, array> Parsed data including course grids, skins, and cards.
	 */
	public function parse_content_shortcodes( $content, $args = [] ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
		extract( $args );

		if ( ! isset( $course_grids ) ) {
			$course_grids = [];
		}

		if ( ! isset( $skins ) ) {
			$skins = [];
		}

		if ( ! isset( $cards ) ) {
			$cards = [];
		}

		/**
		 * Find all learndash_course_grid shortcode instances in content.
		 *
		 * Examples:
		 *
		 * [learndash_course_grid skin="grid" card="grid-1"]
		 * [learndash_course_grid columns=3 skin='masonry' per_page=6]
		 * [learndash_course_grid id="my-grid" skin="list"]
		 */
		preg_match_all( '/\[learndash_course_grid.*?\]/', $content, $matches );

		foreach ( $matches[0] as $match ) {
			$sub_matches = $this->parse_shortcode_tags( $match );

			$course_grids[] = $sub_matches;

			if ( isset( $sub_matches[1] ) && is_array( $sub_matches[1] ) && in_array( 'skin', $sub_matches[1] ) ) {
				$key = array_search( 'skin', $sub_matches[1] );
				if ( $key !== false ) {
					$skins[] = $sub_matches[2][ $key ];
				}
			} else {
				$skins[] = 'grid';
			}

			if ( isset( $sub_matches[1] ) && is_array( $sub_matches[1] ) && in_array( 'card', $sub_matches[1] ) ) {
				$key = array_search( 'card', $sub_matches[1] );
				if ( $key !== false ) {
					$cards[] = $sub_matches[2][ $key ];
				}
			} else {
				$cards[] = 'grid-1';
			}
		}

		return compact( 'course_grids', 'skins', 'cards' );
	}

	/**
	 * Parses content blocks.
	 *
	 * This method extracts and processes blocks from the provided content,
	 * identifying course grids, skins, and cards used in the content.
	 *
	 * @since 4.21.4
	 *
	 * @param string $content The content containing blocks.
	 * @param array  $args    Optional. Additional arguments for parsing.
	 *
	 * @return array<string, array> Parsed data including course grids, skins, and cards.
	 */
	public function parse_content_blocks( $content, $args ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
		extract( $args );

		/**
		 * Find all learndash/ld-course-grid Gutenberg block instances in content.
		 *
		 * Examples:
		 *
		 * <!-- wp:learndash/ld-course-grid {"skin":"grid","card":"grid-1"} -->
		 * <!-- wp:learndash/ld-course-grid {"columns":3,"skin":"masonry"} -->
		 * <!-- wp:learndash/ld-course-grid {"id":"my-grid","skin":"list"} -->
		 */
		preg_match_all( '/<\!-- wp:learndash\/ld-course-grid.*?-->/', $content, $matches );

		foreach ( $matches[0] as $match ) {
			$block_tags = $this->parse_block_tags( $match );

			$course_grids[] = $block_tags;

			if ( ! empty( $block_tags['skin'] ) ) {
				$skins[] = $block_tags['skin'];
			} else {
				$skins[] = 'grid';
			}

			if ( ! empty( $block_tags['card'] ) ) {
				$cards[] = $block_tags['card'];
			} else {
				$cards[] = 'grid-1';
			}
		}

		return compact( 'course_grids', 'skins', 'cards' );
	}

	/**
	 * Enqueues skin assets.
	 *
	 * @since 4.21.4
	 * @since 4.25.6 Moved asset loading logic to handle_skin_assets().
	 *
	 * @return void
	 */
	public function enqueue_skin_assets() {
		global $post;

		// Initialize shortcode attributes as separate variables.
		$skins        = [];
		$cards        = [];
		$course_grids = [];
		$legacy_v1    = false;

		// Handle content string parsing (original logic).
		$content = $post ? $post->post_content : '';

		// Check widget content to load course grid assets.
		$widgets = wp_get_sidebars_widgets();

		foreach ( $widgets as $sidebar => $widgets_list ) {
			if ( $sidebar === 'wp_inactive_widgets' ) {
				continue;
			}

			foreach ( $widgets_list as $widget ) {
				$widget_id = _get_widget_id_base( $widget );

				preg_match( '/-([0-9]+)$/', $widget, $widget_matches );
				$widget_number = $widget_matches[1] ?? null;

				if ( ! $widget_number ) {
					continue;
				}

				$widget_options = get_option( 'widget_' . $widget_id );

				if (
					! empty( $widget_options[ $widget_number ]['content'] )
					&& has_shortcode( $widget_options[ $widget_number ]['content'], 'learndash_course_grid' )
				) {
					$args = $this->parse_content_shortcodes( $widget_options[ $widget_number ]['content'], compact( 'skins', 'course_grids', 'cards' ) );
					// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
					extract( $args );
				}

				if (
					! empty( $widget_options[ $widget_number ]['content'] )
					&& strpos( $widget_options[ $widget_number ]['content'], '<!-- wp:learndash/ld-course-grid' ) !== false
				) {
					$args = $this->parse_content_blocks( $widget_options[ $widget_number ]['content'], compact( 'skins', 'course_grids', 'cards' ) );
					// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
					extract( $args );
				}

				if (
					! empty( $widget_options[ $widget_number ]['content'] )
					&& $this->has_legacy_v1( $widget_options[ $widget_number ]['content'] )
				) {
					$legacy_v1 = true;
				}
			}
		}

		// Parse the provided content string if it contains course grids.
		if ( ! empty( $content ) ) {
			if ( $this->has_legacy_v1( $content ) ) {
				$legacy_v1 = true;
			}

			if ( has_shortcode( $content, 'learndash_course_grid' ) ) {
				$args = $this->parse_content_shortcodes( $content, compact( 'skins', 'course_grids', 'cards' ) );
				// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
				extract( $args );
			}

			if ( strpos( $content, '<!-- wp:learndash/ld-course-grid' ) !== false ) {
				$args = $this->parse_content_blocks( $content, compact( 'skins', 'course_grids', 'cards' ) );
				// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Extract was used historically, do not change.
				extract( $args );
			}
		}

		// Handle legacy v1 shortcodes that don't have explicit skin/card attributes.
		if (
			$legacy_v1
			&& empty( $skins ) // @phpstan-ignore-line  -- This skins var is potentially extracted from the args above.
		) {
			$skins[] = 'grid';
			$cards[] = 'grid-1';
		}

		$this->handle_skin_assets(
			[
				'skins'        => $skins,
				'cards'        => $cards,
				'course_grids' => $course_grids,
			],
			$legacy_v1
		);
	}

	/**
	 * Handles skin assets for either shortcode attributes or content string.
	 *
	 * @since 4.25.6
	 *
	 * @param array{skin?: string, skins?: array<int, string>, skins?: array<int, string>, card?: string,cards?: array<int, string>, course_grids?: array<int, array<string, mixed>>,} $atts         Shortcode attributes array.
	 * @param bool                                                                                                                                                                     $is_legacy_v1 Whether the content is legacy v1.
	 *
	 * @return void
	 */
	public function handle_skin_assets( $atts, $is_legacy_v1 = false ) {
		// This is only used for a filter below. Do not rely on it for parsing.
		global $post;

		$skins        = $atts['skins'] ?? [];
		$cards        = $atts['cards'] ?? [];
		$course_grids = $atts['course_grids'] ?? [];
		$legacy_v1    = $is_legacy_v1;

		// Handle shortcode attributes array.
		$skin = $atts['skin'] ?? 'grid';
		$card = $atts['card'] ?? 'grid-1';

		$skins[] = $skin;
		$cards[] = $card;

		// Load legacy v1 skin assets if needed.
		if ( $is_legacy_v1 ) {
			$skin       = 'legacy-v1';
			$style_file = Utilities::get_skin_style( $skin );

			if ( $style_file ) {
				wp_enqueue_style( 'learndash-course-grid-skin-' . $skin, $style_file, [], LEARNDASH_VERSION );
			}
		}

		/**
		 * Filters the course grid data to add additional course grids from external sources.
		 *
		 * This filter allows plugins or themes to add their own course grid configurations
		 * that should be considered when loading assets.
		 *
		 * @since 4.21.4
		 *
		 * @param array[]      $extra_course_grids Array of additional course grid configurations.
		 * @param WP_Post|null $post               The current post object or null.
		 *
		 * @return array[] Array of additional course grid configurations to be merged with existing ones.
		 */
		$extra_course_grids = apply_filters( 'learndash_course_grid_post_extra_course_grids', [], $post );

		if ( ! empty( $extra_course_grids ) && is_array( $extra_course_grids ) ) {
			foreach ( $extra_course_grids as $extra_course_grid ) {
				$skins        = array_merge( $skins, $extra_course_grid['skins'] );
				$cards        = array_merge( $cards, $extra_course_grid['cards'] );
				$course_grids = array_merge( $course_grids, $extra_course_grid['course_grids'] );
			}
		}

		$skins = array_unique( $skins );

		foreach ( $skins as $skin ) {
			// Register dependencies.
			$skin_args           = $this->get_skin( $skin );
			$script_dependencies = $skin_args['script_dependencies'] ?? [];
			$style_dependencies  = $skin_args['style_dependencies'] ?? [];

			$script_keys = array_keys( $script_dependencies );
			$script_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$script_keys
			);

			$style_keys = array_keys( $style_dependencies );
			$style_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$style_keys
			);

			foreach ( $script_dependencies as $id => $script ) {
				wp_register_script( 'learndash-course-grid-' . $id, $script['url'], [], $script['version'], true );
			}

			foreach ( $style_dependencies as $id => $style ) {
				wp_register_style( 'learndash-course-grid-' . $id, $style['url'], [], $style['version'] );
			}

			$style_file = Utilities::get_skin_style( $skin );

			if ( $style_file ) {
				wp_enqueue_style( 'learndash-course-grid-skin-' . $skin, $style_file, $style_keys, LEARNDASH_VERSION );
			}

			$script_file = Utilities::get_skin_script( $skin );

			if ( $script_file ) {
				wp_enqueue_script( 'learndash-course-grid-skin-' . $skin, $script_file, $script_keys, LEARNDASH_VERSION, true );
			}

			$this->enqueue_general_assets();
			$this->enqueue_pagination_assets();
			$this->enqueue_filter_assets();
		}

		$cards = array_unique( $cards );

		foreach ( $cards as $card ) {
			// Register dependencies.
			$card_args           = $this->get_card( $card );
			$script_dependencies = $card_args['script_dependencies'] ?? [];
			$style_dependencies  = $card_args['style_dependencies'] ?? [];

			$script_keys = array_keys( $script_dependencies );
			$script_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$script_keys
			);

			$style_keys = array_keys( $style_dependencies );
			$style_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$style_keys
			);

			foreach ( $script_dependencies as $id => $script ) {
				wp_register_script( 'learndash-course-grid-' . $id, $script['url'], [], $script['version'], true );
			}

			foreach ( $style_dependencies as $id => $style ) {
				wp_register_style( 'learndash-course-grid-' . $id, $style['url'], [], $style['version'] );
			}

			$style_file = Utilities::get_card_style( $card );

			if ( $style_file ) {
				wp_enqueue_style( 'learndash-course-grid-card-' . $card, $style_file, $style_keys, LEARNDASH_VERSION );
			}

			$script_file = Utilities::get_card_script( $card );

			if ( $script_file ) {
				wp_enqueue_script( 'learndash-course-grid-card-' . $card, $script_file, $script_keys, LEARNDASH_VERSION, true );
			}
		}

		if (
			! empty( $course_grids )
			&& is_array( $course_grids )
		) {
			/**
			 * Prints scripts or data in the head tag on the front end.
			 */
			add_action(
				'wp_footer',
				function () use ( $course_grids ) {
					$this->enqueue_custom_assets( $course_grids );
				},
				100
			);
		}

		/**
		 * Fires after LearnDash course grid assets have been loaded.
		 *
		 * @since 4.21.4
		 */
		do_action( 'learndash_course_grid_assets_loaded' );
	}

	/**
	 * Enqueues Block Editor Skin Assets.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function enqueue_editor_skin_assets() {
		if ( ! is_admin() ) {
			return;
		}

		global $post;

		$skins = $this->get_skins();

		$skin_ids = [];
		foreach ( $skins as $id => $skin ) {
			// Register dependencies
			$skin_args           = $this->get_skin( $id );
			$script_dependencies = $skin_args['script_dependencies'] ?? [];
			$style_dependencies  = $skin_args['style_dependencies'] ?? [];

			$script_keys = array_keys( $script_dependencies );
			$script_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$script_keys
			);

			$style_keys = array_keys( $style_dependencies );
			$style_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$style_keys
			);

			foreach ( $script_dependencies as $id => $script ) {
				wp_register_script( 'learndash-course-grid-' . $id, $script['url'], [], $script['version'], false );
			}

			foreach ( $style_dependencies as $id => $style ) {
				wp_register_style( 'learndash-course-grid-' . $id, $style['url'], [], $style['version'] );
			}

			$style_file = Utilities::get_skin_style( $id );

			if ( $style_file ) {
				wp_enqueue_style( 'learndash-course-grid-skin-' . $id, $style_file, $style_keys, LEARNDASH_VERSION );
			}

			$script_file = Utilities::get_skin_script( $id );
			if ( $script_file ) {
				$skin_ids[] = 'learndash-course-grid-skin-' . $id;

				wp_enqueue_script( 'learndash-course-grid-skin-' . $id, $script_file, $script_keys, LEARNDASH_VERSION, true );
			}
		}

		// Check and load legacy v1 assets.
		$skin       = 'legacy-v1';
		$style_file = Utilities::get_skin_style( $skin );

		if ( $style_file ) {
			wp_enqueue_style( 'learndash-course-grid-skin-' . $skin, $style_file, [], LEARNDASH_VERSION );
		}

		$cards = $this->get_cards();

		foreach ( $cards as $id => $card ) {
			// Register dependencies
			$skin_args           = $this->get_card( $id );
			$script_dependencies = $card_args['script_dependencies'] ?? [];
			$style_dependencies  = $card_args['style_dependencies'] ?? [];

			$script_keys = array_keys( $script_dependencies );
			$script_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$script_keys
			);

			$style_keys = array_keys( $style_dependencies );
			$style_keys = array_map(
				function ( $id ) {
					return 'learndash-course-grid-' . $id;
				},
				$style_keys
			);

			foreach ( $script_dependencies as $id => $script ) {
				wp_register_script( 'learndash-course-grid-' . $id, $script['url'], [], $script['version'], false );
			}

			foreach ( $style_dependencies as $id => $style ) {
				wp_register_style( 'learndash-course-grid-' . $id, $style['url'], [], $style['version'] );
			}

			$style_file = Utilities::get_card_style( $id );

			if ( $style_file ) {
				wp_enqueue_style( 'learndash-course-grid-card-' . $id, $style_file, $style_keys, LEARNDASH_VERSION );
			}

			$script_file = Utilities::get_card_script( $id );
			if ( $script_file ) {
				wp_enqueue_script( 'learndash-course-grid-card-' . $id, $script_file, $script_keys, LEARNDASH_VERSION, false );
			}
		}

		// Add custom CSS wrapper
		add_action(
			'admin_head',
			function () {
				?>
			<style id="learndash-course-grid-custom-css"></style>
				<?php
			},
			100
		);

		$this->enqueue_general_assets();
		$this->enqueue_pagination_assets( false );
		$this->enqueue_filter_assets( false );

		wp_enqueue_script( 'learndash-course-grid-block-editor-helper', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'assets/js/editor.js', $skin_ids, LEARNDASH_VERSION, true );
	}

	/**
	 * Checks if a post has legacy course grid addon v1 widget.
	 *
	 * @since 4.21.4
	 *
	 * @param string $content Post content.
	 *
	 * @return bool True if v1 widget exists, false otherwise.
	 */
	public function has_legacy_v1( $content ) {
		/**
		 * Check for legacy v1 shortcodes that don't have course_grid="false".
		 *
		 * Examples:
		 *
		 * [ld_course_list]
		 * [ld_lesson_list]
		 * [ld_quiz_list]
		 */
		$has_legacy_shortcode = (
			preg_match( '/\[ld_.*?_list/', $content )
			&& ! preg_match( '/\[ld_.*?_list.*?course_grid=(?:"|\')*false(?:"|\')*/', $content )
		);

		/**
		 * Check for legacy v1 blocks that don't have course_grid":false.
		 *
		 * Examples:
		 *
		 * <!-- wp:learndash/ld-course-list -->
		 * <!-- wp:learndash/ld-lesson-list -->
		 * <!-- wp:learndash/ld-quiz-list -->
		 */
		$has_legacy_block = (
			preg_match( '/<!-- wp:learndash\/ld-.*?-list/', $content )
			&& ! preg_match( '/<!-- wp:learndash\/ld-.*?-list.*?course_grid":false/', $content )
		);

		/**
		 * Check for explicit legacy v1 comment marker.
		 *
		 * Example:
		 *
		 * <!-- LearnDash Course Grid v1 -->
		 */
		$has_legacy_comment = (
			strpos( $content, '<!-- LearnDash Course Grid v1 -->' ) !== false
		);

		return (
			$has_legacy_shortcode
			|| $has_legacy_block
			|| $has_legacy_comment
		);
	}

	/**
	 * Generates custom CSS for a course grid based on provided arguments.
	 *
	 * This method creates custom CSS for styling course grid elements including columns,
	 * equal height settings, fonts, colors, and other visual properties.
	 *
	 * @since 4.21.4
	 *
	 * @param array $args Arguments for CSS generation. Can be a flat array of key-value pairs
	 *                    or a multi-dimensional array where $args[1] contains keys and $args[2] contains values.
	 *
	 * @return string|false Generated CSS string or false if no ID is provided.
	 */
	public function generate_custom_css( $args = [] ) {
		// Parse args first
		if ( isset( $args[1] ) && $args[2] ) {
			$temp_args = [];
			foreach ( $args[1] as $index => $key ) {
				$temp_args[ $key ] = $args[2][ $index ];
			}
			$args = $temp_args;
		}

		// Bail if the element doesn't have ID
		if ( empty( $args['id'] ) ) {
			return false;
		}

		$default_atts = Course_Grid::instance()->shortcodes->learndash_course_grid->get_default_atts();

		$skin              = ! empty( $args['skin'] ) ? $args['skin'] : $default_atts['skin'];
		$columns           = ! empty( $args['columns'] ) ? $args['columns'] : $default_atts['columns'];
		$grid_height_equal = ! empty( $args['grid_height_equal'] ) ? $args['grid_height_equal'] : $default_atts['grid_height_equal'];
		$grid_height_equal = filter_var( $grid_height_equal, FILTER_VALIDATE_BOOLEAN );

		$font_family_title      = ! empty( $args['font_family_title'] ) ? $args['font_family_title'] : $default_atts['font_family_title'];
		$font_size_title        = ! empty( $args['font_size_title'] ) ? $args['font_size_title'] : $default_atts['font_size_title'];
		$font_color_title       = ! empty( $args['font_color_title'] ) ? $args['font_color_title'] : $default_atts['font_color_title'];
		$background_color_title = ! empty( $args['background_color_title'] ) ? $args['background_color_title'] : $default_atts['background_color_title'];

		$font_family_description      = ! empty( $args['font_family_description'] ) ? $args['font_family_description'] : $default_atts['font_family_description'];
		$font_size_description        = ! empty( $args['font_size_description'] ) ? $args['font_size_description'] : $default_atts['font_size_description'];
		$font_color_description       = ! empty( $args['font_color_description'] ) ? $args['font_color_description'] : $default_atts['font_color_description'];
		$background_color_description = ! empty( $args['background_color_description'] ) ? $args['background_color_description'] : $default_atts['background_color_description'];
		$font_color_ribbon            = ! empty( $args['font_color_ribbon'] ) ? $args['font_color_ribbon'] : $default_atts['font_color_ribbon'];
		$background_color_ribbon      = ! empty( $args['background_color_ribbon'] ) ? $args['background_color_ribbon'] : $default_atts['background_color_ribbon'];
		$font_color_icon              = ! empty( $args['font_color_icon'] ) ? $args['font_color_icon'] : $default_atts['font_color_icon'];
		$background_color_icon        = ! empty( $args['background_color_icon'] ) ? $args['background_color_icon'] : $default_atts['background_color_icon'];
		$font_color_button            = ! empty( $args['font_color_button'] ) ? $args['font_color_button'] : $default_atts['font_color_button'];
		$background_color_button      = ! empty( $args['background_color_button'] ) ? $args['background_color_button'] : $default_atts['background_color_button'];

		ob_start();
		?>

		<?php // Columns ?>
		<?php if ( in_array( $skin, [ 'grid', 'masonry' ] ) ) : ?>
			<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ); ?> {
			grid-template-columns: repeat( <?php echo esc_html( $columns ); ?>, minmax( 0, 1fr ) );
		}
		<?php endif; ?>

		<?php // Grid Height Equal ?>
		<?php if ( $grid_height_equal && 'grid' == $skin ) : ?>
			<?php echo '#' . esc_html( $args['id'] ) . ' .grid' . ' > .item > .post'; ?>,
			<?php echo '#' . esc_html( $args['id'] ) . ' .grid' . ' > .item .content'; ?> {
				display: flex;
				flex-direction: column;
				height: 100%;
			}


			<?php echo '#' . esc_html( $args['id'] ) . ' .grid' . ' > .item .content > *:last-child'; ?> {
				margin-top: auto;
			}
		<?php endif; ?>

		<?php // Styles ?>
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-title'; ?>,
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-title *'; ?> {
			<?php if ( ! empty( $font_family_title ) ) : ?>
				font-family: <?php echo html_entity_decode( $font_family_title ); ?>;
			<?php endif; ?>

			<?php if ( ! empty( $font_size_title ) ) : ?>
				font-size: <?php echo esc_html( $font_size_title ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-title'; ?> {
			<?php if ( ! empty( $background_color_title ) ) : ?>
				padding: 10px;
				border-radius: 5px;
				background-color: <?php echo esc_html( $background_color_title ); ?>;
			<?php endif; ?>

			<?php if ( ! empty( $font_color_title ) ) : ?>
				color: <?php echo esc_html( $font_color_title ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-title *'; ?> {
			<?php if ( ! empty( $font_color_title ) ) : ?>
				color: <?php echo esc_html( $font_color_title ); ?>;
			<?php endif; ?>
		}

		<?php // Description ?>

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-content'; ?> {
			<?php if ( ! empty( $font_family_description ) ) : ?>
				font-family: <?php echo html_entity_decode( $font_family_description ); ?>;
			<?php endif; ?>

			<?php if ( ! empty( $font_size_description ) ) : ?>
				font-size: <?php echo esc_html( $font_size_description ); ?>;
			<?php endif; ?>

			<?php if ( ! empty( $background_color_description ) ) : ?>
				padding: 10px;
				border-radius: 5px;
				background-color: <?php echo esc_html( $background_color_description ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .content .entry-content *'; ?> {
			<?php if ( ! empty( $font_color_description ) ) : ?>
				color: <?php echo esc_html( $font_color_description ); ?>;
			<?php endif; ?>
		}

		<?php // Elements ?>

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .ribbon'; ?> {
			<?php if ( ! empty( $background_color_ribbon ) ) : ?>
				background-color: <?php echo esc_html( $background_color_ribbon ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .ribbon'; ?> ,
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .ribbon *'; ?> {
			<?php if ( ! empty( $font_color_ribbon ) ) : ?>
				color: <?php echo esc_html( $font_color_ribbon ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .icon'; ?> {
			<?php if ( ! empty( $background_color_icon ) ) : ?>
				background-color: <?php echo esc_html( $background_color_icon ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .icon'; ?> ,
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .icon *'; ?> {
			<?php if ( ! empty( $font_color_icon ) ) : ?>
				color: <?php echo esc_html( $font_color_icon ); ?>;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .button'; ?> ,
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .button *'; ?> {
			<?php if ( ! empty( $background_color_button ) ) : ?>
				background-color: <?php echo esc_html( $background_color_button ); ?>;
				border: none;
			<?php endif; ?>
		}

		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .button'; ?> ,
		<?php echo '#' . esc_html( $args['id'] ) . ' .' . esc_html( $skin ) . ' > .item .button *'; ?> {
			<?php if ( ! empty( $font_color_button ) ) : ?>
				color: <?php echo esc_html( $font_color_button ); ?>;
			<?php endif; ?>
		}

		<?php

		$custom_css = ob_get_clean();

		return preg_replace( '/\s{1,}(?!id|\.|\#|\*|\+|\~|\>|\"|\')/', '', $custom_css );
	}

	/**
	 * Enqueues custom CSS assets for course grids.
	 *
	 * This method outputs custom CSS styles for each course grid in the provided array.
	 * It generates the CSS using the generate_custom_css method and wraps all styles
	 * in a single style tag with the ID 'learndash-course-grid-custom-css'.
	 *
	 * @since 4.21.4
	 *
	 * @param array[] $course_grids Array of course grid configurations.
	 *
	 * @return void
	 */
	public function enqueue_custom_assets( $course_grids ) {
		ob_start();

		echo '<style id="learndash-course-grid-custom-css">';

		foreach ( $course_grids as $args ) {
			echo $this->generate_custom_css( $args );

			/**
			 * Fires after outputting custom CSS for a course grid.
			 *
			 * This action allows developers to add additional custom CSS for each course grid.
			 *
			 * @since 4.21.4
			 *
			 * @param array $args Course grid configuration arguments.
			 */
			do_action( 'learndash_course_grid_custom_css', $args );
		}

		echo '</style>';

		$assets = ob_get_clean();
		echo $assets;
	}
}
