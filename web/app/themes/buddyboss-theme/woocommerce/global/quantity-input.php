<?php
/**
 * Product quantity inputs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/quantity-input.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 *
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'buddyboss-theme' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'buddyboss-theme' );

// In some cases we wish to display the quantity but not allow for it to be changed.
if ( $max_value && $min_value === $max_value ) {
	$is_readonly = true;
	$input_value = $min_value;
} else {
	$is_readonly = false;
}
?>

<div class="quantity <?php echo $is_readonly ? 'quantity--readonly' : ''; ?>">
	<?php do_action( 'woocommerce_before_quantity_input_field' ); ?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>
	<div class="bs-quantity">
		<div class="qty-nav">
			<div class="quantity-button quantity-down <?php echo ( $input_value === $min_value ? esc_attr( 'limit' ) : '' ); ?>">-</div>
		</div>
		<input
			type="<?php echo $is_readonly ? 'text' : 'number'; ?>"
			<?php echo $is_readonly ? 'readonly="readonly"' : ''; ?>
			id="<?php echo esc_attr( $input_id ); ?>"
			class="input-text qty text"
			name="<?php echo esc_attr( $input_name ); ?>"
			value="<?php echo $input_value ? esc_attr( $input_value ) : esc_attr( $min_value ); ?>"
			title="<?php echo esc_attr_x( 'Qty', 'Product quantity input tooltip', 'buddyboss-theme' ); ?>"
			size="4"
			min="<?php echo esc_attr( $min_value ); ?>"
			max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
			<?php if ( ! $is_readonly ) : ?>
				step="<?php echo esc_attr( $step ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				inputmode="<?php echo esc_attr( $inputmode ); ?>"
				autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
			<?php endif; ?>
		/>
		<div class="qty-nav">
			<div class="quantity-button quantity-up <?php echo ( 0 < $max_value && $input_value === $max_value ? esc_attr( 'limit' ) : '' ); ?>">+</div>
		</div>
	</div>
	<?php do_action( 'woocommerce_after_quantity_input_field' ); ?>
</div>
