<?php
/**
 * Stripe Webhook Validation Handler
 *
 * @since 4.20.1
 *
 * @package LearnDash\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LEARNDASH_LMS_PLUGIN_DIR . 'src/deprecated/Core/Modules/Payments/Gateways/Stripe/Webhook_Setup_Validator.php';
