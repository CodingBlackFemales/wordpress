<?php
/**
 * LearnDash Autoloader class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core;

/**
 * LearnDash Autoloader class.
 *
 * @since 4.6.0
 */

/**
 * Class Autoloader
 *
 * Allows for autoloading of LearnDash classes.
 *
 * Example usage:
 *
 *      // will be `/var/www/site/wp-content/plugins/sfwd-lms'
 *      $this_dir = dirname(__FILE__);
 *
 *      // gets hold of the singleton instance of the class
 *      $autoloader = Autoloader::instance();
 *
 *      // register one by one or use `register_prefixes` method
 *      $autoloader->register_prefix( 'LearnDash__Admin__', $this_dir . '/src/admin' );
 *      $autoloader->register_prefix( 'LearnDash__Admin__', $this_dir . '/src/another-dir' );
 *      $autoloader->register_prefix( 'LearnDash__Utils__', $this_dir . '/src/some-dir' );
 *
 *      // register a direct class to path
 *      $autoloader->register_class( 'LearnDash_Some_Deprecated_Class', $this_dir . '/src/deprecated/LearnDash_Some_Deprecated_Class.php' );
 *
 *      // register a fallback dir to be searched for the class before giving up
 *      $autoloader->add_fallback_dir( $this_dir . '/all-the-classes' );
 *
 *      // calls `spl_autoload_register`
 *      $autoloader->register_autoloader();
 *
 *      // class will be searched in the path
 *      // `/var/www/site/wp-content/plugins/sfwd-lms/src/admin/Some_Class.php'
 *      // and
 *      // `/var/www/site/wp-content/plugins/sfwd-lms/src/another-dir/Some_Class.php'
 *      $i = new LearnDash__Admin__Some_Class();
 *
 *      // class will be searched in the path
 *      // `/var/www/site/wp-content/plugins/sfwd-lms/utils/some-dir/Some_Util.php'
 *      $i = new LearnDash__Utils__Some_Util();
 *
 *      // class will be searched in the path
 *      // `/var/www/site/wp-content/plugins/sfwd-lms/src/deprecated/LearnDash_Some_Deprecated_Class.php'
 *      $i = new LearnDash_Some_Deprecated_Class();
 */
class Autoloader {

	/**
	 * The singleton instance of the class.
	 *
	 * @since 4.6.0
	 *
	 * @var Autoloader
	 */
	protected static $instance;

	/**
	 * An arrays of arrays each containing absolute paths.
	 *
	 * Paths are stored trimming any trailing `/`.
	 * E.g. `/var/www/html/wp-content/plugins/sfwd-lms/src/Core`
	 *
	 * @since 4.6.0
	 *
	 * @var string[][]
	 */
	protected $prefixes = array();

	/**
	 * An array of registered prefixes with unique slugs.
	 *
	 * @since 4.6.0
	 *
	 * @var string[]
	 */
	protected $prefix_slugs = array();

	/**
	 * The string acting as a directory separator in a class name.
	 *
	 * E.g.: given `__` as `$dir_separator` then `Admin__Metabox__Some_Metabox`
	 * will map to `/Admin/Metabox/SomeMetabox.php`.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $dir_separator = '__';

	/**
	 * An array of fallback dirs to be searched for the class before giving up.
	 *
	 * @since 4.6.0
	 *
	 *  @var string[]
	 */
	protected $fallback_dirs = array();

