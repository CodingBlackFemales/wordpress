<?php
/**
 * File class.
 *
 * @package LearnDash\Core
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

defined( 'ABSPATH' ) || exit;

class File {
	/**
	 * Engine use to create a dir tree
	 *
	 * @var string
	 */
	public $engine = '';
	/**
	 * Absolute path to a folder need to create a dir tre
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * Is the result including file?
	 *
	 * @var bool
	 */
	public $include_file = true;
	/**
	 * Is the result include dir
	 *
	 * @var bool
	 */
	public $include_dir = true;

	/**
	 * @var bool
	 */
	public $include_hidden = false;

	/**
	 * This is where to define the rules for exclude files out of the result
	 *
	 * 'ext'=>array('jpg','gif') file extension you don't want appear in the result
	 * 'path'=>array('/tmp/file1.txt','/tmp/file2') absolute path to files
	 * 'dir'=>array('/tmp/','/dir/') absolute path to the directory you dont want to include files
	 * 'filename'=>array('abc*') file name you don't want to include, can be regex,
	 *
	 * @var array
	 */
	public $exclude = array();

	/**
	 * This is where to define the rules for include files, please note that if $include is provided, the $exclude
	 * will get ignored
	 *
	 * 'ext'=>array('jpg','gif') file extension you don't want appear in the result
	 * 'path'=>array('/tmp/file1.txt','/tmp/file2') absolute path to files
	 * 'dir'=>array('/tmp/','/dir/') absolute path to the directory you dont want to include files
	 * 'filename'=>array('abc*') file name you don't want to include, can be regex,
	 *
	 * @var array
	 */
	public $include = array();

	/**
	 * Does this search recursive
	 *
	 * @var bool
	 */
	public $is_recursive = true;

	/**
	 * if provided, only search file smaller than this
	 *
	 * @var int
	 */
	public $max_filesize = 0;

	/**
	 * @param $path
	 * @param bool|true  $include_file
	 * @param bool|false $include_dir
	 * @param array      $include
	 * @param array      $exclude
	 * @param bool|true  $is_recursive
	 * @param int        $max_filesize
	 */
	public function __construct(
		$path,
		bool $include_file = true,
		bool $include_dir = false,
		array $include = array(),
		array $exclude = array(),
		bool $is_recursive = true,
		$include_hidden = false,
		int $max_filesize = 0
	) {
		$this->path           = $path;
		$this->include_file   = $include_file;
		$this->include_dir    = $include_dir;
		$this->include        = $include;
		$this->exclude        = $exclude;
		$this->is_recursive   = $is_recursive;
		$this->include_hidden = $include_hidden;
		$this->max_filesize   = $max_filesize;
	}

	/**
	 * @return array
	 */
	public function get_dir_tree(): array {
		$result = array();
		if ( ! is_dir( $this->path ) ) {
			return $result;
		}

		return $this->get_dir_tree_by_scandir();
	}

	/**
	 * @param null $path
	 *
	 * @return array
	 */
	private function get_dir_tree_by_scandir( string $path = null ): array {
		if ( is_null( $path ) ) {
			$path = $this->path;
		}
		$path    = rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
		$r_files = scandir( $path );
		$data    = array();

		foreach ( $r_files as $r_file ) {
			if ( $r_file === '.' || $r_file === '..' ) {
				continue;
			}
			if ( substr( pathinfo( $r_file, PATHINFO_BASENAME ), 0, 1 ) === '.'
				&& false === $this->include_hidden ) {
				// hidden files, move on.
				continue;
			}

			$real_path = $path . $r_file;

			$type = filetype( $real_path );

			if ( ( ! empty( $this->include ) || ! empty( $this->exclude ) ) && ( false === $this->filter_directory(
				$real_path,
				$type
			) ) ) {
				continue;
			}

			if ( 'file' === $type && true === $this->include_file ) {
				if ( is_numeric( $this->max_filesize ) && 0 < $this->max_filesize ) {
					$max_size = $this->max_filesize * ( pow( 1024, 2 ) );
					if ( filesize( $real_path ) > $max_size ) {
						continue;
					} else {
						$data[] = $real_path;
					}
				} else {
					$data[] = $real_path;
				}
			}

			if ( 'dir' === $type ) {
				if ( $this->include_dir ) {
					$data[] = $real_path;
				}
				if ( $this->is_recursive ) {
					$t_data = $this->get_dir_tree_by_scandir( $real_path );
					$data   = array_merge( $data, $t_data );
				}
			}
		}

		return $data;
	}

	/**
	 * Filter for recursive directory tree
	 *
	 * @param $current
	 * @param null    $filetype
	 *
	 * @return bool
	 */
	public function filter_directory( $current, $filetype = null ): bool {
		if ( ! empty( $this->include ) ) {
			return $this->_filter_include( $current, $filetype );
		} elseif ( ! empty( $this->exclude ) ) {
			return $this->_filter_exclude( $current, $filetype );
		}

		return true;
	}

	/**
	 * @param $path
	 * @param null $filetype
	 *
	 * @return bool
	 */
	private function _filter_include( $path, $filetype = null ): bool {
		$include     = $this->include;
		$exclude     = $this->exclude;
		$applied     = 0;
		$dir_include = isset( $include['dir'] ) ? $include['dir'] : array();
		$dir_exclude = isset( $exclude['dir'] ) ? $exclude['dir'] : array();

		if ( ! is_null( $filetype ) ) {
			$type = $filetype;
		} else {
			$type = filetype( $path );
		}

		if ( is_array( $dir_include ) && count( $dir_include ) ) {
			if ( is_array( $dir_exclude ) ) {
				foreach ( $dir_exclude as $dir ) {
					if ( strpos( $path, $dir ) === 0 ) {
						// this mean, exclude matched, we wont list this
						// move to next loop
						continue;
					}
				}
			}

			foreach ( $dir_include as $dir ) {
				if ( strpos( $path, $dir ) === 0 ) {
					return true;
				}
			}
			++$applied;
		}

		// next extension
		$ext_include = isset( $include['ext'] ) ? $include['ext'] : array();

		if ( is_array( $ext_include ) && count( $ext_include ) && $type == 'file' ) {
			// we will uses foreach and strcasecmp instead of regex cause it faster
			foreach ( $ext_include as $ext ) {
				if ( strcasecmp( pathinfo( $path, PATHINFO_EXTENSION ), $ext ) === 0 ) {
					// match
					return true;
				}
			}
			++$applied;
		}

		// now filename
		$filename_include = isset( $include['filename'] ) ? $include['filename'] : array();
		if ( is_array( $filename_include ) && count( $filename_include ) && $type == 'file' ) {
			foreach ( $filename_include as $filename ) {
				if ( preg_match( '/' . $filename . '/', pathinfo( $path, PATHINFO_BASENAME ) ) ) {
					return true;
				}
			}
			++$applied;
		}

		// now abs path
		$path_include = isset( $include['path'] ) ? $include['path'] : array();
		if ( is_array( $path_include ) && count( $path_include ) && $type == 'file' ) {
			foreach ( $path_include as $p ) {
				if ( strcmp( $p, $path ) === 0 ) {
					return true;
				}
			}
			++$applied;
		}

		if ( 0 === $applied ) {
			return true;
		}

		return false;
	}

	/**
	 * Run the filter for a file/dir
	 *
	 * @param $path
	 * @param null $filetype
	 *
	 * @return bool
	 */
	private function _filter_exclude( $path, $filetype = null ): bool {
		$exclude = $this->exclude;
		// first filer dir, or file inside dir
		if ( ! is_null( $filetype ) ) {
			$type = $filetype;
		} else {
			$type = filetype( $path );
		}
		$dir_exclude = isset( $exclude['dir'] ) ? $exclude['dir'] : array();
		if ( is_array( $dir_exclude ) && count( $dir_exclude ) ) {
			foreach ( $dir_exclude as $dir ) {
				if ( strpos( $path, $dir ) === 0 ) {
					return false;
				}
			}
		}

		// next extension
		$ext_exclude = isset( $exclude['ext'] ) ? $exclude['ext'] : array();
		if ( is_array( $ext_exclude ) && count( $ext_exclude ) && $type == 'file' ) {
			// we will uses foreach and strcasecmp instead of regex cause it faster
			foreach ( $ext_exclude as $ext ) {
				if ( strcasecmp( pathinfo( $path, PATHINFO_EXTENSION ), $ext ) === 0 ) {
					// match
					return false;
				}
			}
		}
		// now filename
		$filename_exclude = isset( $exclude['filename'] ) ? $exclude['filename'] : array();
		if ( is_array( $filename_exclude ) && count( $filename_exclude ) && $type == 'file' ) {
			foreach ( $filename_exclude as $filename ) {
				if ( preg_match( '/' . $filename . '/', pathinfo( $path, PATHINFO_BASENAME ) ) ) {
					return false;
				}
			}
		}

		// now abs path
		$path_exclude = isset( $exclude['path'] ) ? $exclude['path'] : array();
		if ( is_array( $path_exclude ) && count( $path_exclude ) && $type == 'file' ) {
			foreach ( $path_exclude as $p ) {
				if ( strcmp( $p, $path ) === 0 ) {
					return false;
				}
			}
		}

		return true;
	}
}
