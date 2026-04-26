<?php
/**
 * Customizer Control Item.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\DTO;

use Learndash_DTO;
use WP_Customize_Setting;

/**
 * Customizer Control Item.
 *
 * @since 4.15.0
 */
class Control extends Learndash_DTO {
	/**
	 * Unique identifier. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $id = '';

	/**
	 * All settings tied to the control. Defaults to the Control ID as a String when not defined.
	 *
	 * This is intentionally not cast to a Setting DTO object to avoid an infinite loop,
	 * as Setting DTO objects have a reference back to its Control.
	 *
	 * @since 4.15.0
	 *
	 * @var array<array{
	 *     id: string,
	 *     type?: string,
	 *     capability?: string,
	 *     theme_supports?: string[],
	 *     default?: mixed,
	 *     transport?: string,
	 *     validate_callback?: callable,
	 *     sanitize_callback?: callable,
	 *     sanitize_js_callback?: callable,
	 *     dirty?: bool,
	 *     selector: string,
	 *     property: string,
	 *     unit?: string,
	 *     important?: bool
	 * }>
	 */
	public array $settings;

	/**
	 * The primary setting for the control (if there is one).
	 * This normally should be left alone unless you're dealing with a Control which is associated with multiple Settings.
	 * In most cases, Controls will handle this itself automatically.
	 * If you still need to manually define this, see WP_Customize_Control's constructor to understand how it is used.
	 *
	 * @since 4.15.0
	 *
	 * @var string|WP_Customize_Setting
	 */
	public $setting;

	/**
	 * Capability required to use this control.
	 *
	 * Normally this is empty and the capability is derived from the capabilities
	 * of the associated `$settings`.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $capability;

	/**
	 * Order priority to load the control in Customizer. 10 is the default priority used by WordPress when creating a control.
	 *
	 * @since 4.15.0
	 *
	 * @var int
	 */
	public int $priority = 10;

	/**
	 * Section the control belongs to.
	 *
	 * @since 4.15.0
	 *
	 * @var Section
	 */
	public Section $section;

	/**
	 * Label for the control. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $label = '';

	/**
	 * Description for the control. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $description = '';

	/**
	 * List of choices for 'radio' or 'select' type controls, where values are the keys, and labels are the values.
	 * Default empty array.
	 *
	 * @since 4.15.0
	 *
	 * @var array<string|int, string>
	 */
	public array $choices = [];

	/**
	 * List of custom input attributes for control output, where attribute names are the keys and values are the values.
	 * Not used for 'checkbox', 'radio', 'select', 'textarea', or 'dropdown-pages' control types.
	 * Default empty array.
	 *
	 * @since 4.15.0
	 *
	 * @var array<string, mixed>
	 */
	public array $input_attrs = [];

	/**
	 * Show UI for adding new content. Only used for the dropdown-pages control. Default false.
	 *
	 * @since 4.15.0
	 *
	 * @var bool
	 */
	public bool $allow_addition = false;

	/**
	 * Control's Type. Default 'text'.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $type = 'text';

	/**
	 * Default callback used when invoking WP_Customize_Control::active().
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
	 * Properties are being cast to the specified type on construction according to the $cast property.
	 * Key is a property name, value is a PHP type which will be passed into "settype".
	 *
	 * @since 4.15.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'id'             => 'string',
		'settings'       => 'array',
		'capability'     => 'string',
		'priority'       => 'int',
		'section'        => Section::class,
		'label'          => 'string',
		'description'    => 'string',
		'choices'        => 'array',
		'input_attrs'    => 'array',
		'allow_addition' => 'bool',
		'type'           => 'string',
	];
}
