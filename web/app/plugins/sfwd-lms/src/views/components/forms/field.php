<?php
/**
 * Forms - generic field output.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<int, string> $wrap_classes Field wrapper classes.
 * @var Template           $this         The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$default_wrap_classes = [
	'ld-form__field-outer-wrapper',
];

if ( empty( $wrap_classes ) ) {
	$wrap_classes = $default_wrap_classes;
} else {
	$wrap_classes = array_merge( $default_wrap_classes, $wrap_classes );
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrap_classes ) ); ?>">
	<?php $this->template( 'components/forms/field-label' ); ?>
	<?php $this->template( 'components/forms/field-wrapper' ); ?>
</div>
