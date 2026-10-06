<?php
namespace PublishPress_Statuses;

// Implement Custom Statuses on the Posts screen and in Post Editor
class PostsListing
{
    var $post_ids = [];

    function __construct() {

        if (defined('PP_STATUSES_POSTS_STATUS_COLUMN')) {
            // Hook to add the status column to Manage Posts
            add_filter('manage_posts_columns', [$this, 'flt_manage_posts_cols']);
            add_filter('manage_pages_columns', [$this, 'flt_manage_posts_cols']);

            add_action('manage_posts_custom_column', [$this, 'flt_manage_posts_custom_column']);
            add_action('manage_pages_custom_column', [$this, 'flt_manage_posts_custom_column']);
        }

        add_action('admin_print_footer_scripts', [$this, 'act_modify_inline_edit_ui']);

        add_action('plugins_loaded', function() {
            add_filter('views_' . \PP_Statuses_Functions::findPostType(), [$this, 'flt_views_stati']);
        });

        add_action('the_post', [$this, 'act_log_displayed_posts']);
    }

    function act_log_displayed_posts($_post)
    {
        $this->post_ids[] = $_post->ID;
    }

    /**
     * Insert new column header for post status after the title column
     *
     * @param array $posts_columns Columns currently shown on the Edit Posts screen
     *
     * @return array Same array as the input array with a "status" column added after the "title" column
     */
    public function flt_manage_posts_cols($posts_columns)
    {
        // Return immediately if the supplied parameter isn't an array (which shouldn't happen in practice?)
        // http://wordpress.org/support/topic/plugin-publishpress-bug-shows-2-drafts-when-there-are-none-leads-to-error-messages
        if (! is_array($posts_columns)) {
            return $posts_columns;
        }

        // Only do it for the post types this module is activated for
        if (\PublishPress_Statuses::DisabledForPostType()) {
            return $posts_columns;
        }

        $result = [];
        foreach ($posts_columns as $key => $value) {
            if ($key == 'title') {
                $result[$key] = $value;
                $result['status'] = __('Status');
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    function flt_manage_posts_custom_column($column_name)
    {
        if ($column_name == 'status') {
            global $post;

            if ($status_obj = get_post_status_object($post->post_status)) {
                if (!empty($status_obj->label)) {
                    echo esc_html($status_obj->label);
                }
            }
        }
    }

    // @todo: move to .js
    // add "keep" checkboxes for custom private stati; set checked based on current or scheduled post status
    // add conditions UI to inline edit
    function act_modify_inline_edit_ui()
    {
        global $current_user;
        global $typenow;

        $screen = get_current_screen();
        
        $post_type_object = get_post_type_object($screen->post_type);
            
        if (empty($post_type_object) || \PublishPress_Statuses::DisabledForPostType($screen->post_type)) {
            return;
        }
        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                $('select[name="_status"] option[value="pending"]').remove();

                $('select[name="_status"] option[value="future"]').after('<option value="_" disabled>______________</option>');

                <?php
                $isContentAdministrator = current_user_can('administrator') 
                || current_user_can('pp_administer_content') 
                || (is_multisite() && is_super_admin());

                $type_obj = get_post_type_object($screen->post_type);

                $args = ['post_type' => $screen->post_type, 'post_status' => 'draft'];
                $moderation_statuses = Admin::get_selectable_statuses(false, $args);

                $can_publish = current_user_can($type_obj->cap->publish_posts);

                // @todo: indented statuses in quick edit dropdown
                ?>
                <?php
                if ($can_publish) {
                    $moderation_statuses = array_merge(
                        ['publish' => get_post_status_object('publish')], 
                        $moderation_statuses
                    );
                }

                $in_alternate_statuses = false;

                foreach ($moderation_statuses as $_status => $_status_obj) :
                    if (!empty($_status_obj->alternate) && !$in_alternate_statuses) :?>
                        $('select[name="_status"]').append('<option value="" disabled>______________</option>');
                    <?php 
                        $in_alternate_statuses = true;
                    endif;
                ?>
                    if (!$('select[name="_status"] option[value="<?php echo esc_attr($_status);?>"]').length) {
                        $('select[name="_status"]').append('<?php 
                            echo '<option value="' . esc_attr($_status) . '">';

                            $caption = (!empty($_status_obj->status_parent) && !empty($moderation_statuses[$_status_obj->status_parent])) 
                            ? '— ' . $_status_obj->labels->caption
                            : $_status_obj->labels->caption;
        
                            echo esc_html($caption) . '</option>';
                        ?>');
                    }
                <?php endforeach;?> 

                $('select[name="_status"]').on('click', function() {
                    if ('publish' != $('select[name="_status"]').val()) {
                        $('div.inline-edit-wrapper input[name="keep_private"]').prop('checked', false);
                    }
                });

                $('div.inline-edit-wrapper input[name="keep_private"]').on('click', function() {
                    if ($(this).prop('checked')) {
                        if ($(this).closest('div.inline-edit-wrapper').find('select[name="_status"] option[value="future"]').length) {
                            $('select[name="_status"]').val('future');
                        } else {
                            $('select[name="_status"]').val('publish');
                        }
                    }
                });
            });
            //]]>
        </script>
        <?php
    } // end function modify_inline_edit_ui

    function flt_views_stati($views)
    {
        $post_type = \PP_Statuses_Functions::findPostType();
        $type_stati = \PublishPress_Statuses::getPostStati(['show_in_admin_all_list' => true, 'post_type' => $post_type]);

        if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        $views = array_intersect_key($views, array_flip($type_stati));

        // also remove filtered stati from "All" count 
        $num_posts = array_intersect_key(wp_count_posts($post_type, 'readable'), $type_stati);

        $total_posts = array_sum((array)$num_posts);

        $class = !isset($views['mine']) && \PP_Statuses_Functions::empty_REQUEST('post_status', 'show_sticky') ? ' class="current"' : '';
        $allposts = (strpos($views['all'], 'all_posts=1')) ? $allposts = '&all_posts=1' : '';

        $views['all'] = "<a href='edit.php?post_type=$post_type{$allposts}'$class>" 
        . sprintf(
            _nx(
                'All <span class="count">(%s)</span>', 
                'All <span class="count">(%s)</span>', 
                (int) $total_posts, 
                'posts'
            ), 
            number_format_i18n($total_posts)
        ) 
        . '</a>';

        return $views;
    }
}