	/**
	 * An array of registered classes with absolute paths.
	 *
	 * @since 4.6.0
	 *
	 * @var array<string,string>
	 */
	protected $class_paths = array();

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return Autoloader
	 */
	public static function instance(): Autoloader {
		if ( ! self::$instance instanceof Autoloader ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers prefixes and root dirs using an array.
	 *
	 * Same as calling `register_prefix` on each one.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string,string> $prefixes_to_root_dirs List of prefixes and root dirs.
	 *
	 * @return void
	 */
	public function register_prefixes( array $prefixes_to_root_dirs ): void {
		foreach ( $prefixes_to_root_dirs as $prefix => $root_dir ) {
			$this->register_prefix( $prefix, $root_dir );
		}
	}

	/**
	 * Associates a class prefix to an absolute path.
	 *
	 * @since 4.6.0
	 *
	 * @param string $prefix   A class prefix, e.g. `LearnDash__Admin__`.
	 * @param string $root_dir The absolute path to the dir containing
	 *                         the prefixed classes.
	 * @param string $slug     An optional unique slug to associate to the prefix.
	 *
	 * @return void
	 */
	public function register_prefix( string $prefix, string $root_dir, string $slug = '' ): void {
		$root_dir = $this->normalize_root_dir( $root_dir );

		// Determine if we need to normalize the $prefix.
		$is_namespaced = false !== strpos( $prefix, '\\' );

		if ( $is_namespaced ) {
			// If the prefix is a namespace, then normalize it.
			$prefix = trim( $prefix, '\\' ) . '\\';
		}

		if ( ! isset( $this->prefixes[ $prefix ] ) ) {
			$this->prefixes[ $prefix ] = array();
		}

		$this->prefixes[ $prefix ][] = $root_dir;

		// Let's make sure we're not adding duplicates.
		$this->prefixes[ $prefix ] = array_unique( $this->prefixes[ $prefix ] );

		if ( $slug ) {
			$this->prefix_slugs[ $slug ] = $prefix;
		}
	}

	/**
	 * Triggers the registration of the autoload method in the SPL
	 * autoload register.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function register_autoloader(): void {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Includes the file defining a class.
	 *
	 * This is the function that's registered as an autoloader.
	 *
	 * @param string $class The name of the class to load.
	 *
	 * @return void
	 */
	public function autoload( string $class ): void {
		$include_path = $this->get_class_path( $class );
		if ( ! empty( $include_path ) ) {
			include_once $include_path;
		}
	}

	/**
	 * Normalizes the root dir by trimming any trailing `/`.
	 *
	 * @since 4.6.0
	 *
	 * @param string $root_dir The root dir to normalize.
	 *
	 * @return string The normalized root dir.
	 */
	private function normalize_root_dir( string $root_dir ): string {
		return rtrim( $root_dir, '/' );
	}

	/**
	 * Returns the path to the file defining a class.
	 *
	 * @since 4.6.0
	 *
	 * @param string $class The name of the class.
	 *
	 * @return string The path to the file defining the class or an empty string if not found.
	 */
	protected function get_prefixed_path( string $class ): string {
		foreach ( $this->prefixes as $prefix => $dirs ) {
			$is_namespaced = false !== strpos( $prefix, '\\' );

			if ( strpos( $class, $prefix ) !== 0 ) {
				continue;
			}

			$class_name = str_replace( $prefix, '', $class );

			if ( ! $is_namespaced ) {
				$class_path_frag = implode( '/', (array) explode( $this->dir_separator, $class_name ) ) . '.php';
			} else {
				$class_path_frag = implode( '/', explode( '\\', $class_name ) ) . '.php';
			}

			foreach ( $dirs as $dir ) {
				$path = $dir . '/' . $class_path_frag;
				if ( ! file_exists( $path ) ) {
					// check if the file exists in lowercase.
					$class_path_frag = strtolower( $class_path_frag );
					$path            = $dir . '/' . $class_path_frag;
				}
				if ( ! file_exists( $path ) ) {
					continue;
				}

				return $path;
			}
		}
		return '';
	}

	/**
	 * Gets the absolute path to a class file using the fallback dirs.
	 *
	 * @since 4.6.0
	 *
	 * @param string $class The class name.
	 *
	 * @return string Either the absolute path to the class file or an empty string.
	 */
	protected function get_fallback_path( string $class ): string {
		foreach ( $this->fallback_dirs as $fallback_dir ) {
			$include_path = $fallback_dir . '/' . $class . '.php';
			if ( ! file_exists( $include_path ) ) {
				// check if the file exists in lowercase.
				$class        = strtolower( $class );
				$include_path = $fallback_dir . '/' . $class . '.php';
			}
			if ( ! file_exists( $include_path ) ) {
				continue;
			}

			return $include_path;
		}

		return '';
	}

	/**
	 * Gets the absolute path to a class file.
	 *
	 * @since 4.6.0
	 *
	 * @param string $class The class name.
	 *
	 * @return string Either the absolute path to the class file or an
	 *                empty string if the file was not found.
	 */
	public function get_class_path( string $class ): string {
		$prefixed_path = $this->get_prefixed_path( $class );
		if ( ! empty( $prefixed_path ) ) {
			return $prefixed_path;
		}

		$class_path = ! empty( $this->class_paths[ $class ] ) ? $this->class_paths[ $class ] : '';
		if ( ! empty( $class_path ) ) {
			return $class_path;
		}

		return $this->get_fallback_path( $class );
	}

	/**
	 * Get the registered prefix by slug
	 *
	 * @since 4.6.0
	 *
	 * @param string $slug Unique slug for registered prefix.
	 *
	 * @return string The prefix registered to the unique slug or empty string if not found.
	 */
	public function get_prefix_by_slug( string $slug ): string {
		$prefix = '';

		if ( isset( $this->prefix_slugs[ $slug ] ) ) {
			$prefix = $this->prefix_slugs[ $slug ];
		}

		return $prefix;
	}

	/**
	 * Adds a folder to search for classes that were not found among
	 * the prefixed ones.
	 *
	 * This is the method to use to register a directory of deprecated
	 * classes.
	 *
	 * @since 4.6.0
	 *
	 * @param string $dir An absolute path to a dir.
	 *
	 * @return void
	 */
	public function add_fallback_dir( string $dir ): void {
		if ( in_array( $dir, $this->fallback_dirs, true ) ) {
			return;
		}

		$this->fallback_dirs[] = $this->normalize_root_dir( $dir );
	}

	/**
	 * Returns the directory separator used by the class loader.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_dir_separator(): string {
		return $this->dir_separator;
	}

	/**
	 * Registers a class path.
	 *
	 * @since 4.6.0
	 *
	 * @param string $class The class name.
	 * @param string $path The path to the class file.
	 *
	 * @return void
	 */
	public function register_class( string $class, string $path ): void {
		$this->class_paths[ $class ] = $path;
	}
}
