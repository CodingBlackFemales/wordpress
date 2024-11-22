<div class="pmxi-switcher">
    <div class="pmxi-switcher-radio-group">
        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_yes" class="switcher" data-test="switcher-yes" name="<?php echo $switcher_name; ?>" value="yes" <?php echo 'no' != $switcher_value ? 'checked="checked"' : '' ?> />
            <span><?php echo $yes_label; ?></span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_no" class="switcher" data-test="switcher-no" name="<?php echo $switcher_name; ?>" value="no" <?php echo 'no' == $switcher_value ? 'checked="checked"' : ''; ?> />
            <span><?php echo $no_label; ?></span>
            <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Specify the value. For multiple values, separate with commas. If the choices are of the format option : Option, option-2 : Option 2, use option and option-2 for values.', 'wp_all_import_acf_add_on') ?>">?</a>
        </label>
    </div>

    <?php if ($yes_input) { ?>
        <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_yes">
            <?php echo $yes_input; ?>
        </div>
    <?php } ?>

    <?php if ($no_input) { ?>
        <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_no">
            <?php echo $no_input; ?>
        </div>
    <?php } ?>
</div>
