<?php
/**
 * View: Progress Bar Label.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Progress_Bar $progress_bar Progress Bar.
 * @var Template     $this         Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;

if ( $progress_bar->is_complete() ) : ?>
	<?php $this->template( 'modern/components/progress-bar/label/complete' ); ?>
<?php else : ?>
	<?php $this->template( 'modern/components/progress-bar/label/progress' ); ?>
	<?php
endif;
