<?php
namespace PublishPress\Permissions\Circles\UI\Dashboard;

use \PublishPress\Permissions\Circles as Circles;

class Profile
{
    public static function displayUserCirclesUI($user)
    {
        $circles = [];

        foreach (Circles::getCircleTypes() as $circle_type) {
            $circles[$circle_type] = Circles::getCircleMembers($circle_type, $user);
        }

        if (empty($circles['read']) && empty($circles['edit']))
            return;

        echo '<div class="pp-group-box pp-group_post-types"><h3>' . esc_html__('Circle Membership', 'presspermit-pro') . '</h3>';

        $circle_labels = ['read' => __('Viewing', 'presspermit-pro'), 'edit' => __('Editing', 'presspermit-pro')];

        foreach (array_keys($circles) as $circle_type) {
            if (!empty($circles[$circle_type])) {
                echo '<div>' 
                . sprintf(
                    esc_html__('%s is limited to circle authors only, for these post types:', 'presspermit-pro'), 
                    esc_html($circle_labels[$circle_type])
                )
                . '</div>';
                
                $labels = [];
                foreach (array_keys($circles[$circle_type]) as $post_type) {
                    if ( $type_obj = get_post_type_object($post_type) ) {
                        $labels[$post_type] = $type_obj->label;
                    }
                }
                echo '<div style="margin-left:10px;margin-bottom:10px">' . esc_html(implode(", ", $labels)) . '</div>';
            }
        }

        echo '</div>';
    }
}
