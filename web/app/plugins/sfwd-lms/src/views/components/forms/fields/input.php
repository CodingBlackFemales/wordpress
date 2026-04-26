<?php
/**
 * Forms - text field output.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<string, int|string|null> $extra_attrs   Field attributes.
 * @var array<int, string>             $field_classes Field classes.
 * @var string                         $field_id      Field id.
 * @var bool                           $is_required   Field is required.
 * @var string                         $field_label   Field label.
 * @var string                         $field_name    Field name.
 * @var string                         $field_type    Field type.
 * @var string                         $field_value   Field value.
 * @var Template                       $this          The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;

$field_attrs   = [];
$field_classes = empty( $field_classes ) ? [] : $field_classes;

if ( empty( $extra_attrs ) ) {
	$extra_attrs = [];
}

foreach ( $extra_attrs as $attr => $value ) {
	if ( $value === null ) {
		$field_attrs[] = esc_attr( $attr );
	}
	$field_attrs[] = esc_attr( $attr ) . '="' . esc_attr( Cast::to_string( $value ) ) . '"';
}
?>
<input
	<?php echo esc_attr( $is_required ? 'required' : '' ); ?>
	type="<?php echo esc_attr( $field_type ); ?>"
	id="<?php echo esc_attr( $field_id ); ?>"
	name="<?php echo esc_attr( $field_name ); ?>"
	value="<?php echo esc_attr( $field_value ); ?>"
	class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>"
	<?php
	echo implode(
		' ',
		$field_attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	?>
/>
