<?php
/**
 * Forms - radio button.
 *
 * @since 4.16.0
 * @version 4.21.0
 *
 * @var array<string, int|string|null> $extra_attrs Field attributes.
 * @var string                         $field_id    Radio button id.
 * @var bool                           $is_selected Radio button is
 * @var string                         $field_label Radio button label.
 * @var string                         $field_name  Radio button name.
 * @var string                         $field_value Radio button value.selected.
 * @var Template                       $this        The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( empty( $extra_attrs ) ) {
	$extra_attrs = [];
}

if ( $is_selected ) {
	$extra_attrs['checked'] = 'checked';
}

$this->update_arg( 'extra_attrs', $extra_attrs );
$this->update_arg( 'field_type', 'radio' );
?>
<label class="ld-form__field-wrapper ld-form__field-wrapper--type-radio ld-form__field-wrapper--type-svgradio" for="<?php echo esc_attr( $field_id ); ?>">
	<?php $this->template( 'components/forms/fields/input' ); ?>
	<?php
	$this->template(
		'components/icons/radio',
		[
			'is_aria_hidden' => true,
		]
	);
	?>
	<span>
		<?php echo esc_html( $field_label ); ?>
	</span>
</label>
