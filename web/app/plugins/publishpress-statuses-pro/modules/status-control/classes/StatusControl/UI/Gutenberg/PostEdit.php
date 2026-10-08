<?php
namespace PublishPress\Statuses\StatusControl\UI\Gutenberg;

class PostEdit
{
    function __construct() 
    {
        if ($post_id = \PP_Statuses_Functions::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }
        
        add_action('rest_api_init', [$this, 'act_status_control_scripts']);
    }

    // If PressPermit permissions filtering is enabled for this post type, load additional js to support it
    public function act_status_control_scripts() {
        if (self::isPostTypeEnabled()) {
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Gutenberg/PostEditPrivacy.php');
            new PostEditPrivacy();

            if (is_post_type_hierarchical(\PP_Statuses_Functions::findPostType())) {
                require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Gutenberg/PostEditPrivacySub.php');
                new PostEditPrivacySub();
            }
        }
    }

	// If PressPermit permissions filtering is disabled for post type, don't load custom js for status dropdown and button labeling
    // Note, though, that 'pp_custom_status_list' filter still applies any per-type status availability set in Permissions > Post Statuses
    private static function isPostTypeEnabled($post_type = '') {
        global $post, $typenow;

        if (!empty($post)) {
            $post_type = $post->post_type;
        } else {
            if ($post_id = \PP_Statuses_Functions::getPostID()) {
                $post_type = get_post_field('post_type', $post_id);
            } else {
                $post_type = (!empty($typenow)) ? $typenow : '';
            }
        }

        $statuses_type_enabled = ! \PublishPress_Statuses::DisabledForPostType($post_type);

        return $statuses_type_enabled;
    }
}
