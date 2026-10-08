<?php

namespace PressShack;

class Database
{
    // deprecated
    public static function dbDelta($queries, $execute = true)
    { 
        if (defined('PP_STATUS_CONTROL_CLASSPATH')) {
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/DatabaseSetup.php');
            return \PublishPress\Statuses\StatusControl\DB\DatabaseSetup::dbDelta($tabledefs, $execute);
                        } else {
            return [];
        }
    }
}
