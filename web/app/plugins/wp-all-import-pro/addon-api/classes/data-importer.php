<?php

namespace Wpai\AddonAPI;

use PMXI_Model_Record;
use wpdb;

abstract class PMXI_Addon_Data_Importer {

    public PMXI_Model_Record $record;
    public bool $is_cron = false;
    public wpdb $wpdb;
    public $logger_callback;

    public static $default_model = PMXI_Addon_Post_Data_Importer::class;

    public function __construct( $record ) {
        $this->record = $record;
        $this->wpdb   = $GLOBALS['wpdb'];
    }

    public function __get( $name ) {
        if ( $name === 'options' ) {
            // check if defined

            if ( isset( $this->record->options ) ) {
                return $this->record->options;
            }

            return $this->record->options;
        }

        if ( $name === 'id' ) {
            return $this->record->id;
        }

        return null;
    }

    public function setCron( $is_cron ) {
        $this->is_cron = $is_cron;
    }

    public function setLogger( $logger ) {
        $this->logger_callback = $logger;
    }

    /**
     * Check if there is an addon that supports the specific post type
     *
     * @param $record
     *
     * @return PMXI_Addon_Data_Importer
     */
    public static function create( $record = null ) {

        // Default model
        $model = self::$default_model;

        // Check if the record has options set, if not, use the default model
        if ( ! isset( $record->options ) ) {
            return new $model( $record );
        }

        $found_addon = \Wpai\AddonAPI\PMXI_Addon_Manager::get_owner_addon_for_type(
            $record->options
        );

        if ( $found_addon ) {
            $model = $found_addon->getCustomImporter( $record->options ) ?? $model;
        }

        return new $model( $record );
    }

    /**
     * @param string $message
     */
    public function logger( $message ) {
        if ( $this->logger_callback ) {
            call_user_func( $this->logger_callback, $message );
        }else{
	        // Default logger.
	        if($this->is_cron) {
		        $logger = function($m) {
			        print("<p>[". date("H:i:s") ."] ".wp_all_import_filter_html_kses($m)."</p>\n");
		        };
	        }else{
		        $logger = function ( $m ) {
			        echo "<div class='progress-msg'>[" . date( "H:i:s" ) . "] " . wp_all_import_filter_html_kses( $m ) . "</div>\n";
			        flush();
		        };
	        }
	        $this->logger_callback = apply_filters('wp_all_import_logger', $logger);

	        call_user_func( $this->logger_callback, $message );
        }
    }

    abstract public function get_record( $post_id );

    abstract public function get_record_title( $articleData );

    abstract public function get_record_meta( $post_id );

    abstract public function upsert( $articleData, $custom_type_details );

    abstract public function update_content( $post_id, $content );

    abstract public function update_meta( $post_id, $meta_key, $meta_value );

    abstract public function combine_data( $data );

    abstract public function choose_data_to_update( $post_to_update, $post_to_update_id, $post_type, $taxonomies, &$articleData, $i );

    abstract public function change_post_status_to_draft( $missing_post_id );

    abstract public function delete_meta( $post_id, $meta_key );

    abstract public function delete_records( $ids );

    abstract public function delete_images( $post_id, $articleData, $images_bundle );

    abstract public function delete_image_custom_field( $post_id, $meta_key );

    abstract public function delete_record_not_present_in_file( $missing_post_id );

    abstract public function get_missing_records( $ids, $missing_status, $missing_cf );

    abstract public function move_missing_record_to_trash( $missing_post_id );
}