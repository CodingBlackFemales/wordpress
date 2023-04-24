<?php
/**
 * BuddyBoss Custom Fonts Admin Ui
 *
 * @since  1.2.10
 * @package BuddyBoss_Custom_Fonts
 */

namespace BuddyBossTheme;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\BuddyBossTheme\BuddyBoss_Custom_Fonts_Render' ) ) :

	/**
	 * BuddyBoss_Custom_Fonts_Render
	 */
	class BuddyBoss_Custom_Fonts_Render {

		/**
		 * Instance of BuddyBoss_Custom_Fonts_Render
		 *
		 * @since  1.2.10
		 * @var (Object) BuddyBoss_Custom_Fonts_Render
		 */
		private static $_instance = null;

		/**
		 * Font base.
		 *
		 * This is used in case of Elementor's Font param
		 *
		 * @since  1.2.10
		 * @var string
		 */
		private static $font_base = 'buddyboss-theme-custom-fonts';

		/**
		 * Member Varible
		 *
		 * @var string $font_css
		 */
		protected $font_css = '';

		/**
		 * Instance of BuddyBoss_Custom_Fonts_Render.
		 *
		 * @since  1.2.10
		 *
		 * @return object Class object.
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

			// Beaver builder theme customizer, beaver buidler page builder.
			add_filter( 'fl_theme_system_fonts', array( $this, 'bb_custom_fonts' ) );
			add_filter( 'fl_builder_font_families_system', array( $this, 'bb_custom_fonts' ) );

			// Add font files style.
			add_action( 'wp_head', array( $this, 'add_style' ) );
			add_action( 'login_head', array( $this, 'add_style' ) );

			if ( is_admin() ) {
				if ( ! empty( $_GET['page'] ) && 'buddyboss_theme_options' === $_GET['page'] ) {
					add_action( 'admin_head', array( $this, 'add_style' ) );
				}
				add_action( 'enqueue_block_assets', array( $this, 'add_style' ) );
			}

			add_filter( 'elementor/fonts/groups', array( $this, 'elementor_group' ) );
			add_filter( 'elementor/fonts/additional_fonts', array( $this, 'add_elementor_fonts' ) );
		}

		/**
		 * Add Custom Font group to elementor font list.
		 *
		 * Group name "Custom" is added as the first element in the array.
		 *
		 * @since  1.2.10
		 *
		 * @param  Array  $font_groups  default font groups in elementor.
		 *
		 * @return Array              Modified font groups with newly added font group.
		 */
		public function elementor_group( $font_groups ) {
			$new_group[ self::$font_base ] = __( 'BuddyBoss', 'buddyboss-theme' );
			$font_groups                   = $new_group + $font_groups;

			return $font_groups;
		}

		/**
		 * Add Custom Fonts to the Elementor Page builder's font param.
		 *
		 * @param  Array  $fonts  Custom Font's array.
		 *
		 * @since  1.2.10
		 */
		public function add_elementor_fonts( $fonts ) {

			$all_fonts = BuddyBoss_Custom_Fonts_CPT::get_fonts();

			if ( ! empty( $all_fonts ) ) {
				foreach ( $all_fonts as $font_id => $font_data ) {
					$fonts[ $font_data['name'] ] = self::$font_base;
				}
			}

			return $fonts;
		}


		/**
		 * Add Custom Font list to BB theme and BB Page Builder
		 *
		 * @since  1.2.10
		 *
		 * @param  array  $bb_fonts  font families added by bb.
		 *
		 */
		function bb_custom_fonts( $bb_fonts ) {

			$fonts        = BuddyBoss_Custom_Fonts_CPT::get_fonts();
			$custom_fonts = array();
			if ( ! empty( $fonts ) ) {
				foreach ( $fonts as $font_id => $font_data ) {

					$weights = array();
					if ( ! empty( $font_data['font_face'] ) ) {
						foreach ( $font_data['font_face'] as $key => $font_face ) {
							$weights[] = $font_face['font_weight'];
						}
					}

					$custom_fonts[ $font_data['name'] ] = array(
						'fallback' => 'Verdana, Arial, sans-serif',
						'weights'  => array_unique( $weights ),
					);
				}
			}

			return array_merge( $bb_fonts, $custom_fonts );
		}

		/**
		 * Enqueue Scripts
		 *
		 * @since 1.2.10
		 */
		public function add_style() {
			$fonts = BuddyBoss_Custom_Fonts_CPT::get_fonts();
			if ( ! empty( $fonts ) ) {
				foreach ( $fonts as $font_id => $font_data ) {
					$this->render_font_css( $font_id, $font_data );
				}
				?>
                <style type="text/css">
                    <?php echo wp_strip_all_tags( $this->font_css ); ?>
                </style>
				<?php
			}
		}

		/**
		 * Create css for font-face
		 *
		 * @since 1.2.10
		 *
		 * @param  array|bool  $font_data  selected font data from custom font list or false.
		 *
		 * @param  int  $font_id  selected font id from custom font list.
		 */
		private function render_font_css( $font_id, $font_data = false ) {
			if ( empty( $font_data ) ) {
				$font_family_name = get_the_title( $font_id );
				$font_data        = array(
					'name'      => $font_family_name,
					'font_face' => BuddyBoss_Custom_Fonts_CPT::get_font_data( $font_id )
				);
			}

			$css = '';
			if ( ! empty( $font_data['font_face'] ) && ! empty( $font_data['name'] ) ) {
				foreach ( $font_data['font_face'] as $key => $font_face ) :
					$css .= '@font-face { font-family: ' . esc_attr( $font_data['name'] ) . '; ';
					$css .= 'src: ';
					$arr = array();
					if ( ! empty( $font_face['woff2']['url'] ) ) {
						$arr[] = 'url(' . esc_url( $font_face['woff2']['url'] ) . ") format('woff2')";
					}
					if ( ! empty( $font_face['woff']['url'] ) ) {
						$arr[] = 'url(' . esc_url( $font_face['woff']['url'] ) . ") format('woff')";
					}
					if ( ! empty( $font_face['ttf']['url'] ) ) {
						$arr[] = 'url(' . esc_url( $font_face['ttf']['url'] ) . ") format('truetype')";
					}
					$css .= join( ', ', $arr );
					$css .= '; ';

					if ( ! empty( $font_face['font_weight'] )
					     && in_array( $font_face['font_weight'],
							array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) ) ) {
						$css .= 'font-weight: ' . $font_face['font_weight'] . '; ' ;
					}

					if ( ! empty( $font_face['font_style'] )
					     && in_array( $font_face['font_style'], array( 'normal', 'italic', 'oblique' ) ) ) {
						$css .= 'font-style: ' . $font_face['font_style'] . '; ';
					}

                    $css .= 'font-display: swap; ';

					$css .= '} ';
				endforeach;
			}

			$this->font_css .= $css;
		}
	}

	/**
	 *  Kicking this off by calling 'get_instance()' method
	 */
	BuddyBoss_Custom_Fonts_Render::get_instance();

endif;
