<?php
/**
 * View: Group Pricing Recurring.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product  $product Product model.
 * @var Template $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Product;

if ( $product->has_trial() ) {
	$this->template( 'modern/group/enrollment/pricing/recurring/trial' );
} else {
	$this->template( 'modern/group/enrollment/pricing/recurring/no-trial' );
}
