<?php
/**
 * Admin Template retrieval class.
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier
 */

namespace LearnDash\Zapier\Templates;

use LearnDash\Zapier\StellarWP\Templates\Template as StellarWP_Template;

/**
 * Admin Template retrieval class.
 *
 * @since 2.3.2
 */
class Admin_Template extends StellarWP_Template {
	/**
	 * Base template for where to look for template.
	 *
	 * @since 2.3.2
	 *
	 * @var string[]
	 */
	protected array $template_base_path = [ LEARNDASH_ZAPIER_PLUGIN_PATH . 'src/admin-views' ];

	/**
	 * Allow changing if class will extract data from the local context.
	 *
	 * @since 2.3.2
	 *
	 * @var boolean
	 */
	protected bool $template_context_extract = true;
}
