<?php

function pmxi_wp_ajax_wpai_dismiss_review_modal(){

	if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp-all-import-pro'))) );
	}

	if ( ! current_user_can( PMXI_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp-all-import-pro'))) );
	}

	$reviewLogic = new \Wpai\Reviews\ReviewLogic();
    $reviewLogic->dismissNotice();

	exit(json_encode(array('result' => true)));
}