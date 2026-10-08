<?php
namespace PublishPress_Statuses;

// @ todo: merge this with PostsListing

// Implement Custom Statuses on the Posts screen and in Post Editor
class PostEdit
{
    function __construct() {
        if (!in_array(\PP_Statuses_Functions::findPostType(), ['forum', 'topic', 'reply'])) {
            // Gutenberg scripts are only loaded if Gutenberg-specific actions fire.
            add_action('enqueue_block_editor_assets', [$this, 'actLoadGutenbergScripts']);

            // Always load basic scripts for Classic Editor support unless explicitly disabled by plugin setting
            if ('gutenberg' !== \PublishPress_Statuses::instance()->options->force_editor_detection) {
                add_action('add_meta_boxes', [$this, 'act_replace_publish_metabox'], 10, 2);

                add_action('admin_print_scripts', [$this, 'act_classic_editor_failsafe'], 100);

                add_action('admin_enqueue_scripts', function() {
                    // Load full set of Classic Editor scripts if Gutenberg is not detected, or if Classic Editor explicitly specified by plugin setting
                    if (! \PP_Statuses_Functions::isBlockEditorActive(['force' => \PublishPress_Statuses::instance()->options->force_editor_detection])) {
                        require_once(__DIR__ . '/PostEditClassic.php');
                        $obj = new PostEditClassic();
                        $obj->post_admin_header();
                    }
                });
            }
        }

        add_action('admin_head', [$this, 'act_status_labels_structural_check_and_supplement'], 5);

        global $pagenow;

        $post_type = \PP_Statuses_Functions::findPostType();

        $options = \PublishPress_Statuses::instance()->options;
        $default_privacy = (is_object($options) && !empty($options->default_privacy) && !empty($options->default_privacy[$post_type])) ? $options->default_privacy[$post_type] : '';
    }

    public function actLoadGutenbergScripts() {
        require_once(__DIR__ . '/PostEditGutenberg.php');
        $obj = new \PublishPress_Statuses\PostEditGutenberg();
        $obj->actEnqueueBlockEditorAssets();
    }

    function act_status_labels_structural_check_and_supplement() {
        global $wp_post_statuses;

        foreach ($wp_post_statuses as $status_name => $post_status_obj) {
            // work around issues with visibility status storage / retrieval; precaution for other statuses
            if (isset($post_status_obj->labels) && is_array($post_status_obj->labels) && is_numeric(key($post_status_obj->labels))) {
                $post_status_obj->labels = reset($post_status_obj->labels);
                $wp_post_statuses[$status_name]->labels = $post_status_obj;
            }
            
            if (!empty($post_status_obj->labels) && is_serialized($post_status_obj->labels)) {
                $post_status_obj->labels = maybe_unserialize($post_status_obj->labels);     // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

                if (is_object($post_status_obj->labels)) {
                    foreach (get_object_vars($post_status_obj->labels) as $k => $val) {
                        $post_status_obj->labels->$k = sanitize_text_field(wp_unslash($val));
                    }
                }

                $wp_post_statuses[$status_name]->labels = $post_status_obj;
            }

            if (!empty($post_status_obj->private) && ('private' != $status_name)) {
                // visibility property may be used by Permissions Pro
                if (!empty($post_status_obj->labels) && is_object($post_status_obj->labels)) {
                    if (empty($wp_post_statuses[$status_name]->labels->visibility)) {
                        $wp_post_statuses[$status_name]->labels->visibility = $post_status_obj->label;
                    }
                }
            }
        }
    }

    public function post_submit_meta_box($post, $args = [])
    {
        require_once(__DIR__ . '/PostEditClassicSubmitMetabox.php');
        PostEditClassicSubmitMetabox::post_submit_meta_box($post, $args);
    }

    public function act_replace_publish_metabox($post_type, $post)
    {
        global $wp_meta_boxes;

        if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        if ('attachment' != $post_type) {
            if (!empty($wp_meta_boxes[$post_type]['side']['core']['submitdiv'])) {
                // Classic Editor: override WP submit metabox with a compatible equivalent (applying the same hooks as core post_submit_meta_box()

                if (!empty($post)) {
                    if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
                    || \PublishPress_Statuses::isPostBlacklisted($post->ID)
                    ) {
                        return;
                    }
                }

                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $wp_meta_boxes[$post_type]['side']['core']['submitdiv']['callback'] = [$this, 'post_submit_meta_box'];
            }
        }
    }

    function act_classic_editor_failsafe() {
        global $post;

        if (empty($post) || defined('PUBLISHPRESS_STATUSES_DISABLE_CLASSIC_FAILSAFE')) {
            return;
        }

        if (\PublishPress_Statuses::DisabledForPostType($post->post_type)) {
            return;
        }

        if (\PublishPress_Statuses::isUnknownStatus($post->post_status)
        || \PublishPress_Statuses::isPostBlacklisted($post->ID)
        ) {
            return;
        }

        $moderation_statuses = Admin::get_selectable_statuses($post, []);

        if (!$custom_statuses = array_diff_key($moderation_statuses, array_fill_keys(['draft', 'pending', 'future', 'auto-draft', 'publish', 'private'], true))) {
            return;
        }

        $current_status_obj = get_post_status_object($post->post_status); 
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
        var intStatusesFailsafe = setInterval(() => {
            if (!$('#poststuff').length) {
                return;
            }

            clearInterval(intStatusesFailsafe);

            if (!$('#misc-publishing-actions').length || !$('select#post_status').length || $('#pp_statuses_ui_rendered').length) {
                return;
            }

            <?php
            foreach ($custom_statuses as $status_name => $status_obj)
            :?> if (!$('#post-status-select option [value="<?php echo esc_attr($status_name);?>"]').length) {
                    $('select#post_status').append('<option value="<?php echo esc_attr($status_name);?>"><?php echo esc_html($status_obj->label);?></option>');
                }
            <?php endforeach;?>

            <?php if (isset($custom_statuses[$post->post_status]))
            :?> $('#post-status-select').val('<?php echo esc_attr($post->post_status);?>');
                $('#post-status-display').html('<?php echo esc_html($current_status_obj->label);?>');
            <?php endif;?>
        }, 100);
        });
        /* ]]> */
        </script>
        <?php
    }
}
