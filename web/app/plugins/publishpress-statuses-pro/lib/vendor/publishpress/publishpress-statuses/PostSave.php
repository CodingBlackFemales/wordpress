<?php
namespace PublishPress\Statuses;

class PostSave
{
    public static function fltPostStatus($post_status, $args = [])
    {
        global $current_user, $pagenow;

        $defaults = ['filter_draft_status' => false];
        $args = array_merge($defaults, $args);
        foreach(array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if ((defined('DOING_AJAX') && DOING_AJAX) 
        || (!empty($_SERVER['SCRIPT_NAME']) && (false !== strpos(wp_unslash($_SERVER['SCRIPT_NAME']), 'edit.php')))  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
        ) {
            return $post_status;
        }

        $post_id = \PP_Statuses_Functions::getPostID();

        if ($_post = get_post($post_id)) {
            $type_obj = get_post_type_object($_post->post_type);
        }

        $status_obj = get_post_status_object($post_status);

        if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && !empty($status_obj->public) || !empty($status_obj->private)) {
            if (!$selected_privacy = get_transient("_pp_selected_privacy_{$current_user->ID}_{$post_id}")) {
                $selected_privacy = get_transient("_pp_selected_privacy_{$post_id}");
            }

            if (!empty($selected_privacy) && !in_array($selected_privacy, ['publish', 'private'])) {
                if ($status_obj = get_post_status_object($selected_privacy)) {
                    $post_status = $selected_privacy;
                }

                // Note: To deal with any subsequent filter calls, transients are not deleted until next editor load
            }
        }

        if (defined('PUBLISHPRESS_REVISIONS_VERSION') && in_array($post_status, rvy_revision_base_statuses())) {
            if ($mime_type = get_post_field('post_mime_type', $post_id)) {
                if (in_array($mime_type, rvy_revision_statuses())) {
                    return $post_status;
                }
            }
        } elseif (defined('REVISIONARY_VERSION') && (
            in_array($post_status,['pending-revision', 'future-revision']))
            || (!\PP_Statuses_Functions::empty_POST() && \PP_Statuses_Functions::is_REQUEST('page', 'rvy-revisions')
        )) {
            return $post_status;
        }

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

        $post_type = (!empty($_post)) ? $_post->post_type : \PP_Statuses_Functions::findPostType();

        if ($stored_status = get_post_field('post_status', $post_id)) {
            $stored_status_obj = get_post_status_object($stored_status);
        }

        if (\PP_Statuses_Functions::isBlockEditorActive($post_type)) {
            $_post_status = \PP_Statuses_Functions::POST_key('post_status');
            $selected_status = ($_post_status && ('publish' != $_post_status)) ? $_post_status : $post_status;
        } else {
            $selected_status = $post_status;
        }

        $filtered_statuses = ($filter_draft_status) ? ['publish', 'draft'] : ['publish'];

        if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && (!\PP_Statuses_Functions::isBlockEditorActive($post_type) || in_array($selected_status, $filtered_statuses)) && !\PP_Statuses_Functions::empty_POST('visibility')) {
            $vis_status = \PP_Statuses_Functions::POST_key('visibility');

            if (!in_array($vis_status, ['publish', 'public', 'draft'])) {
                if (!PPS::haveStatusPermission('set_status', $post_type, $vis_status, ['internal_call' => true])) {
                    exit;
                    return $post_status;
                }

                $selected_status = $vis_status;
            }
        }

        if ('public' == $selected_status) {
            $selected_status = 'publish';
        }

        // inline edit: apply keep_status checkbox selection
        if ($_post && \PP_Statuses_Functions::is_POST('action', 'inline-save')) {
            foreach (\PP_Statuses_Functions::getPostStatuses(['private' => true, 'post_type' => $post_type]) as $_status) {
                if (!\PP_Statuses_Functions::empty_POST("keep_{$_status}")) {
                    $selected_status = $_status;
                    break;
                }
            }
        }

        if (!$post_status_obj = get_post_status_object($selected_status)) {
            return $post_status;
        }

        // Important: if other plugin code inserts additional posts in response, don't filter those
        static $done;
        if (!empty($done) && empty($done[$post_id])) return $post_status;  
        $done = [$post_id => true];

        $post_status = $selected_status;

        $_post = get_post($post_id);

