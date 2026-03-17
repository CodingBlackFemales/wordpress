<?php
/**
 * BuddyBoss SSO Loader.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp access control.
 *
 * @since 2.6.30
 */
function bb_register_sso() {

	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, '2.7.40', '<' )
	) {
		return;
	}

	require_once 'includes/class-bb-sso-exception.php';
	require_once 'lib/Persistent/class-bb-sso-persistent.php';
	require_once 'lib/class-bb-sso-notices.php';
	require_once 'lib/class-bb-sso-rest.php';
	require_once 'lib/class-bb-sso-gdpr.php';
	require_once 'includes/class-bb-social-login-settings.php';
	require_once 'includes/class-bb-sso-provider-oauth.php';
	require_once 'admin/class-bb-social-login-admin.php';
	require_once 'vendor/autoloader.php';
	bb_platform_pro()->sso = new BB_SSO();
}

add_action( 'bp_setup_components', 'bb_register_sso' );
