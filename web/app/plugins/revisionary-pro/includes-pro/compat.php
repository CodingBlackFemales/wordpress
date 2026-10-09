<?php

class RevisionaryCompat {
    private $saved_meta_keys = [];
    private $rest_buffer_controller = [];
    private $rest_method = '';
    private $rest_params = false;

    private $polylang_descripts = [];
    private $polylang_post_terms = [];

    private $post_revision_fields = [];
    private $field_revision_created = [];
    private $creating_field_revision = false;

    function __construct() {
        add_action('wp_ajax_revisionary_update_ignored_meta_fields', [$this, 'ajaxUpdateIgnoredMetaFields']);
        add_action('revisionary_option_ui_visual_compare_options', [$this, 'ignoredMetaFieldsSettingsUI']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueIgnoredMetaFieldsSettings']);
        add_action('personal_options', [$this, 'ignoredMetaFieldsProfileUI']);
        add_action('init', [$this, 'stripIgnoredFieldValues']);

        add_filter(
            'visual_post_compare_comparison_payload',
            function ($payload, $current_post, $slider_posts) {
                require_once REVISIONARY_PRO_ABSPATH . '/includes-pro/VisualCompareFields.php';
                return RevisionaryVisualCompareFields::filterPayload($payload, $current_post, $slider_posts);
            },
            10,
            3
        );

        add_filter('visual_post_compare_script_dependencies', [$this, 'fltVisualCompareScriptDependencies']);

        if (defined('FL_BUILDER_VERSION')) {
            add_action('rvy_init', function($revisionary) {
                require_once(dirname(__FILE__).'/compat/beaver-builder.php');
                new RevisionaryBeaverBuilder();
            });
			
			add_action('fl_builder_after_save_layout', [$this, 'flt_after_save_layout'], 10, 4);
        }

        if (defined('ET_BUILDER_PLUGIN_VERSION') || (false !== stripos(get_template(), 'divi'))) {
            add_filter('et_builder_should_load_framework',
                function($load) {
                    if (!empty($_REQUEST['action']) && ('revise' == $_REQUEST['action'])) {         //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $load = true;
                    }
                    
                    return $load;
                }
            );

            add_action('rvy_init', function($revisionary) {
                global $current_user;

                if ((!defined('REST_REQUEST') || ! REST_REQUEST) && !empty($current_user->ID)) {
					require_once(dirname(__FILE__).'/compat/divi.php');
                	new RevisionaryDivi($revisionary);
                }
            });

            add_action(
                'revision_applied', 
                function($published_id, $revision) {
                    if (class_exists('ET_Core_PageResource') && method_exists('ET_Core_PageResource', 'remove_static_resources')) {
                        ET_Core_PageResource::remove_static_resources( 'all', 'all' );
                    }
                }, 
                10, 2
            );
        }

        if (defined('ELEMENTOR_VERSION') && !defined('RVY_DISABLE_ELEMENTOR_INTEGRATION')) {
            require_once(dirname(__FILE__).'/compat/elementor.php');
            new RevisionaryElementor();
        }

        // WPML
        if ( defined('ICL_SITEPRESS_VERSION') ) {
            require_once(REVISIONARY_PRO_ABSPATH . '/includes-pro/compat/wpml.php');
        }

        // WPML: Multilanguage subdomain configuration fails Revisions preview integration because WPML doesn't implement user login for those subdomain requests
        add_filter('revisionary_preview_url', 
            function($preview_url, $revision, $args) {
                if (rvy_get_option('unfiltered_preview_links')) {
                    require_once(dirname(__FILE__).'/compat/links-unfiltered.php');
                    $rvy_unfiltered_links = new RevisionaryUnfilteredLinks();

                    $preview_url = $rvy_unfiltered_links->preview_url_unfiltered($preview_url, $revision, $args);
                }

                return $preview_url;
            }, 10, 3
        );

        add_filter('_wp_post_revision_fields', [$this, 'fltLogRevisionFields'], 10, 2);
        add_action('_wp_put_post_revision', [$this, 'actArchivePostMeta'], 10, 2);
        add_filter('add_post_metadata', [$this, 'fltCreateFieldRevision'], 10, 5);
        add_filter('update_post_metadata', [$this, 'fltCreateFieldRevision'], 10, 5);
        add_filter('delete_post_metadata', [$this, 'fltCreateFieldRevision'], 10, 5);

        // WooCommerce (Product Variations)
        if (class_exists('WooCommerce')) {
            require_once(dirname(__FILE__).'/compat/woocommerce.php');
            new RevisionaryWooCommerce();
        }

        // NitroPack cache
        if (defined('NITROPACK_VERSION')) {
            add_filter(
                'revisionary_create_revision_redirect',
                function($redirect) {
                    do_action('nitropack_integration_purge_all');
                    return $redirect;
                },
                5
            );

            add_action(
                'revision_applied', 
                function($post_id) {
                    if (function_exists('nitropack_sdk_invalidate')) {
                        if ($url = get_permalink($post_id)) {
                            nitropack_sdk_invalidate($url);
                        }
                    }
                }
            );
        }

        if (defined('LEARNDASH_VERSION')) {
            add_filter(
                'revisionary_use_autodraft_meta',
                function ($use_autodraft, $revision_data) {
                    $post_type = (!empty($revision_data['post_type'])) ? $revision_data['post_type'] : '';

                    if (in_array($post_type, ['sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-question', 'sfwd-certificates', 'sfwd-assignment', 'ld-exam', 'ld-achievement', 'ld-notification'])) {
                        $use_autodraft = false;
                    }

                    return $use_autodraft;
                },
                10, 2
            );
        }
        
        // Page Links To
        if (class_exists('CWS_PageLinksTo')) {
            $preview_arg = (defined('RVY_PREVIEW_ARG')) ? sanitize_key(constant('RVY_PREVIEW_ARG')) : 'rv_preview';

            if (
                (
                    (
                    !is_admin() 
                    && (!empty($_REQUEST[$preview_arg]) || !empty($_REQUEST['preview'])) && !empty($_REQUEST['nc'])     //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    )
                    || (
                        !empty($_REQUEST['action'])                                                                     //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        && in_array(
                            sanitize_key($_REQUEST['action']),                                                          //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            ['create_revision', 'submit_revision', 'approve_revision', 'publish_revision']
                        )
                    )
                ) && empty($_GET['customize_theme'])                                                                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ) {
                add_action('template_redirect', function() {
                    $_GET['customize_theme'] = true;
                }, 9);

                add_action('template_redirect', function() {
                    unset($_GET['customize_theme']);                                                                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                }, 11);
            }

            if (!empty($_REQUEST['page']) && in_array(sanitize_key($_REQUEST['page']), ['revisionary-q'])) {            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                add_action(
                    'pp_revisions_get_post_link',
                    function($post_id) {
                        global $pp_revisions_link_id;
                        $pp_revisions_link_id = $post_id;
                    }
                );

                add_filter(
                    'page_links_to_link', 
                    function ($meta_link, $post, $link) {
                        global $pp_revisions_link_id;

                        if (!empty($pp_revisions_link_id) && rvy_in_revision_workflow($pp_revisions_link_id)) {
                            return $link;
                        }

                        return $meta_link;
                    },
                    99, 3
                );
            }
        }

        if (defined('POLYLANG_VERSION')) {
            // Classic Editor
            add_action( 'load-post.php', function() {
                if (!empty($_POST['post_lang_choice'])) {                                //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                    if ($post_id = rvy_detect_post_id()) {
                        if (rvy_in_revision_workflow($post_id)) {
                            unset($_POST['post_lang_choice']);                           //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                        }
                    }
                }
            }, 9);

            add_action('save_post', function($post_id, $_post, $args = []) {
                $this->bufferPolyLangData($post_id);
            }, 1, 2);

            add_action('save_post', function($post_id, $_post, $args = []) {
                global $wpdb;
                
                if (rvy_in_revision_workflow($post_id)) {
                    if (!empty($this->polylang_descripts)) {
                        foreach($this->polylang_descripts as $tt_id => $descript) {
                            $wpdb->update($wpdb->term_taxonomy, ['description' => $descript], ['term_taxonomy_id' => intval($tt_id)]);      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        }
                    }

                    if (!empty($this->polylang_post_terms)) {
                        if ($published_post_id = rvy_post_id($post_id)) {
                            $term_ids = [];
                            foreach($this->polylang_post_terms as $term) {
                                $term_ids []= $term->term_id;
                            }

                            wp_set_object_terms($published_post_id, $term_ids, 'post_translations');
                        }
                    }
                }
            }, 999, 3);
        }

        // ACF: Revisions match field group page matching rule
        add_filter('acf/location/rule_match/page', [$this, 'fltACFpageMatch'], 10, 4);
        add_filter('acf/location/rule_match/page_type', [$this, 'fltACFfrontPageMatch'], 10, 4);

        // ACF: ensure custom fields are stored to archive after pending / scheduled revision publication
        add_action('revision_applied', [$this, 'actRevisionApplied'], 20, 2);

        add_action('init', [$this, 'actInitNonceWorkaroundACF'], 20);
		add_filter('wp_revisions_to_keep', [$this, 'fltACFpreviewWorkaround'], 10, 2);

		// todo: move to admin file
        add_filter('revisionary_diff_ui', [$this, 'flt_revision_diff_ui'], 10, 4);

        add_filter('revisionary_compare_meta_fields', [$this, 'flt_compare_meta_fields']);

        // Pro
        if (class_exists('ACFE')) {
            add_action('wp_loaded', [$this, 'addACFEsupport']);
        }
		
		add_action('revisionary_copy_postmeta', [$this, 'actPodsCopyPostmeta'], 10, 3);
    }

    function fltLogRevisionFields($fields, $post) {
        $this->post_revision_fields = $fields;

        return $fields;
    }

    function fltCreateFieldRevision($check, $post_id, $meta_key, $meta_value, $operation_arg = false) {
        global $revisionary;

        if (!rvy_get_option('archive_postmeta_on_edit')
        || $this->creating_field_revision
        || !empty($this->field_revision_created[$post_id])
        || wp_is_post_revision($post_id)
        ) {
            return $check;
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return $check;
        }

        if (isset($revisionary->enabled_post_types_archive[$post->post_type])
        && empty($revisionary->enabled_post_types_archive[$post->post_type])) {
            return $check;
        }

        require_once REVISIONARY_PRO_ABSPATH . '/includes-pro/VisualCompareFields.php';
        if (!RevisionaryVisualCompareFields::isRevisionTriggerMetaKey($post, $meta_key)
        || !$this->isPostMetaChange($post_id, $meta_key, $meta_value, $operation_arg)) {
            return $check;
        }

        $this->creating_field_revision = true;
        $revision_id = $this->putFieldRevision($post);
        $this->creating_field_revision = false;

        if ($revision_id && !is_wp_error($revision_id)) {
            $this->field_revision_created[$post_id] = true;
        }

        return $check;
    }

    private function putFieldRevision($post) {
        $revision_data = _wp_post_revision_data(get_object_vars($post), false);
        $revision_id = wp_insert_post(wp_slash($revision_data), false, false);

        if ($revision_id && !is_wp_error($revision_id)) {
            do_action('_wp_put_post_revision', $revision_id, $post->ID);
        }

        return $revision_id;
    }

    private function isPostMetaChange($post_id, $meta_key, $meta_value, $operation_arg) {
        $filter = current_filter();
        $values = get_post_meta($post_id, $meta_key, false);

        if ('add_post_metadata' === $filter) {
            return !$operation_arg || !$values;
        }

        if ('delete_post_metadata' === $filter) {
            if ($operation_arg || !$values) {
                return false;
            }
            return '' === $meta_value || in_array($meta_value, $values, true);
        }

        return 1 !== count($values) || maybe_serialize(reset($values)) !== maybe_serialize($meta_value);
    }

    function actArchivePostMeta($revision_id, $post_id) {
        global $revisionary;

        $this->field_revision_created[$post_id] = true;

        if (!rvy_get_option('archive_postmeta_on_edit')) {
            return;
        }

        $post_type = get_post_field('post_type', $post_id);

        // An absent entry can occur for a revision-capable custom post type which was
        // registered without declaring native "revisions" support (PublishPress Cart
        // products are one example). Reaching _wp_put_post_revision proves that a real
        // Past Revision was created, so only an explicitly disabled entry should block
        // metadata archival.
        if (isset($revisionary->enabled_post_types_archive[$post_type])
        && empty($revisionary->enabled_post_types_archive[$post_type])) {
            return;
        }

        $skip_post_meta = [];

        if (!empty($this->post_revision_fields['meta_input'])) {
            $skip_post_meta = array_keys($this->post_revision_fields['meta_input']);
        }

        revisionary_copy_postmeta($post_id, $revision_id, compact('skip_post_meta'));
    }

    function fltVisualCompareScriptDependencies($dependencies) {
        $handle = $this->registerVisualCompareFieldsScript();
        $this->localizeIgnoredMetaFieldsScript($handle);
        $this->enqueueVisualCompareFieldsStyle();

        $dependencies[] = $handle;
        return array_values(array_unique($dependencies));
    }

    private function registerVisualCompareFieldsScript() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        $relative_path = "/includes-pro/visual-compare-fields{$suffix}.js";
        $script_path = REVISIONARY_PRO_ABSPATH . $relative_path;
        $handle = 'revisionary-pro-visual-compare-fields';

        wp_register_script(
            $handle,
            plugins_url(ltrim($relative_path, '/'), REVISIONARY_PRO_FILE),
            ['wp-element', 'wp-i18n'],
            file_exists($script_path) ? filemtime($script_path) : PUBLISHPRESS_REVISIONS_PRO_VERSION,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations($handle, 'revisionary', REVISIONARY_PRO_ABSPATH . '/languages');
        }

        return $handle;
    }

