<?php

namespace Wpai\Scheduling;

use Wpai\Scheduling\Exception\SchedulingHttpException;

/**
 * Class SchedulingApi
 * @package Wpai\Scheduling
 */
class SchedulingApi {

	const TIMEOUT = 30;

	/**
	 * @var
	 */
	private $api_url;

	/**
	 * SchedulingApi constructor.
	 * @param $api_url
	 */
	public function __construct( $api_url ) {
		$this->api_url = $api_url;
	}

	/**
	 * @return bool
	 */
	public function checkConnection() {

		// Short-circuit check if connection transient is set to true.
		if ( get_transient( 'wpai_wpae_scheduling_connection_confirmed' ) ) {
			return true;
		}

		if ( is_multisite() ) {
			$url = get_site_url( get_current_blog_id(), '/wp-load.php' );
		} else {
			$url = get_site_url( null, '/wp-load.php' );
		}

		$ping_back_url = $this->get_api_url( 'connection' ) . '?url=' . rawurlencode( $url );

		$response = wp_remote_request(
			$ping_back_url,
			array(
				'method'  => 'GET',
				'timeout' => self::TIMEOUT,
			)
		);

		if ( $response instanceof \WP_Error ) {
			return false;
		}

		if ( 200 === (int) $response['response']['code'] ) {
			// Set transient so we don't keep checking the connection unnecessarily.
			// Expire transient after ten minutes.
			set_transient( 'wpai_wpae_scheduling_connection_confirmed', true, 600 );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $elementId
	 * @param $elementType
	 * @return array|bool|mixed|null|object
	 */
	public function getSchedules( $elementId, $elementType ) {
		$response = wp_remote_request(
			$this->get_api_url(
				'schedules?forElement=' . $elementId .
				'&type=' . $elementType .
				'&endpoint=' . rawurlencode( get_site_url() )
			),
			array(
				'method'  => 'GET',
				'headers' => $this->getHeaders(),
				'timeout' => self::TIMEOUT,
			)
		);

		if ( $response instanceof \WP_Error ) {
			return false;
		}

		return json_decode( $response['body'] );
	}

	/**
	 * @param $scheduleId
	 */
	public function getSchedule( $scheduleId ) {
		wp_remote_request(
			$this->get_api_url( 'schedules/' . $scheduleId ),
			array(
				'method'  => 'GET',
				'headers' => $this->getHeaders(),
				'timeout' => self::TIMEOUT,
			)
		);
	}

	/**
	 * @param $scheduleData
	 * @return array|\WP_Error
	 * @throws \Wpai\Scheduling\Exception\SchedulingHttpException
	 */
	public function createSchedule( $scheduleData ) {

		$response = wp_remote_request(
			$this->get_api_url( 'schedules' ),
			array(
				'method'  => 'PUT',
				'headers' => $this->getHeaders(),
				'body'    => json_encode( $scheduleData ),
				'timeout' => self::TIMEOUT,
			)
		);

		if ( $response instanceof \WP_Error ) {
			throw new SchedulingHttpException( 'There was a problem saving the schedule' );
		}

		return $response;
	}

	/**
	 * @param $scheduleId
	 */
	public function deleteSchedule( $scheduleId ) {
		wp_remote_request(
			$this->get_api_url( 'schedules/' . $scheduleId ),
			array(
				'method'  => 'DELETE',
				'headers' => $this->getHeaders(),
				'timeout' => self::TIMEOUT,
			)
		);
	}

	/**
	 * @param $scheduleId
	 * @param $scheduleTime
	 * @return array|\WP_Error
	 * @throws \Wpai\Scheduling\Exception\SchedulingHttpException
	 */
	public function updateSchedule( $scheduleId, $scheduleTime ) {

		$response = wp_remote_request(
			$this->get_api_url( 'schedules/' . $scheduleId ),
			array(
				'method'  => 'POST',
				'headers' => $this->getHeaders(),
				'body'    => json_encode( $scheduleTime ),
				'timeout' => self::TIMEOUT,
			)
		);

		if ( $response instanceof \WP_Error ) {
			throw new SchedulingHttpException( 'There was a problem saving the schedule' );
		}

		return $response;
	}

	public function disableSchedule( $remoteScheduleId ) {
		wp_remote_request(
			$this->get_api_url( 'schedules/' . $remoteScheduleId . '/disable' ),
			array(
				'method'  => 'DELETE',
				'headers' => $this->getHeaders(),
			)
		);
	}

	public function enableSchedule( $scheduleId ) {
		wp_remote_request(
			$this->get_api_url( 'schedules/' . $scheduleId . '/enable' ),
			array(
				'method'  => 'POST',
				'headers' => $this->getHeaders(),
			)
		);
	}

	public function updateScheduleKey( $remoteScheduleId, $newKey ) {
		wp_remote_request(
			$this->get_api_url( 'schedules/' . $remoteScheduleId . '/key' ),
			array(
				'method'  => 'POST',
				'headers' => $this->getHeaders(),
				'body'    => json_encode( array( 'key' => $newKey ) ),

			)
		);
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getHeaders() {

		$options = \PMXI_Plugin::getInstance()->getOption();

		if ( ! empty( $options['scheduling_license'] ) ) {
			return array(
				'Authorization' => 'License ' . \PMXI_Plugin::decode( $options['scheduling_license'] ),
				'key' => \PMXI_Plugin::getInstance()->getOption('cron_job_key'),
			);
		} else {
			//TODO: Throw custom exception
			throw new \Exception( 'No license present' );
		}
	}

	/**
	 * @return string
	 */
	private function get_api_url( $resource_str ) {
		return $this->api_url . '/' . $resource_str;
	}
}
