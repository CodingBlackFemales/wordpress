<?php
/**
 * REST API for BuddyBoss Social Login.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/BBSSO
 */

namespace BBSSO;

use Exception;
use BB_SSO;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use BB_SSO_Provider_OAuth;

use function add_action;
use function register_rest_route;

/**
 * Class BB_SSO_REST.
 *
 * Handles REST API requests for BuddyBoss Social Login.
 *
 * @since 2.6.30
 */
class BB_SSO_REST {

	/**
	 * BB_SSO_REST constructor.
	 * Registers the REST API initialization action hook.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		add_action(
			'rest_api_init',
			array(
				$this,
				'rest_api_init',
			)
		);

		add_filter( 'bb_exclude_endpoints_from_restriction', array( $this, 'bb_exclude_endpoints_from_restriction' ), 99, 2 );

		add_filter( 'bp_rest_platform_settings', array( $this, 'bb_add_provider_settings_to_rest' ) );

		add_action( 'bp_rest_signup_create_item', array( $this, 'bb_rest_signup_create_item' ), 10, 3 );
	}

	/**
	 * Enable to allow to access the Rest api for the private sites.
	 *
	 * @since 2.6.30
	 *
	 * @param array  $default_exclude_endpoint Array of the excluded endpoints.
	 * @param string $current_endpoint         Current endpoint.
	 *
	 * @return mixed
	 */
	public function bb_exclude_endpoints_from_restriction( $default_exclude_endpoint, $current_endpoint ) {
		$pattern = '/bb-social-login/v1/(?P<provider>\w[\w\s\-]*)/get_user';

		// Check if the current endpoint matches the pattern.
		if ( preg_match( "#^{$pattern}$#", $current_endpoint ) ) {
			// Add the current endpoint to the array if it matches.
			$default_exclude_endpoint[] = $current_endpoint;
		}

		return $default_exclude_endpoint;
	}

	/**
	 * Registers a REST route for retrieving a user based on the social provider.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function rest_api_init() {
		register_rest_route(
			'bb-social-login/v1',
			'/(?P<provider>\w[\w\s\-]*)/get_user',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array(
						$this,
						'get_user',
					),
					'permission_callback' => '__return_true',
					'args'                => array(
						'provider'     => array(
							'type'        => 'string',
							'enum'        => array_keys( BB_SSO::$enabled_providers ),
							'required'    => true,
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_key',
							),
						),
						'access_token' => array(
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Validates the social provider to ensure it is enabled and supported.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider identifier.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if not supported.
	 */
	public function validate_provider( $provider_id ) {
		if ( BB_SSO::is_provider_enabled( $provider_id ) ) {
			if ( BB_SSO::$enabled_providers[ $provider_id ] instanceof BB_SSO_Provider_OAuth ) {
				return true;
			} else {
				/*
				 * OpenID providers don't have a secure Access Token, but just a simple ID that is usually easy to guess.
				 * For this reason we shouldn't return the WordPress user ID over the REST API of providers based on OpenID authentication.
				 */
				return new WP_Error( 'error', __( 'This provider doesn\'t support REST API calls!', 'buddyboss-pro' ) );
			}
		}

		return false;
	}

