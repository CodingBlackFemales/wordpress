<?php

/**
 * Settings
 *
 * @package Buddyboss_Menu_Icons
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */

/**
 * BuddyBoss Menu Icons Settings module
 */
final class Menu_Icons_Settings {

	const UPDATE_KEY = 'menu-icons-settings-update';

	const RESET_KEY = 'menu-icons-settings-reset';

	const TRANSIENT_KEY = 'menu_icons_message';

	/**
	 * Default setting values
	 *
	 * @since Menu Icons 0.3.0
	 * @var   array
	 * @access protected
	 */
	protected static $defaults = array(
		'global' => array(
			'icon_types' => array( 'buddyboss' ),
		),
	);

	/**
	 * Setting values
	 *
	 * @since Menu Icons 0.3.0
	 * @var   array
	 * @access protected
	 */
	protected static $settings = array();

	/**
	 * Script dependencies
	 *
	 * @since Menu Icons 0.9.0
	 * @access protected
	 * @var    array
	 */
	protected static $script_deps = array( 'jquery' );

	/**
	 * Settings init
	 *
	 * @since 0.3.0
	 */
	public static function init() {
		/**
		 * Allow themes/plugins to override the default settings
		 *
		 * @since 0.9.0
		 *
		 * @param array $default_settings Default settings.
		 */
		self::$defaults = apply_filters( 'menu_icons_settings_defaults', self::$defaults );

		self::$settings = get_option( 'menu-icons', self::$defaults );

		foreach ( self::$settings as $key => &$value ) {
			if ( 'global' === $key ) {
				// Remove unregistered icon types.
				$value['icon_types'] = array_values(
					array_intersect(
						array_keys( Buddyboss_Menu_Icons::get( 'types' ) ),
						array_filter( (array) $value['icon_types'] )
					)
				);
			} else {
				// Backward-compatibility.
				if ( isset( $value['width'] ) && ! isset( $value['svg_width'] ) ) {
					$value['svg_width'] = $value['width'];
				}

				unset( $value['width'] );
			}
		}

		unset( $value );

		/**
		 * Allow themes/plugins to override the settings
		 *
		 * @since 0.9.0
		 *
		 * @param array $settings BuddyBoss Menu Icons settings.
		 */
		self::$settings = apply_filters( 'menu_icons_settings', self::$settings );

		if ( ! empty( self::$settings['global']['icon_types'] ) ) {
			require_once Buddyboss_Menu_Icons::get( 'dir' ) . 'includes/picker.php';
			Menu_Icons_Picker::init();
			self::$script_deps[] = 'icon-picker';
		}

		add_action( 'load-nav-menus.php', array( __CLASS__, '_load_nav_menus' ), 1 );
		add_action( 'wp_ajax_menu_icons_update_settings', array( __CLASS__, '_ajax_menu_icons_update_settings' ) );

		// add footer in admin section
		add_action( 'in_admin_footer', array( 'Menu_Icons_Settings', 'in_admin_footer' ) );
	}

	public static function in_admin_footer() {
		global $pagenow;
		if ( 'nav-menus.php' === $pagenow ) {
			foreach ( self::_get_fields() as $section_index => $section ) {
				if ( 'global' === $section_index ) {
					?>
						<div class="hidden hide buddyboss-menu-icon-tabs">
							<div class="menu-icon-tabs">
								<button type="button" data-id="menu-item-buddyboss" class="active"><?php esc_html_e( 'Icons', 'buddyboss-theme' ); ?></button>
								<button type="button" data-id="menu-item-image"><?php esc_html_e( 'Custom', 'buddyboss-theme' ); ?></button>
								<button type="button" data-id="menu-item-manage"><?php esc_html_e( 'Manage', 'buddyboss-theme' ); ?></button>
							</div>
						</div>
						<div class="hidden hide buddyboss-menu-icon-settings">
							<div class="buddyboss-menu-icon-panel">
								<h3><?php esc_html_e( 'Select Icon Packs', 'buddyboss-theme' ); ?></h3>
								<div class="buddyboss-menu-icon-settings-options">
									<?php
									foreach ( $section['fields'] as $field ) {
										$field->render();
									}
									?>
								</div>
							</div>
						</div>
						<div class="hidden hide buddyboss-menu-icon-buttons"> <?php self::submit_button(); ?> </div>
					<?php
				}
			}
		}
	}

