<?php

namespace Wpai\Reviews;


class ReviewLogic
{
    const MAILTO = 'support@wpallimport.com';
    const SUBJECT = 'New Feedback';

    private $wpdb;

    private $imports = false;

    private $pluginName = '';

    private $pluginReviewLink = '';

    private $modalType;

    private $pluginModalText;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }



    public function shouldShowReviewModal()
    {

        // Only display on the Manage Imports page.
        if($_GET['page'] !== 'pmxi-admin-manage' || isset($_GET['id']) ){
            return false;
        }

        if($this->hasMoreThanMaxModalsDismissed()) {
            return false;
        }

        if(!$this->hasImportsThatMatch()) {
            return false;
        }

        if($this->thereWasAModalInTheLast30Days()) {
            return false;
        }

        // Determine version of WP All Import running
	    if(defined('PMXI_EDITION') && PMXI_EDITION === 'free') {
		    $wpaiVersion       = 'WP All Import';
	    }else{
		    $wpaiVersion       = 'WP All Import Pro';
	    }

        $modalToShow = $this->getModalToShow();

        $this->modalType = $modalToShow;

        if($modalToShow == 'products') {
            $this->pluginName = 'the WooCommerce Product Import Add-On';
            $this->pluginReviewLink = 'https://wordpress.org/plugins/woocommerce-xml-csv-product-import/#reviews';
            $this->pluginModalText ='How was your experience importing WooCommerce products with '.$wpaiVersion.'?';
            return true;
        }

        if($modalToShow === 'wpai') {

			$this->pluginName = 'WP All Import';
            $this->pluginReviewLink = 'https://wordpress.org/plugins/wp-all-import/#reviews';
            $this->pluginModalText  = 'How was your experience importing records with '.$wpaiVersion.'?';
            return true;
        }


        return false;
    }

    public function dismissNotice()
    {
        if (current_user_can(\PMXI_Plugin::$capabilities)) {
            update_option('wpai_modal_review_dismissed', true, false);
            update_option('wpai_modal_review_dismissed_time', time(), false);

            $dismissedModals = get_option('wpai_modal_review_dismissed_modals', []);

            $dismissModalType = esc_html($_POST['modal_type']);

            if(!is_array($dismissedModals)) {
                $dismissedModals = [];
            }

            $dismissedModals[] = $dismissModalType;
            update_option('wpai_modal_review_dismissed_modals', $dismissedModals);

            $dismissedTimes = get_option('wpai_modal_review_dismissed_times', 0);
            $dismissedTimes++;

            update_option('wpai_modal_review_dismissed_times', $dismissedTimes, false);

        }
    }

    public function submitFeedback()
    {

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $this->dismissNotice();

        $proInUse = '';

        // Check if WP All Import Pro is installed
        if( defined('PMXI_EDITION') && PMXI_EDITION === 'paid' ){
            $proInUse .= 'Installed Pro Plugin: WP All Import Pro <br/><br/>';
        }

        // Check if the WooCommerce Import Add-On is installed
        if( defined('PMWI_EDITION') and PMWI_EDITION == "paid" ){
            $proInUse .= 'Installed Pro Plugin: WooCommerce Import Add-On Pro <br/><br/>';
        }

        // Prettify the reviewed plugin.
        $plugin = 'Plugin Reviewed: ';
        switch( $_POST['plugin'] ){
            case 'wpai':
                $plugin .= 'WP All Import';
                break;

            case 'products':
                $plugin .= 'Product Import Add-On';
                break;
        }

        $message = $plugin . " <br/><br/>" . $proInUse . wp_kses_post(stripslashes(wpautop($_POST['message'])));
        wp_mail( self::MAILTO, self::SUBJECT, $message, $headers );
    }


    public function getPluginName() {
        return $this->pluginName;
    }

    public function getReviewLink() {
        return $this->pluginReviewLink;
    }

    public function getModalType() {
        return $this->modalType;
    }

    public function getModalText() {
        return $this->pluginModalText;
    }

    private function getModalToShow()
    {
        $importCount = [
            'products' => 0
        ];

        // Only show modal for import types that have been on the site for at least two days.
        $importOlderThanTwoDays = [
            'products' => false
        ];

        $imports = $this->getImports();

        // Go through the imports and find the import count for each import type
        foreach($imports as $import) {
            $options = maybe_unserialize($import->options);

            if ($options) {

                $custom_type = $options['custom_type'];

                if (!is_array($custom_type)) {
                    $custom_type = [$custom_type];
                }

                // Is product import
                if (in_array('product', $custom_type)) {
                    $importCount['products']++;
                    if( strtotime($import->first_import) < time() - 2 * 24 * 3600 ){
                        $importOlderThanTwoDays['products'] = true;
                    }
                }

            }
        }

        // Get the plugin with most imports
        $max = 0;
        $plugin = false;

        $dismissedModals = get_option('wpai_modal_review_dismissed_modals', []);

        foreach($importCount as $key => $imports) {
            if($imports > $max && !in_array($key, $dismissedModals) && $importOlderThanTwoDays[$key]) {
                $plugin = $key;
                $max = $imports;
            }
        }

        if(!$plugin && !in_array('wpai', $dismissedModals)) {
            $plugin = 'wpai';
        }

        return $plugin;
    }


    private function thereWasAModalInTheLast30Days()
    {
        $lastModalDismissed = get_option('wpai_modal_review_dismissed_time');

        if( $lastModalDismissed > time() - 30 * 24 * 3600 ) {

            return true;
        }

        return false;
    }

    private function hasImportsThatMatch(){

        $importsOlderThan48Hours = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "pmxi_imports WHERE first_import < NOW() - INTERVAL 2 DAY AND first_import <> '0000-00-00 00:00:00' ");

        $imports = $this->getImports();

        return (count($importsOlderThan48Hours) >= 1 && count($imports) >= 5 );
    }

    /**
     * @return imports[]
     */
    private function getImports()
    {
        if (!$this->imports) {
            $this->imports = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->prefix . "pmxi_imports");
        }

        return $this->imports;
    }

    private function hasMoreThanMaxModalsDismissed()
    {
        $dismissedTimes = get_option('wpai_modal_review_dismissed_times', 0);

        if($dismissedTimes > 1) {
            return true;
        }

        return false;
    }
}