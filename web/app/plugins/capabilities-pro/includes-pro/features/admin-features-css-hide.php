<?php
namespace PublishPress\Capabilities;

class AdminFeaturesCssHide {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new AdminFeaturesCssHide();
        }

        return self::$instance;
    }

    function __construct() {
        //add hide css element to admin features element
        add_filter('pp_capabilities_admin_features_elements', [$this, 'cssHideElements'], 50);
        //add hide css section icon
        add_filter('pp_capabilities_admin_features_icons', [$this, 'cssHideSectionIcon']);
        //add hide css section title
        add_filter('pp_capabilities_admin_features_titles', [$this, 'cssHideSectionTitle']);
        //add hide css action
        add_filter('pp_capabilities_admin_features_actions', [$this, 'cssHideSectionAction']);
        //add form css hide element to admin features table bottom
        add_action('pp_capabilities_admin_features_hidecsselement_before_subsection_tr', [$this, 'cssHideAddForm']);
        //ajax handler for hide css new entry submission
        add_action('wp_ajax_ppc_submit_feature_css_hide_by_ajax', [$this, 'cssHideNewEntryAjaxHandler']);
        //ajax handler for deleting css hide item
        add_action('wp_ajax_ppc_delete_feature_css_hide_item_by_ajax', [$this, 'cssHideDeleteItemAjaxHandler']);
        //Add hidden css element styles to admin pages
        add_action('ppc_admin_feature_restriction', [$this, 'cssHideAddStyles']);
    }

    /**
     * Fetch admin features css hide options.
     *
     * @return mixed
     *
     * @since 2.3.1
     */
    public static function getData()
    {
        $data = (array)get_option('ppc_admin_feature_css_hide_custom_data');
        $data = array_filter($data);

        if (empty($data) && empty(get_option('ppc_admin_feature_css_hide_demo_installed'))) {
            $data = [];
            //add demo data 1
            $element_id = uniqid(true) . 11;
            $data[$element_id]= ['label' => __('Hide Hello Dolly from plugin list', 'capabilities-pro'), 'elements' => 'body.plugins-php table.plugins tr[data-slug=hello-dolly]'];
            //add demo data 2
            $element_id = uniqid(true) . 22;
            $data[$element_id]= ['label' => __('Hide plugin row action links (Deactivate, Activate, Delete etc buttons)', 'capabilities-pro'), 'elements' => 'body.plugins-php table.plugins .row-actions'];
            //add demo data 3
            $element_id = uniqid(true) . 33;
            $data[$element_id]= ['label' => __('Hide Add New Post button from post screen', 'capabilities-pro'), 'elements' => 'body.post-type-post a.page-title-action'];
            //add demo data 4
            $element_id = uniqid(true) . 44;
            $data[$element_id]= ['label' => __('Hide plugin row Deactivate button from all plugin lists', 'capabilities-pro'), 'elements' => 'body.plugins-php table.plugins .row-actions .deactivate'];
            //add demo data 5
            $element_id = uniqid(true) . 55;
            $data[$element_id]= ['label' => __('Hide plugin row Delete button from all plugin lists', 'capabilities-pro'), 'elements' => 'body.plugins-php table.plugins .row-actions .delete'];
            update_option('ppc_admin_feature_css_hide_custom_data', $data);
            update_option('ppc_admin_feature_css_hide_demo_installed', 1);
        }

        return $data;
    }


    /**
     * Add hide css section icon
     *
     * @param array $icons admin features screen elements
     *
     * @return array $icons updated icon list
     *
     * @since 2.3.1
     */
    function cssHideSectionIcon($icons) {

        $icons['hidecsselement']     = 'hidden';

        return $icons;
    }


    /**
     * Add hide css section title
     *
     * @param array $icons admin features screen elements
     *
     * @return array $icons updated icon list
     *
     * @since 2.3.1
     */
    function cssHideSectionTitle($titles) {

        $titles['Hide CSS Element'] = esc_html__('Hide CSS Elements', 'capabilities-pro');

        return $titles;
    }


    /**
     * Add hide css action
     *
     * @param array $actions
     *
     * @return array $actions
     *
     * @since 2.18.2
     */
    function cssHideSectionAction($titles) {

        $titles['Hide CSS Element'] = 'ppc_hidden_css';

        return $titles;
    }


    /**
     * Add hide css element to admin features element
     *
     * @param array $element admin features screen elements
     *
     * @return array
     *
     * @since 2.3.1
     */
    function cssHideElements($elements) {
        $data = self::getData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $target_urls = isset($restrict_data['target_urls']) ? (string) $restrict_data['target_urls'] : '';
                $url_wildcard = self::isUrlWildcardEnabled($restrict_data) ? 1 : 0;

                $added_element[$name] = [
                    'label'          => $restrict_data['label'],
                    'action'         => 'ppc_hidden_css',
                    'elements'       => $restrict_data['elements'],
                    'target_urls'    => $target_urls,
                    'url_wildcard'   => $url_wildcard,
                    'custom_element' => true,
                    'button_class'   => 'ppc-custom-features-css-delete red-pointer',
                    'edit_class'     => 'ppc-custom-features-css-edit',
                    'button_data_id' => $name,
                    'element_label'  => $restrict_data['label'],
                    'element_items'  => $restrict_data['elements'],
                ];
            }
        }

        $elements['Hide CSS Element'] = $added_element;

        return $elements;
    }



    /**
     * Add form css hide element to admin features table bottom
     *
     * @since 2.3.1
     */
    public function cssHideAddForm() {
        ?>
        <tr class="ppc-menu-row child-menu hidecsselement">
            <td colspan="2" class="form-td">
                <table class="editor-features-custom admin-features-css-hide-form">
                <tr class="ppc-menu-row parent-menu ppc-add-custom-row-header">
                    <td>
                        <p class="cme-subtext"><?php esc_html_e('You can remove other elements from admin area by adding their IDs or classes below:', 'capabilities-pro');?>
                        </p>
                        <p class="editing-custom-item hidecsselement">
                            <strong><?php esc_html_e('Editing:', 'capabilities-pro'); ?></strong>
                            <span class="title"></span>
                        </p>
                        </h4>
                    </td>
                </tr>

                <tr class="ppc-add-custom-row-body">
                    <td>
                        <table class="wp-list-table widefat fixed ppc-custom-features-table hidecsselement">

                        <tr class="field-row">
                            <th scope="row">
                                <?php esc_html_e('Title', 'capabilities-pro'); ?>: <font color="red">*</font>
                            </th>
                            <td>
                                <input class="form-label ppc-feature-css-hide-new-name" type="text"/><br>
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
                                <textarea class="form-element ppc-feature-css-hide-new-element"></textarea><br>
                                <span class="description">
                                    <?php esc_html_e('IDs or classes to hide. Separate multiple values by comma (.custom-item-one, .custom-item-two, #new-item-id).', 'capabilities-pro'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="field-row">
                            <th scope="row">
                                <?php esc_html_e('Target URLs', 'capabilities-pro'); ?>:
                            </th>
                            <td>
                                <textarea class="ppc-feature-css-hide-target-urls"></textarea><br>
                                <span class="description">
                                    <?php esc_html_e('Optional. Restrict this CSS hide rule to specific admin URLs. Separate multiple URLs by comma.', 'capabilities-pro'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="field-row">
                            <th scope="row">
                                <?php esc_html_e('Wildcard URL Match', 'capabilities-pro'); ?>:
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" class="ppc-feature-css-hide-url-wildcard-enabled" value="1" />
                                    <?php esc_html_e('Enable wildcard prefix matching for Target URLs.', 'capabilities-pro'); ?>
                                </label>
                                <br>
                                <span class="description">
                                    <?php esc_html_e('When enabled, this rule applies if current URL starts with any Target URL value.', 'capabilities-pro'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="field-row">
                            <td colspan="2">
                                <input type="hidden" class="custom-edit-id" value="">
                                <input class="ppc-feature-submit-form-nonce" type="hidden"
                                    value="<?php echo esc_attr(wp_create_nonce('ppc-custom-feature-nonce')); ?>"/>
                                <div class="custom-item-submit-buttons">
                                    <div class="cancel-custom-features-item-edit button button-secondary" data-section="hidecsselement">
                                        <?php esc_html_e('Cancel Edit', 'capabilities-pro'); ?>
                                    </div>

                                    <button type="button" class="submit-button ppc-feature-css-hide-new-submit button button-secondary
                                    data-add="<?php esc_html_e('Add New', 'capabilities-pro'); ?>" data-edit="<?php esc_html_e('Save Edit', 'capabilities-pro'); ?>">
                                        <span class="ppc-feature-post-loader spinner"></span>
                                        <?php esc_html_e('Add New', 'capabilities-pro'); ?>
                                    </button>
                                </div>
                                <div class="ppc-post-features-note"></div>

                        </td></tr>
                        </table>

                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <?php
    }

    /**
     * Ajax handler for hide css new entry submission
     *
     * @since 3.3.1
     */
    public static function cssHideNewEntryAjaxHandler()
    {
        $response['status']  = 'error';
        $response['message'] = esc_html__('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_features')) {
            $response['message'] = esc_html__('You do not have permission to manage admin feature restrictions.', 'capabilities-pro');
            wp_send_json($response);
        }

        $custom_label   = isset($_POST['custom_label']) ? sanitize_text_field($_POST['custom_label']) : '';
        $custom_element = isset($_POST['custom_element']) ? stripslashes_deep(sanitize_textarea_field($_POST['custom_element'])) : '';
        $target_urls    = isset($_POST['target_urls']) ? sanitize_textarea_field($_POST['target_urls']) : '';
        $url_wildcard   = isset($_POST['url_wildcard']) ? (int) sanitize_key($_POST['url_wildcard']) : 0;
        $security       = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';
        $item_id        = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = esc_html__('Invalid action. Reload this page and try again.', 'capabilities-pro');
        } elseif (empty(trim($custom_label)) || empty(trim($custom_element))) {
            $response['message'] = esc_html__('All fields are required.', 'capabilities-pro');
        } else {
            $element_id       = (!empty($item_id)) ? $item_id : uniqid(true);
            $data             = self::getData();
            $data[$element_id]= [
                'label'        => $custom_label,
                'elements'     => $custom_element,
                'target_urls'  => $target_urls,
                'url_wildcard' => (int) !empty($url_wildcard),
            ];
            update_option('ppc_admin_feature_css_hide_custom_data', $data);


            $response['message'] = (!empty($item_id)) ? esc_html__('CSS element updated successfully.', 'capabilities-pro') : esc_html__('New CSS element added successfully.', 'capabilities-pro');
            $response['status']  = 'success';

            $checked = (!empty($item_id)) ? 'checked' : '';
            $url_target_badges = '';
            if (!empty(trim($target_urls))) {
                $url_target_badges .= ' <span class="ppc-url-target-badge">' . esc_html__('URL targeted', 'capabilities-pro') . '</span>';
                if (!empty($url_wildcard)) {
                    $url_target_badges .= ' <span class="ppc-wildcard-badge">' . esc_html__('URL wildcard', 'capabilities-pro') . '</span>';
                }
            }

            $response_content    = '<tr class="ppc-menu-row child-menu ppc-menu-overlay-item hidecsselement custom-item-'. $element_id .'">

                <td class="restrict-column ppc-menu-checkbox">
                    <input id="check-item-'. esc_attr($element_id) .'" class="check-item" type="checkbox" name="capsman_disabled_admin_features[]" '. $checked .' value="ppc_hidden_css||' . esc_attr($element_id) . '">
                </td>

                <td class="menu-column ppc-menu-item custom-item-row ppc-flex">
                    <div class="ppc-flex-item">
                        <div>
                            <label for="check-item-'. $element_id .'">
                                <span class="menu-item-link">
                                <strong><i class="dashicons dashicons-arrow-right"></i> ' . $custom_label . $url_target_badges . ' </strong></span>
                            </label>
                        </div>
                        <div class="custom-item-output">
                            <div class="custom-item-display">
                                ' . $custom_element . '
                            </div>
                        </div>
                    </div>
                    <div class="ppc-flex-item">
                        <div class="button view-custom-item">' . esc_html__('View', 'capabilities-pro') .'</div>
                        <div class="button edit-features-custom-item"
                        data-section="hidecsselement"
                        data-label="'. esc_attr($custom_label) .'"
                        data-element="'. esc_attr($custom_element) .'"
                        data-target-urls="'. esc_attr($target_urls) .'"
                        data-url-wildcard="'. esc_attr((int) !empty($url_wildcard)) .'"
                        data-id="'. esc_attr($element_id) .'">
                        '. esc_html__('Edit', 'capabilities-pro') .'
                        </div>
                            <div
                                class="button ppc-custom-features-css-delete red-pointer feature-red"
                                data-id="'. $element_id .'">
                                '. esc_html__('Delete', 'capabilities-pro') .'
                            </div>
                        </div>
                    </div>
                </td>
            </tr>';

            $response['content'] = $response_content;
        }

        wp_send_json($response);
    }

    /**
     * Ajax handler for deleting css hide item.
     *
     * @since 2.1.1
     */
    public static function cssHideDeleteItemAjaxHandler()
    {
        $response = [];
        $response['status']  = 'error';
        $response['message'] = esc_html__('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_features')) {
            $response['message'] = esc_html__('You do not have permission to manage admin feature restrictions.', 'capabilities-pro');
            wp_send_json($response);
        }

        $delete_id     = isset($_POST['delete_id']) ? sanitize_key($_POST['delete_id']) : '';
        $security      = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';

        if (!wp_verify_nonce($security, 'ppc-custom-feature-nonce')) {
            $response['message'] = esc_html__('Invalid action. Reload this page and try again.', 'capabilities-pro');
        } elseif (empty(trim($delete_id))) {
            $response['message'] = esc_html__('Invalid request!.', 'capabilities-pro');
        } else {
             // Delete from custom data
            $data = self::getData();
            if (array_key_exists($delete_id, $data)) {
                unset($data[$delete_id]);
                update_option('ppc_admin_feature_css_hide_custom_data', $data);
            }

            // Clean up role based restrictions
            $disabled_admin_items = get_option('capsman_disabled_admin_features', []);
            if (!empty($disabled_admin_items) && is_array($disabled_admin_items)) {
                foreach ($disabled_admin_items as $role => $items) {
                    if (!empty($items) && is_array($items)) {
                        // Remove any items matching the deleted ID
                        $filtered_items = array_filter($items, function($item) use ($delete_id) {
                            return strpos($item, 'ppc_hidden_css||' . $delete_id) === false;
                        });
                        $disabled_admin_items[$role] = array_values($filtered_items);
                    }
                }
                update_option('capsman_disabled_admin_features', $disabled_admin_items, false);
            }

            $response['status']  = 'success';
            $response['message'] = esc_html__('Selected item deleted successfully', 'capabilities-pro');
        }

        wp_send_json($response);
    }

    /**
     * Add hidden css element styles to admin pages
     *
     * @param array $disabled_elements
     *
     * @since 3.3.1
     */
    public static function cssHideAddStyles($disabled_elements)
    {
        global $css_hidden_element;

        if (empty($css_hidden_element)) {
            $css_hidden_element = [];
        }

        if(!is_admin()){//this feature block is only restricted to admin area
            return;
        }

        //get element related to css hide alone
        $data_key = 'ppc_hidden_css';
        $ppc_hidden_css    = array_filter(
			$disabled_elements,
			function($value, $key) use ($data_key) {return strpos($value, $data_key) === 0;}, ARRAY_FILTER_USE_BOTH
		);

        if(count($ppc_hidden_css) > 0){
            $data = self::getData();
            $current_page_url = self::currentPageUrl();
            foreach($ppc_hidden_css as $blocked_element){
                $blocked_element = str_replace($data_key.'||', '', $blocked_element);
                if (array_key_exists($blocked_element, $data)) {
                    if (!self::isRuleApplicableToCurrentUrl($data[$blocked_element], $current_page_url)) {
                        continue;
                    }

                    $css_elements = explode (",", $data[$blocked_element]['elements']);
                    $multiple_elements = [];
                    foreach ($css_elements as $css_element) {
                        //check if it's a plugin element
                        if (count(explode('/', $css_element)) === 2) {
                            $multiple_elements[] = '[data-plugin="'. trim($css_element) .'"]';
                        } else {
                            $multiple_elements[] = trim($css_element);
                        }
                    }
                    $css_hidden_element[] = $multiple_elements;//merge multiple element into array
                }
            }
            //merge all array into one
            if ($css_hidden_element) {
                $css_hidden_element = call_user_func_array('array_merge', $css_hidden_element);
            }
            ppc_add_inline_style('' . implode(',', $css_hidden_element) . ' {display:none !important;}');
        }
    }

    /**
     * Check if URL wildcard is explicitly enabled for rule.
     *
     * @param array $rule_data
     *
     * @return bool
     */
    public static function isUrlWildcardEnabled($rule_data)
    {
        if (!is_array($rule_data) || !array_key_exists('url_wildcard', $rule_data)) {
            return false;
        }

        return in_array($rule_data['url_wildcard'], [1, '1', true], true);
    }

    /**
     * Determine if rule should be applied on current URL.
     *
     * @param array  $rule_data
     * @param string $current_url
     *
     * @return bool
     */
    public static function isRuleApplicableToCurrentUrl($rule_data, $current_url)
    {
        if (!is_array($rule_data)) {
            return true;
        }

        $target_urls = isset($rule_data['target_urls']) ? trim((string) $rule_data['target_urls']) : '';
        if ($target_urls === '') {
            return true;
        }

        $use_wildcard = self::isUrlWildcardEnabled($rule_data);
        $target_url_items = array_filter(array_map('trim', explode(',', $target_urls)));

        if (empty($target_url_items)) {
            return true;
        }

        foreach ($target_url_items as $target_url) {
            if ($use_wildcard) {
                if (self::isWildcardUrlMatch($current_url, $target_url)) {
                    return true;
                }

                continue;
            }

            if (self::isExactUrlMatch($current_url, $target_url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare URL with wildcard support.
     *
     * @param string $current_url
     * @param string $target_url
     *
     * @return bool
     */
    public static function isWildcardUrlMatch($current_url, $target_url)
    {
        $target_url = trim((string) $target_url);
        if (empty($target_url)) {
            return false;
        }

        $target_parts = parse_url($target_url);
        if ($target_parts === false) {
            return false;
        }

        $compare_with_host = isset($target_parts['host']);

        $current_value = self::normalizeUrlForWildcardComparison($current_url, $compare_with_host);
        $target_value = self::normalizeUrlForWildcardComparison($target_url, $compare_with_host);

        if (empty($current_value) || empty($target_value)) {
            return false;
        }

        return strpos($current_value, $target_value) === 0;
    }

    /**
     * Compare URL with strict exact matching.
     *
     * @param string $current_url
     * @param string $target_url
     *
     * @return bool
     */
    public static function isExactUrlMatch($current_url, $target_url)
    {
        $target_url = trim((string) $target_url);
        if (empty($target_url)) {
            return false;
        }

        $target_parts = parse_url($target_url);
        if ($target_parts === false) {
            return false;
        }

        $compare_with_host = isset($target_parts['host']);

        $current_value = self::normalizeUrlForExactComparison($current_url, $compare_with_host);
        $target_value = self::normalizeUrlForExactComparison($target_url, $compare_with_host);

        if (empty($current_value) || empty($target_value)) {
            return false;
        }

        return $current_value === $target_value;
    }

    /**
     * Normalize URL for wildcard comparison.
     *
     * @param string $url
     * @param bool   $with_host
     *
     * @return string
     */
    public static function normalizeUrlForWildcardComparison($url, $with_host = false)
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }

        $normalized = '';

        if ($with_host && isset($parts['host'])) {
            $normalized .= strtolower($parts['host']);
        }

        if (isset($parts['path'])) {
            $normalized .= $parts['path'];
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $normalized .= '?' . $parts['query'];
        }

        return $normalized;
    }

    /**
     * Normalize URL for exact matching comparison.
     *
     * @param string $url
     * @param bool   $with_host
     *
     * @return string
     */
    public static function normalizeUrlForExactComparison($url, $with_host = false)
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }

        $normalized = '';

        if ($with_host && isset($parts['host'])) {
            $normalized .= strtolower($parts['host']);
        }

        $normalized .= isset($parts['path']) ? $parts['path'] : '';

        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $query_params);
            if (is_array($query_params) && !empty($query_params)) {
                ksort($query_params);
                $normalized_query = http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
            } else {
                $normalized_query = trim($parts['query']);
            }

            if ($normalized_query !== '') {
                $normalized .= '?' . $normalized_query;
            }
        }

        return $normalized;
    }

    /**
     * Retrieve current page URL.
     *
     * @return string
     */
    public static function currentPageUrl()
    {
        if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
            return esc_url_raw((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }

        return admin_url('');
    }

}
