<select name="<?php echo esc_attr($html_name); ?>" data-test="input">
    <option value=""><?php _e('Select', 'wp-all-import-pro'); ?></option>
    <?php foreach ($field['choices'] as $choice) : ?>
        <option value="<?php echo $choice['value']; ?>" <?php echo $choice['value'] == $field_value ? 'selected="selected"' : ''; ?>>
            <?php echo $choice['label']; ?>
        </option>
    <?php endforeach; ?>
</select>
