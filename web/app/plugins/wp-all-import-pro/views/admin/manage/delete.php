<h2><?php _e('Delete Import', 'wp-all-import-pro') ?></h2>

<?php

if (!empty($item->options['custom_type'])){
	switch ($item->options['custom_type']){
		case 'taxonomies':
			$tx = get_taxonomy($item->options['taxonomy_type']);
			$custom_type = new stdClass();
			$custom_type->label = empty($tx->labels->name) ? __('Taxonomy Terms', 'wp-all-import-pro') : $tx->labels->name;
			$custom_type->singular_label = empty($tx->labels->singular_name) ? __('Taxonomy Term', 'wp-all-import-pro') : $tx->labels->singular_name;
			break;
        case 'comments':
            $custom_type = new stdClass();
            $custom_type->label = __('Comments', 'wp-all-import-pro');
            $custom_type->singular_label = __('Comment', 'wp-all-import-pro');
            break;
        case 'woo_reviews':
            $custom_type = new stdClass();
            $custom_type->label = __('WooCommerce Reviews', 'wp-all-import-pro');
            $custom_type->singular_label = __('Review', 'wp-all-import-pro');
            break;
		default:
			$custom_type = wp_all_import_custom_type( $item->options['custom_type'] );
			if ( ! empty($custom_type) ) {
				$custom_type->label = $custom_type->labels->name;
				$custom_type->singular_label = $custom_type->labels->singular_name;
			}
			break;
	}
	$cpt_name = ( ! empty($custom_type)) ? ( ($associated_posts == 1) ? $custom_type->singular_label : $custom_type->label) : '';
	// Remove mention of WooCommerce from post type string
	$cpt_del_name = str_replace("WooCommerce", "", $cpt_name);
}
else{
	$cpt_name = '';
	$cpt_del_name = '';
}

?>

<form method="post">	
	<div class="input">
		<div class="input">
			<input type="hidden" name="is_delete_import" value="0"/>
			<input type="checkbox" id="is_delete_import" name="is_delete_import" style="position: relative; top: 2px;" value="1"/> 
			<label for="is_delete_import"><?php _e('Delete import','wp-all-import-pro');?> </label>
		</div>
		<div class="input">
			<input type="hidden" name="is_delete_posts" value="0"/>
			<input type="checkbox" id="is_delete_posts" name="is_delete_posts" class="switcher" style="position: relative; top: 2px;" value="1"/>
			<label for="is_delete_posts"><?php printf(__('Delete %s created by %s','wp-all-import-pro'), strtolower($cpt_del_name), empty($item->friendly_name) ? $item->name : $item->friendly_name );?> </label>
		</div>
		<div class="switcher-target-is_delete_posts" style="padding: 5px 17px;">
			<div class="input">
				<input type="hidden" name="is_delete_images" value="no"/>
				<input type="checkbox" id="is_delete_images" name="is_delete_images" value="yes" />
				<label for="is_delete_images"><?php _e('Delete associated images from media gallery', 'wp-all-import-pro') ?></label>			
			</div>
			<div class="input">
				<input type="hidden" name="is_delete_attachments" value="no"/>
				<input type="checkbox" id="is_delete_attachments" name="is_delete_attachments" value="yes" />
				<label for="is_delete_attachments"><?php _e('Delete associated files from media gallery', 'wp-all-import-pro') ?></label>			
			</div>			
		</div>
		<?php if ( ! empty($item->options['deligate']) and $item->options['deligate'] == 'wpallexport' and class_exists('PMXE_Plugin')): ?>
			<?php
				$export = new PMXE_Export_Record();
				$export->getById($item->options['export_id']);
				if ( ! $export->isEmpty() ){
					printf(__('<p class="wpallimport-delete-posts-warning"><strong>Important</strong>: this import was created automatically by WP All Export. All posts exported by the "%s" export job have been automatically associated with this import.</p>', 'wp_all_export_plugin'), $export->friendly_name );
				}
			?>
		<?php endif; ?>		

		<p class="wp-all-import-sure-to-delete"><?php _e('Are you sure you want to delete ', 'wp-all-import-pro'); ?><span class="sure_delete_posts"><?php printf('<strong>%s %s</strong>', $associated_posts, $cpt_name); ?></span><span class="sure_delete_posts_and_import"> <?php _e('and', 'wp-all-import-pro');?> </span><span class="sure_delete_import"><?php printf(__('the <strong>%s</strong> import', 'wp-all-import-pro'), empty($item->friendly_name) ? $item->name : $item->friendly_name);?></span>?</p>
	</div>
	<div class="submit" style="width: 90px;">
		<?php wp_nonce_field('delete-import', '_wpnonce_delete-import') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="hidden" name="import_ids[]" value="<?php echo esc_attr($item->id); ?>" />
		<input type="hidden" name="base_url" value="<?php echo $this->baseUrl; ?>">
		<input type="submit" class="button-primary delete-single-import wp_all_import_ajax_deletion" value="Delete" />
		<div class="wp_all_import_functions_preloader"></div>
	</div>
	<div class="wp_all_import_deletion_log"></div>
</form>