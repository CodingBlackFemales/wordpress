<?php
/**
 * View: Course Enrollment Button.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var string   $payment_buttons Payment Buttons HTML.
 * @var Product  $product         Product model.
 * @var Template $this            Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Payment button HTML.
echo $payment_buttons;
