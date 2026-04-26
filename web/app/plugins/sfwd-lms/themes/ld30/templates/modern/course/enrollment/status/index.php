<?php
/**
 * View: Course Enrollment Status.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product  $product Product model.
 * @var WP_User  $user    WP_User object.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

if ( $product->is_pre_ordered( $user ) ) {
	$this->template( 'modern/course/enrollment/status/pre-ordered' );
}
