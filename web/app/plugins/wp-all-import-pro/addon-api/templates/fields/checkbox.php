<ul class="pmxi-addon-checkbox-list">
    <?php foreach ($field['choices'] as $choice) : ?>
        <?php $checked = $field['multiple'] && in_array($choice['value'], $field_value ?? []) ?: $field_value === 1; ?>
        <li>
            <label>
                <input data-test="input" type="checkbox" id="checkbox-choice-<?php echo esc_attr($field['key'] . '-' . $choice['value']); ?>" name="<?php echo esc_attr($html_name); ?>" value="<?php echo esc_attr($choice['value']); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                <?php echo $choice['label']; ?>
            </label>
        </li>
    <?php endforeach; ?>
</ul>