    private function enqueueVisualCompareFieldsStyle() {
        $style_path = REVISIONARY_PRO_ABSPATH . '/includes-pro/visual-compare-fields.css';
        wp_enqueue_style(
            'revisionary-pro-visual-compare-fields',
            plugins_url('includes-pro/visual-compare-fields.css', REVISIONARY_PRO_FILE),
            [],
            file_exists($style_path) ? filemtime($style_path) : PUBLISHPRESS_REVISIONS_PRO_VERSION
        );
    }

    private function ignoredMetaFieldsCapability() {
        return (string) apply_filters('revisionary_compare_ignore_meta_fields_capability', 'manage_options');
    }

    private function canManageIgnoredMetaFields() {
        return is_user_logged_in() && current_user_can('read');
    }

    private function ignoredMetaFields() {
        $fields = get_user_meta(get_current_user_id(), 'rvy_compare_ignore_meta_fields', true);
		if (!is_array($fields) && current_user_can($this->ignoredMetaFieldsCapability())) {
			$legacy = get_option('rvy_compare_ignore_meta_fields', []);
			$fields = is_array($legacy) ? $legacy : [];
		}
        return is_array($fields) ? array_values(array_unique(array_filter($fields, 'is_string'))) : [];
    }

	private function canSuppressMetaFields() {
		return current_user_can('manage_options');
	}

