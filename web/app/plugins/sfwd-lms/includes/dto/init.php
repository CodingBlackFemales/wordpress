<?php
/**
 * LearnDash DTO.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Basic validation.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/validation/interface-learndash-dto-property-validator.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/validation/class-learndash-dto-property-validation-result.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/validation/class-learndash-dto-validation-exception.php';

// Basic DTO.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-dto.php';

// Validators.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/validation/class-learndash-dto-property-validator-string-case.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/validation/class-learndash-dto-property-validator-possible-values.php';

// DTOs.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-pricing-dto.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-coupon-dto.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-transaction-meta-dto.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-transaction-coupon-dto.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/dto/class-learndash-transaction-gateway-transaction-dto.php';
