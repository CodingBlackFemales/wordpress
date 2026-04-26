<?php
/**
 * Forms - password field output.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<int, string> $field_classes Field classes.
 * @var string             $field_id      Field id.
 * @var bool               $show_meter    Field should show the password strength meter.
 * @var bool               $show_toggle   Field should show the toggle.
 * @var bool               $is_required   Field is required.
 * @var string             $field_label   Field label.
 * @var string             $field_name    Field name.
 * @var string             $field_value   Field value.
 * @var Template           $this          The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$show_meter  = empty( $show_meter ) ? false : true;
$show_toggle = empty( $show_toggle ) ? false : true;
$extra_attrs = [];

if ( $show_meter ) {
	$field_classes[]                 = 'ld-form__field--needs-password-strength';
	$extra_attrs['aria-describedby'] = 'ld-password-strength__meter';
}

$this->update_arg( 'extra_attrs', $extra_attrs );
$this->update_arg( 'field_classes', $field_classes );
?>
<?php $this->template( 'components/forms/fields/input' ); ?>
<?php if ( $show_toggle ) : ?>
	<button
		type="button"
		class="ld-button ld-button--secondary ld-button--border ld-button__password-visibility-toggle ld--ignore-inline-css"
		aria-label="<?php esc_attr_e( 'Toggle password visibility', 'learndash' ); ?>"
		aria-live="polite"
	>
		<?php esc_html_e( 'Show', 'learndash' ); ?>
	</button>
<?php endif; ?>
