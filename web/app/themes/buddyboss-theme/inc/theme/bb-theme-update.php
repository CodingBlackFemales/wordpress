<?php

/**
 * BB Theme Update
 *
 * @since 1.8.7
 */

namespace BuddyBossTheme;

if ( ! class_exists( '\BuddyBossTheme\BBThemeUpdate' ) ) {

	/**
	 * Class BB Theme Update.
	 */
	class BBThemeUpdate {

		/**
		 * Constructor
		 *
		 * @since 1.8.7
		 */
		public function __construct() {
			add_action( 'admin_footer', array( $this, 'bb_update_theme_modal_file' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'bb_update_theme_modal_admin_script' ) );
			add_filter( 'wp_prepare_themes_for_js', array( $this, 'bb_change_theme_update_info_html' ), 10, 1 );
		}

		/**
		 * Update theme data for buddyboss theme for popup.
		 *
		 * @since 1.8.7
		 *
		 * @param array $prepared_themes Theme data.
		 *
		 * @return mixed
		 */
		public function bb_change_theme_update_info_html( $prepared_themes ) {

			$theme_option_get = get_option( 'bb_theme_options_major', true );
			if ( $theme_option_get ) {
				$theme_slug = function_exists( 'get_template' ) ? get_template() : '';
				if ( ! empty( $theme_slug ) && $prepared_themes[ $theme_slug ]['hasUpdate'] ) {
					$prepared_themes[ $theme_slug ]['hasPackage']              = false;
					$prepared_themes[ $theme_slug ]['update']                  = $this->bb_get_theme_update_available( wp_get_theme( $theme_slug ) );
					$prepared_themes[ $theme_slug ]['autoupdate']['enabled']   = false;
					$prepared_themes[ $theme_slug ]['autoupdate']['supported'] = false;
					update_option( 'bb_theme_options_major', false );
				}
			}
			return $prepared_themes;
		}

		/**
		 * Get theme data for buddyboss theme for popup.
		 *
		 * @since 1.8.7
		 *
		 * @param array $theme object.
		 *
		 * @return HTML
		 */
		public function bb_get_theme_update_available( $theme ) {

			if ( ! is_a( $theme, 'WP_Theme' ) ) {
				return false;
			}
			static $themes_update = null;

			if ( ! current_user_can( 'update_themes' ) ) {
				return false;
			}

			if ( ! isset( $themes_update ) ) {
				$themes_update = get_site_transient( 'update_themes' );
			}

			if ( ! ( $theme instanceof \WP_Theme ) ) {
				return false;
			}

			$stylesheet = $theme->get_stylesheet();

			$html = '';

			if ( isset( $themes_update->response[ $stylesheet ] ) ) {
				$update      = $themes_update->response[ $stylesheet ];
				$theme_name  = $theme->display( 'Name' );
				$details_url = add_query_arg(
					array(
						'TB_iframe' => 'true',
						'width'     => 1024,
						'height'    => 800,
					),
					$update['url']
				); // Theme browser inside WP? Replace this. Also, theme preview JS will override this on the available list.
				$update_url  = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&amp;theme=' . urlencode( $stylesheet ) ), 'upgrade-theme_' . $stylesheet );

				if ( ! is_multisite() ) {
					if ( ! current_user_can( 'update_themes' ) ) {
						$html = sprintf(
							/* translators: 1: Theme name, 2: Theme details URL */
							'<p><strong>' . esc_html__( 'There is a new version of %1$s available. %2$s', 'buddyboss-theme' ) . '</strong></p>',
							$theme_name,
							sprintf(
								/* translators: 1: Theme details URL, 2: Additional link attributes, 3: Additional link text */
								'<a href="%1$s" %2$s>%3$s</a>',
								esc_url( $details_url ),
								sprintf(
									'class="thickbox open-plugin-details-modal" aria-label="%s"',
									/* translators: 1: Theme name, 2: Version number. */
									esc_attr( sprintf( esc_html__( 'View %1$s version %2$s details', 'buddyboss-theme' ), $theme_name, $update['new_version'] ) )
								),
								sprintf(
									/* translators: 1: Version number. */
									esc_html__( 'View version %1$s details', 'buddyboss-theme' ),
									$update['new_version']
								)
							)
						);
					} elseif ( empty( $update['package'] ) ) {
						$html = sprintf(
							/* translators: 1: Theme name, 2: Theme details URL, 3: Additional link attributes */
							'<p><strong>' . esc_html__( 'There is a new version of %1$s available. %2$s. %3$s', 'buddyboss-theme' ) . '</strong></p>',
							$theme_name,
							sprintf(
								/* translators: 1: Theme details URL, 2: Additional link attributes, 3: Additional link text */
								' <a href="%1$s" %2$s>%3$s</a>',
								esc_url( $details_url ),
								sprintf(
									'class="thickbox open-plugin-details-modal" aria-label="%s"',
									/* translators: 1: Theme name, 2: Version number. */
									esc_attr( sprintf( esc_html__( 'View %1$s version %2$s details', 'buddyboss-theme' ), $theme_name, $update['new_version'] ) )
								),
								sprintf(
									/* translators: 1: Version number. */
									esc_html__( 'View version %1$s details', 'buddyboss-theme' ),
									$update['new_version']
								)
							),
							sprintf(
								/* translators: 1: Automatic Update Text. */
								'<em>%1$s</em>',
								esc_html__( 'Automatic update is unavailable for this theme.', 'buddyboss-theme' )
							)
						);
					} else {
						$html = sprintf(
							/* translators: 1: Theme name, 2: Theme details URL, 3: Additional link attributes */
							'<p><strong>' . esc_html__( 'There is a new version of %1$s available. %2$s or %3$s.', 'buddyboss-theme' ) . '</strong></p>',
							$theme_name,
							sprintf(
								/* translators: 1: Theme details URL, 2: Additional link attributes, 3: Additional link text */
								'<a href="%1$s" %2$s>%3$s</a>',
								esc_url( $details_url ),
								sprintf(
									'class="thickbox open-plugin-details-modal" aria-label="%s"',
									/* translators: 1: Theme name, 2: Version number. */
									esc_attr( sprintf( esc_html__( 'View %1$s version %2$s details', 'buddyboss-theme' ), $theme_name, $update['new_version'] ) )
								),
								sprintf(
									/* translators: 1: Version number. */
									esc_html__( 'View version %1$s details', 'buddyboss-theme' ),
									$update['new_version']
								)
							),
							sprintf(
								/* translators: 1: Theme Update URL, 2: Additional link attributes, 3: Update text */
								'<a data-src="%1$s" href="javascript:void(0);" %2$s class="bb-theme-update-popup">%3$s</a>',
								esc_url( $update_url ),
								sprintf(
									'aria-label="%s" id="bb-update-theme" data-slug="%s"',
									/* translators: %s: Update now. */
									esc_attr( sprintf( _x( 'Update %s now', 'theme', 'buddyboss-theme' ), $theme_name ) ),
									$stylesheet
								),
								esc_html__( 'update now', 'buddyboss-theme' )
							)
						);
						ob_start();
						include get_template_directory() . '/template-parts/update-hello-theme-popup.php';
						$html .= ob_get_clean();
					}
				}
			}

			return $html;
		}

		/**
		 * Include file for theme update modal.
		 *
		 * @since 1.8.7
		 */
		public function bb_update_theme_modal_file() {
			if ( 0 === strpos( get_current_screen()->id, 'update-core' ) ) {
				include get_template_directory() . '/template-parts/update-hello-theme-popup.php';
			}
		}

		/**
		 * Enqueue style and script for modal popup.
		 *
		 * @since 1.8.7
		 */
		public function bb_update_theme_modal_admin_script() {
			if (
				0 === strpos( get_current_screen()->id, 'themes' ) ||
				0 === strpos( get_current_screen()->id, 'update-core' )
			) {
				$rtl_css      = is_rtl() ? '-rtl' : '';
				$minified_css = buddyboss_theme_get_option( 'boss_minified_css' );
				$mincss       = $minified_css ? '.min' : '';
				$minified_js  = buddyboss_theme_get_option( 'boss_minified_js' );
				$minjs        = $minified_js ? '.min' : '';

				wp_register_style( 'buddyboss-theme-hello-css', get_template_directory_uri() . '/assets/css' . $rtl_css . '/hello-theme' . $mincss . '.css', '', buddyboss_theme()->version() );
				wp_enqueue_style( 'buddyboss-theme-hello-css' );
				wp_register_script( 'buddyboss-theme-hello-js', get_template_directory_uri() . '/assets/js/hello-theme' . $minjs . '.js', array( 'jquery' ), buddyboss_theme()->version(), true );
				wp_enqueue_script( 'buddyboss-theme-hello-js' );
				wp_register_script( 'buddyboss-update-theme-modal-js', get_template_directory_uri() . '/assets/js/update-theme-modal' . $minjs . '.js', array( 'jquery' ), buddyboss_theme()->version(), true );
				wp_enqueue_script( 'buddyboss-update-theme-modal-js' );
			}
		}

	}

}
