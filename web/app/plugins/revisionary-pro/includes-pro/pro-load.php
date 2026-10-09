<?php

class RevisionaryPro {
    private static $instance = null;

    var $default_notification_workflows = false;
    private $notification_workflow_post = false;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new RevisionaryPro();
        }

        return self::$instance;
    }

    private function __construct() {
        global $script_name;

        add_filter('default_options_rvy', [$this, 'fltDefaultOptions']);
        add_filter('options_sitewide_rvy', [$this, 'fltDefaultOptionScope']);
        add_filter('wp_revisions_to_keep', [$this, 'fltMaybeSkipRevisionCreation'], 10, 2);

        add_filter('revisionary_main_post_statuses', [$this, 'fltMainPostStatuses'], 5, 2);
        add_filter('revisionary_preview_compare_view_caption', [$this, 'fltPreviewCompareViewCaption'], 10, 2);
        add_filter('revisionary_preview_view_caption', [$this, 'fltPreviewViewCaption'], 10, 2);

        require_once(__DIR__ . '/CacheCompatibility.php');
        new RevisionaryProCacheCompatibility();

        add_action('revisionary_front_init', [$this, 'loadACFtaxonomyPreviewFilters']);

        add_filter('revisionary_apply_revision_fields', [$this, 'fltApplyRevisionFields'], 10, 4);

        if (get_option('revisionary_pro_fix_default_notifications_meta_key')) {
            require_once(__DIR__ . '/NotificationDefaults.php');
            $notification_defaults = new \PublishPress\Revisions\NotificationDefaults();
            $notification_defaults->fixDefaultNotificationsMetaKey();
        }

        if (get_option('revisionary_pro_fix_default_notification_shortcodes')) {
            require_once(__DIR__ . '/NotificationDefaults.php');
            $notification_defaults = new \PublishPress\Revisions\NotificationDefaults();
            $notification_defaults->fixNotificationShortcodes();
        }

        if (get_option('revisionary_pro_fix_revision_scheduled_notification')) {
            require_once(__DIR__ . '/NotificationDefaults.php');
            $notification_defaults = new \PublishPress\Revisions\NotificationDefaults();
            $notification_defaults->fixRevisionScheduledNotification();
        }

        if (defined('PUBLISHPRESS_VERSION')) {    
            if (get_option('revisionary_pro_restore_notifications') && version_compare(PUBLISHPRESS_VERSION, '4.7.1-beta', '>=')
            && ((empty($script_name) || ('plugin-install.php' != $script_name)))
            ) {
                add_action('init', 
                    function() {
                        require_once(__DIR__ . '/NotificationDefaults.php');
                        
                        if (empty($notification_defaults)) {
                            $notification_defaults = new \PublishPress\Revisions\NotificationDefaults();
                        }
                        
                        $notification_defaults->restoreDefaultNotificationWorkflows();
                    }
                );
            }
            
            if (rvy_get_option('use_publishpress_notifications')) {
                if ($flush_notifications = version_compare(PUBLISHPRESS_REVISIONS_PRO_VERSION, '3.6.1', '<') && get_option('revisionary_pro_flush_notifications')) {
                    delete_option('revisionary_pro_flush_notifications');
                }

                $flush_notifications = $flush_notifications || defined('PUBLISHPRESS_STATUSES_FLUSH_NOTIFICATIONS');

                if ($activated = get_option('revisionary_pro_activate')) {
                    delete_option('revisionary_pro_activate');
                }

                $activated = $activated || did_action('revisionary_pro_activate');

                require_once(__DIR__ . '/RevisionNotifications.php');
                new \PublishPress\Revisions\RevisionNotifications();

                // Possible future use: selectively set / clear these options
                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /*
                if (defined('PUBLISHPRESS_REVISIONS_PRO_VERSION') && version_compare($last_ver, '3.6.0-rc6', '<')) {
                    update_option('revisionary_pro_flush_notifications', true);
                    delete_option('_pp_statuses_planner_default_revision_notifications');
                    delete_option('_pp_statuses_default_revision_notifications');
                }
                */

                if (!get_option('_pp_statuses_planner_default_revision_notifications') || $activated || !empty($flush_notifications)) {
                    require_once(__DIR__ . '/NotificationDefaults.php');
                    
                    if (empty($notification_defaults)) {
                        $notification_defaults = new \PublishPress\Revisions\NotificationDefaults();
                    }
                    
                    $notification_defaults->applyRevisionDefaults($flush_notifications);
                }
            } else {
                if (is_admin()) {
                    add_filter('posts_clauses_request', [$this, 'fltPostsClauses'], 50, 2);
                }
            }
        }

        add_action(
            'admin_print_scripts',
            function() {
                global $pagenow;

                if (is_admin() && !empty($pagenow) && ('admin.php' == $pagenow) 
                && !empty($_REQUEST['page']) && ('publishpress-statuses' == $_REQUEST['page'])                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                && !empty($_REQUEST['action']) && ('edit-status' == $_REQUEST['action'])                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                && !empty($_REQUEST['name']) && rvy_is_revision_status(sanitize_key($_REQUEST['name']))         // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                && !rvy_get_option('permissions_compat_mode')
                ) {
                    ?>
                    <script type="text/javascript">
                    /* <![CDATA[ */
                    jQuery(document).ready(function ($) {
                        $('#pp-post_access_table #status_capability_status').attr('disabled', true);

                        <?php
                        $url = admin_url('admin.php?page=revisionary-settings');
                        ?>

                        $('#pp-post_access_table #status_capability_status').siblings('div').first().hide().after
                        ('<div style="padding-top: 10px"><?php printf(
                            esc_html__('To control revision editing and deletion, please set %1$sRevisions > Post Types > Compatibility Mode%2$s to "Enhanced Revision access control"', 'revisionary-pro'),
                            '<a href="' . esc_url($url) . '" target="_blank">',
                            '</a>'
                        );?></div>');
                    });
                    /* ]]> */
                    </script>
                    <?php
                }
            }, 50
        );

        if (class_exists('WooCommerce')) {
            add_filter(
                'woocommerce_register_post_type_product', 
                function($args) {
                    global $revisionary;

                    if (!empty($revisionary->enabled_post_types_archive['product'])) {
                        $args['supports'][] = 'revisions';
                    }

                    return $args;
                }
            );
        }

        add_filter(
            'pp_statuses_postmeta_status_types',
            function ($status_types) {
                if (rvy_get_option('permissions_compat_mode')) {
                    $status_types = array_unique(array_merge($status_types, ['revision']));
                } else {
                    $status_types = array_diff($status_types, ['revision']);
                }

                return $status_types;
            }
        );

        add_filter('rvy_revision_statuses', [$this, 'fltRevisionStatusObjects'], 50, 2);
    }

    function fltRevisionStatusObjects($statuses, $args = []) {
        if (!empty($args['output']) && ('object' == $args['output'])) {
            foreach ($statuses as $k => $status) {
                if (!is_object($status) && defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && class_exists('PublishPress_Statuses')) {
                    if ($_status_obj = \PublishPress_Statuses::getStatusBy('slug', $status)) {
                        $statuses[$k] = $_status_obj;
                    } else {
                        unset($statuses[$k]);
                    }
                }
            }
        }
    
        return $statuses;
    }

    function fltPostsClauses($clauses, $_wp_query = false, $args = [])
    {
        global $pagenow, $typenow, $wpdb;

        if (!empty($pagenow) && ('edit.php' == $pagenow) && !empty($typenow) && ('psppnotif_workflow' == $typenow)) {
            if (!rvy_get_option('use_publishpress_notifications')) {
                $clauses['where'] .= " AND $wpdb->posts.post_name NOT IN ('revision-scheduled-publication', 'scheduled-revision-is-published', 'revision-scheduled', 'revision-is-scheduled', 'revision-declined', 'revision-deferred-or-rejected', 'revision-submission', 'revision-is-submitted', 'new-revision', 'new-revision-created', 'revision-status-changed', 'revision-is-applied', 'revision-is-published')";
            }
        }

        return $clauses;
    }

    function fltApplyRevisionFields($update_fields, $revision, $published, $actual_revision_status) {
        if ($published_status = get_post_status_object($published->post_status)) {
            if (empty($published_status->public) && empty($published_status->private) && (('future-revision' == $actual_revision_status) || rvy_get_option('publish_by_revision'))) {
                $update_fields['post_status'] = 'publish';
            }
        }

        return $update_fields;
    }

    function deleteSubposts($parent_post_id, $subpost_type, $args = []) {
        global $wpdb;

        $keep_clause = (!empty($args['keep_ids'])) ? "AND ID NOT IN ('" . implode("','", array_map('intval', (array) $args['keep_ids'])) . "')" : '';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $subposts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d $keep_clause",    //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $subpost_type,
                $parent_post_id
            )
        );

        if (!$subposts) {
            return;
        }

        foreach ($subposts as $subpost) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $wpdb->postmeta WHERE post_id = %d",
                    $subpost->ID
                )
            );

            wp_delete_post($subpost->ID);
        }
    }

    function copySubposts($source_parent_id, $target_parent_id, $subpost_type, $args=[]) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $subposts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d",
                $subpost_type,
                $source_parent_id
            )
        );

        if (!$subposts) {
            return [];
        }

        $copied_ids = [];

        $source_is_revision = rvy_in_revision_workflow($source_parent_id) || ('inherit' == get_post_field('post_status', $source_parent_id));

        foreach ($subposts as $subpost) {
			if (empty($subpost)) {
				continue;
			}
			
            $data = array_intersect_key(
                (array) $subpost, 
                array_fill_keys( 
                    ['post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'], 
                    true
                )
            );

            $data['post_parent'] = $target_parent_id;

            $target_subpost_id = 0;

            if ($source_is_revision) {
                if ($subpost_original_source_id = get_post_meta($subpost->ID, '_rvy_subpost_original_source_id', true)) {
                    if ($target_subpost = get_post($subpost_original_source_id)) {
                        $target_subpost_id = $target_subpost->ID;
                    }
                }
            }

            if (!empty($target_subpost_id)) {
                $wpdb->update($wpdb->posts, $data, ['ID' => $target_subpost_id]);                       // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            } else {
                $wpdb->insert($wpdb->posts, $data);                                                     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $target_subpost_id = (int)$wpdb->insert_id;
            }

            if ($target_subpost_id) {
                $copied_ids [$subpost->ID]= $target_subpost_id;

                revisionary_copy_postmeta($subpost->ID, $target_subpost_id, ['apply_deletions' => true]);

                if (!$source_is_revision) {
                    update_post_meta($target_subpost_id, '_rvy_subpost_original_source_id', $subpost->ID);
                }
            }
        }

        return $copied_ids;
    }

    function getSubposts($parent_id, $subpost_type) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $subposts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d",
                $subpost_type,
                $parent_id
            )
        );

        return $subposts;
    }

    function fltDefaultOptions($options) {
        $options['pending_revision_unpublished'] = 0;
        $options['publish_by_revision'] = 0;
        $options['beaver_builder_revisor_access'] = 1;
        $options['prevent_rest_revisions'] = 0;
        $options['unfiltered_preview_links'] = 0;
        $options['num_revisions'] = (defined('WP_POST_REVISIONS')) ? WP_POST_REVISIONS : 0;;
        return $options;
    }

    function fltDefaultOptionScope($options) {
        $options['pending_revision_unpublished'] = true;
        $options['publish_by_revision'] = true;
        $options['prevent_rest_revisions'] = true;
        $options['unfiltered_preview_links'] = true;
        $options['num_revisions'] = true;
        return $options;
    }

    function fltMaybeSkipRevisionCreation($num, $post) {	
		if (class_exists('ACF') && rvy_get_option('prevent_rest_revisions')) {	
			$arr_url = wp_parse_url(get_option('siteurl'));
			
			if ($arr_url && isset($arr_url['path'])) {
				if (!empty($_SERVER['REQUEST_URI'])) {
                    if (0 === strpos(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), $arr_url['path'] . '/wp-json/wp/')) {
                        $num = 0;
                    }
                }
			}
		}

		return $num;
    }
    
    // @todo: Are these ACF filters still needed with Revisions 3.0 submission mechanism?
    
    function loadACFtaxonomyPreviewFilters() {
        // Some ACF implementations cause the current revision (post_status = 'inherit') to be loaded as queried object prior to taxonomy field value retrieval
		// However, don't force revision_id elsewhere because main post / current revision ID seems to be required for some other template rendering. 
		add_filter("acf/load_value", [$this, 'fltACFenablePostFilter'], 1);
		add_filter("acf/load_value", [$this, 'fltACFdisablePostFilter'], 9999);
    }

    public function fltACFenablePostFilter($val) {
		add_filter("acf/decode_post_id", [$this, 'fltACFdecodePostID'], 10, 2);
		return $val;
	}

	public function fltACFdisablePostFilter($val) {
		remove_filter("acf/decode_post_id", [$this, 'fltACFdecodePostID'], 10, 2);
		return $val;
	}

    public function fltACFdecodePostID($args, $post_id) {
        if (!in_array($args["type"], ['option', 'block', 'comment', 'term', 'user', 'woo_order'])) {
            if ($detect_post_id = rvy_detect_post_id()) {
                // Replace with detected post ID only if it is a revision of specified post.
                if (!empty($args['id'])) {
                    if ($main_post_id = rvy_post_id($detect_post_id)) {
                        if (($main_post_id != $detect_post_id) && ($main_post_id == $args['id'])) {
                            $args['id'] = $detect_post_id;
                        }
                    }
                }
            }
        }

        return $args;
    }

    function fltPreviewCompareViewCaption($caption, $revision) {
        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = esc_html__("%sCompare%s%sView Current Draft%s", 'revisionary-pro');
        }

        $caption = str_replace( ' ', '&nbsp;', $caption);

        return $caption;
    }

    function fltPreviewViewCaption($caption, $revision) {

        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = esc_html__("%sView Current Draft%s", 'revisionary-pro');
        }

        $caption = str_replace( ' ', '&nbsp;', $caption);

        return $caption;
    }

    function fltMainPostStatuses($statuses, $return = 'object') {
        if (rvy_get_option('pending_revision_unpublished')) {
            $statuses = get_post_stati( ['internal' => false], $return );
        }

        return $statuses;
    }
}
