<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_StyleManager extends WpProQuiz_Controller_Controller {

	public function route() {
		$this->show();
	}

	private function show() {
		global $learndash_assets_loaded;

		$filepath = SFWD_LMS::get_template( 'learndash_quiz_front.css', null, null, true );
		if ( ! empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_quiz_front_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_quiz_front_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_quiz_front_css'] = __FUNCTION__;
		}

		$view = new WpProQuiz_View_StyleManager();

		$view->show();
	}
}
