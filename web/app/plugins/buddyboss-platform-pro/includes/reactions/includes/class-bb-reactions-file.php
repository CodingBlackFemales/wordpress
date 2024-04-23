<?php
/**
 * Reactions File helper.
 *
 * @since   2.4.50
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class contains all file handling functions.
 *
 * @package BuddyBossPro
 */
class BB_Reactions_File {

	/**
	 * Reads the file and return the data of it.
	 *
	 * @since 2.4.50
	 *
	 * @param string $file File to be read.
	 *
	 * @return bool|string
	 */
	public static function read_file( $file ) {
		if ( ! file_exists( $file ) ) {
			return false;
		}

		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$data   = fread( $handle, filesize( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $data;
	}

	/**
	 * Helps to write file into the path.
	 *
	 * @since 2.4.50
	 *
	 * @param string $path    File path.
	 * @param string $content Content to write in the file.
	 *
	 * @return bool
	 */
	public static function write_file( $path, $content ) {
		// NOTE : Proceed with creation since no file exists yet.
		// if path already exists unlink it. avoid overwriting.
		if ( file_exists( $path ) && ! is_dir( $path ) ) {
			wp_delete_file( $path );
		}

		$wp_file_system_cls = new WP_Filesystem_Direct( array() );
		$write              = $wp_file_system_cls->put_contents( $path, $content );

		return $write;
	}

	/**
	 * Helps to delete the directory recursively.
	 *
	 * @since 2.4.50
	 *
	 * @param string $dir Directory.
	 */
	public static function delete_dir( $dir ) {
		$dir = untrailingslashit( $dir );

		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );

			foreach ( $objects as $object ) {
				if ( '.' !== $object && '..' !== $object ) {
					if ( is_dir( $dir . '/' . $object ) ) {
						self::delete_dir( $dir . '/' . $object );
					} else {
						wp_delete_file( $dir . '/' . $object );
					}
				}
			}
			$file_system_direct = new \WP_Filesystem_Direct( false );
			$file_system_direct->rmdir( $dir, true );
		}
	}

	/**
	 * Copy Dir
	 *
	 * @since 2.4.50
	 *
	 * @param string $source Directory to be copied.
	 * @param string $target Directory to copy.
	 */
	public static function copy_dir( $source, $target ) {
		if ( ! is_dir( $source ) ) { // It is a file, do a normal copy.
			self::copy_file( $source, $target );
		}

		// It is a folder, copy its files & sub-folders.
		wp_mkdir_p( $target );

		$d           = dir( $source );
		$nav_folders = array( '.', '..' );
		$file_entry  = $d->read();

		while ( false !== $file_entry ) { // Copy one by one.
			// Skip if it is navigation folder . or ..
			if ( in_array( $file_entry, $nav_folders, true ) ) {
				continue;
			}

			// Do copy.
			$s = "$source/$file_entry";
			$t = "$target/$file_entry";
			self::copy_file( $s, $t );
		}

		$d->close();
	}

	/**
	 * Create and upload file with content.
	 *
	 * @since 2.4.50
	 *
	 * @param string $file    Uploaded File Absolute path with name.
	 * @param string $content File content.
	 *
	 * @return bool
	 */
	public static function file_handler( $file, $content ) {
		if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		$wp_files_system = new \WP_Filesystem_Direct( new \stdClass() );

		/* Now we can use $plugin_path in all our Filesystem API method calls */
		if ( ! $wp_files_system->is_dir( dirname( $file ) ) ) {
			/* directory didn't exist, so let's create it */
			$wp_files_system->mkdir( dirname( $file ) );
		}

		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;

		return $wp_files_system->put_contents( $file, $content, $chmod );
	}

	/**
	 * Function creates directory.
	 *
	 * @since 2.4.50
	 *
	 * @param string $dir_path Directory path.
	 */
	public static function create_dir( $dir_path ) {
		if ( ! is_dir( $dir_path ) ) {
			wp_mkdir_p( $dir_path );
		}
	}

	/**
	 * Function to copy file.
	 *
	 * @since 2.4.50
	 *
	 * @param string $source_file File to be copied.
	 * @param string $target_file File to copy.
	 *
	 * @return bool|void
	 */
	public static function copy_file( $source_file, $target_file ) {
		if ( ! is_dir( $source_file ) ) { // It is a file, do a normal copy.
			if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}

			$wp_files_system = new \WP_Filesystem_Direct( new \stdClass() );
			$chmod           = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
			$copy            = $wp_files_system->copy( $source_file, $target_file, true, $chmod );
			if ( ! empty( $copy ) && ! is_wp_error( $copy ) ) {
				return false;
			}

			return true;
		}
	}

	/**
	 * Check file exists.
	 *
	 * @since 2.4.50
	 *
	 * @param string $file File path.
	 *
	 * @return bool
	 */
	public static function file_exists( $file ) {
		if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$wp_files_system = new \WP_Filesystem_Direct( new \stdClass() );

		return $wp_files_system->exists( $file );
	}

	/**
	 * Read file content.
	 *
	 * @since 2.4.50
	 *
	 * @param string $file File path.
	 *
	 * @return false|string
	 */
	public static function get_contents( $file ) {
		if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$wp_files_system = new \WP_Filesystem_Direct( new \stdClass() );

		return $wp_files_system->get_contents( $file );
	}

}
