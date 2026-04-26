<?php
/**
 * View: Course Enrollment.
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

?>
<div class="ld-enrollment">
	<?php if ( $product->is_pre_ordered( $user ) ) : ?>
		<?php $this->template( 'modern/course/enrollment/status' ); ?>
	<?php else : ?>
		<?php $this->template( 'modern/course/enrollment/pricing' ); ?>
	<?php endif; ?>

	<?php $this->template( 'modern/course/enrollment/access' ); ?>

	<?php $this->template( 'modern/course/enrollment/join' ); ?>
</div>
