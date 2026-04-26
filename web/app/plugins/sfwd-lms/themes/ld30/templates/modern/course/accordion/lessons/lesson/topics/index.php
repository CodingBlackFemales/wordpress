<?php
/**
 * View: Course Accordion Topic - Quizzes.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Topic[]  $topics Array of topic model objects.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if ( empty( $topics ) ) {
	return;
}
?>
<div class="ld-accordion__items ld-accordion__items--lesson-topics">
	<?php foreach ( $topics as $topic ) : ?>
		<?php $this->template( 'modern/course/accordion/lessons/lesson/topics/topic', [ 'topic' => $topic ] ); ?>
	<?php endforeach; ?>
</div>
