<?php $html_id = str_replace(['[', ']'], ['_', ''], $html_name); ?>

<input type="text" placeholder="" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['url']); ?>" name="<?php echo esc_attr($html_name); ?>[url]" data-test="input" class="text w95 widefat rad4" />

<div class="pmxi-addon-subfields">
    <div class="input">
        <input type="hidden" name="<?php echo esc_attr($html_name); ?>[search_in_media]" value="0" />
        <input class="pmxi-search-in-media-input" type="checkbox" id="<?php echo $html_id . '_search_in_media'; ?>" name="<?php echo esc_attr($html_name); ?>[search_in_media]" value="1" <?php echo (!empty($field_value['search_in_media'])) ? 'checked="checked"' : ''; ?> />
        <label for="<?php echo $html_id . '_search_in_media'; ?>">
            <?php _e('Search through the Media Library for existing images before importing new images', 'wp-all-import-pro'); ?></label>
        <a href="#help" class="wpallimport-help" title="<?php _e('If an image with the same file name is found in the Media Library then that image will be attached to this record instead of importing a new image. Disable this setting if your import has different images with the same file name.', 'wp-all-import-pro') ?>" style="position: relative; top: -2px;">?</a>
    </div>

    <div class="pmxi-search-logic">
        <div class="input">
            <input type="radio" id="<?php echo $html_id; ?>_search_logic_by_url" name="<?php echo esc_attr($html_name); ?>[search_logic]" value="by_url" <?php echo (!empty($field_value['search_logic']) && "by_url" == $field_value['search_logic'] || empty($field_value['search_logic'])) ? 'checked="checked"' : '' ?> />
            <label for="<?php echo $html_id; ?>_search_logic_by_url"><?php _e('Match image by URL', 'wp-all-import-pro') ?></label>
        </div>

        <div class="input">
            <input type="radio" id="<?php echo $html_id; ?>_search_logic_by_filename" name="<?php echo esc_attr($html_name); ?>[search_logic]" value="by_filename" <?php echo (!empty($field_value['search_logic']) && "by_filename" == $field_value['search_logic']) ? 'checked="checked"' : '' ?> />
            <label for="<?php echo $html_id; ?>_search_logic_by_filename"><?php _e('Match image by filename', 'wp-all-import-pro') ?></label>
        </div>
    </div>


    <div class="input">
        <input type="hidden" name="<?php echo esc_attr($html_name); ?>[search_in_files]" value="0" />
        <input type="checkbox" id="<?php echo $html_id . '_search_in_files'; ?>" name="<?php echo esc_attr($html_name); ?>[search_in_files]" value="1" <?php echo (!empty($field_value['search_in_files'])) ? 'checked="checked"' : ''; ?> />
        <label for="<?php echo $html_id . '_search_in_files'; ?>">
            <?php _e('Use images currently uploaded in wp-content/uploads/wpallimport/files/', 'wp-all-import-pro'); ?>
        </label>
    </div>
</div>
