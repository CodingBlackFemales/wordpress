<?php
class Test_PluginUpdater extends WP_UnitTestCase {

	public function test_get_updater_instance() {
		$updater = Astoundify_PluginUpdater::instance();

		$this->assertInstanceOf( 'Astoundify_PluginUpdater', $updater );
	}

	public function test_get_updater_lib_instance() {
		$sl = new EDD_SL_Plugin_Updater( null, null, null );

		$this->assertInstanceOf( 'EDD_SL_Plugin_Updater', $sl );
	}

}
