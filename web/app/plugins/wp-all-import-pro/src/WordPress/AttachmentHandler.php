<?php

namespace Wpai\WordPress;

class AttachmentHandler{
	protected $imageUploadDir,$articleData,$currentXmlNode,$pid,$isImageToUpdate,$missing_images;
	protected $postAuthor, $postType, $importId, $recordIndex, $img_captions, $img_titles, $img_alts, $img_descriptions;
	protected $option_slug, $slug, $bundle_data;
	public static $is_cron = false;
	private static $option_name = 'pmxi_attachment_data_';
	protected static $createdAttachmentIds = [];
	public static $image_meta_titles_bundle = [], $image_meta_captions_bundle = [], $image_meta_alts_bundle = [], $image_meta_descriptions_bundle = [], $auto_rename_images_bundle = [], $auto_extensions_bundle = [],$images_bundle = [], $importRecord, $logger, $importData, $attachments, $chunk, $xml, $uploads, $cxpath;
	public static $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';


	public function __construct($articleData, $pid, $missing_images = [], $postAuthor = [], $postType = [], $i = 0) {

		$current_xml_node = false;
		if (!empty($importData['current_xml_node'])) {
			$current_xml_node = $importData['current_xml_node'];
		}

		$this->recordIndex = $i;
		$this->currentXmlNode = $current_xml_node;
		self::$uploads = wp_upload_dir();
		$this->imageUploadDir = apply_filters('wp_all_import_images_uploads_dir', self::$uploads, $articleData, $current_xml_node, self::$importRecord->id, $pid);
		$this->articleData = $articleData;
		$this->pid = $pid;
		$this->isImageToUpdate = apply_filters('pmxi_is_images_to_update', true, $articleData, $current_xml_node, $pid);
		$this->missing_images = $missing_images;
		$this->postAuthor = $postAuthor;
		$this->postType = $postType;
		$this->importId = self::$importRecord->id;

		self::$createdAttachmentIds = self::retrieve_data('createdAttachmentIds');

	}

	public static function allow_delay_image_resize(){

		$importOptions = \PMXI_Plugin::getCurrentImportOptions();
		return apply_filters('pmxi_allow_delay_image_resize', $importOptions['allow_delay_image_resize'] ?? false);
	}

	public static function before_xml_import($import_id){
		if(self::allow_delay_image_resize()) {
			add_filter( 'intermediate_image_sizes', '__return_empty_array' );
		}
	}

	public static function after_xml_import($import_id, $import){
		if(self::allow_delay_image_resize()) {
			remove_filter( 'intermediate_image_sizes', '__return_empty_array' );
			self::generate_thumbnails();
		}
	}

	private static function generate_thumbnails() {

		if(class_exists('WC_REST_System_Status_Tools_Controller') && in_array(\wp_all_import_get_import_post_type(), ['product']) && apply_filters('woocommerce_background_image_regeneration', true)) {
			$tools_controller = new \WC_REST_System_Status_Tools_Controller();

			if ( array_key_exists( 'regenerate_thumbnails', $tools_controller->get_tools() ) ) {

				add_filter( 'query', [ __CLASS__, 'regenerate_thumbnails_sql' ] );

				$tools_controller->execute_tool( 'regenerate_thumbnails' );
				\WC_Admin_Notices::add_notice( 'regenerating_thumbnails' );
				self::reset_data('createdAttachmentIds', false);
			}
		}else{
			RegenerateImages::init(self::retrieve_data('createdAttachmentIds'), '10', \wp_all_import_get_import_id());
		}

	}

	public static function regenerate_thumbnails_sql($sql){

		global $wpdb;

		if ("SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image/%'
			ORDER BY ID DESC" === $sql) {
			// Filter the array to only include numeric values
			$numericArray = array_filter(self::retrieve_data('createdAttachmentIds'), 'is_numeric');

			// Create a comma-separated string
			$idList = join(',', $numericArray);

			// Ensure generated SQL is valid.
			if(empty($idList)){
				$idList = "''";
			}

			$sql = "SELECT ID
					FROM {$wpdb->posts}
					WHERE post_type = 'attachment'
					AND ID IN ($idList)		
					AND post_mime_type LIKE 'image/%'
					ORDER BY ID DESC";

		}

		return $sql;
	}


	public static function log_created_attachment($attachment_id){

		is_numeric($attachment_id) && self::$createdAttachmentIds[] = $attachment_id;

		self::store_data('createdAttachmentIds', self::$createdAttachmentIds);
	}

	public static function remove_values_from_stored_data($import_id, $field, array $values_to_remove)
	{
		$stored_data = self::retrieve_data($field, $import_id);
		if (is_array($stored_data) && !empty($stored_data)) {
			$stored_data = array_filter($stored_data, function($value) use ($values_to_remove) {
				return !in_array($value, $values_to_remove);
			});
			self::store_data($field, array_values($stored_data), true, $import_id);
		}
	}

	public static function get_import_id(){
		return \wp_all_import_get_import_id();
	}

	public static function store_data($field, $value, $autoload = true, $import_id = null) {
		$import_id = $import_id ?? self::get_import_id();
		update_option(self::$option_name . $import_id .'_'.$field, $value, $autoload);
	}

	public static function retrieve_data($field, $import_id = null) {
		$import_id = $import_id ?? self::get_import_id();
		return get_option(self::$option_name . $import_id .'_'.$field, []);
	}

	public static function reset_data($field, $autoload = true, $import_id = null) {
		self::store_data($field, [], $autoload, $import_id);
	}

	private function deleteImageMetaFields(){
		if ( $this->isImageToUpdate and ! empty($this->imageUploadDir) and false === $this->imageUploadDir['error'] and (empty($this->articleData['ID']) or self::$importRecord->options['update_all_data'] == "yes" or ( self::$importRecord->options['update_all_data'] == "no" and self::$importRecord->options['is_update_images']))) {
			// If images set to be updated then delete image related custom fields as well.
			if ( self::$importRecord->options['update_images_logic'] == "full_update" ) {
				$image_custom_fields = [ '_thumbnail_id', '_product_image_gallery' ];
				foreach ( $image_custom_fields as $image_custom_field ) {
					switch ( self::$importRecord->options['custom_type'] ) {
						case 'import_users':
						case 'shop_customer':
							delete_user_meta( $this->pid, $image_custom_field );
							break;
						case 'taxonomies':
							delete_term_meta( $this->pid, $image_custom_field );
							break;
						case 'woo_reviews':
						case 'comments':
							delete_comment_meta( $this->pid, $image_custom_field );
							break;
						case 'shop_order':
							$order = \wc_get_order($this->pid);
							$order->delete_meta_data($image_custom_field);
							break;
						case 'gf_entries':
							// No actions required.
							break;
						default:
							self::$importRecord->importer->delete_image_custom_field($this->pid, $image_custom_field);
							break;
					}
				}
			}
		}
	}

