<?php
/**
 * BuddyBoss Custom Fonts Admin Ui
 *
 * @since  1.2.10
 * @package BuddyBoss_Custom_Fonts
 */

namespace BuddyBossTheme;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\BuddyBossTheme\BuddyBoss_Custom_Fonts_Admin' ) ) :

	/**
	 * BuddyBoss_Custom_Fonts_Admin
	 */
	class BuddyBoss_Custom_Fonts_Admin {

		/**
		 * Instance of BuddyBoss_Custom_Fonts_Admin
		 *
		 * @since  1.2.10
		 * @var (Object) BuddyBoss_Custom_Fonts_Admin
		 */
		private static $_instance = null;

		/**
		 * Parent Menu Slug
		 *
		 * @since  1.2.9
		 * @var (string) $parent_menu_slug
		 */
		protected $parent_menu_slug = 'buddyboss-settings';

		/**
		 * Instance of BuddyBoss_Custom_Fonts_Admin.
		 *
		 * @return object Class object.
		 * @since  1.2.10
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.2.10
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_custom_fonts_menu' ), 11 );
			add_action( 'admin_head-post.php', array( $this, 'highlight_menu_item' ) );

			add_filter( 'redux/buddyboss_theme_options/field/bb_typography/custom_fonts', array( $this, 'add_typography_field_custom_fonts' ) );

			add_filter( 'upload_mimes', array( $this, 'add_fonts_to_allowed_mimes' ) );
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'update_mime_types' ), 10, 3 );
        }

		/**
		 * Register custom font menu
		 *
		 * @since 1.0.0
		 */
		public function register_custom_fonts_menu() {
			$title = apply_filters( THEME_HOOK_PREFIX . 'custom_fonts_menu_title', __( 'Custom Fonts', 'buddyboss-theme' ) );
			add_submenu_page(
				$this->parent_menu_slug,
				$title,
				$title,
				BuddyBoss_Custom_Fonts_CPT::$capability,
				'edit.php?post_type=' . BuddyBoss_Custom_Fonts_CPT::$register_cpt_slug
			);
		}

		/**
		 * Register custom font menu
		 *
		 * @since 1.0.0
		 */
		public function highlight_menu_item() {
			global $current_screen;

			// Not our post type, exit earlier
			if ( 'buddyboss_fonts' != $current_screen->post_type ) {
				return;
			}

			?>
			<script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var reference = $('li.current').closest('li.wp-has-submenu');

                    // add highlighting to our custom submenu
                    reference.addClass('wp-has-current-submenu').addClass('wp-menu-open').removeClass('wp-not-current-submenu');
                    reference.find('>a').addClass('wp-has-current-submenu').addClass('wp-menu-open').removeClass('wp-not-current-submenu');
                });
			</script>
			<?php
		}

		/**
		 * Add custom fonts in redux's typography field
		 *
		 * @since 1.3.0
		 * @param $fonts
		 *
		 * @return mixed
		 */
		function add_typography_field_custom_fonts( $fonts ) {
			$loaded_fonts = BuddyBoss_Custom_Fonts_CPT::get_fonts();
			$custom_fonts = array();

			foreach ( $loaded_fonts as $font_id => $font_data ) {
				if ( ! empty( $font_data['name'] ) ) {
					$custom_fonts[ $font_data['name'] ] = array();

					$custom_fonts[ $font_data['name'] ]['variants'] = array();
					foreach( $font_data['font_face'] as $font_face ) {
						if ( ! empty( $font_face['woff']['url'] ) || ! empty( $font_face['woff2']['url'] ) || ! empty( $font_face['ttf']['url'] ) ) {
						    switch( $font_face['font_weight'] ) {
                                case '100':
                                    $font_weight = __( 'Thin 100', 'buddyboss-theme' );
                                    break;
                                case '200':
	                                $font_weight = __( 'Extra Light 200', 'buddyboss-theme' );
	                                break;
                                case '300':
	                                $font_weight = __( 'Light 300', 'buddyboss-theme' );
	                                break;
                                case '400':
	                                $font_weight = __( 'Normal 400', 'buddyboss-theme' );
	                                break;
                                case '500':
	                                $font_weight = __( 'Medium 500', 'buddyboss-theme' );
	                                break;
                                case '600':
	                                $font_weight = __( 'Semi Bold 600', 'buddyboss-theme' );
	                                break;
                                case '700':
	                                $font_weight = __( 'Bold 700', 'buddyboss-theme' );
	                                break;
                                case '800':
	                                $font_weight = __( 'Extra Bold 800', 'buddyboss-theme' );
	                                break;
                                case '900':
	                                $font_weight = __( 'Black 900', 'buddyboss-theme' );
	                                break;
                                default:
	                                $font_weight = __( 'Normal', 'buddyboss-theme' );
	                                break;
                            }

                            $font_style = '';
						    if ( 'normal' !== $font_face['font_style'] ) {
							    $font_style = ucwords( $font_face['font_style'] );
                            }
							$custom_fonts[ $font_data['name'] ]['variants'][$font_face['font_weight'].$font_face['font_style']] = $font_weight . ' ' . $font_style;
						}
					}

					if ( empty( $custom_fonts[ $font_data['name'] ]['variants'] ) ) {
						unset( $custom_fonts[ $font_data['name'] ] );
					}
				}
			}

			if ( ! empty( $custom_fonts ) ) {
				$fonts[ __( 'Custom Fonts', 'buddyboss-theme' ) ] = $custom_fonts;
			}

			return $fonts;
		}

		/**
		 * Allowed mime types and file extensions
		 *
		 * @since 1.3.0
		 * @param array $mimes Current array of mime types.
		 * @return array $mimes Updated array of mime types.
		 */
		public function add_fonts_to_allowed_mimes( $mimes ) {
			$mimes['woff']  = 'application/x-font-woff';
			$mimes['woff2'] = 'application/x-font-woff2';
			$mimes['ttf']   = 'application/x-font-ttf';

			return $mimes;
		}

		/**
		 * Correct the mome types and extension for the font types.
		 *
		 * @since 1.3.0
		 *
		 * @param array  $defaults File data array containing 'ext', 'type', and
		 *                                          'proper_filename' keys.
		 * @param string $file                      Full path to the file.
		 * @param string $filename                  The name of the file (may differ from $file due to
		 *                                          $file being in a tmp directory).
		 * @return Array File data array containing 'ext', 'type', and
		 */
		public function update_mime_types( $defaults, $file, $filename ) {
			if ( 'ttf' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
				$defaults['type'] = 'application/x-font-ttf';
				$defaults['ext']  = 'ttf';
			}

			return $defaults;
		}
	}


	/**
	 *  Kicking this off by calling 'get_instance()' method
	 */
	BuddyBoss_Custom_Fonts_Admin::get_instance();

endif;
