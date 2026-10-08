<?php
/**
 * WP Pro Quiz Toplist Model
 *
 * @package LearnDash\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WpProQuiz_Model_Toplist
 */
class WpProQuiz_Model_Toplist extends WpProQuiz_Model_Model {
	protected $_toplistId;
	protected $_quizId;
	protected $_userId;
	protected $_date;
	protected $_name;
	protected $_email;
	protected $_points;
	protected $_result;
	protected $_ip;

	public function setToplistId( $_toplistId ) {
		$this->_toplistId = (int) $_toplistId;
		return $this;
	}

	public function getToplistId() {
		return $this->_toplistId;
	}

	public function setQuizId( $_quizId ) {
		$this->_quizId = (int) $_quizId;
		return $this;
	}

	public function getQuizId() {
		return $this->_quizId;
	}

	public function setUserId( $_userId ) {
		$this->_userId = (int) $_userId;
		return $this;
	}

	public function getUserId() {
		return $this->_userId;
	}

	public function setDate( $_date ) {
		$this->_date = (int) $_date;
		return $this;
	}

	public function getDate() {
		return $this->_date;
	}

	public function setName( $_name ) {
		$this->_name = (string) $_name;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

	public function setEmail( $_email ) {
		$this->_email = (string) $_email;
		return $this;
	}

	public function getEmail() {
		return $this->_email;
	}

	/**
	 * Sets points.
	 *
	 * @param mixed $_points Points.
	 *
	 * @return self
	 */
	public function setPoints( $_points ) {
		$this->_points = learndash_format_course_points( $_points );

		return $this;
	}

	public function getPoints() {
		return $this->_points;
	}

	public function setResult( $_result ) {
		$this->_result = (float) $_result;
		return $this;
	}

	public function getResult() {
		return $this->_result;
	}

	public function setIp( $_ip ) {
		$this->_ip = (string) $_ip;
		return $this;
	}

	public function getIp() {
		return $this->_ip;
	}
}
