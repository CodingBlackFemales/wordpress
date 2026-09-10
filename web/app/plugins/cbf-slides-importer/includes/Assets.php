<?php
/**
 * Handle script and style registration and enqueue.
 *
 * @class   Assets
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets class.
 *
 * Extends the boilerplate pattern from cbf-multisite:
 * - resolves .min.css/.min.js automatically when SCRIPT_DEBUG is false.
 * - data passed per-script is localized via wp_localize_script.
 */
abstract class Assets {

	/** @var array<string> Registered script handles. */
	private static array $scripts = array();

	/** @var array<string> Registered style handles. */
	private static array $styles = array();

	/** @var array<string> Already-localized script handles. */
	private static array $wp_localize_scripts = array();


	/**
	 * Resolve the URL for an asset, preferring the minified version.
	 *
	 * @param  string $path Path relative to the assets/ directory.
	 * @return string Protocol-relative URL.
	 */
	public static function localize_asset( string $path ): string {
		$assets_path     = Utils::plugin_path() . '/assets/';
		$assets_path_url = str_replace( array( 'http:', 'https:' ), '', Utils::plugin_url() ) . '/assets/';

		if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$ext_pos = strrpos( $path, '.' );
			if ( is_numeric( $ext_pos ) ) {
				$clean    = substr( $path, 0, $ext_pos );
				$ext      = substr( $path, $ext_pos );
				$min_path = $clean . '.min' . $ext;
				if ( file_exists( $assets_path . $min_path ) ) {
					$path = $min_path;
				}
			}
		}

		return $assets_path_url . $path;
	}


	/** @return array<string,array> */
	public static function get_styles(): array {
		return apply_filters( 'cbf_si_enqueue_styles', array() );
	}


	/** @return array<string,array> */
	public static function get_scripts(): array {
		return apply_filters( 'cbf_si_enqueue_scripts', array() );
	}


	/**
	 * Cache-bust an asset on its own modification time.
	 *
	 * Assets are served with a one-year max-age, and the plugin version only
	 * changes at release, so an edited script keeps its `?ver=` and a browser
	 * that has the page open goes on running the old file indefinitely. That is
	 * indistinguishable from the fix not working, and cost a round of debugging
	 * a bug that had already been fixed.
	 *
	 * The plugin version stays in the string so the query argument still says
	 * what release the file came from.
	 *
	 * @param  string $src     The asset URL, as returned by localize_asset().
	 * @param  string $version Version the caller asked for.
	 * @return string
	 */
	private static function file_version( string $src, string $version ): string {
		if ( $version !== VERSION ) {
			return $version;
		}

		$base = str_replace( array( 'http:', 'https:' ), '', Utils::plugin_url() ) . '/assets/';

		if ( strpos( $src, $base ) !== 0 ) {
			return $version;
		}

		$file = Utils::plugin_path() . '/assets/' . substr( $src, strlen( $base ) );

		if ( ! file_exists( $file ) ) {
			return $version;
		}

		$mtime = filemtime( $file );

		return $mtime ? $version . '.' . $mtime : $version;
	}


	/** Register a script. */
	private static function register_script( string $handle, string $path, array $deps = array( 'jquery' ), string $version = VERSION, bool $in_footer = true ): void {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, self::file_version( $path, $version ), $in_footer );
	}


	/** Register and enqueue a script. */
	private static function enqueue_script( string $handle, string $path = '', array $deps = array( 'jquery' ), string $version = VERSION, bool $in_footer = true ): void {
		if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}


	/** Register a stylesheet. */
	private static function register_style( string $handle, string $path, array $deps = array(), string $version = VERSION, string $media = 'all' ): void {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, self::file_version( $path, $version ), $media );
	}


	/** Register and enqueue a stylesheet. */
	private static function enqueue_style( string $handle, string $path = '', array $deps = array(), string $version = VERSION, string $media = 'all' ): void {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media );
		}
		wp_enqueue_style( $handle );
	}


	/**
	 * Register and enqueue all declared scripts and styles.
	 *
	 * Hooked to `admin_enqueue_scripts`.
	 */
	public static function load_scripts(): void {
		if ( ! did_action( 'before_cbf_si_init' ) ) {
			return;
		}

		foreach ( static::get_scripts() as $handle => $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'src'       => '',
					'deps'      => array( 'jquery' ),
					'version'   => VERSION,
					'in_footer' => true,
					'enqueue'   => true,
				)
			);
			if ( $args['enqueue'] ) {
				self::enqueue_script( $handle, $args['src'], $args['deps'], $args['version'], $args['in_footer'] );
			} else {
				self::register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['in_footer'] );
			}
		}

		foreach ( static::get_styles() as $handle => $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'src'     => '',
					'deps'    => array(),
					'version' => VERSION,
					'media'   => 'all',
					'enqueue' => true,
				)
			);
			if ( $args['enqueue'] ) {
				self::enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
			} else {
				self::register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
			}
		}
	}


	/** Localize a single script handle if it is enqueued. */
	private static function localize_script( string $handle ): void {
		if ( in_array( $handle, self::$wp_localize_scripts, true ) || ! wp_script_is( $handle ) ) {
			return;
		}
		$data = self::get_script_data( $handle );
		if ( $data ) {
			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}


	/** @return array<string,mixed>|false */
	private static function get_script_data( string $handle ): array|false {
		$scripts = static::get_scripts();
		if ( isset( $scripts[ $handle ]['data'] ) ) {
			$data = $scripts[ $handle ]['data'];
			return is_callable( $data ) ? call_user_func( $data ) : $data;
		}
		return false;
	}


	/** Localize all registered scripts that are currently enqueued. */
	public static function localize_printed_scripts(): void {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}
}
