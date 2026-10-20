<?php
namespace PublishPress\Permissions\Compat;

class ACF {
    var $acf_taxonomies = [];
    var $assign_terms = [];
    var $blocked_fields = [];

    function __construct() {
        if (!defined('PRESSPERMIT_DISABLE_ACF_TAXONOMY_FILTERS')) {
            add_filter('acf/update_value', [$this, 'fltTaxonomyTerm'], 10, 4);
            add_action('save_post', [$this, 'actSavePost'], 5, 2);

            // Pre-insert terms on post publication to avoid chicken-egg term restrictions for editing access
            add_action('check_admin_referer', [$this, 'act_check_admin_referer']);

            if (PWP::isFront()) {
                add_action('presspermit_init', [$this, 'actApplyFieldGroupFilters']);
                add_filter('acf/pre_load_value', [$this, 'fltBlockFieldValue'], 10, 3);
            }

            add_filter('presspermit_item_edit_exception_ops', [$this, 'fltItemEditExceptionOps'], 10, 4);
            add_filter('presspermit_exception_operations', [$this, 'fltLimitFieldGroupExceptionOperations'], 10, 3);
            add_filter('presspermit_exception_modes', [$this, 'fltLimitFieldGroupModTypes'], 10, 4);
        }
    }

    function fltItemEditExceptionOps($operations, $for_item_source, $for_item_type, $via_item_type = '')
    {
        if ('post' == $for_item_source && 'acf-field-group' == $for_item_type) {
            // Hide all ops for acf-field-group
            $operations = [];
        }

        return $operations;
    }

    function fltLimitFieldGroupExceptionOperations($ops, $for_source_name, $for_type) {
        if ('acf-field-group' == $for_type) {
            $ops = array_intersect_key($ops, array_fill_keys(['read'], true));
        }

        return $ops;
    }

    function fltLimitFieldGroupModTypes($modes, $for_source_name, $for_type, $operation) {
        if ('acf-field-group' == $for_type) {
            $modes = array_intersect_key($modes, array_fill_keys(['additional', 'exclude'], true));
        }

        return $modes;
    }

    // Apply Field Group read blockages
    function actApplyFieldGroupFilters() {
        if (!in_array('acf-field-group', presspermit()->getEnabledPostTypes(), true)) {
            return;
        }

        $acf_field_store = acf_get_store('fields');

        $user = presspermit()->getUser();
        
        $blocked_field_groups = $user->getExceptionPosts('read', 'exclude', 'acf-field-group');

        if ($blocked_field_groups && (!defined('PP_RESTRICTION_PRIORITY') || ! PP_RESTRICTION_PRIORITY)) {
            $blocked_field_groups = array_diff(
                $blocked_field_groups,
                $user->getExceptionPosts('read', 'additional', 'acf-field-group')
            );
        }

        foreach ($blocked_field_groups as $field_group_post_id) {
            if ($field_group = acf_get_field_group($field_group_post_id)) {
                $fields = acf_get_fields($field_group);

                foreach ($fields as $group_field) {
                    $acf_field_store->set($group_field['key'], false);
                    $this->blocked_fields []= $group_field['name'];
                }
            }
        }

        return;
    }

    function fltBlockFieldValue($field_val, $post_id, $field) {
        if (in_array($field['name'], $this->blocked_fields)) {
            return false;
        }

        return $field_val;
    }


    function fltTaxonomyTerm($value, $post_id, $field, $orig_value) {
        if (('taxonomy' == $field['type']) && !empty($field['taxonomy'])) {
            if (in_array($field['taxonomy'], presspermit()->getEnabledTaxonomies())) {
                $this->acf_taxonomies[$field['taxonomy']] = true;
                
                require_once(PRESSPERMIT_COLLAB_CLASSPATH . '/PostTermsSave.php');

                $value = (array) $value;
                
                $term_ids = [];

                foreach ($value as $term) {
                    if (is_object($term)) {
                        $use_objects = true;
                        $term_ids []= $term->term_id;
                    } else {
                        $term_ids []= $term;
                    }
                }

                $term_ids = \PublishPress\Permissions\Collab\PostTermsSave::fltTermsInput($term_ids, $field['taxonomy']);

                if (!empty($use_objects)) {
                    $value = [];

                    foreach ($term_ids as $term_id) {
                        $value []= get_term($term_id, $field['taxonomy']);
                    }
                } else {
                    $value = $term_ids;
                }
            }
        }

        return $value;
    }

