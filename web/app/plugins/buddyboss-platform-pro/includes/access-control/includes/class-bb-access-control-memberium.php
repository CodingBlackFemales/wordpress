<?php
/**
 * BuddyBoss Memberium Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp memberium access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Memberium extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Memberium constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'memb_add_tag', array( $this, 'bb_access_control_memb_add_tag' ), PHP_INT_MAX, 2 );
		add_action( 'memb_remove_tag', array( $this, 'bb_access_control_memb_remove_tag' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Fires after a user has tag added.
	 *
	 * @param int          $contact_id contact id.
	 * @param array|string $tag        tag.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_memb_add_tag( $contact_id, $tag ) {
		do_action( 'bb_access_control_memb_add_tag', $contact_id, $tag );
	}

	/**
	 * Fires after a user has tag removed.
	 *
	 * @param int $contact_id contact id.
	 * @param int $tag        tag.
	 *
	 * @since 1.1.0
	 */
	public function bb_access_control_memb_remove_tag( $contact_id, $tag ) {
		do_action( 'bb_access_control_memb_remove_tag', $contact_id, $tag );
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
			self::$instance->slug = 'memberium';
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

		// Verify if Memberium plugin is active.
		if ( ! defined( 'MEMBERIUM_SKU' ) ) {
			return array();
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( 'm4ac' == MEMBERIUM_SKU ) {
			$levels = get_option( MEMBERIUM_MEMBERSHIP_SETTINGS, array() );
		} else {
			$settings = get_option( 'memberium', array() );
			$levels   = $settings['memberships'] ? $settings['memberships'] : array();
		}
		$results = array();
		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level_id => $level ) {
				if ( empty( $search ) || strpos( strtolower( $level['name'] ), strtolower( $search ) ) !== false ) {
					$results[] = array(
						'id'      => $level_id,
						'text'    => $level['name'],
						'default' => false,
					);
				}
			}
		}

		return apply_filters( 'memberium_get_level_lists', $results );

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

		$contact_id = memb_getContactIdByUserId( $user_id );

		if ( $threaded ) {
			$current_user_contact_id = memb_getContactIdByUserId( bp_loggedin_user_id() );
			if ( ! empty( $current_user_contact_id ) ) {
				foreach ( $settings_data['access-control-options'] as $level_id ) {
					if ( memb_hasAnyTags( $level_id, $current_user_contact_id ) ) {
						$arr_key = 'access-control-' . $level_id . '-options';
						if ( empty( $settings_data[ $arr_key ] ) ) {
							$has_access = true;
							break;
						}
						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$has_access = true;
							break;
						}
						foreach ( $settings_data[ $arr_key ] as $level ) {
							if ( memb_hasAnyTags( $level, $contact_id ) ) {
								$has_access = true;
								break;
							}
						}
						if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							foreach ( wp_list_pluck( self::get_level_lists(), 'id' ) as $level ) {
								if ( memb_hasAnyTags( $level, $contact_id ) ) {
									$has_access = true;
									break;
								}
							}
						}
					}
				}
			}
		} else {
			if ( ! empty( $contact_id ) ) {
				foreach ( $settings_data['access-control-options'] as $level_id ) {
					if ( memb_hasAnyTags( $level_id, $contact_id ) ) {
						$has_access = true;
						break;
					}
				}
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
