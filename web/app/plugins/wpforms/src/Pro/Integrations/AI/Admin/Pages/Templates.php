<?php

namespace WPForms\Pro\Integrations\AI\Admin\Pages;

use WPForms\Pro\Integrations\AI\Admin\Builder\Enqueues;

/**
 * Enqueue assets on the Form Templates admin page in Pro.
 *
 * @since 1.9.2
 */
class Templates {

	/**
	 * The Builder enqueues class instance.
	 *
	 * @since 1.9.2
	 *
	 * @var Enqueues
	 */
	private $builder_enqueues;

	/**
	 * Initialize.
	 *
	 * @since 1.9.2
	 */
	public function init() {

		$this->hooks();

		$this->builder_enqueues = new Enqueues();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.9.2
	 */
	private function hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @since 1.9.2
	 */
	public function enqueues() {

		$this->builder_enqueues->enqueues( 'setup' );
	}
}