	/**
	 * Check if menu icons is disabled for a menu
	 *
	 * @since 0.8.0
	 *
	 * @param int $menu_id Menu ID. Defaults to current menu being edited.
	 *
	 * @return bool
	 */
	public static function is_menu_icons_disabled_for_menu( $menu_id = 0 ) {
		if ( empty( $menu_id ) ) {
			$menu_id = self::get_current_menu_id();
		}

		// When we're creating a new menu or the recently edited menu
		// could not be found.
		if ( empty( $menu_id ) ) {
			return true;
		}

		$menu_settings = self::get_menu_settings( $menu_id );
		$is_disabled   = ! empty( $menu_settings['disabled'] );

		return $is_disabled;
	}

	/**
	 * Get ID of menu being edited
	 *
	 * @since Menu Icons 0.7.0
	 * @since Menu Icons 0.8.0 Get the recently edited menu from user option.
	 *
	 * @return int
	 */
	public static function get_current_menu_id() {
		global $nav_menu_selected_id;

		if ( ! empty( $nav_menu_selected_id ) ) {
			return $nav_menu_selected_id;
		}

		if ( is_admin() && isset( $_REQUEST['menu'] ) ) {
			$menu_id = absint( $_REQUEST['menu'] );
		} else {
			$menu_id = absint( get_user_option( 'nav_menu_recently_edited' ) );
		}

		return $menu_id;
	}

	/**
	 * Get menu settings
	 *
	 * @since Menu Icons 0.3.0
	 *
	 * @param  int $menu_id
	 *
	 * @return array
	 */
	public static function get_menu_settings( $menu_id ) {
		$menu_settings = self::get( sprintf( 'menu_%d', $menu_id ) );
		$menu_settings = apply_filters( 'menu_icons_menu_settings', $menu_settings, $menu_id );

		if ( ! is_array( $menu_settings ) ) {
			$menu_settings = array();
		}

		return $menu_settings;
	}

	/**
	 * Get setting value
	 *
	 * @since Menu Icons 0.3.0
	 * @return mixed
	 */
	public static function get() {
		$args = func_get_args();

		return kucrut_get_array_value_deep( self::$settings, $args );
	}

	/**
	 * Prepare wp-admin/nav-menus.php page
	 *
	 * @since Menu Icons  0.3.0
	 * @wp_hook action load-nav-menus.php
	 */
	public static function _load_nav_menus() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_assets' ), 99 );


		self::_maybe_update_settings();
		add_action( 'admin_notices', array( __CLASS__, '_admin_notices' ) );
		/**
		 * Allow settings meta box to be disabled.
		 *
		 * @since 0.4.0
		 *
		 * @param bool $disabled Defaults to FALSE.
		 */
		$settings_disabled = apply_filters( 'menu_icons_disable_settings', true );
		if ( true === $settings_disabled ) {
			return;
		}


