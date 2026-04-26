<?php
/**
 * Customizer Section Item.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\DTO;

use Learndash_DTO;

/**
 * Customizer Section Item.
 *
 * @since 4.15.0
 */
class Section extends Learndash_DTO {
	/**
	 * Unique identifier. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $id = '';

	/**
	 * Priority of the panel, defining the display order of panels and sections.
	 * 160 is the default priority used by WordPress when creating a section.
	 *
	 * @since 4.15.0
	 *
	 * @var int
	 */
	public int $priority = 160;

	/**
	 * Panel in which to show the section, making it a sub-section.
	 * If not defined, the Section effectively becomes a Panel as it is top-level.
	 *
	 * @since 4.15.0
	 *
	 * @var Panel
	 */
	public Panel $panel;

	/**
	 * Capability required for the panel. Default 'edit_theme_options'.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $capability = 'edit_theme_options';

	/**
	 * Theme features required to support the section. Default empty array.
	 *
	 * @since 4.15.0
	 *
	 * @var string[]
	 */
	public array $theme_supports = [];

	/**
	 * Title of the panel to show in UI. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $title = '';

	/**
	 * Description to show in the UI. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $description = '';

	/**
	 * Type of this panel. Default 'default'.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $type = 'default';

	/**
	 * Default callback used when invoking WP_Customize_Section::active().
	 *
	 * @since 4.15.0
	 *
	 * @see WP_Customize_Section::active()
	 *
	 * @var callable Callback is called with one argument, the instance of WP_Customize_Section,
	 * and returns bool to indicate whether the section is active (such as it relates to the URL currently being previewed).
	 */
	public $active_callback;

	/**
	 * Show the description or hide it behind the help icon. Default false.
	 *
	 * @since 4.15.0
	 *
	 * @var bool Indicates whether the Section's description should be hidden behind a help icon ("?") in the Section header,
	 * similar to how help icons are displayed on Panels.
	 */
	public bool $description_hidden = false;

	/**
	 * Properties are being cast to the specified type on construction according to the $cast property.
	 * Key is a property name, value is a PHP type which will be passed into "settype".
	 *
	 * @since 4.15.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'id'                 => 'string',
		'priority'           => 'int',
		'panel'              => Panel::class,
		'capability'         => 'string',
		'theme_supports'     => 'array',
		'title'              => 'string',
		'description'        => 'string',
		'type'               => 'string',
		'description_hidden' => 'bool',
	];
}
