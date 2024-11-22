<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Post_Field extends PMXI_Addon_Field {

    static $repeater_path = 'value';

    public function parseDelimitedValues($value) {
        $delimiter = $value['delim'] ?? ',';
        $values = explode($delimiter, $value['value'] ?? '');
        $values = array_filter($values);
        $values = array_map('trim', $values);
        return $values;
    }

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        global $wpdb;

        $post_ids = [];
        $values = $this->parseDelimitedValues($value);
        $post_types = $this->args['search_post_type'] ?? [$data['articleData']['post_type']];

        if (empty($values)) {
            return $post_ids;
        }

        foreach ($values as $ev) {
            $relation = false;

            if (ctype_digit($ev)) {
                if (empty($post_types)) {
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", $ev));
                } else {
                    $relation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_type IN ('" . implode("','", $post_types) . "')", $ev));
                }
            }

            if (empty($relation)) {
                if (empty($post_types)) {
                    $sql = "SELECT * FROM {$wpdb->posts} WHERE post_type != %s AND ( post_title = %s OR post_name = %s )";
                    $relation = $wpdb->get_row($wpdb->prepare($sql, 'revision', $ev, sanitize_title_for_query($ev)));
                } else {
                    $sql = "SELECT * FROM {$wpdb->posts} WHERE post_type IN ('" . implode("','", $post_types) . "') AND ( post_title = %s OR post_name = %s )";
                    $relation = $wpdb->get_row($wpdb->prepare($sql, $ev, sanitize_title_for_query($ev)));
                }
            }

            if ($relation) {
                $post_ids[] = (string) $relation->ID;
            }
        }

        if (empty($this->multiple)) {
            return array_shift($post_ids);
        }

        return $post_ids;
    }
}
