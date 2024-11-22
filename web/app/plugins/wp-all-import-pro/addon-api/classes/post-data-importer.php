<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Post_Data_Importer extends PMXI_Addon_Data_Importer {

    public function get_record( $post_id ) {
        return get_post( $post_id );
    }

    public function get_record_title( $articleData ) {
        return $articleData['post_title'];
    }

    public function get_record_meta( $post_id ) {
        return get_post_meta( $post_id );
    }

	public function upsert( $articleData, $custom_type_details ) {
		$articleTitle = $this->get_record_title( $articleData );
		$labelName = $custom_type_details->labels->singular_name ?? $custom_type_details->labels->name ?? '';

		if ( empty( $articleData['ID'] ) ) {
			$this->logger( sprintf( __( '<b>CREATING</b> `%s` `%s`', 'wp_all_import_plugin' ), $articleTitle, $labelName ) );
		} else {
			$this->logger( sprintf( __( '<b>UPDATING</b> `%s` `%s`', 'wp_all_import_plugin' ), $articleTitle, $labelName ) );
		}

		return ( empty( $articleData['ID'] ) ) ? wp_insert_post( $articleData, true ) : wp_update_post( $articleData, true );
	}

    public function update_content( $post_id, $content ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $content
        ] );
    }

    public function update_meta( $post_id, $meta_key, $meta_value ) {
        update_post_meta( $post_id, $meta_key, $meta_value );

        $this->logger( sprintf( __( 'Instead of deletion post with ID `%s`, set Custom Field `%s` to value `%s`', 'wp-all-import-pro' ), $post_id, $meta_key, $meta_value ) );
    }

    public function move_missing_record_to_trash( $missing_post_id ) {
        if ( $final_post_type = get_post_type( $missing_post_id ) and 'trash' != get_post_status( $missing_post_id ) ) {
            wp_trash_post( $missing_post_id );
            $this->record->recount_terms( $missing_post_id, $final_post_type );
            $this->logger( sprintf( __( 'Instead of deletion, change post with ID `%s` status to trash', 'wp-all-import-pro' ), $missing_post_id ) );
        }
    }

    public function change_post_status_to_draft( $missing_post_id ) {
        if ( $final_post_type = get_post_type( $missing_post_id ) and $this->options['status_of_removed'] != get_post_status( $missing_post_id ) ) {
            $this->wpdb->update( $this->wpdb->posts, array( 'post_status' => $this->options['status_of_removed'] ), array( 'ID' => $missing_post_id ) );
            $this->record->recount_terms( $missing_post_id, $final_post_type );
            $this->logger( sprintf( __( 'Instead of deletion, change post with ID `%s` status to %s', 'wp-all-import-pro' ), $missing_post_id, $this->options['status_of_removed'] ) );
        }
    }

    public function change_post_status( $post_id, $new_status ) {
        if ( $final_post_type = get_post_type( $post_id ) and $new_status != get_post_status( $post_id ) ) {
            $this->wpdb->update( $this->wpdb->posts, array( 'post_status' => $new_status ), array( 'ID' => $post_id ) );
            $this->record->recount_terms( $post_id, $final_post_type );
            $this->logger( sprintf( __( 'Instead of deletion, change post with ID `%s` status to %s', 'wp-all-import-pro' ), $post_id, $new_status ) );
        }
    }

    public function delete_record_not_present_in_file( $missing_post_id ) {
        // Trigger pre delete hook.
        do_action( 'pmxi_before_delete_post', $missing_post_id, $this->record );

        // Remove attachments
        if ( ! empty( $this->options['is_delete_attachments'] ) ) {
            wp_delete_attachments( $missing_post_id, true, 'files' );
        }

        // Remove images
        if ( ! empty( $this->options['is_delete_imgs'] ) ) {
            wp_delete_attachments( $missing_post_id, true, 'images' );
        }

        // Clear post's relationships
        wp_delete_object_term_relationships( $missing_post_id, get_object_taxonomies( '' != $this->options['custom_type'] ? $this->options['custom_type'] : 'post' ) );
    }

    public function delete_records( $ids ) {
        do_action( 'pmxi_delete_post', $ids, $this->record );

        foreach ( $ids as $id ) {
            wp_delete_post( $id, true );
        }
    }

    public function combine_data( $data ) {
        extract( $data );

        $articleData = apply_filters( 'wp_all_import_combine_article_data', array(
            'post_type'      => $post_type,
            'post_status'    => ( "xpath" == $this->options['status'] ) ? $post_status : $this->options['status'],
            'comment_status' => ( "xpath" == $this->options['comment_status'] ) ? $comment_status : $this->options['comment_status'],
            'ping_status'    => ( "xpath" == $this->options['ping_status'] ) ? $ping_status : $this->options['ping_status'],
            'post_title'     => ( ! empty( $this->options['is_leave_html'] ) ) ? html_entity_decode( $title ) : $title,
            'post_excerpt'   => apply_filters( 'pmxi_the_excerpt', ( ( ! empty( $this->options['is_leave_html'] ) ) ? html_entity_decode( $post_excerpt ) : $post_excerpt ), $this->id ),
            'post_name'      => $post_slug,
            'post_content'   => apply_filters( 'pmxi_the_content', ( ( ! empty( $this->options['is_leave_html'] ) ) ? html_entity_decode( $contents ) : $contents ), $this->id ),
            'post_date'      => $date,
            'post_date_gmt'  => get_gmt_from_date( $date ),
            'post_author'    => $post_author,
            'menu_order'     => (int) $menu_order,
            'post_parent'    => ( "no" == $this->options['is_multiple_page_parent'] ) ? wp_all_import_get_parent_post( $page_parent, $post_type, $this->options['type'] ) : (int) $this->options['parent'],
            'page_template'  => ( "no" == $this->options['is_multiple_page_template'] ) ? $page_template : $this->options['page_template']
        ), $this->options['custom_type'], $this->record->id, $i );
        if ( 'shop_coupon' == $post_type ) {
            $articleData['post_excerpt'] = $articleData['post_content'];
        }
        $this->logger( sprintf( __( 'Combine all data for post `%s`...', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );

        // if ( "xpath" == $this->options['status'] )
        // {
        // 	$status_object = get_post_status_object($post_status);

        // 	if ( empty($status_object) )
        // 	{
        // 		$articleData['post_status'] = 'draft';
        // 		$this->logger(sprintf(__('<b>WARNING</b>: Post status `%s` is not supported, post `%s` will be saved in draft.', 'wp-all-import-pro'), $post_status, $articleData['post_title']));
        // 		$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
        // 	}
        // }

        return $articleData;
    }

    public function choose_data_to_update( $post_to_update, $post_to_update_id, $post_type, $taxonomies, &$articleData, $i, &$existing_taxonomies = [] ) {
        // preserve date of already existing article when duplicate is found
        if ( ( ! $this->options['is_update_categories'] and ( is_object_in_taxonomy( $post_type, 'category' ) or is_object_in_taxonomy( $post_type, 'post_tag' ) ) ) or ( $this->options['is_update_categories'] and $this->options['update_categories_logic'] != "full_update" ) ) {
            $this->logger( sprintf( __( 'Preserve taxonomies of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
            $existing_taxonomies = array();
            foreach ( array_keys( $taxonomies ) as $tx_name ) {
                $txes_list = get_the_terms( $articleData['ID'], $tx_name );
                if ( is_wp_error( $txes_list ) ) {
                    $this->logger( sprintf( __( '<b>WARNING</b>: Unable to get current taxonomies for article #%d, updating with those read from XML file', 'wp-all-import-pro' ), $articleData['ID'] ) );
                    ! $this->is_cron and \PMXI_Plugin::$session->warnings ++;
                } else {
                    $txes_new = array();
                    if ( ! empty( $txes_list ) ):
                        foreach ( $txes_list as $t ) {
                            $txes_new[] = $t->term_taxonomy_id;
                        }
                    endif;
                    $existing_taxonomies[ $tx_name ][ $i ] = $txes_new;
                }
            }
        }

        if ( ! $this->options['is_update_dates'] ) { // preserve date of already existing article when duplicate is found
            $articleData['post_date']     = $post_to_update->post_date;
            $articleData['post_date_gmt'] = $post_to_update->post_date_gmt;
            $this->logger( sprintf( __( 'Preserve date of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_status'] ) { // preserve status and trashed flag
            $articleData['post_status'] = $post_to_update->post_status;
            $this->logger( sprintf( __( 'Preserve status of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_content'] ) {
            unset( $articleData['post_content'] );
            $this->logger( sprintf( __( 'Preserve content of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_title'] ) {
            $articleData['post_title'] = $post_to_update->post_title;
            $this->logger( sprintf( __( 'Preserve title of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_slug'] ) {
            $articleData['post_name'] = $post_to_update->post_name;
            $this->logger( sprintf( __( 'Preserve slug of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        // Check for changed slugs for published post objects and save the old slug.
        if ( ! empty( $articleData['post_name'] ) and $articleData['post_name'] != $post_to_update->post_name ) {
            $old_slugs = (array) get_post_meta( $post_to_update_id, '_wp_old_slug' );

            // If we haven't added this old slug before, add it now.
            if ( ! empty( $post_to_update->post_name ) && ! in_array( $post_to_update->post_name, $old_slugs ) ) {
                add_post_meta( $post_to_update_id, '_wp_old_slug', $post_to_update->post_name );
            }

            // If the new slug was used previously, delete it from the list.
            if ( in_array( $articleData['post_name'], $old_slugs ) ) {
                delete_post_meta( $post_to_update_id, '_wp_old_slug', $articleData['post_name'] );
            }
        }

        if ( ! $this->options['is_update_excerpt'] ) {
            $articleData['post_excerpt'] = $post_to_update->post_excerpt;
            $this->logger( sprintf( __( 'Preserve excerpt of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_menu_order'] ) {
            $articleData['menu_order'] = $post_to_update->menu_order;
            $this->logger( sprintf( __( 'Preserve menu order of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_parent'] ) {
            $articleData['post_parent'] = $post_to_update->post_parent;
            $this->logger( sprintf( __( 'Preserve post parent of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_post_type'] ) {
            $articleData['post_type'] = $post_to_update->post_type;
            $this->logger( sprintf( __( 'Preserve post type of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_comment_status'] ) {
            $articleData['comment_status'] = $post_to_update->comment_status;
            $this->logger( sprintf( __( 'Preserve comment status of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_ping_status'] ) {
            $articleData['ping_status'] = $post_to_update->ping_status;
            $this->logger( sprintf( __( 'Preserve ping status of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! $this->options['is_update_author'] ) {
            $articleData['post_author'] = $post_to_update->post_author;
            $this->logger( sprintf( __( 'Preserve post author of already existing article for `%s`', 'wp-all-import-pro' ), $this->get_record_title( $articleData ) ) );
        }
        if ( ! wp_all_import_is_update_cf( '_wp_page_template', $this->options ) ) {
            $articleData['page_template'] = get_post_meta( $post_to_update_id, '_wp_page_template', true );
        }
    }

    public function delete_images( $post_id, $articleData, $images_bundle ) {
        $title = $this->get_record_title( $articleData );

        if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_attachments'] ) ) {
            $this->logger( sprintf( __( 'Deleting attachments for `%s`', 'wp-all-import-pro' ), $title ) );
            wp_delete_attachments( $post_id, ! $this->options['is_search_existing_attach'], 'files' );
        }
        // handle obsolete attachments (i.e. delete or keep) according to import settings
        if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_images'] and $this->options['update_images_logic'] == "full_update" ) ) {
            $this->logger( sprintf( __( 'Deleting images for `%s`', 'wp-all-import-pro' ), $title ) );
            if ( ! empty( $images_bundle ) ) {
                foreach ( $images_bundle as $slug => $bundle_data ) {
                    $option_slug = ( $slug == 'pmxi_gallery_image' ) ? '' : $slug;
                    if ( count( $images_bundle ) > 1 && $slug == 'pmxi_gallery_image' ) {
                        continue;
                    }
                    $do_not_remove_images = ( $this->options[ $option_slug . 'download_images' ] == 'gallery' or $this->options[ $option_slug . 'do_not_remove_images' ] ) ? false : true;
                    $missing_images       = wp_delete_attachments( $post_id, $do_not_remove_images, 'images' );
                }
            }
        }

        return $missing_images ?? [];
    }

    public function delete_image_custom_field( $post_id, $meta_key ) {
        $this->delete_meta( $post_id, $meta_key );
    }

    public function delete_meta( $post_id, $meta_key ) {
        delete_post_meta( $post_id, $meta_key );
    }

    public function get_missing_records( $ids, $missing_status, $missing_cf ) {
	    $query = "SELECT ID as post_id FROM " . $this->wpdb->prefix . "posts";
	    if (!empty($missing_cf)) {
		    foreach ($missing_cf as $key => $cf) {
			    if (!empty($cf['name'])) {
				    $query .= " LEFT JOIN " . $this->wpdb->prefix . "postmeta AS pm". $key . " ON (". $this->wpdb->prefix ."posts.ID = pm". $key .".post_id AND pm". $key .".meta_key='". $cf['name'] ."')";
			    }
		    }
	    }
	    if ($this->options['custom_type'] == 'product') {
		    $query .= " WHERE post_type IN (\"product\", \"product_variation\") AND ID NOT IN (" . implode(",", $ids) . ")";
	    } else {
		    $query .= " WHERE post_type = '" . $this->options['custom_type'] . "' AND ID NOT IN (" . implode(",", $ids) . ")";
	    }
	    if (!empty($missing_status) || !empty($missing_cf)) {
		    $query .= " AND (";
	    }
	    if (!empty($missing_status)) {
		    $query .= " post_status != '" . $missing_status . "'";
		    if($this->options['custom_type'] == 'product' && in_array($missing_status, ['trash','draft'])){
			    $query .= " AND post_status != 'private'";
		    }
		    if (!empty($missing_cf)) {
			    $query .= " OR ";
		    }
	    }
	    if (!empty($missing_cf)) {
		    $cf_conditions = [];
		    foreach ($missing_cf as $key => $cf) {
			    if (!empty($cf['name'])) {
				    $cf_conditions[] = "pm" . $key . ".meta_value != '" . $cf['value'] . "'";
			    }
		    }
		    $query .= implode(" OR ", $cf_conditions);
	    }
	    if (!empty($missing_status) || !empty($missing_cf)) {
		    $query .= ")";
	    }

	    $missing_ids = $this->wpdb->get_results($query, ARRAY_A);

	    return $missing_ids;
    }
}