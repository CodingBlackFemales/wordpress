<?php
/**
 * LearnDash Admin Pointers
 *
 * @since 3.0.0
 * @package LearnDash\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Pointers' ) ) {
	/**
	 * LearnDash Admin Pointers Class
	 *
	 * @since 3.0.0
	 */
	class Learndash_Admin_Pointers {
		/**
		 * Array of registered pointers
		 *
		 * @var array $pointers Current registered pointers.
		 */
		protected $pointers = array();

		/**
		 * Screen ID
		 *
		 * @var string
		 */
		public $screen_id;

		/**
		 * Register variables and start up plugin
		 *
		 * @since 3.0.0
		 *
		 * @param array $pointers Current pointers.
		 */
		public function __construct( $pointers = array() ) {
			if ( get_bloginfo( 'version' ) < '3.3' ) {
				return;
			}

			if ( ! empty( $pointers ) ) {
				$this->pointers = $pointers;
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 1000 );
		}

		/**
		 * Add pointers to the current screen if they were not dismissed
		 *
		 * @since 3.0.0
		 */
		public function admin_enqueue_scripts() {
			$this->register_pointers();
			$this->check_user_dismissed();

			if ( empty( $this->pointers ) ) {
				return;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );

			// Make sure some metaboxes can't be toggled off.
			wp_enqueue_script(
				'learndash-admin-pointer-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-pointers' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			global $_wp_admin_css_colors;
			$current_color = get_user_option( 'admin_color' );
			if ( ( ! empty( $current_color ) ) && ( isset( $_wp_admin_css_colors[ $current_color ] ) ) ) {
				$pointer_color = $_wp_admin_css_colors[ $current_color ]->colors[2];
			} else {
				$pointer_color = '#00a0d2';
			}

			wp_localize_script(
				'learndash-admin-pointer-script',
				'learndash_admin_pointers_data',
				array(
					'pointer_color' => $pointer_color,
					'pointers'      => $this->pointers,
				)
			);
		}

		/**
		 * Register the available pointers for the current screen
		 *
		 * @since 3.0.0
		 */
		public function register_pointers() {
			$this->screen_id = get_current_screen()->id;
			/**
			 * Filters screen pointers.
			 *
			 * @since 3.0.0
			 *
			 * @param array  $pointers  An array of screen pointer data.
			 * @param string $screen_id Current Screen id.
			 */
			$pointers = apply_filters( 'learndash_screen_pointers', array(), $this->screen_id );
			if ( ( ! empty( $pointers ) ) && ( is_array( $pointers ) ) ) {
				$screen_pointers = array();
				foreach ( $pointers as $ptr ) {
					$include_pointer = false;

					// Do we want to show on ALL pages?
					if ( empty( $ptr['screen'] ) ) {
						$include_pointer = true;
					} elseif ( ( is_string( $ptr['screen'] ) ) && ( $ptr['screen'] === $this->screen_id ) ) {
						$include_pointer = true;
					} elseif ( is_array( $ptr['screen'] ) ) {
						foreach ( $ptr['screen'] as $screen_id ) {
							if ( $screen_id === $this->screen_id ) {
								$include_pointer = true;
								break;
							}
						}
					}
					if ( true === $include_pointer ) {
						$options                       = array(
							'pointer_id' => $ptr['id'],
							'content'    => sprintf(
								'<h3> %s </h3> <p> %s </p>',
								$ptr['title'],
								$ptr['content']
							),
							'position'   => $ptr['position'],
						);
						$screen_pointers[ $ptr['id'] ] = array(
							'pointer_id' => $ptr['id'],
							'screen'     => $ptr['screen'],
							'target'     => $ptr['target'],
							'options'    => $options,
						);
					}
				}
				$this->pointers = $screen_pointers;
			}
		}

		/**
		 * Check pointers against dismissed user pointers.
		 *
		 * @since 3.0.0
		 */
		protected function check_user_dismissed() {
			if ( ! $this->pointers || ! is_array( $this->pointers ) ) {
				return;
			}

			if ( isset( $_GET['ld_reset_pointers'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				delete_user_meta( get_current_user_id(), 'dismissed_wp_pointers' );
			}

			// Get dismissed pointers.
			$get_dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
			$dismissed     = explode( ',', (string) $get_dismissed );

			// Check pointers and remove dismissed ones.
			foreach ( $this->pointers as $pointer_id => $pointer ) {
				if ( ( in_array( $pointer_id, $dismissed, true ) ) || ( empty( $pointer ) ) || ( empty( $pointer_id ) ) || ( empty( $pointer['target'] ) ) || ( empty( $pointer['options'] ) ) ) {
					unset( $this->pointers[ $pointer_id ] );
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_admin_init',
	function() {
		new Learndash_Admin_Pointers();
	}
);

add_filter(
	'learndash_screen_pointers',
	function( $pointers = array(), $screen_id = '' ) {

		$ld_prior_version = learndash_data_upgrades_setting( 'prior_version' );

		if ( learndash_is_admin_user() ) {
			if ( 'new' === $ld_prior_version ) {
				if ( ! isset( $pointers['learndash-new-install'] ) ) {

					$setup_page_slug = LearnDash_Settings_Page_Setup::SETUP_SLUG;

					if ( learndash_cloud_is_enabled() ) {
						$setup_page_slug = LearnDash_Settings_Page_Setup::SETUP_SLUG_CLOUD;
					}

					$pointers['learndash-new-install'] = array(
						'id'       => 'learndash-new-install',
						'screen'   => '',
						'target'   => '#toplevel_page_learndash-lms .wp-menu-name',
						'title'    => '<span id="ld-pointer-title-learndash-new-install" class="ld-pointer-title">' . esc_html__( 'First time using LearnDash?', 'learndash' ) . '</span>',
						'content'  => '<span class="ld-pointer-content">' . sprintf(
							// translators: placeholder: Link to Setup page.
							esc_html_x( 'Go to the LearnDash %s', 'placeholder: Link to Setup page', 'learndash' ),
							'<a href="' . admin_url( 'admin.php?page=' ) . $setup_page_slug . '">' . esc_html__( 'setup', 'learndash' ) . '</a>'
						) . '</span>',
						'position' => array(
							'edge'  => is_rtl() ? 'right' : 'left', // top, bottom, left, right.
							'align' => 'middle', // top, bottom, left, right, middle.
						),
					);
				}
			}
		}

		return $pointers;
	},
	10,
	2
);
