<?php

namespace Wpai\AddonAPI;

use PMXI_API;

class PMXI_Addon_Media_Field extends PMXI_Addon_Field {

	static $repeater_path = 'url';

	public static function isImage($url) {
		$ext = pmxi_getExtensionFromStr($url);
		$exts = ['jpeg', 'jpg', 'png', 'gif', 'svg', 'webp'];

		if (in_array($ext, $exts)) return true;

		// If file lacks an extension, try to get it from the remote file
		$ext = pmxi_get_remote_image_ext($url);
		return in_array($ext, $exts);
	}

	public function beforeImport($postId, $value, $data, $logger, $rawData) {
		$img_url = $value['url'] ?? '';
		$search_in_files = empty($value['search_in_files']) ? 0 : 1;
		$search_logic = $value['search_logic'] ?? 'by_url';
		$search_in_gallery = empty($value['search_in_media']) ? 0 : 1;

		if (!$img_url) {
			return '';
		}

		// Trim urls to ensure matching of existing images by URL works.
		$img_url = trim($img_url);

		// Search image attachment by ID.
		if ($search_in_gallery and is_numeric($img_url)) {
			if (wp_get_attachment_url($img_url)) {
				return $img_url;
			}
		}

		$fileName = "";
		$downloadFiles = "yes";

		if ($search_in_gallery) {
			$attch_id = searchExistingImage($img_url, $postId, $search_logic, 'images', $data, $logger);

			if ($attch_id) {
				// Attach media to current post if it's currently unattached.
				$attch = get_post($attch_id);

				if (empty($attch->post_parent)) {
					wp_update_post(
						array(
							'ID' => $attch_id,
							'post_parent' => $postId
						)
					);
				}

				return $attch_id;
			}
		}

		// Search for existing image in /files folder.
		if ($search_in_files) {
			$downloadFiles = "no";
			$fileName = wp_all_import_basename(parse_url(trim($img_url), PHP_URL_PATH));
		}

		if (!self::isImage($img_url)) {
			$attach_id = PMXI_API::upload_image($postId, $img_url, $downloadFiles, $logger, true, $fileName, 'files', false, $data['articleData'], $data);
		} else {
			$attach_id = PMXI_API::upload_image($postId, $img_url, $downloadFiles, $logger, true, $fileName, 'images', false, $data['articleData'], $data);
		}

		return $attach_id;
	}
}
