<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename(esc_url_raw(wp_unslash($_SERVER['SCRIPT_FILENAME']))) )
	die( 'This page cannot be called directly.' );

/**
 * @package     PublishPress\Revisions\RevisionaryWooCommerce
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryWooCommerce
{
    function __construct() {
        add_action('revisionary_new_revision', [$this, 'actNewRevisionCopyVariations'], 10, 2);

        add_action('revision_applied', [$this, 'actApplyRevisionCopyVariations'], 10, 2);

        add_action('delete_post', [$this, 'actDeletePost']);

        add_filter('revisionary_diff_ui', [$this, 'flt_revision_diff_ui'], 20, 4);
    }

    // WooCommerce Product variations issue:

    /*
    I did find the issue with this and it was not another plugin but the handling of nulls in the revisions plugin when the database SQL mode is set to strict. 
    The way it copies properties from the sub posts for the revision checks if the autosave is empty but does not check for null on either the autosave or source property. 
    The below seems to have fixed the issue.

    wp-content/plugins/revisionary-pro/lib/vendor/publishpress/publishpress-revisions/revision-creation_rvy.php
    line: 87-89

    foreach($set_post_properties as $prop) { 
        if (!empty($use_autosave) && !empty($autosave_post->$prop)) { 
            $data[$prop] = ($autosave_post->$prop === null) ? "" : $autosave_post->$prop; 
        
        } else{ 
            $data[$prop] = ($source_post->$prop === null) ? "" : $source_post->$prop; 
        } 
        
        //$data[$prop] = (!empty($use_autosave) && !empty($autosave_post->$prop)) ? $autosave_post->$prop : ;
    }

    After further testing, I believe I left the default as Null in the database from testing. So the issue was still the same but I do not think my code had any effect. 
    I doubt there is anything that can be done code-wise after more thought. Just ensuring that, if the database is in strict mode, the post columns have an empty string set as the default. 
    Mine for some reason did not.

    What worked:
    ALTER TABLE `wp_posts` CHANGE `post_content_filtered` `post_content_filtered` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '';
    */

    function actNewRevisionCopyVariations($revision_id, $revision_status) {
        global $wpdb;

        $revision = get_post($revision_id);

        if (!$revision || ('product' != $revision->post_type)) {
            return;
        }

        if (!$published_post = get_post(rvy_post_id($revision_id))) {
            return;
        }

        RevisionaryPro::instance()->copySubposts($published_post->ID, $revision_id, 'product_variation');
    }

    function actApplyRevisionCopyVariations($published_id, $revision) {
        if (!$revision || ('product' != $revision->post_type)) {
            return;
        }

        $copied_ids = RevisionaryPro::instance()->copySubposts($revision->ID, $published_id, 'product_variation');

        RevisionaryPro::instance()->deleteSubposts($published_id, 'product_variation', ['keep_ids' => $copied_ids]);

        delete_transient('wc_product_children_' . $published_id);
    }

    function actDeletePost($post_id) {
        if (rvy_in_revision_workflow($post_id)) {
            RevisionaryPro::instance()->deleteSubposts($post_id, 'product_variation');
        }
    }

    function flt_revision_diff_ui($return, $compare_from, $compare_to, $args) {
        global $revisionary_compat;

        if (!is_array($args) || empty($revisionary_compat)) {
            return $return;
        }

        if (!$subposts = RevisionaryPro::instance()->getSubposts($compare_to->ID, 'product_variation')) {
            return $return;
        }

        $from_subpost_ids = [];

        foreach ($subposts as $to_subpost) {
            if ($orig_source_id = get_post_meta($to_subpost->ID, '_rvy_subpost_original_source_id', true)) {
                $source_subpost_ids []= $orig_source_id;
                $from_subpost = get_post($orig_source_id);
            } else {
                $from_subpost = false;
            }

            if (!empty($from_subpost)) {
                $args['from_meta'] = apply_filters('revisionary_compare_meta_from', get_post_meta($orig_source_id), $orig_source_id);
                $args['title'] = $from_subpost->post_title;
                unset($args['suppress_from']);
            } else {
                $args['from_meta'] = [];
                $args['title'] = $to_subpost->post_title;
                $args['suppress_from'] = true;
            }

            $args['to_meta'] = apply_filters('revisionary_compare_meta_from', get_post_meta($to_subpost->ID), $to_subpost->ID);
            $args['id_prefix'] = $to_subpost->ID . ':';

            // Force unified sorting by meta key name, regardless of leading underscore
            foreach ($args['to_meta'] as $k => $val) {
                if (0 !== strpos($k, '_')) {
                    $j = '_' . $k;
                    unset($args['to_meta'][$k]);
                    $args['to_meta'][$j] = $val;

                    if (isset($args['from_meta'][$k])) {
                        $args['from_meta'][$j] = $args['from_meta'][$k];
                        unset($args['from_meta'][$k]);
                    }
                }
            }

            $args['extra_fields'] = $args['to_meta'];

            if ($new = $revisionary_compat->flt_revision_diff_ui([], $from_subpost, $to_subpost, $args)) {
                $return = array_merge($return, $new);
            }
        }

        $orig_subposts = RevisionaryPro::instance()->getSubposts($compare_from->ID, 'product_variation');

        // Also show deletions
        foreach ($orig_subposts as $from_subpost) {
            if (in_array($from_subpost->ID, $source_subpost_ids)) {
                continue;
            }

            $args['title'] = $from_subpost->post_title;
            $args['from_meta'] = apply_filters('revisionary_compare_meta_from', get_post_meta($from_subpost->ID), $from_subpost->ID);
            
            // Force unified sorting by meta key name, regardless of leading underscore
            foreach ($args['from_meta'] as $k => $val) {
                if (0 !== strpos($k, '_')) {
                    $j = '_' . $k;
                    unset($args['from_meta'][$k]);
                    $args['from_meta'][$j] = $val;
                }
            }

            $args['extra_fields'] = $args['from_meta'];

            $args['to_meta'] = [];
            $args['id_prefix'] = $from_subpost->ID . ':';
            $args['suppress_to'] = true;
            unset($args['suppress_from']);

            if ($new = $revisionary_compat->flt_revision_diff_ui([], $from_subpost, false, $args)) {
                $return = array_merge($return, $new);
            }
        }

        return $return;
    }
}
