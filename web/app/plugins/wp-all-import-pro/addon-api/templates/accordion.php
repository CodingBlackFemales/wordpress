<div class="wpallimport-collapsed pmxi-addon <?php echo $addon->isAccordionClosed($type, $subtype) ? 'closed' : ''; ?>" data-addon="<?php echo $addon->slug; ?>" data-type="<?php echo $type ?>" data-subtype="<?php echo $subtype; ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')) ?>">
    <div class="wpallimport-content-section">
        <div class="wpallimport-collapsed-header">
            <h3 data-test="toggle"><?php echo $addon->name(); ?></h3>
        </div>

        <div class="wpallimport-collapsed-content" style="padding: 0;">
            <div class="wpallimport-collapsed-content-inner">
                <table class="form-table" style="max-width:none;">
                    <tr>
                        <td colspan="3">
                            <?php if (!empty($groups)) : ?>
                                <p>
                                    <strong><?php _e("Please choose your group.", 'wp-all-import-pro'); ?></strong>
                                </p>
                                <ul>
                                    <?php foreach ($groups as $group) {
                                        $show_group = apply_filters('wp_all_import_addon_show_group', true, $addon, $group, $type);
                                        $id = $group['id'];
                                        $label = $group['label'];
                                        $is_checked = in_array($id, $importOptions[$addon->slug . '_groups']);

                                        if ($show_group) : ?>
                                            <li>
                                                <input id="addon-group-<?php echo "{$addon->slug}-{$id}"; ?>" type="checkbox" data-label="<?php echo $label; ?>" name="<?php echo $addon->slug; ?>_groups[]" value="<?php echo $id; ?>" data-group="<?php echo $id; ?>" <?php if ($is_checked) : ?>checked="checked" <?php endif; ?> class="wpallimport-import-group-checkbox" />
                                                <label for="addon-group-<?php echo "{$addon->slug}-{$id}"; ?>"><?php echo $label; ?></label>
                                            </li>
                                        <?php endif; ?>
                                    <?php } ?>
                                </ul>

                                <div class="pmxi-addon-groups-output"></div>
                            <?php else : ?>
                                <p>
                                    <strong><?php _e("Please create Groups.", 'wp-all-import-pro'); ?></strong>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
