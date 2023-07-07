<?php
/**
 * View: Quiz Header.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Models\Quiz $quiz Quiz model.
 * @var Template $this    Current Instance of template engine rendering this template.
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
<header class="ld-layout__header ld-quiz__header">
	<?php $this->template( 'components/breadcrumbs' ); ?>

	<?php $this->template( 'components/title' ); ?>

	<?php if ( $quiz->get_time_limit_in_seconds() > 0 ) : ?>
		<?php
		$this->template(
			'components/alert',
			[
				'type'    => 'timer',
				'icon'    => 'clock',
				'message' => __( 'Quiz Time Limit', 'learndash' ),
				'action'  => [
					'label' => $quiz->get_time_limit_formatted(),
				],
			]
		);
		?>
	<?php endif; ?>
</header>
