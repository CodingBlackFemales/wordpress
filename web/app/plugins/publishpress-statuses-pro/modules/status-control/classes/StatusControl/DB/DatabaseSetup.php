<?php
namespace PublishPress\Statuses\StatusControl\DB;

require_once(PUBLISHPRESS_STATUS_CONTROL_ABSPATH . '/db-config.php');

class DatabaseSetup
{
    public function __construct($last_db_ver)
    {
        if (MULTISITE) {
            add_action('switch_blog', [__CLASS__, 'multisite_db_support']);
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

        // Conditions table def 
        //
        // This table is used to store "child post visibility" per-post. It does not pertain to post status registration. 
        // Some of the schema is carryover from PP < 2.0, where it had a broader purpose.

        // phpcs Note: Direct query to create plugin table

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $tabledefs = "CREATE TABLE $wpdb->pp_conditions (
         assignment_id bigint(20) NOT NULL auto_increment,
         attribute varchar(32) NOT NULL default '',
         condition_name varchar(32) NOT NULL default '',
         scope enum('site', 'term', 'object') NOT NULL,
         item_source varchar(32) NOT NULL,
         item_id bigint(20) NOT NULL,
         assign_for enum('item', 'children') NOT NULL default 'item',
         mode enum('set', 'force', 'default') NOT NULL default 'set',
         inherited_from bigint(20) NOT NULL default '0',
            PRIMARY KEY  (assignment_id),
            KEY pp_item_condition (scope,assign_for,mode,attribute,condition_name,item_source,item_id),
            KEY pp_item_cond_assign (scope,assign_for,mode,attribute,condition_name,item_source,item_id,inherited_from,assignment_id) )
            $charset_collate
        ;
        ";

        require_once(PP_STATUS_CONTROL_CLASSPATH . '/DB/DatabaseSetup.php');
                
        // apply all table definitions
        \PublishPress\Statuses\StatusControl\DB\DatabaseSetup::dbDelta($tabledefs);
    }

    public static function multisite_db_support()
    {
        require_once(PUBLISHPRESS_STATUS_CONTROL_ABSPATH . '/db-config.php');
    }

