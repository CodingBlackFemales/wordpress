<?php
namespace PublishPress\Statuses\StatusControl\UI;

class PermissionsAjax
{
    public static function actExceptionsStatusUi($for_type, $args = [])
    {
        $defaults = ['via_source_name' => '', 'operation' => ''];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if ('term' == $via_source_name) {
            if ('forum' != $for_type) {  // @todo: API
                $organized_stati = [];
                $organized_stati['private'] = publishpress_status_control()->orderTypes(\PP_Statuses_Functions::getPostStatuses(['private' => true, '_builtin' => false, 'post_type' => $for_type], 'object'), ['order_property' => 'label']);

                if ('edit' == $operation) {
                    $organized_stati['moderation'] = publishpress_status_control()->orderTypes(\PP_Statuses_Functions::getPostStatuses(['moderation' => true, 'post_type' => $for_type], 'object'), ['order_property' => 'order']);
                    
                    if (function_exists('rvy_get_option') && rvy_get_option('permissions_compat_mode')) {
                        $organized_stati['revision'] = publishpress_status_control()->orderTypes(\PP_Statuses_Functions::getPostStatuses(['for_revision' => true, 'post_type' => $for_type], 'object'), ['order_property' => 'order']);
                    }
                }

                if ($organized_stati) {
                    $stati_captions = ['moderation' => esc_html__('Main Workflow: ', 'publishpress-statuses-pro'),
                        'private' => esc_html__('Visibility: ', 'presspermit')];

                    echo '<div id="pp_select_custom_attribs">';

                    foreach ($organized_stati as $status_class => $stati) {
                        echo '<div class="pp-attrib">';
                        $did_caption = false;
                        foreach ($stati as $status_name => $status_obj) {
                            if (!empty($status_obj->status_capability) && ($status_obj->status_capability != $status_name)) {
                                continue;
                            }

                            if (!$did_caption) {
                                echo esc_html($stati_captions[$status_class]) . '<br />';
                                $did_caption = true;
                            }

                            echo '<p class="pp-checkbox pp-attrib">'
                                . "<input type='checkbox' id='" . esc_attr("pp_select_x_cond_post_status_{$status_name}") . "' name='pp_select_x_cond[]' value='". esc_attr("post_status:{$status_name}") . "' /> "
                                . "<label id='" . esc_attr("lbl_pp_select_x_cond_post_status_{$status_name}") . "' for='" . esc_attr("pp_select_x_cond_post_status_{$status_name}") . "'>" . esc_html($status_obj->label) . '</label>'
                                . '</p>';
                        }
                    }

                    echo '</div>'; // pp_select_custom_attribs
                }
            }
        }
    }
}
