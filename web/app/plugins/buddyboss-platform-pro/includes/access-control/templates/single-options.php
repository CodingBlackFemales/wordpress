<?php
/**
 * This template will display the AJAX threaded select box options based on the settings page selected option.
 *
 * @var       $key
 * @var       $disable
 * @var       $checked
 * @var       $ajax
 * @var array $option
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
<div>
	<input <?php echo esc_attr( $disable ); ?> <?php echo esc_attr( $checked ); ?> id="<?php echo esc_attr( $key ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>" value="<?php echo esc_attr( $option['id'] ); ?>" name="<?php echo esc_attr( $key ); ?>[access-control-options][]" type="checkbox">
	<label for="<?php echo esc_attr( $key ); ?>_access-control-options_<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['text'] ); ?></label>
</div>

<?php
