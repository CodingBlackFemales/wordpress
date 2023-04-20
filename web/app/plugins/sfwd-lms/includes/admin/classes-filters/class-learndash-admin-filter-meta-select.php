<?php
/**
 * LearnDash Admin Filter Meta Select.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Filter_Meta' ) &&
	! class_exists( 'Learndash_Admin_Filter_Meta_Select' )
) {
	/**
	 * Filters posts by meta value.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Meta_Select extends Learndash_Admin_Filter_Meta {
		/**
		 * Select options.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		private $options;

		/**
		 * Construct.
		 *
		 * @since 4.2.0
		 *
		 * @param string $meta_key_name    Meta key name.
		 * @param string $label            Label.
		 * @param array  $options          Select options.
		 * @param string $meta_value_index Meta value index, if the meta value is an array.
		 */
		public function __construct( string $meta_key_name, string $label, array $options, string $meta_value_index = '' ) {
			$this->options = $options;

			parent::__construct( $meta_key_name, $label, $meta_value_index );
		}

		/**
		 * Echoes the input HTML.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function display(): void {
			?>
			<div class="sfwd_option_input">
				<select
					name="<?php echo esc_attr( $this->get_parameter_name() ); ?>"
					class="<?php echo esc_attr( $this->get_input_class() ); ?>"
					multiple="multiple"
					autocomplete="off"
					type="select"
					data-close-on-select="false"
					data-ld-select2="1"
				>
					<?php foreach ( $this->options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>">
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
		}
	}
}