    public function act_check_admin_referer($referer_name) {
        global $current_user;
        
        if (0 === strpos($referer_name, 'update-post_')) {
            $post_id = str_replace('update-post_', '', $referer_name);

            if ($_post = get_post($post_id)) {
                if (($_post->post_author == $current_user->ID)
                && ('auto-draft' == $_post->post_status) 
                || !empty($_REQUEST['auto_draft'])                                                                      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                || (!empty($_REQUEST['original_post_status']) && ('auto-draft' == $_REQUEST['original_post_status']))   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ) {
                    if (in_array($_post->post_type, presspermit()->getEnabledPostTypes(), true)) {
                        $this->actSavePost($post_id, $_post, ['is_auto_draft' => true]);
                    }
                }
            }
        }
    }

    function actSavePost($post_id, $post = false, $args = []) {
        if (empty($post) || ('revision' == $post->post_type) || !presspermit()->getOption('auto_assign_available_term')) {
            return;
        }
        
        $pp_enabled_taxonomies = presspermit()->getEnabledTaxonomies();

        $field_groups = acf_get_field_groups(['post_id' => $post_id]);

        foreach ($field_groups as $field_group) {

            $fields = acf_get_fields($field_group['ID']);

            foreach ($fields as $field) {
                // If acf/update_value was not fired for a taxonomy field, apply filter for default terms
                if (('taxonomy' == $field['type']) && !empty($field['taxonomy'])) {
                    if (in_array($field['taxonomy'], $pp_enabled_taxonomies)) {
                        require_once(PRESSPERMIT_COLLAB_CLASSPATH . '/PostTermsSave.php');

                        if (isset($_REQUEST['acf']) && isset($_REQUEST['acf'][$field['key']])) {                                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            $terms = array_filter(array_map('sanitize_text_field', (array) $_REQUEST['acf'][$field['key']]));   // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                            foreach ($terms as $k => $_term) {
                                if (is_numeric($_term)) {
                                    $terms[$k] = (int) $_term;
                                }
                            }

                            $posted_terms = true;
                        } else {
                            $terms = wp_get_object_terms($post_id, $field['taxonomy'], ['fields' => 'ids']);
                        }

                        if (is_array($terms)) {
                            if (empty($this->acf_taxonomies[$field['taxonomy']]) || $posted_terms || (!$posted_terms && empty($terms))) {
                                
                                $new_terms = (array) \PublishPress\Permissions\Collab\PostTermsSave::fltTermsInput($terms, $field['taxonomy'], $args);

                                if ($new_terms && (array_diff($new_terms, $terms) || array_diff($terms, $new_terms) || !empty($posted_terms))) {
                                    wp_set_object_terms($post_id, $new_terms, $field['taxonomy']);

                                    // Newly inserted terms may not be retrieved by immediately subsequent posts query
                                    do_action('presspermit_bypass_term_restrictions');
                                    do_action('presspermit_bypass_term_restrictions_' . $post_id);

                                    if (!isset($this->assign_terms[$post_id])) {
                                        $this->assign_terms[$post_id] = [];
                                    }

                                    $this->assign_terms[$post_id][$field['taxonomy']] = $new_terms;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($this->assign_terms)) {
            // Compensate for any downstream code that strips out stored terms
            add_action('shutdown', function() {
                foreach ($this->assign_terms as $post_id => $taxonomies) {
                    foreach($taxonomies as $taxonomy => $assign_terms) {
                        wp_set_post_terms($post_id, $assign_terms, $taxonomy);
                    }
                }
            }, 20);
        }
    }
}
