<?php
/**
 * Customizer Setting Item.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\DTO;

use Learndash_DTO;

/**
 * Customizer Setting Item.
 *
 * @since 4.15.0
 */
class Setting extends Learndash_DTO {
	/**
	 * Unique identifier. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $id = '';

	/**
	 * Type of customize settings. Default 'theme_mod'.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $type = 'theme_mod';

	/**
	 * Capability required to edit this setting. Default 'edit_theme_options'.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $capability = 'edit_theme_options';

	/**
	 * Theme features required to support the setting. Default empty array.
	 *
	 * @since 4.15.0
	 *
	 * @var string[]
	 */
	public array $theme_supports = [];

	/**
	 * The default value for the setting. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var mixed
	 */
	public $default = '';

	/**
	 * Options for rendering the live preview of changes in Customizer.
	 * Set this value to 'postMessage' to enable a custom JavaScript handler to render changes to this setting as opposed
	 * to reloading the whole page. 'refresh' will force the whole page to refresh instead.
	 * WordPress normally defaults this to 'refresh', but Settings DTOs will default to 'postMessage' instead.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $transport = 'postMessage';

	/**
	 * Server-side validation callback for the setting's value.
	 *
	 * @since 4.15.0
	 *
	 * @var callable
	 */
	public $validate_callback;

	/**
	 * Callback to filter a Customize setting value in un-slashed form.
	 *
	 * @since 4.15.0
	 *
	 * @var callable
	 */
	public $sanitize_callback;

	/**
	 * Callback to convert a Customize PHP setting value to a value that is JSON serializable.
	 *
	 * @since 4.15.0
	 *
	 * @var callable
	 */
	public $sanitize_js_callback;

	/**
	 * Whether or not the setting is initially "dirty" when created.
	 * This is used to ensure that a setting will be sent from the pane to the preview when loading the Customizer.
	 * Normally a setting only is synced to the preview if it has been changed. This allows the setting to be sent from the start.
	 * Default false.
	 *
	 * @since 4.15.0
	 *
	 * @var bool
	 */
	public bool $dirty = false;

	/**
	 * CSS Selector associated with this Setting on the frontend. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $selector = '';

	/**
	 * CSS Property to modify using this Setting on the frontend. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $property = '';

	/**
	 * Unit to append to the value of the Setting on the frontend. Default empty string.
	 *
	 * @since 4.15.0
	 *
	 * @var string
	 */
	public string $unit = '';

	/**
	 * Whether to append !important to the value of the Setting on the frontend. Default false.
	 *
	 * @since 4.15.0
	 *
	 * @var bool
	 */
	public bool $important = false;

	/**
	 * Whether this Setting supports specific additional CSS transformations.
	 * Sometimes, we need to apply transformations to the provided setting in some scenarios (like on button hover)
	 * that we won't expose as customizer controls in order to avoid overwhelming the user with too many options and
	 * to attempt to apply a base level of accessibility.
	 *
	 * @since 4.21.3
	 *
	 * @var string[]
	 */
	public array $supports = [];

	/**
	 * Control object the Setting is registered for.
	 *
	 * @since 4.15.0
	 *
	 * @var Control
	 */
	public Control $control;

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
		'type'           => 'string',
		'capability'     => 'string',
		'theme_supports' => 'array',
		'transport'      => 'string',
		'dirty'          => 'bool',
		'selector'       => 'string',
		'property'       => 'string',
		'unit'           => 'string',
		'important'      => 'bool',
		'supports'       => 'array',
		'control'        => Control::class,
	];
}
