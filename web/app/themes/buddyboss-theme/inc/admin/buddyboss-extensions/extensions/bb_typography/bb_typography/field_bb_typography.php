<?php
/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - render()
 * - enqueue()
 * - make_google_web_font_link()
 * - make_google_web_font_string()
 * - output()
 * - get_google_array()
 * - get_subsets()
 * - get_variants()
 * Classes list:
 * - ReduxFramework_typography
 */
if ( ! class_exists( 'ReduxFramework_bb_typography' ) ) {
	#[\AllowDynamicProperties]
	class ReduxFramework_bb_typography {
		/**
		 * Array of data for typography preview.
		 *
		 * @var array
		 */
		private $typography_preview = array();
		/**
		 *  Standard font array.
		 *
		 * @var array $std_fonts
		 */
		private $std_fonts = array(
			'Arial, Helvetica, sans-serif'                             => 'Arial, Helvetica, sans-serif',
			'\'Arial Black\', Gadget, sans-serif'                      => '\'Arial Black\', Gadget, sans-serif',
			'\'Bookman Old Style\', serif'                             => '\'Bookman Old Style\', serif',
			'\'Comic Sans MS\', cursive'                               => '\'Comic Sans MS\', cursive',
			'Courier, monospace'                                       => 'Courier, monospace',
			'Garamond, serif'                                          => 'Garamond, serif',
			'Georgia, serif'                                           => 'Georgia, serif',
			'Impact, Charcoal, sans-serif'                             => 'Impact, Charcoal, sans-serif',
			'\'Lucida Console\', Monaco, monospace'                    => '\'Lucida Console\', Monaco, monospace',
			'\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif'   => '\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif',
			'\'MS Sans Serif\', Geneva, sans-serif'                    => '\'MS Sans Serif\', Geneva, sans-serif',
			'\'MS Serif\', \'New York\', sans-serif'                   => '\'MS Serif\', \'New York\', sans-serif',
			'\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif' => '\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif',
			'Tahoma,Geneva, sans-serif'                                => 'Tahoma, Geneva, sans-serif',
			'\'Times New Roman\', Times,serif'                         => '\'Times New Roman\', Times, serif',
			'\'Trebuchet MS\', Helvetica, sans-serif'                  => '\'Trebuchet MS\', Helvetica, sans-serif',
			'Verdana, Geneva, sans-serif'                              => 'Verdana, Geneva, sans-serif',
		);
		/**
		 * User font array.
		 *
		 * @var bool $user_fonts
		 */
		private $user_fonts = true;
		public $extension_dir = '';
		public $extension_url = '';
		
		/**
		 * Redux_Field constructor.
		 *
		 * @param array  $field  Field array.
		 * @param string $value  Field values.
		 * @param null   $parent ReduxFramework object pointer.
		 */
		public function __construct( $field = array(), $value = null, $parent = null ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
			$this->parent = $parent;
			$this->field  = $field;
			$this->value  = $value;
			$this->set_defaults();
			if ( empty( $this->extension_dir ) ) {
				$this->extension_dir = trailingslashit( str_replace( '\\', '/', dirname( __FILE__ ) ) );
				$this->extension_url = site_url( str_replace( trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ), 'wp-content/', $this->extension_dir ) );
			}
			$this->timestamp = Redux_Core::$version;
			if ( $parent->args['dev_mode'] ) {
				$this->timestamp .= '.' . time();
			}
		}
		
		/**
		 * Sets default values for field.
		 */
		public function set_defaults() {
			// Shim out old arg to new.
			if ( isset( $this->field['all_styles'] ) && ! empty( $this->field['all_styles'] ) ) {
				$this->field['all-styles'] = $this->field['all_styles'];
				unset( $this->field['all_styles'] );
			}
			$defaults    = array(
				'font-family'             => true,
				'font-size'               => true,
				'font-weight'             => true,
				'font-style'              => true,
				'font-backup'             => false,
				'subsets'                 => true,
				'custom_fonts'            => true,
				'text-align'              => true,
				'text-transform'          => false,
				'font-variant'            => false,
				'text-decoration'         => false,
				'color'                   => true,
				'preview'                 => true,
				'line-height'             => true,
				'multi'                   => array(
					'subsets' => false,
					'weight'  => false,
				),
				'word-spacing'            => false,
				'letter-spacing'          => false,
				'google'                  => true,
				'font_family_clear'       => true,
				'allow_empty_line_height' => false,
				'margin-top'              => false,
				'margin-bottom'           => false,
				'text-shadow'             => false,
			);
			$this->field = wp_parse_args( $this->field, $defaults );
			if ( isset( $this->field['color_alpha'] ) ) {
				if ( is_array( $this->field['color_alpha'] ) ) {
					$this->field['color_alpha']['color']        = $this->field['color_alpha']['color'] ?? false;
					$this->field['color_alpha']['shadow-color'] = $this->field['color_alpha']['shadow-color'] ?? false;
				} else {
					$mode                                       = $this->field['color_alpha'];
					$this->field['color_alpha']                 = array();
					$this->field['color_alpha']['color']        = $mode;
					$this->field['color_alpha']['shadow-color'] = $mode;
				}
			} else {
				$this->field['color_alpha']['color']        = false;
				$this->field['color_alpha']['shadow-color'] = false;
			}
			// Set value defaults.
			$defaults    = array(
				'font-family'       => '',
				'font-options'      => '',
				'font-backup'       => '',
				'text-align'        => '',
				'text-transform'    => '',
				'font-variant'      => '',
				'text-decoration'   => '',
				'line-height'       => '',
				'word-spacing'      => '',
				'letter-spacing'    => '',
				'subsets'           => '',
				'google'            => false,
				'font-script'       => '',
				'font-weight'       => '',
				'font-style'        => '',
				'color'             => '',
				'font-size'         => '',
				'margin-top'        => '',
				'margin-bottom'     => '',
				'shadow-color'      => '#000000',
				'shadow-horizontal' => '1',
				'shadow-vertical'   => '1',
				'shadow-blur'       => '4',
			);
			$this->value = wp_parse_args( $this->value, $defaults );
			$units       = array(
				'px',
				'em',
				'rem',
				'%',
			);
			if ( empty( $this->field['units'] ) || ! in_array( $this->field['units'], $units, true ) ) {
				$this->field['units'] = 'px';
			}
			// Get the Google array.
			$this->get_google_array();
			if ( empty( $this->field['fonts'] ) ) {
				$this->user_fonts     = false;
				$this->field['fonts'] = $this->std_fonts;
			}
			// Localize std fonts.
			$this->localize_std_fonts();
		}
		
		/**
		 * Localize font array
		 *
		 * @param array  $field Field array.
		 * @param string $value Value.
		 *
		 * @return array
		 */
		public function localize( array $field, string $value = '' ): array {
			$params = array();
			if ( true === $this->user_fonts && ! empty( $this->field['fonts'] ) ) {
				$params['std_font'] = $this->field['fonts'];
			}
			
			return $params;
		}
		
		/**
		 * Field Render Function.
		 * Takes the vars and outputs the HTML for the field in the settings
		 *
		 * @since ReduxFramework 1.0.0
		 */
		public function render() {
			// Since fonts declared is CSS (@font-face) are not rendered in the preview,
			// they can be declared in a CSS file and passed here, so they DO display in
			// font preview.  Do NOT pass style.css in your theme, as that will mess up
			// admin page styling.  It's recommended to pass a CSS file with ONLY font
			// declarations.
			// If field is set and not blank, then enqueue field.
			if ( isset( $this->field['ext-font-css'] ) && '' !== $this->field['ext-font-css'] ) {
				wp_enqueue_style( 'redux-external-fonts', $this->field['ext-font-css'], array(), $this->timestamp );
			}
			if ( empty( $this->field['units'] ) && ! empty( $this->field['default']['units'] ) ) {
				$this->field['units'] = $this->field['default']['units'];
			}
			$unit = $this->field['units'];
			echo '<div id="' . esc_attr( $this->field['id'] ) . '" class="redux-typography-container" data-id="' . esc_attr( $this->field['id'] ) . '" data-units="' . esc_attr( $unit ) . '">';
			$this->select2_config['allowClear'] = true;
			if ( isset( $this->field['select2'] ) ) {
				$this->field['select2'] = wp_parse_args( $this->field['select2'], $this->select2_config );
			} else {
				$this->field['select2'] = $this->select2_config;
			}
			$this->field['select2'] = Redux_Functions::sanitize_camel_case_array_keys( $this->field['select2'] );
			$select2_data           = Redux_Functions::create_data_string( $this->field['select2'] );
			/* Font Family */
			if ( true === $this->field['font-family'] ) {
				if ( filter_var( $this->value['google'], FILTER_VALIDATE_BOOLEAN ) ) {
					// Divide and conquer.
					$font_family = explode( ', ', $this->value['font-family'], 2 );
					// If array 0 is empty and array 1 is not.
					if ( empty( $font_family[0] ) && ! empty( $font_family[1] ) ) {
						// Make array 0 = array 1.
						$font_family[0] = $font_family[1];
						// Clear array 1.
						$font_family[1] = '';
					}
				}
				// If no fontFamily array exists, create one and set array 0
				// with font value.
				if ( ! isset( $font_family ) ) {
					$font_family    = array();
					$font_family[0] = $this->value['font-family'];
					$font_family[1] = '';
				}
				// Is selected font a Google font.
				$is_google_font = '0';
				if ( isset( $this->parent->fonts['google'][ $font_family[0] ] ) ) {
					$is_google_font = '1';
				}
				// If not a Google font, show all font families.
				if ( '1' !== $is_google_font ) {
					$font_family[0] = $this->value['font-family'];
				}
				$user_fonts = '0';
				if ( true === $this->user_fonts ) {
					$user_fonts = '1';
				}
				echo '<input
						type="hidden"
						class="redux-typography-font-family ' . esc_attr( $this->field['class'] ) . '"
						data-user-fonts="' . esc_attr( $user_fonts ) . '" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-family]"
						value="' . esc_attr( $this->value['font-family'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				echo '<input
						type="hidden"
						class="redux-typography-font-options ' . esc_attr( $this->field['class'] ) . '"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-options]"
						value="' . esc_attr( $this->value['font-options'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				echo '<input
						type="hidden"
						class="redux-typography-google-font" value="' . esc_attr( $is_google_font ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-google-font">';
				echo '<div class="select_wrapper typography-family" style="width: 220px; margin-right: 5px;">';
				$placeholder = $font_family[0] ? $font_family[0] : __( 'Font family', 'buddyboss-theme' );
				echo '<label>' . $placeholder . '</label>';
				$new_arr                = $this->field['select2'];
				$new_arr['allow-clear'] = $this->field['font_family_clear'];
				$new_data               = Redux_Functions::create_data_string( $new_arr );
				echo '<select class=" redux-typography redux-typography-family select2-container ' . esc_attr( $this->field['class'] ) . '" id="' . esc_attr( $this->field['id'] ) . '-family" data-placeholder="' . esc_attr( $placeholder ) . '" data-id="' . esc_attr( $this->field['id'] ) . '" data-value="' . esc_attr( $font_family[0] ) . '"' . esc_html( $new_data ) . '>';
				echo '</select>';
				echo '</div>';
				$google_set = false;
				if ( true === $this->field['google'] ) {
					// Set a flag, so we know to set a header style or not.
					echo '<input
							type="hidden"
							class="redux-typography-google ' . esc_attr( $this->field['class'] ) . '"
							id="' . esc_attr( $this->field['id'] ) . '-google" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[google]"
							type="text" value="' . esc_attr( $this->field['google'] ) . '"
							data-id="' . esc_attr( $this->field['id'] ) . '" />';
					$google_set = true;
				}
			}
			/* Backup Font */
			if ( true === $this->field['font-family'] && true === $this->field['google'] ) {
				if ( false === $google_set ) {
					// Set a flag, so we know to set a header style or not.
					echo '<input
							type="hidden"
							class="redux-typography-google ' . esc_attr( $this->field['class'] ) . '"
							id="' . esc_attr( $this->field['id'] ) . '-google" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[google]"
							type="text" value="' . esc_attr( $this->field['google'] ) . '"
							data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				}
				if ( true === $this->field['font-backup'] ) {
					echo '<div class="select_wrapper typography-family-backup" style="width: 220px; margin-right: 5px;">';
					echo '<label>' . esc_html__( 'Backup Font Family', 'buddyboss-theme' ) . '</label>';
					echo '<select
							data-placeholder="' . esc_html__( 'Backup Font Family', 'buddyboss-theme' ) . '"
							name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-backup]"
							class="redux-typography redux-typography-family-backup ' . esc_attr( $this->field['class'] ) . '"
							id="' . esc_attr( $this->field['id'] ) . '-family-backup"
							data-id="' . esc_attr( $this->field['id'] ) . '"
							data-value="' . esc_attr( $this->value['font-backup'] ) . '"' . esc_attr( $select2_data ) . '>';
					echo '<option data-google="false" data-details="" value=""></option>';
					foreach ( $this->field['fonts'] as $i => $family ) {
						echo '<option data-google="true" value="' . esc_attr( $i ) . '" ' . selected( $this->value['font-backup'], $i, false ) . '>' . esc_html( $family ) . '</option>';
					}
					echo '</select></div>';
				}
			}
			/* Font Style/Weight */
			if ( true === $this->field['font-style'] || true === $this->field['font-weight'] ) {
				echo '<div class="select_wrapper typography-style" original-title="' . esc_html__( 'Font style', 'buddyboss-theme' ) . '">';
				echo '<label>' . esc_html__( 'Font Weight &amp; Style', 'buddyboss-theme' ) . '</label>';
				$style = $this->value['font-weight'] . $this->value['font-style'];
				echo '<input
						type="hidden"
						class="typography-font-weight" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-weight]"
						value="' . esc_attr( $this->value['font-weight'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  /> ';
				echo '<input
						type="hidden"
						class="typography-font-style" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-style]"
						value="' . esc_attr( $this->value['font-style'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  /> ';
				$multi = ( isset( $this->field['multi']['weight'] ) && $this->field['multi']['weight'] ) ? ' multiple="multiple"' : '';
				echo '<select' . esc_html( $multi ) . '
				        data-placeholder="' . esc_html__( 'Style', 'buddyboss-theme' ) . '"
				        class="redux-typography redux-typography-style select ' . esc_attr( $this->field['class'] ) . '"
				        original-title="' . esc_html__( 'Font style', 'buddyboss-theme' ) . '"
				        id="' . esc_attr( $this->field['id'] ) . '_style" data-id="' . esc_attr( $this->field['id'] ) . '"
				        data-value="' . esc_attr( $style ) . '"' . esc_attr( $select2_data ) . '>';
				if ( empty( $this->value['subsets'] ) || empty( $this->value['font-weight'] ) ) {
					echo '<option value=""></option>';
				}
				echo '</select></div>';
			}
			/* Font Script */
			if ( true === $this->field['font-family'] && true === $this->field['subsets'] && true === $this->field['google'] ) {
				echo '<div class="select_wrapper typography-script tooltip" original-title="' . esc_html__( 'Font subsets', 'buddyboss-theme' ) . '">';
				echo '<input
						type="hidden"
						class="typography-subsets"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[subsets]"
						value="' . esc_attr( $this->value['subsets'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  /> ';
				echo '<label>' . esc_html__( 'Font Subsets', 'buddyboss-theme' ) . '</label>';
				$multi = ( isset( $this->field['multi']['subsets'] ) && $this->field['multi']['subsets'] ) ? ' multiple="multiple"' : '';
				echo '<select' . esc_html( $multi ) . '
						data-placeholder="' . esc_html__( 'Subsets', 'buddyboss-theme' ) . '"
						class="redux-typography redux-typography-subsets ' . esc_attr( $this->field['class'] ) . '"
						original-title="' . esc_html__( 'Font script', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-subsets"
						data-value="' . esc_attr( $this->value['subsets'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"' . esc_attr( $select2_data ) . '>';
				if ( empty( $this->value['subsets'] ) ) {
					echo '<option value=""></option>';
				}
				echo '</select></div>';
			}
			/* Font Align */
			if ( true === $this->field['text-align'] ) {
				echo '<div class="select_wrapper typography-align tooltip" original-title="' . esc_html__( 'Text Align', 'buddyboss-theme' ) . '">';
				echo '<label>' . esc_html__( 'Text Align', 'buddyboss-theme' ) . '</label>';
				echo '<select
						data-placeholder="' . esc_html__( 'Text Align', 'buddyboss-theme' ) . '"
						class="redux-typography redux-typography-align ' . esc_attr( $this->field['class'] ) . '"
						original-title="' . esc_html__( 'Text Align', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-align"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[text-align]"
						data-value="' . esc_attr( $this->value['text-align'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"' . esc_attr( $select2_data ) . '>';
				echo '<option value=""></option>';
				$align = array(
					esc_html__( 'inherit', 'buddyboss-theme' ),
					esc_html__( 'left', 'buddyboss-theme' ),
					esc_html__( 'right', 'buddyboss-theme' ),
					esc_html__( 'center', 'buddyboss-theme' ),
					esc_html__( 'justify', 'buddyboss-theme' ),
					esc_html__( 'initial', 'buddyboss-theme' ),
				);
				foreach ( $align as $v ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( $this->value['text-align'], $v, false ) . '>' . esc_html( ucfirst( $v ) ) . '</option>';
				}
				echo '</select></div>';
			}
			/* Text Transform */
			if ( true === $this->field['text-transform'] ) {
				echo '<div class="select_wrapper typography-transform tooltip" original-title="' . esc_html__( 'Text Transform', 'buddyboss-theme' ) . '">';
				echo '<label>' . esc_html__( 'Text Transform', 'buddyboss-theme' ) . '</label>';
				echo '<select
						data-placeholder="' . esc_html__( 'Text Transform', 'buddyboss-theme' ) . '"
						class="redux-typography redux-typography-transform ' . esc_attr( $this->field['class'] ) . '"
						original-title="' . esc_html__( 'Text Transform', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-transform"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[text-transform]"
						data-value="' . esc_attr( $this->value['text-transform'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"' . esc_attr( $select2_data ) . '>';
				echo '<option value=""></option>';
				$values = array(
					esc_html__( 'none', 'buddyboss-theme' ),
					esc_html__( 'capitalize', 'buddyboss-theme' ),
					esc_html__( 'uppercase', 'buddyboss-theme' ),
					esc_html__( 'lowercase', 'buddyboss-theme' ),
					esc_html__( 'initial', 'buddyboss-theme' ),
					esc_html__( 'inherit', 'buddyboss-theme' ),
				);
				foreach ( $values as $v ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( $this->value['text-transform'], $v, false ) . '>' . esc_html( ucfirst( $v ) ) . '</option>';
				}
				echo '</select></div>';
			}
			/* Font Variant */
			if ( true === $this->field['font-variant'] ) {
				echo '<div class="select_wrapper typography-font-variant tooltip" original-title="' . esc_html__( 'Font Variant', 'buddyboss-theme' ) . '">';
				echo '<label>' . esc_html__( 'Font Variant', 'buddyboss-theme' ) . '</label>';
				echo '<select
						data-placeholder="' . esc_html__( 'Font Variant', 'buddyboss-theme' ) . '"
						class="redux-typography redux-typography-font-variant ' . esc_attr( $this->field['class'] ) . '"
						original-title="' . esc_html__( 'Font Variant', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-font-variant"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-variant]"
						data-value="' . esc_attr( $this->value['font-variant'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"' . esc_attr( $select2_data ) . '>';
				echo '<option value=""></option>';
				$values = array(
					esc_html__( 'inherit', 'buddyboss-theme' ),
					esc_html__( 'normal', 'buddyboss-theme' ),
					esc_html__( 'small-caps', 'buddyboss-theme' ),
				);
				foreach ( $values as $v ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( $this->value['font-variant'], $v, false ) . '>' . esc_attr( ucfirst( $v ) ) . '</option>';
				}
				echo '</select></div>';
			}
			/* Text Decoration */
			if ( true === $this->field['text-decoration'] ) {
				echo '<div class="select_wrapper typography-decoration tooltip" original-title="' . esc_html__( 'Text Decoration', 'buddyboss-theme' ) . '">';
				echo '<label>' . esc_html__( 'Text Decoration', 'buddyboss-theme' ) . '</label>';
				echo '<select
						data-placeholder="' . esc_html__( 'Text Decoration', 'buddyboss-theme' ) . '"
						class="redux-typography redux-typography-decoration ' . esc_attr( $this->field['class'] ) . '"
						original-title="' . esc_html__( 'Text Decoration', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-decoration"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[text-decoration]"
						data-value="' . esc_attr( $this->value['text-decoration'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"' . esc_attr( $select2_data ) . '>';
				echo '<option value=""></option>';
				$values = array(
					esc_html__( 'none', 'buddyboss-theme' ),
					esc_html__( 'inherit', 'buddyboss-theme' ),
					esc_html__( 'underline', 'buddyboss-theme' ),
					esc_html__( 'overline', 'buddyboss-theme' ),
					esc_html__( 'line-through', 'buddyboss-theme' ),
					esc_html__( 'blink', 'buddyboss-theme' ),
				);
				foreach ( $values as $v ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( $this->value['text-decoration'], $v, false ) . '>' . esc_html( ucfirst( $v ) ) . '</option>';
				}
				echo '</select></div>';
			}
			/* Font Size */
			if ( true === $this->field['font-size'] ) {
				echo '<div class="input_wrapper font-size redux-container-typography">';
				echo '<label>' . esc_html__( 'Font Size', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input
						type="text"
						class="span2 redux-typography redux-typography-size mini typography-input ' . esc_attr( $this->field['class'] ) . '"
						title="' . esc_html__( 'Font Size', 'buddyboss-theme' ) . '"
						placeholder="' . esc_html__( 'Size', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-size"
						value="' . esc_attr( str_replace( $unit, '', $this->value['font-size'] ) ) . '"
						data-value="' . esc_attr( str_replace( $unit, '', $this->value['font-size'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input type="hidden" class="typography-font-size" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[font-size]" value="' . esc_attr( $this->value['font-size'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"/>';
				echo '</div>';
			}
			/* Line Height */
			if ( true === $this->field['line-height'] ) {
				echo '<div class="input_wrapper line-height redux-container-typography">';
				echo '<label>' . esc_html__( 'Line Height', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input
						type="text"
						class="span2 redux-typography redux-typography-height mini typography-input ' . esc_attr( $this->field['class'] ) . '"
						title="' . esc_html__( 'Line Height', 'buddyboss-theme' ) . '"
						placeholder="' . esc_html__( 'Height', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-height"
						value="' . esc_attr( str_replace( $unit, '', $this->value['line-height'] ) ) . '"
						data-allow-empty="' . esc_attr( $this->field['allow_empty_line_height'] ) . '"
						data-value="' . esc_attr( str_replace( $unit, '', $this->value['line-height'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input type="hidden" class="typography-line-height" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[line-height]" value="' . esc_attr( $this->value['line-height'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"/>';
				echo '</div>';
			}
			/* Word Spacing */
			if ( true === $this->field['word-spacing'] ) {
				echo '<div class="input_wrapper word-spacing redux-container-typography">';
				echo '<label>' . esc_html__( 'Word Spacing', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input
						type="text"
						class="span2 redux-typography redux-typography-word mini typography-input ' . esc_attr( $this->field['class'] ) . '"
						title="' . esc_html__( 'Word Spacing', 'buddyboss-theme' ) . '"
						placeholder="' . esc_html__( 'Word Spacing', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-word"
						value="' . esc_attr( str_replace( $unit, '', $this->value['word-spacing'] ) ) . '"
						data-value="' . esc_attr( str_replace( $unit, '', $this->value['word-spacing'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input type="hidden" class="typography-word-spacing" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[word-spacing] " value="' . esc_attr( $this->value['word-spacing'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"/>';
				echo '</div>';
			}
			/* Letter Spacing */
			if ( true === $this->field['letter-spacing'] ) {
				echo '<div class="input_wrapper letter-spacing redux-container-typography">';
				echo '<label>' . esc_html__( 'Letter Spacing', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input
						type="text"
						class="span2 redux-typography redux-typography-letter mini typography-input ' . esc_attr( $this->field['class'] ) . '"
						title="' . esc_html__( 'Letter Spacing', 'buddyboss-theme' ) . '"
						placeholder="' . esc_html__( 'Letter Spacing', 'buddyboss-theme' ) . '"
						id="' . esc_attr( $this->field['id'] ) . '-letter"
						value="' . esc_attr( str_replace( $unit, '', $this->value['letter-spacing'] ) ) . '"
						data-value="' . esc_attr( str_replace( $unit, '', $this->value['letter-spacing'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input
						type="hidden"
						class="typography-letter-spacing"
						name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[letter-spacing]"
						value="' . esc_attr( $this->value['letter-spacing'] ) . '"
						data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				echo '</div>';
			}
			echo '<div class="clearfix"></div>';
			// Margins.
			if ( $this->field['margin-top'] ) {
				echo '<div class="input_wrapper margin-top redux-container-typography">';
				echo '<label>' . esc_html__( 'Margin Top', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input type="text" class="span2 redux-typography redux-typography-margin-top mini typography-input ' . esc_attr( $this->field['class'] ) . '" title="' . esc_html__( 'Margin Top', 'buddyboss-theme' ) . '" placeholder="' . esc_html__( 'Top', 'buddyboss-theme' ) . '" id="' . esc_attr( $this->field['id'] ) . '-margin-top" value="' . esc_attr( str_replace( $unit, '', $this->value['margin-top'] ) ) . '" data-value="' . esc_attr( str_replace( $unit, '', $this->value['margin-top'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input type="hidden" class="typography-margin-top" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[margin-top]" value="' . esc_attr( $this->value['margin-top'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				echo '</div>';
			}
			/* Bottom Margin */
			if ( $this->field['margin-bottom'] ) {
				echo '<div class="input_wrapper margin-bottom redux-container-typography">';
				echo '<label>' . esc_html__( 'Margin Bottom', 'buddyboss-theme' ) . '</label>';
				echo '<div class="input-append">';
				echo '<input type="text" class="span2 redux-typography redux-typography-margin-bottom mini typography-input ' . esc_attr( $this->field['class'] ) . '" title="' . esc_html__( 'Margin Bottom', 'buddyboss-theme' ) . '" placeholder="' . esc_html__( 'Bottom', 'buddyboss-theme' ) . '" id="' . esc_attr( $this->field['id'] ) . '-margin-bottom" value="' . esc_attr( str_replace( $unit, '', $this->value['margin-bottom'] ) ) . '" data-value="' . esc_attr( str_replace( $unit, '', $this->value['margin-bottom'] ) ) . '">';
				echo '<span class="add-on">' . esc_html( $unit ) . '</span>';
				echo '</div>';
				echo '<input type="hidden" class="typography-margin-bottom" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[margin-bottom]" value="' . esc_attr( $this->value['margin-bottom'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"  />';
				echo '</div>';
			}
			if ( $this->field['margin-top'] || $this->field['margin-bottom'] ) {
				echo '<div class="clearfix"></div>';
			}
			/* Font Color */
			if ( true === $this->field['color'] ) {
				$default = '';
				if ( empty( $this->field['default']['color'] ) && ! empty( $this->field['color'] ) ) {
					$default = $this->value['color'];
				} elseif ( ! empty( $this->field['default']['color'] ) ) {
					$default = $this->field['default']['color'];
				}
				echo '<div class="picker-wrapper">';
				echo '<label>' . esc_html__( 'Font Color', 'buddyboss-theme' ) . '</label>';
				echo '<div id="' . esc_attr( $this->field['id'] ) . '_color_picker" class="colorSelector typography-color">';
				echo '<div style="background-color: ' . esc_attr( $this->value['color'] ) . '"></div>';
				echo '</div>';
				echo '<input ';
				echo 'data-default-color="' . esc_attr( $default ) . '"';
				echo 'class="color-picker redux-color redux-typography-color ' . esc_attr( $this->field['class'] ) . '"';
				echo 'original-title="' . esc_html__( 'Font color', 'buddyboss-theme' ) . '"';
				echo 'id="' . esc_attr( $this->field['id'] ) . '-color"';
				echo 'name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[color]"';
				echo 'type="text"';
				echo 'value="' . esc_attr( $this->value['color'] ) . '"';
				echo 'data-id="' . esc_attr( $this->field['id'] ) . '"';
				$data = array(
					'field' => $this->field,
					'index' => 'color',
				);
				echo Redux_Functions_Ex::output_alpha_data( $data );
				echo '>';
				echo '</div>';
			}
			echo '<div class="clearfix"></div>';
			/* Font Preview */
			if ( ! isset( $this->field['preview'] ) || false !== $this->field['preview'] ) {
				$g_text = $this->field['preview']['text'] ?? '1 2 3 4 5 6 7 8 9 0 A B C D E F G H I J K L M N O P Q R S T U V W X Y Z a b c d e f g h i j k l m n o p q r s t u v w x y z';
				$style  = '';
				if ( isset( $this->field['preview']['always_display'] ) ) {
					if ( true === filter_var( $this->field['preview']['always_display'], FILTER_VALIDATE_BOOLEAN ) ) {
						if ( true === $is_google_font ) {
							$this->typography_preview[ $font_family[0] ] = array(
								'font-style' => array( $this->value['font-weight'] . $this->value['font-style'] ),
								'subset'     => array( $this->value['subsets'] ),
							);
							wp_deregister_style( 'redux-typography-preview' );
							wp_dequeue_style( 'redux-typography-preview' );
							wp_enqueue_style( 'redux-typography-preview', $this->make_google_web_font_link( $this->typography_preview ), array(), $this->timestamp );
						}
						$style = 'display: block; font-family: ' . esc_attr( $this->value['font-family'] ) . '; font-weight: ' . esc_attr( $this->value['font-weight'] ) . ';';
					}
				}
				if ( isset( $this->field['preview']['font-size'] ) ) {
					$style  .= 'font-size: ' . $this->field['preview']['font-size'] . ';';
					$in_use = '1';
				} else {
					$in_use = '0';
				}
				echo '<p data-preview-size="' . esc_attr( $in_use ) . '" class="clear ' . esc_attr( $this->field['id'] ) . '_previewer typography-preview" style="' . esc_attr( $style ) . '">' . esc_html( $g_text ) . '</p>';
				if ( $this->field['text-shadow'] ) {
					/* Shadow Colour */
					echo '<div class="picker-wrapper">';
					echo '<label>' . esc_html__( 'Shadow Color', 'buddyboss-theme' ) . '</label>';
					echo '<div id="' . esc_attr( $this->field['id'] ) . '_color_picker" class="colorSelector typography-shadow-color"><div style="background-color: ' . esc_attr( $this->value['color'] ) . '"></div></div>';
					echo '<input
		                    data-default-color="' . esc_attr( $this->value['shadow-color'] ) . '"
		                    class="color-picker redux-color redux-typography-shadow-color ' . esc_attr( $this->field['class'] ) . '"
		                    original-title="' . esc_html__( 'Shadow color', 'buddyboss-theme' ) . '"
		                    id="' . esc_attr( $this->field['id'] ) . '-shadow-color"
		                    name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[shadow-color]"
		                    type="text"
		                    value="' . esc_attr( $this->value['shadow-color'] ) . '"
		                    data-alpha="' . esc_attr( $this->field['color_alpha']['shadow-color'] ) . '"
		                    data-id="' . esc_attr( $this->field['id'] ) . '"
		                  />';
					echo '</div>';
					/* Shadow Horizontal Length */
					echo '<div class="input_wrapper shadow-horizontal redux-container-typography" style="top:-60px;margin-left:20px;width:20%">';
					echo '<label>' . esc_html__( 'Horizontal', 'buddyboss-theme' ) . ': <strong>' . esc_attr( $this->value['shadow-horizontal'] ) . 'px</strong></label>';
					echo '<div
                            class="redux-typography-slider span2 redux-typography redux-typography-shadow-horizontal mini typography-input ' . esc_attr( $this->field['class'] ) . '"
                            id="' . esc_attr( $this->field['id'] ) . '"
                            data-id="' . esc_attr( $this->field['id'] ) . '-h"
                            data-min="-20"
                            data-max="20"
                            data-step="1"
                            data-rtl="' . esc_attr( is_rtl() ) . '"
                            data-label="' . esc_attr__( 'Horizontal', 'buddyboss-theme' ) . '"
                            data-default = "' . esc_attr( $this->value['shadow-horizontal'] ) . '">
                        </div>';
					echo '<input type="hidden" id="redux-slider-value-' . esc_attr( $this->field['id'] ) . '-h" class="typography-shadow-horizontal" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[shadow-horizontal]" value="' . esc_attr( $this->value['shadow-horizontal'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"  />';
					echo '</div>';
					/* Shadow Vertical Length */
					echo '<div class="input_wrapper shadow-vertical redux-container-typography" style="top:-60px;margin-left:20px;width:20%">';
					echo '<label>' . esc_html__( 'Vertical', 'buddyboss-theme' ) . ': <strong>' . esc_attr( $this->value['shadow-vertical'] ) . 'px</strong></label>';
					echo '<div
                            class="redux-typography-slider span2 redux-typography redux-typography-shadow-vertical mini typography-input ' . esc_attr( $this->field['class'] ) . '"
                            id="' . esc_attr( $this->field['id'] ) . '"
                            data-id="' . esc_attr( $this->field['id'] ) . '-v"
                            data-min="-20"
                            data-max="20"
                            data-step="1"
                            data-rtl="' . esc_attr( is_rtl() ) . '"
                            data-label="' . esc_attr__( 'Vertical', 'buddyboss-theme' ) . '"
                            data-default = "' . esc_attr( $this->value['shadow-vertical'] ) . '">
                        </div>';
					echo '<input type="hidden" id="redux-slider-value-' . esc_attr( $this->field['id'] ) . '-v" class="typography-shadow-vertical" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[shadow-vertical]" value="' . esc_attr( $this->value['shadow-vertical'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"  />';
					echo '</div>';
					/* Shadow Blur */
					echo '<div class="input_wrapper shadow-blur redux-container-typography" style="top:-60px;margin-left:20px;width:20%">';
					echo '<label>' . esc_html__( 'Blur', 'buddyboss-theme' ) . ': <strong>' . esc_attr( $this->value['shadow-blur'] ) . 'px</strong></label>';
					echo '<div
                            class="redux-typography-slider span2 redux-typography redux-typography-shadow-blur mini typography-input ' . esc_attr( $this->field['class'] ) . '"
                            id="' . esc_attr( $this->field['id'] ) . '"
                            data-id="' . esc_attr( $this->field['id'] ) . '-b"
                            data-min="0"
                            data-max="25"
                            data-step="1"
                            data-rtl="' . esc_attr( is_rtl() ) . '"
                            data-label="' . esc_attr__( 'Blur', 'buddyboss-theme' ) . '"
                            data-default = "' . esc_attr( $this->value['shadow-blur'] ) . '">
                        </div>';
					echo '<input type="hidden" id="redux-slider-value-' . esc_attr( $this->field['id'] ) . '-b" class="typography-shadow-blur" name="' . esc_attr( $this->field['name'] . $this->field['name_suffix'] ) . '[shadow-blur]" value="' . esc_attr( $this->value['shadow-blur'] ) . '" data-id="' . esc_attr( $this->field['id'] ) . '"  />';
					echo '</div>';
				}
				echo '</div>'; // end typography container.
			}
		}
		
		/**
		 * Enqueue Function.
		 * If this field requires any scripts, or CSS define this function and register/enqueue the scripts/css
		 *
		 * @since ReduxFramework 1.0.0
		 */
		function enqueue() {
			if ( ! wp_style_is( 'select2-css' ) ) {
				wp_enqueue_style( 'select2-css' );
			}
			if ( ! wp_style_is( 'wp-color-picker' ) ) {
				wp_enqueue_style( 'wp-color-picker' );
			}
			wp_enqueue_script( 'redux-webfont-js', '//' . 'ajax' . '.googleapis' . '.com/ajax/libs/webfont/1.6.26/webfont.js', array(), '1.6.26', true ); // phpcs:ignore Generic.Strings.UnnecessaryStringConcat
			if ( ! wp_script_is( 'redux-field-bb-typography-js' ) ) {
				wp_enqueue_script(
					'redux-field-bb-typography-js',
					$this->extension_url . 'field_bb_typography.js',
					array( 'jquery', 'wp-color-picker', 'select2-js', 'redux-js', 'redux-webfont-js' ),
					time(),
					true
				);
			}
			wp_localize_script(
				'redux-field-bb-typography-js',
				'redux_ajax_script',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			);
			if ( $this->parent->args['dev_mode'] ) {
				if ( ! wp_style_is( 'redux-color-picker-css' ) ) {
					wp_enqueue_style( 'redux-color-picker-css' );
				}
				if ( ! wp_style_is( 'redux-field-bb-typography-css' ) ) {
					wp_enqueue_style(
						'redux-field-bb-typography-css',
						$this->extension_url . 'field_bb_typography.css',
						array(),
						time(),
						'all'
					);
				}
			}
		}  //function
		
		/**
		 * Make_google_web_font_link Function.
		 * Creates the Google fonts link.
		 *
		 * @param array $fonts Array of google fonts.
		 *
		 * @return string
		 *
		 * @since ReduxFramework 3.0.0
		 */
		public function make_google_web_font_link( array $fonts ): string {
			$link    = '';
			$subsets = array();
			foreach ( $fonts as $family => $font ) {
				if ( ! empty( $link ) ) {
					$link .= '|'; // Append a new font to the string.
				}
				$link .= $family;
				if ( ! empty( $font['font-style'] ) || ! empty( $font['all-styles'] ) ) {
					$link .= ':';
					if ( ! empty( $font['all-styles'] ) ) {
						$link .= implode( ',', $font['all-styles'] );
					} elseif ( ! empty( $font['font-style'] ) ) {
						$link .= implode( ',', $font['font-style'] );
					}
				}
				if ( ! empty( $font['subset'] ) || ! empty( $font['all-subsets'] ) ) {
					if ( ! empty( $font['all-subsets'] ) ) {
						foreach ( $font['all-subsets'] as $subset ) {
							if ( ! in_array( $subset, $subsets, true ) ) {
								array_push( $subsets, $subset );
							}
						}
					} elseif ( ! empty( $font['subset'] ) ) {
						foreach ( $font['subset'] as $subset ) {
							if ( ! in_array( $subset, $subsets, true ) ) {
								array_push( $subsets, $subset );
							}
						}
					}
				}
			}
			if ( ! empty( $subsets ) ) {
				$link .= '&subset=' . implode( ',', $subsets );
			}
			$link .= '&display=' . $this->parent->args['font_display'];
			
			return 'https://fonts.googleapis.com/css?family=' . $link;
		}
		
		/**
		 * Make_google_web_font_string Function.
		 * Creates the Google fonts link.
		 *
		 * @param array $fonts Array of Google fonts.
		 *
		 * @return string
		 *
		 * @since ReduxFramework 3.1.8
		 */
		public function make_google_web_font_string( array $fonts ): string {
			$link    = '';
			$subsets = array();
			foreach ( $fonts as $family => $font ) {
				if ( ! empty( $link ) ) {
					$link .= "', '"; // Append a new font to the string.
				}
				$link .= $family;
				if ( ! empty( $font['font-style'] ) || ! empty( $font['all-styles'] ) ) {
					$link .= ':';
					if ( ! empty( $font['all-styles'] ) ) {
						$link .= implode( ',', $font['all-styles'] );
					} elseif ( ! empty( $font['font-style'] ) ) {
						$link .= implode( ',', $font['font-style'] );
					}
				}
				if ( ! empty( $font['subset'] ) || ! empty( $font['all-subsets'] ) ) {
					if ( ! empty( $font['all-subsets'] ) ) {
						foreach ( $font['all-subsets'] as $subset ) {
							if ( ! in_array( $subset, $subsets, true ) && ! is_numeric( $subset ) ) {
								array_push( $subsets, $subset );
							}
						}
					} elseif ( ! empty( $font['subset'] ) ) {
						foreach ( $font['subset'] as $subset ) {
							if ( ! in_array( $subset, $subsets, true ) && ! is_numeric( $subset ) ) {
								array_push( $subsets, $subset );
							}
						}
					}
				}
			}
			if ( ! empty( $subsets ) ) {
				$link .= '&subset=' . implode( ',', $subsets );
			}
			
			return "'" . $link . "'";
		}
		
		/**
		 * Compiles field CSS for output.
		 *
		 * @param array $data Array of data to process.
		 *
		 * @return string
		 */
		public function css_style( $data ): string {
			$style = '';
			$font  = $data;
			// Shim out old arg to new.
			if ( isset( $this->field['all_styles'] ) && ! empty( $this->field['all_styles'] ) ) {
				$this->field['all-styles'] = $this->field['all_styles'];
				unset( $this->field['all_styles'] );
			}
			// Check for font-backup.  If it's set, stick it on a variabhle for
			// later use.
			if ( ! empty( $font['font-family'] ) && ! empty( $font['font-backup'] ) ) {
				$font['font-family'] = str_replace( ', ' . $font['font-backup'], '', $font['font-family'] );
				$font_backup         = ',' . $font['font-backup'];
			}
			$font_value_set = false;
			if ( ! empty( $font ) ) {
				foreach ( $font as $key => $value ) {
					if ( ! empty( $value ) && in_array( $key, array( 'font-family', 'font-weight' ), true ) ) {
						$font_value_set = true;
					}
				}
			}
			if ( ! empty( $font ) ) {
				foreach ( $font as $key => $value ) {
					if ( 'font-options' === $key ) {
						continue;
					}
					// Check for font-family key.
					if ( 'font-family' === $key ) {
						// Enclose font family in quotes if spaces are in the
						// name.  This is necessary because if there are numerics
						// in the font name, they will not render properly.
						// Google should know better.
						if ( strpos( $value, ' ' ) && ! strpos( $value, ',' ) ) {
							$value = '"' . $value . '"';
						}
						// Ensure fontBackup isn't empty. We already option
						// checked this earlier.  No need to do it again.
						if ( ! empty( $font_backup ) ) {
							// Apply the backup font to the font-family element
							// via the saved variable.  We do this here, so it
							// doesn't get appended to the Google stuff below.
							$value .= $font_backup;
						}
					}
					if ( empty( $value ) && in_array(
						$key,
						array(
							'font-weight',
							'font-style',
						),
						true
					) && true === $font_value_set ) {
						$value = 'normal';
					}
					if ( 'font-weight' === $key && false === $this->field['font-weight'] ) {
						continue;
					}
					if ( 'font-style' === $key && false === $this->field['font-style'] ) {
						continue;
					}
					if ( 'font-weight' === $key && in_array( substr( $value, 0, 3 ), array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ), true ) ) {
						$value = substr( $value, 0, 3 );
					}
					if ( 'google' === $key || 'subsets' === $key || 'font-backup' === $key || empty( $value ) ) {
						continue;
					}
					if ( isset( $data['key'] ) ) {
						return $data;
					}
					$continue = false;
					if ( 'shadow-horizontal' === $key || 'shadow-vertical' === $key || 'shadow-blur' === $key ) {
						$continue = true;
					}
					if ( 'shadow-color' === $key ) {
						if ( $this->field['text-shadow'] ) {
							$key   = 'text-shadow';
							$value = $data['shadow-horizontal'] . 'px ' . $data['shadow-vertical'] . 'px ' . $data['shadow-blur'] . 'px ' . $data['shadow-color'];
						} else {
							$continue = true;
						}
					}
					if ( $continue ) {
						continue;
					}
					$style .= $key . ':' . $value . ';';
				}
				$style .= 'font-display:' . $this->parent->args['font_display'] . ';';
			}
			
			return $style;
		}
		
		/**
		 * CSS Output to send to the page.
		 *
		 * @param string|null|array $style CSS styles.
		 */
		public function output( $style = '' ) {
			$font = $this->value;
			if ( '' !== $style ) {
				if ( ! empty( $this->field['output'] ) && ! is_array( $this->field['output'] ) ) {
					$this->field['output'] = array( $this->field['output'] );
				}
				if ( ! empty( $this->field['output'] ) && is_array( $this->field['output'] ) ) {
					$keys                    = implode( ',', $this->field['output'] );
					$this->parent->outputCSS .= $keys . '{' . $style . '}';
				}
				if ( ! empty( $field['compiler'] ) && ! is_array( $field['compiler'] ) ) {
					$field['compiler'] = array( $field['compiler'] );
				}
				if ( ! empty( $this->field['compiler'] ) && is_array( $this->field['compiler'] ) ) {
					$keys                      = implode( ',', $this->field['compiler'] );
					$this->parent->compilerCSS .= $keys . '{' . $style . '}';
				}
			}
			$this->set_google_fonts( $font );
		}
		
		/**
		 * Set global Google font data for global pointer.
		 *
		 * @param array $font Array of font data.
		 */
		private function set_google_fonts( array $font ) {
			// Google only stuff!
			if ( ! empty( $font['font-family'] ) && ! empty( $this->field['google'] ) && filter_var( $this->field['google'], FILTER_VALIDATE_BOOLEAN ) ) {
				// Added standard font matching check to avoid output to Google fonts call - kp
				// If no custom font array was supplied, then load it with default
				// standard fonts.
				if ( empty( $this->field['fonts'] ) ) {
					$this->field['fonts'] = $this->std_fonts;
				}
				// Ensure the fonts array is NOT empty.
				if ( ! empty( $this->field['fonts'] ) ) {
					// Make the font keys in the array lowercase, for case-insensitive matching.
					$lc_fonts = array_change_key_case( $this->field['fonts'] );
					// Rebuild font array with all keys stripped of spaces.
					$arr = array();
					foreach ( $lc_fonts as $key => $value ) {
						$key         = str_replace( ', ', ',', $key );
						$arr[ $key ] = $value;
					}
					$lc_fonts = array_change_key_case( $this->field['custom_fonts'] );
					foreach ( $lc_fonts as $group => $font_arr ) {
						foreach ( $font_arr as $key => $value ) {
							$arr[ Redux_Core::strtolower( $key ) ] = $key;
						}
					}
					$lc_fonts = $arr;
					unset( $arr );
					// lowercase chosen font for matching purposes.
					$lc_font = Redux_Core::strtolower( $font['font-family'] );
					// Remove spaces after commas in chosen font for mathcing purposes.
					$lc_font = str_replace( ', ', ',', $lc_font );
					// If the lower cased passed font-family is NOT found in the standard font array
					// Then it's a Google font, so process it for output.
					if ( ! array_key_exists( $lc_font, $lc_fonts ) ) {
						$family = $font['font-family'];
						// TODO: This method doesn't respect spaces after commas, hence the reason
						// Strip out spaces in font names and replace with with plus signs
						// for the std_font array keys having no spaces after commas.  This could be
						// fixed with RegEx in the future.
						$font['font-family'] = str_replace( ' ', '+', $font['font-family'] );
						// Push data to parent typography variable.
						if ( empty( $this->parent->typography[ $font['font-family'] ] ) ) {
							$this->parent->typography[ $font['font-family'] ] = array();
						}
						if ( isset( $this->field['all-styles'] ) || isset( $this->field['all-subsets'] ) ) {
							if ( ! isset( $font['font-options'] ) || empty( $font['font-options'] ) ) {
								$this->get_google_array();
								if ( isset( $this->parent->google_array ) && ! empty( $this->parent->google_array ) && isset( $this->parent->google_array[ $family ] ) ) {
									$font['font-options'] = $this->parent->google_array[ $family ];
								}
							} else {
								$font['font-options'] = json_decode( $font['font-options'], true );
							}
						}
						if ( isset( $font['font-options'] ) && ! empty( $font['font-options'] ) && isset( $this->field['all-styles'] ) && filter_var( $this->field['all-styles'], FILTER_VALIDATE_BOOLEAN ) ) {
							if ( isset( $font['font-options'] ) && ! empty( $font['font-options']['variants'] ) ) {
								if ( ! isset( $this->parent->typography[ $font['font-family'] ]['all-styles'] ) || empty( $this->parent->typography[ $font['font-family'] ]['all-styles'] ) ) {
									$this->parent->typography[ $font['font-family'] ]['all-styles'] = array();
									foreach ( $font['font-options']['variants'] as $variant ) {
										$this->parent->typography[ $font['font-family'] ]['all-styles'][] = $variant['id'];
									}
								}
							}
						}
						if ( isset( $font['font-options'] ) && ! empty( $font['font-options'] ) && isset( $this->field['all-subsets'] ) && $this->field['all-styles'] ) {
							if ( isset( $font['font-options'] ) && ! empty( $font['font-options']['subsets'] ) ) {
								if ( ! isset( $this->parent->typography[ $font['font-family'] ]['all-subsets'] ) || empty( $this->parent->typography[ $font['font-family'] ]['all-subsets'] ) ) {
									$this->parent->typography[ $font['font-family'] ]['all-subsets'] = array();
									foreach ( $font['font-options']['subsets'] as $variant ) {
										$this->parent->typography[ $font['font-family'] ]['all-subsets'][] = $variant['id'];
									}
								}
							}
						}
						if ( ! empty( $font['font-weight'] ) ) {
							if ( empty( $this->parent->typography[ $font['font-family'] ]['font-weight'] ) || ! in_array( $font['font-weight'], $this->parent->typography[ $font['font-family'] ]['font-weight'], true ) ) {
								$style = $font['font-weight'];
							}
							if ( ! empty( $font['font-style'] ) ) {
								$style .= $font['font-style'];
							}
							if ( empty( $this->parent->typography[ $font['font-family'] ]['font-style'] ) || ! in_array( $style, $this->parent->typography[ $font['font-family'] ]['font-style'], true ) ) {
								$this->parent->typography[ $font['font-family'] ]['font-style'][] = $style;
							}
						}
						if ( ! empty( $font['subsets'] ) ) {
							if ( empty( $this->parent->typography[ $font['font-family'] ]['subset'] ) || ! in_array( $font['subsets'], $this->parent->typography[ $font['font-family'] ]['subset'], true ) ) {
								$this->parent->typography[ $font['font-family'] ]['subset'][] = $font['subsets'];
							}
						}
					}
				}
			}
		}
		
		/**
		 * Localize standard, custom and typekit fonts.
		 */
		private function localize_std_fonts() {
			if ( false === $this->user_fonts ) {
				if ( isset( $this->parent->fonts['std'] ) && ! empty( $this->parent->fonts['std'] ) ) {
					return;
				}
				$this->parent->font_groups['std'] = array(
					'text'     => esc_html__( 'Standard Fonts', 'buddyboss-theme' ),
					'children' => array(),
				);
				foreach ( $this->field['fonts'] as $font => $extra ) {
					$this->parent->font_groups['std']['children'][] = array(
						'id'          => $font,
						'text'        => $font,
						'data-google' => 'false',
					);
				}
			}
			if ( false !== $this->field['custom_fonts'] ) {
				$this->field['custom_fonts'] = apply_filters( "redux/{$this->parent->args['opt_name']}/field/bb_typography/custom_fonts", array() );
				if ( ! empty( $this->field['custom_fonts'] ) ) {
					foreach ( $this->field['custom_fonts'] as $group => $fonts ) {
						$this->parent->font_groups['customfonts'][ strtolower( str_replace( ' ', '', $group ) ) ] = array(
							'text'     => $group,
							'children' => array(),
						);
						foreach ( $fonts as $family => $v ) {
							$this->parent->font_groups['customfonts'][ strtolower( str_replace( ' ', '', $group ) ) ]['children'][] = array(
								'id'          => $family,
								'text'        => $family,
								'variants'    => ! empty( $v['variants'] ) ? $v['variants'] : false,
								'data-google' => 'false',
							);
						}
					}
				}
			}
		}
		
		/**
		 *   Construct the Google array from the stored JSON/HTML
		 */
		private function get_google_array() {
			if ( ( isset( $this->parent->fonts['google'] ) && ! empty( $this->parent->fonts['google'] ) ) || isset( $this->parent->fonts['google'] ) && false === $this->parent->fonts['google'] ) {
				return;
			}
			$gFile = dirname( __FILE__ ) . '/googlefonts.php';
			// Weekly update
			if ( isset( $this->parent->args['google_update_weekly'] ) && $this->parent->args['google_update_weekly'] && ! empty( $this->parent->args['google_api_key'] ) ) {
				if ( file_exists( $gFile ) ) {
					// Keep the fonts updated weekly
					$weekback     = strtotime( date( 'jS F Y', time() + ( 60 * 60 * 24 * - 7 ) ) );
					$last_updated = filemtime( $gFile );
					if ( $last_updated < $weekback ) {
						unlink( $gFile );
					}
				}
			}
			if ( ! file_exists( $gFile ) ) {
				$result = @wp_remote_get( apply_filters( 'redux-google-fonts-api-url', 'https://www.googleapis.com/webfonts/v1/webfonts?key=' ) . $this->parent->args['google_api_key'], array( 'sslverify' => false ) );
				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$result = json_decode( $result['body'] );
					foreach ( $result->items as $font ) {
						$this->parent->googleArray[ $font->family ] = array(
							'variants' => $this->get_variants( $font->variants ),
							'subsets'  => $this->get_subsets( $font->subsets ),
						);
					}
					if ( ! empty( $this->parent->googleArray ) ) {
						$this->parent->filesystem->execute( 'put_contents', $gFile, array( 'content' => "<?php return json_decode( '" . json_encode( $this->parent->googleArray ) . "', true );" ) );
					}
				}
			}
			if ( ! file_exists( $gFile ) ) {
				$this->parent->fonts['google'] = false;
				
				return;
			}
			if ( ! isset( $this->parent->fonts['google'] ) || empty( $this->parent->fonts['google'] ) ) {
				$fonts = include $gFile;
				if ( $fonts === true ) {
					$this->parent->fonts['google'] = false;
					
					return;
				}
				if ( isset( $fonts ) && ! empty( $fonts ) && is_array( $fonts ) && $fonts != false ) {
					$this->parent->fonts['google'] = $fonts;
					$this->parent->googleArray     = $fonts;
					// optgroup
					$this->parent->font_groups['google'] = array(
						'text'     => __( 'Google Webfonts', 'buddyboss-theme' ),
						'children' => array(),
					);
					// options
					foreach ( $this->parent->fonts['google'] as $font => $extra ) {
						$this->parent->font_groups['google']['children'][] = array(
							'id'          => $font,
							'text'        => $font,
							'data-google' => 'true',
						);
					}
				}
			}
		}
		
		/**
		 * Clean up the Google Webfonts subsets to be human-readable
		 *
		 * @param array $var Font subset array.
		 *
		 * @return array
		 *
		 * @since ReduxFramework 0.2.0
		 */
		private function get_subsets( array $var ): array {
			$result = array();
			foreach ( $var as $v ) {
				if ( strpos( $v, '-ext' ) ) {
					$name = ucfirst( str_replace( '-ext', ' Extended', $v ) );
				} else {
					$name = ucfirst( $v );
				}
				array_push(
					$result,
					array(
						'id'   => $v,
						'name' => $name,
					)
				);
			}
			
			return array_filter( $result );
		}
		
		/**
		 * Clean up the Google Webfonts variants to be human-readable
		 *
		 * @param array $var Font variant array.
		 *
		 * @return array
		 *
		 * @since ReduxFramework 0.2.0
		 */
		private function get_variants( array $var ): array {
			$result = array();
			foreach ( $var as $v ) {
				$name = '';
				$first_char = (string) $v[0];
				if ( '1' === $first_char ) {
					$name = 'Ultra-Light 100';
				} elseif ( '2' === $first_char ) {
					$name = 'Light 200';
				} elseif ( '3' === $first_char ) {
					$name = 'Book 300';
				} elseif ( '4' === $first_char || 'r' === $first_char ) {
					$name = 'Normal 400';
				} elseif ( '5' === $first_char ) {
					$name = 'Medium 500';
				} elseif ( '6' === $first_char ) {
					$name = 'Semi-Bold 600';
				} elseif ( '7' === $first_char ) {
					$name = 'Bold 700';
				} elseif ( '8' === $first_char ) {
					$name = 'Extra-Bold 800';
				} elseif ( '9' === $first_char ) {
					$name = 'Ultra-Bold 900';
				}

				if ( strpos( $v, 'italic' ) || 'italic' === $v ) {
					$name .= ' Italic';
					$name  = trim( $name );
					if ( 'italic' === $v ) {
						$v = '400italic';
					}
					$result[] = array(
						'id'   => $v,
						'name' => $name,
					);
				} else {
					$result[] = array(
						'id'   => $v,
						'name' => $name,
					);
				}
			}

			return array_filter( $result );
		}

		/**
		 * Enable output_variables to be generated.
		 *
		 * @return void
		 * @since       4.0.3
		 */
		public function output_variables() {
			// No code needed, just defining the method is enough.
		}
	}
}           //class exists
