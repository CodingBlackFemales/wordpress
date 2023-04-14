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
 * @author      Dovy Paukstys
 * @version     3.1.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't duplicate me!
if ( ! class_exists( 'ReduxFramework_image' ) ) {

	/**
	 * Main ReduxFramework_custom_field class.
	 *
	 * @since 2.0.0
	 */
	#[\AllowDynamicProperties]
	class ReduxFramework_image {

		/**
		 * Field Constructor.
		 *
		 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
		 *
		 * @since       2.0.0
		 * @access      public
		 *
		 * @param array  $field  Field array.
		 * @param string $value  Field values.
		 * @param object $parent ReduxFramework object pointer.
		 *
		 * @return      void
		 */
		function __construct( $field = array(), $value = '', $parent = null ) {

			$this->parent = $parent;
			$this->field  = $field;
			$this->value  = $value;

			if ( empty( $this->extension_dir ) ) {
				$this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
				$this->extension_url = site_url( str_replace( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), '', $this->extension_dir ) );
			}

			// Set default args for this field to avoid bad indexes. Change this to anything you use.
			$defaults    = array(
				'options'          => array(),
				'stylesheet'       => '',
				'output'           => true,
				'enqueue'          => true,
				'enqueue_frontend' => true,
			);
			$this->field = wp_parse_args( $this->field, $defaults );

		}

		/**
		 * Field Render Function.
		 *
		 * Takes the vars and outputs the HTML for the field in the settings.
		 *
		 * @since  2.0.0
		 * @access public
		 * @return void
		 */
		public function render() {
			if ( ! empty( $this->field['image_url'] ) ) {
				if ( ! empty( $this->field['image_desc'] ) ) {
					?>
					<div class="description field-desc">
						<?php echo wp_kses_post( $this->field['image_desc'] ); ?>
					</div>
					<?php
				}
				if ( ! empty( $this->field['image_type'] ) && 'svg' === $this->field['image_type'] ) {
					?>
					<div class="image-svg">
						<?php echo $this->field['image_url']; ?>
					</div>
					<?php
				} else {
					?>
					<img alt="" src="<?php echo esc_url( $this->field['image_url'] ); ?>"/>
					<?php
				}
			}
		}

		/**
		 * Enqueue Function.
		 *
		 * If this field requires any scripts, or css define this function and register/enqueue the scripts/css.
		 *
		 * @since  2.0.0
		 * @access public
		 * @return void
		 */
		public function enqueue() {}

		/**
		 * Output Function.
		 *
		 * Used to enqueue to the front-end.
		 *
		 * @since  2.0.0
		 * @access public
		 * @return void
		 */
		public function output() {}

	}
}
