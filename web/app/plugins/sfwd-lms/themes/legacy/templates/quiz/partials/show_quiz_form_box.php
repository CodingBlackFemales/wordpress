<?php
/**
 * Displays Quiz Form Box
 *
 * Available Variables:
 *
 * @var object $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz      WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$info = '<div class="wpProQuiz_invalidate">' . esc_html__( 'You must fill out this field.', 'learndash' ) . '</div>';

$validateText = array(
	WpProQuiz_Model_Form::FORM_TYPE_NUMBER => esc_html__( 'You must specify a number.', 'learndash' ),
	WpProQuiz_Model_Form::FORM_TYPE_TEXT   => esc_html__( 'You must specify a text.', 'learndash' ),
	WpProQuiz_Model_Form::FORM_TYPE_EMAIL  => esc_html__( 'You must specify an email address.', 'learndash' ),
	WpProQuiz_Model_Form::FORM_TYPE_DATE   => esc_html__( 'You must specify a date.', 'learndash' ),
);
?>
<div class="wpProQuiz_forms">
	<table>
		<tbody>

		<?php
		$index = 0;
		foreach ( $quiz_view->forms as $form ) {
			/* @var $form WpProQuiz_Model_Form */

			$id   = 'forms_' . $quiz->getId() . '_' . $index ++;
			$name = 'wpProQuiz_field_' . $form->getFormId();
			?>
			<tr>
				<td>
					<?php
					echo '<label for="' . $id . '">';
					echo esc_html( $form->getFieldname() );
					echo $form->isRequired() ? '<span class="wpProQuiz_required">*</span>' : '';
					echo '</label>';
					?>
				</td>
				<td>

					<?php
					switch ( $form->getType() ) {
						case WpProQuiz_Model_Form::FORM_TYPE_TEXT:
						case WpProQuiz_Model_Form::FORM_TYPE_EMAIL:
						case WpProQuiz_Model_Form::FORM_TYPE_NUMBER:
							echo '<input name="' . esc_attr( $name ) . '" autocomplete="off" id="' . esc_attr( $id ) . '" type="text" ',
								'data-required="' . (int) $form->isRequired() . '" data-type="' . esc_attr( $form->getType() ) . '" data-form_id="' . esc_attr( $form->getFormId() ) . '">';
							break;
						case WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA:
							echo '<textarea rows="5" cols="20" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" ',
								'data-required="' . (int) $form->isRequired() . '" data-type="' . esc_attr( $form->getType() ) . '" data-form_id="' . esc_attr( $form->getFormId() ) . '"></textarea>';
							break;
						case WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX:
							echo '<input name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" type="checkbox" value="1"',
								'data-required="' . (int) $form->isRequired() . '" data-type="' . esc_attr( $form->getType() ) . '" data-form_id="' . esc_attr( $form->getFormId() ) . '">';
							break;
						case WpProQuiz_Model_Form::FORM_TYPE_DATE:
							echo '<div data-required="' . (int) $form->isRequired() . '" data-type="' . esc_attr( $form->getType() ) . '" class="wpProQuiz_formFields" data-form_id="' . esc_attr( $form->getFormId() ) . '">';
							echo WpProQuiz_Helper_Until::getDatePicker( get_option( 'date_format', 'j. F Y' ), $name );
							echo '</div>';
							break;
						case WpProQuiz_Model_Form::FORM_TYPE_RADIO:
							echo '<div data-required="' . (int) $form->isRequired() . '" data-type="' . esc_attr( $form->getType() ) . '" class="wpProQuiz_formFields" data-form_id="' . esc_attr( $form->getFormId() ) . '">';

							if ( $form->getData() !== null ) {
								foreach ( $form->getData() as $data ) {
									echo '<label>';
									echo '<input name="' . esc_attr( $name ) . '" type="radio" value="' . esc_attr( $data ) . '"> ',
									esc_html( $data );
									echo '</label> ';
								}
							}

							echo '</div>';

							break;
						case WpProQuiz_Model_Form::FORM_TYPE_SELECT:
							if ( $form->getData() !== null ) {
								echo '<select name="' . $name . '" id="' . $id . '" ',
									'data-required="' . (int) $form->isRequired() . '" data-type="' . $form->getType() . '" data-form_id="' . $form->getFormId() . '">';
								echo '<option value=""></option>';

								foreach ( $form->getData() as $data ) {
									echo '<option value="' . esc_attr( $data ) . '">', esc_html( $data ), '</option>';
								}

								echo '</select>';
							}
							break;
						case WpProQuiz_Model_Form::FORM_TYPE_YES_NO:
							echo '<div data-required="' . (int) $form->isRequired() . '" data-type="' . $form->getType() . '" class="wpProQuiz_formFields" data-form_id="' . $form->getFormId() . '">';
							echo '<label>';
							echo '<input name="' . $name . '" type="radio" value="1"> ',
							esc_html__( 'Yes', 'learndash' );
							echo '</label> ';

							echo '<label>';
							echo '<input name="' . $name . '" type="radio" value="0"> ',
							esc_html__( 'No', 'learndash' );
							echo '</label> ';
							echo '</div>';
							break;
					}

					if ( isset( $validate_array[ $form->getType() ] ) ) {
						echo '<div class="wpProQuiz_invalidate">' . $validateText[ $form->getType() ] . '</div>';
					} else {
						echo '<div class="wpProQuiz_invalidate">' . esc_html__( 'You must fill out this field.', 'learndash' ) . '</div>';
					}
					?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

</div>
