<?php
/**
 * View: PayPal Checkout text list.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var array<string> $list  List of values.
 * @var string        $class Class name.
 *
 * @package LearnDash\Core
 */

if ( empty( $list ) ) {
	return;
}

$class      = ! empty( $class )
	? $class
	: '';
$class_list = ! empty( $class )
	? $class . '-item'
	: '';

?>
<ul class="<?php echo esc_attr( $class ); ?>">
	<?php foreach ( $list as $item ) : ?>
		<li class="<?php echo esc_attr( $class_list ); ?>">
			<?php echo esc_html( $item ); ?>
		</li>
	<?php endforeach; ?>
</ul>
