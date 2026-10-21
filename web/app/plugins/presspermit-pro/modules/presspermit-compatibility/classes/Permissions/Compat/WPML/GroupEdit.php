<?php
namespace PublishPress\Permissions\Compat\WPML;

class GroupEdit
{
    private $buffer_current_lang = '';

    function __construct()
    {
        add_filter('presspermit_permit_items_meta_box_object', [$this, 'fltPostsMetaboxQueryArgs']);

        // if mirroring term roles, force default language terms regardless of lang setting
        if (presspermit()->getOption('mirror_term_translation_exceptions')) {
            add_filter('terms_clauses', [$this, 'fltSetDefaultLang'], 9);
            add_filter('terms_clauses', [$this, 'fltRestoreCurrentLang'], 11);
        }
    }

    function fltSetDefaultLang($clauses)
    {
        global $sitepress;

        $current_lang = $sitepress->get_current_language();
        $default_lang = $sitepress->get_default_language();

        if ($current_lang != $default_lang) {
            $this->buffer_current_lang = $current_lang;
            $sitepress->switch_lang($default_lang, true);
        }

        return $clauses;
    }

    function fltRestoreCurrentLang($clauses)
    {
        if ($this->buffer_current_lang) {
            global $sitepress;
            $sitepress->switch_lang($this->buffer_current_lang);
        }

        return $clauses;
    }

    // if syncing roles to translations, support removal of translations from posts metabox on Edit Agent form
    function fltPostsMetaboxQueryArgs($type_obj)
    {
        if (isset($type_obj->taxonomies)) {
            global $wpdb;

            if (presspermit()->getOption('mirror_post_translation_exceptions')) {
                // phpcs Note: Direct query on WPML table to support permissions filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($omit_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND ( language_code != source_language_code ) AND source_language_code != ''"
                        . " AND NOT ISNULL(source_language_code)"
                    
                        , "post_{$type_obj->name}"
                    )
                )) {
                    $type_obj->_default_query['post__not_in'] = $omit_ids;
                    $type_obj->_default_query['suppress_filters'] = false;
                }
            } else {
                global $sitepress;
                $current_lang = $sitepress->get_current_language();

                if ('all' == $current_lang)
                    return $type_obj;

                // TODO: better perf through inner join?

                // phpcs Note: Direct query on WPML table to support permissions filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($include_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND language_code = %s", "post_{$type_obj->name}"
                        
                        , $current_lang
                    )
                )) {
                    
                    $type_obj->_default_query['post__in'] = $include_ids;
                    $type_obj->_default_query['suppress_filters'] = false;
                }
            }
        } // note: get_terms() filtering is handled by WPML; forced to default lang by fltSetDefaultLang() if mirroring enabled

        return $type_obj;
    }
}