	/**
	 * Retrieves the user associated with the access token from the social provider.
	 *
	 * @since 2.6.30
	 *
	 * @param WP_REST_Request $request The REST request object containing the provider and access token.
	 *
	 * @return WP_Error|WP_REST_Response Response object containing user details or error message.
	 */
	public function get_user( $request ) {
		$provider     = BB_SSO::$enabled_providers[ $request['provider'] ];
		$response     = new WP_REST_Response();
		$device_token = $request->get_param( 'device_token' );
		$access_token = $request->get_param( 'access_token' );
		$email        = '';
		$user         = null;

		// Required for app to check about the IAP flow and tick the screens.
		$login_type = 'login';

		if ( 'apple' === $request['provider'] && ! empty( $access_token ) ) {
			$access_token_decoded = json_decode( $access_token, true );
			if ( ! empty( $access_token_decoded['access_token'] ) ) {
				$access_token_decoded['id_token'] = $access_token_decoded['access_token'];
			}
			$access_token = wp_json_encode( $access_token_decoded );
		}

		if (
			! empty( $request->get_header( 'appplatform' ) ) &&
			'ios' === $request->get_header( 'appplatform' ) &&
			'facebook' === $request['provider']
		) {
			$access_token_decoded = json_decode( $access_token, true );

			try {
				// Step 1: Verify Facebook JWT.
				$decoded_jwt = $this->verify_facebook_jwt( $access_token_decoded['access_token'] );
				if ( ! $decoded_jwt ) {
					throw new Exception( __( 'Invalid Facebook token', 'buddyboss-pro' ) );
				}

				$decoded_jwt['id'] = $decoded_jwt['sub'];

				$provider->set_auth_user_data( $decoded_jwt );
				$email = $provider->get_auth_user_data_by_auth_options( 'email', $access_token );
				$user  = $provider->get_user_id_by_provider_identifier( $provider->get_auth_user_data_by_auth_options( 'sub', $access_token ) );

			} catch ( Exception $e ) {
				$response->set_data(
					array(
						'response' => 'error',
						'message'  => $e->getMessage(),
					)
				);

				$response->set_status( 400 );

				return $response;
			}
		} else {
			$provider->find_social_id_by_access_token( $access_token );
		}

		try {
			if ( empty( $user ) && empty( $email ) ) {
				$user = $provider->find_user_by_access_token( $access_token );
			}
			if ( null !== $user && ! get_user_by( 'id', $user ) ) {
				$provider->remove_connection_by_user_id( $user );
				$user = null;
			}

			$social_user_id = $provider->get_auth_user_data_by_auth_options( 'id', $access_token );

			// Check is null.
			if ( is_null( $user ) ) {
				$wordpress_user_id = false;
				if ( empty( $email ) ) {
					$email = $provider->get_auth_user_data_by_auth_options( 'email', $access_token );
				}
				if ( empty( $email ) ) {
					$response->set_data(
						array(
							'response' => 'error',
							'message'  => __( 'Email is required.', 'buddyboss-pro' ),
						)
					);
					$response->set_status( 400 );
				} else {
					$wordpress_user_id = email_exists( $email );
				}
				if ( false !== $wordpress_user_id ) {
					$first_name = '';
					$last_name  = '';
					if ( 'apple' === $provider->get_id() && ! empty( $access_token_decoded ) ) {
						$apple_user_data = $this->bb_get_user_names_from_social_table( $provider->get_id(), $social_user_id, $access_token_decoded );
						$first_name      = $apple_user_data['first_name'];
						$last_name       = $apple_user_data['last_name'];
					}
					if ( $provider->link_user_to_provider_identifier( $wordpress_user_id, $social_user_id, false, $first_name, $last_name ) ) {
						$provider->trigger_sync( $wordpress_user_id, $access_token, 'login', true );

						$provider->log_login_date( $wordpress_user_id );

						$data = $this->generate_token( $wordpress_user_id, $device_token );
						// Return the user id.
						$response->set_data(
							array(
								'response'   => 'success',
								'user_id'    => $wordpress_user_id,
								'token'      => $data,
								'login_type' => $login_type,
							)
						);
						$response->set_status( 200 );
					} else {
						// Throw error: User already have another social account from this provider linked to the WordPress account that has the email match. They should use that account.
						$response->set_data(
							array(
								'response' => 'error',
								'message'  => __( 'User already have another social account from this provider linked to the WordPress account that has the email match. They should use that account.', 'buddyboss-pro' ),
							)
						);
						$response->set_status( 400 );
					}
				} else {
					// Check is registration enabled.
					if ( ! BB_SSO::bb_sso_is_register_allowed() ) {
						// Throw error: Registration is disabled.
						$default_disabled_message = __( 'Registration to this site has been disabled, please contact site owners for further assistance.', 'buddyboss-pro' );
						$response->set_data(
							array(
								'response' => 'error',
								'message'  => $default_disabled_message,
							)
						);
						$response->set_status( 400 );

						return $response;
					}

					$get_provider            = BB_SSO::get_provider_by_provider_id( $request['provider'] );
					$access_token_data       = array( 'access_token_data' => $access_token );
					$bb_sso_user             = new \BB_SSO_User( $get_provider, $access_token_data );
					$allow_signup            = $bb_sso_user->validate_signup( $email );
					$allow_signup['message'] = '';
					if ( ! $allow_signup['allow_signup'] ) {
						// If Apple, remove field labels based on available data from the social table and access token data.
						if ( 'apple' === $request['provider'] && ! empty( $access_token_decoded ) ) {
							$apple_user_data = $this->bb_get_user_names_from_social_table( $get_provider->get_id(), $social_user_id, $access_token_decoded );

							// Remove field labels based on available data.
							if ( ! empty( $apple_user_data['first_name'] ) ) {
								unset( $allow_signup['require_fields_label'][1] ); // Remove First Name.
								unset( $allow_signup['require_fields_label'][3] ); // Remove Nickname if we have first name.
							}
							if ( ! empty( $apple_user_data['last_name'] ) ) {
								unset( $allow_signup['require_fields_label'][2] ); // Remove Last Name.
								unset( $allow_signup['require_fields_label'][3] ); // Remove Nickname if we have last name.
							}
						}
						// Get the labels from the $fields_labels array.
						// For signup_email and signup_email_confirm, we need to get the label from the account_details group.
						$all_fields_labels = array_map(
							function ( $item ) {
								return is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $item;
							},
							$allow_signup['require_fields_label']
						);

						// Throw error: Registration with required fields.
						$signup_fields_msg = apply_filters(
							'bb_sso_register_signup_fields_not_found',
							sprintf(
							/* translators: %1$s: required fields list, %2$s: required fields list */
								'<div class="bb-sso-reg-error"><p>%1$s </p>%2$s</div>',
								esc_html__( 'Please fill in the required fields to complete your registration:', 'buddyboss-pro' ),
								'<ul><li>' . implode(
									'</li><li>',
									array_map(
										function ( $label ) {
											return '<strong>' . esc_html( $label ) . '</strong>';
										},
										$all_fields_labels
									)
								) . '</li></ul>'
							)
						);

						// Append invalid nickname message to bb-sso-reg-error div if available.
						if ( ! empty( $allow_signup['signup_fields_invalid_nickname_message'] )) {
							$signup_fields_msg = bb_sso_append_error_to_signup_div( $signup_fields_msg, $allow_signup['signup_fields_invalid_nickname_message'] );
						}

						$redirect_url = BB_SSO::enable_notice_for_url( $allow_signup['redirect_url'] );

						$data = array(
							'response' => 'error',
							'message'  => ! empty( $allow_signup['message'] ) ? $allow_signup['message'] : $signup_fields_msg,
						);

						// Collect the required fields.
						$required_fields = array();
						if ( ! empty( $allow_signup['require_fields_label'] ) ) {
							$fields_endpoint = new \BP_REST_XProfile_Fields_Endpoint();
							foreach ( $allow_signup['require_fields_label'] as $field_id => $field_label ) {
								$field = xprofile_get_field( $field_id );
								if ( ! empty( $field ) && ! is_array( $field_label ) ) {
									$field             = $fields_endpoint->assemble_response_data( $field, $request );
									$required_fields[] = array(
										'id'          => 'field_' . $field['id'],
										'label'       => ( ! empty( $field['alternate_name'] ) ? $field['alternate_name'] : $field['name'] ),
										'description' => $field['description']['rendered'],
										'type'        => $field['type'],
										'required'    => $field['is_required'],
										'options'     => $field['options'],
										'member_type' => bp_xprofile_get_meta( $field['id'], 'field', 'member_type', false ),
									);
								} elseif ( is_array( $field_label ) ) {
									$field_label['id'] = $field_id;

									$required_fields[] = array(
										'id'          => $field_id,
										'label'       => $field_label['label'],
										'description' => '',
										'type'        => $field_label['type'],
										'required'    => $field_label['required'],
										'options'     => array(),
										'member_type' => '',
									);
								}
							}

							// Append invalid nickname field.
							if ( ! empty( $allow_signup['signup_fields_invalid_nickname_message'] ) ) {
								$nickname_field_id = bp_xprofile_nickname_field_id();
								$nickname_field    = xprofile_get_field( $nickname_field_id );
								if ( ! empty( $nickname_field ) ) {

									// Get the value for the nickname field.
									$nickname_url_components = wp_parse_url( $redirect_url );
									parse_str( $nickname_url_components['query'], $nickname_query_params );
									$nickname_value = ! empty( $nickname_query_params['field_' . $nickname_field_id] ) ? $nickname_query_params['field_' . $nickname_field_id] : '';

									$field             = $fields_endpoint->assemble_response_data( $nickname_field, $request );
									$required_fields[] = array(
										'id'          => 'field_' . $nickname_field_id,
										'label'       => ( ! empty( $field['alternate_name'] ) ? $field['alternate_name'] : $field['name'] ),
										'description' => $field['description']['rendered'],
										'type'        => $field['type'],
										'required'    => $field['is_required'],
										'options'     => array(),
										'member_type' => '',
										'value'       => $nickname_value,
									);
								}
							}
						}

						$legal_agreement_field = function_exists( 'bb_register_legal_agreement' ) ? bb_register_legal_agreement() : false;
						if ( $legal_agreement_field ) {
							$page_ids = bp_core_get_directory_page_ids();
							$terms    = ! empty( $page_ids['terms'] ) ? $page_ids['terms'] : false;
							$privacy  = ! empty( $page_ids['privacy'] ) ? $page_ids['privacy'] : (int) get_option( 'wp_page_for_privacy_policy' );

							$headline = '';
							if ( ! empty( $terms ) && ! empty( $privacy ) ) {
								$headline = sprintf(
								/* translators: 1. Term agreement page. 2. Privacy page. */
									__( 'I agree to the %1$s and %2$s.', 'buddyboss-pro' ),
									'<a href="' . esc_url( get_permalink( $terms ) ) . '">' . get_the_title( $terms ) . '</a>',
									'<a href="' . esc_url( get_permalink( $privacy ) ) . '">' . get_the_title( $privacy ) . '</a>'
								);
							} elseif ( ! empty( $terms ) && empty( $privacy ) ) {
								$headline = sprintf(
								/* translators: Term agreement page. */
									__( 'I agree to the %s.', 'buddyboss-pro' ),
									'<a href="' . esc_url( get_permalink( $terms ) ) . '">' . get_the_title( $terms ) . '</a>'
								);
							} elseif ( empty( $terms ) && ! empty( $privacy ) ) {
								$headline = sprintf(
								/* translators: Privacy page. */
									__( 'I agree to the %s.', 'buddyboss-pro' ),
									'<a href="' . esc_url( get_permalink( $privacy ) ) . '">' . get_the_title( $privacy ) . '</a>'
								);
							}

							$required_fields[] = array(
								'id'          => 'legal_agreement',
								'label'       => $headline,
								'description' => '',
								'type'        => 'checkbox',
								'required'    => true,
								'options'     => array(),
								'member_type' => '',
							);
						}

						if ( isset( $allow_signup['fields']['first_name'] ) && $allow_signup['fields']['first_name'] ) {
							$prefill_fields['field_1'] = $provider->get_auth_user_data_by_auth_options( 'first_name', $access_token );
						}

						// Prefill last name if it's not a required field.
						if ( isset( $allow_signup['require_fields_label'] ) && ! array_key_exists( 2, $allow_signup['require_fields_label'] ) ) {
							$lname = $provider->get_auth_user_data_by_auth_options( 'last_name', $access_token );
							if ( ! empty( $lname ) ) {
								$prefill_fields['field_2'] = $lname;
							}
						}

						// If Apple, remove required fields based on available data from the social table and access token data.
						if ( 'apple' === $get_provider->get_id() && ! empty( $access_token_decoded ) ) {
							$apple_user_data = $this->bb_get_user_names_from_social_table( $get_provider->get_id(), $social_user_id, $access_token_decoded );
							// Create array of fields to remove based on available data.
							$fields_to_remove = array();
							if ( ! empty( $apple_user_data['first_name'] ) ) {
								$prefill_fields['field_1'] = $apple_user_data['first_name'];
								$fields_to_remove[]        = 'field_1';
								$fields_to_remove[]        = 'field_3';
							}
							if ( ! empty( $apple_user_data['last_name'] ) ) {
								$prefill_fields['field_2'] = $apple_user_data['last_name'];
								$fields_to_remove[]        = 'field_2';
								$fields_to_remove[]        = 'field_3';
							}
							if ( ! empty( $prefill_fields['field_1'] ) || ! empty( $prefill_fields['field_2'] ) ) {
								$field_3 = $prefill_fields['field_1'] . $prefill_fields['field_2'];
								if ( ! bb_enable_additional_sso_name() ) {
									$bb_autogenerate_user_prefix = apply_filters( 'bb_sso_autogenerate_user_prefix', '' );
									$field_3                     = function_exists( 'bb_generate_user_random_profile_slugs' ) ? bb_generate_user_random_profile_slugs( 1, $bb_autogenerate_user_prefix ) : '';
									if ( ! empty( $field_3 ) ) {
										$field_3 = current( $field_3 );
									} else {
										$field_3 = sanitize_user( $bb_autogenerate_user_prefix . md5( uniqid( wp_rand() ) ), true );
									}
								}
								$prefill_fields['field_3'] = sanitize_user( $field_3 );
							}

							// Remove fields in one go if we have any to remove.
							if ( ! empty( $fields_to_remove ) ) {
								$required_fields = array_values(
									array_filter(
										$required_fields,
										function ( $field ) use ( $fields_to_remove ) {
											return ! in_array( $field['id'], $fields_to_remove );
										}
									)
								);
							}
						}

						$data['required_field'] = $required_fields;
						if ( ! empty( $data['required_field'] ) ) {
							$url_components = parse_url( $redirect_url );

							// Parse the query string into an associative array
							parse_str( $url_components['query'], $query_params );

							// Unset unnecessary params.
							unset( $query_params['confirm_email_on'] );

							foreach ( $query_params as $key => $value ) {
								if ( ! empty( $value ) ) {
									// Map the query parameters to your desired format.
									$prefill_fields[ $key ] = $value;
									if ( 'signup_password' === $key ) {
										$prefill_fields[ $key ] = wp_generate_password( 12, false );
									}
									if ( 'signup_password_confirm' === $key ) {
										$prefill_fields[ $key ] = $prefill_fields['signup_password'];
									}

									if ( ! empty( $allow_signup['signup_fields_invalid_nickname_message'] ) && 'field_' . $nickname_field_id === $key ) {
										unset( $prefill_fields[ $key ] );
									}
								}
							}
						}
						$prefill_fields['picture']      = $provider->get_auth_user_data_by_auth_options( 'picture', $access_token ) ?? '';
						$prefill_fields['device_token'] = $device_token;
						$prefill_fields['login_type']   = 'register';

						$data['prefill_fields'] = $prefill_fields;

						// Add the user to the database in SSO table with 0 id.
						$prefill_fields['field_1'] = ! empty( $prefill_fields['field_1'] ) ? $prefill_fields['field_1'] : '';
						$prefill_fields['field_2'] = ! empty( $prefill_fields['field_2'] ) ? $prefill_fields['field_2'] : '';
						$provider->link_user_to_provider_identifier( 0, $social_user_id, false, $prefill_fields['field_1'], $prefill_fields['field_2'] );

						$response->set_data( $data );
						$response->set_status( 400 );

						return $response;
					}

					$first_name = '';
					$last_name  = '';
					$name       = '';
					if ( 'twitter' === $get_provider->get_id() || bb_enable_additional_sso_name() ) {
						$first_name = $provider->get_auth_user_data_by_auth_options( 'first_name', $access_token );
						$last_name  = $provider->get_auth_user_data_by_auth_options( 'last_name', $access_token );
						$name       = $provider->get_auth_user_data_by_auth_options( 'name', $access_token );

						if ( 'apple' === $get_provider->get_id() && ! empty( $access_token_decoded ) ) {
							$apple_user_data = $this->bb_get_user_names_from_social_table( $get_provider->get_id(), $social_user_id, $access_token_decoded );
							$first_name      = $apple_user_data['first_name'];
							$last_name       = $apple_user_data['last_name'];
							$name            = sanitize_user( strtolower( $first_name ) . strtolower( $last_name ), true );
						}
					}
					if ( empty( $name ) || ! validate_username( $name ) ) {
						$bb_autogenerate_user_prefix = apply_filters( 'bb_sso_autogenerate_user_prefix', '' );
						$name                        = function_exists( 'bb_generate_user_random_profile_slugs' ) ? bb_generate_user_random_profile_slugs( 1, $bb_autogenerate_user_prefix ) : '';
						if ( ! empty( $name ) ) {
							$name = current( $name );
						} else {
							$name = sanitize_user( $bb_autogenerate_user_prefix . md5( uniqid( wp_rand() ) ), true );
						}
					}
					$user_data = array(
						'user_login'   => $name,
						'user_email'   => $email,
						'user_pass'    => wp_generate_password(),
						'display_name' => $name,
					);
					if ( ! empty( $first_name ) ) {
						$user_data['first_name'] = $first_name;
					}
					if ( ! empty( $last_name ) ) {
						$user_data['last_name'] = $last_name;
					}
					$wordpress_user_id = wp_insert_user( $user_data );
					if ( ! is_wp_error( $wordpress_user_id ) && $wordpress_user_id ) {
						if ( $provider->link_user_to_provider_identifier( $wordpress_user_id, $social_user_id, true, $first_name, $last_name ) ) {
							$provider->trigger_sync( $wordpress_user_id, $access_token_data, 'register', true );

							// BuddyPress - add register activity to accounts registered with social login.
							if ( class_exists( 'BuddyPress', false ) ) {
								if ( bp_is_active( 'activity' ) ) {
									if ( ! function_exists( 'bp_core_new_user_activity' ) ) {
										require_once buddypress()->plugin_dir . '/bp-members/bp-members-activity.php';
									}
									bp_core_new_user_activity( $wordpress_user_id );
								}

								// Set xprofile firstname, lastname and nickname.
								xprofile_set_field_data( bp_xprofile_firstname_field_id(), $wordpress_user_id, $first_name );
								xprofile_set_field_data( bp_xprofile_lastname_field_id(), $wordpress_user_id, $last_name );
								xprofile_set_field_data( bp_xprofile_nickname_field_id(), $wordpress_user_id, $name );
							}

							$data = $this->generate_token( $wordpress_user_id, $device_token );

							$login_type = 'register';

							// Return success with user id.
							$response->set_data(
								array(
									'response'   => 'success',
									'user_id'    => $wordpress_user_id,
									'token'      => $data,
									'login_type' => $login_type,
								)
							);
							$response->set_status( 200 );
						}
					} else {
						// Throw error: There was an error with the registration.
						$response->set_data(
							array(
								'response' => 'error',
								'message'  => __( 'There was an error with the registration.', 'buddyboss-pro' ),
							)
						);
						$response->set_status( 400 );
					}
				}
			} else {
				$data = $this->generate_token( (int) $user, $device_token );

				if ( 'apple' === $provider->get_id() && ! empty( $access_token_decoded ) ) {
					$apple_user_data = $this->bb_get_user_names_from_social_table( $provider->get_id(), $social_user_id, $access_token_decoded );
					$first_name      = $apple_user_data['first_name'];
					$last_name       = $apple_user_data['last_name'];
					$provider->link_user_to_provider_identifier( (int) $user, $social_user_id, false, $first_name, $last_name );
				}

				$provider->log_login_date( (int) $user );

				// Return success with user id.
				$response->set_data(
					array(
						'response'   => 'success',
						'user_id'    => (int) $user,
						'token'      => $data,
						'login_type' => $login_type,
					)
				);

				$response->set_status( 200 );
			}
		} catch ( Exception $e ) {
			$response->set_data(
				array(
					'response' => 'error',
					'message'  => $e->getMessage(),
				)
			);
			$response->set_status( 400 );
		}

		return $response;
	}

