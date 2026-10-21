<?php
namespace PublishPress\Permissions\Membership\DB;

class GroupUpdate
{
    public static function updateGroup($agent_type, $group_id, $selected)
    {   
        $pp_groups = presspermit()->groups();
        $current = $pp_groups->getGroupMembers($group_id, $agent_type, 'all', ['status' => 'any']);

        $selected_ids = [];
        foreach ($selected as $elem) {
            $arr_elem = explode('|', $elem);

            $user_id = $arr_elem[0];
            $selected_ids[] = $user_id;

            $status = 'active';
            $start_date_gmt = constant('PRESSPERMIT_MIN_DATE_STRING');
            $end_date_gmt = constant('PRESSPERMIT_MAX_DATE_STRING');
            $date_limited = false;
            $start_stamp = 0;
            $end_stamp = 0;

            // phpcs Note: time zone is accounted for

            // phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date

            $local_offset = date('Z');  // convert to 0:00 local time

            if (count($arr_elem) > 1) {
                if (is_numeric($arr_elem[1])) {
                    $start_stamp = time() + ($arr_elem[1] * 86400);
                    $start_date_gmt = date('Y-m-d', $start_stamp - $local_offset);
                } else {
                    if ($start_stamp = strtotime($arr_elem[1])) {
                        $start_date_gmt = date('Y-m-d', $start_stamp - $local_offset);
                    }
                }
            }

            if (count($arr_elem) > 2) {
                $start_time = (in_array($start_date_gmt, [constant('PRESSPERMIT_MIN_DATE_STRING'), '0000-00-00 00:00:00'])) ? strtotime(gmdate('M d Y H:i:s')) : strtotime($start_date_gmt);

                if (is_numeric($arr_elem[2])) {
                    $end_stamp = $start_time + ($arr_elem[2] * 86400);
                    $end_date_gmt = date('Y-m-d', $end_stamp - $local_offset);
                } else {
                    if ($end_stamp = strtotime($arr_elem[2])) {
                        $end_date_gmt = date('Y-m-d', $end_stamp - $local_offset);
                    }
                }
            }

            if (!empty($start_stamp) || !empty($end_stamp)) {
                $date_limited = true;

                if ($start_stamp && $end_stamp && ($end_stamp < $start_stamp)) {
                    $buffer_end_date_gmt = $end_date_gmt;
                    $end_date_gmt = $start_date_gmt;
                    $start_date_gmt = $buffer_end_date_gmt;
                }

                if ($start_stamp > time())
                    $status = 'scheduled';
                elseif ($end_stamp && ($end_stamp < time()))
                    $status = 'expired';
            }

            $args = compact('agent_type', 'status', 'date_limited', 'start_date_gmt', 'end_date_gmt');

            if (isset($current[$user_id])) {
                $pp_groups->updateGroupUser($group_id, $user_id, $args);
            } else {
                $pp_groups->addGroupUser($group_id, (int)$user_id, $args);
            }
        }

        if ($remove_users = array_diff(array_keys($current), $selected_ids)) {
            $pp_groups->removeGroupUser($group_id, $remove_users, compact('agent_type'));
        }
    } 
}
