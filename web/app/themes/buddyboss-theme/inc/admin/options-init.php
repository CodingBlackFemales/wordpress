<?php
if ( ! class_exists( 'buddyboss_theme_Redux_Framework_config' ) ) {
	class buddyboss_theme_Redux_Framework_config {

		public $args = array();
		public $sections = array();
		public $theme;
		public $ReduxFramework;

		public function __construct() {
			if ( ! class_exists( 'ReduxFramework' ) ) {
				return;
			}
			// This is needed. Bah WordPress bugs.
			if ( true === apply_filters( 'buddyboss_theme_redux_is_theme', (bool) Redux_Helpers::isTheme( __FILE__ ) ) ) {
				$this->initSettings();
			} else {
				add_action( 'plugins_loaded', array( $this, 'initSettings' ), 10 );
			}
		}

		public function initSettings() {
			// Just for demo purposes. Not needed per say.
			$this->theme = wp_get_theme();
			// Set the default arguments.
			$this->setArguments();
			// Create the sections and fields.
			$this->setSections();
			if ( ! isset( $this->args['opt_name'] ) ) { // No errors please.
				return;
			}
			// If Redux is running as a plugin, this will remove the demo notice and links.
			add_action( 'redux/loaded', array( $this, 'remove_demo' ) );
			$this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
		}

		// Remove the demo link and the notice of integrated demo from the redux-framework plugin.
		function remove_demo() {
			// Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
			if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
				remove_filter(
					'plugin_row_meta',
					array(
						ReduxFrameworkPlugin::instance(),
						'plugin_metalinks',
					),
					null,
					2
				);
				// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
				remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
			}
		}

		/**
		 * Add theme options sections and fields.
		 */
		public function setSections() {
			global $color_schemes;

			$customize_url  = add_query_arg( 'return', urlencode( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), 'customize.php' );
			$admin_url      = admin_url( $customize_url );
			$theme_options  = get_option( 'buddyboss_theme_options' );
			$theme_template = '2';

			if ( ! empty( $theme_options ) && ! empty( $theme_options['theme_template'] ) ) {
				$theme_template = $theme_options['theme_template'];
			} elseif ( ! empty( $theme_options ) && empty( $theme_options['theme_template'] ) ) {
				$theme_template = '1';
			}

			// Styling section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Styling', 'buddyboss-theme' ),
				'id'         => 'template_style',
				'customizer' => false,
				'icon'       => 'bb-icon-l bb-icon-eye',
				'fields'     => array(
					array(
						'id'         => 'theme_template',
						'title'      => esc_html__( 'Theme Style', 'buddyboss-theme' ),
						'subtitle'   => esc_html__( 'Select which theme style to use.', 'buddyboss-theme' ),
						'desc'       => esc_html__( 'The theme style controls the look of elements such as buttons, forms, navigation bars and form fields.', 'buddyboss-theme' ),
						'type'       => 'image_select',
						'customizer' => false,
						'default'    => $theme_template,
						'options'    => array(
							'2' => array(
								'alt'   => 'Theme 2.0',
								'title' => '2.0',
								'img'   => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/templates/2.0.png' ),
							),
							'1' => array(
								'alt'   => 'Theme 1.0',
								'title' => '1.0',
								'img'   => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/templates/1.0.png' ),
							),
						),
					),
					array(
						'id'            => 'button_default_radius',
						'title'         => esc_html__( 'Button Radius', 'buddyboss-theme' ),
						'subtitle'      => esc_html__( 'Select the border radius of your buttons.', 'buddyboss-theme' ),
						'desc'          => __( 'Set the radius to "0" for a square button, "100" for a pill-style button and any number in between for a rounded button. When using the 2.0 theme style, we recommend setting the button radius to "6".', 'buddyboss-theme' ),
						'type'          => 'slider',
						'default'       => 7,
						'min'           => 0,
						'step'          => 1,
						'max'           => 100,
						'display_value' => '7px',
					),
				),
			);

			$desktop_logo_dark_info   = array();
			$desktop_logo_dark        = array();
			$desktop_logo_size_dark   = array();
			$mobile_logo_dark_info    = array();
			$mobile_logo_dark         = array();
			$mobile_logo_size_dark    = array();
			$desktop_logo_dark_switch = array();
			$mobile_logo_dark_switch  = array();

			if ( class_exists( 'SFWD_LMS' ) || class_exists( 'LifterLMS' ) || function_exists( 'tutor' ) ) {
				$desktop_logo_dark_info   = array(
					'slug'     => 'desktop_logo_dark_options_info',
					'id'       => 'desktop_logo_dark_info',
					'desc'     => __( 'Desktop Logo (Dark Mode)', 'buddyboss-theme' ),
					'type'     => 'info',
					'required' => array( 'logo_switch', 'equals', '1' ),
				);
				$desktop_logo_dark_switch = array(
					'id'       => 'logo_dark_switch',
					'type'     => 'switch',
					'title'    => esc_html__( 'Desktop Logo (Dark Mode)', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Displays on lesson and quiz content when Dark Mode is toggle on by the user.', 'buddyboss-theme' ),
					'default'  => '0',
					'on'       => esc_html__( 'On', 'buddyboss-theme' ),
					'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					'required' => array( 'logo_switch', 'equals', '1' ),
				);
				$desktop_logo_dark        = array(
					'id'       => 'logo_dark',
					'type'     => 'media',
					'url'      => false,
					'required' => array( 'logo_dark_switch', 'equals', '1' ),
					'class'    => 'bbThumbScale bbThumbScaleLiD',
				);
				$desktop_logo_size_dark   = array(
					'id'       => 'logo_dark_size',
					'type'     => 'slider',
					'title'    => esc_html__( 'Desktop Logo Size (Dark Mode)', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Adjust the size of your logo', 'buddyboss-theme' ),
					'desc'     => sprintf(
					/* translators: 1. Maximum logo message. 2. Size message. 3. Header style message. */
						'%1$s <br /> %2$s <br /> %3$s',
						esc_html__( 'Maximum logo width 350px.', 'buddyboss-theme' ),
						esc_html__( 'If the logo size is taller than the header height, it will be made smaller to fit within the header.', 'buddyboss-theme' ),
						esc_html__( 'If "Show Logo in BuddyPanel" is enabled and the logo size is wider than the BuddyPanel, it will be made smaller to fit within the BuddyPanel.', 'buddyboss-theme' ),
					),
					'default'  => '0',
					'min'      => 0,
					'step'     => 1,
					'max'      => 350,
					'class'    => 'bbThumbSlide bbThumbSlideLiD',
					'required' => array( 'logo_dark_switch', 'equals', '1' ),
				);
				$mobile_logo_dark_info    = array(
					'slug'     => 'mobile_logo_dark_options_info',
					'id'       => 'mobile__logo_dark_info',
					'desc'     => __( 'Mobile Logo (Dark Mode)', 'buddyboss-theme' ),
					'type'     => 'info',
					'required' => array( 'mobile_logo_switch', 'equals', '1' ),
				);
				$mobile_logo_dark_switch  = array(
					'id'       => 'mobile_logo_dark_switch',
					'type'     => 'switch',
					'title'    => esc_html__( 'Mobile Logo (Dark Mode)', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Displays on lesson and quiz content when Dark Mode is toggle on by the user.', 'buddyboss-theme' ),
					'default'  => '0',
					'on'       => esc_html__( 'On', 'buddyboss-theme' ),
					'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					'required' => array( 'mobile_logo_switch', 'equals', '1' ),
				);
				$mobile_logo_dark         = array(
					'id'       => 'mobile_logo_dark',
					'type'     => 'media',
					'url'      => false,
					'required' => array( 'mobile_logo_dark_switch', 'equals', '1' ),
					'class'    => 'bbThumbScale bbThumbScaleLimD',
				);
				$mobile_logo_size_dark    = array(
					'id'       => 'mobile_logo_dark_size',
					'type'     => 'slider',
					'title'    => esc_html__( 'Mobile Logo Size (Dark Mode)', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Adjust the size of mobile logo', 'buddyboss-theme' ),
					'desc'     => sprintf(
					/* translators: 1. Maximum logo message. 2. Size message. */
						'%1$s <br /> %2$s',
						esc_html__( 'Maximum logo width 350px.', 'buddyboss-theme' ),
						esc_html__( 'If the logo size is taller than the header height, it will be made smaller to fit within the header.', 'buddyboss-theme' ),
					),
					'default'  => '0',
					'min'      => 0,
					'step'     => 1,
					'max'      => 350,
					'class'    => 'bbThumbSlide bbThumbSlideLimD',
					'required' => array( 'mobile_logo_dark_switch', 'equals', '1' ),
				);
			}

			// Pages List.
			$page_items['default'] = esc_html__( 'Home', 'buddyboss-theme' );
			$published_pages       = ( isset( $_GET['page'] ) && 'buddyboss_theme_options' === $_GET['page'] ) ? bb_theme_get_published_pages() : array();
			$page_items            = $page_items + $published_pages;

			// Logo Settings.
			$this->sections[] = array(
				'title'      => esc_html__( 'Logo', 'buddyboss-theme' ),
				'icon'       => 'bb-icon-l bb-icon-bolt',
				'customizer' => false,
				'priority'   => 20,
				'fields'     => array(
					array(
						'slug' => 'desktop_logo_options_info',
						'id'   => 'desktop_logo_info',
						'desc' => __( 'Desktop Logo', 'buddyboss-theme' ),
						'type' => 'info',
					),
					array(
						'id'       => 'logo_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Desktop Logo', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Upload your custom site logo for desktop layout (280px by 80px).', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'logo',
						'type'     => 'media',
						'url'      => false,
						'required' => array( 'logo_switch', 'equals', '1' ),
						'class'    => 'bbThumbScale bbThumbScaleLi',
					),
					array(
						'id'       => 'logo_size',
						'type'     => 'slider',
						'title'    => esc_html__( 'Desktop Logo Size', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Adjust the size of your logo', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: 1. Maximum logo message. 2. Size message. 3. Header style message. */
							'%1$s <br /> %2$s <br /> %3$s',
							esc_html__( 'Maximum logo width 350px.', 'buddyboss-theme' ),
							esc_html__( 'If the logo size is taller than the header height, it will be made smaller to fit within the header.', 'buddyboss-theme' ),
							esc_html__( 'If "Show Logo in BuddyPanel" is enabled and the logo size is wider than the BuddyPanel, it will be made smaller to fit within the BuddyPanel.', 'buddyboss-theme' ),
						),
						'default'  => '0',
						'min'      => 0,
						'step'     => 1,
						'max'      => 350,
						'class'    => 'bbThumbSlide bbThumbSlideLi',
						'required' => array( 'logo_switch', 'equals', '1' ),
					),
					$desktop_logo_dark_info,
					$desktop_logo_dark_switch,
					$desktop_logo_dark,
					$desktop_logo_size_dark,
					array(
						'slug' => 'mobile_logo_options_info',
						'id'   => 'mobile_logo_info',
						'desc' => __( 'Mobile Logo', 'buddyboss-theme' ),
						'type' => 'info',
					),
					array(
						'id'       => 'mobile_logo_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Mobile Logo', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Upload your custom site logo for mobile layout (280px by 80px).', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'mobile_logo',
						'type'     => 'media',
						'url'      => false,
						'required' => array( 'mobile_logo_switch', 'equals', '1' ),
						'class'    => 'bbThumbScale bbThumbScaleLi',
					),
					array(
						'id'       => 'mobile_logo_size',
						'type'     => 'slider',
						'title'    => esc_html__( 'Mobile Logo Size', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Adjust the size of mobile logo', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: 1. Maximum logo message. 2. Size message. 3. Header style message. */
							'%1$s <br /> %2$s',
							esc_html__( 'Maximum logo width 350px.', 'buddyboss-theme' ),
							esc_html__( 'If the logo size is taller than the header height, it will be made smaller to fit within the header.', 'buddyboss-theme' ),
						),
						'default'  => '0',
						'min'      => 0,
						'step'     => 1,
						'max'      => 350,
						'class'    => 'bbThumbSlide bbThumbSlideLim',
						'required' => array( 'mobile_logo_switch', 'equals', '1' ),
					),
					$mobile_logo_dark_info,
					$mobile_logo_dark_switch,
					$mobile_logo_dark,
					$mobile_logo_size_dark,
					array(
						'slug' => 'header_logo_destination',
						'id'   => 'header_logo_destination',
						'desc' => __( 'Destination', 'buddyboss-theme' ),
						'type' => 'info',
					),
					array(
						'id'          => 'header_logo_loggedout_link',
						'type'        => 'select',
						'title'       => esc_html__( 'Logged-out Page', 'buddyboss-theme' ),
						'subtitle'    => esc_html__( 'The page to open when logged-out visitors click on your logo.', 'buddyboss-theme' ),
						'options'     => $page_items,
						'default'     => 'default',
						'placeholder' => $page_items['default'],
						'select2'     => array( 'allowClear' => false ),
					),
					array(
						'id'          => 'header_logo_loggedin_link',
						'type'        => 'select',
						'title'       => esc_html__( 'Logged-in Page', 'buddyboss-theme' ),
						'subtitle'    => esc_html__( 'The page to open when logged-in members click on your logo.', 'buddyboss-theme' ),
						'options'     => $page_items,
						'default'     => 'default',
						'placeholder' => $page_items['default'],
						'select2'     => array( 'allowClear' => false ),
					),
					array(
						'slug' => 'favicon_options_info',
						'id'   => 'favicon_info',
						'desc' => __( 'Site Icon', 'buddyboss-theme' ),
						'type' => 'info',
					),
					array(
						'id'       => 'favicon',
						'type'     => 'raw',
						'url'      => false,
						'title'    => esc_html__( 'Site Icon', 'buddyboss-theme' ),
						'subtitle' => sprintf(
						/* translators: 1. Admin customise URL with text. */
							__( 'Upload your custom site icon (favicon) at %1$s in the Site Identity section.', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Admin customise URL. 2. Text. 3. Text. */
								'<a href="%1$s">%2$s &gt; %3$s</a>',
								esc_url( $admin_url ),
								esc_html__( 'Appearance', 'buddyboss-theme' ),
								esc_html__( 'Customize', 'buddyboss-theme' )
							)
						),
						'desc'     => '',
					),
				),
			);

			// Color section.
			$style_elements        = array(
				array(
					'slug'    => 'buddyboss_theme_scheme_select',
					'desc'    => esc_html__( 'ss', 'buddyboss-theme' ),
					'type'    => 'preset',
					'default' => 'default',
				),

				/* General colors */
				array(
					'slug' => 'general_options_info',
					'desc' => esc_html__( 'General', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'accent_color',
					'title'    => esc_html__( 'Primary color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Used in various elements such as links, icons and counts.', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#385DFF',
				),
				array(
					'slug'     => 'body_background',
					'title'    => esc_html__( 'Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#FAFBFD',
				),
				array(
					'slug'     => 'body_blocks',
					'title'    => esc_html__( 'Content Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#FFFFFF',
				),
				array(
					'slug'     => 'light_background_blocks',
					'title'    => esc_html__( 'Content Alternate Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#F2F4F5',
				),
				array(
					'slug'     => 'body_blocks_border',
					'title'    => esc_html__( 'Content Border Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#D6D9DD',
				),
				array(
					'slug'     => 'buddyboss_theme_group_cover_bg',
					'title'    => esc_html__( 'Cover Image Background Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'The background color used behind default cover images.', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#647385',
				),
				array(
					'slug'     => 'heading_text_color',
					'title'    => esc_html__( 'Headings Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Used for headings tags (H1, H2. H3, etc)', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#1E2132',
				),
				array(
					'slug'     => 'body_text_color',
					'title'    => esc_html__( 'Body Text Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Used for paragraph and main content text.', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#5A5A5A',
				),
				array(
					'slug'     => 'alternate_text_color',
					'title'    => esc_html__( 'Alternate Text Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Used for meta data and supplementary text.', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#9B9C9F',
				),
				/* Buttons colors */
				array(
					'slug' => 'buttons_options_info',
					'desc' => esc_html__( 'Buttons', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'primary_button_background',
					'title'    => esc_html__( 'Primary Button Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#385DFF',
						'hover'   => '#1E42DD',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'primary_button_border',
					'title'    => esc_html__( 'Primary Button Border Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#385DFF',
						'hover'   => '#1E42DD',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'primary_button_text_color',
					'title'    => esc_html__( 'Primary Button Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#ffffff',
						'hover'   => '#ffffff',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'secondary_button_background',
					'title'    => esc_html__( 'Secondary Button Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#F2F4F5',
						'hover'   => '#385DFF',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'secondary_button_border',
					'title'    => esc_html__( 'Secondary Button Border Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#F2F4F5',
						'hover'   => '#385DFF',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'secondary_button_text_color',
					'title'    => esc_html__( 'Secondary Button Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#1E2132',
						'hover'   => '#FFFFFF',
						'active'  => false,
					),
				),
				/* Header colors */
				array(
					'slug' => 'header_color_options_info',
					'desc' => esc_html__( 'Header', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'header_background',
					'title'    => esc_html__( 'Header Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#ffffff',
				),
				array(
					'slug'     => 'header_alternate_background',
					'title'    => esc_html__( 'Header Alternate Background Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Used for the search bar and hover effects.', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#F2F4F5',
				),
				array(
					'slug'     => 'header_links',
					'title'    => esc_html__( 'Header Text/Link Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Menu and Icons Links Color', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#1E2132',
				),
				array(
					'slug'     => 'header_links_hover',
					'title'    => esc_html__( 'Header Hover/Active Link Color', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Menu and Icons Hover Links Color', 'buddyboss-theme' ),
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#385DFF',
				),
				/* BuddyPanel colors */
				array(
					'slug' => 'sidenav_color_options_info',
					'desc' => esc_html__( 'BuddyPanel', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'sidenav_background',
					'title'    => esc_html__( 'BuddyPanel Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#ffffff',
				),
				array(
					'slug'     => 'sidenav_text_color',
					'title'    => esc_html__( 'BuddyPanel Menu Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
						'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
						'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#ffffff',
					),
				),
				array(
					'slug'     => 'sidenav_menu_background_color',
					'title'    => esc_html__( 'BuddyPanel Menu Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
						'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_alt_background' ) ? buddyboss_theme_get_option( 'sidenav_alt_background' ) : '#F2F4F5',
						'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
					),
				),
				array(
					'slug'     => 'sidenav_count_text_color',
					'title'    => esc_html__( 'BuddyPanel Count Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
						'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
						'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
					),
				),
				array(
					'slug'     => 'sidenav_count_background_color',
					'title'    => esc_html__( 'BuddyPanel Count Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_alt_background' ) ? buddyboss_theme_get_option( 'sidenav_alt_background' ) : '#F2F4F5',
						'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
						'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
					),
				),
				/* Footer colors */
				array(
					'slug' => 'footer_color_options_info',
					'desc' => esc_html__( 'Footer', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'footer_background',
					'title'    => esc_html__( 'Footer Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#FAFBFD',
				),
				array(
					'slug'     => 'footer_widget_background',
					'title'    => esc_html__( 'Footer Widgets Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#FAFBFD',
				),
				array(
					'slug'     => 'footer_text_color',
					'title'    => esc_html__( 'Footer Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#5A5A5A',
				),
				array(
					'slug'     => 'footer_menu_link_color',
					'title'    => esc_html__( 'Footer Menu Link Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#5A5A5A',
						'hover'   => '#385DFF',
						'active'  => '#1E2132',
					),
				),
				/* Login / Register Screens */
				array(
					'slug' => 'admin_screen_info',
					'desc' => esc_html__( 'Login / Register Screens', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'admin_screen_bgr_color',
					'title'    => esc_html__( 'Login / Register Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#FFFFFF',
				),
				array(
					'slug'     => 'admin_screen_txt_color',
					'title'    => esc_html__( 'Login / Register Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#1E2132',
				),
				array(
					'slug'     => 'login_register_link_color',
					'title'    => esc_html__( 'Login / Register Link Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#5A5A5A',
						'hover'   => '#1E42DD',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'login_register_button_background_color',
					'title'    => esc_html__( 'Login / Register Button Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#385DFF',
						'hover'   => '#1E42DD',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'login_register_button_border_color',
					'title'    => esc_html__( 'Login / Register Button Border Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#385DFF',
						'hover'   => '#1E42DD',
						'active'  => false,
					),
				),
				array(
					'slug'     => 'login_register_button_text_color',
					'title'    => esc_html__( 'Login / Register Button Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'link_color',
					'default'  => array(
						'regular' => '#FFFFFF',
						'hover'   => '#FFFFFF',
						'active'  => false,
					),
				),
				/* Label colors */
				array(
					'slug' => 'label_options_info',
					'desc' => esc_html__( 'Labels', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'label_background_color',
					'title'    => esc_html__( 'Label Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#D7DFFF',
				),
				array(
					'slug'     => 'label_text_color',
					'title'    => esc_html__( 'Label Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#385DFF',
				),
				/* Tooltips colors */
				array(
					'slug' => 'tooltip_options_info',
					'desc' => esc_html__( 'Tooltips', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'tooltip_background',
					'title'    => esc_html__( 'Tooltips Background Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#1E2132',
				),
				array(
					'slug'     => 'tooltip_color',
					'title'    => esc_html__( 'Tooltips Text Color', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#ffffff',
				),
				/* Notices / Alerts */
				array(
					'slug' => 'notice_color_info',
					'desc' => __( 'Notices / Alerts', 'buddyboss-theme' ),
					'type' => 'info',
				),
				array(
					'slug'     => 'default_notice_bg_color',
					'title'    => esc_html__( 'Info', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#385DFF',
				),
				array(
					'slug'     => 'success_notice_bg_color',
					'title'    => esc_html__( 'Success', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#14B550',
				),
				array(
					'slug'     => 'warning_notice_bg_color',
					'title'    => esc_html__( 'Warning', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#ED9615',
				),
				array(
					'slug'     => 'error_notice_bg_color',
					'title'    => esc_html__( 'Error', 'buddyboss-theme' ),
					'subtitle' => '',
					'desc'     => '',
					'type'     => 'color',
					'default'  => '#DB222A',
				),
			);
			$color_scheme_elements = apply_filters( 'buddyboss_theme_color_element_options', $style_elements );
			$style_fields          = array();
			$color_schemes         = array(
				'default' => array(
					'alt'     => 'Default',
					'img'     => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/presets/default.png' ),
					'presets' => array(
						'accent_color'                           => '#385DFF',
						'body_background'                        => '#FAFBFD',
						'light_background_blocks'                => '#F2F4F5',
						'body_blocks'                            => '#FFFFFF',
						'body_blocks_border'                     => '#D6D9DD',
						'buddyboss_theme_group_cover_bg'         => '#647385',
						'heading_text_color'                     => '#1E2132',
						'body_text_color'                        => '#5A5A5A',
						'alternate_text_color'                   => '#9B9C9F',
						'header_background'                      => '#ffffff',
						'header_alternate_background'            => '#F2F4F5',
						'header_links'                           => '#1E2132',
						'header_links_hover'                     => '#385DFF',
						'sidenav_background'                     => '#ffffff',
						'sidenav_text_color' 					 => array(
							'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
							'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
							'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
						),
						'sidenav_menu_background_color' 		 => array(
							'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
							'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_alt_background' ) ? buddyboss_theme_get_option( 'sidenav_alt_background' ) : '#F2F4F5',
							'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
						),
						'sidenav_count_text_color' 				 => array(
							'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links' ) ? buddyboss_theme_get_option( 'sidenav_links' ) : '#1E2132',
							'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
							'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
						),
						'sidenav_count_background_color' 		 => array(
							'regular' => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_alt_background' ) ? buddyboss_theme_get_option( 'sidenav_alt_background' ) : '#F2F4F5',
							'hover'   => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_links_hover' ) ? buddyboss_theme_get_option( 'sidenav_links_hover' ) : '#385DFF',
							'active'  => function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'sidenav_background' ) ? buddyboss_theme_get_option( 'sidenav_background' ) : '#FFFFFF',
						),
						'footer_background'                      => '#FAFBFD',
						'footer_widget_background'               => '#FAFBFD',
						'footer_text_color'                      => '#5A5A5A',
						'admin_login_heading_color'              => '#FFFFFF',
						'admin_screen_bgr_color'                 => '#FFFFFF',
						'admin_screen_txt_color'                 => '#1E2132',
						'label_background_color'                 => '#D7DFFF',
						'label_text_color'                       => '#385DFF',
						'tooltip_background'                     => '#1E2132',
						'tooltip_color'                          => '#ffffff',
						'default_notice_bg_color'                => '#385DFF',
						'success_notice_bg_color'                => '#14B550',
						'warning_notice_bg_color'                => '#ED9615',
						'error_notice_bg_color'                  => '#DB222A',
						'primary_button_background'              => array(
							'regular' => '#385DFF',
							'hover'   => '#1E42DD',
							'active'  => false,
						),
						'primary_button_border'                  => array(
							'regular' => '#385DFF',
							'hover'   => '#1E42DD',
							'active'  => false,
						),
						'primary_button_text_color'              => array(
							'regular' => '#ffffff',
							'hover'   => '#ffffff',
							'active'  => false,
						),
						'secondary_button_background'            => array(
							'regular' => '#F2F4F5',
							'hover'   => '#385DFF',
							'active'  => false,
						),
						'secondary_button_border'                => array(
							'regular' => '#F2F4F5',
							'hover'   => '#385DFF',
							'active'  => false,
						),
						'secondary_button_text_color'            => array(
							'regular' => '#1E2132',
							'hover'   => '#FFFFFF',
							'active'  => false,
						),
						'footer_menu_link_color'                 => array(
							'regular' => '#5A5A5A',
							'hover'   => '#385DFF',
							'active'  => '#1E2132',
						),
						'login_register_link_color'              => array(
							'regular' => '#5A5A5A',
							'hover'   => '#1E42DD',
							'active'  => false,
						),
						'login_register_button_background_color' => array(
							'regular' => '#385DFF',
							'hover'   => '#1E42DD',
							'active'  => false,
						),
						'login_register_button_border_color'     => array(
							'regular' => '#385DFF',
							'hover'   => '#1E42DD',
							'active'  => false,
						),
						'login_register_button_text_color'       => array(
							'regular' => '#FFFFFF',
							'hover'   => '#FFFFFF',
							'active'  => false,
						),
					),
				),
			);
			foreach ( $color_scheme_elements as $elem ) {
				if ( 'color' === $elem['type'] ) {
					$style_fields[] = array(
						'id'          => $elem['slug'],
						'type'        => $elem['type'],
						'title'       => $elem['title'],
						'subtitle'    => $elem['subtitle'],
						'desc'        => $elem['desc'],
						'default'     => $elem['default'],
						'transparent' => false,
						'validate'   => 'color',
					);
				} elseif ( 'link_color' === $elem['type'] ) {
					$style_fields[] = array(
						'id'          => $elem['slug'],
						'type'        => $elem['type'],
						'title'       => $elem['title'],
						'subtitle'    => $elem['subtitle'],
						'desc'        => $elem['desc'],
						'default'     => $elem['default'],
						'transparent' => false,
						'validate'   => 'color',
					);
				} elseif ( 'slider' === $elem['type'] ) {
					$style_fields[] = array(
						'id'            => $elem['slug'],
						'type'          => $elem['type'],
						'title'         => $elem['title'],
						'subtitle'      => $elem['subtitle'],
						'desc'          => $elem['desc'],
						'default'       => $elem['default'],
						'min'           => $elem['min'],
						'step'          => $elem['step'],
						'max'           => $elem['max'],
						'display_value' => $elem['display_value'],
					);
				} elseif ( 'info' === $elem['type'] ) {
					$style_fields[] = array(
						'id'   => $elem['slug'],
						'type' => 'info',
						'desc' => $elem['desc'],
					);
				} elseif ( 'preset' == $elem['type'] ) {
					$style_fields[] = array(
						'id'         => $elem['slug'],
						'type'       => 'custom_image_select',
						'title'      => esc_html__( 'Default Color Scheme', 'buddyboss-theme' ),
						'subtitle'   => esc_html__( 'Reset all colors back to the default color scheme', 'buddyboss-theme' ),
						'presets'    => true,
						'customizer' => false,
						'default'    => $elem['default'],
						'options'    => apply_filters( 'buddyboss_theme_color_schemes', $color_schemes ),
					);
				}
			}
			$this->sections[] = array(
				'icon'       => 'bb-icon-l bb-icon-droplet',
				'icon_class' => 'icon-large',
				'title'      => esc_html__( 'Colors', 'buddyboss-theme' ),
				'priority'   => 20,
				'desc'       => '',
				'fields'     => $style_fields,
			);

			// Typography fields.
			$font_options = array(
				array(
					'id'       => 'custom_typography',
					'type'     => 'switch',
					'title'    => esc_html__( 'Custom Typography', 'buddyboss-theme' ),
					'subtitle' => sprintf(
					/* translators: 1. Custom font URL with text. */
						__( 'Enable custom typography. Add custom fonts in the %s section.', 'buddyboss-theme' ),
						sprintf(
						/* translators: 1. Link URL. 2. Link text. */
							'<a href="%1$s">%2$s</a>',
							admin_url( 'edit.php?post_type=buddyboss_fonts' ),
							esc_html__( 'Fonts', 'buddyboss-theme' )
						)
					),
					'on'       => esc_html__( 'On', 'buddyboss-theme' ),
					'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					'default'  => '0',
				),
				array(
					'id'          => 'boss_site_title_font_family',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'Site Title', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the site title properties.', 'buddyboss-theme' ),
					'google'      => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '30px',
						'font-weight' => '500',
						'google'      => 'false',
					),
					'output'      => array( '.site-header .site-title' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_body_font_family',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'Body Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the body font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Text',
						'font-size'   => '16px',
						'font-weight' => '400normal',
						'google'      => 'false',
					),
					'output'      => array( 'body' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h1_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H1 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H1 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '34px',
						'font-weight' => '400',
						'google'      => 'false',
					),
					'output'      => array( 'h1' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h2_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H2 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H2 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '24px',
						'font-weight' => '400',
						'google'      => 'false',
					),
					'output'      => array( 'h2' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h3_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H3 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H3 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '20px',
						'font-weight' => '400',
						'google'      => 'false',
					),
					'output'      => array( 'h3' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h4_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H4 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H4 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '18px',
						'font-weight' => '400',
						'google'      => 'false',
					),
					'output'      => array( 'h4' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h5_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H5 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H5 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '16px',
						'font-weight' => '400',
						'google'      => 'false',
					),
					'output'      => array( 'h5' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
				array(
					'id'          => 'boss_h6_font_options',
					'type'        => 'bb_typography',
					'title'       => esc_html__( 'H6 Font', 'buddyboss-theme' ),
					'subtitle'    => esc_html__( 'Specify the H6 tag font properties.', 'buddyboss-theme' ),
					'google'      => true,
					'font-size'   => true,
					'line-height' => false,
					'text-align'  => false,
					'subsets'     => true,
					'color'       => false,
					'default'     => array(
						'font-family' => 'SF UI Display',
						'font-size'   => '12px',
						'font-weight' => '500',
						'google'      => 'false',
					),
					'output'      => array( 'h6' ),
					'required'    => array( 'custom_typography', 'equals', '1' ),
				),
			);

			// Typography section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Typography', 'buddyboss-theme' ),
				'icon'       => 'bb-icon-l bb-icon-font',
				'customizer' => false,
				'priority'   => 20,
				'fields'     => apply_filters( 'buddyboss_theme_font_options', $font_options ),
			);

			// Array of header options.
			$header_options = array(
				'1' => array(
					'alt' => 'Header style 1',
					'img' => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/headers/style1.png' ),
				),
				'2' => array(
					'alt' => 'Header style 2',
					'img' => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/headers/style2.png' ),
				),
				'3' => array(
					'alt' => 'Header style 3',
					'img' => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/headers/style3.png' ),
				),
				'4' => array(
					'alt' => 'Header style 4',
					'img' => get_template_directory_uri() . '/inc/admin/assets/images/headers/style4.png',
				),
			);
			$buddypanel_link = admin_url( 'admin.php?page=buddyboss_theme_options&tab=24' );
			// Header Settings.
			$this->sections[] = array(
				'title'      => esc_html__( 'Header', 'buddyboss-theme' ),
				'id'         => 'header_layout',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-maximize',
				'fields'     => array(
					array(
						'id'   => 'header_desktop_options',
						'type' => 'info',
						'desc' => esc_html__( 'Desktop', 'buddyboss-theme' ),
					),
					array(
						'id'         => 'buddyboss_header',
						'title'      => esc_html__( 'Header Style', 'buddyboss-theme' ),
						'subtitle'   => esc_html__( 'Select the layout of your desktop header.', 'buddyboss-theme' ),
						'desc'       => sprintf(
						/* translators: 1. Text, 2. Link */
							__( 'If you have enabled %1$s in the %2$s settings, it will be hidden in your desktop header.', 'buddyboss-theme' ),
							'<strong>' . esc_html__( 'BuddyPanel Logo', 'buddyboss-theme' ) . '</strong>',
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="%1$s">%2$s</a>',
								esc_url( $buddypanel_link ),
								esc_html__( 'BuddyPanel', 'buddyboss-theme' )
							)
						),
						'type'       => 'image_select',
						'customizer' => false,
						'default'    => '1',
						'options'    => $header_options,
					),
					array(
						'id'       => 'menu_style',
						'type'     => 'button_set',
						'title'    => esc_html__( 'Menu Style', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select the style of your desktop header menu.', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: Link with description. */
							__( 'The Tab Bar style requires icons, which you can assign to menu items in the %s settings.', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="%1$s">%2$s</a>',
								esc_url( admin_url( 'nav-menus.php' ) ),
								esc_html__( 'Menus', 'buddyboss-theme' )
							)
						),
						'default'  => 'standard',
						'options'  => array(
							'standard' => esc_html__( 'Standard', 'buddyboss-theme' ),
							'tab_bar'  => esc_html__( 'Tab Bar', 'buddyboss-theme' ),
						),
					),
					array(
						'id'       => 'desktop_component_opt_multi_checkbox',
						'type'     => 'checkbox',
						'title'    => esc_html__( 'Components', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select which components show in your desktop header.', 'buddyboss-theme' ),

						// Must provide key => value pairs for multi checkbox options.
						'options'  => array(
							'desktop_header_search' => esc_html__( 'Search', 'buddyboss-theme' ),
							'desktop_messages'      => esc_html__( 'Messages', 'buddyboss-theme' ),
							'desktop_notifications' => esc_html__( 'Notifications', 'buddyboss-theme' ),
							'desktop_shopping_cart' => esc_html__( 'Shopping Cart - requires WooCommerce', 'buddyboss-theme' ),
						),

						// See how default has changed? you also don't need to specify opts that are 0.
						'default'  => array(
							'desktop_header_search' => '1',
							'desktop_messages'      => '1',
							'desktop_notifications' => '1',
							'desktop_shopping_cart' => '1',
						),
					),
					array(
						'id'       => 'profile_dropdown',
						'type'     => 'button_set',
						'title'    => esc_html__( 'Profile Dropdown', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select your style of profile dropdown. Sign In / Sign Up buttons will be used for logged out users.', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: Link with description. */
							__( 'You can create a menu assign it to the Profile Dropdown in the %s settings.', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="%1$s">%2$s</a>',
								esc_url( admin_url( 'nav-menus.php' ) ),
								esc_html__( 'Menu', 'buddyboss-theme' )
							)
						),
						'default'  => 'name_and_avatar',
						'options'  => array(
							'name_and_avatar' => esc_html__( 'Name & Avatar', 'buddyboss-theme' ),
							'avatar'          => esc_html__( 'Avatar Only', 'buddyboss-theme' ),
							'off'             => esc_html__( 'Off', 'buddyboss-theme' ),
						),
					),
					array(
						'id'   => 'mobile_header_buttons_info',
						'type' => 'info',
						'desc' => esc_html__( 'Mobile', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'mobile_component_opt_multi_checkbox',
						'type'     => 'checkbox',
						'title'    => esc_html__( 'Components', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select which components show in your mobile header.', 'buddyboss-theme' ),

						// Must provide key => value pairs for multi checkbox options.
						'options'  => array(
							'mobile_header_search' => esc_html__( 'Search', 'buddyboss-theme' ),
							'mobile_messages'      => esc_html__( 'Messages', 'buddyboss-theme' ),
							'mobile_notifications' => esc_html__( 'Notifications', 'buddyboss-theme' ),
							'mobile_shopping_cart' => esc_html__( 'Shopping Cart - requires WooCommerce', 'buddyboss-theme' ),
						),

						// See how default has changed? you also don't need to specify opts that are 0.
						'default'  => array(
							'mobile_header_search' => '1',
							'mobile_messages'      => '0',
							'mobile_notifications' => '0',
							'mobile_shopping_cart' => '0',
						),
					),
					array(
						'id'   => 'header_layout_options',
						'type' => 'info',
						'desc' => esc_html__( 'Advanced', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'header_sticky',
						'type'     => 'switch',
						'title'    => esc_html__( 'Sticky Header', 'buddyboss-theme' ),
						'subtitle' => sprintf(
						/* translators: 1. LearnDash URL with text. */
							__( 'Stick your headers to the top of the screen while scrolling. Header is always sticky in %1$s lessons and topics.', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="%1$s">%2$s</a>',
								esc_url( 'https://learndash.idevaffiliate.com/111.html' ),
								esc_html__( 'LearnDash', 'buddyboss-theme' )
							)
						),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'            => 'header_height',
						'type'          => 'slider',
						'title'         => esc_html__( 'Header Height', 'buddyboss-theme' ),
						'subtitle'      => esc_html__( 'Set a height for your headers.', 'buddyboss-theme' ),
						'desc'          => esc_html__( 'Select a value between 60px and 200px.', 'buddyboss-theme' ),
						'default'       => 76,
						'min'           => 60,
						'step'          => 1,
						'max'           => 200,
						'display_value' => '76px',
					),
					array(
						'id'       => 'header_shadow',
						'type'     => 'switch',
						'title'    => esc_html__( 'Header Shadow', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select whether to show a shadow under your headers.', 'buddyboss-theme' ),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
				),
			);

			// BuddyPanel section.
			$this->sections[] = array(
				'title'      => esc_html__( 'BuddyPanel', 'buddyboss-theme' ),
				'id'         => 'theme_layout',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-sidebar',
				'fields'     => array(
					array(
						'id'       => 'buddypanel',
						'type'     => 'switch',
						'title'    => esc_html__( 'BuddyPanel', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select whether to display the BuddyPanel menu in a side navigation panel on your site.', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: 1. Link with description. */
							__( 'Go to the %s page to create a menu and assign it to the BuddyPanel location.', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="%1$s">%2$s</a>',
								esc_url( admin_url( 'nav-menus.php' ) ),
								esc_html__( 'Menus', 'buddyboss-theme' )
							)
						),
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'default'  => '1',
					),
					array(
						'id'       => 'buddypanel_show_logo',
						'type'     => 'switch',
						'title'    => esc_html__( 'Show Logo in BuddyPanel', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select whether to show a logo in the BuddyPanel.', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: 1. Description with Text, 2. Description with Text */
							__( 'When enabled, the %1$s will be hidden your from header and shown in the BuddyPanel\'s open state. In the closed state, your %2$s will be used.', 'buddyboss-theme' ),
							'<strong>' . esc_html__( 'Desktop Logo', 'buddyboss-theme' ) . '</strong>',
							'<strong>' . esc_html__( 'Site Icon', 'buddyboss-theme' ) . '</strong>'
						),
						'default'  => '',
						'options'  => array(
							'on'  => esc_html__( 'On', 'buddyboss-theme' ),
							'off' => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						'required' => array(
							array( 'buddypanel', 'equals', '1' ),
						),
					),
					array(
						'id'       => 'buddypanel_position',
						'type'     => 'button_set',
						'title'    => esc_html__( 'BuddyPanel Position', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select which side of the screen the BuddyPanel should be aligned to.', 'buddyboss-theme' ),
						'default'  => 'left',
						'required' => array(
							array( 'buddypanel', 'equals', '1' ),
							array( 'buddyboss_header', 'equals', array( '1', '2', '4' ) ),
						),
						'options'  => array(
							'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
							'right' => esc_html__( 'Right', 'buddyboss-theme' ),
						),
					),
					array(
						'id'       => 'buddypanel_position_h3',
						'type'     => 'button_set',
						'title'    => esc_html__( 'BuddyPanel Position', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select which side of the screen the BuddyPanel should be aligned to.', 'buddyboss-theme' ),
						'default'  => 'left',
						'required' => array(
							array( 'buddypanel', 'equals', '1' ),
							array( 'buddyboss_header', 'equals', '3' ),
						),
						'options'  => array(
							'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
							'right' => esc_html__( 'Right', 'buddyboss-theme' ),
						),
					),
					array(
						'id'       => 'buddypanel_toggle',
						'type'     => 'switch',
						'title'    => esc_html__( 'Toggle BuddyPanel', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'When enabled, the BuddyPanel can be toggled between open and closed.', 'buddyboss-theme' ),
						'default'  => '1',
						'options'  => array(
							'on'  => esc_html__( 'On', 'buddyboss-theme' ),
							'off' => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						'required' => array(
							array( 'buddypanel', 'equals', '1' ),
						),
					),
					array(
						'id'       => 'buddypanel_state',
						'type'     => 'button_set',
						'title'    => esc_html__( 'BuddyPanel Default State', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select the BuddyPanel\'s default state for new sessions.', 'buddyboss-theme' ),
						'default'  => 'close',
						'required' => array( 'buddypanel', 'equals', '1' ),
						'options'  => array(
							'open'  => esc_html__( 'Open', 'buddyboss-theme' ),
							'close' => esc_html__( 'Closed', 'buddyboss-theme' ),
						),
					),
				),
			);

			$sidebar_array                  = array(
				'id'       => 'sidebar',
				'type'     => 'button_set',
				'title'    => esc_html__( 'Blog Sidebar', 'buddyboss-theme' ),
				'subtitle' => esc_html__( 'Select the blog post sidebar alignment.', 'buddyboss-theme' ),
				'default'  => 'right',
				'options'  => array(
					'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
					'right' => esc_html__( 'Right', 'buddyboss-theme' ),
				),
			);
			$page_sidebar_array             = array(
				'id'       => 'page',
				'type'     => 'button_set',
				'title'    => esc_html__( 'Page Sidebar', 'buddyboss-theme' ),
				'subtitle' => esc_html__( 'Select the pages sidebar alignment.', 'buddyboss-theme' ),
				'default'  => 'right',
				'options'  => array(
					'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
					'right' => esc_html__( 'Right', 'buddyboss-theme' ),
				),
			);
			$members_sidebar_array          = array(
				'id'       => 'members',
				'type'     => 'button_set',
				'title'    => esc_html__( 'Members Directory Sidebar', 'buddyboss-theme' ),
				'subtitle' => esc_html__( 'Select the members directory sidebar alignment.', 'buddyboss-theme' ),
				'default'  => 'right',
				'options'  => array(
					'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
					'right' => esc_html__( 'Right', 'buddyboss-theme' ),
				),
			);
			$profile_sidebar_array          = array();
			$group_directory_sidebar_array  = array();
			$single_group_sidebar_array     = array();
			$activity_sidebar_array         = array();
			$woocommerce_sidebar_array      = array();
			$learndash_sidebar_array        = array();
			$learndash_single_sidebar_array = array();
			$lifterlms_sidebar_array        = array();
			if ( function_exists( 'bp_is_active' ) ) {
				$profile_sidebar_array = array(
					'id'       => 'profile',
					'type'     => 'button_set',
					'title'    => esc_html__( 'Member Profile Sidebar', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Select the member profile sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'left',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);

				if ( bp_is_active( 'groups' ) ) {
					$group_directory_sidebar_array = array(
						'id'       => 'groups',
						'type'     => 'button_set',
						'title'    => esc_html__( 'Groups Directory Sidebar', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select the groups directory sidebar alignment.', 'buddyboss-theme' ),
						'default'  => 'right',
						'options'  => array(
							'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
							'right' => esc_html__( 'Right', 'buddyboss-theme' ),
						),
					);

					$single_group_sidebar_array = array(
						'id'       => 'group',
						'type'     => 'button_set',
						'title'    => esc_html__( 'Group Single Sidebar', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select the group single sidebar alignment.', 'buddyboss-theme' ),
						'default'  => 'right',
						'options'  => array(
							'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
							'right' => esc_html__( 'Right', 'buddyboss-theme' ),
						),
					);
				}
			}

			$forums_sidebar_array = array();
			if ( function_exists( 'is_bbpress' ) ) {
				$forums_sidebar_array = array(
					'id'       => 'forums',
					'type'     => 'button_set',
					'title'    => esc_html__( 'Forums Sidebar', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Select the forums sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'right',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);
			}

			if ( class_exists( 'WooCommerce' ) ) {
				$woocommerce_sidebar_array = array(
					'id'       => 'woocommerce',
					'type'     => 'button_set',
					'title'    => sprintf(
					/* translators: 1. Text, 2. Text */
						__( '%1$s &rarr; %2$s', 'buddyboss-theme' ),
						esc_html__( 'WooCommerce', 'buddyboss-theme' ),
						esc_html__( 'Shop Sidebar', 'buddyboss-theme' )
					),
					'subtitle' => esc_html__( 'Select the woocommerce sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'right',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);
			}

			if ( class_exists( 'SFWD_LMS' ) ) {
				$learndash_sidebar_array = array(
					'id'       => 'learndash',
					'type'     => 'button_set',
					'title'    => esc_html__( 'LearnDash Sidebar', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Select the learndash sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'right',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);

				$learndash_single_sidebar_array = array(
					'id'       => 'learndash_single_sidebar',
					'type'     => 'button_set',
					'title'    => esc_html__( 'LearnDash Single Pages Sidebar', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Select the learndash single pages sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'left',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);
			}

			if ( class_exists( 'LifterLMS' ) ) {
				$lifterlms_sidebar_array = array(
					'id'       => 'lifterlms',
					'type'     => 'button_set',
					'title'    => esc_html__( 'LifterLMS Sidebar', 'buddyboss-theme' ),
					'subtitle' => esc_html__( 'Select the LifterLMS sidebar alignment.', 'buddyboss-theme' ),
					'default'  => 'right',
					'options'  => array(
						'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
						'right' => esc_html__( 'Right', 'buddyboss-theme' ),
					),
				);
			}

			$search_sidebar_array = array(
				'id'       => 'search',
				'type'     => 'button_set',
				'title'    => esc_html__( 'Search Results Sidebar', 'buddyboss-theme' ),
				'subtitle' => esc_html__( 'Select the search results page sidebar alignment.', 'buddyboss-theme' ),
				'default'  => 'right',
				'options'  => array(
					'left'  => esc_html__( 'Left', 'buddyboss-theme' ),
					'right' => esc_html__( 'Right', 'buddyboss-theme' ),
				),
			);

			$sidebar_fields = array(
				array(
					'id'   => 'buddypress_sidebar_info',
					'type' => 'info',
					'desc' => esc_html__( 'Add widgets into your sidebars at Appearance &gt; Widgets', 'buddyboss-theme' ),
				),
			);

			if ( ! empty( $sidebar_array ) ) {
				$sidebar_fields[] = $sidebar_array;
			}
			if ( ! empty( $page_sidebar_array ) ) {
				$sidebar_fields[] = $page_sidebar_array;
			}
			if ( ! empty( $activity_sidebar_array ) ) {
				$sidebar_fields[] = $activity_sidebar_array;
			}
			if ( ! empty( $members_sidebar_array ) ) {
				$sidebar_fields[] = $members_sidebar_array;
			}
			if ( ! empty( $profile_sidebar_array ) ) {
				$sidebar_fields[] = $profile_sidebar_array;
			}
			if ( ! empty( $group_directory_sidebar_array ) ) {
				$sidebar_fields[] = $group_directory_sidebar_array;
			}
			if ( ! empty( $single_group_sidebar_array ) ) {
				$sidebar_fields[] = $single_group_sidebar_array;
			}
			if ( ! empty( $forums_sidebar_array ) ) {
				$sidebar_fields[] = $forums_sidebar_array;
			}
			if ( ! empty( $woocommerce_sidebar_array ) ) {
				$sidebar_fields[] = $woocommerce_sidebar_array;
			}
			if ( ! empty( $learndash_sidebar_array ) ) {
				$sidebar_fields[] = $learndash_sidebar_array;
			}
			if ( ! empty( $learndash_single_sidebar_array ) ) {
				$sidebar_fields[] = $learndash_single_sidebar_array;
			}
			if ( ! empty( $lifterlms_sidebar_array ) ) {
				$sidebar_fields[] = $lifterlms_sidebar_array;
			}
			if ( ! empty( $search_sidebar_array ) ) {
				$sidebar_fields[] = $search_sidebar_array;
			}

			// Sidebar Settings.
			$this->sections[] = array(
				'title'      => esc_html__( 'Sidebars', 'buddyboss-theme' ),
				'icon'       => 'bb-icon-l bb-icon-columns',
				'customizer' => false,
				'priority'   => 20,
				'fields'     => $sidebar_fields,
			);

			// Prepare array of social options for Footer section.
			$social_options = array(
				'clubhouse' => '',
				'dribbble'  => '',
				'email'     => '',
				'facebook'  => '',
				'flickr'    => '',
				'github'    => '',
				'instagram' => '',
				'linkedin'  => '',
				'medium'    => '',
				'meetup'    => '',
				'pinterest' => '',
				'quora'     => '',
				'reddit'    => '',
				'rss'       => '',
				'skype'     => '',
				'telegram'  => '',
				'tiktok'    => '',
				'tumblr'    => '',
				'twitter'   => '',
				'vimeo'     => '',
				'vk'        => '',
				'xing'      => '',
				'youtube'   => '',
				'x'         => '',
			);
			// Social link options.
			$social_options = apply_filters( 'buddyboss_social_options', $social_options );
			$footer_options = array(
				'1' => array(
					'alt' => 'Footer style 1',
					'img' => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/footers/footer-1.png' ),
				),
				'2' => array(
					'alt' => 'Footer style 2',
					'img' => esc_url( get_template_directory_uri() . '/inc/admin/assets/images/footers/footer-2.png' ),
				),
			);
			// Menu List.
			$menus      = wp_get_nav_menus();
			$menu_items = array();
			foreach ( $menus as $menu ) :
				$menu_items[ $menu->slug ] = $menu->name;
			endforeach;
			// Footer Settings.
			$this->sections[] = array(
				'title'      => esc_html__( 'Footer', 'buddyboss-theme' ),
				'icon'       => 'bb-icon-l bb-icon-maximize bb-flipped',
				'customizer' => false,
				'priority'   => 20,
				'fields'     => array(
					array(
						'id'   => 'footer_widgets_options',
						'type' => 'info',
						'desc' => esc_html__( 'Footer Widget Columns', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'footer_widgets',
						'type'     => 'switch',
						'title'    => esc_html__( 'Footer Widget Columns', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Add a section of widget columns into the footer.', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'footer_widget_columns',
						'type'     => 'select',
						'title'    => esc_html__( 'Number of Columns', 'buddyboss-theme' ),
						'subtitle' => sprintf(
						/* translators: 1. HTML tag start. 2. HTML tag close. */
							__( 'Select the number of widget columns to be generated at %1$sAppearance &gt; Widgets%2$s.', 'buddyboss-theme' ),
							'<em>',
							'</em>',
						),
						'options'  => array(
							'1' => __('1 Column', 'buddyboss-theme' ),
							'2' => __('2 Column', 'buddyboss-theme' ),
							'3' => __('3 Column', 'buddyboss-theme' ),
							'4' => __('4 Column', 'buddyboss-theme' ),
							'5' => __('5 Column', 'buddyboss-theme' ),
							'6' => __('6 Column', 'buddyboss-theme' ),
						),
						'default'  => '4',
						'required' => array( 'footer_widgets', 'equals', '1' ),
					),
					array(
						'id'   => 'footer_bottom_options',
						'type' => 'info',
						'desc' => esc_html__( 'Footer Info Section', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'footer_copyright',
						'type'     => 'switch',
						'title'    => esc_html__( 'Footer Info Section', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Add a section for displaying information in the footer, including your logo, menus, copyright, etc.', 'buddyboss-theme' ),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'         => 'footer_style',
						'title'      => esc_html__( 'Footer Style', 'buddyboss-theme' ),
						'subtitle'   => esc_html__( 'Select the layout of your footer info section.', 'buddyboss-theme' ),
						'type'       => 'image_select',
						'customizer' => false,
						'default'    => '1',
						'options'    => $footer_options,
						'required'   => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_left_content_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Left Side Content', 'buddyboss-theme' ),
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_logo',
						'title'    => esc_html__( 'Footer Logo', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Upload your custom site logo for footer layout.', 'buddyboss-theme' ),
						'type'     => 'media',
						'url'      => false,
						'required' => array( 'footer_style', 'equals', '2' ),
						'class'    => 'footer-logo bbThumbScale bbThumbScaleFl',
					),
					array(
						'id'       => 'footer_logo_size',
						'type'     => 'slider',
						'title'    => esc_html__( 'Footer Logo Size', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Adjust the size of your footer logo', 'buddyboss-theme' ),
						'desc'     => sprintf(
						/* translators: 1. Break the current line. */
							esc_html__( 'Maximum logo width 350px.%sIf the logo size is taller than the footer height, it will be made smaller to fit within the footer.', 'buddyboss-theme' ),
							'<br />'
						),
						'default'  => '0',
						'min'      => 0,
						'step'     => 1,
						'max'      => 350,
						'class'    => 'bbThumbSlide bbThumbSlideFl',
						'required' => array( 'footer_style', 'equals', '2' ),
					),
					array(
						'id'       => 'footer_tagline',
						'title'    => esc_html__( 'Logo Tagline', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Add a tagline next to your logo.', 'buddyboss-theme' ),
						'type'     => 'text',
						'required' => array( 'footer_style', 'equals', '2' ),
					),
					array(
						'id'       => 'footer_left_content_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Left Side Content', 'buddyboss-theme' ),
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_menu',
						'type'     => 'select',
						'title'    => esc_html__( 'Footer Primary Menu', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select a navigation menu from the dropdown.', 'buddyboss-theme' ),
						'options'  => $menu_items,
						'default'  => '',
						'required' => array( 'footer_style', 'equals', '1' ),
					),
					array(
						'id'       => 'copyright_text',
						'type'     => 'editor',
						'title'    => esc_html__( 'Copyright Notice', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Use the shortcode [buddyboss_current_year] to always display the current year before your copyright notice.', 'buddyboss-theme' ),
						'default'  => '&copy; [buddyboss_current_year] - ' . get_bloginfo( 'name' ),
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 6,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_secondary_menu',
						'type'     => 'select',
						'title'    => esc_html__( 'Footer Menu', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select a navigation menu from the dropdown.', 'buddyboss-theme' ),
						'options'  => $menu_items,
						'default'  => '',
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_right_content_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Right Side Content', 'buddyboss-theme' ),
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'footer_description',
						'type'     => 'editor',
						'title'    => esc_html__( 'Footer Description', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter text or shortcode to display in your footer.', 'buddyboss-theme' ),
						'default'  => '',
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 4,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
						'required' => array( 'footer_copyright', 'equals', '1' ),
					),
					array(
						'id'       => 'boss_footer_social_links',
						'type'     => 'sortable',
						'title'    => esc_html__( 'Social Links', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter URLs to social links to display as icons in the footer.', 'buddyboss-theme' ),
						'label'    => true,
						'required' => array( 'footer_copyright', 'equals', '1' ),
						'options'  => $social_options,
					),
				),
			);

			// WordPress Login.
			$this->sections[] = array(
				'title'      => esc_html__( 'Login &amp; Registration', 'buddyboss-theme' ),
				'id'         => 'admin_login',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-lock-alt',
				'fields'     => array(
					array(
						'id'       => 'boss_custom_login',
						'type'     => 'switch',
						'title'    => esc_html__( 'Custom Login/Register Screen', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Toggle the custom login/register screen design options on or off.', 'buddyboss-theme' ),
						'default'  => true,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'admin_logo_media',
						'type'     => 'media',
						'title'    => esc_html__( 'Custom Logo', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'We display a custom logo in place of the default WordPress logo.', 'buddyboss-theme' ),
						'url'      => false,
						'required' => array( 'boss_custom_login', 'equals', '1' ),
						'class'    => 'bbThumbScale bbThumbScaleLr',
					),
					array(
						'id'            => 'admin_logo_width',
						'type'          => 'slider',
						'title'         => esc_html__( 'Logo Width', 'buddyboss-theme' ),
						'subtitle'      => esc_html__( 'Set logo width size', 'buddyboss-theme' ),
						'desc'          => esc_html__( 'Value between 50 and 320px', 'buddyboss-theme' ),
						'default'       => 145,
						'min'           => 50,
						'step'          => 1,
						'max'           => 320,
						'display_value' => '145px',
						'required'      => array( 'boss_custom_login', 'equals', '1' ),
						'class'         => 'bbThumbSlide bbThumbSlideLr',
					),
					array(
						'id'       => 'admin_login_background_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Toggle Custom Background', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Set custom background design on or off.', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'required' => array( 'boss_custom_login', 'equals', '1' ),
					),
					array(
						'id'       => 'admin_login_background_media',
						'type'     => 'media',
						'title'    => esc_html__( 'Background Image', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'We display a custom background image in half width of the screen.', 'buddyboss-theme' ),
						'url'      => false,
						'required' => array( 'admin_login_background_switch', 'equals', '1' ),
					),
					array(
						'id'       => 'admin_login_background_text',
						'type'     => 'text',
						'title'    => esc_html__( 'Custom Heading', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'We display a custom title above the background image.', 'buddyboss-theme' ),
						'msg'      => esc_html__( 'Custom login heading', 'buddyboss-theme' ),
						'default'  => '',
						'required' => array( 'admin_login_background_switch', 'equals', '1' ),
					),
					array(
						'id'           => 'admin_login_background_textarea',
						'type'         => 'textarea',
						'title'        => esc_html__( 'Custom Text', 'buddyboss-theme' ),
						'subtitle'     => esc_html__( 'We display custom text above the background image.', 'buddyboss-theme' ),
						'validate'     => 'html_custom',
						'default'      => '',
						'required'     => array( 'admin_login_background_switch', 'equals', '1' ),
						'allowed_html' => array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'br'     => array(),
							'em'     => array(),
							'strong' => array(),
						),
					),
					array(
						'id'            => 'admin_login_overlay_opacity',
						'type'          => 'slider',
						'title'         => esc_html__( 'Overlay Opacity', 'buddyboss-theme' ),
						'subtitle'      => esc_html__( 'Set overlay opacity', 'buddyboss-theme' ),
						'desc'          => esc_html__( 'Value between 0 and 100%', 'buddyboss-theme' ),
						'default'       => 30,
						'min'           => 0,
						'step'          => 10,
						'max'           => 100,
						'display_value' => '30%',
						'required'      => array( 'admin_login_background_switch', 'equals', '1' ),
					),
					array(
						'id'       => 'admin_login_heading_color',
						'type'     => 'color',
						'title'    => esc_html__( 'Custom Heading Color', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select your text color for custom heading section.', 'buddyboss-theme' ),
						'default'  => '#FFFFFF',
						'validate' => 'color',
						'required' => array( 'admin_login_background_switch', 'equals', '1' ),
					),
				),
			);

			// Blog section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Blog', 'buddyboss-theme' ),
				'id'         => 'blog',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-article',
				'fields'     => array(
					array(
						'id'   => 'blog_layout',
						'type' => 'info',
						'desc' => esc_html__( 'Blog Directories', 'buddyboss-theme' ),
					),
					array(
						'id'         => 'blog_archive_layout',
						'title'      => esc_html__( 'Directory Layout', 'buddyboss-theme' ),
						'subtitle'   => esc_html__( 'Select the layout of posts in your blog directories and archives.', 'buddyboss-theme' ),
						'desc'       => esc_html__( 'In the Grid layout, the first post will be enlarged.', 'buddyboss-theme' ),
						'type'       => 'image_select',
						'customizer' => false,
						'default'    => 'standard',
						'options'    => array(
							'standard' => array(
								'alt' 	=> 'List',
								'title' => esc_html__( 'List', 'buddyboss-theme' ),
								'img' 	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/blog-standard.png' ),
							),
							'masonry'  => array(
								'alt' 	=> 'Masonry',
								'title'	=> esc_html__( 'Masonry', 'buddyboss-theme' ),
								'img'	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/blog-masonry.png' ),
							),
							'grid'     => array(
								'alt'	=> 'Grid',
								'title'	=> esc_html__( 'Grid', 'buddyboss-theme' ),
								'img'	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/blog-grid.png' ),
							),
						),
					),
					array(
						'id'   => 'blog_single_blog',
						'type' => 'info',
						'desc' => esc_html__( 'Single Post', 'buddyboss-theme' ),
					),
					array(
						'id'         => 'blog_featured_img',
						'title'      => esc_html__( 'Featured Image', 'buddyboss-theme' ),
						'subtitle'   => esc_html__(
							'Select the style of featured images in your blog posts.',
							'buddyboss-theme'
						),
						'desc'       => esc_html__( 'In the Full Width layouts, the sidebar will not be visible.', 'buddyboss-theme' ),
						'type'       => 'image_select',
						'customizer' => false,
						'default'    => 'default-fi',
						'options'    => array(
							'default-fi'     => array(
								'alt'	=> 'Default',
								'title' => esc_html__( 'Standard', 'buddyboss-theme' ),
								'img'	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/featured-img-default.png' ),
							),
							'full-fi'        => array(
								'alt'	=> 'Fullwidth',
								'title'	=> esc_html__( 'Full Width (Below Title)', 'buddyboss-theme' ),
								'img'	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/blog-title-top.png' ),
							),
							'full-fi-invert' => array(
								'alt'	=> 'Fullwidth (Title below)',
								'title'	=> esc_html__( 'Full Width (Above Title)', 'buddyboss-theme' ),
								'img'	=> esc_url( get_template_directory_uri() . '/inc/admin/assets/images/blog/blog-title-bottom.png' ),
							),
						),
					),
					// Added for line seprator
					array(
						'id'   => 'blog_featured_img_seprator',
						'type' => 'info',
					),
					array(
						'id'       => 'blog_related_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Related Posts', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show related posts under single blog posts.', 'buddyboss-theme' ),
						'default'  => true,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'blog_related_posts_limit',
						'type'     => 'text',
						'desc'     => esc_html__( 'Enter the maximum number of related posts to show.', 'buddyboss-theme' ),
						'validate' => 'numeric',
						'msg'      => 'Set number of related posts',
						'default'  => '5',
						'required' => array( 'blog_related_switch', 'equals', '1' ),
					),
					// Added for line seprator
					array(
						'id'   => 'blog_related_posts_limit_seprator',
						'type' => 'info',
					),
					array(
						'id'       => 'blog_author_box',
						'type'     => 'switch',
						'title'    => esc_html__( 'Post Author Box', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show information about the post’s author below the post.', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					// Added for line seprator
					array(
						'id'   => 'blog_author_box_seprator',
						'type' => 'info',
					),
					array(
						'id'       => 'blog_share_box',
						'type'     => 'switch',
						'title'    => esc_html__( 'Floating Social Share', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show floating icons for sharing the post on Facebook and Twitter.', 'buddyboss-theme' ),
						'desc'     => esc_html__( 'Social share icons will be visible underneath the post, even when the floating icons are disabled.', 'buddyboss-theme' ),
						'default'  => true,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					// Added for line seprator
					array(
						'id'   => 'blog_share_box_seprator',
						'type' => 'info',
					),
					array(
						'id'       => 'blog_platform_author_link',
						'type'     => 'switch',
						'title'    => esc_html__( 'BuddyBoss Profile Link', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Link the post author’s name to their BuddyBoss profile.', 'buddyboss-theme' ),
						'desc'     => esc_html__( 'When disabled, the post author’s name will link to an archive displaying all their posts.', 'buddyboss-theme' ),
						'default'  => true,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					// Added for line seprator
					array(
						'id'   => 'blog_platform_author_link_seprator',
						'type' => 'info',
					),
					array(
						'id'       => 'blog_newsletter_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Call to Action / Sign Up Form', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show a call to action, such as a sign up form or advertisement, under the blog post.', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'blog_shortcode',
						'type'     => 'editor',
						'desc'     => esc_html__( 'Enter the content for the call to action. You can add shortcodes here.', 'buddyboss-theme' ),
						'validate' => 'html_custom',
						'default'  => '',
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 10,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
						'required' => array( 'blog_newsletter_switch', 'equals', '1' ),
					),
				),
			);

			// bbPress Forums section.
			if ( function_exists( 'is_bbpress' ) ) {
				// bbPress Forums.
				$this->sections[] = array(
					'title'      => esc_html__( 'Forums', 'buddyboss-theme' ),
					'id'         => 'bbPress_forums',
					'customizer' => false,
					'priority'   => 20,
					'icon'       => 'bb-icon-l bb-icon-comments-square',
					'fields'     => array(
						array(
							'id'       => 'bbpress_forums_item_layout',
							'type'     => 'select',
							'title'    => esc_html__( 'Forum Grids', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Set forum grid layouts to Card or Cover style.', 'buddyboss-theme' ),
							'options'  => array(
								'card'  => esc_html__( 'Card', 'buddyboss-theme' ),
								'cover' => esc_html__( 'Cover', 'buddyboss-theme' ),
							),
							'default'  => 'card',
						),
						array(
							'id'       => 'bbpress_banner_switch',
							'type'     => 'switch',
							'title'    => esc_html__( 'Show Forum Banner', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'If enabled it will show a banner on the Forum index.', 'buddyboss-theme' ),
							'default'  => false,
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'bbpress_banner_image',
							'type'     => 'media',
							'title'    => esc_html__( 'Custom Banner Image', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'We display a custom banner on forum index page.', 'buddyboss-theme' ),
							'url'      => false,
							'required' => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
						array(
							'id'          => 'bbpress_banner_overlay',
							'type'        => 'color',
							'title'       => esc_html__( 'Background Overlay Color', 'buddyboss-theme' ),
							'subtitle'    => esc_html__( 'Select background overlay color for banner image.', 'buddyboss-theme' ),
							'default'     => '#007CFF',
							'validate'    => 'color',
							'transparent' => false,
							'required'    => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
						array(
							'id'            => 'bbpress_banner_overlay_opacity',
							'type'          => 'slider',
							'title'         => esc_html__( 'Background Overlay Opacity', 'buddyboss-theme' ),
							'subtitle'      => esc_html__( 'Set background overlay opacity', 'buddyboss-theme' ),
							'desc'          => esc_html__( 'Value between 0 and 100%', 'buddyboss-theme' ),
							'default'       => 40,
							'min'           => 0,
							'step'          => 10,
							'max'           => 100,
							'display_value' => '40%',
							'required'      => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
						array(
							'id'          => 'bbpress_banner_text',
							'type'        => 'color',
							'title'       => esc_html__( 'Banner Text Color', 'buddyboss-theme' ),
							'subtitle'    => esc_html__( 'Select text color for banner area.', 'buddyboss-theme' ),
							'default'     => '#ffffff',
							'validate'    => 'color',
							'required'    => array( 'bbpress_banner_switch', 'equals', '1' ),
							'transparent' => false,
						),
						array(
							'id'       => 'bbpress_banner_title',
							'type'     => 'text',
							'title'    => esc_html__( 'Forum Title', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Title that will be shown on forum index banner area.', 'buddyboss-theme' ),
							'msg'      => 'Forum Title',
							'default'  => '',
							'required' => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
						array(
							'id'           => 'bbpress_banner_description',
							'type'         => 'textarea',
							'title'        => esc_html__( 'Forum Description', 'buddyboss-theme' ),
							'subtitle'     => esc_html__( 'Description that will be shown on forum index banner area.', 'buddyboss-theme' ),
							'validate'     => 'html_custom',
							'default'      => esc_html__( 'Find answers, ask questions, and connect with our <br>community around the world.', 'buddyboss-theme' ),
							'allowed_html' => array(
								'a'      => array(
									'href'  => array(),
									'title' => array(),
								),
								'br'     => array(),
								'em'     => array(),
								'strong' => array(),
							),
							'required'     => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
						array(
							'id'       => 'bbpress_banner_search',
							'type'     => 'switch',
							'title'    => esc_html__( 'Enable Search', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'If enabled search will show on banner.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
							'required' => array( 'bbpress_banner_switch', 'equals', '1' ),
						),
					),
				);
			}

			// LearnDash pages section.
			if ( class_exists( 'SFWD_LMS' ) ) {
				// LearnDash pages.
				$this->sections[] = array(
					'title'      => esc_html__( 'LearnDash', 'buddyboss-theme' ),
					'id'         => 'learndash',
					'customizer' => false,
					'priority'   => 20,
					'icon'       => 'bb-icon-l bb-icon-brand-learndash',
					'fields'     => array(
						array(
							'id'   => 'learndash_course_archive',
							'type' => 'info',
							'desc' => esc_html__( 'Courses Index', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'learndash_course_index_show_categories_filter',
							'type'     => 'switch',
							'title'    => esc_html__( 'Show Categories Filter', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Enable filtering the courses index by categories.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'learndash_course_index_categories_filter_taxonomy',
							'type'     => 'select',
							'title'    => esc_html__( 'Taxonomy', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Select the taxonomy to filter by.', 'buddyboss-theme' ),
							'options'  => array(
								'ld_course_category' => esc_html__( 'Course Categories', 'buddyboss-theme' ),
								'ld_course_tag'      => esc_html__( 'Course Tags', 'buddyboss-theme' ),
							),
							'default'  => 'ld_course_category',
							'required' => array( 'learndash_course_index_show_categories_filter', 'equals', '1' ),
						),
						array(
							'id'       => 'learndash_course_index_show_instructors_filter',
							'type'     => 'switch',
							'title'    => esc_html__( 'Show Instructors Filter', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Enable filtering the courses index by instructors.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'   => 'learndash_course_archive',
							'type' => 'info',
							'desc' => esc_html__( 'Course Content', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'learndash_course_author',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Author', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the course author on courses, lessons and topics.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'learndash_course_author_info',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Author Bio', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the course author bio on single courses. This data comes from the "Biographical Info" section when editing a user in the backend.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
							'required' => array( 'learndash_course_author', 'equals', '1' ),
						),
						array(
							'id'       => 'learndash_course_date',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Date', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the course date on courses, lessons and topics.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'learndash_course_participants',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Participants', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the list of enrolled course participants on courses, lessons and topics.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
					),
				);
			}

			// LifterLMS section.
			if ( class_exists( 'LifterLMS' ) ) {
				// LifterLMS pages.
				$this->sections[] = array(
					'title'      => esc_html__( 'LifterLMS', 'buddyboss-theme' ),
					'id'         => 'lifterlms',
					'customizer' => false,
					'priority'   => 20,
					'icon'       => 'bb-icon-l bb-icon-brand-lifterlms',
					'fields'     => array(
						array(
							'id'   => 'lifterlms_course_archive',
							'type' => 'info',
							'desc' => esc_html__( 'Courses Index', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_course_index_show_categories_filter',
							'type'     => 'switch',
							'title'    => esc_html__( 'Show Categories Filter', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Enable filtering the courses index by categories.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_course_index_categories_filter_taxonomy',
							'type'     => 'select',
							'title'    => esc_html__( 'Taxonomy', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Select the taxonomy to filter by.', 'buddyboss-theme' ),
							'options'  => array(
								'llms_course_category'   => esc_html__( 'Course Categories', 'buddyboss-theme' ),
								'llms_course_tag'        => esc_html__( 'Course Tags', 'buddyboss-theme' ),
								'llms_course_tracks'     => esc_html__( 'Course Tracks', 'buddyboss-theme' ),
								'llms_course_difficulty' => esc_html__( 'Course Difficulty', 'buddyboss-theme' ),
							),
							'default'  => 'llms_course_category',
							'required' => array( 'lifterlms_course_index_show_categories_filter', 'equals', '1' ),
						),
						array(
							'id'       => 'lifterlms_course_index_show_instructors_filter',
							'type'     => 'switch',
							'title'    => esc_html__( 'Show Instructors Filter', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Enable filtering the courses index by instructors.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'   => 'lifterlms_course_archive',
							'type' => 'info',
							'desc' => esc_html__( 'Course Content', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_course_author',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Author', 'buddyboss-theme' ),
							'subtitle' => esc_html__(
								'Display the course author on courses, lessons and topics.',
								'buddyboss-theme'
							),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_course_date',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Date', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the course date on courses, lessons and topics.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_lesson_list',
							'type'     => 'switch',
							'title'    => esc_html__( 'Lessons List', 'buddyboss-theme' ),
							'subtitle' => esc_html__( 'Display the lessons list on lessons, quizzes and topics.', 'buddyboss-theme' ),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
						array(
							'id'       => 'lifterlms_course_participant',
							'type'     => 'switch',
							'title'    => esc_html__( 'Course Participants', 'buddyboss-theme' ),
							'subtitle' => esc_html__(
								'Display the list of enrolled course participants on courses, lessons and quizzes.',
								'buddyboss-theme'
							),
							'default'  => '1',
							'on'       => esc_html__( 'On', 'buddyboss-theme' ),
							'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						),
					),
				);
			}

			// 404 Page section.
			$this->sections[] = array(
				'title'      => esc_html__( '404 Page', 'buddyboss-theme' ),
				'id'         => '404_page',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-exclamation-triangle',
				'fields'     => array(
					array(
						'id'       => '404_title',
						'type'     => 'text',
						'title'    => esc_html__( 'Page Title', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter a title for your 404 page.', 'buddyboss-theme' ),
						'msg'      => esc_html__( 'Custom login heading', 'buddyboss-theme' ),
						'default'  => esc_html__( 'Looks like you got lost!', 'buddyboss-theme' ),
					),
					array(
						'id'           => '404_desc',
						'type'         => 'textarea',
						'title'        => esc_html__( 'Page Description', 'buddyboss-theme' ),
						'subtitle'     => esc_html__( 'Enter a description for your 404 page.', 'buddyboss-theme' ),
						'validate'     => 'html_custom',
						'default'      => __( "We couldn't find the page you were looking for.", 'buddyboss-theme' ),
						'allowed_html' => array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'br'     => array(),
							'em'     => array(),
							'strong' => array(),
						),
					),
					array(
						'id'       => '404_featured_image',
						'type'     => 'button_set',
						'title'    => esc_html__( 'Featured Image', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Select which type of image to use on your 404 page.', 'buddyboss-theme' ),
						'default'  => 'theme_2_0',
						'options'  => array(
							'theme_2_0' => esc_html__( '2.0', 'buddyboss-theme' ),
							'theme_1_0' => esc_html__( '1.0', 'buddyboss-theme' ),
							'custom'    => esc_html__( 'Custom', 'buddyboss-theme' ),
							'none'      => esc_html__( 'None', 'buddyboss-theme' ),
						),
					),
					array(
						'id'         => '404_theme_2_0_image',
						'type'       => 'image',
						'image_url'  => bb_theme_get_404_svg_code( 2 ),
						'image_type' => 'svg',
						'image_desc' => __( 'The image will be tinted with your <b>Primary Color</b>.', 'buddyboss-theme' ),
						'required'   => array( '404_featured_image', 'equals', 'theme_2_0' ),
					),
					array(
						'id'         => '404_theme_1_0_image',
						'type'       => 'image',
						'image_url'  => bb_theme_get_404_svg_code(),
						'image_type' => 'svg',
						'image_desc' => '',
						'required'   => array( '404_featured_image', 'equals', 'theme_1_0' ),
					),
					array(
						'id'       => '404_image',
						'type'     => 'media',
						'title'    => '',
						'url'      => false,
						'required' => array( '404_featured_image', 'equals', 'custom' ),
					),
					array(
						'id'       => '404_button_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Page Button', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show a button at the bottom of your 404 page.', 'buddyboss-theme' ),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => '404_button_text',
						'type'     => 'text',
						'title'    => esc_html__( 'Button Text', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter the text for your button.', 'buddyboss-theme' ),
						'default'  => esc_html__( 'Return Home', 'buddyboss-theme' ),
						'required' => array( '404_button_switch', 'equals', '1' ),
					),
					array(
						'id'       => '404_button_link',
						'type'     => 'text',
						'title'    => esc_html__( 'Button Link', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter the URL for your button.', 'buddyboss-theme' ),
						'default'  => esc_url( home_url( '/' ) ),
						'required' => array( '404_button_switch', 'equals', '1' ),
					),
				),
			);

			// Maintenance Mode section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Maintenance Mode', 'buddyboss-theme' ),
				'id'         => 'maintenance_page',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-tools',
				'fields'     => array(
					array(
						'id'      => 'maintenance_mode',
						'type'    => 'switch',
						'title'   => esc_html__( 'Enable Maintenance Mode', 'buddyboss-theme' ),
						'desc'    => esc_html__( 'If enabled, only site administrators will be able to login to your site. Logged-out members will see the maintenance mode page.', 'buddyboss-theme' ),
						'default' => false,
						'on'      => esc_html__( 'On', 'buddyboss-theme' ),
						'off'     => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'maintenance_desktop_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Page Content', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_title',
						'type'     => 'text',
						'title'    => esc_html__( 'Page Title', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter a title for your maintenance mode page.', 'buddyboss-theme' ),
						'msg'      => esc_html__( 'Custom Maintenance Title', 'buddyboss-theme' ),
						'default'  => esc_html__( 'Maintenance Mode', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_desc',
						'type'     => 'editor',
						'title'    => esc_html__( 'Page Description', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter a description for your maintenance mode page.', 'buddyboss-theme' ),
						'validate' => 'html_custom',
						'default'  => __( 'Undergoing scheduled maintenance. <br>Sorry for the inconvenience.', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 10,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
					),
					array(
						'id'       => 'maintenance_image_switch',
						'type'     => 'switch',
						'title'    => esc_html__( 'Featured Image', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show a featured image on your maintenance mode page.', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_image',
						'type'     => 'media',
						'url'      => false,
						'required' => array( 'maintenance_image_switch', 'equals', '1' ),
					),
					array(
						'id'       => 'contact_button_text',
						'type'     => 'editor',
						'title'    => esc_html__( 'Bottom Text', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter text for the bottom of your maintenance mode page.', 'buddyboss-theme' ),
						'default'  => sprintf(
						/* translators: 1. email url */
							__( 'Contact us at  %1$s', 'buddyboss-theme' ),
							sprintf(
							/* translators: 1. Link URL. 2. Link text. */
								'<a href="mailto:%1$s">%2$s</a>',
								get_option( 'admin_email' ),
								get_option( 'admin_email' )
							),
						),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 10,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
					),
					array(
						'id'       => 'maintenance_countdown_timer_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Countdown Timer', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_countdown',
						'type'     => 'switch',
						'title'    => esc_html__( 'Enable Countdown', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_time',
						'type'     => 'text',
						'msg'      => 'Back Online Date',
						'default'  => '',
						'desc'     => esc_html__( 'Enter the date the timer should count down to, using the following date format: MM/DD/YYYY', 'buddyboss-theme' ),
						'required' => array( 'maintenance_countdown', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_feature_content_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Featured Content', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_subscribe',
						'type'     => 'switch',
						'title'    => esc_html__( 'Enable Featured Content', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enable a featured content section on your maintenance mode page.', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_subscribe_title',
						'type'     => 'text',
						'title'    => esc_html__( 'Section Title', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Enter a title for your featured content section.', 'buddyboss-theme' ),
						'msg'      => esc_html__( 'Subscribe Title', 'buddyboss-theme' ),
						'required' => array( 'maintenance_subscribe', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_subscribe_shortcode',
						'type'     => 'editor',
						'title'    => esc_html__( 'Section Content', 'buddyboss-theme' ),
						'desc'     => esc_html__( 'You can use shortcodes in this content.', 'buddyboss-theme' ),
						'validate' => 'html_custom',
						'default'  => '',
						'required' => array( 'maintenance_subscribe', 'equals', '1' ),
						'args'     => array(
							'teeny'         => true,
							'textarea_rows' => 10,
							'media_buttons' => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,link,unlink,undo,redo',
							),
							'quicktags'     => false,
						),
					),
					array(
						'id'       => 'maintenance_social_links_options',
						'type'     => 'info',
						'desc'     => esc_html__( 'Social Links', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_social_networks',
						'type'     => 'switch',
						'title'    => esc_html__( 'Enable Social Links', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Show links to your social networks on your maintenance mode page.', 'buddyboss-theme' ),
						'default'  => false,
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
						'required' => array( 'maintenance_mode', 'equals', '1' ),
					),
					array(
						'id'       => 'maintenance_social_links',
						'type'     => 'sortable',
						'title'    => esc_html__( 'Social Links', 'buddyboss-theme' ),
						'label'    => true,
						'required' => array( 'maintenance_social_networks', 'equals', '1' ),
						'options'  => $social_options,
					),
				),
			);

			// Codes Settings section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Custom Codes', 'buddyboss-theme' ),
				'icon'       => 'bb-icon-l bb-icon-code',
				'customizer' => false,
				'priority'   => 20,
				'fields'     => array(
					array(
						'id'       => 'tracking',
						'type'     => 'switch',
						'title'    => esc_html__( 'Tracking Code', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Paste your Google Analytics (or other) tracking code here. This will be added before the closing of body tag.', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'boss_tracking_code',
						'type'     => 'ace_editor',
						'mode'     => 'plain_text',
						'theme'    => 'chrome',
						'required' => array( 'tracking', 'equals', '1' ),
					),
					array(
						'id'       => 'custom_css',
						'type'     => 'switch',
						'title'    => esc_html__( 'CSS', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Quickly add some CSS here to make design adjustments. It is a much better solution than manually editing the theme. You may also consider using a child theme.', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'boss_custom_css',
						'type'     => 'ace_editor',
						'mode'     => 'css',
						'validate' => 'css',
						'theme'    => 'chrome',
						'default'  => ".your-class {\n    color: blue;\n}",
						'required' => array( 'custom_css', 'equals', '1' ),
					),
					array(
						'id'       => 'custom_js',
						'type'     => 'switch',
						'title'    => esc_html__( 'JavaScript', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'Quickly add some JavaScript code here. It is a much better solution than manually editing the theme. You may also consider using a child theme.', 'buddyboss-theme' ),
						'default'  => '0',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'boss_custom_js',
						'type'     => 'ace_editor',
						'mode'     => 'javascript',
						'validate' => 'plain_text',
						'theme'    => 'chrome',
						'default'  => "jQuery( document ).ready( function(){\n    //Your codes start from here\n});",
						'required' => array( 'custom_js', 'equals', '1' ),
					),
				),
			);

			// Minify Assets section.
			$this->sections[] = array(
				'title'      => esc_html__( 'Minify Assets', 'buddyboss-theme' ),
				'id'         => 'optimizations',
				'customizer' => false,
				'priority'   => 20,
				'icon'       => 'bb-icon-l bb-icon-compress',
				'fields'     => array(
					array(
						'id'       => 'boss_minified_css',
						'type'     => 'switch',
						'title'    => esc_html__( 'Minify CSS', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'By default the theme loads stylesheets that are not minified. You can enable this setting to instead load minified and combined stylesheets.', 'buddyboss-theme' ),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
					array(
						'id'       => 'boss_minified_js',
						'type'     => 'switch',
						'title'    => esc_html__( 'Minify JavaScript', 'buddyboss-theme' ),
						'subtitle' => esc_html__( 'By default the theme loads scripts that are not minified. You can enable this setting to instead load minified and combined JS files.', 'buddyboss-theme' ),
						'default'  => '1',
						'on'       => esc_html__( 'On', 'buddyboss-theme' ),
						'off'      => esc_html__( 'Off', 'buddyboss-theme' ),
					),
				),
			);

			// Import / Export section.
			$this->sections[] = array(
				'title'    => esc_html__( 'Import / Export', 'buddyboss-theme' ),
				// 'desc'     => __( 'Import and Export your Boss theme settings from file, text or URL.', 'buddyboss-theme' ),
				'icon'     => 'bb-icon-l bb-icon-sync',
				'priority' => 20,
				'fields'   => array(
					array(
						'id'         => 'opt-import-export',
						'type'       => 'import_export',
						'full_width' => true,
					),
				),
			);
		}

		/**
		 * All the possible arguments for Boss.
		 * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
		 * */
		public function setArguments() {
			$theme      = wp_get_theme(); // For use with some settings. Not necessary.
			$this->args = array(
				// TYPICAL -> Change these values as you need/desire.
				'opt_name'             => 'buddyboss_theme_options',
				// This is where your data is stored in the database and also becomes your global variable name.
				'display_name'         => $theme->get( 'Name' ),
				// Name that appears at the top of your panel.
				'display_version'      => $theme->get( 'Version' ),
				// Version that appears at the top of your panel.
				'menu_type'            => 'submenu',
				// Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only).
				'allow_sub_menu'       => true,
				// Show the sections below the admin menu item or not.
				'menu_title'           => __( 'Theme Options', 'buddyboss-theme' ),
				'page_title'           => __( 'BuddyBoss Theme', 'buddyboss-theme' ),
				// https://console.developers.google.com/project/ Must be defined to add google fonts to the typography module.
				// Set to keep your google fonts updated weekly.
				'async_typography'     => false,
				// Use a asynchronous font on the front end or font string.
				// 'disable_google_fonts_link' => true,                    // Disable this in case you want to create your own google fonts loader.
				'admin_bar'            => false,
				// Show the panel pages on the admin bar.
				'global_variable'      => '',
				// Set a different name for your global variable other than the opt_name.
				'dev_mode'             => defined( 'WP_DEBUG' ) && true === WP_DEBUG ? true : false,
				// Show the time the page took to load, etc.
				'customizer'           => true,
				// Enable basic customizer support.
				'page_priority'        => null,
				// Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
				'page_parent'          => function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ? 'buddyboss-platform' : 'buddyboss-settings',
				// For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters.
				'page_permissions'     => 'manage_options',
				// Permissions needed to access the options panel.
				'menu_icon'            => '',
				// Specify a custom URL to an icon.
				'last_tab'             => '',
				// Force your panel to always open to a specific tab (by id).
				'page_icon'            => 'icon-themes',
				// Icon displayed in the admin panel next to your menu_title.
				'page_slug'            => 'buddyboss_theme_options',
				// Page slug used to denote the panel.
				'save_defaults'        => true,
				// On load save the defaults to DB before user clicks save or not.
				'default_show'         => false,
				// If true, shows the default value next to each field that is not the default value.
				'default_mark'         => '',
				// What to print by the field's title if the value shown is default. Suggested: *.
				'show_import_export'   => true,
				// Shows the Import/Export panel when not used as a field.
				// CAREFUL -> These options are for advanced use only.
				'transient_time'       => 60 * MINUTE_IN_SECONDS,
				'output'               => true,
				// Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output.
				'output_tag'           => true,
				// Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head.
				'footer_credit'        => ' ',
				// Disable the footer credit of Redux. Please leave if you can help it.
				'output_location'      => array( 'frontend', 'login' ),
				// For enqueue font and css for front side and login.

				// Updated template path to perform translation.
				// @todo Update the template path here when update the redux core.
				'templates_path'       => dirname( __FILE__ ) . '/redux-templates/panel/'
			);
			// SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
			$this->args['share_icons'][] = array(
				'url'   => 'https://www.youtube.com/c/BuddybossWP',
				'title' => esc_html__( 'Follow BuddyBoss on YouTube', 'buddyboss-theme' ),
				'icon'  => 'el-icon-youtube',
			);
			$this->args['share_icons'][] = array(
				'url'   => 'https://twitter.com/BuddyBossWP',
				'title' => esc_html__( 'Follow BuddyBoss on Twitter', 'buddyboss-theme' ),
				'icon'  => 'el-icon-twitter',
			);
			$this->args['share_icons'][] = array(
				'url'   => 'https://facebook.com/BuddyBossWP',
				'title' => esc_html__( 'Like BuddyBoss on Facebook', 'buddyboss-theme' ),
				'icon'  => 'el-icon-facebook',
			);
			// Panel Intro text -> before the form.
			if ( ! isset( $this->args['global_variable'] ) || $this->args['global_variable'] !== false ) {
				if ( ! empty( $this->args['global_variable'] ) ) {
					$v = $this->args['global_variable'];
				} else {
					$v = str_replace( '-', '_', $this->args['opt_name'] );
				}
				$this->args['intro_text'] = sprintf( __( '<p>To access any of your saved options from within your code you can use your global variable: <strong>$%1$s</strong></p>', 'buddyboss-theme' ), $v );
			} else {
				$this->args['intro_text'] = __( '<p>This text is displayed above the options panel. It isn\'t required, but more info is always better! The intro_text field accepts all HTML.</p>', 'buddyboss-theme' );
			}
		}

	}

	global $reduxConfig;
	$reduxConfig = new buddyboss_theme_Redux_Framework_config();
}