		self::_add_settings_meta_box();
	}

	/**
	 * Update settings
	 *
	 * @since 0.3.0
	 */
	public static function _maybe_update_settings() {
		if ( ! empty( $_POST['menu-icons']['settings'] ) ) {
			check_admin_referer( self::UPDATE_KEY, self::UPDATE_KEY );

			$redirect_url = self::_update_settings( $_POST['menu-icons']['settings'] ); // Input var okay.
			wp_redirect( $redirect_url );
		} elseif ( ! empty( $_REQUEST[ self::RESET_KEY ] ) ) {
			check_admin_referer( self::RESET_KEY, self::RESET_KEY );
			wp_redirect( self::_reset_settings() );
		}

		// update menu icon settings based on header menu selection.
		if ( isset( $_POST['nav-menu-locations'] ) && ! empty( $_POST['nav-menu-locations'] ) ) {
			$current_menu = ! empty( $_POST['menu'] ) ? sanitize_text_field( wp_unslash( $_POST['menu'] ) ) : '';
			$header_menus = array();
			if ( isset( $_POST['menu-locations']['header-menu'] ) ) {
				$header_menus[] = sanitize_text_field( wp_unslash( $_POST['menu-locations']['header-menu'] ) );
			}
			if ( isset( $_POST['menu-locations']['header-menu-logout'] ) ) {
				$header_menus[] = sanitize_text_field( wp_unslash( $_POST['menu-locations']['header-menu-logout'] ) );
			}
			if ( ! in_array( $current_menu, $header_menus ) ) {
				self::update_menu_icon_settings( $current_menu );
			}
		}
	}

	/**
	 * Update menu icon settings based on header menu selection.
	 *
	 * @since 2.0.0
	 *
	 * @param int $current_menu term id.
	 *
	 * @return void
	 */
	public static function update_menu_icon_settings( $current_menu ) {
		$args = array(
			'post_type'   => 'nav_menu_item',
			'post_status' => 'publish',
			'tax_query'   => array(
				array(
					'taxonomy' => 'nav_menu',
					'field'    => 'id',
					'terms'    => $current_menu,
				),
			),
		);

		$r              = wp_parse_args( null, $args );
		$get_posts      = new \WP_Query();
		$nav_menu_items = $get_posts->query( $r );

		if ( isset( $nav_menu_items ) && ! empty( $nav_menu_items ) ) {
			$nav_menu_items = wp_list_pluck( $nav_menu_items, 'ID' );
			foreach ( $nav_menu_items as $single ) {
				$menu_icons = get_post_meta( $single, 'menu-icons', true );
				if ( ! empty( $menu_icons['hide_label'] ) ) {
					$menu_icons['hide_label'] = '';
				}
				if ( isset( $menu_icons['position'] ) && 'before' !== $menu_icons['position'] ) {
					$menu_icons['position'] = 'before';
				}
				update_post_meta( $single, 'menu-icons', $menu_icons );
			}
		}
	}

	/**
	 * Update settings
	 *
	 * @since Menu Icons 0.7.0
	 * @access protected
	 *
	 * @param  array $values Settings values.
	 *
	 * @return string    Redirect URL.
	 */
	protected static function _update_settings( $values ) {
		// include image and svg so that tabs working.
		if ( isset( $values['global'] ) && isset( $values['global']['icon_types'] ) ) {
			array_push( $values['global']['icon_types'], 'image', 'manage' );
		}

		update_option(
			'menu-icons',
			wp_parse_args(
				kucrut_validate( $values ),
				self::$settings
			)
		);
		set_transient( self::TRANSIENT_KEY, 'updated', 30 );

		$redirect_url = remove_query_arg(
			array( 'menu-icons-reset' ),
			wp_get_referer()
		);

		return $redirect_url;
	}

	/**
	 * Reset settings
	 *
	 * @since Menu Icons 0.7.0
	 * @access protected
	 * @return string    Redirect URL.
	 */
	protected static function _reset_settings() {
		delete_option( 'menu-icons' );
		// update with default data.
		$reset_data['global']['icon_types'] = array( 'buddyboss', 'image', 'manage' );
		update_option(
			'menu-icons',
			wp_parse_args(
				kucrut_validate( $reset_data ),
				self::$settings
			)
		);
		set_transient( self::TRANSIENT_KEY, 'reset', 30 );

		$redirect_url = remove_query_arg(
			array( self::RESET_KEY, 'menu-icons-updated' ),
			wp_get_referer()
		);

		return $redirect_url;
	}

	/**
	 * Settings meta box
	 *
	 * @since Menu Icons 0.3.0
	 * @access private
	 */
	private static function _add_settings_meta_box() {
		add_meta_box(
			'menu-icons-settings',
			__( 'Menu Icons', 'buddyboss-theme' ),
			array( __CLASS__, '_meta_box' ),
			'nav-menus',
			'side',
			'low',
			array()
		);
	}

	/**
	 * Update settings via ajax
	 *
	 * @since Menu Icons  0.7.0
	 * @wp_hook action wp_ajax_menu_icons_update_settings
	 */
	public static function _ajax_menu_icons_update_settings() {
		check_ajax_referer( self::UPDATE_KEY, self::UPDATE_KEY );

		if ( empty( $_POST['menu-icons']['settings'] ) ) {
			wp_send_json_error();
		}


		$redirect_url = self::_update_settings( $_POST['menu-icons']['settings'] ); // Input var okay.
		wp_send_json_success( array( 'redirectUrl' => $redirect_url ) );
	}

	/**
	 * Print admin notices
	 *
	 * @since Menu Icons  0.3.0
	 * @wp_hook action admin_notices
	 */
	public static function _admin_notices() {
		$messages = array(
			'updated' => __( '<strong>BuddyBoss Menu Icons Settings</strong> have been successfully updated.', 'buddyboss-theme' ),
			'reset'   => __( '<strong>BuddyBoss Menu Icons Settings</strong> have been successfully reset.', 'buddyboss-theme' ),
		);

		$message_type = get_transient( self::TRANSIENT_KEY );

		if ( ! empty( $message_type ) && ! empty( $messages[ $message_type ] ) ) {
			printf(
				'<div class="updated notice is-dismissible"><p>%s</p></div>',
				wp_kses( $messages[ $message_type ], array( 'strong' => true ) )
			);
		}

		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Settings meta box
	 *
	 * @since 0.3.0
	 */
	public static function _meta_box() {
		?>
        <div class="taxonomydiv">
            <ul id="menu-icons-settings-tabs" class="taxonomy-tabs add-menu-item-tabs hide-if-no-js">
				<?php foreach ( self::get_fields() as $section ) : ?>
					<?php
					printf(
						'<li><a href="#" title="%s" class="mi-settings-nav-tab" data-type="menu-icons-settings-%s">%s</a></li>',
						esc_attr( $section['description'] ),
						esc_attr( $section['id'] ),
						esc_html( $section['title'] )
					);
					?>
				<?php endforeach; ?>
				<?php
				printf(
					'<li><a href="#" class="mi-settings-nav-tab" data-type="menu-icons-settings-extensions">%s</a></li>',
					esc_html__( 'Extensions', 'buddyboss-theme' )
				);
				?>
            </ul>
			<?php foreach ( self::_get_fields() as $section_index => $section ) : ?>
                <div id="menu-icons-settings-<?php echo esc_attr( $section['id'] ) ?>"
                     class="tabs-panel _<?php echo esc_attr( $section_index ) ?>">
                    <h4 class="hide-if-js"><?php echo esc_html( $section['title'] ) ?></h4>
					<?php foreach ( $section['fields'] as $field ) : ?>
                        <div class="_field">
							<?php
							printf(
								'<label for="%s" class="_main">%s</label>',
								esc_attr( $field->id ),
								esc_html( $field->label )
							);
							?>
							<?php $field->render() ?>
                        </div>
					<?php endforeach; ?>
                </div>
			<?php endforeach; ?>
            <div id="menu-icons-settings-extensions" class="tabs-panel _extensions">
                <h4 class="hide-if-js"><?php echo esc_html__( 'Extensions', 'buddyboss-theme' ) ?></h4>
                <ul>
                    <li><a target="_blank" href="http://wordpress.org/plugins/menu-icons-icomoon/">IcoMoon</a></li>
                </ul>
            </div>
        </div>
		<?php

		self::submit_button();
	}

	public static function submit_button() {
		?>
        <p class="submitbox button-controls">
			<?php wp_nonce_field( self::UPDATE_KEY, self::UPDATE_KEY ) ?>
            <span class="list-controls">
				<?php
				printf(
					'<a href="%s" title="%s" class="select-all submitdelete">%s</a>',
					esc_url(
						wp_nonce_url(
							admin_url( '/nav-menus.php' ),
							self::RESET_KEY,
							self::RESET_KEY
						)
					),
					esc_attr__( 'Discard all changes and reset to default state', 'buddyboss-theme' ),
					esc_html__( 'Reset Icon Packs', 'buddyboss-theme' )
				);
				?>
			</span>

            <span class="add-to-menu">
			<span class="spinner"></span>
				<?php
				submit_button(
					esc_html__( 'Load Icon Packs', 'buddyboss-theme' ),
					'primary menu-icons-settings-save',
					'menu-icons-settings-save',
					false
				);
				?>
			</span>
        </p>
		<?php
	}

	/**
	 * Get settings sections
	 *
	 * @since Menu Icons 0.3.0
	 * @uses   apply_filters() Calls 'menu_icons_settings_sections'.
	 * @return array
	 */
	public static function get_fields() {
		$menu_id    = self::get_current_menu_id();
		$icon_types = wp_list_pluck( Buddyboss_Menu_Icons::get( 'types' ), 'name' );

		if ( isset( $icon_types['image'] ) ) {
			unset( $icon_types['image'] );
		}
		if ( isset( $icon_types['manage'] ) ) {
			unset( $icon_types['manage'] );
		}

		asort( $icon_types );

		$sections = array(
			'global' => array(
				'id'          => 'global',
				'title'       => __( 'Global', 'buddyboss-theme' ),
				'description' => __( 'Global settings', 'buddyboss-theme' ),
				'fields'      => array(
					array(
						'id'      => 'icon_types',
						'type'    => 'checkbox',
						'label'   => __( 'Icon Types', 'buddyboss-theme' ),
						'choices' => $icon_types,
						'value'   => self::get( 'global', 'icon_types' ),
					),
				),
				'args'        => array(),
			),
		);

		if ( ! empty( $menu_id ) ) {
			$menu_term     = get_term( $menu_id, 'nav_menu' );
			$menu_key      = sprintf( 'menu_%d', $menu_id );
			$menu_settings = self::get_menu_settings( $menu_id );

			$sections['menu'] = array(
				'id'          => $menu_key,
				'title'       => __( 'Current Menu', 'buddyboss-theme' ),
				'description' => sprintf(
					__( '"%s" menu settings', 'buddyboss-theme' ),
					apply_filters( 'single_term_title', $menu_term->name )
				),
				'fields'      => self::get_settings_fields( $menu_settings ),
				'args'        => array( 'inline_description' => true ),
			);
		}

		return apply_filters( 'menu_icons_settings_sections', $sections, $menu_id );
	}

	/**
	 * Get settings fields
	 *
	 * @since Menu Icons 0.4.0
	 *
	 * @param  array $values Values to be applied to each field.
	 *
	 * @uses   apply_filters()          Calls 'menu_icons_settings_fields'.
	 * @return array
	 */
	public static function get_settings_fields( array $values = array() ) {
		$fields = array(
			'icon_style'      => array(
				'id'      => 'icon_style',
				'type'    => 'select',
				'label'   => esc_html__( 'Icon Style', 'buddyboss-theme' ),
				'default' => 'lined',
				'choices' => array(
					array(
						'value' => 'lined',
						'label' => esc_html__( 'Lined', 'buddyboss-theme' ),
					),
					array(
						'value' => 'filled',
						'label' => esc_html__( 'Filled', 'buddyboss-theme' ),
					),
				),
			),
			'box_style'      => array(
				'id'      => 'box_style',
				'type'    => 'select',
				'label'   => esc_html__( 'Box Style', 'buddyboss-theme' ),
				'default' => 'none',
				'choices' => array(
					array(
						'value' => 'none',
						'label' => esc_html__( 'None', 'buddyboss-theme' ),
					),
					array(
						'value' => 'rounded',
						'label' => esc_html__( 'Boxed', 'buddyboss-theme' ),
					),
					array(
						'value' => 'circle',
						'label' => esc_html__( 'Rounded', 'buddyboss-theme' ),
					),
				),
			),
			'hide_label'     => array(
				'id'      => 'hide_label',
				'type'    => 'select',
				'label'   => esc_html__( 'Hide Label', 'buddyboss-theme' ),
				'default' => '',
				'choices' => array(
					array(
						'value' => '',
						'label' => esc_html__( 'No', 'buddyboss-theme' ),
					),
					array(
						'value' => '1',
						'label' => esc_html__( 'Yes', 'buddyboss-theme' ),
					),
				),
			),
			'position'       => array(
				'id'      => 'position',
				'type'    => 'select',
				'label'   => esc_html__( 'Icon Position', 'buddyboss-theme' ),
				'default' => 'before',
				'choices' => array(
					array(
						'value' => 'before',
						'label' => esc_html__( 'Before', 'buddyboss-theme' ),
					),
					array(
						'value' => 'after',
						'label' => esc_html__( 'After', 'buddyboss-theme' ),
					),
				),
			),
			'vertical_align' => array(
				'id'      => 'vertical_align',
				'type'    => 'select',
				'label'   => esc_html__( 'Vertical Align', 'buddyboss-theme' ),
				'default' => 'middle',
				'choices' => array(
					array(
						'value' => 'super',
						'label' => esc_html__( 'Super', 'buddyboss-theme' ),
					),
					array(
						'value' => 'top',
						'label' => esc_html__( 'Top', 'buddyboss-theme' ),
					),
					array(
						'value' => 'text-top',
						'label' => esc_html__( 'Text Top', 'buddyboss-theme' ),
					),
					array(
						'value' => 'middle',
						'label' => esc_html__( 'Middle', 'buddyboss-theme' ),
					),
					array(
						'value' => 'baseline',
						'label' => esc_html__( 'Baseline', 'buddyboss-theme' ),
					),
					array(
						'value' => 'text-bottom',
						'label' => esc_html__( 'Text Bottom', 'buddyboss-theme' ),
					),
					array(
						'value' => 'bottom',
						'label' => esc_html__( 'Bottom', 'buddyboss-theme' ),
					),
					array(
						'value' => 'sub',
						'label' => esc_html__( 'Sub', 'buddyboss-theme' ),
					),
				),
			),
			'font_size'      => array(
				'id'          => 'font_size',
				'type'        => 'select',
				'label'       => esc_html__( 'Icon Size', 'buddyboss-theme' ),
				'default'     => 'default',
				'choices' => array(
					array(
						'value' => 'default',
						'label' => esc_html__( 'Default', 'buddyboss-theme' ),
					),
					array(
						'value' => 'custom',
						'label' => esc_html__( 'Custom', 'buddyboss-theme' ),
					),
				),
			),
			'font_size_amount'      => array(
				'id'          => 'font_size_amount',
				'type'        => 'number',
				'label'       => '',
				'default'     => '24',
				'description' => 'px',
				'attributes'  => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'svg_width'      => array(
				'id'          => 'svg_width',
				'type'        => 'number',
				'label'       => esc_html__( 'SVG Width', 'buddyboss-theme' ),
				'default'     => '1',
				'description' => 'em',
				'attributes'  => array(
					'min'  => '.5',
					'step' => '.1',
				),
			),
			'image_size'     => array(
				'id'      => 'image_size',
				'type'    => 'select',
				'label'   => esc_html__( 'Image Size', 'buddyboss-theme' ),
				'default' => 'thumbnail',
				'choices' => kucrut_get_image_sizes(),
			),
		);

		$fields = apply_filters( 'menu_icons_settings_fields', $fields );

		foreach ( $fields as &$field ) {
			if ( isset( $values[ $field['id'] ] ) ) {
				$field['value'] = $values[ $field['id'] ];
			}

			if ( ! isset( $field['value'] ) && isset( $field['default'] ) ) {
				$field['value'] = $field['default'];
			}
		}

		unset( $field );

		return $fields;
	}

	/**
	 * Get processed settings fields
	 *
	 * @since Menu Icons 0.3.0
	 * @access private
	 * @return array
	 */
	private static function _get_fields() {
		if ( ! class_exists( 'Kucrut_Form_Field' ) ) {
			require_once Buddyboss_Menu_Icons::get( 'dir' ) . 'includes/library/form-fields.php';
		}

		$keys     = array( 'menu-icons', 'settings' );
		$sections = self::get_fields();

		foreach ( $sections as &$section ) {
			$_keys = array_merge( $keys, array( $section['id'] ) );
			$_args = array_merge( array( 'keys' => $_keys ), $section['args'] );

			foreach ( $section['fields'] as &$field ) {
				$field = Kucrut_Form_Field::create( $field, $_args );
			}

			unset( $field );
		}

		unset( $section );

		return $sections;
	}

	/**
	 * Enqueue scripts & styles for Appearance > Menus page
	 *
	 * @since Menu Icons  0.3.0
	 * @wp_hook action admin_enqueue_scripts
	 */
	public static function _enqueue_assets() {
		$url    = Buddyboss_Menu_Icons::get( 'url' );
		$suffix = kucrut_get_script_suffix();

		if ( defined( 'MENU_ICONS_SCRIPT_DEBUG' ) && MENU_ICONS_SCRIPT_DEBUG ) {
			$script_url = '//localhost:8081/';
		} else {
			$script_url = $url;
		}

		wp_enqueue_style(
			'menu-icons',
			"{$url}css/admin{$suffix}.css",
			false,
			Buddyboss_Menu_Icons::VERSION
		);

		wp_enqueue_script(
			'menu-icons',
			"{$script_url}js/admin{$suffix}.js",
			self::$script_deps,
			Buddyboss_Menu_Icons::VERSION,
			true
		);

		$customizer_url = add_query_arg(
			array(
				'autofocus[section]' => 'custom_css',
				'return'             => admin_url( 'nav-menus.php' ),
			),
			admin_url( 'customize.php' )
		);

		/**
		 * Allow plugins/themes to filter the settings' JS data
		 *
		 * @since 0.9.0
		 *
		 * @param array $js_data JS Data.
		 */
		$menu_current_theme = '';
		$theme              = wp_get_theme();
		if ( ! empty( $theme ) ) {
			if ( is_child_theme() ) {
				$menu_current_theme = $theme->parent()->get( 'Name' );
			} else {
				$menu_current_theme = $theme->get( 'Name' );
			}
		}

		$active_types = self::get( 'global', 'icon_types' );
		if ( is_array( $active_types ) && ! in_array( 'image', $active_types, true ) ) {
			$active_types[] = 'image';
		}
		if ( is_array( $active_types ) && ! in_array( 'manage', $active_types, true ) ) {
			$active_types[] = 'manage';
		}

		$menu_id        = self::get_current_menu_id();
		$locations      = get_nav_menu_locations();
		$is_header_menu = false;
		$header_menus   = array();

		$menu_style      = buddyboss_menu_icons()->get_menu_style();
		$menu_style_link = esc_url( admin_url( 'admin.php?page=buddyboss_theme_options&tab=23' ) );

		if ( isset( $locations['header-menu'] ) ) {
			$header_menus[] = $locations['header-menu'];
		}
		if ( isset( $locations['header-menu-logout'] ) ) {
			$header_menus[] = $locations['header-menu-logout'];
		}

		if ( in_array( $menu_id, $header_menus, true ) ) {
			$is_header_menu = true;
		}

		$js_data = apply_filters(
			'menu_icons_settings_js_data',
			array(
				'text'           => array(
					'title'          => esc_html__( 'Select Icon', 'buddyboss-theme' ),
					'select'         => esc_html__( 'Select', 'buddyboss-theme' ),
					'remove'         => esc_html__( 'Remove', 'buddyboss-theme' ),
					'change'         => esc_html__( 'Change', 'buddyboss-theme' ),
					'all'            => esc_html__( 'All', 'buddyboss-theme' ),
					'preview'        => esc_html__( 'Preview', 'buddyboss-theme' ),
					'settings'       => esc_html__( 'Icon Settings', 'buddyboss-theme' ),
					'header_menu'    => esc_html__( 'Header Menu', 'buddyboss-theme' ),
					'instruction'    => esc_html__( 'Select an icon to configure its appearance.', 'buddyboss-theme' ),
					'settings_tip'   => sprintf( '<span>%s</span> %s', esc_html__( 'Tip:', 'buddyboss-theme' ), esc_html__( 'If you select lined, the icon will be dynamically changed to filled when the menu item is active.', 'buddyboss-theme' ) ),
					'tab_style_info' => sprintf(
					/* translators: Description with link. */
						__( 'Menu labels are hidden in your header menu as you\'ve set your %s to Tab Bar Menu.', 'buddyboss-theme' ),
						sprintf(
						/* translators: 1. Link, 2. Text */
							'<a href="%1$s" target="_blank">%2$s</a>',
							esc_url( $menu_style_link ),
							esc_html__( 'Menu Style', 'buddyboss-theme' )
						)
					),
				),
				'is_header_menu' => $is_header_menu,
				'menu_style'     => $menu_style,
				'settingsFields' => self::get_settings_fields(),
				'activeTypes'    => $active_types,
				'ajaxUrls'       => array(
					'update' => add_query_arg( 'action', 'menu_icons_update_settings', admin_url( '/admin-ajax.php' ) ),
				),
				'menuSettings'   => self::get_menu_settings( self::get_current_menu_id() ),
			)
		);

		wp_localize_script( 'menu-icons', 'menuIcons', $js_data );
	}
}
