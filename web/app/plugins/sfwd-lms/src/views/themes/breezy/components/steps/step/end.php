<?php
/**
 * View: Step End.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step        Step.
 * @var bool     $is_enrolled Whether the user is enrolled.
 * @var int      $depth       Depth.
 * @var Template $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Steps\Step;
use LearnDash\Core\Template\Template;
?>
<?php if ( 0 === $depth && $step->get_steps_number() > 0 ) : ?>
	</button>
<?php elseif ( $is_enrolled ) : ?>
	</a>
<?php else : ?>
	</div>
<?php endif; ?>
