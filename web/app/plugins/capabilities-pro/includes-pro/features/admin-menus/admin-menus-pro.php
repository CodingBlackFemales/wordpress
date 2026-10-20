<?php
namespace PublishPress\Capabilities;

/** New menu creation extends the shared Free screen. @since 2.53.0 */
class AdminMenusPro
{
    public function __construct()
    {
        add_filter('pp_capabilities_admin_menus_new_menu_enabled', '__return_true');
        add_action('pp_capabilities_admin_menus_enqueue_scripts', [$this, 'enqueueScripts']);
        if (is_admin() && pp_capabilities_feature_enabled('admin-menus')) {
            add_action('wp_ajax_ppc_add_new_admin_menu', [$this, 'ajaxAddNewAdminMenu']);
        }
    }

    public function enqueueScripts()
    {
        wp_enqueue_script('pp-capabilities-pro-new-admin-menu',
            plugins_url('', PUBLISHPRESS_CAPS_PRO_FILE) . '/includes-pro/features/admin-menus/assets/js/admin-menus-pro.js',
            ['pp-capabilities-pro-admin-menu-js'], PUBLISHPRESS_CAPS_VERSION, true);
    }

    public function ajaxAddNewAdminMenu() {

        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capabilities-pro');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capabilities-pro');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capabilities-pro');
        } else {
            $form_menu_type      = !empty($_POST['form_menu_type']) ? sanitize_text_field($_POST['form_menu_type']) : '';
            $menu_type   = !empty($_POST['menu_type']) ? sanitize_text_field($_POST['menu_type']) : '';
            $menu_position = !empty($_POST['menu_position']) ? sanitize_text_field($_POST['menu_position']) : 0;
            $menu_position_after    = !empty($_POST['menu_position_after']) ? sanitize_text_field($_POST['menu_position_after']) : '';
            $menu_parent    = !empty($_POST['menu_parent']) ? sanitize_text_field($_POST['menu_parent']) : '';
            $menu_data        = !empty($_POST['menu_data']) ? map_deep($_POST['menu_data'], 'sanitize_text_field') : [];
            $menu_order        = !empty($_POST['menu_order']) ? map_deep($_POST['menu_order'], 'sanitize_text_field') : [];
            $separator_color   = !empty($_POST['separator_color']) ? sanitize_hex_color(wp_unslash($_POST['separator_color'])) : '';

            $custom_admin_menus = !empty(get_option('ppc_admin_menus_custom_menus')) ? (array) get_option('ppc_admin_menus_custom_menus', []) : [];

            $new_menu = [
                'form_menu_type' => $form_menu_type,
                'menu_type' => $menu_type,
                'menu_position' => $menu_position,
                'menu_position_after' => $menu_position_after,
                'menu_data' => $menu_data,
            ];

            if ($separator_color) {
                $new_menu['separator_color'] = $separator_color;
            }

            if ($menu_type == 'menu') {
                $custom_admin_menus['menus'][$menu_data[2]] = $new_menu;
            } else {
                $new_menu['menu_parent'] = $menu_parent;
                $custom_admin_menus['submenus'][$menu_data[2]] = $new_menu;
            }

            // update new menu
            update_option('ppc_admin_menus_custom_menus', $custom_admin_menus);

            // update menu order
            update_option('ppc_admin_menus_order', $menu_order);

            // Force the next request to rebuild its menu snapshots with the new item.
            Admin_Menus_Filters::instance()->clearStoredData();

            $response['status']  = 'success';
            $response['redirect']  = admin_url('admin.php?page=pp-capabilities-admin-menus');
            $response['message'] = __('New menu added successfully. Reloading page...', 'capabilities-pro');
        }

        wp_send_json($response);
    }

}

new AdminMenusPro();
