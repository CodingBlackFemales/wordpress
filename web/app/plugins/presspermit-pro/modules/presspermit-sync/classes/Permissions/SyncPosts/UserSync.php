<?php
namespace PublishPress\Permissions\SyncPosts;

/**
 * UserSync class
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2024, PublishPress
 *
 */
class UserSync
{
    var $log = [];

    /**
     * Parse template string and replace placeholders with user data
     * 
     * @param string $template Template string with [placeholder] syntax
     * @param object $user WP_User object
     * @param array $context Additional context data
     * @return string Parsed template with values replaced
     */
    private function parseUserTemplate($template, $user, $context = [])
    {
        // If no placeholders found, return as-is (backward compatibility)
        if (strpos($template, '[') === false) {
            return $template;
        }

        $user_id = is_object($user) ? $user->ID : $user;
        if (!is_object($user)) {
            $user = new \WP_User($user_id);
        }

        // Build replacement map
        $replacements = [
            '[ID]' => $user->ID,
            '[user_login]' => $user->user_login,
            '[user_email]' => $user->user_email,
            '[user_nicename]' => $user->user_nicename,
            '[display_name]' => $user->display_name,
            '[first_name]' => get_user_meta($user->ID, 'first_name', true),
            '[last_name]' => get_user_meta($user->ID, 'last_name', true),
            '[nickname]' => get_user_meta($user->ID, 'nickname', true),
        ];

        // Add context variables
        if (!empty($context['field_value'])) {
            $replacements['[field_value]'] = $context['field_value'];
        }
        if (!empty($context['quantity_number'])) {
            $replacements['[quantity_number]'] = $context['quantity_number'];
        }

        // Find all custom placeholders [custom_field]
        preg_match_all('/\[([^\]]+)\]/', $template, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $field_name) {
                $placeholder = '[' . $field_name . ']';
                
                // Skip if already in replacements
                if (isset($replacements[$placeholder])) {
                    continue;
                }
                
                // Try to get as user meta
                $meta_value = get_user_meta($user->ID, $field_name, true);
                if ($meta_value !== '') {
                    $replacements[$placeholder] = $meta_value;
                } else {
                    // Leave empty if meta doesn't exist
                    $replacements[$placeholder] = '';
                }
            }
        }

        // Apply filter to allow custom replacements
        $replacements = apply_filters(
            'presspermit_sync_user_template_replacements',
            $replacements,
            $user,
            $template,
            $context
        );