    public static function dbDelta($queries, $execute = true)
    {  // lifted from WP because forced inclusion of schema.php interferes with site creation
        global $wpdb;

        // Separate individual queries into an array
        if (!is_array($queries)) {
            $queries = explode(';', $queries);
            if ('' == $queries[count($queries) - 1]) array_pop($queries);
        }

        $cqueries = []; // Creation Queries
        $iqueries = []; // Insertion Queries
        $for_update = [];

        // Create a tablename index for an array ($cqueries) of queries
        foreach ($queries as $qry) {
            if (preg_match("|CREATE TABLE (?:IF NOT EXISTS )?([^ ]*)|", $qry, $matches)) {
                $cqueries[trim($matches[1], '`')] = $qry;
                $for_update[$matches[1]] = 'Created table ' . $matches[1];
            } else if (preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches)) {
                array_unshift($cqueries, $qry);
            } else if (preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches)) {
                $iqueries[] = $qry;
            } else if (preg_match("|UPDATE ([^ ]*)|", $qry, $matches)) {
                $iqueries[] = $qry;
            } else {
                // Unrecognized query type
            }
        }

        // Check to see which tables and fields exist
        if ($tables = $wpdb->get_col('SHOW TABLES;')) {                         // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            // For every table in the database
            foreach ($tables as $table) {
                // If a table query exists for the database table...
                if (array_key_exists($table, $cqueries)) {
                    // Clear the field and index arrays
                    $cfields = [];
                    $indices = [];

                    // Get all of the field names in the query from between the parens
                    preg_match("|\((.*)\)|ms", $cqueries[$table], $match2);
                    $qryline = trim($match2[1]);

                    // Separate field lines into an array
                    $flds = explode("\n", $qryline);

                    // For every field line specified in the query
                    foreach ($flds as $fld) {
                        // Extract the field name
                        preg_match("|^([^ ]*)|", trim($fld), $fvals);
                        $fieldname = trim($fvals[1], '`');

                        // Verify the found field name
                        $validfield = true;
                        switch (strtolower($fieldname)) {
                            case '':
                            case 'primary':
                            case 'index':
                            case 'fulltext':
                            case 'unique':
                            case 'key':
                                $validfield = false;
                                $indices[] = trim(trim($fld), ", \n");
                                break;
                        }
                        $fld = trim($fld);

                        // If it's a valid field, add it to the field array
                        if ($validfield) {
                            $cfields[strtolower($fieldname)] = trim($fld, ", \n");
                        }
                    }

                    $table = sanitize_title($table);

                    // Fetch the table column structure from the database (NOTE: table variable is not to be quoted, so sanitized above)
                    $tablefields = $wpdb->get_results("DESCRIBE {$table};");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                    // For every field in the table
                    foreach ($tablefields as $tablefield) {
                        // If the table field exists in the field array...
                        if (array_key_exists(strtolower($tablefield->Field), $cfields)) {
                            // Get the field type from the query
                            preg_match("|" . $tablefield->Field . " ([^ ]*( unsigned)?)|i", $cfields[strtolower($tablefield->Field)], $matches);
                            $fieldtype = $matches[1];

                            // Is actual field type different from the field type in query?
                            if ($tablefield->Type != $fieldtype) {
                                // Add a query to change the column type
                                $cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN {$tablefield->Field} " . $cfields[strtolower($tablefield->Field)];
                                $for_update[$table . '.' . $tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
                            }

                            // Get the default value from the array
                            if (preg_match("| DEFAULT '(.*)'|i", $cfields[strtolower($tablefield->Field)], $matches)) {
                                $default_value = $matches[1];
                                if ($tablefield->Default != $default_value) {
                                    // Add a query to change the column's default value
                                    $cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} SET DEFAULT '{$default_value}'";
                                    $for_update[$table . '.' . $tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
                                }
                            }

                            // Remove the field from the array (so it's not added)
                            unset($cfields[strtolower($tablefield->Field)]);
                        } else {
                            // This field exists in the table, but not in the creation queries?
                        }
                    }

                    // For every remaining field specified for the table
                    foreach ($cfields as $fieldname => $fielddef) {
                        // Push a query line into $cqueries that adds the field to that table
                        $cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
                        $for_update[$table . '.' . $fieldname] = 'Added column ' . $table . '.' . $fieldname;
                    }

                    $table = sanitize_title($table);

                    // Index stuff goes here
                    // Fetch the table index structure from the database  (NOTE: table variable is not to be quoted, so sanitized above)
                    $tableindices = $wpdb->get_results("SHOW INDEX FROM {$table};");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

                    if ($tableindices) {
                        // Clear the index array
                        $index_ary = [];

                        // For every index in the table
                        foreach ($tableindices as $tableindex) {
                            // Add the index to the index data array
                            $keyname = $tableindex->Key_name;
                            $index_ary[$keyname]['columns'][] = ['fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part];
                            $index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0) ? true : false;
                        }

                        // For each actual index in the index array
                        foreach ($index_ary as $index_name => $index_data) {
                            // Build a create string to compare to the query
                            $index_string = '';
                            if ($index_name == 'PRIMARY') {
                                $index_string .= 'PRIMARY ';
                            } else if ($index_data['unique']) {
                                $index_string .= 'UNIQUE ';
                            }
                            $index_string .= 'KEY ';
                            if ($index_name != 'PRIMARY') {
                                $index_string .= $index_name;
                            }
                            $index_columns = '';
                            // For each column in the index
                            foreach ($index_data['columns'] as $column_data) {
                                if ($index_columns != '') $index_columns .= ',';
                                // Add the field to the column list string
                                $index_columns .= $column_data['fieldname'];
                                if ($column_data['subpart'] != '') {
                                    $index_columns .= '(' . $column_data['subpart'] . ')';
                                }
                            }
                            // Add the column list to the index create string
                            $index_string .= ' (' . $index_columns . ')';
                            if (!(($aindex = array_search($index_string, $indices)) === false)) {
                                unset($indices[$aindex]);
                            }
                        }
                    }

                    // For every remaining index specified for the table
                    foreach ((array) $indices as $index) {
                        // Check if the index already exists
                        $index_name = preg_match('/KEY\s+([^\s]+)/', $index, $matches) ? $matches[1] : '';
                        if ($index_name && !isset($index_ary[$index_name])) {
                            // Push a query line into $cqueries that adds the index to that table
                            $cqueries[] = "ALTER TABLE {$table} ADD $index";
                            $for_update[$table . '.' . $index_name] = 'Added index ' . $table . ' ' . $index;
                        }
                    }

                    // Remove the original table creation query from processing
                    unset($cqueries[$table]);
                    unset($for_update[$table]);
                } else {
                    // This table exists in the database, but not in the creation queries?
                }
            }
        }

        $allqueries = array_merge($cqueries, $iqueries);
        if ($execute) {
            foreach ($allqueries as $query) {
                // Execute the queries that were built and sanitized above. Longstanding code from WP core dbDelta().
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query($query);                                  
            }
        }

        return $for_update;
    }
}
