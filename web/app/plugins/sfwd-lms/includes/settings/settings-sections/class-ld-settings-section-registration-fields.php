<?php
/**
 * LearnDash Settings Section for Registration Fields Metabox.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Registration_Fields' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Registration Fields Metabox.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Section_Registration_Fields extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 3.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_registration';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_registration_fields';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_registration_fields';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_registration_fields';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Registration Fields', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$new_settings = false;
			if ( ! is_array( $this->setting_option_values ) ) {
				$new_settings                = true;
				$this->setting_option_values = array();
			}

			// Fields orders.
			if ( ( ! isset( $this->setting_option_values['fields_order'] ) ) || ( empty( $this->setting_option_values['fields_order'] ) ) ) {
				$this->setting_option_values['fields_order'] = array( 'username', 'email', 'first_name', 'last_name', 'password' );
			}

			// Username.
			if ( ! isset( $this->setting_option_values['username_enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['username_enabled'] = 'yes';
				} else {
					$this->setting_option_values['username_enabled'] = '';
				}
			}
			if ( ! isset( $this->setting_option_values['username_required'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['username_required'] = 'yes';
				} else {
					$this->setting_option_values['username_required'] = '';
				}
			}
			$this->setting_option_values['username_placeholder'] = esc_html__( 'Username', 'learndash' );
			if ( ( ! isset( $this->setting_option_values['username_label'] ) ) || ( empty( $this->setting_option_values['username_label'] ) ) ) {
				$this->setting_option_values['username_label'] = $this->setting_option_values['username_placeholder'];
			}

			// Email.
			if ( ! isset( $this->setting_option_values['email_enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['email_enabled'] = 'yes';
				} else {
					$this->setting_option_values['email_enabled'] = '';
				}
			}
			if ( ! isset( $this->setting_option_values['email_required'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['email_required'] = 'yes';
				} else {
					$this->setting_option_values['email_required'] = '';
				}
			}
			$this->setting_option_values['email_placeholder'] = esc_html__( 'Email', 'learndash' );
			if ( ( ! isset( $this->setting_option_values['email_label'] ) ) || ( empty( $this->setting_option_values['email_label'] ) ) ) {
				$this->setting_option_values['email_label'] = $this->setting_option_values['email_placeholder'];
			}

			// First Name.
			if ( ! isset( $this->setting_option_values['first_name_enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['first_name_enabled'] = 'yes';
				} else {
					$this->setting_option_values['first_name_enabled'] = '';
				}
			}
			if ( ! isset( $this->setting_option_values['first_name_required'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['first_name_required'] = 'yes';
				} else {
					$this->setting_option_values['first_name_required'] = '';
				}
			}
			$this->setting_option_values['first_name_placeholder'] = esc_html__( 'First Name', 'learndash' );
			if ( ( ! isset( $this->setting_option_values['first_name_label'] ) ) || ( empty( $this->setting_option_values['first_name_label'] ) ) ) {
				$this->setting_option_values['first_name_label'] = $this->setting_option_values['first_name_placeholder'];
			}

			// Last Name.
			if ( ! isset( $this->setting_option_values['last_name_enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['last_name_enabled'] = 'yes';
				} else {
					$this->setting_option_values['last_name_enabled'] = '';
				}
			}
			if ( ! isset( $this->setting_option_values['last_name_required'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['last_name_required'] = 'yes';
				} else {
					$this->setting_option_values['last_name_required'] = '';
				}
			}
			$this->setting_option_values['last_name_placeholder'] = esc_html__( 'Last Name', 'learndash' );
			if ( ( ! isset( $this->setting_option_values['last_name_label'] ) ) || ( empty( $this->setting_option_values['last_name_label'] ) ) ) {
				$this->setting_option_values['last_name_label'] = $this->setting_option_values['last_name_placeholder'];
			}

			// Passwords Set.
			if ( ! isset( $this->setting_option_values['password_enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['password_enabled'] = 'yes';
				} else {
					$this->setting_option_values['password_enabled'] = '';
				}
			}
			if ( ! isset( $this->setting_option_values['password_required'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['password_required'] = 'yes';
				} else {
					$this->setting_option_values['password_required'] = '';
				}
			}
			$this->setting_option_values['password_placeholder'] = esc_html__( 'Password', 'learndash' );
			if ( ( ! isset( $this->setting_option_values['password_label'] ) ) || ( empty( $this->setting_option_values['password_label'] ) ) ) {
				$this->setting_option_values['password_label'] = $this->setting_option_values['password_placeholder'];
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array();

			$this->setting_option_fields['fields_order'] = array(
				'name'              => 'fields_order',
				'label'             => '',
				'type'              => 'hidden',
				'value'             => $this->setting_option_values['fields_order'],
				'validate_callback' => array( $this, 'validate_registration_fields_order' ),
				'display_callback'  => array( $this, 'display_registration_fields_order' ),
			);

			foreach ( $this->setting_option_values['fields_order'] as $field_prefix ) {
				$this->setting_option_fields[ $field_prefix . '_enabled' ]  = array(
					'name'    => $field_prefix . '_enabled',
					'type'    => 'checkbox-switch',
					'label'   => '',
					'value'   => $this->setting_option_values[ $field_prefix . '_enabled' ],
					'options' => array(
						'yes' => '',
					),
				);
				$this->setting_option_fields[ $field_prefix . '_label' ]    = array(
					'name'        => $field_prefix . '_label',
					'type'        => 'text',
					'label'       => '',
					'value'       => $this->setting_option_values[ $field_prefix . '_label' ],
					'class'       => 'regular-text',
					'placeholder' => $this->setting_option_values[ $field_prefix . '_placeholder' ],
					'attrs'       => array(
						'required' => 'required',
					),
				);
				$this->setting_option_fields[ $field_prefix . '_required' ] = array(
					'name'    => $field_prefix . '_required',
					'type'    => 'checkbox',
					'label'   => '',
					'value'   => $this->setting_option_values[ $field_prefix . '_required' ],
					'options' => array(
						'yes' => '',
					),
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Custom field validation for `fields_order` settings.
		 *
		 * @since 3.6.0
		 *
		 * @param mixed  $val  Value to validate.
		 * @param string $key  Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return string value.
		 */
		public function validate_registration_fields_order( $val, $key, $args = array() ) {
			if ( ! is_array( $val ) ) {
				$val = array();
			}

			if ( ( is_array( $args['field']['value'] ) ) && ( ! empty( $args['field']['value'] ) ) ) {
				$val_new = array();
				foreach ( $val as $val_value ) {
					$val_value = sanitize_text_field( $val_value );

					if ( in_array( $val_value, $args['field']['value'], true ) ) {
						$val_new[] = $val_value;
					}
				}
				$val = $val_new;
			} else {
				$val = array();
			}

			return $val;
		}

		/**
		 * Intercept the WP options save logic and ensure labels are not empty
		 *
		 * @since 3.6.0
		 *
		 * @param array  $current_values Array of section fields values.
		 * @param array  $old_values     Array of old values.
		 * @param string $option         Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $current_values = '', $old_values = '', $option = '' ) {
			if ( $option === $this->setting_option_key ) {
				$current_values = parent::section_pre_update_option( $current_values, $old_values, $option );
				foreach ( $this->setting_option_values['fields_order'] as $field_prefix ) {
					if ( ( ! isset( $current_values[ $field_prefix . '_label' ] ) ) || ( empty( $current_values[ $field_prefix . '_label' ] ) ) ) {
						if ( isset( $this->setting_option_values[ $field_prefix . '_placeholder' ] ) ) {
							$current_values[ $field_prefix . '_label' ] = $this->setting_option_values[ $field_prefix . '_placeholder' ];
						}
					}
				}
			}

			return $current_values;
		}

		/**
		 * Customer Show the meta box settings
		 *
		 * @since 3.6.0
		 *
		 * @param string $section Section to be shown.
		 */
		public function show_settings_section( $section = null ) {
			global $wp_settings_fields;

			$page    = $this->settings_page_id;
			$section = $this->settings_section_key;

			if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			$fields = $wp_settings_fields[ $page ][ $section ];

			?>
			<div class="sfwd sfwd_options">
				<table class="learndash-settings-table learndash-settings-table-registrations-fields learndash-settings-table-sortable widefat striped" cellspacing="0">
				<thead>
				<tr>
					<th class="col-name-move"></th>
					<th class="col-name-enabled"><?php esc_html_e( 'Enabled', 'learndash' ); ?></th>
					<th class="col-name-required"><?php esc_html_e( 'Required', 'learndash' ); ?></th>
					<th class="col-name-label"><?php esc_html_e( 'Label', 'learndash' ); ?></th>
				<tr>
				</thead>
				<tbody>
				<?php
				foreach ( (array) $fields['fields_order']['args']['value'] as $field_prefix ) {
					?>
						<tr>
							<td class="col-name-move col-valign-middle">
								<span class="dashicons dashicons-menu-alt"></span>
								<input type="hidden" name="learndash_settings_registration_fields[fields_order][]" id="learndash_settings_registration_fields_fields_order_<?php echo esc_attr( $field_prefix ); ?>" class="learndash-section-field learndash-section-field-hidden" value="<?php echo esc_attr( $field_prefix ); ?>">
							</td>
							<td class="col-name-enabled col-valign-middle">
								<div class="sfwd_option_div">
							<?php
							if ( isset( $fields[ $field_prefix . '_enabled' ] ) && ( 'first_name' === $field_prefix || 'last_name' === $field_prefix ) ) {
								call_user_func( $fields[ $field_prefix . '_enabled' ]['args']['display_callback'], $fields[ $field_prefix . '_enabled' ]['args'] );
							} else {
								echo '<input type="hidden" value="yes" name="learndash_settings_registration_fields[' . esc_attr( $field_prefix ) . '_enabled]" />';
								echo esc_html_e( 'Required', 'learndash' );
							}
							?>
								</div>
							</td>
							<td class="col-name-required col-valign-middle">
								<div class="sfwd_option_div">
								<?php
								if ( isset( $fields[ $field_prefix . '_required' ] ) && ( 'first_name' === $field_prefix || 'last_name' === $field_prefix ) ) {
									call_user_func( $fields[ $field_prefix . '_required' ]['args']['display_callback'], $fields[ $field_prefix . '_required' ]['args'] );
								} else {
									echo '<input type="hidden" value="yes" name="learndash_settings_registration_fields[' . esc_attr( $field_prefix ) . '_required]" / checked="checked">';
									echo esc_html_e( 'Required', 'learndash' );
								}
								?>
								</div>
							</td>
							<td class="col-name-label">
								<div class="sfwd_option_div">
								<?php
								if ( isset( $fields[ $field_prefix . '_label' ] ) ) {
									call_user_func( $fields[ $field_prefix . '_label' ]['args']['display_callback'], $fields[ $field_prefix . '_label' ]['args'] );
								}
								?>
								</div>
							</td>
						</tr>
						<?php
				}
				?>
				</tbody>
				</table>
			</div>
			<?php
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Registration_Fields::add_section_instance();
	}
);
