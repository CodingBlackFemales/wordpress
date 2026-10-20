<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (is_multisite()) {
    global $wpdb;

    $wpdb->pp_groups_netwide = $wpdb->base_prefix . 'pp_groups';
    $wpdb->pp_group_members_netwide = $wpdb->base_prefix . 'pp_group_members';
}
