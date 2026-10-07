<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_Category extends WpProQuiz_Model_Model {

	protected $_categoryId   = 0;
	protected $_categoryName = '';

	public function setCategoryId( $_categoryId ) {
		$this->_categoryId = (int) $_categoryId;
		return $this;
	}

	public function getCategoryId() {
		return $this->_categoryId;
	}

	public function setCategoryName( $_categoryName = '' ) {
		$this->_categoryName = (string) $_categoryName;
		return $this;
	}

	public function getCategoryName() {
		return $this->_categoryName;
	}
}
