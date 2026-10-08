<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Template {
	public static function ajaxEditTemplate( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			return wp_json_encode( array() );
		}

		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		$template = new WpProQuiz_Model_Template( $data );

		$templateMapper->updateName( $template->getTemplateId(), $template->getName() );

		return wp_json_encode( array() );
	}

	public static function ajaxDeleteTemplate( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			return wp_json_encode( array() );
		}

		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		$template = new WpProQuiz_Model_Template( $data );

		$templateMapper->delete( $template->getTemplateId() );

		return wp_json_encode( array() );
	}
}