	private function importAttachments(){

		$is_attachments_to_update = apply_filters('pmxi_is_attachments_to_update', true, $this->articleData, $this->currentXmlNode);

		$attachments_uploads = apply_filters('wp_all_import_attachments_uploads_dir', self::$uploads, $this->articleData, $this->currentXmlNode, self::$importRecord->id);

		if ( $is_attachments_to_update and ! empty($attachments_uploads) and false === $attachments_uploads['error'] and !empty(self::$attachments[$this->recordIndex]) and (empty($articleData['ID']) or self::$importRecord->options['update_all_data'] == "yes" or (self::$importRecord->options['update_all_data'] == "no" and self::$importRecord->options['is_update_attachments']))) {

			$targetDir = $attachments_uploads['path'];
			$targetUrl = $attachments_uploads['url'];

			self::$logger and call_user_func(self::$logger, __('<b>ATTACHMENTS:</b>', 'wp_all_import_plugin'));

			if ( ! @is_writable($targetDir) ){
				self::$logger and call_user_func(self::$logger, sprintf(__('- <b>ERROR</b>: Target directory %s is not writable', 'wp_all_import_plugin'), trim($targetDir)));
			}
			else{
				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');

				if ( ! is_array(self::$attachments[$this->recordIndex]) ) self::$attachments[$this->recordIndex] = array(self::$attachments[$this->recordIndex]);

				self::$logger and call_user_func(self::$logger, sprintf(__('- Importing attachments for `%s` ...', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));

				foreach (self::$attachments[$this->recordIndex] as $attachment) {

					if ("" == $attachment) continue;

					$atchs = str_getcsv($attachment, self::$importRecord->options['atch_delim']);

					if ( ! empty($atchs) ) {

						foreach ($atchs as $atch_url) {

							if (empty($atch_url)) continue;

							$attachments_uploads = apply_filters('wp_all_import_single_attachment_uploads_dir', $attachments_uploads, $atch_url, $this->articleData, $this->currentXmlNode, self::$importRecord->id);

							if (empty($attachments_uploads)) {
								self::$logger and call_user_func(self::$logger, __('- <b>ERROR</b>: Target directory is not defined', 'wp_all_import_plugin'));
								continue;
							}

							$targetDir = $attachments_uploads['path'];
							$targetUrl = $attachments_uploads['url'];

							if ( ! @is_writable($targetDir) ){
								self::$logger and call_user_func(self::$logger, sprintf(__('- <b>ERROR</b>: Target directory %s is not writable', 'wp_all_import_plugin'), trim($targetDir)));
								continue;
							}

							$download_file = true;
							$create_file = false;
							$attach_id = false;

							$atch_url = str_replace(" ", "%20", trim($atch_url));

							$attachment_filename = urldecode(wp_all_import_basename(parse_url(trim($atch_url), PHP_URL_PATH)));

							// If there is no file extension try to detect it.
							$ext = pmxi_getExtension($attachment_filename);

							if( empty($ext) ){
								$ext = pmxi_getExtension($atch_url);

								if( !empty($ext) ){
									$attachment_filename .= '.' . $ext;
								}
							}

							$attachment_filepath = $targetDir . '/' . sanitize_file_name($attachment_filename);

							if (self::$importRecord->options['is_search_existing_attach']){

								// search existing attachment
								$attch = wp_all_import_get_image_from_gallery($attachment_filename, $targetDir, 'files', self::$logger);

								if ( $attch != null ){
									$download_file = false;
									$create_file = false;
									$attach_id = $attch->ID;
									self::$logger and call_user_func(self::$logger, sprintf(__('- Using existing file `%s` for post `%s` ...', 'wp_all_import_plugin'), trim($attachment_filename), self::$importRecord->getRecordTitle($this->articleData)));
								}

								// search existing attachment in files folder
								if (empty($attach_id)){

									$wpai_uploads = self::$uploads['basedir'] . DIRECTORY_SEPARATOR . \PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR;
									$wpai_file_path = $wpai_uploads . str_replace('%20', ' ', $atch_url);

									self::$logger and call_user_func(self::$logger, sprintf(__('- Searching for existing file `%s`', 'wp_all_import_plugin'), $wpai_file_path));

									if ( @file_exists($wpai_file_path) and @copy( $wpai_file_path, $attachment_filepath )){
										// validate import attachments
										if( ! $wp_filetype = wp_check_filetype(wp_all_import_basename($attachment_filepath), null )) {
											self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Can\'t detect attachment file type %s', 'wp_all_import_plugin'), trim($attachment_filepath)));
											self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
											@unlink($attachment_filepath);
										}
										else {
											$create_file = true;
											$download_file = false;
											self::$logger and call_user_func(self::$logger, sprintf(__('- File `%s` has been successfully found', 'wp_all_import_plugin'), $wpai_file_path));
										}
									}
								}
							}

							if ($download_file && preg_match('%^(http|ftp)s?://%i', $atch_url)){

								$attachment_filename = wp_unique_filename($targetDir, $attachment_filename);
								$attachment_filepath = $targetDir . '/' . sanitize_file_name($attachment_filename);

								self::$logger and call_user_func(self::$logger, sprintf(__('- Filename for attachment was generated as %s', 'wp_all_import_plugin'), $attachment_filename));

								$request = get_file_curl(trim($atch_url), $attachment_filepath);

								$get_ctx = stream_context_create(array('http' => array('timeout' => 5)));

								if ( (is_wp_error($request) or $request === false)  and ! @file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url), false, $get_ctx))) {
									self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'wp_all_import_plugin'), trim($atch_url), $attachment_filepath));
									is_wp_error($request) and self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WP Error</b>: %s', 'wp_all_import_plugin'), $request->get_error_message()));
									self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
									unlink($attachment_filepath); // delete file since failed upload may result in empty file created
								} elseif( ! $wp_filetype = wp_check_filetype(wp_all_import_basename($attachment_filename), null )) {
									self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Can\'t detect attachment file type %s', 'wp_all_import_plugin'), trim($atch_url)));
									self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
								} else {
									$create_file = true;
								}
							}

							if ($create_file){
								$handle_attachment = apply_filters( 'wp_all_import_handle_upload', array(
									'file' => $attachment_filepath,
									'url'  => $targetUrl . '/' . wp_all_import_basename($attachment_filepath),
									'type' => $wp_filetype['type']
								));

								self::$logger and call_user_func(self::$logger, sprintf(__('- File %s has been successfully downloaded', 'wp_all_import_plugin'), $atch_url));
								$attachment_data = array(
									'guid' => $handle_attachment['url'],
									'post_mime_type' => $handle_attachment['type'],
									'post_title' => preg_replace('/\.[^.]+$/', '', wp_all_import_basename($handle_attachment['file'])),
									'post_content' => '',
									'post_status' => 'inherit',
									'post_author' => $this->postAuthor,
								);
								$attach_id = wp_insert_attachment( $attachment_data, $handle_attachment['file'], $this->pid );

								if (is_wp_error($attach_id)) {
									self::$logger and call_user_func(self::$logger, __('- <b>WARNING</b>', 'wp_all_import_plugin') . ': ' . $attach_id->get_error_message());
									self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
								} else {
									wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $handle_attachment['file']));
									self::$logger and call_user_func(self::$logger, sprintf(__('- Attachment has been successfully created for post `%s`', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));
									self::$logger and call_user_func(self::$logger, __('- <b>ACTION</b>: pmxi_attachment_uploaded', 'wp_all_import_plugin'));
									do_action( 'pmxi_attachment_uploaded', $this->pid, $attach_id, $handle_attachment['file']);
								}
							}

							if ($attach_id && ! is_wp_error($attach_id))
							{
								if ($attch != null && empty($attch->post_parent) && ! in_array($this->postType, array('taxonomies'))){
									wp_update_post(
										array(
											'ID' => $attch->ID,
											'post_parent' => $this->pid
										)
									);
								}

								if ($attch != null and empty($attch->post_parent))
								{
									self::$logger and call_user_func(self::$logger, sprintf(__('- Attachment has been successfully updated for file `%s`', 'wp_all_import_plugin'), (isset($handle_attachment)) ? $handle_attachment['url'] : $targetUrl . '/' . wp_all_import_basename($attachment_filepath)));
								}
								elseif(empty($attch))
								{
									self::$logger and call_user_func(self::$logger, sprintf(__('- Attachment has been successfully created for file `%s`', 'wp_all_import_plugin'), (isset($handle_attachment)) ? $handle_attachment['url'] : $targetUrl . '/' . wp_all_import_basename($attachment_filepath)));
								}
								self::$logger and call_user_func(self::$logger, __('- <b>ACTION</b>: pmxi_attachment_uploaded', 'wp_all_import_plugin'));
								do_action( 'pmxi_attachment_uploaded', $this->pid, $attach_id, $attachment_filepath);
							}
						}
					}
				}
			}
		}

		if ( ! $is_attachments_to_update )
		{
			self::$logger and call_user_func(self::$logger, sprintf(__('Attachments import skipped for post `%s` according to \'pmxi_is_attachments_to_update\' filter...', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));
		}
	}

	private function preProcessImagesMeta(){
		if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_title'] and !empty(self::$image_meta_titles_bundle[$this->slug])){
			$this->img_titles = [];
			$line_img_titles = explode("\n", self::$image_meta_titles_bundle[$this->slug][$this->recordIndex]);
			if ( ! empty($line_img_titles) )
				foreach ($line_img_titles as $line_img_title)
					$this->img_titles = array_merge($this->img_titles, ( ! empty(self::$importRecord->options[$this->option_slug . 'image_meta_title_delim']) ) ? explode(self::$importRecord->options[$this->option_slug . 'image_meta_title_delim'], $line_img_title) : array($line_img_title) );

		}
		if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_caption'] and !empty(self::$image_meta_captions_bundle[$this->slug])){
			$this->img_captions = array();
			$line_img_captions = explode("\n", self::$image_meta_captions_bundle[$this->slug][$this->recordIndex]);
			if ( ! empty($line_img_captions) )
				foreach ($line_img_captions as $line_img_caption)
					$this->img_captions = array_merge($this->img_captions, ( ! empty(self::$importRecord->options[$this->option_slug . 'image_meta_caption_delim']) ) ? explode(self::$importRecord->options[$this->option_slug . 'image_meta_caption_delim'], $line_img_caption) : array($line_img_caption) );

		}
		if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_alt'] and !empty(self::$image_meta_alts_bundle[$this->slug])){
			$this->img_alts = array();
			$line_img_alts = explode("\n", self::$image_meta_alts_bundle[$this->slug][$this->recordIndex]);
			if ( ! empty($line_img_alts) )
				foreach ($line_img_alts as $line_img_alt)
					$this->img_alts = array_merge($this->img_alts, ( ! empty(self::$importRecord->options[$this->option_slug . 'image_meta_alt_delim']) ) ? explode(self::$importRecord->options[$this->option_slug . 'image_meta_alt_delim'], $line_img_alt) : array($line_img_alt) );

		}
		if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_description'] and !empty(self::$image_meta_descriptions_bundle[$this->slug])){
			$this->img_descriptions = array();
			$line_img_descriptions = (self::$importRecord->options[$this->option_slug . 'image_meta_description_delim_logic'] == 'line' or empty(self::$importRecord->options[$this->option_slug . 'image_meta_description_delim'])) ? explode("\n", self::$image_meta_descriptions_bundle[$this->slug][$this->recordIndex]) : array(self::$image_meta_descriptions_bundle[$this->slug][$this->recordIndex]);
			if ( ! empty($line_img_descriptions) )
				foreach ($line_img_descriptions as $line_img_description)
					$this->img_descriptions = array_merge($this->img_descriptions, (self::$importRecord->options[$this->option_slug . 'image_meta_description_delim_logic'] == 'separate' and ! empty(self::$importRecord->options[$this->option_slug . 'image_meta_description_delim']) ) ? explode(self::$importRecord->options[$this->option_slug . 'image_meta_description_delim'], $line_img_description) : array($line_img_description) );

		}

	}

	private function importContentImages(){

		require_once(ABSPATH . 'wp-admin/includes/image.php');

		self::$logger and call_user_func(self::$logger, __('<b>CONTENT IMAGES:</b>', 'wp_all_import_plugin'));

		// search for images in galleries
		$galleries = array();
		if (preg_match_all('%\[gallery[^\]]*ids="([^\]^"]*)"[^\]]*\]%is', $this->articleData['post_content'], $matches, PREG_PATTERN_ORDER)) {
			$galleries = array_unique(array_filter($matches[1]));
		}
		$gallery_images = array();
		if ( ! empty($galleries) ){
			foreach ($galleries as $key => $gallery) {
				$imgs = array_unique(array_filter(explode(",", $gallery)));
				if (!empty($imgs)){
					foreach ($imgs as $img) {
						if ( ! is_numeric($img) ){
							$gallery_images[] = json_decode(base64_decode($img), true);
						}
					}
				}
			}
		}
		// search for images in <img> tags
		$tag_images = array();
		if (preg_match_all('%<img\s[^>]*src=(?(?=")"([^"]*)"|(?(?=\')\'([^\']*)\'|([^\s>]*)))%is', $this->articleData['post_content'], $matches, PREG_PATTERN_ORDER)) {
			$tag_images = array_unique(array_merge(array_filter($matches[1]), array_filter($matches[2]), array_filter($matches[3])));
		}

		if (preg_match_all('%<img\s[^>]*srcset=(?(?=")"([^"]*)"|(?(?=\')\'([^\']*)\'|([^\s>]*)))%is', $this->articleData['post_content'], $matches, PREG_PATTERN_ORDER)) {
			$srcset_images = array_unique(array_merge(array_filter($matches[1]), array_filter($matches[2]), array_filter($matches[3])));
			if (!empty($srcset_images)) {
				foreach ($srcset_images as $srcset_image) {
					$srcset = explode(",", $srcset_image);
					$srcset = array_filter($srcset);
					foreach($srcset as $srcset_img) {
						$srcset_image_parts = explode(" ", $srcset_img);
						foreach ($srcset_image_parts as $srcset_image_part) {
							if ( !empty(filter_var($srcset_image_part, FILTER_VALIDATE_URL)) ) {
								$tag_images[] = trim($srcset_image_part);
							}
						}
					}
				}
			}
		}

		$content_images_try_go_get_full_size = apply_filters('wp_all_import_content_images_get_full_size', true, $this->articleData, self::$importRecord->id);

		$images_sources = array(
			'gallery' => $gallery_images,
			'tag' => $tag_images
		);

		foreach ($images_sources as $source_type => $images){

			if ( empty($images) ) continue;

			foreach ( $images as $image ){

				$base64_name = false;

				$is_base64_images_allowed = apply_filters("wp_all_import_is_base64_images_allowed", true, $image, self::$importRecord->id);

				$current_image_is_base64 = wp_all_import_is_base64_encoded($image);

				if($is_base64_images_allowed && $current_image_is_base64){
					$base64_name = md5($image) .'.'. ($this->get_base64_image_type($image) ?? 'jpg');
				}elseif (!$is_base64_images_allowed && $current_image_is_base64){
					self::$logger and call_user_func(self::$logger, __('<b>ERROR</b>: Base64 encoded images have been disallowed by `wp_all_import_is_base64_images_allowed`', 'wp_all_import_plugin'));
					continue;
				}

				$image_uploads = apply_filters('wp_all_import_single_image_uploads_dir', $this->imageUploadDir, $image, $this->articleData, $this->currentXmlNode, self::$importRecord->id, $this->pid);

				if (empty($image_uploads)) {
					self::$logger and call_user_func(self::$logger, __('<b>ERROR</b>: Target directory is not defined', 'wp_all_import_plugin'));
					continue;
				}

				$targetDir = $image_uploads['path'];
				$targetUrl = $image_uploads['url'];

				if ( ! @is_writable($targetDir) ){
					self::$logger and call_user_func(self::$logger, sprintf(__('<b>ERROR</b>: Target directory %s is not writable', 'wp_all_import_plugin'), $targetDir));
					continue;
				}

				if ($source_type == 'gallery'){
					$image_data = $image;
					$image = $image_data['url'];
					$image_title = $image_data['title'];
					$image_caption = $image_data['caption'];
					$image_alt = $image_data['alt'];
					$image_description = $image_data['description'];
				}
				$original_image_url = $image;
				// Trying to get image full size.
				if ($content_images_try_go_get_full_size) {
					$full_size = preg_replace('%-\d{2,4}x\d{2,4}%', '', $image);
					if ($full_size != $image){
						// check if full size image exists
						$full_size_headers = get_headers($full_size, true);
						if (!empty($full_size_headers['Content-Type'])) {
							if (is_array($full_size_headers['Content-Type'])) {
								$full_size_headers['Content-Type'] = end($full_size_headers['Content-Type']);
							}
							if (strpos($full_size_headers['Content-Type'], 'image') !== false){
								$image = $full_size;
							}
						}
					}
				}

				if (empty($image)) continue;

				$attid = false;

				$url = html_entity_decode(trim($image), ENT_QUOTES);

				if (empty($url)) continue;

				$bn  = wp_all_import_sanitize_filename(urldecode(wp_all_import_basename((!empty($base64_name)) ? $base64_name : $url)));

				$img_ext = pmxi_getExtensionFromStr((!empty($base64_name)) ? $base64_name : $url);
				$default_extension = pmxi_getExtension($bn);
				if ($img_ext == "") $img_ext = pmxi_get_remote_image_ext($url);

				// Generate local file name.
				$image_name = urldecode(("" != $img_ext) ? str_replace("." . $default_extension, "", $bn) : $bn) . (("" != $img_ext) ? '.' . $img_ext : '');

				$image_filename = wp_unique_filename($targetDir, $image_name);
				$image_filepath = $targetDir . '/' . $image_filename;

				if (self::$importRecord->options['search_existing_images']) {

					$attch = $this->findExistingImage($image, !empty($base64_name) ? $base64_name : $image_name, $targetDir, $base64_name);

				}

				// Existing image found.
				if (!empty($attch)) {
					$attid = $attch->ID;
					self::$logger and call_user_func(self::$logger, sprintf(__('- Existing image was found for post content `%s`...', 'wp_all_import_plugin'), (!empty($base64_name)) ? $base64_name : rawurldecode($image)));
				} else {

					if (self::$importRecord->options['search_existing_images']) {
						self::$logger and call_user_func(self::$logger, sprintf(__('- Image `%s` was not found...', 'wp_all_import_plugin'), (!empty($base64_name)) ? $base64_name : rawurldecode($image)));
					}

					// Handle base64_encoded image.
					if (empty($base64_name)){
						$image_info = $this->downloadFile($url, $image_filepath, self::$is_cron);
					}else{
						$img = @imagecreatefromstring(wp_all_import_base64_decode_image($url));
						if($img)
						{
							self::$logger and call_user_func(self::$logger, __('- found base64_encoded image', 'wp_all_import_plugin'));

							imagejpeg($img, $image_filepath);
							if( ! ($image_info = apply_filters('pmxi_getimagesize', @getimagesize($image_filepath), $image_filepath)) or ! in_array($image_info[2], wp_all_import_supported_image_types())) {
								self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $image_filepath));
								self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
							}
						}
					}


					if ($image_info) {
						// Create an attachment.
						$file_mime_type = '';
						if (!empty($image_info) && is_array($image_info)) {
							$file_mime_type = image_type_to_mime_type($image_info[2]);
						}
						$file_mime_type = apply_filters('wp_all_import_image_mime_type', $file_mime_type, $image_filepath);
						$handle_image = array(
							'file' => $image_filepath,
							'url'  => $targetUrl . '/' . $image_filename,
							'type' => $file_mime_type
						);
						$attid = $this->createAttachment($this->pid, $handle_image, $image_name, $this->postAuthor, $this->postType, self::$is_cron);
					}
				}

				// attachment founded or successfully created
				if ($attid){

					$this->saveToImagesTable($attid, ($image_name ?? $base64_name), $image);

					if ($source_type == 'gallery') {
						$this->articleData['post_content'] = str_replace(base64_encode(json_encode($image_data)), $attid, $this->articleData['post_content']);
					}
					$attachmentURL = wp_get_attachment_url($attid);
					if ($attachmentURL){
						preg_match('%-\d{2,4}x\d{2,4}%', $original_image_url, $matches);
						if (!empty($matches)){
							$attachment_thumbnail_url = preg_replace('%\.(\D{2,4})$%', $matches[0] . '.$1', $attachmentURL);
							// check is thumbnail exists
							$attachment_thumbnail_path = str_replace($targetUrl, self::$uploads['path'], $attachment_thumbnail_url);
							if (file_exists($attachment_thumbnail_path)){
								$this->articleData['post_content'] = str_replace($original_image_url, $attachment_thumbnail_url, $this->articleData['post_content']);
							}
							else{
								$this->articleData['post_content'] = str_replace($original_image_url, $attachmentURL, $this->articleData['post_content']);
							}
						}
						$this->articleData['post_content'] = str_replace($image, $attachmentURL, $this->articleData['post_content']);
					}

					if ($source_type == 'gallery'){
						$update_attachment_meta = array();
						$update_attachment_meta['post_title'] = trim($image_title);
						$update_attachment_meta['post_excerpt'] = trim($image_caption);
						$update_attachment_meta['post_content'] =  trim($image_description);
						update_post_meta($attid, '_wp_attachment_image_alt', trim($image_alt));
						global $wpdb;
						$wpdb->update( $wpdb->posts, $update_attachment_meta, array('ID' => $attid) );
					}
					if (empty($featuredImage) && $this->isImageToUpdate) {
						$featuredImage = $attid;
					}
				}
			}
			switch (self::$importRecord->options['custom_type']){
				case 'import_users':
				case 'shop_customer':
				case 'shop_order':
				case 'gf_entries':
					// No action needed.
					break;
				case 'comments':
				case 'woo_reviews':
					wp_update_comment([
						'comment_ID' => $this->pid,
						'comment_content' => $this->articleData['post_content']
					]);
					break;
				case 'taxonomies':
					wp_update_term($this->pid, self::$importRecord->options['taxonomy_type'], [
						'description' => wp_kses_post($this->articleData['post_content'])
					]);
					break;
				default:
					self::$importRecord->importer->update_content($this->pid, $this->articleData['post_content']);
					break;
			}
		}

	}

	public function importImages(){

		$featuredImage = false;

		// Content Images
		if (!empty($this->articleData['post_content']) && (empty($this->articleData['ID']) || self::$importRecord->options['is_keep_former_posts'] == "no" && (self::$importRecord->options['update_all_data'] == "yes" || self::$importRecord->options['is_update_content'])) && self::$importRecord->options['import_img_tags'] && "gallery" !== self::$importRecord->options['download_images'] ) {

			$this->importContentImages();
		}

		if ( ! in_array(self::$importRecord->options['custom_type'], array('shop_order', 'import_users', 'shop_customer')) ) {

			self::$logger and call_user_func(self::$logger, __('<b>IMAGES:</b>', 'wp_all_import_plugin'));
		}

		if ( $this->isImageToUpdate and ! empty($this->imageUploadDir) and false === $this->imageUploadDir['error'] and (empty($this->articleData['ID']) or self::$importRecord->options['update_all_data'] == "yes" or ( self::$importRecord->options['update_all_data'] == "no" and self::$importRecord->options['is_update_images']))) {

			if ( ! empty(self::$images_bundle) ){

				require_once(ABSPATH . 'wp-admin/includes/image.php');

				$is_show_add_new_images = apply_filters('wp_all_import_is_show_add_new_images', true, $this->postType);

				$is_images_section_enabled = apply_filters('wp_all_import_is_images_section_enabled', true, $this->postType);

				foreach (self::$images_bundle as $this->slug => $this->bundle_data) {

					if ( ! $is_images_section_enabled && $this->slug == 'pmxi_gallery_image' ) continue;

					$featured_images = $this->bundle_data['files'];

					$this->option_slug = ($this->slug == 'pmxi_gallery_image') ? '' : $this->slug;

					$gallery_attachment_ids = array();

					if ( ! empty($featured_images[$this->recordIndex]) ){

						$targetDir = $this->imageUploadDir['path'];
						$targetUrl = $this->imageUploadDir['url'];

						if ( ! @is_writable($targetDir) ){
							self::$logger and call_user_func(self::$logger, sprintf(__('<b>ERROR</b>: Target directory %s is not writable', 'wp_all_import_plugin'), $targetDir));
						}
						else{

							$success_images = false;

							$imgs = array();

							switch (self::$importRecord->options[$this->option_slug . 'download_images']) {
								case 'no':
									$featured_delim = self::$importRecord->options[$this->option_slug . 'featured_delim'];
									break;
								case 'gallery':
									$featured_delim = self::$importRecord->options[$this->option_slug . 'gallery_featured_delim'];
									break;
								default: // yes
									$featured_delim = self::$importRecord->options[$this->option_slug . 'download_featured_delim'];
									break;
							}

							$line_imgs = explode("\n", $featured_images[$this->recordIndex]);
							if ( ! empty($line_imgs) )
								foreach ($line_imgs as $line_img)
									$imgs = array_merge($imgs, ( ! empty($featured_delim) ) ? str_getcsv($line_img, $featured_delim) : array($line_img) );

							// keep existing and add newest images
							if ( ! empty($this->articleData['ID']) and self::$importRecord->options['is_update_images'] and self::$importRecord->options['update_images_logic'] == "add_new" and self::$importRecord->options['update_all_data'] == "no" and $is_show_add_new_images){

								self::$logger and call_user_func(self::$logger, __('- Keep existing and add newest images ...', 'wp_all_import_plugin'));

								$attachment_imgs = get_attached_media( 'image', $this->pid );

								if ( $this->postType == "product" ){
									$gallery_attachment_ids = array_filter(explode(",", get_post_meta($this->pid, '_product_image_gallery', true)));
								}

								if ( $attachment_imgs ) {
									foreach ( $attachment_imgs as $attachment_img ) {
										$post_thumbnail_id = get_post_thumbnail_id( $this->pid );
										if ( empty($post_thumbnail_id) and self::$importRecord->options[$this->option_slug . 'is_featured'] ) {
											set_post_thumbnail($this->pid, $attachment_img->ID);
										}
										elseif(!in_array($attachment_img->ID, $gallery_attachment_ids) and $post_thumbnail_id != $attachment_img->ID) {
											$gallery_attachment_ids[] = $attachment_img->ID;
										}
									}
									$success_images = true;
								}

								if ( ! empty($gallery_attachment_ids) ){
									foreach ($gallery_attachment_ids as $aid){
										do_action( $this->slug, $this->pid, $aid, wp_get_attachment_url($aid), 'update_images');
									}
								}
							}

							if ( ! empty($imgs) ) {

								$this->preProcessImagesMeta();

								$is_keep_existing_images = ( ! empty($this->articleData['ID']) and self::$importRecord->options['is_update_images'] and self::$importRecord->options['update_images_logic'] == "add_new" and self::$importRecord->options['update_all_data'] == "no" and $is_show_add_new_images);

								if ( 'yes' === self::$importRecord->options[$this->option_slug . 'preload_images']) {
									// Only preload images if there are more than 2 for this record.
									count($imgs) > 2 &&	$this->downloadAllImages($imgs);
								}

								foreach ($imgs as $k => $img_url) {

									if(empty($img_url)){
										continue;
									}

									$image_uploads = apply_filters('wp_all_import_single_image_uploads_dir', $this->imageUploadDir, $img_url, $this->articleData, $this->currentXmlNode, self::$importRecord->id, $this->pid);

									if (empty($image_uploads)) {
										self::$logger and call_user_func(self::$logger, __('<b>ERROR</b>: Target directory is not defined', 'wp_all_import_plugin'));
										continue;
									}

									$targetDir = $image_uploads['path'];
									$targetUrl = $image_uploads['url'];

									if ( ! @is_writable($targetDir) ){
										self::$logger and call_user_func(self::$logger, sprintf(__('<b>ERROR</b>: Target directory %s is not writable', 'wp_all_import_plugin'), $targetDir));
										continue;
									}

									$attid = false;

									$attch = null;

									// remove encoded quotes from url (&#34; and &#39;)
									$url = html_entity_decode(trim($img_url), ENT_QUOTES);

									if (empty($url)) continue;

									$image_name = $this->generateImageName($img_url, $k);

									// if wizard store image data to custom field
									$create_image   = false;
									$download_image = true;
									$wp_filetype    = false;

									$is_base64_images_allowed = apply_filters("wp_all_import_is_base64_images_allowed", true, $url, self::$importRecord->id);

									if ( $this->bundle_data['type'] == 'images' and wp_all_import_is_base64_encoded($url) and $is_base64_images_allowed ){
										$image_name = empty(self::$importRecord->options[$this->option_slug . 'auto_rename_images']) ? md5($url) . '.jpg' : sanitize_file_name(self::$auto_rename_images_bundle[$this->slug][$this->recordIndex]) . '.jpg';
										$image_name = apply_filters("wp_all_import_image_filename", $image_name, empty($this->img_titles[$k]) ? '' : $this->img_titles[$k], empty($this->img_captions[$k]) ? '' : $this->img_captions[$k], empty($this->img_alts[$k]) ? '' : $this->img_alts[$k], $this->articleData, self::$importRecord->id, $img_url);

										$image_filename = $image_name;

										// search existing attachment
										if (self::$importRecord->options[$this->option_slug . 'search_existing_images'] or "gallery" == self::$importRecord->options[$this->option_slug . 'download_images']){

											$attch = wp_all_import_get_image_from_gallery($image_name, $targetDir, $this->bundle_data['type'], self::$logger);

											if ("gallery" == self::$importRecord->options[$this->option_slug . 'download_images']) $download_image = false;

											if (empty($attch)) {
												self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Image %s not found in media gallery.', 'wp_all_import_plugin'), trim($image_name)));
											}
											else {
												self::$logger and call_user_func(self::$logger, sprintf(__('- Using existing image `%s` for post `%s` ...', 'wp_all_import_plugin'), trim($image_name), self::$importRecord->getRecordTitle($this->articleData)));
												$download_image = false;
												$create_image   = false;
												$attid 			= $attch->ID;
											}
										}

										// Handle base64_encoded image.
										if ($download_image){
											$img = @imagecreatefromstring(wp_all_import_base64_decode_image($url));
											if($img)
											{
												self::$logger and call_user_func(self::$logger, __('- found base64_encoded image', 'wp_all_import_plugin'));

												$image_filename = wp_unique_filename($targetDir, $image_filename);
												$image_filepath = $targetDir . '/' . $image_filename;
												imagejpeg($img, $image_filepath);
												if( ! ($image_info = apply_filters('pmxi_getimagesize', @getimagesize($image_filepath), $image_filepath)) or ! in_array($image_info[2], wp_all_import_supported_image_types())) {
													self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $image_filepath));
													self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
												} else {
													$create_image = true;
												}
											}
										}
									}

									if ( ! $create_image && empty($attch) ) {

										if (self::$importRecord->options[$this->option_slug . 'auto_rename_images'] and !empty(self::$auto_rename_images_bundle[$this->slug][$this->recordIndex])) {
											if ($k) {
												$image_name = str_replace('.' . pmxi_getExtension($image_name), '', $image_name) . '-' . $k . '.' . pmxi_getExtension($image_name);
											}
										}

										$image_filename = wp_unique_filename($targetDir, $image_name);
										$image_filepath = $targetDir . '/' . $image_filename;

										// search existing attachment
										if (self::$importRecord->options[$this->option_slug . 'search_existing_images'] or "gallery" == self::$importRecord->options[$this->option_slug . 'download_images']){

											$image_filename = $image_name;

											if (self::$importRecord->options[$this->option_slug . 'download_images'] === "yes") {
												// trying to find existing image in images table
												$attch = $this->findExistingImage( $url, $image_name, $targetDir );
											}

											if ("gallery" == self::$importRecord->options[$this->option_slug . 'download_images']) {
												$download_image = false;
											}

											// Search for existing images for new imports only using old logic.
											if (self::$importRecord->options[$this->option_slug . 'download_images'] !== "yes" && empty($attch)) {
												self::$logger and call_user_func(self::$logger, sprintf(__('- Search for existing image `%s` by `_wp_attached_file` ...', 'wp_all_import_plugin'), rawurldecode($image_name)));
												$attch = wp_all_import_get_image_from_gallery($image_name, $targetDir, $this->bundle_data['type'], self::$logger);
											}

											if (empty($attch)) {
												self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Image %s not found in media gallery.', 'wp_all_import_plugin'), rawurldecode($image_name)));
											}
											else {
												self::$logger and call_user_func(self::$logger, sprintf(__('- Using existing image `%s` for post `%s` ...', 'wp_all_import_plugin'), rawurldecode($image_name), self::$importRecord->getRecordTitle($this->articleData)));
												$download_image = false;
												$create_image   = false;
												$attid 			= $attch->ID;
												// save image into images table
												$this->saveToImagesTable($attid,$image_name, $url);
											}
										}

										if ($download_image && "gallery" != self::$importRecord->options[$this->option_slug . 'download_images']){

											// do not download images
											if ( "no" == self::$importRecord->options[$this->option_slug . 'download_images'] ){

												$image_filename = $image_name;
												$image_filepath = $targetDir . '/' . $image_filename;
												if ( @file_exists($image_filepath) ) {
													$image_filename = wp_unique_filename($targetDir, $image_name);
													$image_filepath = $targetDir . '/' . $image_filename;
												}

												$wpai_uploads = self::$uploads['basedir'] . DIRECTORY_SEPARATOR . \PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR;
												$wpai_image_path = $wpai_uploads . str_replace('%20', ' ', $url);

												self::$logger and call_user_func(self::$logger, sprintf(__('- Searching for existing image `%s`', 'wp_all_import_plugin'), $wpai_image_path));

												if ( @file_exists($wpai_image_path) and @copy( $wpai_image_path, $image_filepath )){
													$download_image = false;
													// validate import attachments
													if ($this->bundle_data['type'] == 'files'){
														if( ! $wp_filetype = wp_check_filetype(wp_all_import_basename($image_filepath), null )) {
															self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: Can\'t detect attachment file type %s', 'wp_all_import_plugin'), trim($image_filepath)));
															self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
															@unlink($image_filepath);
														}
														else {
															$create_image = true;
															self::$logger and call_user_func(self::$logger, sprintf(__('- File `%s` has been successfully found', 'wp_all_import_plugin'), $wpai_image_path));
														}
													}
													// validate import images
													elseif($this->bundle_data['type'] == 'images'){
														if( preg_match('%\W(svg)$%i', wp_all_import_basename($image_filepath)) or $image_info = apply_filters('pmxi_getimagesize', @getimagesize($image_filepath), $image_filepath) and in_array($image_info[2], wp_all_import_supported_image_types())) {
															$create_image = true;
															self::$logger and call_user_func(self::$logger, sprintf(__('- Image `%s` has been successfully found', 'wp_all_import_plugin'), $wpai_image_path));
														}
														else
														{
															self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $image_filepath));
															self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
															@unlink($image_filepath);
														}
													}
												}
											} else {
												$image_info = $this->downloadFile($url, $image_filepath, self::$is_cron, $this->bundle_data['type']);

												if ( ! $image_info ) {
													if ( $img_url !== pmxi_convert_encoding($img_url) ) {
														$url = trim(pmxi_convert_encoding($img_url));
														$image_info = $this->downloadFile($url, $image_filepath, self::$is_cron, $this->bundle_data['type']);
													}
													else{
														file_exists($image_filepath) && unlink($image_filepath);
													}
												}
												$create_image = empty($image_info) ? false : true;
											}
										}
									}

									$handle_image = false;

									// existing image not founded, create new attachment
									if ($create_image){

										$file_mime_type = '';

										if ($this->bundle_data['type'] == 'images') {
											if ( ! empty($image_info) && is_array($image_info) ) {
												$file_mime_type = image_type_to_mime_type($image_info[2]);
											}
											$file_mime_type = apply_filters('wp_all_import_image_mime_type', $file_mime_type, $image_filepath);
										}
										else {
											$file_mime_type = $image_info['type'];
										}

										$handle_image = apply_filters( 'wp_all_import_handle_upload', array(
											'file' => $image_filepath,
											'url'  => $targetUrl . '/' . $image_filename,
											'type' => $file_mime_type
										));

										$attid = $this->createAttachment($this->pid, $handle_image, $image_name, $this->postAuthor, $this->postType, self::$is_cron, $this->bundle_data['type']);

										// save image into images table
										$this->saveToImagesTable($attid, $image_name, $url);
									}

									if ($attid && ! is_wp_error($attid))
									{
										if ($attch != null && empty($attch->post_parent) && ! in_array($this->postType, array('taxonomies'))){
											wp_update_post(
												array(
													'ID' => $attch->ID,
													'post_parent' => $this->pid
												)
											);
										}

										$update_attachment_meta = array();
										if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_title'] and ! empty($this->img_titles[$k]) ) $update_attachment_meta['post_title'] = trim($this->img_titles[$k]);
										if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_caption'] and ! empty($this->img_captions[$k]) ) $update_attachment_meta['post_excerpt'] =  trim($this->img_captions[$k]);
										if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_description'] and ! empty($this->img_descriptions[$k]) ) $update_attachment_meta['post_content'] =  trim($this->img_descriptions[$k]);
										if ( self::$importRecord->options[$this->option_slug . 'set_image_meta_alt'] and ! empty($this->img_alts[$k]) ) update_post_meta($attid, '_wp_attachment_image_alt', trim($this->img_alts[$k]));

										if ( !empty($update_attachment_meta)) {
											$update_attachment_meta['ID'] = $attid;
											$gallery_post = wp_update_post( $update_attachment_meta, true );
											if (is_wp_error($gallery_post)) {
												self::$logger and call_user_func(self::$logger, sprintf(__('- <b>ERROR</b>: %s', 'wp_all_import_plugin'), $gallery_post));
											}
										}

										self::$logger and call_user_func(self::$logger, __('- <b>ACTION</b>: ' . $this->slug, 'wp_all_import_plugin'));
										do_action( $this->slug, $this->pid, $attid, ($handle_image) ? $handle_image['file'] : $image_filepath, $is_keep_existing_images ? 'add_images' : 'update_images');

										$success_images = true;

										switch ($this->postType){
											case 'taxonomies':
												$post_thumbnail_id = get_term_meta( $this->pid, 'thumbnail_id', true );
												if ($this->bundle_data['type'] == 'images' and empty($post_thumbnail_id) and (self::$importRecord->options[$this->option_slug . 'is_featured'] or !empty($is_image_featured[$this->recordIndex])) ) {
													update_term_meta($this->pid, 'thumbnail_id', $attid);
												}
												elseif(!in_array($attid, $gallery_attachment_ids) and $post_thumbnail_id != $attid){
													$gallery_attachment_ids[] = $attid;
												}
												break;
											default:
												$post_thumbnail_id = get_post_thumbnail_id( $this->pid );

												if ($this->bundle_data['type'] == 'images' and empty($post_thumbnail_id) and (self::$importRecord->options[$this->option_slug . 'is_featured'] or !empty($is_image_featured[$this->recordIndex])) ) {
													set_post_thumbnail($this->pid, $attid);
												}
												elseif(!in_array($attid, $gallery_attachment_ids) and $post_thumbnail_id != $attid){
													$gallery_attachment_ids[] = $attid;
												}
												break;
										}

										if ($attch != null and empty($attch->post_parent)) {
											self::$logger and call_user_func(self::$logger, sprintf(__('- Attachment with ID: `%s` has been successfully updated for image `%s`', 'wp_all_import_plugin'), $attid, ($handle_image) ? $handle_image['url'] : $targetUrl . '/' . $image_filename));
										}
										elseif(empty($attch)) {
											self::$logger and call_user_func(self::$logger, sprintf(__('- Attachment with ID: `%s` has been successfully created for image `%s`', 'wp_all_import_plugin'), $attid, ($handle_image) ? $handle_image['url'] : $targetUrl . '/' . $image_filename));
										}
									}
								}
							}

							// Set product gallery images
							if ( $this->postType == "product" ){
								update_post_meta($this->pid, '_product_image_gallery', (!empty($gallery_attachment_ids)) ? implode(',', $gallery_attachment_ids) : '');
							}

							// Create entry as Draft if no images are downloaded successfully
							$final_post_type = get_post_type($this->pid);
							if ( ! $success_images and "yes" == self::$importRecord->options[$this->option_slug . 'create_draft'] and ! in_array($this->postType, array('taxonomies', 'comments', 'woo_reviews'))) {
								global $wpdb;
								$wpdb->update( $wpdb->posts, array('post_status' => 'draft'), array('ID' => $this->pid) );
								self::$logger and call_user_func(self::$logger, sprintf(__('- Post `%s` saved as Draft, because no images are downloaded successfully', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));
							}
						}
					}
					else{
						// Create entry as Draft if no images are downloaded successfully
						$final_post_type = get_post_type($this->pid);
						if ( "yes" == self::$importRecord->options[$this->option_slug . 'create_draft'] and ! in_array($this->postType, array('taxonomies', 'comments', 'woo_reviews'))){
							global $wpdb;
							$wpdb->update( $wpdb->posts, array('post_status' => 'draft'), array('ID' => $this->pid) );
							self::$logger and call_user_func(self::$logger, sprintf(__('Post `%s` saved as Draft, because no images are downloaded successfully', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));
						}
					}

					if ( self::$importRecord->options[$this->option_slug . "download_images"] == 'gallery' or self::$importRecord->options[$this->option_slug . "do_not_remove_images"] ){
						do_action("wpallimport_after_images_import", $this->pid, $gallery_attachment_ids, $this->missing_images);
					}
				}
			}

		} else {

			if ( ! empty(self::$images_bundle) ) {

				foreach (self::$images_bundle as $this->slug => $this->bundle_data) {

					$this->option_slug = ($this->slug == 'pmxi_gallery_image') ? '' : $this->slug;

					if ( ! empty($this->bundle_data['images'][$this->recordIndex]) ){

						$imgs = array();

						$featured_delim = ( "yes" == self::$importRecord->options[$this->option_slug . 'download_images'] ) ? self::$importRecord->options[$this->option_slug . 'download_featured_delim'] : self::$importRecord->options[$this->option_slug . 'featured_delim'];

						$line_imgs = explode("\n", $this->bundle_data['images'][$this->recordIndex]);
						if ( ! empty($line_imgs) ){
							foreach ($line_imgs as $line_img){
								$imgs = array_merge($imgs, ( ! empty($featured_delim) ) ? str_getcsv($line_img, $featured_delim) : array($line_img) );
							}
						}

						foreach ($imgs as $img) {
							do_action( $this->slug, $this->pid, false, $img, false);
						}
					}
				}
			}
		}

		// Set first image as featured in case when Images section nor defined in import template.
		if (!empty($featuredImage)) {
			$post_thumbnail_id = get_post_thumbnail_id( $this->pid );
			if (empty($post_thumbnail_id) and (self::$importRecord->options['is_featured'] or !empty($is_image_featured[$this->recordIndex])) ) {
				set_post_thumbnail($this->pid, $featuredImage);
			}
		}

		if ( ! $this->isImageToUpdate )
		{
			self::$logger and call_user_func(self::$logger, sprintf(__('Images import skipped for post `%s` according to \'pmxi_is_images_to_update\' filter...', 'wp_all_import_plugin'), self::$importRecord->getRecordTitle($this->articleData)));
		}
	}

	public function importCore() {

		$this->deleteImageMetaFields();
		$this->importImages();
		$this->importAttachments();

	}

	private function generateImageName($img_url, $k){

		// remove encoded quotes from url (&#34; and &#39;)
		$url = html_entity_decode(trim($img_url), ENT_QUOTES);

		$bn  = wp_all_import_sanitize_filename(urldecode(wp_all_import_basename($url)));
		$default_extension = pmxi_getExtension($bn);

		if ( "yes" == self::$importRecord->options[$this->option_slug . 'download_images'] and ! empty(self::$auto_extensions_bundle[$this->slug][$this->recordIndex]) and preg_match('%^(jpg|jpeg|png|gif|webp)$%i', self::$auto_extensions_bundle[$this->slug][$this->recordIndex])){
			$img_ext = self::$auto_extensions_bundle[$this->slug][$this->recordIndex];
		}
		else {
			$img_ext = pmxi_getExtensionFromStr($url);

			if ($img_ext == "") $img_ext = pmxi_get_remote_image_ext($url);
		}

		self::$logger and call_user_func(self::$logger, sprintf(__('- Importing image `%s` for `%s` ...', 'wp_all_import_plugin'), $img_url, self::$importRecord->getRecordTitle($this->articleData)));

		// generate local file name
		$image_name = urldecode((self::$importRecord->options[$this->option_slug . 'auto_rename_images'] and !empty(self::$auto_rename_images_bundle[$this->slug][$this->recordIndex])) ? sanitize_file_name(($img_ext) ? str_replace("." . $default_extension, "", self::$auto_rename_images_bundle[$this->slug][$this->recordIndex]) : self::$auto_rename_images_bundle[$this->slug][$this->recordIndex]) : (($img_ext) ? str_replace("." . $default_extension, "", $bn) : $bn)) . (("" != $img_ext) ? '.' . $img_ext : '');
		$image_name = apply_filters("wp_all_import_image_filename", $image_name, empty($this->img_titles[$k]) ? '' : $this->img_titles[$k], empty($this->img_captions[$k]) ? '' : $this->img_captions[$k], empty($this->img_alts[$k]) ? '' : $this->img_alts[$k], $this->articleData, self::$importRecord->id, $img_url);

		return $image_name;
	}

	private function saveToImagesTable($attid, $image_name, $url){


		if ( $attid && "yes" === self::$importRecord->options[$this->option_slug . 'download_images']){

			$is_base64_images_allowed = apply_filters("wp_all_import_is_base64_images_allowed", true, $url, self::$importRecord->id);
			$imageRecord = new \PMXI_Image_Record();
			$imageRecord->getBy(array(
				'attachment_id' => $attid
			));
			if ($imageRecord->isEmpty()){
				$imageRecord->set(array(
					'attachment_id' => $attid,
					'image_url' => (wp_all_import_is_base64_encoded($url) && $is_base64_images_allowed) ? '' : $url,
					'image_filename' => $image_name
				))->insert();
			}
			else{
				// image already in image table, but was not founded, so updating it with new data
				switch (self::$importRecord->options[$this->option_slug . 'search_existing_images_logic']){
					case 'by_url':
						// update image URL if it was not set
						if (empty($imageRecord->image_url)){
							$imageRecord->set(array(
								'image_url' => (base64_encode(base64_decode($url)) == $url && $is_base64_images_allowed) ? '' : $url
							))->update();
						}
						break;
					default:
						// update image Filename if it was not set
						if (empty($imageRecord->image_filename)){
							$imageRecord->set(array(
								'image_filename' => $image_name
							))->update();
						}
						break;
				}
			}
		}
	}

	private function findExistingImage($url, $image_name, $targetDir, $base64_name = false){

		$imageList = new \PMXI_Image_List();
		$search_existing_image_by = !empty($base64_name) ? 'by_filename' : self::$importRecord->options[$this->option_slug . 'search_existing_images_logic'];

		switch ($search_existing_image_by) {
			case 'by_url':
				$attch = $imageList->getExistingImageByUrl($url);
				self::$logger and call_user_func(self::$logger, sprintf(__('- Searching for existing image `%s` by URL...', 'wp_all_import_plugin'), rawurldecode($url)));
				break;
			default:
				$attch = $imageList->getExistingImageByFilename(basename((!empty($base64_name)) ? $base64_name : $image_name));
				self::$logger and call_user_func(self::$logger, sprintf(__('- Searching for existing image `%s` by filename...', 'wp_all_import_plugin'), basename((!empty($base64_name)) ? $base64_name : $url)));
				if (empty($attch)) {
					self::$logger and call_user_func(self::$logger, sprintf(__('- Search for existing image `%s` by `_wp_attached_file` ...', 'wp_all_import_plugin'), basename((!empty($base64_name)) ? $base64_name : $image_name)));
					$attch = wp_all_import_get_image_from_gallery(basename((!empty($base64_name)) ? $base64_name : $image_name), $targetDir, (!is_null($this->bundle_data) ? ($this->bundle_data['type'] ?? 'images') : 'images'), self::$logger);
				}
				break;
		}

		return $attch;
	}

	public function getPhpAvailableMemory(){
		$memory_limit = ini_get('memory_limit');
		if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
			if ($matches[2] == 'G') {
				$memory_limit = $matches[1] * 1024 * 1024 * 1024;
			} else if ($matches[2] == 'M') {
				$memory_limit = $matches[1] * 1024 * 1024;
			} else if ($matches[2] == 'K') {
				$memory_limit = $matches[1] * 1024;
			}
		}

		$available_memory = $memory_limit - memory_get_usage();

		// Convert bytes to MB.
		return intval($available_memory / 1024 / 1024);

	}

	public function downloadAllImages(&$original_urls){
		$original_urls = array_filter($original_urls);
		$original_urls = array_values($original_urls);
		$original_urls = array_map('trim', $original_urls);

		if (empty($original_urls) || !\extension_loaded('curl')) {
			return;
		}

		self::$logger and call_user_func(self::$logger, __('<b>Note</b>: Attempting to preload images...', 'wp_all_import_plugin'));

		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$max_images_per_preload_iteration = intval($this->getPhpAvailableMemory() / 15);

		$max_images_per_preload_iteration = apply_filters('pmxi_max_images_per_preload_iteration', $max_images_per_preload_iteration);

		$url_chunks = array_chunk($original_urls, $max_images_per_preload_iteration);

		foreach($url_chunks as $urls) {
			// Initialize the multi cURL handle
			$mh                = \curl_multi_init();
			$handles           = [];
			$files             = [];
			$image_filepaths   = [];
			$image_target_urls = [];
			$image_names       = [];

			// Create multiple handles and add them to the multi handle
			foreach ( $urls as $k => $url ) {

				$image_name = $this->generateImageName( $url, $k );

				$image_uploads = apply_filters( 'wp_all_import_single_image_uploads_dir', $this->imageUploadDir, $url, $this->articleData, $this->currentXmlNode, self::$importRecord->id, $this->pid );

				if ( empty( $image_uploads ) ) {
					self::$logger and call_user_func( self::$logger, __( '<b>ERROR</b>: Target directory is not defined', 'wp_all_import_plugin' ) );
					continue;
				}

				$targetDir               = $image_uploads['path'];
				$image_target_urls[ $k ] = $image_uploads['url'];

				if ( self::$importRecord->options[ $this->option_slug . 'auto_rename_images' ] and ! empty( self::$auto_rename_images_bundle[ $this->slug ][ $this->recordIndex ] ) ) {
					if ( $k ) {
						$image_name = str_replace( '.' . pmxi_getExtension( $image_name ), '', $image_name ) . '-' . $k . '.' . pmxi_getExtension( $image_name );
					}
				}

				// Only preload non-existing images.
				if ( ! empty( $this->findExistingImage( $url, $image_name, $targetDir ) ) ) {
					unset( $urls[ $k ] );
					unset( $image_target_urls[ $k ] );
					continue;
				}

				$image_names[ $k ] = $image_name;
				$image_filename        = wp_unique_filename( $targetDir, $image_name );
				$image_filepath        = $targetDir . '/' . $image_filename;
				$image_filepaths[ $k ] = $image_filepath;
				$files[ $k ]   = fopen( $image_filepath, 'w+' );
				$handles[ $k ] = \curl_init();
				\curl_setopt( $handles[ $k ], CURLOPT_URL, $url );
				\curl_setopt( $handles[ $k ], CURLOPT_FILE, $files[ $k ] );
				\curl_setopt( $handles[ $k ], CURLOPT_HEADER, 0 );
				\curl_setopt( $handles[ $k ], CURLOPT_TIMEOUT, apply_filters( 'pmxi_image_download_timeout', 5 ) );
				\curl_setopt( $handles[ $k ], CURLOPT_FOLLOWLOCATION, true );
				\curl_setopt( $handles[ $k ], CURLOPT_SSL_VERIFYPEER, false );
				\curl_setopt($handles[$k], CURLOPT_USERAGENT, self::$user_agent);
				\curl_multi_add_handle( $mh, $handles[ $k ] );
			}

			// Execute the handles
			$active = null;
			do {
				$mrc = \curl_multi_exec( $mh, $active );
			} while ( $mrc == \CURLM_CALL_MULTI_PERFORM || $active );

			// Close and remove the handles
			foreach ( $urls as $k => $url ) {
				$curl_error = \curl_error($handles[$k]);

				\curl_multi_remove_handle( $mh, $handles[ $k ] );
				\curl_close( $handles[ $k ] );
				fclose($files[$k]);

				if ( file_exists( $image_filepaths[ $k ] ) && is_readable( $image_filepaths[ $k ] ) && filesize( $image_filepaths[ $k ] ) > 0 && ( preg_match( '%\W(svg)$%i', wp_all_import_basename( $image_filepaths[ $k ] ) ) || ( $image_info = apply_filters( 'pmxi_getimagesize', @getimagesize( $image_filepaths[ $k ] ), $image_filepaths[ $k ] ) ) && in_array( $image_info[2], wp_all_import_supported_image_types() ) ) ) {
					if ( preg_match( '%\W(svg)$%i', wp_all_import_basename( $image_filepaths[ $k ] ) ) ) {
						$image_info = true;
					}
					self::$logger and call_user_func( self::$logger, sprintf( __( '- Image `%s` has been successfully downloaded', 'wp_all_import_plugin' ), $url ) );
				} else {
					self::$logger and call_user_func( self::$logger, sprintf( __( "- <b>WARNING</b>: File %s is not a valid image or has failed to download. cURL Error: %s", 'wp_all_import_plugin' ), $url, $curl_error ) );
					self::$logger and ! self::$is_cron and \PMXI_Plugin::$session->warnings ++;
					file_exists( $image_filepaths[ $k ] ) && unlink( $image_filepaths[ $k ] ); // delete file since failed upload may result in empty file created

					// If the image isn't valid, don't try to process it again during this run.
					$urls_key = array_search($url, $original_urls);

					if($urls_key !== false){
						unset($original_urls[$urls_key]);
					}

					$image_info = false;
				}

				if ( ! empty( $image_info ) && is_array( $image_info ) ) {
					$file_mime_type = \image_type_to_mime_type( $image_info[2] );
				} else {
					continue;
				}
				$file_mime_type = apply_filters( 'wp_all_import_image_mime_type', $file_mime_type, $image_filepaths[ $k ] );

				$handle_image = apply_filters( 'wp_all_import_handle_upload', array(
					'file' => $image_filepaths[ $k ],
					'url'  => $image_target_urls[ $k ] . '/' . $image_names[ $k ],
					'type' => $file_mime_type
				) );

				$attid = $this->createAttachment( 0, $handle_image, $image_names[ $k ], $this->postAuthor, $this->postType, self::$is_cron );

				if ( $attid ) {
					$this->saveToImagesTable( $attid, $image_names[ $k ], $url );
				} else {
					file_exists( $image_filepaths[ $k ] ) && unlink( $image_filepaths[ $k ] );
				}
			}

			// Close the multi handle
			\curl_multi_close( $mh );
		}
	}

	public function downloadFile($url, $image_filepath, $is_cron, $type = 'images'){

		$downloaded = false;
		$file_info = false;
		$url = wp_all_import_sanitize_url($url);

		self::$logger and call_user_func(self::$logger, sprintf(__('- Downloading image from `%s`', 'wp_all_import_plugin'), $url));

		if ( ! preg_match('%^(http|https|ftp|ftps)%i', $url)){
			self::$logger and call_user_func(self::$logger, sprintf(__('- URL `%s` is not valid.', 'wp_all_import_plugin'), $url));
			return false;
		}

		$response = wp_remote_get($url, [
			'timeout' => apply_filters('pmxi_image_download_timeout', 5),
			'sslverify' => false,
			'headers' => [
				'User-Agent' => self::$user_agent,
			],
		]);

		if ( is_wp_error($response) ) {
			self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: File %s can not be downloaded, response %s', 'wp_all_import_plugin'), $url, maybe_serialize($response)));
			file_exists($image_filepath) && unlink($image_filepath); // delete file since failed upload may result in empty file created
		} else {

			file_put_contents($image_filepath, wp_remote_retrieve_body($response));

			if(!file_exists($image_filepath)){
				return false;
			}

			if ($type == 'images') {
				if ( preg_match('%\W(svg)$%i', wp_all_import_basename($image_filepath))
				     || ($file_info = apply_filters('pmxi_getimagesize', @getimagesize($image_filepath), $image_filepath))
				        && in_array($file_info[2], wp_all_import_supported_image_types())) {
					$downloaded = true;
					if (preg_match('%\W(svg)$%i', wp_all_import_basename($image_filepath))){
						$file_info = true;
					}
					self::$logger and call_user_func(self::$logger, sprintf(__('- Image `%s` has been successfully downloaded', 'wp_all_import_plugin'), $url));
				} else {
					self::$logger and call_user_func(self::$logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $url));
					self::$logger and !$is_cron and \PMXI_Plugin::$session->warnings++;
					@unlink($image_filepath); // delete file since failed upload may result in empty file created
				}
			} elseif($type == 'files') {
				if( $file_info = wp_check_filetype(wp_all_import_basename($image_filepath), null )) {
					$downloaded = true;
					self::$logger and call_user_func(self::$logger, sprintf(__('- File `%s` has been successfully downloaded', 'wp_all_import_plugin'), $url));
				}
			}
		}

		return $downloaded ? $file_info : false;
	}

	public function createAttachment($pid, $handle_image, $image_name, $post_author, $post_type, $is_cron, $type = 'images'){
		self::$logger and call_user_func(self::$logger, sprintf(__('- Creating an attachment for image `%s`', 'wp_all_import_plugin'), $handle_image['url']));

		$attachment_title = explode(".", $image_name);
		if (is_array($attachment_title) and count($attachment_title) > 1) array_pop($attachment_title);

		empty($handle_image['type']) && $handle_image['type'] = preg_match('%\W(svg)$%i', wp_all_import_basename($handle_image['file'])) ? 'image/svg+xml' : '';

		$handle_image = $this->add_extension_to_file_array($handle_image);

		$attachment = array(
			'post_mime_type' => $handle_image['type'],
			'guid' => $handle_image['url'],
			'post_title' => implode(".", $attachment_title),
			'post_content' => '',
			'post_author' => $post_author,
		);
		if ($type == 'images' and ($image_meta = wp_read_image_metadata($handle_image['file']))) {
			if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
				$attachment['post_title'] = $image_meta['title'];
			if (trim($image_meta['caption']))
				$attachment['post_content'] = $image_meta['caption'];
		}

		remove_all_actions('add_attachment');

		if ( in_array($post_type, array('taxonomies', 'comments', 'woo_reviews')) ){
			$attid = wp_insert_attachment($attachment, $handle_image['file'], 0);
		}
		else{
			$attid = wp_insert_attachment($attachment, $handle_image['file'], $pid);
		}

		if (is_wp_error($attid)) {
			self::$logger and call_user_func(self::$logger, __('- <b>WARNING</b>', 'wp_all_import_plugin') . ': ' . $attid->get_error_message());
			self::$logger and !$is_cron and \PMXI_Plugin::$session->warnings++;
		} else {
			/**
			 * Fires once an attachment has been added.
			 */
			do_action( 'wp_all_import_add_attachment', $attid );
			if(!self::allow_delay_image_resize()) {
				wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $handle_image['file']));
			}else {
				self::log_created_attachment( $attid );
			}
		}

		return ( $attid && ! is_wp_error($attid) ) ? $attid : false;
	}

	public static function composeAttachments($titles, $records){
		if ( ! ((self::$uploads = wp_upload_dir()) && false === self::$uploads['error'])) {
			self::$logger and call_user_func(self::$logger, __('<b>WARNING</b>', 'wp_all_import_plugin') . ': ' . self::$uploads['error']);
			self::$logger and call_user_func(self::$logger, __('<b>WARNING</b>: No attachments will be created', 'wp_all_import_plugin'));
			self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
		} else {
			self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing URLs for attachments files...', 'wp_all_import_plugin'));

			if (self::$importRecord->options['attachments'] && self::$importRecord->is_parsing_required('is_update_attachments') ) {
				// Detect if attachments is separated by comma
				$atchs = empty(self::$importRecord->options['atch_delim']) ? explode(',', self::$importRecord->options['attachments']) : explode(self::$importRecord->options['atch_delim'], self::$importRecord->options['attachments']);
				if (!empty($atchs)){
					$parse_multiple = true;
					foreach($atchs as $atch) if (!preg_match("/{.*}/", trim($atch))) $parse_multiple = false;
					if ($parse_multiple) {
						foreach($atchs as $atch) {
							$posts_attachments = \XmlImportParser::factory(self::$xml, self::$cxpath, trim($atch), $file)->parse($records); $tmp_files[] = $file;
							foreach($posts_attachments as $i => $val){
								if(!isset(self::$attachments[$i]) || !is_array(self::$attachments[$i])){
									self::$attachments[$i] = [];
								}
								self::$attachments[$i][] = $val;
							}
						}
					}
					else {
						self::$attachments = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options['attachments'], $file)->parse($records); $tmp_files[] = $file;
					}
				}
			} else {
				count($titles) and self::$attachments = array_fill(0, count($titles), '');
			}
		}
	}

	public static function composeImages($titles, $records){
		$image_sections = apply_filters('wp_all_import_image_sections', array(
			array(
				'slug'  => '',
				'title' => __('Images', 'wp_all_import_plugin'),
				'type'  => 'images'
			)
		));

		if ( ! ((self::$uploads = wp_upload_dir()) && false === self::$uploads['error'])) {
			self::$logger and call_user_func(self::$logger, __('<b>WARNING</b>', 'wp_all_import_plugin') . ': ' . self::$uploads['error']);
			self::$logger and call_user_func(self::$logger, __('<b>WARNING</b>: No featured images will be created. Uploads folder is not found.', 'wp_all_import_plugin'));
			self::$logger and !self::$is_cron and \PMXI_Plugin::$session->warnings++;
		} else {

			if ( self::$importRecord->is_parsing_required('is_update_images') ){
				foreach ($image_sections as $section) {
					self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing URLs for ' . strtolower($section['title']) . '...', 'wp_all_import_plugin'));
					$featured_images = array();
					if ( "no" == self::$importRecord->options[$section['slug'] . 'download_images']){
						if (self::$importRecord->options[$section['slug'] . 'featured_image']) {
							$featured_images = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'featured_image'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $featured_images = array_fill(0, count($titles), '');
						}
					}
					elseif ("gallery" == self::$importRecord->options[$section['slug'] . 'download_images']) {
						if (self::$importRecord->options[$section['slug'] . 'gallery_featured_image']) {
							$featured_images = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'gallery_featured_image'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $featured_images = array_fill(0, count($titles), '');
						}
					}
					else{
						if (self::$importRecord->options[$section['slug'] . 'download_featured_image']) {
							$featured_images = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'download_featured_image'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $featured_images = array_fill(0, count($titles), '');
						}
					}

					self::$images_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = array(
						'type'  => $section['type'],
						'files' => $featured_images
					);

					// Composing images meta titles
					if ( self::$importRecord->options[$section['slug'] . 'set_image_meta_title'] ){
						self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' meta data (titles)...', 'wp_all_import_plugin'));
						$image_meta_titles = array();
						if (self::$importRecord->options[$section['slug'] . 'image_meta_title']) {
							$image_meta_titles = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'image_meta_title'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $image_meta_titles = array_fill(0, count($titles), '');
						}
						self::$image_meta_titles_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $image_meta_titles;
					}

					// Composing images meta captions
					if ( self::$importRecord->options[$section['slug'] . 'set_image_meta_caption'] ){
						self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' meta data (captions)...', 'wp_all_import_plugin'));
						$image_meta_captions = array();
						if (self::$importRecord->options[$section['slug'] . 'image_meta_caption']) {
							$image_meta_captions = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'image_meta_caption'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $image_meta_captions = array_fill(0, count($titles), '');
						}
						self::$image_meta_captions_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $image_meta_captions;
					}

					// Composing images meta alt text
					if ( self::$importRecord->options[$section['slug'] . 'set_image_meta_alt'] ){
						self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' meta data (alt text)...', 'wp_all_import_plugin'));
						$image_meta_alts = array();
						if (self::$importRecord->options[$section['slug'] . 'image_meta_alt']) {
							$image_meta_alts = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'image_meta_alt'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $image_meta_alts = array_fill(0, count($titles), '');
						}
						self::$image_meta_alts_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $image_meta_alts;
					}

					// Composing images meta description
					if ( self::$importRecord->options[$section['slug'] . 'set_image_meta_description'] ){
						self::$chunk == 1 and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' meta data (description)...', 'wp_all_import_plugin'));
						$image_meta_descriptions = array();
						if (self::$importRecord->options[$section['slug'] . 'image_meta_description']) {
							$image_meta_descriptions = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'image_meta_description'], $file)->parse($records); $tmp_files[] = $file;
						} else {
							count($titles) and $image_meta_descriptions = array_fill(0, count($titles), '');
						}
						self::$image_meta_descriptions_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $image_meta_descriptions;
					}


					// Composing images suffix
					self::$chunk == 1 and self::$importRecord->options[$section['slug'] . 'auto_rename_images'] and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' suffix...', 'wp_all_import_plugin'));
					$auto_rename_images = array();
					if ( self::$importRecord->options[$section['slug'] . 'auto_rename_images'] and ! empty(self::$importRecord->options[$section['slug'] . 'auto_rename_images_suffix']) && self::$importRecord->options['download_images'] !== 'gallery') {
						$auto_rename_images = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'auto_rename_images_suffix'], $file)->parse($records); $tmp_files[] = $file;
					} else {
						count($titles) and $auto_rename_images = array_fill(0, count($titles), '');
					}
					self::$auto_rename_images_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $auto_rename_images;

					// Composing images extensions
					self::$chunk == 1 and self::$importRecord->options[$section['slug'] . 'auto_set_extension'] and self::$logger and call_user_func(self::$logger, __('Composing ' . strtolower($section['title']) . ' extensions...', 'wp_all_import_plugin'));
					$auto_extensions = array();
					if ( self::$importRecord->options[$section['slug'] . 'auto_set_extension'] and ! empty(self::$importRecord->options[$section['slug'] . 'new_extension']) && self::$importRecord->options['download_images'] !== 'gallery') {
						$auto_extensions = \XmlImportParser::factory(self::$xml, self::$cxpath, self::$importRecord->options[$section['slug'] . 'new_extension'], $file)->parse($records); $tmp_files[] = $file;
					} else {
						count($titles) and $auto_extensions = array_fill(0, count($titles), '');
					}
					self::$auto_extensions_bundle[ empty($section['slug']) ? 'pmxi_gallery_image' : $section['slug']] = $auto_extensions;

				}
			}
		}
	}

	function add_extension_to_file_array($file_array) {
		$file_path = $file_array['file'];
		$file_url = $file_array['url'];
		$mime_type = $file_array['type'];

		$extension = pathinfo($file_path, PATHINFO_EXTENSION);

		$mime_map = [
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp'
		];

		if (!$extension && !empty($mime_map[$mime_type])) {

			$new_extension = $mime_map[$mime_type];

			$new_file_path = $file_path . '.' . $new_extension;
			$new_file_url = $file_url . '.' . $new_extension;

			rename($file_path, $new_file_path);

			$file_array['file'] = $new_file_path;
			$file_array['url'] = $new_file_url;
		}
		
		return $file_array;
	}

	public function get_base64_image_type($base64string) {
		// Check if the string starts with the data URL scheme
		if (preg_match('/^data:image\/(\w+);base64,/', $base64string, $matches)) {
			// Extract the MIME type and the actual base64 string
			$mime_type = 'image/' . $matches[1];
			$base64string = substr($base64string, strpos($base64string, ',') + 1);
		} else {
			// If no data URL scheme, attempt to decode directly
			$mime_type = null;
		}

		// Decode the base64 string
		$decoded_data = base64_decode($base64string, true);

		if ($decoded_data === false) {
			return null; // Not a valid base64 string
		}

		// If MIME type was not extracted from the data URL scheme, determine it from the binary data
		if (!$mime_type) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_buffer($finfo, $decoded_data);
			finfo_close($finfo);
		}

		// Map MIME types to common image file extensions
		$mime_to_ext = [
			'image/gif' => 'gif',
			'image/jpeg' => 'jpeg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'image/bmp' => 'bmp',
			'image/x-icon' => 'ico',
			'image/svg+xml' => 'svg',
			'image/tiff' => 'tiff',
		];

		return $mime_to_ext[$mime_type] ?? null;
	}


}