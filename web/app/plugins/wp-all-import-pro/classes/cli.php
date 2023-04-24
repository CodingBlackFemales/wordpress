<?php

/**
 * WP All Import Cli Class.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMXI_Cli
 */
class PMXI_Cli {

    /**
     * ## OPTIONS
     *
     * <import_id>
     * : ID of the import.
     *
     * [--disable-log]
     * : Whether or not save import logs.
     *
     * [--force-run]
     * : Run import even it was already triggered.
     *
     * ## EXAMPLES
     *
     *     wp all-import run 1
     *     wp all-import run 1 --disable-log
     *
     * @when after_wp_load
     * @param $args
     * @param $assoc_args
     */
    function run( $args, $assoc_args ) {

        list( $import_ids ) = $args;

	    $import_ids = explode(',', $import_ids );

        foreach( $import_ids as $import_id) {
            try {
            	$logger = function($m) {
		            print("<p>[". date("H:i:s") ."] ".wp_all_import_filter_html_kses($m)."</p>\n");
	            };
                $logger = apply_filters('wp_all_import_logger', $logger);
                if (array_key_exists('disable-log', $assoc_args)) {
                    $logger = NULL;
                }
                $import = new PMXI_Import_Record();
                $import->getById($import_id);

                if ($import->isEmpty()) {
                    WP_CLI::error(__('Import not found.', PMXI_Plugin::LANGUAGE_DOMAIN));
                }
	            if (($import->triggered || $import->executing) && !array_key_exists('force-run', $assoc_args)) {
		            WP_CLI::error(__('Import already running.', PMXI_Plugin::LANGUAGE_DOMAIN));
	            }
                $import->set([
                    'triggered' => 1,
                    'imported' => 0,
                    'canceled' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'queue_chunk_number' => 0,
                    'processing' => 0
                ])->update();

                $history_log = new PMXI_History_Record();
                // Unlink previous logs.
                $log_storage = (int) PMXI_Plugin::getInstance()->getOption('log_storage');
                $by = [];
                $by[] = [['import_id' => $import->id], 'AND'];
                $historyLogs = new PMXI_History_List();
                $historyLogs->setColumns('id', 'import_id', 'type', 'date')->getBy($by, 'id ASC');
                if ($historyLogs->count() and $historyLogs->count() >= $log_storage ) {
                    $logsToRemove = $historyLogs->count() - $log_storage;
                    foreach ($historyLogs as $i => $file) {
                        $historyRecord = new PMXI_History_Record();
                        $historyRecord->getBy('id', $file['id']);
                        if ( ! $historyRecord->isEmpty()) $historyRecord->delete(); // unlink history file only
                        if ($i == $logsToRemove)
                            break;
                    }
                }
                $history_log->set([
                    'import_id' => $import->id,
                    'date' => date('Y-m-d H:i:s'),
                    'type' => 'cli'
                ])->save();

                $start = time();
                ob_start();
                $import->execute($logger);
                $log_data = ob_get_clean();
                $end = time();
                if ($log_storage && $logger) {
                    $wp_uploads = wp_upload_dir();
                    $log_file = wp_all_import_secure_file($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . \PMXI_Plugin::LOGS_DIRECTORY, $history_log->id) . DIRECTORY_SEPARATOR . $history_log->id . '.html';
                    if (@file_exists($log_file)) {
                        wp_all_import_remove_source($log_file, false);
                    }
                    @file_put_contents($log_file, $log_data);
                }
                $items = [
                    [
                        'Created' => $import->created,
                        'Updated' => $import->updated,
                        'Skipped' => $import->skipped,
                        'Deleted' => $import->deleted,
                        'Changed Missing' => $import->changed_missing,
                        'Count' => $import->count,
                    ]
                ];
                WP_CLI\Utils\format_items( 'table', $items, [ 'Created', 'Updated', 'Skipped', 'Deleted', 'Changed Missing', 'Count' ] );
                WP_CLI::success( sprintf(__('Import completed. [ time: %s ]', PMXI_Plugin::LANGUAGE_DOMAIN), human_time_diff($start, $end)));

                $custom_type = wp_all_import_custom_type_labels($import->options['custom_type'], $import->options['taxonomy_type']);
                $log_msg = sprintf(__("%d %s created %d updated %d skipped", "wp_all_import_plugin"), $import->created, ( ($import->created == 1) ? $custom_type->labels->singular_name : $custom_type->labels->name ), $import->updated, $import->skipped);
                if ($import->options['is_delete_missing']) {
                    if (empty($this->options['delete_missing_action']) || $this->options['delete_missing_action'] != 'remove') {
                        $log_msg = sprintf(__("%d %s created %d updated %d changed missing %d skipped", "wp_all_import_plugin"), $import->created, ( ($import->created == 1) ? $custom_type->labels->singular_name : $custom_type->labels->name ), $import->updated, $import->changed_missing, $import->skipped);
                    } else {
                        $log_msg = sprintf(__("%d %s created %d updated %d deleted %d skipped", "wp_all_import_plugin"), $import->created, ( ($import->created == 1) ? $custom_type->labels->singular_name : $custom_type->labels->name ), $import->updated, $import->deleted, $import->skipped);
                    }
                }
                $history_log->set([
                    'time_run' => $end - $start,
                    'summary' => $log_msg
                ])->save();
            } catch (Exception $e) {
                WP_CLI::error($e->getTraceAsString());
            }
        }
    }

    /**
     * ## EXAMPLES
     *
     *     wp all-import list
     *
     * @when after_wp_load
     *
     * @subcommand list
     * @param $args
     * @param $assoc_args
     */
    function _list( $args, $assoc_args ) {
        try {
            $items = [];
            $imports = new PMXI_Import_List();
            foreach ($imports->setColumns($imports->getTable() . '.*')->getBy(array('id !=' => ''))->convertRecords() as $import){
                $import->getById($import->id);
                if ( ! $import->isEmpty() ){
                    $items[] = [
                        'ID' => $import->id,
                        'Name' => empty($import->friendly_name) ? $import->name : $import->friendly_name,
                        'Created' => $import->created,
                        'Updated' => $import->updated,
                        'Skipped' => $import->skipped,
                        'Deleted' => $import->deleted,
                        'Changed Missing' => $import->changed_missing,
                        'Count' => $import->count,
                        'Last Activity' => $import->last_activity
                    ];
                }
            }

            WP_CLI\Utils\format_items( 'table', $items, array( 'ID', 'Name', 'Created', 'Updated', 'Skipped', 'Deleted', 'Changed Missing', 'Count', 'Last Activity' ) );

        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
}
