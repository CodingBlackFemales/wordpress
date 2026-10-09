<?php

class Rvy_Deletion_Workflow_UI {
    function do_notifications( $notification_type, $status, $post_arr, $args ) {
        global $revisionary, $current_user;

        if ( 'deletion-request' != $notification_type ) {
            return;
        }

        $defaults = array('post' => false, 'object_type' => '', 'selected_recipients' => array() );
        $args = array_merge( $defaults, $args );
        foreach( array_keys($defaults) as $var ) { $$var = $args[$var]; }

        $object_type = sanitize_key($object_type);

        //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*  previous REST workaround:
        if ( $revisionary->doing_rest && $revisionary->rest->is_posts_request && ! empty( $revisionary->rest->request ) ) {
            $post_arr = array_merge( $revisionary->rest->request->get_params(), $post_arr );
        }
        */

        $recipient_ids = [];

        $admin_notify = rvy_get_option( 'pending_rev_notify_admin' );
        $author_notify = rvy_get_option( 'pending_rev_notify_author' );

        if ( $admin_notify || $author_notify ) {
            $type_obj = get_post_type_object( $object_type );
            $type_caption = strtolower($type_obj->labels->singular_name);
            $post_arr['post_type'] = $post->post_type;
            
            $blogname = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
            
            $title = sprintf( esc_html__('[%s] %s', 'revisionary'), $blogname, pp_revisions_status_label('pending-revision', 'name') );
            
            $message = sprintf( esc_html__('%1$s submitted a deletion request for the %2$s "%3$s".', 'revisionary'), $current_user->display_name, $type_caption, $post_arr['post_title'] ) . "\r\n\r\n";
        
            $message .= esc_html__( 'Deletion Queue: ', 'revisionary' ) . rvy_admin_url("admin.php?page=revisionary-deletion") . "\r\n\r\n";
                
            $message .= esc_html__( 'Edit Post:', 'revisionary' ) . ' ' . rvy_admin_url("post.php?action=edit&post={$post->ID}") . "\r\n";

            if ( $admin_notify ) {
                // share recipient logic with pending revision submission
                require_once( dirname(REVISIONARY_FILE).'/revision-workflow_rvy.php' );

                // establish the publisher recipients
                $published_post = get_post($post_arr['ID']);
                $recipient_ids = apply_filters('revisionary_submission_notify_admin', Rvy_Revision_Workflow_UI::getRecipients('rev_submission_notify_admin', compact('type_obj', 'published_post')), ['post_type' => $object_type, 'post_id' => $published_post->ID]);
 
                if ( ( 'always' != $admin_notify ) && $selected_recipients ) {
                    // intersect default recipients with selected recipients
                    $recipient_ids = array_intersect( $selected_recipients, $recipient_ids );
                }
                
                if ( defined( 'RVY_NOTIFY_SUPER_ADMIN' ) && is_multisite() ) {
                    $super_admin_logins = get_super_admins();
                    foreach( $super_admin_logins as $user_login ) {
                        if ( $super = new WP_User($user_login) ) {
                            $recipient_ids []= $super->ID;
                        }
                    }
                }
            }

            if ( $author_notify ) {
                if (function_exists('get_multiple_authors')) {
                    $author_ids = [];
                    foreach(get_multiple_authors($published_post) as $_author) {
                        $author_ids []= $_author->ID;
                    }	
                } else {
                    $author_ids = [$published_post->post_author];
                }

                if ('always' != $author_notify) {
                    $author_ids = $selected_recipients ? array_intersect($author_ids, $selected_recipients) : [];
                }

                $recipient_ids = array_merge($recipient_ids, $author_ids);
            }

            if ( $recipient_ids ) {
                $to_addresses = [];

                foreach($recipient_ids as $user_id) {
                    $user = new WP_User($user_id);                
                
                    if ($user->exists() && !empty($user->user_email)) {
                        $to_addresses[$user_id] = $user->user_email;
                    }
                }

                $to_addresses = array_unique($to_addresses);
            } else {
                $to_addresses = array();
            }

            foreach ( $to_addresses as $user_id => $address ) {
                if (!empty($author_ids) && in_array($user_id, $author_ids)) {
                    $notification_class = 'rev_submission_notify_author';
                } elseif (!empty($monitor_ids) && in_array($user_id, $monitor_ids)) {
                    $notification_class = 'rev_submission_notify_monitor';
                } else {
                    $notification_class = 'rev_submission_notify_admin';
                }

                rvy_mail(
                    $address, 
                    $title, 
                    $message, 
                    [
                        'revision_id' => $revision_id, 
                        'post_id' => $published_post->ID, 
                        'notification_type' => $notification_type,
                        'notification_class' => $notification_class,
                    ]
                );
            }
        }
    }
    
}
