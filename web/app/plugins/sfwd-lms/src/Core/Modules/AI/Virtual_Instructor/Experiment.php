<?php
/**
 * Virtual Instructor experiment.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use LearnDash\Core\Modules\Experiments\Action_Item;
use LearnDash\Core\Modules\Experiments\Experiment as Experiment_Base;
use LearnDash_Custom_Label;

/**
 * Virtual Instructor experiment class.
 *
 * @since 4.13.0
 */
class Experiment extends Experiment_Base {
	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->id           = 'virtual_instructor';
		$this->title        = LearnDash_Custom_Label::get_label( 'virtual_instructor' );
		$this->description  = sprintf(
			// Translators: %s: virtual instructors label.
			esc_html_x( '%s to interact with your students and assist with their learning.', 'placeholder: virtual instructors label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'virtual_instructors' )
		);
		$this->action_items = [
			new Action_Item(
				[
					'label'    => __( 'Give Feedback', 'learndash' ),
					'url'      => 'https://forms.gle/MYbATTwntU3kZeabA',
					'external' => true,
				]
			),
			new Action_Item(
				[
					'label'    => __( 'Learn More', 'learndash' ),
					'url'      => 'https://go.learndash.com/viexperiment',
					'external' => true,
				]
			),
			new Action_Item(
				[
					'label'   => __( 'Settings', 'learndash' ),
					'url'     => 'edit.php?post_type=' . learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ),
					// Action item is enabled only if the experiment is enabled.
					'enabled' => $this->is_enabled(),
				]
			),
		];

		parent::__construct();
	}

	/**
	 * Sets up the hooks.
	 *
	 * @since 4.13.0
	 */
	protected function setup_hooks(): void {
		learndash_register_provider( Provider::class );
	}
}
