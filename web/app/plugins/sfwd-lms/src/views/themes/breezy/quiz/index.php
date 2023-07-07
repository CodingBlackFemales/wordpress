<?php
/**
 * View: Quiz Page.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array<int, mixed> $questions Array of questions to render.
 * @var Models\Quiz       $quiz      Quiz model.
 * @var Template          $this      Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;
?>
<div class="<?php learndash_the_wrapper_class( null, 'ld-quiz' ); ?>">
	<?php $this->template( 'quiz/header' ); ?>

	<?php $this->template( 'quiz/content' ); ?>

	<?php foreach ( $questions as $question ) : ?>

	<?php endforeach; ?>

	<?php $this->template( 'quiz/footer' ); ?>
</div>
