<?php
/**
 * Load the block editor integration.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Editor;

use CodingBlackFemales\SemanticGlossary\Api\Controller;
use CodingBlackFemales\SemanticGlossary\Assets;
use CodingBlackFemales\SemanticGlossary\Entry\Capabilities;
use CodingBlackFemales\SemanticGlossary\Settings;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EditorAssets.
 *
 * One bundle holds the "Glossary term" format, the Glossary sidebar and the
 * Glossary block's editor UI. It depends on the post editor (wp-editor), so it
 * loads only there, not in the site or widgets editors; the block's server-side
 * registration still renders it wherever it is used.
 */
final class EditorAssets {

	/**
	 * Editor script handle.
	 */
	const SCRIPT = 'cbf-semantic-glossary-editor';

	/**
	 * Editor UI stylesheet (popover, sidebar).
	 */
	const STYLE = 'cbf-semantic-glossary-editor';

	/**
	 * Editor canvas stylesheet (how marked text looks while editing).
	 */
	const CONTENT_STYLE = 'cbf-semantic-glossary-editor-content';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_canvas' ) );
	}


	/**
	 * Register the bundle and its stylesheets.
	 */
	public static function register(): void {
		Assets::register_script( self::SCRIPT, 'js/editor/cbf-semantic-glossary-editor.js' );
		Assets::register_style( self::STYLE, 'css/editor/cbf-semantic-glossary-editor.css', array( 'wp-components' ) );
		Assets::register_style( self::CONTENT_STYLE, 'css/editor/cbf-semantic-glossary-editor-content.css' );
	}


	/**
	 * Enqueue the editor UI.
	 */
	public static function enqueue_editor(): void {
		$screen = get_current_screen();

		if ( ! wp_script_is( self::SCRIPT, 'registered' ) || $screen === null || $screen->base !== 'post' ) {
			return;
		}

		wp_add_inline_script( self::SCRIPT, 'window.cbfGlossary = ' . wp_json_encode( self::config() ) . ';', 'before' );
		wp_enqueue_script( self::SCRIPT );
		wp_enqueue_style( self::STYLE );
	}


	/**
	 * Enqueue the canvas styles, inside the editor only.
	 *
	 * enqueue_block_assets also fires on the front end, where marked text is
	 * rendered as links and styled by the front-end stylesheet instead.
	 */
	public static function enqueue_canvas(): void {
		if ( is_admin() ) {
			wp_enqueue_style( self::CONTENT_STYLE );
		}
	}


	/**
	 * Settings the bundle needs.
	 *
	 * @return array<string, mixed>
	 */
	private static function config(): array {
		return array(
			'namespace'       => Controller::NAMESPACE,
			'canCreate'       => Capabilities::can_create(),
			'canPublish'      => Capabilities::can_publish(),
			'autoAppend'      => Settings::auto_append(),
			'indexMinEntries' => Settings::index_min_entries(),
		);
	}
}
