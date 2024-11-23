<?php

function pmxi_wp_ajax_save_import_functions(){

	if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp-all-import-pro'))) );
	}

	if ( ! current_user_can( PMXI_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp-all-import-pro'))) );
	}

	$uploads   = wp_upload_dir();
	$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_IMPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
	$functions = apply_filters( 'import_functions_file_path', $functions );

	$input = new PMXI_Input();
	
	$post = $input->post('data', '');
	$post_to_validate = '';

	// Encode any string parenthesis to avoid validation issues.
	if(!empty($post)){
		$post_to_validate = pmxi_encode_parenthesis_within_strings($post);
	}

	$response = wp_remote_post('https://phpcodechecker.com/check/beta.php', array(
		'body' => array(
			'body' => $post_to_validate,
			'phpversion' => PHP_MAJOR_VERSION
		)
	));

	if (is_wp_error($response))
	{
		if (strpos($post, "<?php") === false || strpos($post, "?>") === false)
		{
			exit(json_encode(array('result' => false, 'msg' => __('PHP code must be wrapped in "&lt;?php" and "?&gt;"', 'wp-all-import-pro')))); die;	
		}	
		else
		{
			file_put_contents($functions, $post);
		}

   		exit(json_encode(array('result' => true, 'msg' => __('File has been successfully updated.', 'wp-all-import-pro')))); die;
	}
	else
	{
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (!empty($body['errors']))
		{
			$error_response = '';
			foreach($body['results'] as $result){
				if(!empty($result['found']) && !empty($result['message'])){
					$error_response .= $result['message'].'<br/>';
				}
			}
			exit(json_encode(array('result' => false, 'msg' => $error_response))); die;
		}
		elseif(empty($body['errors']))
		{
			if (strpos($post, "<?php") === false || strpos($post, "?>") === false)
			{
				exit(json_encode(array('result' => false, 'msg' => __('PHP code must be wrapped in "&lt;?php" and "?&gt;"', 'wp-all-import-pro')))); die;	
			}	
			else
			{
				file_put_contents($functions, $post);
			}					
		}
	}	

	exit(json_encode(array('result' => true, 'msg' => __('File has been successfully updated.', 'wp-all-import-pro')))); die;
}