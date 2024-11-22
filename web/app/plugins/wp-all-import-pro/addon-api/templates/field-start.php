<div class="field field-type-<?php echo esc_attr($field['type']); ?> field-key-<?php echo esc_attr($field['key']); ?>">
    <p class="label">
        <label>
            <b><?php echo $field['label']; ?></b>

            <?php if ($field['hint']) { ?>
                <a href="#help" class="wpallimport-help" title="<?php echo esc_attr($field['hint']); ?>" style="top:0;">?</a>
            <?php } ?>
        </label>
    </p>

    <div class="wpallimport-clear"></div>

    <div class="pmxi-addon-input-wrap">