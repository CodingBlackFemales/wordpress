<?php
/**
 * Stripe Webhook Validation Handler
 *
 * @since 4.6.0
 * @deprecated 4.20.1
 *
 * @package LearnDash\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LEARNDASH_LMS_PLUGIN_DIR . 'src/deprecated/Core/Payments/Stripe/Webhook_Setup_Validator.php';