	private function suppressedMetaFields() {
		$fields = get_option('rvy_compare_suppress_meta_fields', []);
		return is_array($fields) ? array_values(array_unique(array_filter($fields, 'is_string'))) : [];
	}

    private function localizeIgnoredMetaFieldsScript($handle) {
        wp_localize_script($handle, 'revisionaryCompareFields', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revisionary_compare_ignore_meta_fields'),
            'ignoredFields' => $this->ignoredMetaFields(),
            'canManage' => $this->canManageIgnoredMetaFields(),
			'ignoredProviders' => $this->sanitizeIgnoredFieldProviders(get_user_meta(get_current_user_id(), 'rvy_compare_ignored_field_catalog', true)),
			'canSuppress' => $this->canSuppressMetaFields(),
			'suppressedFields' => $this->canSuppressMetaFields() ? $this->suppressedMetaFields() : [],
			'suppressedProviders' => $this->canSuppressMetaFields() ? $this->sanitizeIgnoredFieldProviders(get_option('rvy_compare_suppressed_field_catalog', [])) : [],
        ]);
    }

    private function sanitizeIgnoredFieldProviders($providers) {
        $result = [];
        foreach ((array) $providers as $provider) {
            if (empty($provider['id']) || empty($provider['groups']) || !is_array($provider['groups'])) {
                continue;
            }
            $provider_id = sanitize_key($provider['id']);
            $clean_provider = [
                'id' => $provider_id,
                'label' => sanitize_text_field(isset($provider['label']) ? $provider['label'] : $provider_id),
                'groups' => [],
            ];
            foreach ($provider['groups'] as $group) {
                if (empty($group['fields']) || !is_array($group['fields'])) {
                    continue;
                }
                $clean_group = [
                    'label' => sanitize_text_field(isset($group['label']) ? $group['label'] : ''),
                    'fields' => [],
                ];
                foreach ($group['fields'] as $field) {
                    if (empty($field['key'])) {
                        continue;
                    }
                    $meta_key = sanitize_text_field($field['key']);
                    $clean_group['fields'][] = [
                        'key' => $meta_key,
                        'label' => sanitize_text_field(isset($field['label']) ? $field['label'] : $meta_key),
                    ];
                }
                if ($clean_group['fields']) {
                    $clean_provider['groups'][] = $clean_group;
                }
            }
            if ($clean_provider['groups']) {
                $result[] = $clean_provider;
            }
        }
        return $result;
    }

    public function stripIgnoredFieldValues() {
		$stored = get_option('rvy_compare_suppressed_field_catalog', []);
        if (!is_array($stored)) {
            $stored = [];
        }
        $sanitized = $this->sanitizeIgnoredFieldProviders($stored);
        if ($stored !== $sanitized) {
			update_option('rvy_compare_suppressed_field_catalog', $sanitized, false);
        }
    }

    private function mergeIgnoredFieldCatalog($catalog, $incoming, $ignored_fields) {
        $indexed = [];
        foreach (array_merge((array) $catalog, (array) $incoming) as $provider) {
            $provider_id = isset($provider['id']) ? sanitize_key($provider['id']) : '';
            if (!$provider_id) continue;
            if (!isset($indexed[$provider_id])) {
                $indexed[$provider_id] = [
                    'id' => $provider_id,
                    'label' => isset($provider['label']) ? $provider['label'] : $provider_id,
                    'groups' => [],
                ];
            } else if (!empty($provider['label'])) {
                $indexed[$provider_id]['label'] = $provider['label'];
            }
            foreach ((array) (isset($provider['groups']) ? $provider['groups'] : []) as $group) {
                $group_label = isset($group['label']) ? (string) $group['label'] : '';
                if (!isset($indexed[$provider_id]['groups'][$group_label])) {
                    $indexed[$provider_id]['groups'][$group_label] = ['label' => $group_label, 'fields' => []];
                }
                foreach ((array) (isset($group['fields']) ? $group['fields'] : []) as $field) {
                    if (!empty($field['key']) && in_array($field['key'], $ignored_fields, true)) {
                        $indexed[$provider_id]['groups'][$group_label]['fields'][$field['key']] = $field;
                    }
                }
            }
        }

        $result = [];
        foreach ($indexed as $provider) {
            $groups = [];
            foreach ($provider['groups'] as $group) {
                if ($group['fields']) {
                    $group['fields'] = array_values($group['fields']);
                    $groups[] = $group;
                }
            }
            if ($groups) {
                $provider['groups'] = $groups;
                $result[] = $provider;
            }
        }
        return $result;
    }

    public function ajaxUpdateIgnoredMetaFields() {
        check_ajax_referer('revisionary_compare_ignore_meta_fields', 'nonce');
		$list_type = !empty($_POST['listType']) && 'suppressed' === sanitize_key(wp_unslash($_POST['listType'])) ? 'suppressed' : 'ignored';
		$can_update = 'suppressed' === $list_type ? $this->canSuppressMetaFields() : $this->canManageIgnoredMetaFields();
        if (!$can_update) {
			$message = 'suppressed' === $list_type
				? esc_html__('You are not allowed to suppress comparison fields.', 'revisionary-pro')
				: esc_html__('You are not allowed to change ignored fields.', 'revisionary-pro');
            wp_send_json_error(['message' => $message], 403);
        }

        $operation = !empty($_POST['operation']) ? sanitize_key(wp_unslash($_POST['operation'])) : 'add';
        $posted_fields = isset($_POST['fields']) ? (array) wp_unslash($_POST['fields']) : [];                           // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $posted_fields = array_values(array_unique(array_filter(array_map('sanitize_text_field', $posted_fields))));
		$current_fields = 'suppressed' === $list_type ? $this->suppressedMetaFields() : $this->ignoredMetaFields();
        $ignored_fields = 'replace' === $operation
            ? $posted_fields
            : array_values(array_unique(array_merge($current_fields, $posted_fields)));

        $providers = [];
        if (!empty($_POST['providers'])) {
            $decoded = json_decode(wp_unslash($_POST['providers']), true);                                              // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if (is_array($decoded)) {
                $providers = $this->sanitizeIgnoredFieldProviders($decoded);
            }
        }
		$catalog_key = 'suppressed' === $list_type ? 'rvy_compare_suppressed_field_catalog' : 'rvy_compare_ignored_field_catalog';
		$stored_catalog = 'suppressed' === $list_type
			? get_option($catalog_key, [])
			: get_user_meta(get_current_user_id(), $catalog_key, true);
        $catalog = $this->sanitizeIgnoredFieldProviders($stored_catalog);
        $catalog = $this->mergeIgnoredFieldCatalog($catalog, $providers, $ignored_fields);

		if ('suppressed' === $list_type) {
			update_option('rvy_compare_suppress_meta_fields', $ignored_fields, false);
			update_option($catalog_key, $catalog, false);
		} else {
			update_user_meta(get_current_user_id(), 'rvy_compare_ignore_meta_fields', $ignored_fields);
			update_user_meta(get_current_user_id(), $catalog_key, $catalog);
		}

        wp_send_json_success([
			'listType' => $list_type,
            'ignoredFields' => $ignored_fields,
            'count' => count($ignored_fields),
            'providers' => $catalog,
        ]);
    }

    public function enqueueIgnoredMetaFieldsSettings($hook_suffix = '') {
        $is_settings = !empty($_REQUEST['page'])                                            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && 'revisionary-settings' === sanitize_key(wp_unslash($_REQUEST['page']));      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_profile = 'profile.php' === $hook_suffix
            && !$this->canSuppressMetaFields()
            && !empty($this->ignoredMetaFields());

        if (!$this->canManageIgnoredMetaFields() || (!$is_settings && !$is_profile)) {
            return;
        }

        $ignored_fields = $this->ignoredMetaFields();
		$catalog = $this->sanitizeIgnoredFieldProviders(get_user_meta(get_current_user_id(), 'rvy_compare_ignored_field_catalog', true));
		$can_suppress = $this->canSuppressMetaFields();
		$suppressed_fields = $can_suppress ? $this->suppressedMetaFields() : [];
        $handle = $this->registerVisualCompareFieldsScript();
        $this->localizeIgnoredMetaFieldsScript($handle);
        $this->enqueueVisualCompareFieldsStyle();
        wp_enqueue_script($handle);
        wp_enqueue_script(
            'revisionary-pro-ignored-fields-settings',
            plugins_url('includes-pro/ignored-fields-settings.js', REVISIONARY_PRO_FILE),
            [$handle, 'wp-element', 'wp-i18n'],
            PUBLISHPRESS_REVISIONS_PRO_VERSION,
            true
        );
        wp_localize_script('revisionary-pro-ignored-fields-settings', 'revisionaryIgnoredFieldsSettings', [
            'providers' => $catalog,
            'ignoredFields' => $ignored_fields,
			'canSuppress' => $can_suppress,
			'reviewCaption' => $can_suppress
				? esc_html__('Review %d ignored / suppressed fields', 'revisionary-pro')
				: esc_html__('Review %d ignored fields', 'revisionary-pro'),
			'suppressedProviders' => $can_suppress
				? $this->sanitizeIgnoredFieldProviders(get_option('rvy_compare_suppressed_field_catalog', []))
				: [],
			'suppressedFields' => $suppressed_fields,
        ]);
		if ($is_profile) {
			$settings_style_path = REVISIONARY_PRO_ABSPATH . '/includes-pro/settings-pro.css';
			wp_enqueue_style(
				'revisionary-pro-ignored-fields-profile',
				plugins_url('includes-pro/settings-pro.css', REVISIONARY_PRO_FILE),
				[],
				file_exists($settings_style_path) ? filemtime($settings_style_path) : PUBLISHPRESS_REVISIONS_PRO_VERSION
			);
		}
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'rvy-visual-compare-fields-settings',
            RVY_URLPATH . '/includes/visual-post-compare/visual-post-compare.css',
            ['dashicons'],
            PUBLISHPRESS_REVISIONS_VERSION
        );
    }

    public function ignoredMetaFieldsSettingsUI() {
        if (!$this->canManageIgnoredMetaFields()
        || empty($_REQUEST['page'])                                                             // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        || 'revisionary-settings' !== sanitize_key(wp_unslash($_REQUEST['page']))               // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            return;
        }

        $ignored_fields = $this->ignoredMetaFields();
		$can_suppress = $this->canSuppressMetaFields();
		$suppressed_fields = $can_suppress ? $this->suppressedMetaFields() : [];

		if (empty($ignored_fields) && empty($suppressed_fields)) {
            return;
        }

        ?>
        <div class="agp-vspaced_input revisionary-ignored-fields-setting">
			<button type="button" class="button revisionary-review-fields">
				<?php
				$review_count = count($ignored_fields) + count($suppressed_fields);
				$review_caption = $can_suppress
					? esc_html__('Review %d ignored / suppressed fields', 'revisionary-pro')
					: esc_html__('Review %d ignored fields', 'revisionary-pro');
				printf($review_caption, $review_count); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</button>
        </div>
		<?php $this->renderIgnoredFieldsModal($can_suppress); ?>
		<?php
	}

	public function ignoredMetaFieldsProfileUI($profile_user) {
		if (!$profile_user instanceof WP_User
		|| (int) $profile_user->ID !== get_current_user_id()
		|| !$this->canManageIgnoredMetaFields()
		|| $this->canSuppressMetaFields()
		) {
			return;
		}

		$ignored_fields = $this->ignoredMetaFields();
		if (empty($ignored_fields)) {
			return;
		}
		?>
		<tr class="revisionary-profile-comparison-fields">
			<th scope="row"><?php esc_html_e('Revision Comparison', 'revisionary-pro'); ?></th>
			<td>
				<button type="button" class="button revisionary-review-fields">
					<?php printf(esc_html__('Review %d ignored fields', 'revisionary-pro'), count($ignored_fields)); ?>
				</button>
				<?php $this->renderIgnoredFieldsModal(false); ?>
			</td>
		</tr>
		<?php
	}

	private function renderIgnoredFieldsModal($can_suppress) {
		?>
        <div class="revisionary-ignored-fields-modal" hidden>
            <div class="revisionary-ignored-fields-modal__backdrop"></div>
            <div class="revisionary-ignored-fields-modal__frame" role="dialog" aria-modal="true" aria-labelledby="revisionary-ignored-fields-title">
                <button type="button" class="revisionary-ignored-fields-modal__close" aria-label="<?php esc_attr_e('Close', 'revisionary-pro'); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
				<div class="revisionary-ignored-fields-modal__columns">
					<section class="revisionary-ignored-fields-modal__column">
						<h2 id="revisionary-ignored-fields-title" class="visual-post-compare-fields__manager-headline" tabindex="0">
							<?php esc_html_e('Ignore Fields', 'revisionary-pro'); ?>
							<span class="visual-post-compare-fields__manager-headline-tooltip" role="tooltip"><?php esc_html_e('Changes to these fields will be hidden from the current user.', 'revisionary-pro'); ?></span>
						</h2>
						<div class="revisionary-ignored-fields-modal__scroll"><div class="revisionary-ignored-fields-modal__content"></div></div>
					</section>
					<?php if ($can_suppress) : ?>
					<section class="revisionary-ignored-fields-modal__column">
						<h2 class="visual-post-compare-fields__manager-headline" tabindex="0">
							<?php esc_html_e('Suppress Fields', 'revisionary-pro'); ?>
							<span class="visual-post-compare-fields__manager-headline-tooltip" role="tooltip"><?php esc_html_e('Changes to these fields will be hidden from all users.', 'revisionary-pro'); ?></span>
						</h2>
						<div class="revisionary-ignored-fields-modal__scroll"><div class="revisionary-suppressed-fields-modal__content"></div></div>
					</section>
					<?php endif; ?>
                </div>
				<div class="revisionary-ignored-fields-modal__actions"><button type="button" class="button button-primary revisionary-update-ignored-fields"><?php esc_html_e('Update', 'revisionary-pro'); ?></button></div>
            </div>
        </div>
        <?php
    }
	
	function flt_after_save_layout( $post_id, $publish, $data, $settings ) {
		// Beaver Builder passes the publish intent to this hook. Do not infer it
		// from request payload details, which vary between builder versions.
		if ( !$publish || !rvy_in_revision_workflow($post_id) ) {
			return;
		}

		$post = get_post($post_id);

		if ( !$post ) {
			return;
		}

		// Revisionary's workflow actions perform the authoritative capability checks.
		switch ($post->post_mime_type) {
				case 'draft-revision' :
					require_once(dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');	
					rvy_revision_submit($post_id);
					break;
	
				case 'pending-revision' :
					require_once( dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');	
					rvy_revision_approve($post_id);
					break;
		}
	}

    function bufferPolylangData($post_id) {
        $this->polylang_descripts = [];
        $this->polylang_post_terms = [];

        if (rvy_in_revision_workflow($post_id)) {
            if ($published_post_id = rvy_post_id($post_id)) {
                if ($polylang_post_terms = get_transient("_rvy_polylang_terms_{$post_id}")) {
                    $this->polylang_post_terms = (array) $polylang_post_terms;
                } else {
                    $this->polylang_post_terms = wp_get_object_terms($published_post_id, 'post_translations', ['fields' => 'all']);
                    set_transient("_rvy_polylang_terms_{$post_id}", $this->polylang_post_terms, 15);
                }

                if ($polylang_descripts = get_transient("_rvy_polylang_backup_{$post_id}")) {
                    $this->polylang_descripts = (array) $polylang_descripts;
                } else {
                    foreach($this->polylang_post_terms as $term) {
                        $this->polylang_descripts["{$term->term_taxonomy_id}"] = $term->description;
                    }

                    if ($this->polylang_descripts) {
                        set_transient("_rvy_polylang_backup_{$post_id}", $this->polylang_descripts, 15);
                    }
                }
            }
        }
    }

    function fltACFfrontPageMatch($result, $rule, $screen, $field_group) {
        if (empty($screen['post_id']) || !is_numeric($screen['post_id']) || empty($rule['operator']) || empty($rule['value'])) {
            return $result;
        }
        
        if ('front_page' == $rule['value']) {
            $post_id = rvy_post_id((int) $screen['post_id']);
            $front_page = (int) get_option('page_on_front');
        
            switch ($rule['operator']) {
                case '==':
                    return ($post_id == $front_page);
                    break;
                case '!=':
                    return ($post_id != $front_page);
                    break;
            }
        }
        
        return $result;
    }

    function fltACFpageMatch($result, $rule, $screen, $field_group) {
        if (empty($screen['post_id']) || !is_numeric($screen['post_id']) || empty($rule['operator']) || empty($rule['value'])) {
            return $result;
        }
		
        $post_id = rvy_post_id((int) $screen['post_id']);

        switch ($rule['operator']) {
            case '==':
                return ($post_id == $rule['value']);
                break;
            case '!=':
                return ($post_id != $rule['value']);
                break;
        }

		return $result;
    }

    function actInitNonceWorkaroundACF() {
        $post_id = rvy_detect_post_id();
        $this->restoreACFpreviewNonce($post_id);
    }

    function fltACFpreviewWorkaround($num, $post ) {
        $this->restoreACFpreviewNonce($post->ID);
        return $num;
    }

    private function restoreACFpreviewNonce($post_id) {
        if (!empty($_POST) && !empty($_POST['wp-preview'])              //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        && rvy_in_revision_workflow($post_id) 
        && !defined('REVISIONARY_ACF_DEFAULT_PREVIEW_NONCE')
        ) {
            $action = 'post';
            $user  = wp_get_current_user();
            $uid   = (int) $user->ID;
            $token = wp_get_session_token();
            $i     = wp_nonce_tick( $action );
            $nonce_val = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );

            $_REQUEST['_acf_nonce'] = $nonce_val;
            $_POST['_acf_nonce'] = $nonce_val;
        }
    }

    /* --- Pro: support ACF Extended single_meta --- */
    function addACFEsupport() {
        // Pro: support ACF Extended single_meta
        if (function_exists('acf_get_setting') && acf_get_setting('acfe/modules/single_meta') && function_exists('acf_get_metadata')) {
            add_filter('revisionary_compare_meta_from', [$this, 'fltACFEcompareFrom'], 10, 2);
            add_filter('revisionary_compare_meta_to', [$this, 'fltACFEcompareTo'], 10, 2);
            add_filter('revisionary_compare_extra_fields', [$this, 'fltACFEadjustExtraFields'], 10, 2);
        }
    }

    function fltACFEcompareFrom($from_meta, $post_id) {
        $acf_from = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_from)) ? array_merge($from_meta, $acf_from) : $from_meta;
    }

    function fltACFEcompareTo($to_meta, $post_id) {
        $acf_to = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_to)) ? array_merge($to_meta, $acf_to) : $to_meta;
    }

    function fltACFEadjustExtraFields($extra_fields, $post_id) {
        unset($extra_fields['acf']);
        unset($extra_fields['_acf']);
        $acf_to = acf_get_metadata($post_id, 'acf');

        return array_merge($extra_fields, array_fill_keys(array_keys($acf_to), true));
    }

    function flt_revision_diff_ui($return, $compare_from, $compare_to, $args) {
        if (!is_array($args)) {
            return $return;
        }

        $compare_from_id = (!empty($compare_from)) ? $compare_from->ID : 0;
        $compare_to_id = (!empty($compare_to)) ? $compare_to->ID : 0;
        
        $to_meta = (isset($args['to_meta'])) ? apply_filters('revisionary_compare_meta_to', $args['to_meta'], $compare_to_id) : [];
        $meta_fields = (isset($args['meta_fields'])) ? $args['meta_fields'] : [];
        $native_fields = (isset($args['native_fields'])) ? $args['native_fields'] : [];
        $strip_tags = (isset($args['strip_tags'])) ? $args['strip_tags'] : [];
        $title = (!empty($args['title'])) ? $args['title'] . ': ' : '';
        $id_prefix = (!empty($args['id_prefix'])) ? $args['id_prefix'] : '';

        // Display other scalar meta fields
        if (isset($args['from_meta'])) {
            $from_meta = $args['from_meta'];
        } else {
            $from_meta = ($compare_from) ? get_post_meta($compare_from_id) : [];
        }

        $from_meta = apply_filters('revisionary_compare_meta_from', $from_meta, $compare_from_id);
        $extra_fields = (isset($args['extra_fields'])) ? $args['extra_fields'] : $to_meta;        $extra_fields = array_diff_key($extra_fields, $native_fields, $meta_fields, array_fill_keys(revisionary_unrevisioned_postmeta(), true));
        $extra_fields = apply_filters('revisionary_compare_extra_fields', array_fill_keys(array_keys($extra_fields), true), $compare_to_id);
        $key_captions = apply_filters('revisionary_meta_key_captions', ['_yoast_wpseo_' => 'Yoast SEO ', '_thumbnail_id' => esc_html__('Featured Image', 'revisionary-pro'), ''], $compare_to);
        $caption_keys = array_keys($key_captions);
        $caption_values = array_values($key_captions);
        ksort($extra_fields);
        $skip_meta_fields = apply_filters('revisionary_compare_skip_meta_fields', ['classic-editor-remember']);
        $saved_skip_meta_fields = get_option('rvy_compare_ignore_meta_fields', []);
        $skip_meta_fields = array_values(array_unique(array_merge(
            (array) $skip_meta_fields,
            is_array($saved_skip_meta_fields) ? $saved_skip_meta_fields : []
        )));

        foreach($extra_fields as $field => $name) {
            if ($skip_meta_prefixes = apply_filters('revisionary_unrevisioned_prefixes', [], $compare_to)) {
                foreach($skip_meta_prefixes as $prefix) {
                    if (0 === strpos($field, $prefix)) {
                        continue 2;
                    }
                }
            }
            
            if (in_array($field, $skip_meta_fields, true)) {
                continue;
            }

            $content_to = (isset($to_meta[$field])) ? $to_meta[$field] : '';
		    $content_to = maybe_unserialize($content_to);

		    // ===== TO META =====
            if (is_array($content_to)) {
                $any_nonscalar = false;
                foreach($content_to as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_to) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_to[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_to = implode(', ', $content_to);
                }
            }

            if (!is_scalar($content_to)) {
                continue;
            }
		   // =======================

		   // ===== FROM META =====
            if ($compare_from) {
                $content_from = (isset($from_meta[$field])) ? $from_meta[$field] : '';
            } else {
                $content_from = '';
            }

            if (is_array($content_from)) {
                $any_nonscalar = false;
                foreach($content_from as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_from) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_from[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_from = implode(', ', $content_from);
                }
            }

            if (!is_scalar($content_from)) {
                continue;
            }
		   // =======================

            $diff_args = array(
                'show_split_view' => !empty($args['suppress_to']) ? false : true
            );


            // @todo: map field names for caption hook

            $diff_args = apply_filters( 'revision_text_diff_options', $diff_args, $field, $compare_from, $compare_to );

            if ($strip_tags) {
                $content_from = wp_strip_all_tags($content_from);
                $content_to = wp_strip_all_tags($content_to);
            }

            if ('_thumbnail_id' == $name) {
                $content_from = ($content_from) ? "$content_from (" . wp_get_attachment_image_url($content_from, 'full') . ')' : '';
                $content_to = ($content_to) ? "$content_to (" . wp_get_attachment_image_url($content_to, 'full') . ')' : '';
            }

            if ($name !== true) {
                // field label applied by filter
                $field_name = $name;
            } else {
                $field_name = str_replace($caption_keys, $caption_values, $field);

                if ($field_name == $field) {
                    $field_name = trim(ucwords(str_replace('_', ' ', $field)));
                }
            }

            if ($diff = wp_text_diff( $content_from, $content_to, $diff_args )) {
                if (!empty($args['suppress_from'])) {
                    $diff = str_replace("class='diff-deletedline'", "class='diff-deletedline' style='display:none'", $diff);
                
                } elseif (!empty($args['suppress_to'])) {
                    $diff = str_replace("class='diff-addedline'", "class='diff-addedline' style='display:none'", $diff);
                }

                $return[] = array(
                    'id'   => $id_prefix . $field,
                    'name' => $title . $field_name,
                    'diff' => $diff,
                );

                $title = '';
            }
        }

        return $return;
    }

    function actRevisionApplied($post_id, $revision) {
        if (!function_exists('acf_save_post_revision')) {
            return;
        }

        if ($_post = get_post($post_id)) {
            if (!rvy_in_revision_workflow($_post) && ('inherit' != $_post->post_status)) {
                acf_save_post_revision($post_id, $revision->ID);
            }
        }
    }

    function flt_compare_meta_fields($meta_fields) {
        $meta_fields['_requested_slug'] = esc_html__('Requested Slug', 'revisionary-pro');
        
        if (defined('FL_BUILDER_VERSION') && defined('REVISIONARY_BEAVER_BUILDER_DIFF')) {
            $meta_fields['_fl_builder_data'] = esc_html__('Beaver Builder Data', 'revisionary-pro');
            $meta_fields['_fl_builder_data_settings'] = esc_html__('Beaver Builder Settings', 'revisionary-pro');
        }
    
        if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION') && rvy_get_option('allow_post_author_revision')) {
            $meta_fields['ppma_authors_name'] = esc_html__('Author(s)', 'revisionary-pro');
        }

        return $meta_fields;
    }

    public function actPodsCopyPostmeta($from_post, $to_post_id, $args = []) {
        global $wpdb;

        // Also copy Pods relationship fields
		if (defined('PODS_VERSION')) {
			$qry = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}podsrel WHERE item_id = %d",
				$from_post->ID
			);
	
			$results = $wpdb->get_results(                                          // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}podsrel WHERE item_id = %d",
					$from_post->ID
				)
			);
	
			foreach($results as $row) {
				$rel_data = array_diff_key(
					(array) $row,
					array_fill_keys(['id', 'pod_id', 'field_id', 'item_id'], true)
				);
	
				$rel_data = array_map('intval', $rel_data);
	
				$match_data = [
					'pod_id' => (int) $row->pod_id,
					'field_id' => (int) $row->field_id,
					'item_id' => (int) $to_post_id
				];
	
				if ($rel_id = (int) $wpdb->get_var(                                 // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}podsrel WHERE pod_id = %d AND field_id = %d AND item_id = %d",
							$match_data['pod_id'],
							$match_data['field_id'],
							$match_data['item_id']
						)
					)
				) {
					$wpdb->update(
						$pods_table,
						$rel_data,
						['id' => $rel_id],
						'%d',
						'%d'
					);
				} else {
					$wpdb->insert(                                                  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$pods_table,
						array_merge($rel_data, $match_data),
						'%d'
					);
				}
            }
            
            wp_cache_flush();
        }
    }
}
