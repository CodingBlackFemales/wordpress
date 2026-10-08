<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Request {
	protected static $_post   = null;
	protected static $_cookie = null;

	public static function getPost() {
		if ( null == self::$_post ) {
			self::$_post = self::clear( $_POST );
		}

		return self::$_post;
	}

	public static function getPostValue( $name ) {
		if ( null == self::$_post ) {
			self::$_post = self::clear( $_POST );
		}

		return isset( self::$_post[ $name ] ) ? self::$_post[ $name ] : null;
	}

	public static function getCookie() {
		if ( null == self::$_post ) {
			self::$_cookie = self::clear( $_COOKIE );
		}

		return self::$_cookie;
	}

	private static function clear( $data ) {
		if ( null !== $data ) {
			return stripslashes_deep( $data );
		}

		return array();
	}
}
