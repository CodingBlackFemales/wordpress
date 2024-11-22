<?php
    $html_id = str_replace(['[', ']'], ['_', ''], $html_name);
    $gallery = (is_array($field_value) && isset($field_value['gallery'])) ? $field_value['gallery'] : '';
?>

<div class="input">
    <label><?php _e('Enter image URL one per line, or separate them with a', 'wp-all-import-pro'); ?> </label>
    <input type="text" value="<?php echo (!empty($field_value['delim'])) ? esc_attr($field_value['delim']) : ''; ?>" name="<?php echo esc_attr($html_name); ?>[delim]" class="small rad4" placeholder=",">

    <textarea placeholder="http://example.com/images/image-1.jpg" style="clear: both; display: block; margin-top: 10px;" data-test="input" class="text newline rad4" name="<?php echo esc_attr($html_name); ?>[gallery]"><?php echo esc_attr($gallery); ?></textarea>

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
                <?php _e('Use images currently uploaded in wp-content/uploads/wpallimport/files/', 'wp-all-import-pro'); ?></label>
        </div>
        <!--<div class="input">
            <input type="checkbox" id="<?php echo $html_id . '_only_append_new'; ?>" name="<?php echo esc_attr($html_name); ?>[only_append_new]" value="1" <?php echo (!empty($field_value['only_append_new'])) ? 'checked="checked"' : ''; ?> />
            <label for="<?php echo $html_id . '_only_append_new'; ?>">
                <?php _e('Append only new images and do not touch existing during updating gallery field.', 'wp-all-import-pro'); ?></label>
        </div>-->
    </div>
</div>
