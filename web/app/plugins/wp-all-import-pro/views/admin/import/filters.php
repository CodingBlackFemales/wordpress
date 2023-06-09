<div class="wpallimport-collapsed closed">
    <div class="wpallimport-content-section">
        <div class="wpallimport-collapsed-header">
            <h3><?php _e('Manage Filtering Options', 'wp_all_import_plugin'); ?></h3>
        </div>
        <div class="wpallimport-collapsed-content">
            <div>
                <div class="rule_inputs">
                    <table style="width:100%;">
                        <tr>
                            <th><?php _e('Element', 'wp_all_import_plugin'); ?></th>
                            <th><?php _e('Rule', 'wp_all_import_plugin'); ?></th>
                            <th><?php _e('Value', 'wp_all_import_plugin'); ?></th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <td style="width:25%;">
                                <select id="pmxi_xml_element">
                                    <option value=""><?php _e('Select Element', 'wp_all_import_plugin'); ?></option>
                                    <?php if ($elements && $elements->length) PMXI_Render::render_xml_elements_for_filtring($elements->item(0)); ?>
                                </select>
                            </td>
                            <td style="width:25%;">
                                <select id="pmxi_rule">
                                    <option value=""><?php _e('Select Rule', 'wp_all_import_plugin'); ?></option>
                                    <option value="equals"><?php _e('equals', 'wp_all_import_plugin'); ?></option>
                                    <option value="not_equals"><?php _e('not equals', 'wp_all_import_plugin'); ?></option>
                                    <option value="greater"><?php _e('greater than', 'wp_all_import_plugin');?></option>
                                    <option value="equals_or_greater"><?php _e('equals or greater than', 'wp_all_import_plugin'); ?></option>
                                    <option value="less"><?php _e('less than', 'wp_all_import_plugin'); ?></option>
                                    <option value="equals_or_less"><?php _e('equals or less than', 'wp_all_import_plugin'); ?></option>
                                    <option value="contains"><?php _e('contains', 'wp_all_import_plugin'); ?></option>
                                    <option value="not_contains"><?php _e('not contains', 'wp_all_import_plugin'); ?></option>
                                    <option value="is_empty"><?php _e('is empty', 'wp_all_import_plugin'); ?></option>
                                    <option value="is_not_empty"><?php _e('is not empty', 'wp_all_import_plugin'); ?></option>
                                </select>
                            </td>
                            <td style="width:25%;">
                                <input id="pmxi_value" type="text" placeholder="value" value=""/>
                            </td>
                            <td style="width:15%;">
                                <a id="pmxi_add_rule" href="javascript:void(0);"><?php _e('Add Rule', 'wp_all_import_plugin');?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="clear"></div>
            <table class="xpath_filtering">
                <tr>
                    <td style="width:5%; font-weight:bold; color: #000;"><?php _e('XPath','wp_all_import_plugin');?></td>
                    <td style="width:95%;">
                        <input type="text" name="xpath" value="<?php echo esc_attr($post['xpath']) ?>" style="max-width:none;" />
                        <input type="hidden" id="root_element" name="root_element" value="<?php echo PMXI_Plugin::$session->source['root_element']; ?>"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="is_csv" value="<?php echo empty($is_csv) ? '' : $is_csv;?>" />
            <div id="wpallimport-filters" class="wpallimport-collapsed-content" style="padding:0; <?php if (!empty($post['filters_output'])):?>display: block;<?php endif; ?>">
                <table style="width: 100%; font-weight: bold; padding: 20px 20px 0 20px;">
                    <tr>
                        <td style="width: 30%; padding-left: 30px;"><?php _e('Element', 'wp_all_import_plugin'); ?></td>
                        <td style="width:20%;"><?php _e('Rule', 'wp_all_import_plugin'); ?></td>
                        <td style="width:20%;"><?php _e('Value', 'wp_all_import_plugin'); ?></td>
                        <td style="width:25%;"><?php _e('Condition', 'wp_all_import_plugin'); ?></td>
                    </tr>
                </table>
                <div class="wpallimport-content-section">
                    <fieldset id="filtering_rules">
                        <p style="margin:20px 0 5px; text-align:center; <?php if (!empty($post['filters_output'])):?>display: none;<?php endif; ?>"><?php _e('No filtering options. Add filtering options to only import records matching some specified criteria.', 'wp_all_import_plugin');?></p>
                        <ol class="filtering_rules"><?php if (!empty($post['filters_output'])):?><?php echo json_decode($post['filters_output']);?><?php endif; ?></ol>
                        <div class="clear"></div>
                        <a href="javascript:void(0);" id="apply_filters" <?php if (empty($post['filters_output'])):?>style="display:none;"<?php endif; ?>><?php _e('Apply Filters To XPath', 'wp_all_import_plugin');?></a>
                        <input type="hidden" class="filtering-output" name="filters_output" value="<?php echo esc_attr($post['filters_output'] ?? ''); ?>"/>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</div>
