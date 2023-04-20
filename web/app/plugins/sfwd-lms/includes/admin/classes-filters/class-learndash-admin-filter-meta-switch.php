<?php
/**
 * LearnDash Admin Filter Meta Switch.
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
	! class_exists( 'Learndash_Admin_Filter_Meta_Switch' )
) {
	/**
	 * Filters posts by meta value.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Meta_Switch extends Learndash_Admin_Filter_Meta {
		/**
		 * Echoes the input HTML.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function display(): void {
			$options = array(
				'on' => __( 'Enabled', 'learndash' ),
				''   => __( 'Disabled', 'learndash' ),
			);

			$i = 0;
			foreach ( $options as $value => $label ) :
				$name = $this->get_parameter_name();
				$id   = $name . '-' . uniqid();
				?>
				<input
					type="radio"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					class="<?php echo esc_attr( $this->get_input_class() ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					autocomplete="off"
					<?php echo esc_attr( 0 === $i ? 'checked' : '' ); ?>
				>
				<label for="<?php echo esc_attr( $id ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
				<?php
				$i++;
			endforeach;
		}
	}
}
