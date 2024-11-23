<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection AutoloadingIssuesInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace WPForms\Pro\Integrations\AI;

use WPForms\Integrations\AI\AI as LiteAI;
use WPForms\Pro\Integrations\AI\Admin\Builder\Enqueues;
use WPForms\Pro\Integrations\AI\Admin\Pages\Templates;
use WPForms\Pro\Integrations\AI\Admin\Ajax\Forms;

/**
 * Integration of the AI features in Pro.
 *
 * @since 1.9.2
 */
final class AI extends LiteAI {

	/**
	 * Load the integration classes.
	 *
	 * @since 1.9.2
	 */
	public function load() {

		parent::load();

		if ( wpforms_is_admin_page( 'builder' ) ) {
			( new Enqueues() )->init();
		}

		if ( wpforms_is_admin_page( 'templates' ) ) {
			( new Templates() )->init();
		}
	}

	/**
	 * Load AJAX classes.
	 *
	 * @since 1.9.2
	 */
	protected function load_ajax_classes() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found

		parent::load_ajax_classes();

		( new Forms() )->init();
	}
}
