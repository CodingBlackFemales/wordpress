<?php
namespace PublishPress\Permissions\Circles\DB;

class DatabaseSetup
{
    public function __construct($last_db_ver)
    {
        require_once(PRESSPERMIT_CIRCLES_ABSPATH . '/db-config.php');

        if (is_multisite()) {
            add_action('switch_blog', [__CLASS__, 'actMultisiteSupport']);
        }
        
        self::updateSchema($last_db_ver);
    }

    private static function updateSchema($last_db_ver)
    {
        global $wpdb;

        $charset_collate = '';

        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        // note: dbDelta requires two spaces after PRIMARY KEY, no spaces between KEY columns

        // Circles table def 
        $tabledefs = "CREATE TABLE $wpdb->pp_circles (
         ID bigint(20) NOT NULL auto_increment,
         group_type varchar(32) NOT NULL default '',
         group_id bigint(20) unsigned NOT NULL,
         circle_type varchar(32) NOT NULL default '',
         post_type varchar(32) NOT NULL default '',
            PRIMARY KEY  (ID),
            KEY group_circle (circle_type,group_type,group_id) )
            $charset_collate
        ;
        ";

        require_once(PRESSPERMIT_CLASSPATH . '/DB/DatabaseSetup.php');
                
        // apply all table definitions
        \PublishPress\Permissions\DB\DatabaseSetup::dbDelta($tabledefs);

    } //end updateSchema function

    public static function actMultisiteSupport()
    {
        require(PRESSPERMIT_CIRCLES_ABSPATH . '/db-config.php');
    }
}
