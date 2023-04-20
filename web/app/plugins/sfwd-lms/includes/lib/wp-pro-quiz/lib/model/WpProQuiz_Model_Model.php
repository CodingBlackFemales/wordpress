<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_Model {

	/**
	 * @var WpProQuiz_Model_QuizMapper
	 */
	protected $_mapper = null;

	public function __construct( $array = null ) {
		$this->setModelData( $array );
	}

	public function setModelData( $array ) {
		if ( null != $array ) {
			$n = explode( ' ', implode( '', array_map( 'ucfirst', explode( '_', implode( ' _', array_keys( $array ) ) ) ) ) );

			$a = array_combine( $n, $array );

			if ( isset( $a['Id'] ) ) {
				$this->setId( $a['Id'] );
			}

			foreach ( $a as $k => $v ) {
				$this->{'set' . $k}( $v );
			}
		}
	}

	public function __call( $name, $args ) {
	}

	/**
	 *
	 * @return WpProQuiz_Model_QuizMapper
	 */
	public function getMapper() {
		if ( null === $this->_mapper ) {
			$this->_mapper = new WpProQuiz_Model_QuizMapper();
		}

		return $this->_mapper;
	}

	/**
	 * @param WpProQuiz_Model_QuizMapper $mapper
	 * @return WpProQuiz_Model_Model
	 */
	public function setMapper( $mapper ) {
		$this->_mapper = $mapper;
		return $this;
	}
}
