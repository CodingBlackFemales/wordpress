<?php
/**
 * View: Progress Bar Label - Complete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Progress_Bar $progress_bar   Progress Bar.
 * @var string       $label_complete Complete label.
 * @var Template     $this           Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;

if ( empty( $label_complete ) ) {
	$label_complete = sprintf(
		// translators: placeholder: post type label.
		__( '%s Complete', 'learndash' ),
		$progress_bar->get_label()
	);
}

?>

<div class="ld-progress-bar__label ld-progress-bar__label--complete">
	<?php
	$this->template(
		'components/icons/check-circle',
		[
			'classes' => [
				'ld-progress-bar__label-icon',
				'ld-progress-bar__label-icon--complete',
			],
		]
	);
	?>

	<?php echo esc_html( $label_complete ); ?>
</div>
