<?php
/**
 * LearnDash `[usermeta]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[usermeta]` shortcode output.
 *
 * This shortcode takes a parameter named field, which is the name of the user meta data field to be displayed.
 * Example: [usermeta field="display_name"] would display the user's Display Name.
 *
 * @since 2.1.0
 *
 * @param array  $attr {
 *     An array of shortcode attributes.
 *
 *    @type string  $field   The usermeta field to show
 *    @type int     $user_id User ID. Default current user ID.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'usermeta'.
 *
 * @return string            output of shortcode
 */
function learndash_usermeta_shortcode( $attr = array(), $content = '', $shortcode_slug = 'usermeta' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	// We clear out content because there is no reason to retain it.
	$content = '';

	$attr = shortcode_atts(
		array(
			'field'   => 'user_login',
			'user_id' => get_current_user_id(),
		),
		$attr
	);

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$attr = apply_filters( 'learndash_shortcode_atts', $attr, $shortcode_slug );

	if ( ( ! empty( $attr['user_id'] ) ) && ( ! empty( $attr['field'] ) ) ) {

		$userdata = get_userdata( intval( $attr['user_id'] ) );
		if ( ( $userdata ) && ( is_a( $userdata, 'WP_User' ) ) ) {

			if ( ( learndash_is_admin_user() ) || ( get_current_user_id() == $attr['user_id'] ) ) {
				$usermeta_available_fields = array( $attr['field'] => $attr['field'] );
			} else {
				$usermeta_available_fields = learndash_get_usermeta_shortcode_available_fields( $attr );
			}

			if ( ! is_array( $usermeta_available_fields ) ) {
				$usermeta_available_fields = array( $usermeta_available_fields );
			}

			$value = '';
			if ( array_key_exists( $attr['field'], $usermeta_available_fields ) === true ) {

				switch ( $attr['field'] ) {
					case 'first_last_name':
						$value = $userdata->user_firstname . ' ' . $userdata->user_lastname;
						break;

					default:
						if ( array_key_exists( $attr['field'], $usermeta_available_fields ) === true ) {
							$value = $userdata->{$attr['field']};
						}
						break;
				}
			}

			/**
			 * Filters usermeta shortcode field attribute value.
			 *
			 * @since 2.4.0
			 *
			 * @param string $value                    Usermeta field attribute value.
			 * @param array  $attributes               An array of shortcode attributes.
			 * @param array  $usermeta_available_fields An array of available user meta fields.
			 */
			$content = apply_filters( 'learndash_usermeta_shortcode_field_value_display', $value, $attr, $usermeta_available_fields );
		}
	}

	return $content;
}
add_shortcode( 'usermeta', 'learndash_usermeta_shortcode', 10, 3 );
