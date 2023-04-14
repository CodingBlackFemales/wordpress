<?php
/**
 * BuddyBoss Restrict Content Pro Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp restrict content pro access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Restrict_Content_Pro_Memberships extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Restrict_Content_Pro_Memberships constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'rcp_membership_post_renew', 'bb_access_control_rcp_membership_post_renew', PHP_INT_MAX, 3 );
	}

	/**
	 * Fires after the user's membership renew.
	 *
	 * @param string         $expiration    New expiration date to be set.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 1.1.5
	 */
	public function bb_access_control_rcp_membership_post_renew( $expiration, $membership_id, $membership ) {
		do_action( 'bb_access_control_rcp_membership_post_renew', $expiration, $membership_id, $membership );
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
			self::$instance->slug = 'restrict_content_pro';
		}

		return self::$instance;
	}

	/**
	 * Function will return all the available access control.
	 *
	 * @since 1.1.0
	 *
	 * @return array list of available access control.
	 */
	public function get_level_lists() {

		if ( ! bbp_pro_is_license_valid() ) {
			return array();
		}

		if ( ! function_exists( 'rcp_get_subscription_levels' ) ) {
			return array();
		}

		$levels  = rcp_get_subscription_levels();
		$results = array();
		foreach ( $levels as $level ) {
			if ( empty( $search ) || strpos( strtolower( $level->name ), strtolower( $search ) ) !== false ) {
				$results[] = array(
					'id'      => $level->id,
					'text'    => $level->name,
					'default' => false,
				);
			}
		}

		return apply_filters( 'restrict_content_pro_get_level_lists', $results );

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

		$customer = rcp_get_customer_by_user_id( $user_id );
		if ( is_object( $customer ) ) {
			$access_controls = $customer->get_memberships();
		}

		if ( $threaded ) {
			$current_customer = rcp_get_customer_by_user_id( bp_loggedin_user_id() );
			if ( is_object( $current_customer ) ) {
				$current_access_controls = $current_customer->get_memberships();
				if ( ! empty( $current_access_controls ) ) {
					foreach ( $settings_data['access-control-options'] as $level_id ) {
						foreach ( $current_access_controls as $current_access_control ) {
							// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
							if ( $current_access_control->get_object_id() == $level_id && $current_access_control->is_active() ) {
								$arr_key = 'access-control-' . $level_id . '-options';
								if ( empty( $settings_data[ $arr_key ] ) ) {
									$has_access = true;
									break;
								}
								if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
									$has_access = true;
									break;
								}
								if ( is_object( $customer ) ) {
									foreach ( $settings_data[ $arr_key ] as $level_id ) {
										foreach ( $access_controls as $access_control ) {
											// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
											if ( $access_control->get_object_id() == $level_id && $access_control->is_active() ) {
												$has_access = true;
												break;
											}
										}
									}
									if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
										foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level_id ) {
											foreach ( $access_controls as $access_control ) {
												// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
												if ( $access_control->get_object_id() == $level_id && $access_control->is_active() ) {
													$has_access = true;
													break;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		} else {
			if ( is_object( $customer ) ) {
				foreach ( $settings_data['access-control-options'] as $level_id ) {
					foreach ( $access_controls as $access_control ) {
						// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						if ( $access_control->get_object_id() == $level_id && $access_control->is_active() ) {
							$has_access = true;
							break;
						}
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
