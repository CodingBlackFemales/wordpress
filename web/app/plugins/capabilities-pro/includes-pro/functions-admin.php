<?php
/*
 * PublishPress Capabilities Pro
 *
 * Pro admin functions with broad scope, which are not contained within a class
 */

add_filter('pp_capabilities_backup_sections', 'pp_capabilities_pro_backup_sections');

require_once (dirname(__FILE__) . '/features/custom.php');
\PublishPress\Capabilities\EditorFeaturesCustom::instance();

require_once (dirname(__FILE__) . '/features/metaboxes.php');
\PublishPress\Capabilities\EditorFeaturesMetaboxes::instance();

//admin features css hide integration
require_once (dirname(__FILE__) . '/features/admin-features-css-hide.php');
\PublishPress\Capabilities\AdminFeaturesCssHide::instance();

//admin features block url integration
require_once (dirname(__FILE__) . '/features/admin-features-block-url.php');
\PublishPress\Capabilities\AdminFeaturesBlockUrl::instance();


function pp_capabilities_pro_backup_sections($backup_sections){

    //Admin Features Pro
    $backup_sections['capsman_admin_features_backup']['options'][] = 'ppc_admin_feature_block_url_custom_data';
    $backup_sections['capsman_admin_features_backup']['options'][] = 'ppc_admin_feature_css_hide_custom_data';

    //Editor Features Pro
    $backup_sections['capsman_editor_features_backup']['options'][] = 'ppc_feature_post_gutenberg_custom_data';
    $backup_sections['capsman_editor_features_backup']['options'][] = 'ppc_feature_post_classic_custom_data';

    return $backup_sections;
}

require_once __DIR__ . '/features/admin-menus/admin-menus-pro.php';