        // Scheduled Post handling (Classic Editor)  @todo: Gutenberg
        if (!defined('REST_REQUEST') && in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])) {
            if (isset($_REQUEST["pp_ajax_set_privacy"])) {
                check_ajax_referer('pp-ajax');

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            } elseif ((!isset($_REQUEST['_wpnonce']) || 
                (
                ($post_id && !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "update-post_{$post_id}"))  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                && ($post_type && !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), "add-{$post_type}"))   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                ))  
                && (!isset($_REQUEST['_inline_edit']) ||
                (!wp_verify_nonce(wp_unslash($_REQUEST['_inline_edit']), 'inlineeditnonce'))                 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                )
            ) {
                return $post_status;
            }

            if (!\PP_Statuses_Functions::isBlockEditorActive($post_type)) {
                if (!empty($post_status_obj->private)) {
                    $_POST['post_password'] = '';

                    if (\PP_Statuses_Functions::is_POST('sticky')) {
                        // phpcs Note: Execution is triggered by a WP filter; need to handle regardless of save initialization method

                        unset($_POST['sticky']);                                                                            
                    }
                }

                if ($post_status_obj->public || $post_status_obj->private) {
                    if (!empty($_POST['post_date_gmt'])) {
                        // local variable is only used for comparison to current time
                        $post_date_gmt = \PP_Statuses_Functions::sanitizeEntry(sanitize_text_field(wp_unslash($_POST['post_date_gmt'])));

                    } elseif (!empty($_POST['aa'])) {
                        foreach (['aa' => 'Y', 'mm' => 'n', 'jj' => 'j', 'hh' => '', 'mn' => '', 'ss' => ''] as $var => $format) {
                            $$var = (!$format || (!empty($_POST[$var]) && $_POST[$var] > 0))
                            ? \PP_Statuses_Functions::sanitizeEntry(sanitize_text_field(wp_unslash($_POST[$var])))
                            : date($format);  // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        }
                        $post_date = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, min($jj, 31), min($hh, 23), min($mn, 59), 0);
                        $post_date_gmt = get_gmt_from_date($post_date);
                    }

                    // set status to future if a future date was selected with a private status
                    $now = gmdate('Y-m-d H:i:59');
                    if (!empty($post_date_gmt) && mysql2date('U', $post_date_gmt, false) > mysql2date('U', $now, false)) {
                        update_post_meta($post_id, '_scheduled_status', $post_status);
                        $post_status = 'future';
                    } else {
                        // if a post is being transitioned from scheduled to published/private, apply scheduled status
                        if ($_post) {
                            if ('future' == $_post->post_status) {  // stored status is future
                                if ($_status = get_post_meta($post_id, '_scheduled_status', true)) {
                                    $post_status = $_status;
                                    $post_status_obj = get_post_status_object($post_status);
                                }
        
                                delete_post_meta($post_id, '_scheduled_status');
                            }
                        }
                    }
                }
            }
        }

        if (empty($_post)) {
            return $post_status;
        }

        if (in_array($post_status, ['publish', 'private'])) {
            $default_privacy = (isset(\PublishPress_Statuses::instance()->options->default_privacy[$_post->post_type])) 
            ? \PublishPress_Statuses::instance()->options->default_privacy[$_post->post_type]
            : '';

            if ($default_privacy) {
                if (get_post_status_object($default_privacy)) {                    
                    if ( $stored_status = get_post_meta($post_id, '_pp_original_status') ) {
                        $stored_status_obj = get_post_status_object($stored_status);
                    }

                    if (empty($stored_status_obj) || (empty($stored_status_obj->public) && empty($stored_status_obj->private))) {
                        $post_status = $default_privacy;

                        delete_post_meta($post_id, '_pp_original_status');
                    }
                }
            }
        }

        return $post_status;
    }

    // If a public or private status is selected, change it to the specified force_visibility status
    public static function flt_force_visibility($status)
    {
        if (!$status_obj = get_post_status_object($status)) {
            return $status;
        }

        /*
        * On frontend requests, PP_Statuses_Functions::getPostID() can fall back
        * to the globally queried post. During wp_insert_post() sanitization this
        * may be unrelated to the post currently being inserted. In that case,
        * applying forced/default visibility can overwrite third-party internal
        * post statuses, such as Tutor LMS enrollment status "completed".
        */
        if (!is_admin()
        && (!defined('REST_REQUEST') || !REST_REQUEST)
        && (!defined('DOING_AJAX') || !DOING_AJAX)
        && empty($_REQUEST['post']) && empty($_REQUEST['post_ID']) && empty($_REQUEST['post_id'])   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            return $status;
        }

        static $done;
        if (!empty($done)) return $status;  // Important: if other plugin code inserts additional posts in response, don't filter those
        $done = true;

        if ($status_obj->public || $status_obj->private) {
            if (!$post_id = \PP_Statuses_Functions::getPostID()) {
                return $status;
            }
            
            $_post = get_post($post_id);

            if (\PP_Statuses_Functions::empty_POST() || empty($_post) || !is_object($_post)) {
                return $status;
            }

            if (!\PP_Statuses_Functions::empty_POST()) {
                if (!\PP_Statuses_Functions::empty_POST('post_password')) {
                    return $status;
                }
            } elseif ($_post && $_post->post_password) {
                return $status;
            }

            $options = \PublishPress_Statuses::instance()->options;

            if (!empty($options) && !empty($options->force_default_privacy[$_post->post_type])) {
                return (!empty($options->default_privacy[$_post->post_type])) ? $options->default_privacy[$_post->post_type] : 'publish';
            }

            if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                if ($is_hierarchical = is_post_type_hierarchical($_post->post_type)) {
                    // Since force_visibility is always a propagating condition and the parent setting may be in flux too, 
                    // check setting for parent instead of post
                    if (!\PP_Statuses_Functions::empty_POST() && \PP_Statuses_Functions::is_POST('parent_id')) {
                        $parent_id = apply_filters('pre_post_parent', \PP_Statuses_Functions::POST_int('parent_id'));
                    } elseif ($_post) {
                        $parent_id = $_post->post_parent;
                    }
                }

                if (!$is_hierarchical || !empty($parent_id)) {
                    // also poll force_visibility for non-hierarchical types to support PPCE forcing default visibility
                    $attributes = PPS::attributes();

                    $_args = ($is_hierarchical) 
                    ? ['id' => $parent_id, 'assign_for' => 'children'] 
                    : ['default_only' => true, 'post_type' => $_post->post_type];
                    
                    if ($force_status = $attributes->getItemCondition('post', 'force_visibility', $_args)) {
                        $status = $force_status;
                    }
                }
            }
        }

        return $status;
    }
}
