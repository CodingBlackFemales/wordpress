<?php
/**
 * Path protection handler.
 *
 * @since 4.10.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Infrastructure\File_Protection;

/**
 * Path protection handler.
 *
 * @since 4.10.3
 */
class Path_Protection_Handler {
	/**
	 * Paths to protect.
	 *
	 * @since 4.10.3
	 *
	 * @var array<string, string> $paths Path ID -> Path.
	 */
	private $paths = [
		'uploads_learndash_assignments' => 'learndash' . DIRECTORY_SEPARATOR . 'assignments',
		'uploads_learndash_essays'      => 'learndash' . DIRECTORY_SEPARATOR . 'essays',
		'uploads_assignments'           => 'assignments', // Legacy path for assignments (before 4.10.3).
		'uploads_essays'                => 'essays', // Legacy path for essays (before 4.10.3).
	];

	/**
	 * Protects the paths.
	 *
	 * @since 4.10.3
	 *
	 * @return void
	 */
	public function init(): void {
		$uploads_base_dir = wp_upload_dir()['basedir'];

		/**
		 * Filters the paths to protect.
		 *
		 * @since 4.10.3
		 *
		 * @param array<string, string> $paths Path ID -> Path.
		 *
		 * @return array<string, string> Path ID -> Path.
		 */
		$paths = apply_filters( 'learndash_file_protection_paths', $this->paths );

		foreach ( $paths as $path_id => $path ) {
			$full_path = $uploads_base_dir . DIRECTORY_SEPARATOR . $path;

			$this->protect_directory( $path_id, $full_path );
		}
	}

	/**
	 * Protects the directory.
	 *
	 * @since 4.10.3
	 *
	 * @param string $path_id Path ID.
	 * @param string $path    Path to protect.
	 *
	 * @return void
	 */
	protected function protect_directory( string $path_id, string $path ): void {
		if ( ! file_exists( $path ) ) {
			return;
		}

		File_Download_Handler::register_file_path( $path_id, $path );
		File_Download_Handler::try_to_protect_file_path( $path );
	}
}
