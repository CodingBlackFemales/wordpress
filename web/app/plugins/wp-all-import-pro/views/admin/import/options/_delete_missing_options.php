<?php
    $is_valid_delete_missing = true;
    $error_codes = $this->errors->get_error_codes();
    if ( ! empty($error_codes) and is_array($error_codes) and in_array('delete-missing-validation', $error_codes)) {
        $is_valid_delete_missing = false;
    }
    if (!isset($disabled_delete_missing_options)) {
        $disabled_delete_missing_options = [];
    }
    if (!isset($hidden_delete_missing_options)) {
        $hidden_delete_missing_options = [];
    }
?>
<div class="input">
    <input type="hidden" name="is_delete_missing" value="0" />
    <input type="checkbox" id="is_delete_missing" name="is_delete_missing" value="1" <?php echo $post['is_delete_missing'] ? 'checked="checked"': '' ?> class="switcher"/>
    <label for="is_delete_missing"><?php printf(__('Remove or modify %s that are not present in this import file', 'wp_all_import_plugin'), $cpt_name) ?></label> <a href="https://www.youtube.com/watch?v=djC1IvYtDDY&ab_channel=WPAllImport" target="_blank" class="video-embed" style="position: relative; top: -2px;"></a>
</div>
<div class="switcher-target-is_delete_missing" style="padding-left:17px;">

    <h4><?php printf(__('Which %s do you want to remove or modify?', 'wp_all_import_plugin'), $cpt_name); ?></h4>
    <input type="radio" id="delete_missing_logic_import" name="delete_missing_logic" value="import" <?php echo 'all' != $post['delete_missing_logic'] ? 'checked="checked"': '' ?>/>
    <label for="delete_missing_logic_import"><?php printf(__('Remove or modify %s created or updated by this import and then later removed from this import file', 'wp_all_import_plugin' ), $cpt_name);?></label><br>
    <input type="radio" id="delete_missing_logic_all" name="delete_missing_logic" value="all" <?php echo 'all' == $post['delete_missing_logic'] ? 'checked="checked"': '' ?>/>
    <label for="delete_missing_logic_all"><?php printf(__('Remove or modify all %s on this site that are not present in this import file', 'wp_all_import_plugin' ), $cpt_name);?></label><br>

    <h4><?php printf(__('What do you want to do with those %s?', 'wp_all_import_plugin'), $cpt_name); ?></h4>

    <input type="radio" id="delete_missing_action_keep" class="switcher" name="delete_missing_action" value="keep" <?php echo 'remove' != $post['delete_missing_action'] ? 'checked="checked"': '' ?>/>
    <label for="delete_missing_action_keep"><?php _e('Instead of deletion...', 'wp_all_import_plugin' );?></label><br>

    <div class="switcher-target-delete_missing_action_keep <?php if (empty($is_valid_delete_missing)): ?>delete-missing-error-wrapper<?php endif;?>" style="padding-left:26px;">
        <div class="delete-missing-error <?php if (!empty($is_valid_delete_missing)): ?>hidden<?php endif; ?>"><p><strong><?php _e('Error'); ?>:</strong> <?php _e('at least one option must be selected.'); ?></p></div>
        <?php if ( $post_type !== 'taxonomies' ): ?>
            <?php if ( !in_array('is_send_removed_to_trash', $hidden_delete_missing_options) ): ?>
                <div class="input">
                    <input type="hidden" name="is_send_removed_to_trash" value="0" />
                    <input type="checkbox" id="is_send_removed_to_trash" name="is_send_removed_to_trash" value="1" <?php echo $post['is_send_removed_to_trash'] && !in_array('is_send_removed_to_trash', $disabled_delete_missing_options) ? 'checked="checked"': '' ?> <?php echo in_array('is_send_removed_to_trash', $disabled_delete_missing_options) ? 'disabled="disabled' : '';?>/>
                    <?php if ( $post_type == 'product' ): ?>
                        <label for="is_send_removed_to_trash"><?php printf(__('Send removed %s to trash', 'wp_all_import_plugin'), $cpt_name); ?></label>
                        <a href="#help" class="wpallimport-help" title="<?php _e('Removed parent products will have all of their variations moved to the trash with them. If variations are removed from the import file and their parent product isn\'t, the variations will be disabled but will not be present in the trash. You can edit these disabled product variations in WooCommerce to manually reenable them.', 'wp_all_import_plugin') ?>" style="top: -2px;">?</a>
                    <?php else: ?>
                        <label for="is_send_removed_to_trash"><?php printf(__('Send removed %s to trash', 'wp_all_import_plugin'), $cpt_name); ?></label>
                    <?php endif; ?>
                    <?php if ( in_array('is_send_removed_to_trash', $disabled_delete_missing_options) ): ?>
                        <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('This option is not available when importing %s.', 'wp_all_import_plugin'), $cpt_name) ?>">?</a>
                    <?php endif;?>
                </div>
            <?php endif; ?>
            <?php if ( !in_array('is_change_post_status_of_removed', $hidden_delete_missing_options) ): ?>
                <div class="input">
                    <input type="hidden" name="is_change_post_status_of_removed" value="0" />
                    <input type="checkbox" class="switcher-horizontal" id="is_change_post_status_of_removed" name="is_change_post_status_of_removed" value="1" <?php echo $post['is_change_post_status_of_removed'] && !in_array('is_change_post_status_of_removed', $disabled_delete_missing_options) ? 'checked="checked"': '' ?> <?php echo in_array('is_change_post_status_of_removed', $disabled_delete_missing_options) ? 'disabled="disabled' : '';?>/>
                    <?php if ($post_type == 'gf_entries'): ?>
                        <label for="is_change_post_status_of_removed"><?php printf(__('Change %s property to', 'wp_all_import_plugin'), $cpt_name); ?></label>
                    <?php else: ?>
                        <label for="is_change_post_status_of_removed"><?php printf(__('Change status of removed %s to', 'wp_all_import_plugin'), $cpt_name); ?></label>
                    <?php endif; ?>
                    <select name="status_of_removed" style="height: 20px; width: 150px; font-size: 12px !important; padding-top: 2px;top:-1px;" <?php echo in_array('is_change_post_status_of_removed', $disabled_delete_missing_options) ? 'disabled="disabled' : '';?>>
                        <?php if (in_array($post_type, ['comments', 'woo_reviews'])):
                            $comment_statuses = get_comment_statuses();
                            unset($comment_statuses['trash']);
                            foreach ($comment_statuses as $key => $status): ?>
                                <option value="<?php echo $key;?>" <?php if ($key == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo $status;?></option>
                            <?php endforeach; ?>
                        <?php elseif ($post_type == 'gf_entries'): ?>
                            <option value="restore" <?php if ('restore' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Restore', 'gravityforms' );?></option>
                            <option value="unspam" <?php if ('unspam' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Not Spam', 'gravityforms' );?></option>
                            <option value="mark_read" <?php if ('mark_read' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Mark as Read', 'gravityforms' );?></option>
                            <option value="mark_unread" <?php if ('mark_unread' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Mark as Unread', 'gravityforms' );?></option>
                            <option value="add_star" <?php if ('add_star' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Add Star', 'gravityforms' );?></option>
                            <option value="remove_star" <?php if ('remove_star' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Remove Star', 'gravityforms' );?></option>
                            <option value="spam" <?php if ('spam' == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo esc_html__( 'Spam', 'gravityforms' );?></option>
                        <?php else: ?>
                            <?php foreach (get_post_statuses() as $key => $status): ?>
                                <option value="<?php echo $key;?>" <?php if ($key == $post['status_of_removed']):?>selected="selected"<?php endif; ?>><?php echo $status;?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ( in_array('is_change_post_status_of_removed', $disabled_delete_missing_options) ): ?>
                        <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('This option is not available when importing %s.', 'wp_all_import_plugin'), $cpt_name) ?>">?</a>
                    <?php endif;?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ( !in_array('is_update_missing_cf', $hidden_delete_missing_options) ): ?>
            <div class="input">
                <input type="hidden" name="is_update_missing_cf" value="0" />
                <input type="checkbox" id="is_update_missing_cf" name="is_update_missing_cf" value="1" <?php echo $post['is_update_missing_cf'] && !in_array('is_update_missing_cf', $disabled_delete_missing_options) ? 'checked="checked"': '' ?> <?php echo in_array('is_update_missing_cf', $disabled_delete_missing_options) ? 'disabled="disabled' : '';?> class="switcher"/>
                <label for="is_update_missing_cf"><?php printf(__('Set custom fields for removed %s', 'wp_all_import_plugin'), $cpt_name); ?></label>
                <?php if ( in_array('is_update_missing_cf', $disabled_delete_missing_options) ): ?>
                    <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php printf(__('This option is not available when importing %s.', 'wp_all_import_plugin'), $cpt_name) ?>">?</a>
                <?php endif;?>
                <div class="switcher-target-is_update_missing_cf" style="padding-left:17px;">
                    <?php
                        if (isset($post['update_missing_cf_name']) && !is_array($post['update_missing_cf_name'])) {
                            $post['update_missing_cf_name'] = [$post['update_missing_cf_name']];
                        }
                        $post['update_missing_cf_name'] = array_filter($post['update_missing_cf_name']);
                        if (isset($post['update_missing_cf_value']) && !is_array($post['update_missing_cf_value'])) {
                            $post['update_missing_cf_value'] = [$post['update_missing_cf_value']];
                        }
                        $post['update_missing_cf_value'] = array_filter($post['update_missing_cf_value']);
                    ?>
                    <table class="form-table custom-params" style="max-width:none; border:none; width:350px; margin-left:9px;">
                        <thead>
                        <tr>
                            <td style="padding-bottom:2px;font-weight:500;"><?php _e('Name', 'wp_all_import_plugin') ?></td>
                            <td style="padding-bottom:2px;font-weight:500;"><?php _e('Value', 'wp_all_import_plugin') ?></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($post['update_missing_cf_name'])):?>
                            <?php foreach ($post['update_missing_cf_name'] as $i => $name): ?>
                                <tr class="form-field">
                                    <td style="width: 45%;">
                                        <input type="text" name="update_missing_cf_name[]"  value="<?php echo esc_attr($name) ?>" class="widefat" style="margin-bottom:10px;width:150px;"/>
                                    </td>
                                    <td class="action">
                                        <div class="custom_type" rel="default">
                                            <input type="text" name="update_missing_cf_value[]" class="widefat" style="width:150px;" value="<?php echo esc_html($post['update_missing_cf_value'][$i]) ?>"/>
                                        </div>
                                        <span class="action remove">
                                            <a href="#remove" style="top: 8px; right: 15px;"></a>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr class="form-field">
                                <td style="width: 45%;">
                                    <input type="text" name="update_missing_cf_name[]"  value="" class="widefat" style="margin-bottom:10px;width:150px;"/>
                                </td>
                                <td class="action">
                                    <div class="custom_type" rel="default">
                                        <input type="text" name="update_missing_cf_value[]" class="widefat" style="width:150px;" value=""/>
                                    </div>
                                    <span class="action remove">
                                        <a href="#remove" style="top: 8px; right: 15px;"></a>
                                    </span>
                                </td>
                            </tr>
                        <?php endif;?>
                        <tr class="form-field template">
                            <td style="width: 45%;">
                                <input type="text" name="update_missing_cf_name[]" value="" class="widefat" style="margin-bottom:10px; width:150px;"/>
                            </td>
                            <td class="action">
                                <div class="custom_type" rel="default">
                                    <input type="text" name="update_missing_cf_value[]" class="widefat" style="width:150px" value=""/>
                                </div>
                                <span class="action remove">
                                    <a href="#remove" style="top: 8px; right: 15px;"></a>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><a href="javascript:void(0);" title="<?php _e('Add Custom Field', 'wp_all_import_plugin')?>" class="action add-new-custom add-new-entry"><?php _e('Add Custom Field', 'wp_all_import_plugin') ?></a></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( $post_type == 'product' && $post['wizard_type'] == 'new'): ?>
            <div class="input">
                <input type="hidden" name="missing_records_stock_status" value="0" />
                <input type="checkbox" id="missing_records_stock_status" name="missing_records_stock_status" value="1" <?php echo $post['missing_records_stock_status'] ? 'checked="checked"': '' ?>/>
                <label for="missing_records_stock_status"><?php printf(__('Change stock status of removed %s to', 'wp_all_import_plugin'), $cpt_name); ?></label>
                <select name="status_of_removed_products" style="height: 20px; width: 150px; font-size: 12px !important; padding-top: 2px;top:-1px;">
                    <option value="outofstock" <?php if ('outofstock' == $post['status_of_removed_products']):?>selected="selected"<?php endif; ?>><?php _e('Out of stock');?></option>
                    <option value="instock" <?php if ('instock' == $post['status_of_removed_products']):?>selected="selected"<?php endif; ?>><?php _e('In stock');?></option>
                </select>
                <!--                    <a href="#help" class="wpallimport-help" title="--><?php //_e('Option to set the stock status to out of stock instead of deleting the product entirely.', 'wp_all_import_plugin') ?><!--" style="position:relative; top:-2px;">?</a>-->
            </div>
        <?php endif; ?>
        <?php do_action('wp_all_import_delete_missing_options', $post_type, $post); ?>
    </div>

    <input type="radio" id="delete_missing_action_remove" class="switcher" name="delete_missing_action" value="remove" <?php echo 'remove' == $post['delete_missing_action'] ? 'checked="checked"': '' ?>/>
    <label for="delete_missing_action_remove"><?php printf(__('Delete removed %s', 'wp_all_import_plugin' ), $cpt_name);?></label><br>


    <div class="switcher-target-delete_missing_action_remove" style="padding-left:26px;">
        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_delete_attachments" value="0" />
            <input type="checkbox" id="is_delete_attachments" name="is_delete_attachments" value="1" <?php echo $post['is_delete_attachments'] ? 'checked="checked"': '' ?>/>
            <label for="is_delete_attachments"><?php printf(__('Delete files attached to removed %s', 'wp_all_import_plugin'), $cpt_name); ?></label>
        </div>
        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_delete_imgs" value="0" />
            <input type="checkbox" id="is_delete_imgs" name="is_delete_imgs" value="1" <?php echo $post['is_delete_imgs'] ? 'checked="checked"': '' ?>/>
            <label for="is_delete_imgs"><?php printf(__('Delete images attached to removed %s', 'wp_all_import_plugin'), $cpt_name); ?></label>
        </div>
    </div>

    <div class="delete-missing-helper-texts">
        <div class="helper-text helper-text-1">
            <p><?php printf(__('When re-run, %s created or updated by this import and no longer present in the import file will be moved to the trash.', 'wp_all_import_plugin'), $cpt_name); ?></p>
        </div>
        <div class="helper-text helper-text-2">
            <p><?php printf(__('When re-run, %s created or updated by this import and no longer present in the import file will be set to <span class="status_of_removed">draft</span>.', 'wp_all_import_plugin'), $cpt_name); ?></p>
        </div>
        <div class="helper-text helper-text-3">
            <p><?php printf(__('This combination of options is potentially destructive. When re-run, %s created or updated by this import and no longer present in the import file will be deleted with no option for recovery.', 'wp_all_import_plugin'), $cpt_name); ?></p>
        </div>
        <div class="helper-text helper-text-4">
            <p><?php printf(__('This combination of options can affect all %s on this site, even those not created by this import. During import, all %s not present in this import file will be moved to the trash.', 'wp_all_import_plugin'), $cpt_name, $cpt_name); ?></p>
        </div>
        <div class="helper-text helper-text-5">
            <p><?php printf(__('This combination of options can affect all %s on this site, even those not created by this import. During import, all %s not present in this import file will be moved to <span class="status_of_removed">draft</span>.', 'wp_all_import_plugin'), $cpt_name, $cpt_name); ?></p>
        </div>
        <div class="helper-text helper-text-6">
            <p><?php printf(__('This combination of options is potentially destructive. Every time this import is run, now and in the future, all %s not present in this import file will be deleted without further confirmation, even those not created by this import.', 'wp_all_import_plugin'), $cpt_name); ?></p>
        </div>
    </div>

    <div class="delete-missing-confirmation-modal">
        <div class="confirmation-modal-1">
            <p><?php printf(__(' When this import is re-run in the future, %s created or updated by this import and no longer present in the import file will be sent to the trash.', 'wp_all_import_plugin'), $cpt_name);?></p>
            <p><?php _e('We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin'); ?></p>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-1" style="width: 100%;"/>
            </div>
        </div>
        <div class="confirmation-modal-2">
            <p><?php printf(__(' When this import is re-run in the future, %s created or updated by this import and no longer present in the import file will be sent to <span class="status_of_removed">draft</span>.', 'wp_all_import_plugin'), $cpt_name);?></p>
            <p><?php _e('We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin'); ?></p>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-2" style="width: 100%;"/>
            </div>
        </div>
        <div class="confirmation-modal-3">
            <p><?php printf(__(' When this import is re-run in the future, %s created or updated by this import and no longer present in the import file will be deleted with no option for recovery.', 'wp_all_import_plugin'), $cpt_name); ?></p>
            <?php if ( $post_type !== 'taxonomies' && !in_array('is_send_removed_to_trash', $hidden_delete_missing_options) ): ?>
                <p><?php _e('Consider testing this import with removed records sent to the trash to make sure that the import is configured correctly and that the record matching settings are working as expected. We highly recommend running this import in staging first, and creating site backups before running in production.', 'wp_all_import_plugin');?></p>
            <?php else: ?>
                <p><?php _e('Consider testing this import by setting a custom field instead of deletion to make sure that the import is configured correctly and that the record matching settings are working as expected. We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin');?></p>
            <?php endif; ?>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-3" style="width: 100%;"/>
            </div>
        </div>
        <div class="confirmation-modal-4">
            <p><?php printf(__(' This import can affect all %s on this site, even those not created by this import. During import, all %s not present in this import file will be moved to the trash.', 'wp_all_import_plugin'), $cpt_name, $cpt_name); ?></p>
            <p><?php _e('We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin'); ?></p>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-4" style="width: 100%;"/>
            </div>
        </div>
        <div class="confirmation-modal-5">
            <p><?php printf(__(' This import can affect all %s on this site, even those not created by this import. During import, all %s not present in this import file will be moved to <span class="status_of_removed">draft</span>.', 'wp_all_import_plugin'), $cpt_name, $cpt_name); ?></p>
            <p><?php _e('We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin'); ?></p>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-5" style="width: 100%;"/>
            </div>
        </div>
        <div class="confirmation-modal-6">
            <p><?php printf(__(' This import will delete all %s on site, even those not created by this import. Every time this import is run, now and in the future, all %s not present in this import file will be deleted without further confirmation.', 'wp_all_import_plugin'), $cpt_name, $cpt_name); ?></p>
            <?php if ( $post_type !== 'taxonomies' && !in_array('is_send_removed_to_trash', $hidden_delete_missing_options) ): ?>
                <p><?php _e('Consider testing this import with removed records sent to the trash to make sure that the import is configured correctly and that the record matching settings are working as expected. We highly recommend running this import in staging first, and creating site backups before running in production.', 'wp_all_import_plugin');?></p>
            <?php else: ?>
                <p><?php _e('Consider testing this import by setting a custom field instead of deletion to make sure that the import is configured correctly and that the record matching settings are working as expected. We highly recommend running this import in a staging environment first and creating site backups before running in production.', 'wp_all_import_plugin');?></p>
            <?php endif; ?>
            <div class="input">
                <p><?php _e('Please type the text below to confirm import settings:');?></p>
                <p>I HAVE BACKUPS</p>
                <input type="text" id="confirm-settings-6" style="width: 100%;"/>
            </div>
        </div>
    </div>
</div>
