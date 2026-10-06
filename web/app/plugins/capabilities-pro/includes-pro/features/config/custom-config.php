<?php
namespace PublishPress\Capabilities;

class EditorFeaturesCustomConfig {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new EditorFeaturesCustomConfig();
        }

        return self::$instance;
    }

    function __construct() {
        require_once (dirname(PUBLISHPRESS_CAPS_PRO_FILE) . '/includes-pro/features/custom.php');
        EditorFeaturesCustom::instance();

        add_action('pp_capabilities_features_classic_after_table', [$this, 'customAddFormClassic']);
        add_action('pp_capabilities_features_gutenberg_after_table', [$this, 'customAddForm']);
    }

    public function customAddFormClassic() {
        ?>
        <table class="editor-features-custom ppc-custom-item-parent-table editor-features-classic-custom" <?php if (empty($_REQUEST['ppc-tab']) || ('classic' != $_REQUEST['ppc-tab'])):?>style="display:none"<?php endif;?>>

        <tr class="ppc-menu-row parent-menu">
            <td colspan="2">
                <h4 class="ppc-menu-row-section"> <?php esc_html_e('Add New Classic Editor Restrictions', 'capabilities-pro'); ?>
                </h4>
            </td>
        </tr>
        <tr class="ppc-menu-row parent-menu ppc-add-custom-row-header">
            <td>
                <p class="cme-subtext"><?php _e('You can remove other elements from the editor screen by adding their IDs or classes below:', 'capabilities-pro');?>
                </p>
                <p class="editing-custom-item customclassiceditorrestrictions">
                    <strong><?php esc_html_e('Editing:', 'capabilities-pro'); ?></strong>
                    <span class="title"></span>
                </p>
            </td>
        </tr>

        <tr class="ppc-add-custom-row-body">
            <td>
                <table class="wp-list-table widefat fixed ppc-custom-features-table customclassiceditorrestrictions">

                <tr class="field-row">
                    <th scope="row">
                        <?php esc_html_e('Title', 'capabilities-pro'); ?>: <font color="red">*</font>
                    </th>
                    <td>
                        <input class="form-label ppc-feature-classic-new-name" type="text"><br>
                        <span class="description">
                            <?php esc_html_e('Enter the name/label to identify the custom element on this screen.', 'capabilities-pro'); ?>
                        </span>
                    </td>
                </tr>

                <tr class="field-row">
                    <th scope="row">
                        <?php esc_html_e('Element IDs or Classes', 'capabilities-pro'); ?>: <font color="red">*</font>
                    </th>
                    <td>
                        <textarea class="form-element ppc-feature-classic-new-ids"></textarea><br>
                        <span class="description">
                            <?php esc_html_e('IDs or classes to hide. Separate multiple values by comma (.custom-item-one, .custom-item-two, #new-item-id).', 'capabilities-pro'); ?>
                        </span>
                    </td>
                </tr>

                <tr class="field-row">
                    <td colspan="2">
                        <input type="hidden" class="custom-edit-id" value="">
                        <input class="ppc-feature-submit-form-nonce" type="hidden"
                            value="<?php echo esc_attr(wp_create_nonce('ppc-custom-feature-nonce')); ?>"/>
                        <div class="custom-item-submit-buttons">
                            <div class="cancel-custom-features-item-edit button button-secondary" data-section="customclassiceditorrestrictions">
                                <?php esc_html_e('Cancel Edit', 'capabilities-pro'); ?>
                            </div>

                            <button type="button" class="submit-button ppc-feature-classic-new-submit button button-secondary" data-add="<?php esc_html_e('Add New', 'capabilities-pro'); ?>" data-edit="<?php esc_html_e('Save Edit', 'capabilities-pro'); ?>">
                                <?php esc_html_e('Add New', 'capabilities-pro'); ?>
                            </button>
                        </div>
                        <span class="ppc-feature-post-loader spinner"></span>
                        <div class="ppc-post-features-note"></div>

                </td></tr>
                </table>
            </td>
        </tr>

        </table>
        <?php
    }

    public function customAddForm() {
        ?>
        <table class="editor-features-custom ppc-custom-item-parent-table editor-features-gutenberg-custom" <?php if (!empty($_REQUEST['ppc-tab']) && ('gutenberg' != $_REQUEST['ppc-tab'])):?>style="display:none"<?php endif;?>>

        <tr class="ppc-menu-row parent-menu">
            <td colspan="2">
                <h4 class="ppc-menu-row-section"> <?php esc_html_e('Add New Gutenberg Restrictions', 'capabilities-pro'); ?>
                </h4>
            </td>
        </tr>
        <tr class="ppc-menu-row parent-menu ppc-add-custom-row-header">
            <td>
                <p class="cme-subtext"><?php _e('You can remove other elements from the editor screen by adding their IDs or classes below:', 'capabilities-pro');?>
                <p class="editing-custom-item customgutenbergrestrictions">
                    <strong><?php esc_html_e('Editing:', 'capabilities-pro'); ?></strong>
                    <span class="title"></span>
                </p>
                </p>
            </td>
        </tr>

        <tr class="ppc-add-custom-row-body">
            <td>
                <table class="wp-list-table widefat fixed ppc-custom-features-table customgutenbergrestrictions">

                    <tr class="field-row">
                        <th scope="row">
                            <?php esc_html_e('Title', 'capabilities-pro'); ?>: <font color="red">*</font>
                        </th>
                        <td>
                            <input class="form-label ppc-feature-gutenberg-new-name" type="text"><br>
                            <span class="description">
                                <?php esc_html_e('Enter the name/label to identify the custom element on this screen.', 'capabilities-pro'); ?> </span>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <th scope="row">
                            <?php esc_html_e('Element IDs or Classes', 'capabilities-pro'); ?>: <font color="red">*</font>
                        </th>
                        <td>
                            <textarea class="form-element ppc-feature-gutenberg-new-ids"></textarea><br>
                            <span class="description">
                                <?php esc_html_e('IDs or classes to hide. Separate multiple values by comma (.custom-item-one, .custom-item-two, #new-item-id).', 'capabilities-pro'); ?>
                            </span>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <td colspan="2">
                            <input type="hidden" class="custom-edit-id" value="">
                            <input class="ppc-feature-submit-form-nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('ppc-custom-feature-nonce')); ?>" />
                            <div class="custom-item-submit-buttons">
                                <div class="cancel-custom-features-item-edit button button-secondary" data-section="customgutenbergrestrictions">
                                    <?php esc_html_e('Cancel Edit', 'capabilities-pro'); ?>
                                </div>

                                <button type="button" class="submit-button ppc-feature-gutenberg-new-submit button button-secondary" data-add="<?php esc_html_e('Add New', 'capabilities-pro'); ?>" data-edit="<?php esc_html_e('Save Edit', 'capabilities-pro'); ?>">
                                    <?php esc_html_e('Add New', 'capabilities-pro'); ?>
                                </button>
                            </div>
                            <span class="ppc-feature-post-loader spinner"></span>
                            <div class="ppc-post-features-note"></div>

                    </td></tr>
                </table>

            </td>
        </tr>
        </table>
        <?php
    }

    /**
     * Submit new item for editor feature ajax callback.
     *
     * @since 2.1.1
     */
    public static function addByAjax()
    {
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_editor_features')) {
            $response['message'] = __('You do not have permission to manage editor feature restrictions.', 'capabilities-pro');
            wp_send_json($response);
        }

        $def_post_types = apply_filters('pp_capabilities_feature_post_types', ['post', 'page']);

        $custom_label   = isset($_POST['custom_label']) ? sanitize_text_field($_POST['custom_label']) : '';
        $custom_element = isset($_POST['custom_element']) ? stripslashes_deep(sanitize_textarea_field($_POST['custom_element'])) : '';
        $action         = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
        $security       = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';
        $item_id        = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = __('Invalid action. Reload this page and try again if occured in error.', 'capabilities-pro');
        } elseif (empty(trim($custom_label)) || empty(trim($custom_element))) {
            $response['message'] = __('All fields are required.', 'capabilities-pro');
        } else {
            $element_id            = (!empty($item_id)) ? $item_id : uniqid(true);
            if ($action === 'ppc_submit_feature_gutenberg_by_ajax') {
                $data_parent       = 'gutenberg';
                $data_name_prefix  = 'capsman_feature_restrict_';
                $data              = EditorFeaturesCustom::getData();
                $section            = 'customgutenbergrestrictions';
                $data[$element_id] = ['label' => $custom_label, 'elements' => $custom_element];
                update_option('ppc_feature_post_gutenberg_custom_data', $data);
            } elseif ($action === 'ppc_submit_feature_classic_by_ajax') {
                $data_parent       = 'classic';
                $data_name_prefix  = 'capsman_feature_restrict_classic_';
                $section           = 'customclassiceditorrestrictions';
                $data              = EditorFeaturesCustom::getClassicData();
                $data[$element_id] = ['label' => $custom_label, 'elements' => $custom_element];
                update_option('ppc_feature_post_classic_custom_data', $data);
            }

            if (!empty($action)) {
                $response['message'] = (!empty($item_id)) ? __('Custom item updated successfully. Save changes to apply restrictions.', 'capabilities-pro') : __('New custom item added. Save changes to apply restrictions.', 'capabilities-pro');
                $response['status']  = 'success';
                $response_content    = [
                    'custom_label'      => $custom_label,
                    'custom_element'    => $custom_element,
                    'element_id'        => $element_id,
                    'data_parent'       => $data_parent,
                    'section'           => $section,
                    'view_text'         => esc_html__('View'),
                    'edit_text'         => esc_html__('Edit'),
                    'delete_text'       => esc_html__('Delete'),
                    'data_name_prefix'   => $data_name_prefix
                ];

                $response['content'] = $response_content;
            }
        }

        wp_send_json($response);
    }

    /**
     * Delete custom added post features item ajax callback.
     *
     * @since 2.1.1
     */
    public static function deleteByAjax()
    {
        $response = [];
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_editor_features')) {
            $response['message'] = __('You do not have permission to manage editor feature restrictions.', 'capabilities-pro');
            wp_send_json($response);
        }

        $delete_id     = isset($_POST['delete_id']) ? sanitize_key($_POST['delete_id']) : '';
        $delete_parent = isset($_POST['delete_parent']) ? sanitize_key($_POST['delete_parent']) : '';
        $security      = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = __('Invalid action. Reload this page and try again if occured in error.',
                'capabilities-pro');
        } elseif (empty(trim($delete_id)) || empty(trim($delete_parent))) {
            $response['message'] = __('Invalid request!.', 'capabilities-pro');
        } else {

            if ($delete_parent === 'gutenberg') {
                $data = EditorFeaturesCustom::getData();
                if (array_key_exists($delete_id, $data)) {
                    unset($data[$delete_id]);
                    update_option('ppc_feature_post_gutenberg_custom_data', $data);
                }
            } elseif ($delete_parent === 'classic') {
                $data = EditorFeaturesCustom::getClassicData();
                if (array_key_exists($delete_id, $data)) {
                    unset($data[$delete_id]);
                    update_option('ppc_feature_post_classic_custom_data', $data);
                }
            }

            if (!empty($delete_parent)) {
                $response['status']  = 'success';
                $response['message'] = __('Selected item deleted successfully', 'capabilities-pro');
            }
        }

        wp_send_json($response);
    }
}
