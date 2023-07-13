<?php
/**
 * View: Step Icon.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step  Step.
 * @var int      $depth Depth of the step.
 * @var Template $this  Current Instance of template engine rendering this template.
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

if ( empty( $step->get_icon() ) || 0 === $depth ) {
	return;
}

$this->template( 'components/icons/' . $step->get_icon(), [ 'classes' => [ 'ld-icon--lg' ] ] );

