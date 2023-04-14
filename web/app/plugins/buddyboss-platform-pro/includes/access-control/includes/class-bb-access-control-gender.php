<?php
/**
 * BuddyBoss gender Membership Class.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp WordPress role access control class.
 *
 * @since 1.1.0
 */
class BB_Access_Control_Gender extends BB_Access_Control_Abstract {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.1.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Access_Control_Gender constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'xprofile_updated_profile', 'bb_access_control_gender_xprofile_updated_profile', PHP_INT_MAX, 5 );
	}

	/**
	 * Fires after all XProfile fields have been saved for the current profile.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id          ID for the user whose profile is being saved.
	 * @param array $posted_field_ids Array of field IDs that were edited.
	 * @param bool  $errors           Whether or not any errors occurred.
	 * @param array $old_values       Array of original values before update.
	 * @param array $new_values       Array of newly saved values after update.
	 */
	public function bb_access_control_gender_xprofile_updated_profile( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {
		do_action( 'bb_access_control_gender_xprofile_updated_profile', $user_id, $posted_field_ids, $errors, $old_values, $new_values );
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
			self::$instance->slug = 'bb_gender';
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

		$field_object = xprofile_get_field( bp_get_xprofile_gender_type_field_id() );
		$children     = $field_object->get_children();

		if ( isset( $field_object->id ) && ! empty( $field_object->id ) ) {
			$order = bp_xprofile_get_meta( $field_object->id, 'field', 'gender-option-order' );
		} else {
			$order = array();
		}

		for ( $k = 0, $count = count( $children ); $k < $count; ++ $k ) {
			if ( ! empty( $order ) ) {
				$key = $order[ $k ];

				if ( 'male' === $key ) {
					$children[ $k ]->value = 'his_' . $children[ $k ]->name;
				} elseif ( 'female' === $key ) {
					$children[ $k ]->value = 'her_' . $children[ $k ]->name;
				} else {
					$children[ $k ]->value = 'their_' . $children[ $k ]->name;
				}
			} else {
				if ( '1' === $children[ $k ]->option_order ) {
					$children[ $k ]->value = 'his_' . $children[ $k ]->name;
				} elseif ( '2' === $children[ $k ]->option_order ) {
					$children[ $k ]->value = 'her_' . $children[ $k ]->name;
				} else {
					$children[ $k ]->value = 'their_' . $children[ $k ]->name;
				}
			}
		}

		foreach ( $children as $option ) {
			$results[] = array(
				'id'      => $option->value,
				'text'    => $option->name,
				'default' => false,
			);
		}

		return apply_filters( 'bp_gender_get_level_lists', $results );

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

		$gender = xprofile_get_field_data( bp_get_xprofile_gender_type_field_id(), $user_id );

		if ( $threaded ) {
			$current_user_gender = xprofile_get_field_data( bp_get_xprofile_gender_type_field_id(), bp_loggedin_user_id() );
			if ( in_array( $current_user_gender, $settings_data['access-control-options'], true ) ) {
				$arr_key = 'access-control-' . $current_user_gender . '-options';
				if ( empty( $settings_data[ $arr_key ] ) ) {
					$has_access = true;
				} elseif ( in_array( $gender, $settings_data[ $arr_key ], true ) ) {
					$has_access = true;
				}

				if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					if ( in_array( $gender, wp_list_pluck( self::get_level_lists(), 'id' ), true ) ) {
						$has_access = true;
					}
				}

				if ( ! $has_access && in_array( 'all', $settings_data[ $arr_key ] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$has_access = true;
				}
			}
		} else {
			if ( in_array( $gender, $settings_data['access-control-options'], true ) ) {
				$has_access = true;
			}
		}

		return apply_filters( 'bb_access_control_' . $this->slug . '_has_access', $has_access );

	}
}
