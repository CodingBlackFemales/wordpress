<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * The integration keeps no data of its own beyond cache entries, which expire
 * on their own; glossary entries and references belong to CBF Semantic
 * Glossary and are left in place.
 *
 * @link    https://codingblackfemales.com
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
