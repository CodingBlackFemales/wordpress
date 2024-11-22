<?php

namespace Wpai\AddonAPI;

use PMXI_API;

class PMXI_Addon_Gallery_Field extends PMXI_Addon_Field {

    static $repeater_path = 'gallery';

    public static function isImage($url) {
        $ext = pmxi_getExtensionFromStr($url);
        $exts = ['jpeg', 'jpg', 'png', 'gif', 'svg', 'webp'];

        if (in_array($ext, $exts)) return true;

        // If file lacks an extension, try to get it from the remote file
        $ext = pmxi_get_remote_image_ext($url);
        return in_array($ext, $exts);
    }

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        $delim = $value['delim'] ?? ',';
        $gallery = $value['gallery'] ?? '';
        $search_in_files = empty($value['search_in_files']) ? 0 : 1;
        $search_logic = $value['search_logic'] ?? 'by_url';
        $search_in_gallery = empty($value['search_in_media']) ? 0 : 1;
        $only_append_new = $value['only_append_new'] ?? false;
        $post_type = $data['articleData']['post_type'] ?? false;

        $current_ids = currentValue($post_type, $postId, $this->key, []);

        // Replace all newline characters with the delimiter
        $urls = preg_replace("#\r\n|\r|\n#", $delim, trim($gallery));
        // Remove empty values
        $urls = array_values(array_filter(explode($delim, $urls)));

        $ids = $only_append_new ? $current_ids : [];

        foreach ($urls as $url) {

			// Trim urls to ensure matching of existing images by URL works.
	        $url = trim($url);

            if (!self::isImage($url)) {
                $attachment_id = self::import_file($url, $postId, $logger, $search_in_gallery, $search_in_files, $search_logic, $data);
            } else {
                $attachment_id = self::import_image($url, $postId, $logger, $search_in_gallery, $search_in_files, $search_logic, $data);
            }

            // Add the attachment ID to the list of IDs if it's not already there
            if ($attachment_id && !in_array($attachment_id, $ids)) {
                $ids[] = $attachment_id;
            }
        }

        return $ids;
    }

    /**
     * Extracted from wpai-acf-add-on/src/ACFService.php
     * @param $img_url
     * @param $pid
     * @param $logger
     * @param bool $search_in_gallery
     * @param bool $search_in_files
     *
     * @param array $importData
     * @return bool|int|\WP_Error
     */
    public static function import_image($img_url, $pid, $logger, $search_in_gallery = FALSE, $search_in_files = FALSE, $search_logic = 'by_filename', $importData = array()) {

        // Search image attachment by ID.
        if ($search_in_gallery and is_numeric($img_url)) {
            if (wp_get_attachment_url($img_url)) {
                return $img_url;
            }
        }

        $downloadFiles = "yes";
        $fileName = "";

        if ($search_in_gallery) {
            $attch_id = searchExistingImage($img_url, $pid, $search_logic, 'images', $importData, $logger);

            if ($attch_id) {
                return $attch_id;
            }
        }

        // Search for existing image in /files folder.
        if ($search_in_files) {
            $downloadFiles = "no";
            $fileName = wp_all_import_basename(parse_url(trim($img_url), PHP_URL_PATH));
        }

        return PMXI_API::upload_image($pid, $img_url, $downloadFiles, $logger, true, $fileName, 'images', false, $importData['articleData'], $importData);
    }

    /**
     * @param $atch_url
     * @param $pid
     * @param $logger
     * @param bool $fast
     * @param bool $search_in_gallery
     * @param bool $search_in_files
     *
     * @param array $importData
     * @return bool|int|\WP_Error
     */
    public static function import_file($atch_url, $pid, $logger, $search_in_gallery = FALSE, $search_in_files = FALSE, $search_logic = 'by_filename', $importData = array()) {
        // search file attachment by ID
        if ($search_in_gallery and is_numeric($atch_url)) {
            if (wp_get_attachment_url($atch_url)) {
                return $atch_url;
            }
        }

        $downloadFiles = "yes";
        $fileName = "";

        if ($search_in_gallery) {
            $attch_id = searchExistingImage($atch_url, $pid, $search_logic, 'files', $importData, $logger);

            if ($attch_id) {
                // Attach media to current post if it's currently unattached.
                $attch = get_post($attch_id);

                if (empty($attch->post_parent)) {
                    wp_update_post(
                        array(
                            'ID' => $attch_id,
                            'post_parent' => $pid
                        )
                    );
                }

                return $attch_id;
            }
        }

        // Search for existing image in /files folder.
        if ($search_in_files) {
            $downloadFiles = "no";
            $fileName = basename($atch_url);
        }

        return PMXI_API::upload_image($pid, $atch_url, $downloadFiles, $logger, true, $fileName, 'files', false, $importData['articleData'], $importData);
    }
}
