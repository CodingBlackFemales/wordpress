<?php
namespace PublishPress\Permissions\FileAccess;

class FileDenial 
{
    public static function response404($file, $matched_published_post, $uploads, $attachment_id)
    {
        // File access was not granted.  Since a 404 page will now be displayed, add filters which (for performance) were suppressed on the direct file access request
        global $wp_query;

        $pp = presspermit();
        $pp->clearDirectFileAccess();
        $pp->addMaintenanceTriggers();

        //Determine if teaser message should be triggered
        if (file_exists($uploads['basedir'] . "/$file")) {
            if ($matched_published_post && apply_filters('presspermit_teaser_enabled', false, 'post', 'attachment')) {
                foreach (array_keys($matched_published_post) as $object_type) {
                    if ($pp->getTypeOption('tease_post_types', $object_type)) {
                        if ($matched_published_post[$object_type]) {
                            if (!defined('PP_QUIET_FILE_404')) {
                                // note: subsequent act_attachment_access will call imposePostsTeaser()
                                $will_tease = true; // will_tease flag only used within this function
                                $wp_query->query_vars['attachment'] = $matched_published_post[$object_type];
                                break;
                            }
                        }
                    }
                }
            }

            if (defined('PPFF_STATUS_CODE') && is_numeric(constant('PPFF_STATUS_CODE'))) {
                $code = constant('PPFF_STATUS_CODE');
            } else {
                $code = 401;  // legacy
                $legacy = true;
            }

            status_header($code);

            if (empty($will_tease)) {
                // User is not qualified to access the requested attachment, and no teaser will apply

                // Normally, allow the function to return for WordPress 404 handling 
                // But end script execution here if requested attachment is a media type (or if definition set)
                // Linking pages won't want WP html returned in place of inaccessable image / video

                if (defined('PP_QUIET_FILE_404')) {
                    exit;
                }

                // TODO: why is this necessary with ppc 2.0? passthrough cause PHP warnings for $wp_query->post ?
                if (empty($wp_query->post))
                    $wp_query->post = (object)['ID' => 0, 'post_type' => '', 'post_status' => '', 'ping_status' => '', 'comment_status' => '', 'comment_count' => '', 'post_author' => 0, 'post_content' => '', 'post_date' => '', 'post_mime_type' => ''];

                // this may not be necessary
                if ((404 == $code) || $legacy)
                    $wp_query->is_404 = true;

                if (403 == $code)
                    $wp_query->is_403 = true;

                $wp_query->is_single = true;
                $wp_query->is_singular = true;
                $wp_query->query_vars['is_single'] = true;
            } else {
                // phpcs Note: remove a plugin argument during plugin's file filtering operation

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                unset($_REQUEST['pp_rewrite']);
            }
        }
    }
}
