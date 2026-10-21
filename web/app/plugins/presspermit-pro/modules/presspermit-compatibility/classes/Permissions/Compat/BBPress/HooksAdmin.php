<?php
namespace PublishPress\Permissions\Compat\BBPress;

class HooksAdmin
{
    function __construct() 
    {
        add_filter('presspermit_hidden_post_types', [$this, 'hidden_types'], 20);
        add_filter('presspermit_hidden_taxonomies', [$this, 'hidden_taxonomies']);
        add_filter('presspermit_item_edit_exception_ops', [$this, 'flt_item_edit_exception_ops'], 10, 3);

        add_filter('presspermit_operation_object', [$this, 'operation_object'], 10, 3);
        add_filter('presspermit_exception_operations', [$this, 'flt_add_forum_operations'], 2, 4);
        add_filter('presspermit_available_mirror_ops', [$this, 'flt_available_mirror_operations'], 10, 3);

        add_filter('presspermit_can_set_exceptions', [$this, 'presspermit_can_set_exceptions'], 10, 4);

        add_action('presspermit_post_edit_ui', [$this, 'act_post_edit_ui']);
    }

    function flt_available_mirror_operations($mirror_ops, $op, $post_type) {
        if ('forum' == $post_type) {
            $mirror_ops = array_merge($mirror_ops, ['read', 'publish_topics', 'publish_replies', 'edit', 'associate']);
        }

        return $mirror_ops;
    }

    function presspermit_can_set_exceptions($can, $operation, $for_item_type, $args = [])
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/AdminRoles.php');
        return AdminRoles::fltCanSetExceptions($can, $operation, $for_item_type, $args);
    }

    function operation_object($op_obj, $operation, $post_type)
    {
        if ('publish_topics' == $operation) {
            $op_obj = (object)[
                'label' => esc_html__('Create Topics in', 'presspermit-pro'), 
                'noun_label' => esc_html__('Topic Creation', 'presspermit-pro'), 
                'abbrev' => esc_html__('Create Topic', 'presspermit-pro')
            ];

        } elseif ('publish_replies' == $operation) {
            $op_obj = (object)[
                'label' => esc_html__('Submit Replies in', 'presspermit-pro'), 
                'noun_label' => esc_html__('Reply Submission', 'presspermit-pro'), 
                'abbrev' => esc_html__('Submit Reply', 'presspermit-pro')
            ];

        } elseif (('edit' == $operation) && $op_obj && in_array($post_type, ['forum', 'topic', 'reply'])) {
            $op_obj->label = 'Moderate / Edit';
            $op_obj->abbrev = 'Moderate';
        }

        return $op_obj;
    }

    function flt_add_forum_operations($ops, $for_item_source, $for_type, $args = [])
    {
        if ('forum' == $for_type) {
            $pp_admin = presspermit()->admin();
            $topics_op = $pp_admin->getOperationObject('publish_topics');
            $replies_op = $pp_admin->getOperationObject('publish_replies');

            $ops = array_unique(
                array_merge(
                    $ops, 
                    ['publish_topics' => $topics_op->label, 'publish_replies' => $replies_op->label],
                    $ops
                )
            );
        }

        return $ops;
    }

    function hidden_types($types)
    {
        return array_merge($types, array_fill_keys(['topic', 'reply'], 1));
    }

    function hidden_taxonomies($taxonomies)
    {
        return array_merge($taxonomies, array_fill_keys(['topic-tag'], 0));
    }

    function flt_item_edit_exception_ops($ops, $for_item_source, $for_item_type)
    {
        if ('forum' == $for_item_type) {
            $pp_admin = presspermit()->admin();

            $ops = array_intersect_key($ops, ['read' => true]);

            unset($ops['associate']);

            foreach (['publish_topics', 'publish_replies'] as $op) {
                if ($op_obj = $pp_admin->getOperationObject($op, $for_item_type))
                    $ops[$op] = $op_obj->label;
            }
        }

        return $ops;
    }

    function act_post_edit_ui()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/UI/Dashboard/PostEditUI.php');
        new UI\Dashboard\PostEditUI();
    }
}
