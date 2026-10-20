<?php
namespace PublishPress\Permissions;

class FileAccessHooks
{
    function __construct() {
        if (!defined('PPFF_FOLDER') && !defined('PRESSPERMIT_NO_LEGACY_API')) {
            require_once(PRESSPERMIT_FILEACCESS_ABSPATH . '/includes/api-legacy.php');
        }

        if (is_multisite()) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/Network.php');

            if (FileAccess\Network::msBlogsRewriting()) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/NetworkLegacy.php');
                new FileAccess\NetworkLegacy();
            }
        }

        if (is_admin() || ! defined('PP_NO_FRONTEND_ADMIN')) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/Triggers.php');
            new FileAccess\Triggers();
        }

        add_filter('posts_clauses_request', [$this, 'flt_clauses_trashed_post_image_support'], 50, 2);

        add_filter('presspermit_default_options', [$this, 'flt_default_options']);
        add_filter('presspermit_default_advanced_options', [$this, 'flt_default_advanced_options']);

        add_filter('presspermit_enabled_post_types', [$this, 'flt_enable_attachment_filtering']);
        add_filter('presspermit_locked_post_types', [$this, 'flt_enable_attachment_filtering']);

        // access filtering
        add_filter('map_meta_cap', [$this, 'fltMapMediaMetaCap'], 2, 4);
        add_action('template_redirect', [$this, 'act_attachment_access']);
        add_action('parse_query', [$this, 'act_parseQueryForDirectAccess']);

        add_filter('presspermit_unattached_visibility_clause', [$this, 'flt_unattached_visibility_clause'], 10, 3);
        add_filter('presspermit_attached_visibility_clause', [$this, 'flt_attached_visibility_clause'], 10, 3);

        /* REST filtering is not currently needed, but left as a pattern for possible third party integration issues
        //add_filter('rest_pre_dispatch', [$this, 'fltRestPreDispatch'], 10, 3);
        */
    }

    function flt_default_options($def)
    {
        $new = [
            'unattached_files_private' => 0,
            'attached_files_private' => 0,
            'file_access_apply_redirect' => 0,
        ];

        return array_merge($def, $new);
    }

    function flt_default_advanced_options($def)
    {
        $new = ['small_thumbnails_unfiltered' => 0];

        return array_merge($def, $new);
    }

    function flt_enable_attachment_filtering($enabled) {
        $enabled['attachment'] = 'attachment';
        return $enabled;
    }

    public function flt_clauses_trashed_post_image_support($clauses, $_wp_query = false, $args = []) {
        global $wpdb;

        if ($post_id = PWP::getPostID()) {
            $clauses['where'] = str_replace(
                "AND ($wpdb->posts.ID = '0') AND $wpdb->posts.post_type = 'attachment'", 
                "AND ($wpdb->posts.ID = '$post_id') AND $wpdb->posts.post_type = 'attachment'",
                $clauses['where']
            );
        }

        return $clauses;
    }

    // REST filtering is not currently needed, but left as a pattern for possible third party integration issues

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function fltRestPreDispatch($rest_response, $rest_server, $request)
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/REST.php');
        return FileAccess\REST::instance()->pre_dispatch($rest_response, $rest_server, $request);
    }
    */

    function flt_unattached_visibility_clause($unattached_vis_clause, $clauses, $args)
    {
        return (presspermit()->getOption('unattached_files_private')) 
        ? $this->file_visibility_clause($unattached_vis_clause, $clauses, $args) 
        : $unattached_vis_clause;
    }

    function flt_attached_visibility_clause($attached_vis_clause, $clauses, $args)
    {
        return (defined('PP_ATTACHED_FILE_AUTOPRIVACY') && presspermit()->getOption('attached_files_private')) 
        ? $this->file_visibility_clause($attached_vis_clause, $clauses, $args) 
        : $attached_vis_clause;
    }

    private function file_visibility_clause($vis_clause, $clauses, $args)
    {
        global $current_user;

        $type_obj = get_post_type_object('attachment');
        $read_files_cap = (!empty($type_obj->cap->read_private_posts) && (PRESSPERMIT_READ_PUBLIC_CAP != $type_obj->cap->read_private_posts)) 
        ? $type_obj->cap->read_private_posts 
        : 'read_private_files';

        if (empty($current_user->allcaps[$read_files_cap])) {
            $vis_clause .= " AND " . PWP::postAuthorClause($args);
        }

        return $vis_clause;
    }

    function fltMapMediaMetaCap($reqd_caps, $orig_cap, $user_id, $args)
    {
        if ('read_post' == $orig_cap) {
            if (!empty($args[0])) {
                $_post = (is_object($args[0])) ? $args[0] : get_post($args[0]);

                if ($_post && ('attachment' == $_post->post_type)) {
                    $reqd_caps = apply_filters('presspermit_map_attachment_read_caps', $reqd_caps, $_post, $user_id);
                }
            }
        }

        return $reqd_caps;
    }

    // Filter attacment page content prior to display by attachment template.
    // Note: teaser-subject direct file URL requests also land here
    function act_attachment_access()
    {
        if (PWP::isAttachment()) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/AttachmentTemplate.php');

            if (defined('PRESSPERMIT_TEASER_VERSION') && (!PWP::is_REQUEST('pp_rewrite') || PWP::empty_REQUEST('attachment'))) {
                if (FileAccess\AttachmentTemplate::teaserFilter()) {
                    return;
                }
            }

            FileAccess\AttachmentTemplate::regulateReadAccess();
        }
    }

    // handle access to uploaded file where request was a direct file URL, which was rewritten according to our .htaccess addition
    function act_parseQueryForDirectAccess(&$query)
    {
        if (!defined('PRESSPERMIT_VERSION'))
            return;

        if (empty($query->query_vars['attachment']) || !PWP::SERVER_url('QUERY_STRING') || (false === strpos(sanitize_text_field(PWP::SERVER_url('QUERY_STRING', false)), 'pp_rewrite'))) {
            return;
        }

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/FileFilters.php');
        FileAccess\FileFilters::parseQueryForDirectAccess($query);
    }

    function fltReturnFalse($a)
    {
        return false;
    }
}
