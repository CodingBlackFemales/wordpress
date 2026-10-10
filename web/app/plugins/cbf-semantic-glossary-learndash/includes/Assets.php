<?php
/**
 * Locate built assets.
 *
 * @class   Assets
 * @version 1.0.0
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets.
 *
 * `npm run build:assets` compiles `src/` into `assets/`, writing a minified
 * twin of every file and, for scripts, an `.asset.php` file listing the
 * WordPress script dependencies the bundle imports.
 */
final class Assets {

	/**
	 * URL of a built asset, preferring the minified version unless SCRIPT_DEBUG is on.
	 *
	 * @param string $path Path relative to `assets/`, e.g. `css/frontend/cbf-semantic-glossary-learndash.css`.
	 */
	public static function url( string $path ): string {
		return Utils::plugin_url() . '/assets/' . self::resolve( $path );
	}


	/**
	 * Register a built stylesheet, versioned by its build time.
	 *
	 * Stylesheets have no `.asset.php` hash, and the plugin version only
	 * changes on release, so without this browsers keep a rebuilt file's old
	 * copy.
	 *
	 * @param string   $handle Style handle.
	 * @param string   $path   Path relative to `assets/`, e.g. `css/frontend/cbf-semantic-glossary-learndash.css`.
	 * @param string[] $deps   Dependencies.
	 */
	public static function register_style( string $handle, string $path, array $deps = array() ): void {
		$file = Utils::plugin_path() . '/assets/' . self::resolve( $path );

		wp_register_style( $handle, self::url( $path ), $deps, file_exists( $file ) ? (string) filemtime( $file ) : VERSION );
	}


	/**
	 * Register a built script with the dependencies its `.asset.php` lists.
	 *
	 * @param string   $handle Script handle.
	 * @param string   $path   Path relative to `assets/`, e.g. `js/editor/cbf-semantic-glossary-learndash-editor.js`.
	 * @param string[] $deps   Dependencies in addition to those detected at build time.
	 * @return bool False when the asset has not been built.
	 */
	public static function register_script( string $handle, string $path, array $deps = array() ): bool {
		$resolved = self::resolve( $path );
		$meta     = Utils::plugin_path() . '/assets/' . preg_replace( '/\.js$/', '.asset.php', $resolved );

		if ( ! is_readable( $meta ) ) {
			return false;
		}

		$asset = include $meta;

		wp_register_script(
			$handle,
			Utils::plugin_url() . '/assets/' . $resolved,
			array_merge( $asset['dependencies'] ?? array(), $deps ),
			$asset['version'] ?? VERSION,
			true
		);

		wp_set_script_translations( $handle, 'cbf-semantic-glossary-learndash', Utils::plugin_path() . '/i18n/languages' );

		return true;
	}


	/**
	 * The minified twin of a path when it exists and SCRIPT_DEBUG is off.
	 *
	 * @param string $path Path relative to `assets/`.
	 */
	private static function resolve( string $path ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return $path;
		}

		$min = (string) preg_replace( '/\.(js|css)$/', '.min.$1', $path );

		return file_exists( Utils::plugin_path() . '/assets/' . $min ) ? $min : $path;
	}
}
