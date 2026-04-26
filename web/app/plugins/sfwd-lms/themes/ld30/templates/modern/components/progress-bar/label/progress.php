<?php
/**
 * View: Progress Bar Label - In Progress.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Progress_Bar $progress_bar   Progress Bar.
 * @var string       $label_progress Progress label.
 * @var Template     $this           Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;

if ( empty( $label_progress ) ) {
	$label_progress = sprintf(
		// translators: placeholder: post type label.
		__( '%s Progress', 'learndash' ),
		$progress_bar->get_label()
	);
}

?>

<div class="ld-progress-bar__label ld-progress-bar__label--progress">
	<?php echo esc_html( $label_progress ); ?>
</div>
