<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities Pro
 *
 * Admin execution controller: menu registration and other filters and actions that need to be loaded for every wp-admin URL
 *
 * This module should not include full functions related to our own plugin screens.
 * Instead, use these filter and action handlers to load other classes when needed.
 *
 */


class AdminFiltersPro {
    function __construct() {
        add_action('init', [$this, 'versionInfoRedirect'], 1);
        add_action('admin_init', [$this, 'loadUpdater']);

        add_action('admin_enqueue_scripts', [$this, 'adminScripts']);

        // Editor Features: Custom Items
        add_action('admin_init', [$this, 'initPostFeatureCustom']);

        $this->initPostFeatureCustom();

        // If custom statuses are not being defined by PublishPress Statuses or PublishPress Planner < 4.0, display guidance for Statuses install / activation
        if (!defined('PUBLISHPRESS_STATUSES_VERSION')
        && (!defined('PUBLISHPRESS_VERSION') || !class_exists('PP_Custom_Status'))) {
            require_once(dirname(__FILE__).'/statuses-intro.php');
        }

        add_action('pp_capabilities_editor_features', [$this, 'editorFeaturesUI']);
        add_action('wp_ajax_ppc_submit_feature_gutenberg_by_ajax', [$this, 'ajaxFeaturesRestrictCustomItem']);
        add_action('wp_ajax_ppc_submit_feature_classic_by_ajax', [$this, 'ajaxFeaturesRestrictCustomItem']);
        add_action('wp_ajax_ppc_delete_custom_post_features_by_ajax', [$this, 'ajaxFeaturesClearCustomItem']);

        // Editor Features: Metaboxes
        add_action('admin_head', [$this, 'initPostFeatureMetaboxes'], 999);

        add_action('publishpress-caps_manager-load', [$this, 'CapsManagerLoad']);
        add_action('admin_enqueue_scripts', array($this, 'adminStyles'));

        add_action('admin_init', [$this, 'settingsUI']);
        add_filter('pp_capabilities_settings_options', [$this, 'settingsOption']);

        add_action('publishpress-caps_manager-load', [$this, 'loadStatusesUI']);

        if (is_admin() && !empty($_POST['page']) && ('pp-capabilities-settings' == $_POST['page'])) {
            add_action('init', [$this, 'updateOptions'], 5);
        }





        //translation
        add_action('init', [$this, 'register_textdomain']);

        //Frontend features pages field
        add_action('pp_capabilities_frontend_features_pages', [$this, 'frontendFeaturesPagesField']);

        //Frontend features post types field
        add_action('pp_capabilities_frontend_features_metabox_post_types', [$this, 'frontendFeaturesPostTypesField']);

        add_action('publishpress-caps_manager_postcaps_table', [$this, 'actPostCapsNote'], 10, 3);
    }

    public function actPostCapsNote($cap_type, $item_type, $args) {
        if (!defined('PUBLISHPRESS_STATUSES_VERSION') || (!in_array($cap_type, ['edit', 'delete'])) || ('type' != $item_type)) {
            return;
        }

        if (Pro::presspermitStatusControlActive()) {
            return;
        }

        $url = admin_url('admin.php?page=pp-capabilities-settings&pp_tab=capabilities');

        if (!get_option('cme_custom_status_control')) :
        ?>
        <p class="cme-status-footer cme-custom-status-hints">
            <?php
            printf(
                esc_html__('Note: Control of custom post statuses is %1$sdisabled%2$s.', 'capabilities-pro'),
                '<a href="' . esc_url($url) . '">',
                '</a>'
            );
            ?>
        </p>
        <?php elseif (!Pro::customStatusPostMetaPermissions()) : ?>
        <p class="cme-status-footer cme-custom-status-hints">
            <?php
            printf(
                esc_html__('Note: Status-specific post editing capabilities are %1$sdisabled%2$s.', 'capabilities-pro'),
                '<a href="' . esc_url($url) . '">',
                '</a>'
            );
            ?>
        </p>

        <?php endif;
    }

