<?php
/**
 * Quiz creation AI form component.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 *
 * @var array<string, array<string, mixed>> $form_fields LearnDash answer types in key label pair.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form
	class="ld-bg-white ld-shadow-lg ld-rounded-md ld-p-8"
	method="post"
	id="quiz-creation-ai"
	name="quiz_creation_ai"
	action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>"
>
	<?php
	SFWD_LMS::get_view(
		'AI/quiz-creation/components/notices',
		[],
		true
	);
	?>
	<?php
	SFWD_LMS::get_view(
		'AI/quiz-creation/components/fields',
		[
			'form_fields' => $form_fields,
		],
		true
	);
	?>
	<?php
	SFWD_LMS::get_view(
		'AI/quiz-creation/components/button',
		[],
		true
	);
	?>
</form>
