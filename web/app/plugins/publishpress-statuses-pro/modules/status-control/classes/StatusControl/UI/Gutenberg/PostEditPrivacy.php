<?php

namespace PublishPress\Statuses\StatusControl\UI\Gutenberg;

// This class is only used with PublishPress inactive. It supplies the Post Status dropdown (for custom types).
class PostEditPrivacy
{
    private $custom_privacy_statuses;
    private $post_id;
    private $force_visibility;

    function __construct() 
    {
        add_action('enqueue_block_editor_assets', [$this, 'actLogPrivacyStatuses'], 9);
        add_filter('presspermit_block_editor_privacy_statuses', [$this, 'fltBlockEditorPrivacyStatuses']);

        add_action('admin_enqueue_scripts', [$this, 'actEnqueueScripts'], 11);
        add_action('enqueue_block_editor_assets', [$this, 'actEnqueueBlockEditorAssets'], 11);
        add_action('admin_print_scripts', [$this, 'actPrintScripts']);
    }

    public function actLogPrivacyStatuses()
    {
        $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
    }

    public function fltBlockEditorPrivacyStatuses($statuses)
    {
        $status_names = [];

        foreach($this->custom_privacy_statuses as $status_term) {
            $status_names []= $status_term->slug;
        }

        return $status_names;
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */
    public function actEnqueueScripts()
    {
        if (!isset($this->custom_privacy_statuses)) {
            $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
        }

        if (!$this->custom_privacy_statuses) {
            $post_type = \PP_Statuses_Functions::findPostType();

            if (empty(\PublishPress_Statuses::instance()->options->default_privacy[$post_type])) {
                return;
            }
        }

        global $wp_version;

        $filename = ((version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) || (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>=')))
        ? 'custom-privacy-block-editor' : 'custom-privacy-block-editor-legacy';

        wp_enqueue_style(
            'publishpress-custom_privacy-block',
            PRESSPERMIT_STATUSES_URLPATH . "/common/lib/{$filename}.css", 
            false,
            PRESSPERMIT_STATUSES_VERSION, 
            'all'
        );
    }

    /**
     * Enqueue Gutenberg assets.
     */
    public function actEnqueueBlockEditorAssets()
    {
        global $current_user, $post;

        if (!empty($post)) {
            // Don't load our scripts if the post has one of these meta values
            $post_meta_blacklist = (array) apply_filters('publishpress_statuses_postmeta_blacklist', ['_dp_is_rewrite_republish_copy']);

            foreach ($post_meta_blacklist as $post_meta_key) {
                if (get_post_meta($post->ID, $post_meta_key, true)) {
                    return;
                }
            }
        }

        if (!isset($this->custom_privacy_statuses)) {
            $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
        }

        if (!$this->custom_privacy_statuses) {
            $post_type = \PP_Statuses_Functions::findPostType();

            if (empty(\PublishPress_Statuses::instance()->options->default_privacy[$post_type])) {
                return;
            }
        }

        $statuses = $this->mergeCoreStatuses($this->custom_privacy_statuses);

        $filename = 'custom-privacy-block';

        wp_enqueue_script(
            'presspermit-custom-privacy-block',
            PRESSPERMIT_STATUSES_URLPATH . "/common/lib/{$filename}.min.js",
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
            PRESSPERMIT_STATUSES_VERSION,
            true
        );

        $force = '';

        $post_type = \PP_Statuses_Functions::findPostType();

        $default_privacy = (isset(\PublishPress_Statuses::instance()->options->default_privacy[$post_type]))
        ? \PublishPress_Statuses::instance()->options->default_privacy[$post_type]
        : '';

        if ($default_privacy) {
            if (!empty(\PublishPress_Statuses::instance()->options->force_default_privacy[$post_type])) {
            	$force = $default_privacy;
            }

            if (!$force) {
                if ($status_obj = get_post_status_object($post->post_status)) {
                    if (!empty($status_obj->public) || !empty($status_obj->private)) {
                        $default_privacy = '';
                    }
                }
            }

            if ($default_privacy) {
                // editor selections can change this transient
                set_transient("_pp_selected_privacy_{$current_user->ID}_{$post->ID}", $default_privacy, 43200);
            }
        }

        wp_localize_script(
            'presspermit-custom-privacy-block',
            'PPCustomPrivacy',
            ['statuses' => $statuses, 'ajaxurl' => wp_nonce_url(admin_url(''), 'pp-ajax'), 'defaultPrivacy' => $default_privacy, 'forceVisibility' => $force, 'customPrivacyEnabled' => !PPS::privacyStatusesDisabled()]
        );

        delete_transient("_pp_selected_privacy_{$current_user->ID}_{$post->ID}");
        delete_transient("_pp_selected_privacy_{$post->ID}");
    }

    public function actPrintScripts() {
        global $wp_version;

        if ((version_compare($wp_version, '6.6', '>=') && !defined('GUTENBERG_VERSION')) || (defined('GUTENBERG_VERSION') && version_compare(GUTENBERG_VERSION, '18.5', '>='))) :?>
            <style type="text/css">
            .publishpress-extended-post-privacy div.components-base-control {
                padding-left: 6px;
            }
            </style>
        <?php endif;
    }

    private function mergeCoreStatuses($statuses) 
    {
        return array_merge(
            [(object)['name' => 'draft', 'label' => esc_html__('Draft')]],
            [(object)['name' => 'publish', 'label' => esc_html__('Public')]],
            [(object)['name' => 'private', 'label' => esc_html__('Private')]],
            $statuses
        );
    }

    /**
     * Get all post statuses as an ordered array
     *
     * @param array|string $statuses
     * @param array        $args
     * @param bool         $only_basic_info
     *
     * @return array $statuses All of the statuses
     */
    private function getCustomPrivacyStatuses($args = [], $only_basic_info = false)
    {
        global $post;
        $post_type = \PP_Statuses_Functions::findPostType();
        if ( ! $type_obj = get_post_type_object($post_type) ) {
            return [];
        }

        $attributes = PPS::attributes();
        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

        $statuses = \PP_Statuses_Functions::getPostStatuses(['private' => true, '_builtin' => false, 'post_type' => $post_type], 'object');

        // Allow post to be edited with current custom visibility status even if that visibiity is disabled for assignment
        if (!empty($post) && !in_array($post->post_status, ['draft', 'pending', 'private', 'publish']) && !isset($statuses[$post->post_status])) {
            if ($status_obj = get_post_status_object($post->post_status)) {
                if (!empty($status_obj->private)) {
                    $statuses[$post->post_status] = $status_obj;
                }
            }
        }

        foreach($statuses as $_status => $status_obj) {
            if (!$is_administrator && ($_status != $post->post_status)) {
                if (empty($type_obj->cap->set_posts_status)) {
                    $set_status_cap = $type_obj->cap->publish_posts;
                } else {
                    $_caps = $attributes->getConditionCaps(
                        $type_obj->cap->set_posts_status, 
                        $post->post_type, 
                        'post_status', 
                        $_status
                    );

                    if (!$set_status_cap = reset($_caps)) {
                        $set_status_cap = $type_obj->cap->set_posts_status;
                    }
                }

                if (!current_user_can($set_status_cap)) {
                    unset($statuses[$_status]);
                }
            }
        }

        // compat with js usage of term properties
        foreach($statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $statuses[$key]->slug = $status_obj->name;     // phpcs Note: Prior usage of name property left for reference
                //$statuses[$key]->name = $status_obj->label;  // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                $statuses[$key]->description = '-';
                $statuses[$key]->color = '';
                $statuses[$key]->icon = '';
            }
        }

        $statuses = apply_filters('pp_custom_privacy_list', array_values($statuses), $post);

        if (!$statuses) {
            return [];
        }

        // compat with js usage of term properties
        foreach($statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $statuses[$key]->slug = $status_obj->name;     // phpcs Note: Prior usage of name property left for reference
                //$statuses[$key]->name = $status_obj->label;  // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                $statuses[$key]->description = '-';
                $statuses[$key]->color = '';
                $statuses[$key]->icon = '';
            }
        }

        return $statuses;
    }
}