	public function register_textdomain() {
        //load pro domain
        $domain       = 'capabilities-pro';
        $mofile_custom = sprintf('%s-%s.mo', $domain, get_user_locale());
        $locations = [
        trailingslashit(WP_LANG_DIR . '/plugins/'),
        trailingslashit(WP_LANG_DIR . '/' . $domain),
        trailingslashit(WP_LANG_DIR . '/loco/plugins/'),
        trailingslashit(WP_LANG_DIR),
        trailingslashit(plugin_dir_path(PUBLISHPRESS_CAPS_PRO_FILE) . 'languages'),
            ];

        // Try custom locations in WP_LANG_DIR.
        foreach ($locations as $location) {
            if (load_textdomain($domain, $location . $mofile_custom)) {
                break;
            }
        }

        //load free domain
        $domain       = 'capability-manager-enhanced';
        $mofile_custom = sprintf('%s-%s.mo', $domain, get_user_locale());
        $locations = [
        trailingslashit(WP_LANG_DIR . '/plugins/'),
        trailingslashit(WP_LANG_DIR . '/' . $domain),
        trailingslashit(WP_LANG_DIR . '/loco/plugins/'),
        trailingslashit(WP_LANG_DIR),
        trailingslashit(plugin_dir_path(PUBLISHPRESS_CAPS_PRO_FILE) . 'languages'),
            ];

        // Try custom locations in WP_LANG_DIR.
        foreach ($locations as $location) {
            if (load_textdomain($domain, $location . $mofile_custom)) {
                break;
            }
        }
}

    public function adminScripts() {
        global $capsman;

        $url = plugins_url( '', PUBLISHPRESS_CAPS_PRO_FILE );

        wp_register_style('pp_capabilities_pro_admin', $url . '/includes-pro/common/css/admin.css', false, PUBLISHPRESS_CAPS_VERSION);
        wp_enqueue_style('pp_capabilities_pro_admin');

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        $url .= "/includes-pro/common/js/admin{$suffix}.js";
        wp_enqueue_script( 'pp_capabilities_pro_admin', $url, array('jquery', 'jquery-ui-sortable'), PUBLISHPRESS_CAPS_VERSION, true );

        wp_localize_script('pp_capabilities_pro_admin', 'ppCapabilitiesPro', [
            'nonce' => wp_create_nonce('ppc-pro-feature-nonce'),
        ]);
    }

    public function settingsUI() {
        require_once(dirname(__FILE__).'/settings-ui.php');
        new Pro_Settings_UI();
    }

    public function settingsOption($settings_options) {

        $settings_options[] = 'cme_display_branding';
        $settings_options[] = 'cme_custom_status_control';
        $settings_options[] = 'cme_custom_status_postmeta_caps';
        $settings_options[] = 'presspermit_privacy_statuses_enabled';
        $settings_options[] = 'presspermit_custom_privacy_edit_caps';

        return $settings_options;
    }

