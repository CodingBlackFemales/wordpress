<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_Form extends WpProQuiz_Model_Model {
	const FORM_TYPE_TEXT     = 0;
	const FORM_TYPE_TEXTAREA = 1;
	const FORM_TYPE_NUMBER   = 2;
	const FORM_TYPE_CHECKBOX = 3;
	const FORM_TYPE_EMAIL    = 4;
	const FORM_TYPE_YES_NO   = 5;
	const FORM_TYPE_DATE     = 6;
	const FORM_TYPE_SELECT   = 7;
	const FORM_TYPE_RADIO    = 8;

	protected $_formId    = 0;
	protected $_quizId    = 0;
	protected $_fieldname = '';
	protected $_type      = 0;
	protected $_required  = false;
	protected $_sort      = 0;
	protected $_data      = null;

	public function setFormId( $_formId ) {
		$this->_formId = (int) $_formId;
		return $this;
	}

	public function getFormId() {
		return $this->_formId;
	}

	public function setQuizId( $_quizId ) {
		$this->_quizId = (int) $_quizId;
		return $this;
	}

	public function getQuizId() {
		return $this->_quizId;
	}

	public function setFieldname( $_fieldname ) {
		$this->_fieldname = (string) $_fieldname;
		return $this;
	}

	public function getFieldname() {
		return $this->_fieldname;
	}

	public function setType( $_type ) {
		$this->_type = (int) $_type;
		return $this;
	}

	public function getType() {
		return $this->_type;
	}

	public function setRequired( $_required ) {
		$this->_required = (bool) $_required;
		return $this;
	}

	public function isRequired() {
		return $this->_required;
	}

	public function setSort( $_sort ) {
		$this->_sort = (int) $_sort;
		return $this;
	}

	public function getSort() {
		return $this->_sort;
	}

	public function setData( $_data ) {
		$this->_data = null === $_data ? null : (array) $_data;
		return $this;
	}

	public function getData() {
		return $this->_data;
	}

	public function getValue( $form_data = '' ) {
		switch ( $this->getType() ) {
			case self::FORM_TYPE_TEXT:
			case self::FORM_TYPE_TEXTAREA:
			case self::FORM_TYPE_EMAIL:
			case self::FORM_TYPE_NUMBER:
			case self::FORM_TYPE_RADIO:
			case self::FORM_TYPE_SELECT:
				return esc_html( $form_data );

			case self::FORM_TYPE_CHECKBOX:
				return '1' == $form_data ? esc_html__( 'ticked', 'learndash' ) : esc_html__( 'not ticked', 'learndash' );

			case self::FORM_TYPE_YES_NO:
				return 1 == $form_data ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' );

			case self::FORM_TYPE_DATE:
				return date_format( date_create( $form_data ), get_option( 'date_format' ) );

			default:
				return $form_data;
		}
	}

	public function get_object_as_array() {

		$object_vars = array(
			'_formId'    => $this->getFormId(),
			'_quizId'    => $this->getQuizId(),
			'_fieldname' => $this->getFieldname(),
			'_type'      => $this->getType(),
			'_required'  => $this->isRequired(),
			'_sort'      => $this->getSort(),
			'_data'      => $this->getData(),
		);

		return $object_vars;
	}

	public function set_array_to_object( $array_vars = array() ) {
		foreach ( $array_vars as $key => $value ) {
			switch ( $key ) {
				case '_formId':
					$this->setFormId( $value );
					break;

				case '_quizId':
					$this->setQuizId( $value );
					break;

				case '_fieldname':
					$this->setFieldname( $value );
					break;

				case '_type':
					$this->setType( $value );
					break;

				case '_required':
					$this->setRequired( $value );
					break;

				case '_sort':
					$this->setSort( $value );
					break;

				case '_data':
					$this->setData( $value );
					break;
			}
		}
	}
}