        // Replace all placeholders
        $result = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $result;
    }

    /**
     * Check if a field specification is a template
     * 
     * @param string $field_spec Field specification
     * @return bool True if template, false if simple field
     */
    private function isTemplate($field_spec)
    {
        return (strpos($field_spec, '[') !== false && strpos($field_spec, ']') !== false);
    }

    function newUser($user_id, $role_name = '', $blog_id = '')
    {
        // Use the new sync_posts_to_users_new option instead of legacy sync_posts_to_users_types
        if ($post_types = presspermit()->getOption('sync_posts_to_users_new')) {
            if ($post_types = array_diff($post_types, [false, 0, '0'])) {
                if (!$role_name) {
                    $user = new \WP_User($user_id);
                    $role_name = $user->get_role();
                }

                $this->syncPostsToUsers(
                    ['sync_user_id' => $user_id, 'new_user_role' => $role_name, 'post_types' => array_keys($post_types)]
                );
            }
        }
    }

    function syncPostsToUsers($args = [])
    {
        global $wpdb;

        $pp = presspermit();

        $defaults = ['sync_user_id' => 0, 'post_types' => [], 'new_user_role' => ''];
        $args = array_merge($defaults, (array)$args);

        $post_types = (array)$args['post_types'];
        $sync_user_id = $args['sync_user_id'];
        $new_user_role = $args['new_user_role'];

        if (!$post_types) return;

        // note: only support postmeta and users columns for now

        foreach ($post_types as $post_type) {
            if (!post_type_exists($post_type)) continue;
            $post_type_obj = get_post_type_object($post_type);

            $pp = presspermit();

            $post_field = $pp->getTypeOption('sync_posts_to_users_post_field', $post_type);
            $user_field = $pp->getTypeOption('sync_posts_to_users_user_field', $post_type);
            $roles = $pp->getTypeOption('sync_posts_to_users_role', $post_type);
            $quantity = (int) $pp->getTypeOption('sync_posts_to_users_quantity', $post_type);
            $status = $pp->getTypeOption('sync_posts_to_users_status', $post_type);

            // Handle both single role (legacy) and multiple roles (new)
            if (!is_array($roles)) {
                $roles = $roles ? [$roles] : [];
            }

            if (empty($roles)) {
                $this->log[] = sprintf(esc_html__('%s: Skipping because no roles are selected', 'presspermit-pro'), esc_html($post_type_obj->label));
                do_action('presspermit_sync_post_to_users_no_role_selected', $post_type, $post_field, $user_field);
                continue;
            }

            if (!$post_field || !$user_field) continue;

            // Default to 1 if quantity not set or invalid
            if (!$quantity || $quantity < 1) {
                $quantity = 1;
            }

            // Check if new user role matches any of the selected roles
            $role_match = false;
            foreach ($roles as $role) {
                if (!$role) continue;
                
                // new user registration specifies selected role for new user
                if ($role && $new_user_role && ($role != '(any)') && ($role != $new_user_role)) continue;
                
                $role_match = true;
                break;
            }

            if (!$role_match && $new_user_role) continue;

            if ($pp->getOption('sync_posts_to_users_apply_permissions')) {
                // turn on PP filtering for this post type
                $filtered_post_types = $pp->getOption('enabled_post_types');
                if (empty($filtered_post_types[$post_type])) {
                    $filtered_post_types[$post_type] = 1;
                    $pp->updateOption('enabled_post_types', $filtered_post_types);

                    $this->log[] = sprintf(
                        esc_html__('%s: Enabled PressPermit filtering (Core > Filtered Post Types)', 'presspermit-pro'), 
                        esc_html($post_type_obj->label)
                    );
                }

                // ensure these roles have supplemental author roles for the post type
                foreach ($roles as $role) {
                    if (!$role || $role == '(any)') continue;
                    
                    if ($group_id = $pp->groups()->getMetagroup('wp_role', $role, ['cols' => 'id'])) {
                        $supplemental_roles = $pp->getRoles($group_id, 'pp_group');

                        $pp_role_name = "author:post:{$post_type}";

                        if (empty($supplemental_roles[$pp_role_name])) {
                            $pp->assignRoles([$pp_role_name => [$group_id => true]], 'pp_group');

                            $this->log[] = sprintf(
                                esc_html__('%1$s: Assigned extra %2$s Author role to %3$s role', 'presspermit-pro'), 
                                esc_html($post_type_obj->label), 
                                esc_html($post_type_obj->labels->singular_name), 
                                ucwords($role)
                            );
                        }
                    }
                }
            }

            if ($post_parent = $pp->getTypeOption('sync_posts_to_users_post_parent', $post_type)) {
                $parent_obj = get_post($post_parent);
                if (empty($parent_obj) || ('trash' == $parent_obj->post_status)) {
                    $post_parent = 0;
                }
            }

            // Build user search args for multiple roles
            $has_any_role = in_array('(any)', $roles);
            $specific_roles = array_diff($roles, ['(any)', '']);
            
            $user_search_args = [];
            
            if (!$has_any_role && !empty($specific_roles) && !$new_user_role) {
                // Use role__in for multiple specific roles
                if (count($specific_roles) > 1) {
                    $user_search_args['role__in'] = $specific_roles;
                } else {
                    $user_search_args['role'] = $specific_roles[0];
                }
            }

            $user_search_args['fields'] = ['ID', 'user_login', 'user_email', 'user_nicename', 'display_name'];

            if ($sync_user_id) {
                $user_search_args['search'] = $sync_user_id;
                $user_search_args['search_columns'] = ['ID'];
            }
            $user_query = new \WP_User_Query($user_search_args);

            $users = apply_filters(
                'presspermit_sync_posts_to_users_user_results', 
                $user_query->get_results(), 
                $post_type, 
                $user_field, 
                $roles // Pass the roles array instead of single role
            );

            $users_display_name = [];
            $users_nicename = [];
            foreach ($users as $u) {
                $users_display_name[$u->ID] = $u->display_name;
                $users_nicename[$u->ID] = $u->user_nicename;
            }

            $users_by_field = [];
            $is_template = $this->isTemplate($user_field);

            if ($is_template) {
                // User field is a template - generate field values from template
                foreach ($users as $u) {
                    $field_val = $this->parseUserTemplate($user_field, $u, compact('post_type', 'user_field', 'post_field'));
                    $users_by_field[$field_val] = $u->ID;
                }
            } elseif ($users 
            && !in_array($user_field, ['ID', 'user_login', 'user_email', 'user_nicename', 'display_name'], true)
            ) {
                // User field is a custom meta field
                $user_id_csv = implode(',', array_map('intval', array_keys($users_nicename)));

                // phpcs Note: Direct query of users table on plugin admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT user_id, meta_value FROM $wpdb->usermeta"
                        . " WHERE user_id IN ('$user_id_csv') AND meta_key = %s",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $user_field
                    )
                );

                foreach ($results as $row) {
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                    $users_by_field[$row->meta_value] = $row->user_id;
                }
            } else {
                // User field is a standard WP user field
                foreach ($users as $u) {
                    $field_val = ('display_name' == $user_field) 
                    ? apply_filters('presspermit_sync_posts_to_users_post_title', $u->$user_field, $u->ID, compact('post_type', 'user_field', 'post_field'))
                    : $u->$user_field;

                    $users_by_field[$field_val] = $u->ID;
                }
            }

            if (in_array($post_field, ['post_title', 'post_name'], true)) {
                // $post_field is a column in posts table, but alias it as meta_value for results processing 

                // phpcs Note: Direct query of users table on plugin admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $posts = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT ID, post_author, post_title, post_title, post_name as meta_value FROM $wpdb->posts 
                        WHERE post_type = '%s' AND post_status != 'trash'",

                        $post_type
                    )
                );
            } else {
                // phpcs Note: Direct query of users table on plugin admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $posts = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT p.ID, p.post_author, p.post_title, pm.meta_value FROM $wpdb->posts AS p
                        LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id 
                        WHERE post_type = '%s' AND post_status != 'trash' AND pm.meta_key = '%s'",

                        $post_type, 
                        $post_field
                    )
                );
            }

            $posts_by_field = [];
            $posts_title = [];
            $posts_author = [];
            foreach ($posts as $p) {
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                $posts_by_field[$p->meta_value] = $p->ID;
                $posts_title[$p->ID] = $p->post_title;
                $posts_author[$p->ID] = $p->post_author;
            }

            // === update post_author value of existing posts ===

            if ($matched_posts = array_intersect_key($posts_by_field, $users_by_field)) {

                // make sure we don't sync on a non-unique post match
                if ('post_title' == $post_field) {

                    foreach (array_keys($matched_posts) as $post_title) {
                        // make sure we don't sync on a non-unique post match
                        $post_title_instances = array_intersect($posts_title, [$post_title]);
                        if (count($post_title_instances) > 1) {
                            $this->log[] = sprintf(
                                esc_html__('Ambiguous %1$s match (%2$s)', 'presspermit-pro'), 
                                esc_html($post_type_obj->labels->singular_name), 
                                esc_html($post_title)
                            );
                            
                            do_action(
                                'presspermit_sync_post_to_users_ambiguous_post_match', 
                                $post_type, 
                                $post_field, 
                                $post_title, 
                                $post_title_instances
                            );
                            
                            unset($matched_posts[$post_title]);
                        }
                    }
                }

                // make sure we don't sync on a non-unique user match
                if ('display_name' == $user_field) {

                    foreach ($matched_posts as $display_name => $post_id) {
                        if ($sync_user_id) {
                            $user_search_args['search'] = $display_name;
                            $user_search_args['search_columns'] = ['display_name'];
                            $display_name_query = new \WP_User_Query($user_search_args);
                            $display_name_users = $display_name_query->get_results();

                            if (count($display_name_users) > 1) {
                                $this->log[] = sprintf(
                                    esc_html__('Ambiguous user match for %1$s (%2$s, post id %3$s', 'presspermit-pro'), 
                                    esc_html($post_type_obj->labels->singular_name), 
                                    esc_html($display_name), 
                                    (int) $post_id
                                );
                                
                                do_action(
                                    'presspermit_sync_post_to_users_ambiguous_user_match', 
                                    $post_type, 
                                    $user_field, 
                                    $display_name_users, 
                                    compact('post_id', 'sync_user_id')
                                );
                               
                                return;
                            }
                        } else {
                            if (count(array_intersect($users_display_name, [$display_name])) > 1) {
                                // WP_User_Query for do_action argument consistent with user_register case
                                $user_search_args['search'] = $display_name;
                                $user_search_args['search_columns'] = ['display_name'];
                                $display_name_query = new \WP_User_Query($user_search_args);
                                $display_name_users = $display_name_query->get_results();

                                $this->log[] = sprintf(
                                    esc_html__('Ambiguous user match for %1$s (%2$s, $post id %3$s)', 'presspermit-pro'), 
                                    esc_html($post_type_obj->labels->singular_name), 
                                    esc_html($display_name), 
                                    (int) $post_id
                                );
                                
                                do_action(
                                    'presspermit_sync_post_to_users_ambiguous_user_match', 
                                    $post_type, 
                                    $user_field, 
                                    $display_name_users, 
                                    compact('post_id')
                                );
                                
                                unset($matched_posts[$display_name]);
                            }
                        }
                    }
                }
            }

            if ( empty( $allow_field_update ) ) {
                $allow_field_update = apply_filters( 
                    'presspermit_sync_posts_to_users_allow_non_unique_field_sync', 
                    false, 
                    $post_type, 
                    $post_field, 
                    $user_field 
                );
            }

            $update_count = 0;

            foreach ($matched_posts as $field_value => $post_id) {
                if (!$field_value) continue;

                do_action('presspermit_sync_posts_to_users_matched_post', $post_id, $users_by_field[$field_value]);

                $author_id = (isset($posts_author[$post_id])) ? $posts_author[$post_id] : 0;
                if ($author_id != $users_by_field[$field_value]) {
                    if ($author_id) {
                        if (!isset($administrator_ids)) {
                            $user_query = new \WP_User_Query(['role' => 'administrator', 'fields' => 'ID']);
                            $administrator_ids = $user_query->get_results();
                        }

                        // if this post already has a non-Administrator author set, don't re-sync
                        if (!in_array($author_id, $administrator_ids)) {
                            do_action(
                                'presspermit_sync_posts_to_users_skip_post_sync', 
                                $post_id, 
                                $post_type, 
                                $post_field, 
                                $user_field
                            );
                            
                            continue;
                        }
                    }

                    wp_update_post(['ID' => $post_id, 'post_author' => $users_by_field[$field_value]]);
                    
                    do_action('presspermit_sync_posts_to_users_updated_post', $post_id, $users_by_field[$field_value]);
                    $update_count++;
                }
            }

            // create new posts for unmatched users
            $configured_status = !empty($status) ? $status : 'publish'; // Default to publish if not configured
            $post_status = apply_filters('presspermit_sync_posts_to_users_post_status', $configured_status, $post_type);
            $post_content = apply_filters('presspermit_sync_posts_to_users_post_content', '', $post_type);

            $unmatched_users = array_diff_key($users_by_field, $matched_posts);

            $insert_count = 0;
            foreach ($unmatched_users as $field_value => $user_id) {
                if (!$field_value) continue;

                // Get user object for template parsing
                $user = new \WP_User($user_id);

                // Create multiple posts per user based on quantity setting
                for ($i = 1; $i <= $quantity; $i++) {
                    // Build context for template parsing
                    $template_context = [
                        'field_value' => $field_value,
                        'quantity_number' => $i,
                        'post_type' => $post_type,
                        'user_field' => $user_field,
                        'post_field' => $post_field,
                        'quantity' => $quantity,
                        'current_number' => $i
                    ];

                    // Generate title - use template if user_field is a template
                    if ($is_template) {
                        $post_title = $this->parseUserTemplate($user_field, $user, $template_context);
                    } else {
                        $post_title = $field_value;
                    }
                    
                    // If creating multiple posts, add number suffix to title
                    if ($quantity > 1) {
                        $post_title .= " #{$i}";
                    }
                    
                    $post_title = apply_filters('presspermit_sync_posts_to_users_post_title', $post_title, $user_id, $template_context);

                    // Use field_value for post_name (slug) as well
                    // Sanitize it to make it a valid slug
                    $post_name_base = sanitize_title($field_value);
                    $post_name = ($quantity > 1) ? $post_name_base . '-' . $i : $post_name_base;

                    $post_arr = [
                        'post_type' => $post_type, 
                        'post_author' => $user_id, 
                        'post_name' => $post_name, 
                        'post_title' => $post_title, 
                        'post_content' => $post_content, 
                        'post_parent' => $post_parent, 
                        'post_status' => $post_status
                    ];
                
                    $post_arr = apply_filters(
                        'presspermit_sync_posts_to_users_insert_post_data', 
                        $post_arr, 
                        $post_type, 
                        $user_id, 
                        $field_value, 
                        $post_field
                    );

                    if ('nickname' == $user_field) {
                        $post_arr['post_name'] = $field_value;
                    }

                    if (empty($post_arr)) continue;

                    // make sure we don't sync on a non-unique user match
                    if ('display_name' == $user_field) {
                        foreach (array_keys($unmatched_users) as $display_name) {
                            if ($sync_user_id) {
                                $user_search_args['search'] = $display_name;
                                $user_search_args['search_columns'] = ['display_name'];
                                $display_name_query = new \WP_User_Query($user_search_args);
                                $display_name_users = $display_name_query->get_results();

                                if (count($display_name_users) > 1) {
                                    $this->log[] = sprintf(
                                        esc_html__('Ambiguous user match for %1$s (%2$s)', 'presspermit-pro'), 
                                        esc_html($post_type_obj->labels->singular_name), 
                                        esc_html($display_name)
                                    );
                                    
                                    do_action(
                                        'presspermit_sync_post_to_users_ambiguous_user_match', 
                                        $post_type, 
                                        $user_field, 
                                        $display_name_users, 
                                        compact('sync_user_id')
                                    );
                                    
                                    continue 2; // Continue outer user loop, skip this quantity iteration
                                }
                            } else {
                                if (count(array_intersect($users_display_name, [$display_name])) > 1) {
                                    // WP_User_Query for do_action argument consistent with user_register case
                                    $user_search_args['search'] = $display_name;
                                    $user_search_args['search_columns'] = ['display_name'];
                                    $display_name_query = new \WP_User_Query($user_search_args);
                                    $display_name_users = $display_name_query->get_results();

                                    $this->log[] = sprintf(
                                        esc_html__('Ambiguous user match for %1$s (%2$s)', 'presspermit-pro'), 
                                        esc_html($post_type_obj->labels->singular_name), 
                                        esc_html($display_name)
                                    );
                                    
                                    do_action(
                                        'presspermit_sync_post_to_users_ambiguous_user_match', 
                                        $post_type, 
                                        $user_field, 
                                        $display_name_users, 
                                        []
                                    );
                                    
                                    continue 2; // Continue outer user loop, skip this quantity iteration
                                }
                            }
                        }
                    }

                    if ($post_id = wp_insert_post($post_arr)) {
                        if (!in_array($post_field, ['post_title', 'post_name'])) {
                        	add_post_meta($post_id, $post_field, $field_value);
                        }

    					add_post_meta($post_id, '_pp_sync_author_id', $user_id);
                        // Add quantity metadata for tracking
                        if ($quantity > 1) {
                            add_post_meta($post_id, '_pp_sync_quantity_number', $i);
                        }
                        do_action('presspermit_sync_posts_to_users_added_post', $post_id, $user_id, $post_type);
                        $insert_count++;
                    }
                } // End quantity loop
            } // End unmatched users loop

            if ($insert_count) $this->log[] = sprintf(
                esc_html__('%1$s: %2$s posts created.', 'presspermit-pro'), 
                esc_html($post_type_obj->label), 
                (int) $insert_count
            );

            if ($update_count) $this->log[] = sprintf(
                esc_html__('%1$s: %2$s posts updated.', 'presspermit-pro'), 
                esc_html($post_type_obj->label), 
                (int) $update_count
            );

            if (!empty($specific_roles)) {
                global $wp_roles;
                
                $role_labels = [];
                foreach ($specific_roles as $role) {
                    $role_labels[] = (!empty($wp_roles) && !empty($wp_roles->role_names[$role])) 
                        ? $wp_roles->role_names[$role] 
                        : $role;
                }

                if ($has_any_role) {
                    $role_labels[] = esc_html__('All Roles', 'presspermit-pro');
                }

                $role_display = implode(', ', $role_labels);
                $this->log[] = sprintf(esc_html__('%1$s: Done for %2$s.', 'presspermit-pro'), esc_html($post_type_obj->label), esc_html($role_display));
            } else {
                $this->log[] = sprintf(esc_html__('%s: Done.', 'presspermit-pro'), esc_html($post_type_obj->label));
            }

            do_action('presspermit_sync_posts_to_users_done', $post_type, $update_count, $insert_count);
        }
    }
}