    function versionInfoRedirect() {
        if (!empty($_REQUEST['publishpress_caps_refresh_updates']) && current_user_can('activate_plugins')) { // not a security issue, but prevent status refresh by CSRF
            check_admin_referer('publishpress_caps_refresh_updates');

            publishpress_caps_pro()->keyStatus(true);
            set_transient('publishpress-caps-refresh-update-info', true, 86400);

            delete_site_transient('update_plugins');
            delete_option('_site_transient_update_plugins');

            $opt_val = get_option('cme_edd_key');
            if (is_array($opt_val) && !empty($opt_val['license_key'])) {
                $plugin_slug = basename(PUBLISHPRESS_CAPS_PRO_FILE, '.php'); // 'capabilities-pro';
                $plugin_relpath = basename(dirname(PUBLISHPRESS_CAPS_PRO_FILE)) . '/' . basename(PUBLISHPRESS_CAPS_PRO_FILE);
                $license_key = $opt_val['license_key'];
                $beta = false;

                delete_option(md5($plugin_slug . $license_key . $beta));
                delete_option('edd_api_request_' . md5($plugin_slug . $license_key . $beta));
                delete_option(md5('edd_plugin_' . sanitize_key($plugin_relpath) . '_' . $beta . '_version_info'));
            }

            wp_update_plugins();

            if (current_user_can('update_plugins')) {
                $url = admin_url('admin.php?page=pp-capabilities-settings&publishpress_caps_refresh_done=1');
                $url = wp_nonce_url($url, 'publishpress_caps_refresh_updates');
                wp_redirect(esc_url_raw($url));
                exit;
            }
        }

        if (!empty($_REQUEST['amp;publishpress_caps_refresh_done']) && !empty($_REQUEST['amp;_wpnonce'])) {
            $_REQUEST['publishpress_caps_refresh_done'] = (int) $_REQUEST['amp;publishpress_caps_refresh_done'];
            $_REQUEST['_wpnonce'] = sanitize_key($_REQUEST['amp;_wpnonce']);
        }

        if (!empty($_REQUEST['publishpress_caps_refresh_done']) && empty($_POST)) {
            check_admin_referer('publishpress_caps_refresh_updates');

            if (current_user_can('activate_plugins')) {
                $url = admin_url('update-core.php');
                wp_redirect(esc_url_raw($url));
                exit;
            }
        }
    }

    function CapsManagerLoad() {
        require_once(dirname(__FILE__).'/manager-ui.php');
        new ManagerUI();
    }

    function loadUpdater() {
        require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/library/Factory.php');
        $container = \PublishPress\Capabilities\Factory::get_container();
        return $container['edd_container']['update_manager'];
    }

    function adminStyles() {

        $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

        $capabilities_pages = function_exists('pp_capabilities_admin_pages')
            ? (array) pp_capabilities_admin_pages()
            : [];

        if (!empty($current_page) && in_array($current_page, $capabilities_pages, true)) {
            if ($current_page === 'pp-capabilities' && !defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                $asset_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
                wp_enqueue_style('pp-capabilities-admin-core',
                    plugin_dir_url(CME_FILE) . "includes-core/admin-core{$asset_suffix}.css",
                    [], PUBLISHPRESS_CAPS_VERSION);
            }

            wp_enqueue_style('publishpress-caps-pro', plugins_url( '', PUBLISHPRESS_CAPS_PRO_FILE ) . '/includes-pro/pro.css', [], PUBLISHPRESS_CAPS_VERSION);
            wp_enqueue_style('publishpress-caps-status-caps', plugins_url( '', PUBLISHPRESS_CAPS_PRO_FILE ) . '/includes-pro/common/css/status-caps.css', [], PUBLISHPRESS_CAPS_VERSION);

            add_thickbox();
        }

    }

