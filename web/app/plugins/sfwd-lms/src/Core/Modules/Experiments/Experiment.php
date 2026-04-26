<?php
/**
 * Experiment.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Experiments;

use LearnDash\Core\Utilities\Cast;

/**
 * Experiment base class.
 *
 * @since 4.13.0
 */
abstract class Experiment {
	/**
	 * Option name.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'learndash_experiments';

	/**
	 * ID. The ID should be unique. Default empty string.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Title. The title should be not empty. Default empty string.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Description. Description can be empty. Default empty string.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * URL. Url can be empty. Default empty string.
	 *
	 * @since 4.13.0
	 * @deprecated 4.15.2
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Action items.
	 *
	 * @since 4.15.2
	 *
	 * @var Action_Item[]
	 */
	protected array $action_items = [];

	/**
	 * Constructor.
	 *
	 * @since 4.15.2
	 */
	public function __construct() {
		// Backward compatibility for the $url property.
		if (
			! empty( $this->url )
			&& empty( $this->action_items )
		) {
			$this->action_items = [
				new Action_Item(
					[
						'label' => __( 'Give Feedback', 'learndash' ),
						'url'   => $this->url,
					]
				),
			];
		}
	}

	/**
	 * Sets up the experiment hooks.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	abstract protected function setup_hooks(): void;

	/**
	 * Gets the experiment ID.
	 *
	 * @since 4.13.0
	 *
	 * @return string Experiment ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Gets the experiment title.
	 *
	 * @since 4.13.0
	 *
	 * @return string Experiment title.
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Gets the experiment description.
	 *
	 * @since 4.13.0
	 *
	 * @return string Experiment description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Gets the experiment url.
	 *
	 * @since 4.13.0
	 * @deprecated 4.15.2
	 *
	 * @return string Experiment url.
	 */
	public function get_url(): string {
		// Deprecate the method as the $url property is also deprecated.
		_deprecated_function( __METHOD__, '4.15.2' );

		return $this->url;
	}

	/**
	 * Gets action items.
	 *
	 * @since 4.15.2
	 *
	 * @return Action_Item[]
	 */
	public function get_action_items(): array {
		return array_filter(
			$this->action_items,
			static function ( $action_item ) {
				return $action_item->is_enabled();
			}
		);
	}

	/**
	 * Checks if the experiment is enabled.
	 *
	 * @since 4.13.0
	 *
	 * @return bool True if the experiment is enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return ! empty( $this->id ) && in_array( $this->id, $this->get_enabled_experiment_ids(), true );
	}

	/**
	 * Initializes the experiment.
	 * If the experiment is not enabled, it does nothing.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/**
		 * Fires before the experiment is initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param string     $id         Experiment ID.
		 * @param Experiment $experiment Experiment instance.
		 */
		do_action( 'learndash_experiments_experiment_init_before', $this->id, $this );

		$this->setup_hooks();

		/**
		 * Fires after the experiment is initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param string     $id         Experiment ID.
		 * @param Experiment $experiment Experiment instance.
		 */
		do_action( 'learndash_experiments_experiment_init_after', $this->id, $this );
	}

	/**
	 * Returns the list of IDs of enabled experiments.
	 *
	 * @since 4.13.0
	 *
	 * @return string[]
	 */
	protected function get_enabled_experiment_ids(): array {
		$ids = array_filter(
			(array) get_option( self::OPTION_NAME, [] )
		);
		$ids = array_keys( $ids );

		return array_map(
			[ Cast::class, 'to_string' ],
			$ids
		);
	}
}