	/**
	 * Generates a JWT token for the authenticated user and registers the device for push notifications if needed.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $wordpress_user_id The WordPress user ID.
	 * @param string $device_token      Optional. The device token for registering push notifications.
	 *
	 * @return array The generated token data including access token, refresh token, and user details.
	 */
	public function generate_token( $wordpress_user_id, $device_token = '' ) {

		if ( class_exists( '\BuddyBossApp\Auth\Jwt' ) ) {

			$jwt  = \BuddyBossApp\Auth\Jwt::instance();
			$user = get_user_by( 'id', $wordpress_user_id );

			if ( class_exists( 'BP_Signup' ) ) {
				// Look for the unactivated signup corresponding to the login name.
				$signup = \BP_Signup::get( array( 'user_login' => sanitize_user( $user->user_login ) ) );

				// If the signup found then activate it.
				if ( ! empty( $signup['signups'] ) && ! empty( $signup['signups'][0] ) && isset( $signup['signups'][0]->signup_id ) ) {
					$signup_id = $signup['signups'][0]->signup_id;
					\BP_Signup::activate( array( $signup_id ) );
				}
			}

			// Set global current-user.
			wp_set_current_user( $user->ID );

			$token_args = array(
				'expire_at_days' => 1,
				// allow only 1 day expire for access token. we have refresh token on service for renew.
			);

			$data = $jwt->generate_jwt_base( $user, true, false, $token_args );

			if ( ! empty( $device_token ) && isset( $data['user_id'] ) && ! empty( $data['user_id'] ) ) {
				if ( function_exists( 'bbapp_notifications' ) && ! empty( $data['access_token'] ) ) {
					bbapp_notifications()->register_device_for_user( $data['user_id'], $device_token, $data['access_token'] );
				}
			}

			$response = array(
				'access_token'      => $data['token'], // access token.
				'refresh_token'     => $data['refresh_token'], // refresh token.
				'user_display_name' => $data['user_display_name'], // user display name.
				'user_nicename'     => $data['user_nicename'], // user nicename.
				'user_email'        => $data['user_email'], // user email.
				'user_id'           => $data['user_id'], // user id.
			);

			/**
			 * Fire after user authentication.
			 *
			 * @type string   $user_login User login.
			 * @type \WP_User $user       User data.
			 * @type array    $data       JWT data.
			 */
			do_action( 'bbapp_auth_wp_login', $user->user_login, $user, $data );

			/**
			 * Fires to catch wp_login hooks same as web login.
			 *
			 * @type string   $user_login User login.
			 * @type \WP_User $user       User data.
			 */
			do_action( 'wp_login', $user->user_login, $user );

			return $response;
		}
	}

