<?php
if ( ! defined( 'ABSPATH' ) ) exit();

if ( class_exists( 'LearnDash_Settings_Page' ) ) :

class LearnDash_Zapier_Settings_Page extends LearnDash_Settings_Page {

    public function __construct() {
        $this->parent_menu_page_url     =   'edit.php?post_type=sfwd-zapier';
        $this->menu_page_capability     =   LEARNDASH_ADMIN_CAPABILITY_CHECK;
        $this->settings_page_id         =   'learndash-zapier-settings';
        $this->settings_page_title      =   __( 'LearnDash Zapier Settings', 'learndash-thrivecart' );
        $this->settings_tab_title       =   __( 'Settings', 'learndash-zapier' );
        $this->settings_tab_priority    =   1;
        $this->show_settings_page_function  =   array( $this, 'show_settings_page' );

        parent::__construct(); 
    }
}

add_action( 'learndash_settings_pages_init', function() {
    LearnDash_Zapier_Settings_Page::add_page_instance();
} );

endif;