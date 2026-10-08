<?php
namespace PublishPress\Statuses\StatusControl\UI\Dashboard;

class BulkEdit
{
    public static function bulk_edit_posts($unused = null)
    {
        global $wpdb;

        if (!$post_id = \PP_Statuses_Functions::REQUEST_int('post')) {
            return;
        }

        $post_IDs = array_map('intval', (array) $post_id);

        $status = \PP_Statuses_Functions::REQUEST_key('_status_sub');

        if ('-1' === $status)
            return;

        require_once(PP_STATUS_CONTROL_CLASSPATH . '/ItemSave.php');

        $updated = $locked = $skipped = [];
        foreach ($post_IDs as $post_ID) {
            if (!current_user_can('edit_post', $post_ID)) {
                $skipped[] = $post_ID;
                continue;
            }
            
            if (wp_check_post_lock($post_ID)) {
                $locked[] = $post_ID;
                continue;
            }

            \PublishPress\Statuses\StatusControl\ItemSave::propagate_post_visibility($post_ID, $status);

            $updated[] = $post_ID;
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'locked' => $locked];
    }
}
