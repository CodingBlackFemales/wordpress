<?php

use function Wpai\AddonAPI\view;

$switcher_id = str_replace(['[', ']'], ['-', ''], $html_name);
$current_mode = (!empty($field_value['mode'])) ? $field_value['mode'] : 'fixed';
?>
<div class="pmxi-repeater-mode pmxi-switcher">
    <div class="pmxi-switcher-radio-group">
        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_fixed" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="fixed" <?php echo ($current_mode == 'fixed') ? 'checked="checked"' : ''; ?> />
            <span>Fixed</span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_xml" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="variable-xml" <?php echo ($current_mode == 'variable-xml') ? 'checked="checked"' : ''; ?> />
            <span>Variable (XML)</span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_csv" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="variable-csv" <?php echo ($current_mode == 'variable-csv') ? 'checked="checked"' : ''; ?> />
            <span>Variable (CSV)</span>
        </label>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_fixed">
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_xml">
        <p>
            <?php printf(__("For each %s do ..."), '<input type="text" name="' . $html_name . '[foreach]" value="' . ((empty($field_value["foreach"])) ? '' : $field_value["foreach"]) . '" class="pmxi-repeater-foreach widefat rad4"/>'); ?>
            <a href="http://www.wpallimport.com/documentation/jetengine/repeater-fields/" target="_blank"><?php _e('(documentation)', 'wp_all_import_acf_add_on'); ?></a>
        </p>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_csv">
        <p>
            <?php printf(__("Separator Character %s"), '<input type="text" name="' . $html_name . '[separator]" value="' . ((empty($field_value["separator"])) ? '|' : $field_value["separator"]) . '" class="pmxi-variable-separator small widefat rad4"/>'); ?>
            <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
        </p>
    </div>
</div>

<div class="input">
    <input type="hidden" name="<?php echo esc_attr($html_name); ?>[ignore_blanks]" value="0" />
    <input type="checkbox" id="<?php echo $switcher_id . '_ignore_blanks'; ?>" value="1" name="<?php echo esc_attr($html_name); ?>[ignore_blanks]" <?php echo (!empty($field_value['ignore_blanks'])) ? 'checked="checked"' : ''; ?>>
    <label for="<?php echo $switcher_id . '_ignore_blanks'; ?>">Ignore blank fields</label>
    <a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('If the value of the element or column in your file is blank, it will be ignored. Use this option when some records in your file have a different number of repeating elements than others.', 'wp_all_import_acf_add_on') ?>">?</a>
</div>

<div class="pmxi-repeater">
    <div class="pmxi-repeater-rows">
        <?php
        if (!empty($field_value['rows'])) {
            foreach ($field_value['rows'] as $key => $row) {
                view('repeater-row', [
                    'subfields' => $field['subfields'],
                    'row_index' => $key,
                    'parent_class' => $field_class,
                ]);
            }
        }
        ?>
    </div>

    <div class="pmxi-repeater-actions">
        <button class="pmxi-repeater-button pmxi-repeater-add-row button button-primary" type="button">
            Add Row
        </button>
    </div>

    <template class="pmxi-repeater-template">
        <?php
        view('repeater-row', [
            'subfields' => $field['subfields'],
            'row_index' => '__index__',
            'parent_class' => $field_class,
        ]);
        ?>
    </template>
</div>