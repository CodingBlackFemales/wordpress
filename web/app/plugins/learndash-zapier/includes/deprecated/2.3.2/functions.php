<?php
/**
 * Deprecated functions from LD 2.3.2.
 * The functions will be removed in a later version.
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check and set dependencies.
 *
 * @deprecated 2.3.2
 *
 * @return void
 */
function learndash_zapier_check_dependency() {
	_deprecated_function( __FUNCTION__, '2.3.2' );

	include LEARNDASH_ZAPIER_PLUGIN_PATH . 'src/deprecated/class-dependency-check.php';

	LearnDash_Dependency_Check_LD_Zapier::get_instance()->set_dependencies(
		[
			'sfwd-lms/sfwd_lms.php' => [
				'label'       => '<a href="https://learndash.com">LearnDash LMS</a>',
				'class'       => 'SFWD_LMS',
				'min_version' => '3.0.0',
			],
		]
	);

	LearnDash_Dependency_Check_LD_Zapier::get_instance()->set_message(
		__( 'LearnDash LMS - Zapier Integration Add-on requires the following plugin(s) to be active:', 'learndash-zapier' )
	);
}
