<?php

	/**
	 * Redux Framework is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 2 of the License, or
	 * any later version.
	 * Redux Framework is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 * You should have received a copy of the GNU General Public License
	 * along with Redux Framework. If not, see <http://www.gnu.org/licenses/>.
	 *
	 * @package     Redux Framework
	 * @subpackage  Repeater
	 * @subpackage  Wordpress
	 * @author      Dovy Paukstys (dovy)
	 * @version     1.0.4
	 */

// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

// Don't duplicate me!
	if ( ! class_exists( 'ReduxFramework_extension_repeater' ) ) {


		/**
		 * Main ReduxFramework css_layout extension class
		 *
		 * @since       1.0.0
		 */
		class ReduxFramework_extension_repeater {

			public static $version = '1.0.4';

			// Protected vars
			protected $parent;
			public $extension_url;
			public $extension_dir;
			public static $theInstance;
			public $field_id = '';
			private $class_css = '';
			public $field_name = 'repeater';

			/**
			 * Class Constructor. Defines the args for the extions class
			 *
			 * @since       1.0.0
			 * @access      public
			 *
			 * @param       array $parent Parent settings.
			 *
			 * @return      void
			 */
			public function __construct( $parent ) {

				// Set parent object
				$this->parent = $parent;

				// Set extension dir
				if ( empty( $this->extension_dir ) ) {
					$this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
				}

				// Set instance
				self::$theInstance = $this;

				// Adds the local field
				add_filter( 'redux/' . $this->parent->args['opt_name'] . '/field/class/' . $this->field_name, array(
					&$this,
					'overload_field_path'
				) );
			}

			static public function getInstance() {
				return self::$theInstance;
			}

			// Forces the use of the embeded field path vs what the core typically would use
			public function overload_field_path( $field ) {
				return dirname( __FILE__ ) . '/' . $this->field_name . '/field_' . $this->field_name . '.php';
			}

		} // class
	} // if
