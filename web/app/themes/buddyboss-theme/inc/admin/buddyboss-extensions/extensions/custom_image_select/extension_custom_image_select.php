<?php

/**
 * Redux Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Redux Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Redux Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     ReduxFramework
 * @author      Dovy Paukstys (dovy)
 * @version     3.0.0
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

// Don't duplicate me!
if ( !class_exists( 'ReduxFramework_Extension_custom_image_select' ) ) {

	/**
	 * Main ReduxFramework custom_field extension class
	 *
	 * @since       3.1.6
	 */
	#[\AllowDynamicProperties]
	class ReduxFramework_Extension_custom_image_select extends Redux_Extension_Abstract {

		public $extension_url;
		public $extension_dir;
		public static $theInstance;

		/**
		 * ReduxFramework_Extension_custom_image_select constructor.
		 *
		 * @param ReduxFramework $parent ReduxFramework object.
		 */
		public function __construct( $parent ) {

			parent::__construct( $parent, __FILE__ );

			if ( empty( $this->extension_dir ) ) {
				$this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
			}
			$this->field_name = 'custom_image_select';

			self::$theInstance = parent::get_instance();

			add_filter( 'redux/' . $this->parent->args[ 'opt_name' ] . '/field/class/' . $this->field_name, array( &$this, 'overload_field_path' ), 10, 2 ); // Adds the local field
		}

		public function getInstance() {
			return self::$theInstance;
		}

		// Forces the use of the embeded field path vs what the core typically would use
		public function overload_field_path( string $file, array $field ): string {
			$files = array( dirname( __FILE__ ) . '/' . $this->field_name . '/field_' . $this->field_name . '.php' );

			return Redux_Functions::file_exists_ex( $files );
		}

	}

	// class
} // if