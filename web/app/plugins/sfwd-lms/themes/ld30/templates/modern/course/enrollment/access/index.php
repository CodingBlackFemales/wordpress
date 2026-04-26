<?php
/**
 * View: Course Enrollment Access.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product                                  $product        Product model.
 * @var array{king: ?string, subjects: string[]} $access_options Access options.
 * @var Template                                 $this           Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

// If there's no King, there's nothing to show.
if ( empty( $access_options['king'] ) ) {
	return;
}

?>
<div class="ld-enrollment__access">
	<?php $this->template( 'modern/course/enrollment/access/king' ); ?>
</div>
