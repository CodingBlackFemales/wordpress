<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Pro\Tasks\Actions\Migration190Task;

/**
 * Class upgrade for 1.9.0 release.
 *
 * @since        1.9.0
 *
 * @noinspection PhpUnused
 */
class Upgrade190 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.9.0
	 *
	 * @return bool|null Upgrade result:
	 *                    true - the upgrade completed successfully,
	 *                    false - in the case of failure,
	 *                    null - upgrade started but not yet finished (background task).
	 */
	public function run() {

		return $this->run_async( Migration190Task::class );
	}
}
