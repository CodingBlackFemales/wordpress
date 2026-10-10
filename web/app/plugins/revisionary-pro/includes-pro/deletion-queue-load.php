<?php
class RevisionaryProDeletionQueueAdmin {
    function __construct() {
        add_filter('page_row_actions', [$this, 'fltPostActionLinks'], 20);
        add_filter('post_row_actions', [$this, 'fltPostActionLinks'], 20);

        add_filter('bulk_post_updated_messages',[$this, 'fltBulkMessages'], 10, 2);

        add_action('pp_revisions_admin_init', [$this, 'actRevisionsAdminInit']);
        add_action('revisionary_handle_admin_action', [$this, 'fltHandleAdminAction'], 10, 3);

        add_action('revisionary_admin_menu', [$this, 'actRevisionaryAdminMenu']);

        if (!empty($_REQUEST['requested_deletion_count'])) {                            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $_REQUEST['deleted'] = (int) $_REQUEST['requested_deletion_count'];         //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if (!empty($_REQUEST['posts_deleted'])) {                                       //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $_REQUEST['deleted'] = (int) $_REQUEST['posts_deleted'];                    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
    }

    function fltBulkMessages($bulk_messages, $bulk_counts) {
        global $typenow;
        
        if (!empty($_REQUEST['requested_deletion_count'])) {                            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $count = intval($_REQUEST['requested_deletion_count']);                     //phpcs:ignore WordPress.Security.NonceVerification.Recommended

            $bulk_messages['post']['deleted'] = sprintf(esc_html(_n( '%s deletion requested.', '%s deletions requested.', $count, 'revisionary' )), $count);
            
            if (!empty($typenow)) {
                $bulk_messages[$typenow]['deleted'] = $bulk_messages['post']['deleted'];
            }
        }

        if (!empty($_REQUEST['posts_deleted'])) {                                       //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $count = intval($_REQUEST['posts_deleted']);                                //phpcs:ignore WordPress.Security.NonceVerification.Recommended

            $bulk_messages['post']['deleted'] = sprintf(esc_html(_n( '%s post deleted.', '%s posts deleted.', $count, 'revisionary' )), $count);

            if (!empty($typenow)) {
                $bulk_messages[$typenow]['deleted'] = $bulk_messages['post']['deleted'];
            }
        }

        return $bulk_messages;
    }

    function actDoMetaBoxes() {
        global $pagenow;

        if (!empty($_REQUEST['requested_deletion_count']) && ('edit.php' == $pagenow)) {    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $count = intval($_REQUEST['requested_deletion_count']);                         //phpcs:ignore WordPress.Security.NonceVerification.Recommended

            $messages []= sprintf(esc_html(_n( '%s deletion requested.', '%s deletions requested.', $count, 'revisionary' )), $count);
            echo '<div id="message" class="updated notice is-dismissible"><p>' 
            . implode( ' ', $messages )                                                     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            . '</p></div>';
        }
    }

    function fltPostActionLinks($actions) {
        global $post, $revisionary;

        if (current_user_can('copy_post', $post->ID) && (!current_user_can('delete_post', $post->ID))
        && in_array($post->post_type, array_keys($revisionary->enabled_post_types))
        ) {
            $redirect_arg = ( ! empty($_REQUEST['rvy_redirect']) )                          //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? "&rvy_redirect=" . esc_url_raw(wp_unslash($_REQUEST['rvy_redirect']))                     //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : '';

            $url = wp_nonce_url(rvy_admin_url("admin.php?page=rvy-revisions&amp;post={$post->ID}&amp;action=request_deletion$redirect_arg"), 'deletion-requests');
            
            $caption = apply_filters('revisionary_request_deletion_caption', esc_html__('Request Deletion'), $post);
            $caption = str_replace(' ', '&nbsp;', $caption);

            $actions['request_deletion'] = "<a href='" . esc_url($url) . "'>" . $caption . '</a>';
        }

        return $actions;
    }

    function actRevisionsAdminInit() {
        add_action('wp_loaded', [$this, 'actWPloaded']);
    }

    function actWPloaded() {
        if ( ! empty($_GET['action']) || ! empty($_POST['action']) ) {
            if (isset($_SERVER['REQUEST_URI']) && false !== strpos(urldecode(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))), 'admin.php') && !empty($_REQUEST['page']) && ('rvy-revisions' == $_REQUEST['page'])) {
                if ( ! empty($_GET['action']) && in_array($_GET['action'], ['request_deletion', 'apply_deletion', 'clear_deletion_request'])) {
                    check_admin_referer('deletion-requests');
                    
                    $_post = (isset($_GET['post'])) ? array_map('intval', (array) $_GET['post']) : [];

                    $sendback = $this->fltHandleAdminAction($sendback, sanitize_key($_GET['action']), $_post);
                    wp_redirect($sendback);
                    exit;
                }
            }
        }
    }

    function actRevisionaryAdminMenu() {
        if (get_option('rvy_deletion_requests') 
        || (
            !empty($_REQUEST['page'])                                                       //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && ('revisionary-deletion' == $_REQUEST['page'])                                //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && !empty($_SERVER['HTTP_REFERER'])
            && strpos(esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])), 'page=revisionary-deletion')
            )
        ) {
            add_submenu_page('revisionary-q', esc_html__('Deletion Queue', 'revisionary'), esc_html__('Deletion Queue', 'revisionary'), 'read', 'revisionary-deletion', [$this, 'deletion_queue']);
        }
    }

    function deletion_queue() {
		require_once( dirname(__FILE__).'/deletion-queue_rvy.php');
	}

    function fltHandleAdminAction($sendback, $doaction, $post_ids) {
        if (empty($post_ids)) {
            return;
        }

        if (in_array($doaction, ['request_deletion', 'apply_deletion', 'clear_deletion_request'])) {
            $sendback = (isset($_SERVER['HTTP_REFERER'])) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
            $sendback = remove_query_arg( array('action', 'action2', '_wp_http_referer', '_wpnonce', 'deleted', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view', 'request_cleared_count', 'posts_deleted'), $sendback );
            $sendback = str_replace('#038;', '&', $sendback);	// @todo Proper decode
        }

        switch ($doaction) {
            case 'request_deletion':
                $requested = 0;

                if (!$requested_ids = get_option('rvy_deletion_requests')) {
                    $requested_ids = [];
                }

                foreach ($post_ids as $post_id) {
                    if (current_user_can('copy_post', $post_id) || current_user_can('delete_post', $post_id)) {
                        if ($post = get_post($post_id)) {
                            $requested_ids []= $post_id;
                            update_post_meta($post_id, '_rvy_deletion_request_date', agp_time_gmt());
                            $requested++;

                            require_once( dirname(__FILE__).'/deletion-workflow_rvy.php' );
                            $rvy_workflow_ui = new Rvy_Deletion_Workflow_UI();

                            $args = ['post' => $post, 'object_type' => $post->post_type];
                            $rvy_workflow_ui->do_notifications('deletion-request', 'deletion-request', (array) $post, $args );
                        }
                    }
                }

                if ($requested) {
                    update_option('rvy_deletion_requests', $requested_ids);
					$sendback = add_query_arg('requested_deletion_count', $requested, $sendback);
				}

                break;

            case 'clear_deletion_request':
                $cleared = 0;

                if (!$requested_ids = get_option('rvy_deletion_requests')) {
                    $requested_ids = [];
                }

                foreach ($post_ids as $post_id) {
                    if (current_user_can('delete_post', $post_id)) {
                        if ($post = get_post($post_id)) {
                            $requested_ids = array_diff($requested_ids, [$post_id]);
                            $cleared++;
                        }
                    }
                }

                if ($cleared) {
                    update_option('rvy_deletion_requests', $requested_ids);
                    $sendback = add_query_arg('request_cleared_count', $cleared, $sendback);
                }

                break;

            case 'apply_deletion':
                $deleted = 0;

                if (!$requested_ids = get_option('rvy_deletion_requests')) {
                    $requested_ids = [];
                }

                foreach ($post_ids as $post_id) {
                    if (current_user_can('delete_post', $post_id)) {
                        if ($post = get_post($post_id)) {
                            $requested_ids = array_diff($requested_ids, [$post_id]);

                            wp_delete_post($post_id, true);
                            $deleted++;
                        }
                    }
                }

                if ($deleted) {
                    update_option('rvy_deletion_requests', $requested_ids);
					$sendback = add_query_arg('posts_deleted', $deleted, $sendback);
				}

                break;
        }

        return $sendback;
    }
}
