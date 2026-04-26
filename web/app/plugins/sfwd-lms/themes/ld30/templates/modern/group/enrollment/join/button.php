<?php
/**
 * View: Group Enrollment Button.
 *
 * @since 4.22.0
 * @version 4.22.0
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
