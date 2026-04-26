<?php
/**
 * Experiments.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Experiments;

/**
 * Experiments.
 *
 * @since 4.13.0
 */
class Experiments {
	/**
	 * The handle for the action items javascript.
	 *
	 * @since 4.15.2
	 *
	 * @var string
	 */
	private const JS_ACTION_ITEMS_HANDLE = 'learndash-experiments-action-items';

	/**
	 * Contains the list of enabled experiment instances.
	 *
	 * @since 4.13.0
	 *
	 * @var Experiment[]
	 */
	protected $experiments = [];

	/**
	 * Initializes the module.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_experiments();

		/**
		 * Fires before the experiments are initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 */
		do_action( 'learndash_experiments_init_before', $this->experiments );

		foreach ( $this->experiments as $experiment ) {
			$experiment->init();
		}

		/**
		 * Fires after the experiments are initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 */
		do_action( 'learndash_experiments_init_after', $this->experiments );
	}

	/**
	 * Gets the list of experiments.
	 *
	 * @since 4.13.0
	 *
	 * @return Experiment[]
	 */
	public function get_experiments(): array {
		return $this->experiments;
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 4.15.2
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		wp_register_script(
			self::JS_ACTION_ITEMS_HANDLE,
			LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/admin/modules/experiments/action-items.js',
			[ 'jquery' ],
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);

		wp_enqueue_script( self::JS_ACTION_ITEMS_HANDLE );
	}

	/**
	 * Loads the list of experiments.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	protected function load_experiments(): void {
		/**
		 * Filters the list of experiments.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 *
		 * @return Experiment[] List of experiment instances.
		 */
		$experiments = apply_filters( 'learndash_experiments', [] );

		$experiments = array_filter(
			$experiments,
			function ( $experiment ): bool {
				return $experiment instanceof Experiment;
			}
		);

		$this->experiments = array_values( $experiments );
	}
}
