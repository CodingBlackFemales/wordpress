<?php
/**
 * Frontend Template retrieval class.
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier
 */

namespace LearnDash\Zapier\Templates;

use LearnDash\Zapier\StellarWP\Templates\Template as StellarWP_Template;

/**
 * Frontend Template retrieval class.
 *
 * @since 2.3.2
 */
class Template extends StellarWP_Template {
	/**
	 * Base template for where to look for template.
	 *
	 * @since 2.3.2
	 *
	 * @var string[]
	 */
	protected array $template_base_path = [ LEARNDASH_ZAPIER_PLUGIN_PATH . 'src/views' ];

	/**
	 * Allow changing if class will extract data from the local context.
	 *
	 * @since 2.3.2
	 *
	 * @var boolean
	 */
	protected bool $template_context_extract = true;

	/**
	 * Should we use a lookup into the list of folders to try to find the file.
	 *
	 * @since 2.3.2
	 *
	 * @var boolean
	 */
	protected bool $template_folder_lookup = true;
}
