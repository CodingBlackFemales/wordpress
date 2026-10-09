<?php
class RevisionaryWPMLTM {
    function __construct() {
        if (is_admin()) {
            if (!empty($_REQUEST['rvy_wpml_sync_needs_update'])) {
                check_admin_referer('wpml-sync-update');
                $this->transManageSyncNeedsUpdate();
            }

            if (isset($_REQUEST['needs_update_sync_done'])) {
                add_action('all_admin_notices', [$this, 'confirmationNotice']);
            }

            if (!defined('PP_REVISIONS_DISABLE_WPMLTM_INTEGRATION')) {
                add_filter('wpml_tm_dashboard_post_statuses', [$this, 'fltDashboardStatuses']);

                if (!empty($_GET['page']) && (false !== strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'wpml-translation-management')) && empty($_POST)) {
                    add_filter('get_post_status', [$this, 'fltTMgetStatus'], 10, 2);

                    add_action('rvy_init', [$this, 'actRvyInit']);
                }
            }
        }
    }

    public function fltDashboardStatuses($statuses) {
        foreach(rvy_revision_statuses() as $status) {
            if ($status_obj = get_post_status_object($status)) {
                $statuses[$status] = $status_obj->label;
            }
        }

        return $statuses;
    }

    public function fltTMgetStatus($status, $post) {
        if (rvy_in_revision_workflow($post) && rvy_is_revision_status($post->post_mime_type)) {
            if ('draft-revision' != $post->post_mime_type) {
                $status = $post->post_mime_type;
            }
        }

        return $status;
    }

    public function actRvyInit() {
        global $revisionary;

        $revisionary->is_revisions_query = true;
    }

    // Note: WPML Translation Management sets icl_translation_status.needs_update as long as Mimic API Actions is enabled
    public function transManageSyncNeedsUpdate() {
        global $wpdb;

        /*
        - select both translation_id and element_id
        
        - element_id is the post ID of translations. For each of those, set the needs_update flag in translation_status only if the translation post has a post_modified_gmt value older than the source post's post_modified_gmt value
        */

        $new_flagged = 0;

        if (!$flagged_posts = get_option('_revisionary_wpml_flagged_posts')) {
            $flagged_posts = [];
        }

        if (!$flagged_post_ids = get_option('_revisionary_wpml_flagged_post_ids')) {
            $flagged_post_ids = [];
        }

        $revision_status_csv = array_diff(
            implode("','", array_map('sanitize_key', rvy_revision_statuses())),
            ['draft-revision']
        );

        foreach(get_post_types(['public' => true]) as $post_type) {
            $translations = $wpdb->get_results(         // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT t.translation_id, t.element_id AS translated_post_id, t.trid as source_post_id FROM {$wpdb->prefix}icl_translations t "
                    . " INNER JOIN $wpdb->posts AS source_p ON source_p.ID = t.trid"
                    . " INNER JOIN $wpdb->posts AS trans_p ON trans_p.ID = t.element_id"
                    . " INNER JOIN {$wpdb->prefix}icl_translation_status st ON st.translation_id = t.translation_id "
                    . " WHERE t.element_type = %d AND source_p.post_status NOT IN ('$revision_status_csv') AND st.needs_update != 1 AND source_p.post_date_gmt > trans_p.post_date_gmt",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "post_{$post_type}"
                )
            );

            foreach($translations as $translation) {
                $new_flagged++;
                $flagged_posts[$translation->translation_id] = true;
                $flagged_post_ids[$translation->source_post_id] = true;

                $wpdb->query(                           // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}icl_translation_status SET needs_update = 1 WHERE translation_id = %d",
                        $translation->translation_id
                    )
                );
            }
        }

        if (!empty($new_flagged)) {
            update_option('_revisionary_wpml_flagged_posts', $flagged_posts);
            update_option('_revisionary_wpml_flagged_post_ids', $flagged_post_ids);
        }

        wp_redirect(wp_nonce_url(admin_url("admin.php?page=revisionary-settings&needs_update_sync_done=$new_flagged"), 'rvy-update-sync'));
        exit;
    }

    function confirmationNotice() {
        if (isset($_REQUEST['needs_update_sync_done'])) {
            check_admin_referer('rvy-update-sync');
            $num_flagged = (int) $_REQUEST['needs_update_sync_done'];
        } else {
            $num_flagged = 0;
        }

        if (!empty($_REQUEST['needs_update_sync_done'])) {
            $msg = sprintf(_n( 'WPML Translation Management: %s translation flagged.', 'WPML Translation Management: %s translations flagged.', $num_flagged, 'revisionary-pro' ), $num_flagged);
        } else {
            $msg = __('WPML Translation Management: Flags already synchronized', 'revisionary-pro');
        }

        echo '<div class="notice"><p>' . esc_html($msg) . '</p></div>';
    }

}
