<?php
/**
 * BuddyBoss Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Access_Control {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Access_Control constructor.
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
	 * Function will retun all the supported access control plugin lists.
	 *
	 * @since 1.1.0
	 *
	 * @return array return all the access control plugin lists.
	 */
	public static function bb_get_access_control_plugins_lists() {
		$access_controls = array(

			'learndash'            => array(
				'label'      => __( 'LearnDash Group', 'buddyboss-pro' ),
				'is_enabled' => function_exists( 'learndash_is_user_in_group' ) ? true : false,
				'class'      => BB_Access_Control_Learndash_Membership::class,
			),
			'lifter'               => array(
				'label'      => __( 'LifterLMS', 'buddyboss-pro' ),
				'is_enabled' => function_exists( 'llms_is_user_enrolled' ) ? true : false,
				'class'      => BB_Access_Control_Lifter_Membership::class,
			),
			'memberium'            => array(
				'label'      => __( 'Memberium', 'buddyboss-pro' ),
				'is_enabled' => defined( 'MEMBERIUM_SKU' ) ? true : false,
				'class'      => BB_Access_Control_Memberium::class,
			),
			'memberpress'          => array(
				'label'      => __( 'MemberPress', 'buddyboss-pro' ),
				'is_enabled' => defined( 'MEPR_VERSION' ) ? true : false,
				'class'      => BB_Access_Control_Member_Press::class,
			),
			'pm-pro-membership'    => array(
				'label'      => __( 'Paid Memberships Pro', 'buddyboss-pro' ),
				'is_enabled' => function_exists( 'pmpro_changeMembershipLevel' ) ? true : false,
				'class'      => BB_Access_Control_Paid_Memberships_Pro_Memberships::class,
			),
			'restrict-content-pro' => array(
				'label'      => __( 'Restrict Content Pro', 'buddyboss-pro' ),
				'is_enabled' => function_exists( 'rcp_get_subscription_levels' ) ? true : false,
				'class'      => BB_Access_Control_Restrict_Content_Pro_Memberships::class,
			),
			's2member'             => array(
				'label'      => __( 'S2Member', 'buddyboss-pro' ),
				'is_enabled' => defined( 'WS_PLUGIN__S2MEMBER_VERSION' ) ? true : false,
				'class'      => BB_Access_Control_S2_Member::class,
			),
			'wishlist-member'      => array(
				'label'      => __( 'Wishlist Member', 'buddyboss-pro' ),
				'is_enabled' => class_exists( 'WishListMember' ) ? true : false,
				'class'      => BB_Access_Control_Wishlist_Member::class,
			),
			'woo-membership'       => array(
				'label'      => __( 'WooCommerce Memberships', 'buddyboss-pro' ),
				'is_enabled' => function_exists( 'wc_memberships' ) ? true : false,
				'class'      => BB_Access_Control_Woo_Membership::class,
			),

		);

		return apply_filters( 'bb_get_access_control_plugins_lists', $access_controls );

	}

	/**
	 * Return anyone of membership is activated or not.
	 *
	 * @since 1.1.0
	 *
	 * @return bool $is_available return if enabled.
	 */
	public static function bb_is_access_control_available() {

		$is_available = false;

		foreach ( self::bb_get_access_control_plugins_lists() as $access_control ) {
			if ( $access_control['is_enabled'] ) {
				$is_available = true;
				break;
			}
		}

		return apply_filters( 'bb_is_access_control_available', $is_available );
	}
}
