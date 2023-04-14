<?php
/**
 * This template will display the AJAX threaded select box options based on the settings page selected option.
 *
 * @var        $key
 * @var        $label
 * @var        $disable
 * @var        $checked
 * @var        $ajax
 * @var array  $option
 * @var array  $options_lists
 * @var string $sub_label
 * @var array  $component_settings
 *
 * @since   1.1.0
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'disabled' === trim( $disable ) && 'checked' === trim( $checked ) ) {
	return;
}
?>
<div class="parent <?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>">
	<input
		<?php echo esc_attr( $disable ); ?>
		<?php echo esc_attr( $checked ); ?>
		class="access-control-threaded-input"
		id="<?php echo esc_attr( $key ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>"
		type="checkbox"
		data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>"
		value="<?php echo esc_attr( $option['id'] ); ?>"
		name="<?php echo esc_attr( $key ); ?>[access-control-options][]">
	<label for="<?php echo esc_attr( $key ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>">
		<strong><?php echo esc_html( $option['text'] ); ?></strong>
	</label>
</div>
<div class="access-control-checkbox-list child-<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?> access-control-hide-div">
	<p class="description"><?php echo esc_html( $sub_label ); ?></p>
	<div class="multiple_options">
		<input
				id="all_<?php echo esc_attr( $option['id'] ); ?>"
				type="radio"
				value="all"
				name="<?php echo esc_attr( $key ); ?>[access-control-<?php echo esc_attr( $option['id'] ); ?>-options][]"
				class="chb"
				data-value="all"
				data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>" />
		<label for="all_<?php echo esc_attr( $option['id'] ); ?>"><?php esc_html_e( 'Any', 'buddyboss-pro' ); ?></label><br>
		<input
				id="specific_<?php echo esc_attr( $option['id'] ); ?>"
				type="radio"
				class="chb"
				data-value="specific"
				data-id="<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?>" />
		<label for="specific_<?php echo esc_attr( $option['id'] ); ?>"><?php esc_html_e( 'Specific', 'buddyboss-pro' ); ?></label>
	</div>
	<div class="sub-child-wrap access-control-hide-div">
		<?php
		foreach ( $options_lists as $child_option ) {
			?>
			<div class="sub-child-<?php echo esc_attr( sanitize_title( $option['id'] ) ); ?> access-control-hide-div">
				<input
						data-parent="<?php echo esc_attr( $option['id'] ); ?>"
						class="click_class"
						<?php echo esc_attr( $disable ); ?>
						<?php echo esc_attr( $checked ); ?>
						id="<?php echo esc_attr( $key ) . '_' . esc_attr( $option['id'] ); ?>_access-control-options_<?php echo esc_attr( $child_option['id'] ); ?>_sub"
						value="<?php echo esc_attr( $child_option['id'] ); ?>"
						name="<?php echo esc_attr( $key ); ?>[access-control-<?php echo esc_attr( $option['id'] ); ?>-options][]"
						type="checkbox">
				<label for="<?php echo esc_attr( $key ) . '_' . esc_attr( $option['id'] ); ?>_access-control-options_<?php echo esc_attr( $child_option['id'] ); ?>_sub"><?php echo esc_html( $child_option['text'] ); ?></label>
			</div>
			<?php
		}
		?>
	</div><!-- .sub-child-wrap -->
</div>
<?php
