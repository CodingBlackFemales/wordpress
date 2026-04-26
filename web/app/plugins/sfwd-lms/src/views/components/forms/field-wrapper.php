<?php
/**
 * Forms - field wrapper.
 *
 * @version 4.16.0
 *
 * @since 4.16.0
 * @var string   $field_id   Field id.
 * @var string   $field_name Field name.
 * @var string   $field_type Field type.
 * @var Template $this       The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;


$extra_attrs = empty( $extra_attrs ) ? [] : $extra_attrs;
$field_name  = empty( $field_name ) ? '' : $field_name;
$field_id    = empty( $field_id ) ? $field_name : $field_id;
$field_value = empty( $field_value ) ? '' : $field_value;
$field_type  = empty( $field_type ) ? 'text' : $field_type;
$field_label = empty( $field_label ) ? '' : $field_label;
$is_required = empty( $is_required ) ? false : true;

$default_field_classes = [
	'ld-form__field',
	'ld-form__field-' . $field_name,
	'ld-form__field--type-' . $field_type,
];

if ( empty( $field_classes ) ) {
	$field_classes = $default_field_classes;
} else {
	$field_classes = array_merge( $default_field_classes, $field_classes );
}

// Update the current rendering args with defaults.
$this->update_arg( 'extra_attrs', $extra_attrs );
$this->update_arg( 'field_classes', $field_classes );
$this->update_arg( 'field_id', $field_id );
$this->update_arg( 'field_label', $field_label );
$this->update_arg( 'field_name', $field_name );
$this->update_arg( 'field_type', $field_type );
$this->update_arg( 'field_value', $field_value );
$this->update_arg( 'is_required', $is_required );
?>
<div class="ld-form__field-wrapper ld-form__field-wrapper--type-<?php echo esc_attr( $field_type ); ?> ld-form__field-<?php echo esc_attr( $field_id ); ?>-wrapper">
	<?php
	switch ( $field_type ) {
		case 'password':
			$this->template( 'components/forms/fields/password' );
			break;
		case 'radio':
			$this->template( 'components/forms/fields/radio' );
			break;
		case 'text':
		default:
			$this->template( 'components/forms/fields/input' );
			break;
	}
	?>
</div>
