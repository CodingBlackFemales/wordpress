<?php
/**
 * View: Course Accordion - Lessons.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var array<int, string>                               $sections   Section titles indexed by lesson IDs.
 * @var Course                                           $course     Course model object.
 * @var Lesson[]                                         $lessons    Array of lesson model objects.
 * @var array{lessons: array{paged: int, per_page: int}} $pagination Pagination data.
 * @var Template                                         $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if ( empty( $lessons ) ) {
	return;
}
?>
<div
	class="ld-accordion__section ld-accordion__section--lessons"
	data-ld-pagination-target="<?php echo esc_attr( LDLMS_Post_Types::LESSON ); ?>"
>
	<div class="ld-accordion__items ld-accordion__items--lessons">
		<?php foreach ( $lessons as $lesson ) : ?>
			<?php
			$this->template(
				'modern/course/accordion/section',
				[
					'title' => $sections[ $lesson->get_id() ] ?? '',
				]
			);
			?>

			<?php $this->template( 'modern/course/accordion/lessons/lesson', [ 'lesson' => $lesson ] ); ?>
		<?php endforeach; ?>

		<?php $this->template( 'modern/course/accordion/lessons/pagination' ); ?>
	</div>
</div>
