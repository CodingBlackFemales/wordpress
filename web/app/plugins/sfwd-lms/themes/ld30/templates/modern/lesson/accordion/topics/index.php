<?php
/**
 * View: Lesson Accordion - Topics.
 *
 * @since 4.24.0
 * @version 4.24.0
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
<div
	class="ld-accordion__section ld-accordion__section--topics"
	data-ld-pagination-target="<?php echo esc_attr( LDLMS_Post_Types::TOPIC ); ?>"
>
	<?php foreach ( $topics as $topic ) : ?>
		<?php $this->template( 'modern/lesson/accordion/topics/topic', [ 'topic' => $topic ] ); ?>
	<?php endforeach; ?>

	<?php $this->template( 'modern/lesson/accordion/topics/pagination' ); ?>
</div>
