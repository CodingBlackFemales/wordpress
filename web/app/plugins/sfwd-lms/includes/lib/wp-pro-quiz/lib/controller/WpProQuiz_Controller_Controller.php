<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Controller {
	protected $_post   = null;
	protected $_cookie = null;

	/**
	 * @deprecated
	 */
	public function __construct() {
		if ( null === $this->_post ) {
			$this->_post = stripslashes_deep( $_POST );
		}

		if ( null === $this->_cookie && null !== $_COOKIE ) {
			$this->_cookie = stripslashes_deep( $_COOKIE );
		}
	}
}