	/**
	 * Adds the provider settings to the REST API response.
	 *
	 * @since 2.6.30
	 *
	 * @param array $settings Platform settings.
	 *
	 * @return array Platform settings with provider settings.
	 */
	public function bb_add_provider_settings_to_rest( $settings ) {
		$settings['social_login']               = array();
		$settings['social_login']['is_enabled'] = bb_enable_sso();
		if ( ! empty( BB_SSO::$enabled_providers ) ) {
			$settings['social_login']['enabled_providers'] = array_keys( BB_SSO::$enabled_providers );
		}
		$settings['social_login']['additional_data'] = array(
			'name'            => bb_enable_additional_sso_name(),
			'profile_picture' => bb_enable_additional_sso_profile_picture(),
		);

		$settings['social_login']['registration_option'] = bb_enable_sso_reg_options();

		return $settings;
	}

	/**
	 * Verify Facebook JWT.
	 *
	 * @since 2.6.30
	 *
	 * @param string $access_token The access token.
	 *
	 * @return array|null
	 *
	 * @throws Exception If the token is invalid.
	 */
	private function verify_facebook_jwt( $access_token ) {
		try {
			$token_parts = explode( '.', $access_token );
			if ( count( $token_parts ) !== 3 ) {
				throw new Exception( 'Wrong number of segments' );
			}
			$token_payload = $this->base64_url_decode( $token_parts[1] );
			if ( ! empty( $token_payload ) ) {
				return json_decode( $token_payload, true );
			} else {
				return null;
			}
		} catch ( Exception $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Decode base64 string.
	 *
	 * @param string $data payload string.
	 *
	 * @return false|string
	 */
	private function base64_url_decode( $data ) {
		$data .= str_repeat( '=', 4 - ( strlen( $data ) % 4 ) );

		return base64_decode( strtr( $data, '-_', '+/' ) ); // phpcs:ignore
	}

	/**
	 * Update user id in bb_social_sign_on_users table after user registration.
	 *
	 * @since 2.6.60
	 *
	 * @param \WP_REST_Response $signup   The signup object.
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_REST_Request  $request  The request object.
	 */
	public function bb_rest_signup_create_item( $signup, $response, $request ) {
		$param = $request->get_params();
		// Update user id in bb_social_sign_on_users table after user registration.
		if (
			! empty( $param['sso_type'] ) &&
			! empty( $param['identifier'] ) &&
			! empty( $param['bb-sso-notice'] ) &&
			! empty( $signup->user_email )
		) {
			global $wpdb;
			$user       = get_user_by( 'email', $signup->user_email );
			$wp_user_id = $user ? $user->ID : '';

			$provider_id = $param['sso_type'];
			if ( 'fb' === $param['sso_type'] ) {
				$provider_id = 'facebook';
			}
			$provider = BB_SSO::$enabled_providers[ $provider_id ];

			// Get the first_name and last_name from the bb_social_sign_on_users table and set in the xprofile fields.
			$sso_user = $wpdb->get_results( $wpdb->prepare( "SELECT first_name, last_name FROM {$wpdb->base_prefix}bb_social_sign_on_users WHERE type = %s AND identifier = %s", $param['sso_type'], $param['identifier'] ) );

			// Check not empty $sso_user and set the first and last name then set the xprofile field value.
			if ( ! empty( $sso_user ) ) {
				$first_name = $sso_user[0]->first_name;
				$last_name  = $sso_user[0]->last_name;
				if ( ! empty( $first_name ) ) {
					xprofile_set_field_data( bp_xprofile_firstname_field_id(), $wp_user_id, $first_name );
				}
				if ( ! empty( $last_name ) ) {
					xprofile_set_field_data( bp_xprofile_lastname_field_id(), $wp_user_id, $last_name );
				}
			}

			$wpdb->update(
				$wpdb->prefix . 'bb_social_sign_on_users',
				array(
					'wp_user_id'    => $wp_user_id,
					'link_date'     => current_time( 'mysql' ),
					'register_date' => current_time( 'mysql' ),
					'login_date'    => current_time( 'mysql' ),
				),
				array(
					'type'       => $param['sso_type'],
					'identifier' => $param['identifier'],
				),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
				)
			);

			$sso_user = new \WP_User( $wp_user_id );
			if ( class_exists( 'BP_Signup' ) ) {
				// Look for the unactivated signup corresponding to the login name.
				$signup_data = \BP_Signup::get( array( 'user_login' => sanitize_user( $sso_user->user_login ) ) );

				// If the signup found then activate it.
				if ( ! empty( $signup_data['signups'] ) && ! empty( $signup_data['signups'][0] ) && isset( $signup_data['signups'][0]->signup_id ) ) {
					$signup_id = $signup_data['signups'][0]->signup_id;
					\BP_Signup::activate( array( $signup_id ) );
				}
			}

			$provider->log_login_date( (int) $wp_user_id );

			if ( ! empty( $param['picture'] ) ) {
				\BB_SSO_Avatar::get_instance()->update_avatar( $provider, $wp_user_id, $param['picture'] );
			}

			$response_data = $response->get_data();
			unset( $response_data['message'] );
			unset( $response_data['data'] );

			$data = $this->generate_token( $wp_user_id, $param['device_token'] );

			$new_response = array(
				'response'   => 'success',
				'user_id'    => $wp_user_id,
				'token'      => $data,
				'login_type' => ! empty( $param['login_type'] ) ? $param['login_type'] : 'register',
			);
			$response->set_data( $new_response );
		}
	}

	/**
	 * Get user's first and last name from social table.
	 *
	 * @since 2.7.00
	 *
	 * @param string $provider_id          The provider identifier.
	 * @param string $social_identifier    The social identifier for the user.
	 * @param array  $access_token_decoded The decoded access token.
	 *
	 * @return array An array containing first_name and last_name.
	 */
	private function bb_get_user_names_from_social_table( $provider_id, $social_identifier, $access_token_decoded ) {
		global $wpdb;
		$names = array(
			'first_name' => '',
			'last_name'  => '',
		);

		// First try to get from access token data if available.
		if ( 'apple' === $provider_id && ! empty( $access_token_decoded['user'] ) && bb_enable_additional_sso_name() ) {
			$names['first_name'] = ! empty( $access_token_decoded['user']['name']['firstName'] ) ?
			$access_token_decoded['user']['name']['firstName'] : '';
			$names['last_name']  = ! empty( $access_token_decoded['user']['name']['lastName'] ) ?
			$access_token_decoded['user']['name']['lastName'] : '';
		}

		// If names are still empty, check the database.
		if ( ( empty( $names['first_name'] ) || empty( $names['last_name'] ) ) && ! empty( $social_identifier ) ) {
			$table_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;
			$table_name   = $table_prefix . 'bb_social_sign_on_users';

			$user_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT first_name, last_name FROM {$table_name} WHERE type = %s AND identifier = %s",
					$provider_id,
					$social_identifier
				)
			);

			if ( ! empty( $user_data ) ) {
				$names['first_name'] = ! empty( $user_data->first_name ) ? $user_data->first_name : $names['first_name'];
				$names['last_name']  = ! empty( $user_data->last_name ) ? $user_data->last_name : $names['last_name'];
			}
		}

		return $names;
	}
}

new BB_SSO_REST();
