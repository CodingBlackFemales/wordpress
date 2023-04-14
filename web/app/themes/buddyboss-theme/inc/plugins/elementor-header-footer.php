<?php 

/**
 * Elementor – Header, Footer & Blocks
 *
 */

namespace BuddyBossTheme;

// If plugin not exist then return.
if ( !function_exists( 'hfe_init' ) ) {
	return;
}

if ( ! class_exists( '\BuddyBossTheme\ElementorHeaderFooter' ) ) {

    Class ElementorHeaderFooter {

        protected $_is_active = false;

        /**
         * Constructor
         */
        public function __construct () {
			add_action( 'after_setup_theme', array( $this, 'header_footer_support' ) );
			add_action( 'init', array( $this, 'hooks' ), 0 );
        }

        public function set_active(){
            $this->_is_active = true;
        }

        public function is_active(){
            return $this->_is_active;
        }

		/**
		* Run all the Actions / Filters.
		*/
		public function hooks() {

			// Header Enabled
			if ( hfe_header_enabled() ) {
				add_action( 'template_redirect', [ $this, 'setup_header' ], 10 );
				add_action( THEME_HOOK_PREFIX . 'header', 'hfe_render_header' );
			}

			// Before Footer Enabled
			if ( hfe_is_before_footer_enabled() ) {
				remove_action( 'hfe_footer_before', [ 'Header_Footer_Elementor', 'get_before_footer_content' ] );
				add_action( THEME_HOOK_PREFIX . 'before_footer', [ 'Header_Footer_Elementor', 'get_before_footer_content' ], 10 );
			}

			// Footer Enabled
			if ( hfe_footer_enabled() ) {
				add_action( 'template_redirect', [ $this, 'setup_footer' ], 10 );
				add_action( THEME_HOOK_PREFIX . 'footer', 'hfe_render_footer' );
			}
		}

		/**
		 * Function to add Theme Support
		 *
		 */
		function header_footer_support() {
			add_theme_support( 'header-footer-elementor' );
		}

		/**
		 * Disable header from the theme.
		 */
		public function setup_header() {
			// Remove HFE Header
			remove_action( 'hfe_header', 'hfe_render_header' );

			// Remove BuddyBoss Theme Header Elements
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header' );
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_mobile_header' );
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header_search' );
		}

		/**
		 * Disable footer from the theme.
		 */
		public function setup_footer() {
			remove_action( THEME_HOOK_PREFIX . 'footer', 'buddyboss_theme_footer_area' );
			remove_action( 'hfe_footer', 'hfe_render_footer' );
		}

    }
}