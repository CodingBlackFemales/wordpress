<?php
/**
 * The Course Glossary block and its shortcode.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Block;

use CodingBlackFemales\SemanticGlossary\Render\ContentFilter;
use CodingBlackFemales\SemanticGlossaryLearnDash\Assets;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Resolver;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Steps;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseGlossary;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseOptions;
use CodingBlackFemales\SemanticGlossaryLearnDash\Utils;
use WP_Block;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CourseGlossaryBlock.
 *
 * Dynamic, like core's Glossary block: it saves only its settings and is
 * rendered for the current learner on every request.
 *
 * Caching: the output is per learner, so a page holding the block is exempted
 * from full-page caching (the spec's option (a)). That is one page per course,
 * and it keeps a server-rendered list for visitors without JavaScript.
 */
final class CourseGlossaryBlock {

	/**
	 * Block name.
	 */
	const NAME = 'cbf/course-glossary';

	/**
	 * Shortcode, for the Classic editor.
	 */
	const SHORTCODE = 'course_glossary';

	/**
	 * Front-end stylesheet and script handle.
	 */
	const HANDLE = 'cbf-semantic-glossary-learndash';

	/**
	 * Editor script handle.
	 */
	const EDITOR_HANDLE = 'cbf-semantic-glossary-learndash-editor';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		// After core registers the stylesheet this one builds on.
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_config' ) );
		add_action( 'template_redirect', array( __CLASS__, 'exempt_page_from_cache' ) );
	}


	/**
	 * Register assets, the block and the shortcode.
	 */
	public static function register(): void {
		Assets::register_style( self::HANDLE, 'css/frontend/cbf-semantic-glossary-learndash.css', array( ContentFilter::STYLE ) );
		Assets::register_script( self::HANDLE, 'js/frontend/cbf-semantic-glossary-learndash.js' );
		Assets::register_script( self::EDITOR_HANDLE, 'js/editor/cbf-semantic-glossary-learndash-editor.js' );

		register_block_type(
			Utils::plugin_path() . '/blocks/course-glossary',
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);

		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}


	/**
	 * Block render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content (none).
	 * @param WP_Block|null        $block      Block instance.
	 */
	public static function render( array $attributes, string $content = '', $block = null ): string {
		$post_id = $block instanceof WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		$html    = self::html( $post_id, CourseOptions::from_block( $attributes, 'course-glossary-heading' ), self::is_editor_preview() );

		if ( $html === '' ) {
			return '';
		}

		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'course-glossary' ) ) . '>' . $html . '</div>';
	}


	/**
	 * `[course_glossary course="current" filter="true" index="true"]`.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$html = self::html( (int) get_the_ID(), CourseOptions::from_shortcode( $atts, 'course-glossary-heading' ), false );

		if ( $html === '' ) {
			return '';
		}

		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );

		return '<div class="course-glossary">' . $html . '</div>';
	}


	/**
	 * The glossary for a course, as the current learner or the editor preview sees it.
	 *
	 * @param int           $post_id The post the glossary is placed in.
	 * @param CourseOptions $options Presentation.
	 * @param bool          $editor  Whether this is the block editor's preview.
	 */
	public static function html( int $post_id, CourseOptions $options, bool $editor ): string {
		$course_id = $options->course_id > 0 ? $options->course_id : Steps::course_of( $post_id );

		if ( ! Steps::is_course( $course_id ) ) {
			return $editor ? self::notice( __( 'This page is not part of a course. Choose a course in the block settings.', 'cbf-semantic-glossary-learndash' ) ) : '';
		}

		if ( $editor ) {
			$html = CourseGlossary::render( Resolver::for_editor( $course_id ), $options, true );

			return $html !== '' ? $html : self::notice( __( 'No lesson or topic in this course references a glossary term yet.', 'cbf-semantic-glossary-learndash' ) );
		}

		self::exempt_from_cache();

		return CourseGlossary::render( Resolver::for_learner( $course_id, wp_get_current_user() ), $options );
	}


	/**
	 * Exempt the page from full-page caching when it holds the glossary.
	 *
	 * Runs before output, so the no-cache headers can still be sent. Rendering
	 * exempts the page again, for glossaries placed by templates or patterns.
	 */
	public static function exempt_page_from_cache(): void {
		$post = get_queried_object();

		if ( is_singular() && $post instanceof WP_Post && ( has_block( self::NAME, $post ) || has_shortcode( $post->post_content, self::SHORTCODE ) ) ) {
			self::exempt_from_cache();
		}
	}


	/**
	 * Print the editor script's settings: the courses on offer and the page's own course.
	 */
	public static function editor_config(): void {
		$courses = array_map(
			fn ( int $id ) => array(
				'id'    => $id,
				'title' => Steps::title( $id ),
			),
			Steps::course_ids()
		);

		$current = Steps::course_of( (int) get_the_ID() );

		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.cbfCourseGlossary = ' . wp_json_encode(
				array(
					'courses'       => $courses,
					'currentCourse' => Steps::is_course( $current ) ? Steps::title( $current ) : '',
				)
			) . ';',
			'before'
		);
	}


	/**
	 * Tell page caches not to store this response.
	 *
	 * DONOTCACHEPAGE is honoured by WP Rocket and most page caches; LiteSpeed
	 * takes its own action. The headers stop proxies and browsers reusing it.
	 */
	private static function exempt_from_cache(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'course glossary is rendered per learner' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's hook.

		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}


	/**
	 * Whether this render is the block editor's preview.
	 *
	 * The editor previews the block through the block renderer endpoint.
	 */
	private static function is_editor_preview(): bool {
		$route = (string) ( $GLOBALS['wp']->query_vars['rest_route'] ?? '' );

		return wp_is_serving_rest_request() && str_starts_with( $route, '/wp/v2/block-renderer/' );
	}


	/**
	 * A message shown only in the editor preview.
	 *
	 * @param string $text Message.
	 */
	private static function notice( string $text ): string {
		return '<p class="course-glossary-editor-notice">' . esc_html( $text ) . '</p>';
	}
}
