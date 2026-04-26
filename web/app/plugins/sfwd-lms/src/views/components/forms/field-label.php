<?php
/**
 * Forms - field label.
 *
 * @version 4.21.3
 * @since 4.16.0
 *
 * @var string   $field_id    Field id.
 * @var string   $field_label Field label.
 * @var string   $field_name  Field name.
 * @var string   $field_type  Field type.
 * @var bool     $is_required Field is required.
 * @var Template $this        The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$field_name  = empty( $field_name ) ? '' : $field_name;
$field_id    = empty( $field_id ) ? '' : $field_id;
$is_required = empty( $is_required ) ? false : true;
$field_type  = empty( $field_type ) ? 'text' : $field_type;

if ( empty( $field_label ) ) {
	return;
}

$required_indicator = '<span class="required">' . esc_html__( '(required)', 'learndash' ) . '</span>';
?>
<label for="<?php echo esc_attr( $field_id ); ?>">
	<?php echo esc_html( $field_label ); ?>
	<?php if ( $is_required ) : ?>
		<?php echo wp_kses_post( $required_indicator ); ?>
	<?php endif; ?>
</label>
