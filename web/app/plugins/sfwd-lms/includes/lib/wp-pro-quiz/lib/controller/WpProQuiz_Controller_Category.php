<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Category {

	public static function ajaxAddCategory( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			return wp_json_encode( array() );
		}

		$categoryMapper = new WpProQuiz_Model_CategoryMapper();

		$category = new WpProQuiz_Model_Category( $data );

		$categoryMapper->save( $category );

		return wp_json_encode(
			array(
				'categoryId'   => $category->getCategoryId(),
				'categoryName' => stripslashes( $category->getCategoryName() ),
			)
		);
	}

	public static function ajaxEditCategory( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			return wp_json_encode( array() );
		}

		$categoryMapper = new WpProQuiz_Model_CategoryMapper();

		$category = new WpProQuiz_Model_Category( $data );

		$categoryMapper->save( $category );

		return wp_json_encode( array() );
	}

	public static function ajaxDeleteCategory( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			return wp_json_encode( array() );
		}

		$categoryMapper = new WpProQuiz_Model_CategoryMapper();

		$category = new WpProQuiz_Model_Category( $data );

		$categoryMapper->delete( $category->getCategoryId() );

		return wp_json_encode( array() );
	}
}
