<?php
/**
 * BuddyBoss MemberPress Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp memberpress access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Member_Press extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Member_Press constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'mepr-event-create', array( $this, 'bb_access_control_mepr_event_create' ), PHP_INT_MAX, 1 ); // phpcs:ignore
	}

	/**
	 * Do things when a user becomes active on a membership, and when they become inactive on a membership
	 *
	 * @param object $event Event Object.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_mepr_event_create( $event ) {
		do_action( 'bb_access_control_mepr_event_create', $event );
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
			$class_name           = __CLASS__;
			self::$instance       = new $class_name();
			self::$instance->slug = 'memberpress';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available membership.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available membership.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		$results = bb_access_control_get_posts( 'memberpressproduct' );

		return apply_filters( 'memberpress_access_control_get_level_lists', $results );

	}

	/**
	 * Function will check whether user has access or not.
	 *
	 * @param int     $user_id       user id.
	 * @param array   $settings_data DB settings.
	 * @param boolean $threaded      threaded check.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean whether user has access to do a particular given action.
	 */
	public function has_access( $user_id = 0, $settings_data = array(), $threaded = false ) {

		$has_access = parent::has_access( $user_id, $settings_data, $threaded );

		if ( ! is_null( $has_access ) ) {
			return $has_access;
		}

		$has_access = false;

		$user                = new MeprUser( $user_id );
		$get_access_controls = $user->active_product_subscriptions();

		if ( $threaded ) {
			$current_user                     = new MeprUser( bp_loggedin_user_id() );
			$current_user_get_access_controls = $current_user->active_product_subscriptions();
			if ( ! empty( $current_user_get_access_controls ) ) {
				$current_user_get_access_controls = array_values( array_unique( $current_user_get_access_controls ) );
			} else {
				$current_user_get_access_controls = array();
			}
			if ( $current_user_get_access_controls ) {
				foreach ( $current_user_get_access_controls as $current_user_get_access_control ) {
					if ( in_array( $current_user_get_access_control, $settings_data['access-control-options'], true ) ) {
						$arr_key = 'access-control-' . $current_user_get_access_control . '-options';
						if ( empty( $settings_data[ $arr_key ] ) ) {
							$has_access = true;
							break;
						}

						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$has_access = true;
							break;
						}
						if ( ! empty( $get_access_controls ) ) {
							$user_memberships = array_values( array_unique( $get_access_controls ) );
						} else {
							$user_memberships = array();
						}
						if ( $user_memberships ) {
							foreach ( $user_memberships as $user_membership ) {
								if ( in_array( $user_membership, $settings_data[ $arr_key ], true ) ) {
									$has_access = true;
									break;
								}
							}
							if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								foreach ( $user_memberships as $user_membership ) {
									if ( in_array( $user_membership, wp_list_pluck( self::get_level_lists(), 'id' ), true ) ) {
										$has_access = true;
										break;
									}
								}
							}
						}
					}
				}
			}
		} else {
			if ( ! empty( $get_access_controls ) ) {
				$user_memberships = array_values( array_unique( $get_access_controls ) );
			} else {
				$user_memberships = array();
			}
			if ( $user_memberships ) {
				foreach ( $user_memberships as $user_membership ) {
					if ( in_array( $user_membership, $settings_data['access-control-options'], true ) ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
