<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wpdb;

$wpdb->pp_conditions = $wpdb->prefix . 'pp_conditions';
