<?php 
/**
 * @var PMXI_Addon_Base $addon
 * @var string $prefix
 * @var string $prefix_id
 * @var array $options
 */

use Wpai\AddonAPI\PMXI_Addon_Base;

?>
<div class="input">
    <input type="hidden" name="<?php echo $prefix; ?>[fields_list]" value="0" />
    <input type="hidden" name="<?php echo $prefix; ?>[is_update]" value="0" />
    <input type="checkbox" id="<?php echo $prefix_id; ?>_is_update" name="<?php echo $prefix; ?>[is_update]" value="1" <?php echo $options['is_update'] ? 'checked="checked"': '' ?>  class="switcher"/>
    <label for="<?php echo $prefix_id; ?>_is_update"><?php printf(esc_html__('%s Fields', 'wp-all-import-pro'), $addon->name()); ?></label>
    <!--a href="#help" class="wpallimport-help" title="<?php printf(esc_html__('If Keep %s Fields box is checked, it will keep all %s Fields, and add any new %s Fields specified in %s Fields section, as long as they do not overwrite existing fields. If \'Only keep this %s Fields\' is specified, it will only keep the specified fields.', 'wp-all-import-pro'), $addon->name(), $addon->name(), $addon->name(), $addon->name(), $addon->name(), $addon->name()); ?>">?</a-->
    <div class="switcher-target-<?php echo $prefix_id; ?>_is_update" style="padding-left:17px;">
        <div class="input">
            <input type="radio" id="<?php echo $prefix_id; ?>_logic_full_update" name="<?php echo $prefix; ?>[update_logic]" value="full_update" <?php echo ( "full_update" == $options['update_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
            <label for="<?php echo $prefix_id; ?>_logic_full_update"><?php printf(esc_html__('Update all %s Fields', 'wp-all-import-pro'), $addon->name()); ?></label>
        </div>
        <div class="input">
            <input type="radio" id="<?php echo $prefix_id; ?>_logic_only" name="<?php echo $prefix; ?>[update_logic]" value="only" <?php echo ( "only" == $options['update_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
            <label for="<?php echo $prefix_id; ?>_logic_only"><?php printf(esc_html__('Update only these %s Fields, leave the rest alone', 'wp-all-import-pro'), $addon->name()); ?></label>
            <div class="switcher-target-<?php echo $prefix_id; ?>_logic_only pmxi_choosen" style="padding-left:17px;">
                <span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
                <input class="choosen_input" value="<?php if (!empty($options['fields_list']) and "only" == $options['update_logic']) echo esc_html(implode(',', $options['fields_list'])); ?>" type="hidden" name="<?php echo $prefix; ?>[fields_only_list]"/>
            </div>
        </div>
        <div class="input">
            <input type="radio" id="<?php echo $prefix_id; ?>_logic_except" name="<?php echo $prefix; ?>[update_logic]" value="all_except" <?php echo ( "all_except" == $options['update_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
            <label for="<?php echo $prefix_id; ?>_logic_except"><?php printf(esc_html__('Leave these fields alone, update all other %s Fields', 'wp-all-import-pro'), $addon->name()); ?></label>
            <div class="switcher-target-<?php echo $prefix_id; ?>_logic_except pmxi_choosen" style="padding-left:17px;">
                <span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo esc_html(implode(',', $existing_meta_keys));?></span>
                <input class="choosen_input" value="<?php if (!empty($options['fields_list']) and "all_except" == $options['update_logic']) echo esc_html(implode(',', $options['fields_list'])); ?>" type="hidden" name="<?php echo $prefix; ?>[fields_except_list]"/>
            </div>
        </div>
    </div>
</div>
