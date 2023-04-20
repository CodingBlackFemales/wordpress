<?php
/**
 * LearnDash Settings Fields API.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Fields' ) ) {

	/**
	 * Class to create the settings field.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Settings_Fields {

		/**
		 * Array to hold all field type instances.
		 *
		 * @var array
		 */
		protected static $_instances = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Define the field type 'text', 'select', etc. This is unique.
		 *
		 * @var string
		 */
		protected $field_type = '';

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
		}

		/**
		 * Get field instance by key
		 *
		 * @since 3.0.0
		 *
		 * @param string $field_key Key to unique field instance.
		 *
		 * @return object instance of field if present.
		 */
		final public static function get_field_instance( $field_key = '' ) {
			if ( ! empty( $field_key ) ) {
				if ( isset( self::$_instances[ $field_key ] ) ) {
					return self::$_instances[ $field_key ];
				}
			}

			return null;
		}

		/**
		 * Add field instance by key
		 *
		 * @since 3.0.0
		 *
		 * @param string $field_key Key to unique field instance.
		 *
		 * @return object instance of field if present.
		 */
		final public static function add_field_instance( $field_key = '' ) {
			if ( ! empty( $field_key ) ) {
				if ( ! isset( self::$_instances[ $field_key ] ) ) {
					$section_class                  = get_called_class();
					self::$_instances[ $field_key ] = new $section_class();
				}
				return self::$_instances[ $field_key ];
			}

			return null;
		}

		/**
		 * Utility function so we are not hard coding the create/validate
		 * member functions in various settings files.
		 *
		 * @since 3.0.0
		 *
		 * @return reference to validation function.
		 */
		final public function get_creation_function_ref() {
			return array( $this, 'create_section_field' );
		}

		/**
		 * Utility function so we are not hard coding the create/validate
		 * member functions in various settings files.
		 *
		 * @since 3.0.0
		 *
		 * @return reference to validation function.
		 */
		final public function get_validation_function_ref() {
			return array( $this, 'validate_section_field' );
		}

		/**
		 * Utility function so we are not hard coding the create/validate
		 * member functions in various settings files.
		 *
		 * @since 3.0.0
		 *
		 * @return reference to validation function.
		 */
		final public function get_value_function_ref() {
			return array( $this, 'value_section_field' );
		}

		/**
		 * Show all fields in section.
		 *
		 * @since 3.0.0
		 *
		 * @param array $section_fields Array of fields for section.
		 */
		public static function show_section_fields( $section_fields = array() ) {

			if ( ! empty( $section_fields ) ) {
				$parents_settings = array();
				foreach ( $section_fields as $field_id => $field ) {
					if ( ( isset( $field['args']['parent_setting'] ) ) && ( ! empty( $field['args']['parent_setting'] ) ) ) {
						// if we have a 'parent_setting'. Then try and figure out if it was the same as the last one.
						if ( ( empty( $parents_settings ) ) || ( ! in_array( $field['args']['parent_setting'], $parents_settings, true ) ) ) {
							$parent_setting_slug = $field['args']['parent_setting'];
							if ( ( isset( $section_fields[ $parent_setting_slug ]['args']['child_section_state'] ) ) && ( 'open' === $section_fields[ $parent_setting_slug ]['args']['child_section_state'] ) ) {
								$child_setting_state = 'open';
							} else {
								$child_setting_state = 'closed';
							}
							$parents_settings[] = $field['args']['parent_setting'];

							echo '<div class="ld-settings-sub ld-settings-sub-level-' . count( $parents_settings ) . ' ld-settings-sub-' . esc_attr( $field['args']['parent_setting'] ) . ' ld-settings-sub-state-' . esc_attr( $child_setting_state ) . '" data-parent-field="' . esc_attr( $field['args']['setting_option_key'] ) . '_' . esc_attr( $field['args']['parent_setting'] ) . '_field">';
						} else {
							if ( $parents_settings[ count( $parents_settings ) - 1 ] === $field['args']['parent_setting'] ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf

							} elseif ( in_array( $field['args']['parent_setting'], $parents_settings, true ) ) {
								while ( ! empty( $parents_settings ) ) {
									$p_set = $parents_settings[ count( $parents_settings ) - 1 ];
									if ( $p_set !== $field['args']['parent_setting'] ) {
										echo '</div>';
										unset( $parents_settings[ count( $parents_settings ) - 1 ] );
									} else {
										break;
									}
								}
								if ( empty( $parents_settings ) ) {
									$parents_settings = array();
								} else {
									$parents_settings = array_values( $parents_settings );
								}
							}
						}
					} elseif ( ! empty( $parents_settings ) ) {
						while ( ! empty( $parents_settings ) ) {
							$p_set = $parents_settings[ count( $parents_settings ) - 1 ];
							echo '</div>';
							unset( $parents_settings[ count( $parents_settings ) - 1 ] );
						}
						if ( empty( $parents_settings ) ) {
							$parents_settings = array();
						} else {
							$parents_settings = array_values( $parents_settings );
						}
					}
					self::show_section_field_row( $field );
				}
				if ( ! empty( $parents_settings ) ) {
					while ( ! empty( $parents_settings ) ) {
						$p_set = $parents_settings[ count( $parents_settings ) - 1 ];
						echo '</div>';
						unset( $parents_settings[ count( $parents_settings ) - 1 ] );
					}
					if ( empty( $parents_settings ) ) {
						$parents_settings = array();
					}
				}
			}
		}

		/**
		 * Shows the field row
		 *
		 * @since 3.0.0
		 *
		 * @param array $field Array of field settings.
		 */
		public static function show_section_field_row( $field ) {
			$field_error_class = '';

			if ( ( isset( $field['args']['setting_option_key'] ) ) && ( ! empty( $field['args']['setting_option_key'] ) ) ) {
				$settings_errors = get_settings_errors( $field['args']['setting_option_key'] );
				if ( ! empty( $settings_errors ) ) {
					foreach ( $settings_errors as $settings_error ) {
						if ( ( $settings_error['setting'] == $field['args']['setting_option_key'] ) && ( $settings_error['code'] == $field['args']['name'] ) && ( 'error' == $settings_error['type'] ) ) {
							$field_error_class = 'learndash-settings-field-error';
						}
					}
				}
			}

			$field_class = '';
			if ( ( isset( $field['args']['type'] ) ) && ( ! empty( $field['args']['type'] ) ) ) {
				$field_instance = self::get_field_instance( $field['args']['type'] );
				if ( ( ! $field_instance ) || ( 'LearnDash_Settings_Fields' !== get_parent_class( $field_instance ) ) ) {
					return;
				}
				$field_class = 'sfwd_input_type_' . $field['args']['type'];
			}

			if ( ( isset( $field['args']['desc_before'] ) ) && ( ! empty( $field['args']['desc_before'] ) ) ) {
				echo wp_kses_post( wptexturize( $field['args']['desc_before'] ) );
			}
			if ( ( isset( $field['args']['row_disabled'] ) ) && ( true === $field['args']['row_disabled'] ) ) {
				$field_class .= ' learndash-row-disabled';
			}

			// Adds a required class to the field row container.
			if ( ( isset( $field['args']['required'] ) ) && ( 'required' === $field['args']['required'] ) ) {
				$field_class .= ' learndash-settings-input-required';
			}

			if ( ( isset( $field['args']['type'] ) ) && ( 'hidden' !== $field['args']['type'] ) ) {
				/**
				 * Filters the HTML content to be echoed before outside row settings.
				 *
				 * @param string $output_content Content to be echoed.
				 * @param array  $field_arguments An array of setting field arguments.
				 */
				$output = apply_filters( 'learndash_settings_row_outside_before', '', $field['args'] );
				if ( ! empty( $output ) ) {
					echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
				}
				?>
				<div id="<?php echo esc_attr( $field['args']['id'] ); ?>_field" class="sfwd_input <?php echo esc_attr( $field_class ); ?> <?php echo esc_attr( $field_error_class ); ?>">
					<?php
						/**
						 * Filters the HTML content to be echoed before inside row settings.
						 *
						 * @param string $output_content Content to be echoed.
						 * @param array  $field_arguments An array of setting field arguments.
						 */
						$output = apply_filters( 'learndash_settings_row_inside_before', '', $field['args'] );
					if ( ! empty( $output ) ) {
						echo $output;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
					};
					?>
					<?php
					if ( ( isset( $field['args']['row_description_before'] ) ) && ( ! empty( $field['args']['row_description_before'] ) ) ) {
						echo '<span class="sfwd_row_description sfwd_row_description_before">' . esc_html( $field['args']['row_description_before'] ) . '</span>';
					}
					?>
					<?php if ( ( ! isset( $field['args']['label_none'] ) ) || ( true !== $field['args']['label_none'] ) ) { ?>
						<?php
						/**
						 * Filters the HTML content to be echoed before outside row label settings.
						 *
						 * @param string $output_content Content to be echoed.
						 * @param array  $field_arguments An array of setting field arguments.
						 */
						$output = apply_filters( 'learndash_settings_row_label_outside_before', '', $field['args'] );
						if ( ! empty( $output ) ) {
							echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
						}
						?>
						<span class="sfwd_option_label
						<?php
						if ( ( isset( $field['args']['label_full'] ) ) && ( true === $field['args']['label_full'] ) ) {
							echo ' sfwd_option_label_full';
						}
						?>
							">
							<?php
							/**
							 * Filters the HTML content to be echoed before inside row label settings.
							 *
							 * @param string $output_content Content to be echoed.
							 * @param array  $field_arguments An array of setting field arguments.
							 */
								$output = apply_filters( 'learndash_settings_row_label_inside_before', '', $field['args'] );
							if ( ! empty( $output ) ) {
								echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
							};
							?>
							<a class="sfwd_help_text_link"
								<?php if ( ( isset( $field['args']['help_text'] ) ) && ( ! empty( $field['args']['help_text'] ) ) ) { ?>
									style="cursor:pointer;" title="<?php esc_html_e( 'Click for Help!', 'learndash' ); ?>"
									onclick="toggleVisibility('<?php echo esc_attr( $field['args']['id'] ); ?>_tip');"
								<?php } ?>
								>
								<?php if ( ( isset( $field['args']['help_text'] ) ) && ( ! empty( $field['args']['help_text'] ) ) ) { ?>
									<img alt="" src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL ); ?>assets/images/question.png" />
								<?php } ?>

								<label for="<?php echo esc_attr( $field['args']['label_for'] ); ?>" class="sfwd_label">
								<?php
								if ( ( isset( $field['args']['label'] ) ) && ( ! empty( $field['args']['label'] ) ) ) {
									echo esc_html( $field['args']['label'] );
								}
								if ( isset( $field['args']['required'] ) ) {
									?>
									<span class="learndash_required_field"><abbr title="<?php esc_html_e( 'Required', 'learndash' ); ?>">*</abbr></span>
									<?php
								}
								?>
								</label>
							</a>
							<?php
							if ( ( isset( $field['args']['label_description'] ) ) && ( ! empty( $field['args']['label_description'] ) ) ) {
								?>
								<span class="descripton"><?php echo wp_kses_post( $field['args']['label_description'] ); // cspell:disable-line. ?></span>
								<?php
							}

							if ( ( isset( $field['args']['help_text'] ) ) && ( ! empty( $field['args']['help_text'] ) ) ) {
								if ( ( isset( $field['args']['help_show'] ) ) && ( true === $field['args']['help_show'] ) ) {
									$help_style = ' style="display: block !important;" ';
								} else {
									$help_style = ' style="display: none;" ';
								}
								?>
								<div id="<?php echo esc_attr( $field['args']['id'] ); ?>_tip" class="sfwd_help_text_div" <?php echo $help_style; ?>> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $help_style hardcoded above. ?>
									<label class="sfwd_help_text"><?php echo wp_kses_post( $field['args']['help_text'] ); ?></label>
								</div>
								<?php
							}
							?>
							<?php
								/**
								 * Filters the HTML content to be echoed after inside row label settings.
								 *
								 * @param string $output_content Content to be echoed.
								 * @param array  $field_arguments An array of setting field arguments.
								 */
								$output = apply_filters( 'learndash_settings_row_label_inside_after', '', $field['args'] );
							if ( ! empty( $output ) ) {
								echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
							};
							?>
						</span>
							<?php
								/**
								 * Filters the HTML content to be echoed after outside row label settings.
								 *
								 * @param string $output_content Content to be echoed.
								 * @param array  $field_arguments An array of setting field arguments.
								 */
								$output = apply_filters( 'learndash_settings_row_label_outside_after', '', $field['args'] );
							if ( ! empty( $output ) ) {
								echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
							};
							?>
					<?php } ?>
					<?php
						/**
						 * Filters the HTML content to be echoed before outside row input settings.
						 *
						 * @param string $output_content Content to be echoed.
						 * @param array  $field_arguments An array of setting field arguments.
						 */
						$output = apply_filters( 'learndash_settings_row_input_outside_before', '', $field['args'] );
					if ( ! empty( $output ) ) {
						echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
					};
					?>
					<span class="sfwd_option_input
					<?php
					if ( ( isset( $field['args']['input_full'] ) ) && ( true === $field['args']['input_full'] ) ) {
						echo ' sfwd_option_input_full';
					}
					?>
						">
						<?php
							/**
							 * Filters the HTML content to be echoed before outside row label settings.
							 *
							 * @param string $output_content Content to be echoed.
							 * @param array  $field_arguments An array of setting field arguments.
							 */
							$output = apply_filters( 'learndash_settings_row_input_inside_before', '', $field['args'] );
						if ( ! empty( $output ) ) {
							echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
						}

						if ( ( ! isset( $field['args']['input_show'] ) ) || ( true === $field['args']['input_show'] ) ) {
							?>
							<div class="sfwd_option_div">
								<?php call_user_func( $field['args']['display_callback'], $field['args'] ); ?>

								<?php
								if ( isset( $field['args']['input_note'] ) && ! empty( $field['args']['input_note'] ) ) {
									?>
									<span class="sfwd_option_input_note"><?php echo wp_kses_post( $field['args']['input_note'] ); ?></span>
									<?php
								}
								?>
							</div>
							<?php
						}
						/**
						 * Filters the HTML content to be echoed after inside row input settings.
						 *
						 * @param string $output_content Content to be echoed.
						 * @param array  $field_arguments An array of setting field arguments.
						 */
						$output = apply_filters( 'learndash_settings_row_input_inside_after', '', $field['args'] );
						if ( ! empty( $output ) ) {
							echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
						};
						?>
					</span>
					<?php
						/**
						 * Filters the HTML content to be echoed after outside row input settings.
						 *
						 * @param string $output_content Content to be echoed.
						 * @param array  $field_arguments An array of setting field arguments.
						 */
						$output = apply_filters( 'learndash_settings_row_input_outside_after', '<p class="ld-clear"></p>', $field['args'] );
					if ( ! empty( $output ) ) {
						echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
					};
					?>
					<?php
					if ( ( isset( $field['args']['row_description_after'] ) ) && ( ! empty( $field['args']['row_description_after'] ) ) ) {
						echo '<span class="sfwd_row_description sfwd_row_description_after">' . esc_html( $field['args']['row_description_after'] ) . '</span>';
					}
					?>

					<?php
					/**
					 * Filters the HTML content to be echoed after inside row settings.
					 *
					 * @param string $output_content Content to be echoed.
					 * @param array  $field_arguments An array of setting field arguments.
					 */
					$output = apply_filters( 'learndash_settings_row_inside_after', '', $field['args'] );
					if ( ! empty( $output ) ) {
						echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
					}
					?>
				</div>
				<?php
				/**
				 * Filters the HTML content to be echoed after outside row settings.
				 *
				 * @param string $output_content Content to be echoed.
				 * @param array  $field_arguments An array of setting field arguments.
				 */
				$output = apply_filters( 'learndash_settings_row_outside_after', '', $field['args'] );
				if ( ! empty( $output ) ) {
					echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
				}
			} else {
				if ( ( isset( $field['callback'] ) ) && ( ! empty( $field['callback'] ) ) && ( is_callable( $field['callback'] ) ) ) {
					call_user_func( $field['callback'], $field['args'] );
				}
			}
			if ( ( isset( $field['args']['desc_after'] ) ) && ( ! empty( $field['args']['desc_after'] ) ) ) {
				echo wp_kses_post( wptexturize( $field['args']['desc_after'] ) );
			}
		}

		/**
		 * Skeleton function to create the field output.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array.
		 */
		public function create_section_field( $field_args = array() ) {
		}

		/**
		 * Create the HTML output from the field args 'id' attribute.
		 *
		 * @since 3.4.0.5
		 *
		 * @param array   $field_args main field args array. should contain element for 'attrs'.
		 * @param boolean $wrap Flag to wrap field attribute in normal output or just return value.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_id( $field_args = array(), $wrap = true ) {
			$field_attribute = '';

			if ( isset( $field_args['id'] ) ) {
				if ( true === $wrap ) {
					$field_attribute .= ' id="' . $field_args['id'] . '" ';
				} else {
					$field_attribute .= $field_args['id'];
				}
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'required' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_required( $field_args = array() ) {
			$field_attribute = '';

			if ( isset( $field_args['required'] ) ) {
				$field_attribute .= ' required="' . $field_args['required'] . '" ';
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'input_label_before' attribute.
		 *
		 * @since 3.3.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_label_before( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['input_label_before'] ) ) && ( ! empty( $field_args['input_label_before'] ) ) ) {
				$field_attribute .= $field_args['input_label_before'];
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'input_mask_before' attribute.
		 *
		 * @since 3.3.0
		 *
		 * @param array $field_args main field args array.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_mask_before( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['input_mask_before'] ) ) && ( ! empty( $field_args['input_mask_before'] ) ) ) {
				$field_attribute .= ' value-input-mask-before="' . $field_args['input_mask_before'] . '" ';
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'name' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array   $field_args main field args array. should contain element for 'attrs'.
		 * @param boolean $wrap Flag to wrap field attribute in normal output or just return value.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_name( $field_args = array(), $wrap = true ) {
			$field_attribute = '';

			if ( isset( $field_args['name'] ) ) {
				$field_multiple = '';
				if ( ( isset( $field_args['multiple'] ) ) && ( true == $field_args['multiple'] ) ) {
					$field_multiple = '[]';
				}

				if ( ! empty( $field_args['setting_option_key'] ) && ( ! isset( $field_args['use_raw_name'] ) || ( isset( $field_args['use_raw_name'] ) && false === $field_args['use_raw_name'] ) ) ) {
					if ( true === $wrap ) {
						if ( ( isset( $field_args['name_wrap'] ) ) && ( true === $field_args['name_wrap'] ) ) {
							$field_attribute .= ' name="' . $field_args['setting_option_key'] . '[' . $field_args['name'] . ']' . $field_multiple . '" ';
						} else {
							$field_attribute .= ' name="' . $field_args['name'] . $field_multiple . '" ';
						}
					} else {
						if ( ( isset( $field_args['name_wrap'] ) ) && ( true === $field_args['name_wrap'] ) ) {
							$field_attribute .= $field_args['setting_option_key'] . '[' . $field_args['name'] . ']';
						} else {
							$field_attribute .= $field_args['name'];
						}
					}
				} else {
					if ( true === $wrap ) {
						$field_attribute .= ' name="' . $field_args['name'] . $field_multiple . '" ';
					} else {
						$field_attribute .= $field_args['name'] . $field_multiple;
					}
				}
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'placeholder' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_placeholder( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['placeholder'] ) ) && ( ! empty( $field_args['placeholder'] ) ) ) {
				if ( is_string( $field_args['placeholder'] ) ) {
					$field_attribute .= ' placeholder="' . esc_html( $field_args['placeholder'] ) . '" ';
				} elseif ( is_array( $field_args['placeholder'] ) ) {
					foreach ( $field_args['placeholder'] as $placeholder_key => $placeholder_value ) {
						$field_attribute .= ' placeholder="' . esc_html( $placeholder_value ) . '" ';
						break;
					}
				}
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'placeholder' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array   $field_args main field args array. should contain element for 'attrs'.
		 * @param boolean $wrap Flag to wrap field attribute in normal output or just return value.
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_value( $field_args = array(), $wrap = true ) {
			$field_attribute = '';

			if ( isset( $field_args['id'] ) ) {
				if ( true === $wrap ) {
					$field_attribute .= ' value="' . $field_args['value'] . '" ';
				} else {
					$field_attribute .= $field_args['value'];
				}
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'legend' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_legend( $field_args = array() ) {
			$field_legend = '';

			if ( ( isset( $field_args['label'] ) ) && ( ! empty( $field_args['label'] ) ) ) {
				$field_legend .= '<legend class="screen-reader-text">';
				$field_legend .= '<span>' . $field_args['label'] . '</span>';
				$field_legend .= '</legend>';
			}

			return $field_legend;
		}

		/**
		 * Create the HTML output from the field args 'type' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_type( $field_args = array() ) {
			$field_attribute = '';

			if ( isset( $field_args['type'] ) ) {
				$field_attribute .= ' type="' . $field_args['type'] . '" ';
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'class' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 * @param bool  $wrap       Whether to create a CSS class from the field args.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_class( $field_args = array(), $wrap = true ) {
			$field_attribute = '';

			if ( true === $wrap ) {
				$field_attribute .= 'class="';
			}
			$field_attribute .= 'learndash-section-field learndash-section-field-' . $this->field_type;

			if ( ( isset( $field_args['class'] ) ) && ( ! empty( $field_args['class'] ) ) ) {
				$field_attribute .= ' ' . $field_args['class'];
			}
			if ( true === $wrap ) {
				$field_attribute .= '" ';
			}

			return $field_attribute;
		}

		/**
		 * Create the HTML output from the field args 'attrs' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. should contain element for 'attrs'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_misc( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['attrs'] ) ) && ( ! empty( $field_args['attrs'] ) ) ) {
				foreach ( $field_args['attrs'] as $key => $val ) {
					$field_attribute .= ' ' . $key . '="' . $val . '" ';
				}
			}

			return $field_attribute;
		}


		/**
		 * Create the HTML output from the field args 'input_label' attribute.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args main field args array. Should contain element for 'input_label'.
		 *
		 * @return string of HTML representation of the attrs array attributes.
		 */
		public function get_field_attribute_input_label( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['input_label'] ) ) && ( ! empty( $field_args['input_label'] ) ) ) {
				$field_attribute .= ' ' . $field_args['input_label'];
			}

			return $field_attribute;
		}

		/**
		 * Get Field Error Message
		 *
		 * @since 3.0.7
		 *
		 * @param array $field_args Array of field args.
		 */
		public function get_field_error_message( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['input_error'] ) ) && ( ! empty( $field_args['input_error'] ) ) ) {
				$field_attribute .= '<div class="learndash-section-field-error" style="display:none;">' . $field_args['input_error'] . '</div>';
			}

			return $field_attribute;
		}

		/**
		 * Get Field Attribute Input Description
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args Array of field args.
		 */
		public function get_field_attribute_input_description( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['input_description'] ) ) && ( ! empty( $field_args['input_description'] ) ) ) {
				$field_attribute .= '<span class="descripton">' . $field_args['input_description'] . '</span>'; // cspell:disable-line.
			}

			return $field_attribute;
		}

		/**
		 * Get Field Sub-Field Trigger
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args Array of field args.
		 */
		public function get_field_sub_trigger( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['name'] ) ) && ( ! empty( $field_args['name'] ) ) ) {
				$field_attribute .= ' data-settings-sub-trigger="ld-settings-sub-' . $field_args['name'] . '" ';
			}

			return $field_attribute;
		}

		/**
		 * Get Field Inner-Field Trigger
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args Array of field args.
		 */
		public function get_field_inner_trigger( $field_args = array() ) {
			$field_attribute = '';

			if ( ( isset( $field_args['name'] ) ) && ( ! empty( $field_args['name'] ) ) ) {
				$field_attribute .= ' data-settings-inner-trigger="ld-settings-inner-' . $field_args['name'] . '" ';
			}

			return $field_attribute;
		}


		/**
		 * Default validation function. Should be overridden in Field subclass.
		 *
		 * @since 3.0.0
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return mixed $val validated value.
		 */
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ! empty( $val ) ) {
						$val = call_user_func( $args['field']['value_type'], $val );
			}

			return $val;
		}

		/**
		 * Get Settings Field Value.
		 *
		 * @since 3.0.0
		 *
		 * @param mixed  $val       Value to validate.
		 * @param string $key       Key of value being validated.
		 * @param array  $args      Array of field args.
		 * @param array  $post_args Array of post args.
		 *
		 * @return mixed $val validated value.
		 */
		public function value_section_field( $val = '', $key = '', $args = array(), $post_args = array() ) {
			return $val;
		}

		/**
		 * Convert Settings Field value to REST value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed           $val        Value from REST to be converted to internal value.
		 * @param string          $key        Key field for value.
		 * @param array           $field_args Array of field args.
		 * @param WP_REST_Request $request    Request object.
		 */
		public function field_value_to_rest_value( $val, $key, $field_args, WP_REST_Request $request ) {
			return $val;
		}

		/**
		 * Convert REST submit value to internal Settings Field acceptable value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed  $val        Value from REST to be converted to internal value.
		 * @param string $key        Key field for value.
		 * @param array  $field_args Array of field args.
		 */
		public function rest_value_to_field_value( $val = '', $key = '', $field_args = array() ) {
			return $val;
		}
	}
}