    function loadStatusesUI() {
        if ((Pro::customStatusPermissionsAvailable() && (get_option('cme_custom_status_control') || Pro::presspermitStatusControlActive()))
        || defined('PUBLISHPRESS_REVISIONS_VERSION')
        ) {
            require_once(dirname(__FILE__).'/admin.php');
            new CustomStatusCapsUI();
        }

        if (Pro::customPrivacyStatusesAvailable()
        && (defined('PUBLISHPRESS_STATUSES_VERSION') || (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '3.13', '<') && get_option('presspermit_legacy_status_control')))
        ) {
            require_once(dirname(__FILE__).'/admin-privacy.php');
            new CustomPrivacyCapsUI();
        }
    }

    function updateCapabilitiesOptions() {
        check_admin_referer('capsman-general-manager');

        if (!current_user_can('manage_capabilities_settings')) {
            wp_die('<strong>' . esc_html__('You do not have permission to manage settings.', 'capabilities-pro') . '</strong>', '', ['response' => 403]);
        }

        update_option('cme_custom_status_control', (int) !empty($_REQUEST['cme_custom_status_control']));
        update_option('cme_custom_status_postmeta_caps', (int) !empty($_REQUEST['cme_custom_status_postmeta_caps']));

        if (isset($_REQUEST['presspermit_privacy_statuses_enabled'])) {
            update_option('presspermit_privacy_statuses_enabled', (int) !empty($_REQUEST['presspermit_privacy_statuses_enabled']));
        }

        if (isset($_REQUEST['presspermit_custom_privacy_edit_caps'])) {
            update_option('presspermit_custom_privacy_edit_caps', (int) !empty($_REQUEST['presspermit_custom_privacy_edit_caps']));
        }
    }

    function updateOptions() {
        $this->updateCapabilitiesOptions();

        update_option('cme_display_branding', (int) !empty($_REQUEST['cme_display_branding']));
    }

    function editorFeaturesUI() {
        require_once (dirname(__FILE__) . '/features/config/metaboxes-config.php');
        new EditorFeaturesMetaboxesConfig();

        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::instance();

        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {

                $('.editor-features-tab').click(function (e) {
                    e.preventDefault();

                    $('.editor-features-custom').hide();
                    var elem = $(this).attr('data-tab') + '-custom';
                    $(elem).show();
                });
            });
            /* ]]> */
        </script>
        <?php
    }

    /**
     * Capture metaboxes for post features
     *
     * @param array $post_types Post type.
     * @param array $elements All elements.
     * @param array $post_disabled All disabled post type element.
     *
     * @since 2.1.1
     */
    function initPostFeatureMetaboxes()
    {
        $screen = get_current_screen();

        if ($screen && !empty($screen->base) && ($screen->base == 'post')) {
            require_once (dirname(__FILE__) . '/features/config/metaboxes-config.php');
            $features_metaboxes = new EditorFeaturesMetaboxesConfig();
            $features_metaboxes->capturePostFeatureMetaboxes($screen->post_type);
        }
    }

    function initPostFeatureCustom() {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::instance();
    }

    /**
     * Ajax callback to add restriction for a custom editor features item.
     *
     * @since 2.1.1
     */
    function ajaxFeaturesRestrictCustomItem()
    {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::addByAjax();
    }

    /**
     * Ajax callback to delete custom-added editor features item restriction.
     *
     * @since 2.1.1
     */
    function ajaxFeaturesClearCustomItem()
    {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::deleteByAjax();
    }

    /**
     * Frontend features pages field
     *
     * @since 2.9.0
     */
    function frontendFeaturesPagesField()
    {
        $options = [
          'homepage'      => esc_html__('Homepage', 'capabilities-pro'),
          'archive_pages' => esc_html__('Archive Pages', 'capabilities-pro'),
          'single_pages'  => esc_html__('Single Pages', 'capabilities-pro')
        ];
        ?>
        <select class="frontend-element-new-element-pages chosen-cpt-select frontendelements-form-pages" data-placeholder="<?php esc_attr_e('Select pages...', 'capabilities-pro'); ?>" multiple>
            <?php foreach ($options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>">
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br />
        <small>
            <?php esc_html_e('You can select page types where this element will be added.', 'capabilities-pro'); ?>
        </small>
    <?php
    }

    /**
     * Frontend features post types field
     *
     * @since 2.9.0
     */
    function frontendFeaturesPostTypesField()
    {
        ?>
        <select class="frontend-element-new-element-post-types chosen-cpt-select frontendelements-form-post-types"
            data-placeholder="<?php esc_attr_e('Select post types...', 'capabilities-pro'); ?>"
            multiple>
            <?php foreach (get_post_types(['public' => true], 'objects') as $name => $post_type) :
                if ($name === 'attachment') {
                    continue;
                } ?>
                <option
                    value="<?php echo esc_attr($name); ?>">
                    <?php echo esc_html($post_type->label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br />
         <small>
            <?php esc_html_e('This will add a metabox on the post editing screen. You can use this feature to add body classes only for that post.', 'capabilities-pro'); ?>
        </small>
    <?php
    }
}
