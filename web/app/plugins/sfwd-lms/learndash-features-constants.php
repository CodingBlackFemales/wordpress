<?php
/**
 * LearnDash features constants
 *
 * NOTE: All constants defined here are for development purposes only and should not be used on production sites.
 *       They are used to enable in progress features and should be removed before the final release of the feature.
 *
 * @since 4.6.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEARNDASH_ENABLE_IN_PROGRESS_FEATURES' ) ) {
	/**
	 * Enable in progress features.
	 *
	 * NOTE: it is only for development purposes. DO NOT enable it on production sites.
	 *
	 * @since 4.6.0
	 *
	 * @var bool $value True to enable in progress features. Default is false.
	 */
	define( 'LEARNDASH_ENABLE_IN_PROGRESS_FEATURES', false );
}

if ( ! defined( 'LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE' ) ) {
	/**
	 * Enable the Breezy template.
	 *
	 * @since 4.6.0
	 *
	 * @var bool $value True to enable the feature. Default is false.
	 */
	define( 'LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE', false );
}
