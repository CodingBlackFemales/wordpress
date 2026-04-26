<?php
/**
 * Experiment action item class file.
 *
 * @since 4.15.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Experiments;

/**
 * Experiment action item class.
 *
 * @since 4.15.2
 */
class Action_Item {
	/**
	 * Label.
	 *
	 * @since 4.15.2
	 *
	 * @var string
	 */
	private string $label = '';

	/**
	 * URL.
	 *
	 * @since 4.15.2
	 *
	 * @var string
	 */
	private string $url = '';

	/**
	 * Whether the action item is enabled or not. Default true.
	 *
	 * @since 4.15.2
	 *
	 * @var bool
	 */
	private bool $enabled = true;

	/**
	 * Whether the action item target is external or not. Default false.
	 *
	 * @since 4.15.2
	 *
	 * @var bool
	 */
	private bool $external = false;

	/**
	 * Constructor.
	 *
	 * @since 4.15.2
	 *
	 * @param array{ 'label'?: string, 'url'?: string, 'enabled'?: bool, 'external'?: bool } $args Arguments.
	 */
	public function __construct( array $args ) {
		$this->label    = $args['label'] ?? '';
		$this->url      = $args['url'] ?? '';
		$this->enabled  = $args['enabled'] ?? $this->enabled;
		$this->external = $args['external'] ?? $this->external;
	}

	/**
	 * Gets label.
	 *
	 * @since 4.15.2
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Gets URL.
	 *
	 * @since 4.15.2
	 *
	 * @return string
	 */
	public function get_url(): string {
		return $this->url;
	}

	/**
	 * Checks if the action item is enabled.
	 *
	 * @since 4.15.2
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Checks if the action item target is external.
	 *
	 * @since 4.15.2
	 *
	 * @return bool
	 */
	public function is_external(): bool {
		return $this->external;
	}
}
