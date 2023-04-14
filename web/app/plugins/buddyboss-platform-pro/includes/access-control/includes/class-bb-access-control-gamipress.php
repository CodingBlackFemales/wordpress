<?php
/**
 * BuddyBoss Gamipress Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp gamipress access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Gamipress {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since 1.1.0
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Gamipress constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {

	}

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.1.0
	 *
	 * @return Controller|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Function will return all available rank and achievements types.
	 *
	 * @since 1.1.0
	 *
	 * @return array all available rank and achievements types.
	 */
	public static function bb_get_access_control_gamipress_lists() {
		$access_controls = array(
			'achievement' => array(
				'label'      => __( 'Achievement', 'buddyboss-pro' ),
				'is_enabled' => class_exists( 'GamiPress' ) ? true : false,
				'class'      => BB_Access_Control_Gamipress_Achievement::class,
			),
			'rank'        => array(
				'label'      => __( 'Rank', 'buddyboss-pro' ),
				'is_enabled' => class_exists( 'GamiPress' ) ? true : false,
				'class'      => BB_Access_Control_Gamipress_Rank::class,
			),
		);

		return apply_filters( 'bb_get_access_control_gamipress_lists', $access_controls );

	}

}
