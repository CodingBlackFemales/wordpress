<?php
/**
 * View: Virtual Instructor chatbox header.
 *
 * @since 4.13.0
 * @version 4.13.0
 *
 * @var Virtual_Instructor $model    Virtual Instructor model instance.
 * @var Template           $this     Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Virtual_Instructor;
use LearnDash\Core\Template\Template;
?>
<div class="ld-virtual-instructor-chatbox__header">
	<img
		class="ld-virtual-instructor-chatbox__avatar"
		src="<?php echo esc_url( $model->get_avatar_url() ); ?>"
		alt="<?php esc_attr_e( 'Avatar', 'learndash' ); ?>"
	/>

	<div class="ld-virtual-instructor-chatbox__heading-wrapper">
		<h1 class="ld-virtual-instructor-chatbox__heading">
			<?php
			printf(
				// translators: placeholder: virtual Instructor.
				esc_html_x( 'Chat with a %s', 'placeholder: virtual instructor', 'learndash' ),
				esc_html( LearnDash_Custom_Label::get_label( 'virtual_instructor' ) )
			);
			?>
		</h1>

		<h2 class="ld-virtual-instructor-chatbox__subheading">
			<?php echo esc_html( $model->get_name() ); ?>
		</h2>
	</div>

	<button
		class="ld-virtual-instructor-chatbox__header-button ld-virtual-instructor-chatbox__header-button--open"
		id="ld-virtual-instructor-chatbox__header-button"
	>
	</button>
</div>
