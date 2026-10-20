<?php
namespace PublishPress\Permissions\FileAccess\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

class SettingsUtility
{
    public static function requestedAttachFiles()
    {
        if (defined('PRESSPERMIT_REQUESTED_ATTACH_FILES')) {
            return;
        }

        if ($key = presspermit()->getOption('file_filtering_regen_key')) {
            if (PWP::is_GET('key', $key)) {  // user must store their own non-null key before this will work
                http_response_code(200);
                self::attachLinkedUploads(!PWP::empty_GET('echo'));
            } else {
                http_response_code(403);
                SettingsAdmin::echoStr('attachments_util_invalid_regen_key');
            }
        } else {
            http_response_code(401);
            SettingsAdmin::echoStr('attachments_util_no_regen_key');
        }

        exit(0);
    }

    public static function display()
    {
        $uploads = \PublishPress\Permissions\FileAccess::getUploadInfo();
        $upload_path = $uploads['baseurl'];
        $search_replace_url = function_exists('pp_plugin_search_url')
            ? pp_plugin_search_url('replace')
            : (get_option('siteurl') . "/wp-admin/plugin-install.php?tab=search&type=tag&s=replace");
        $regen_key = get_option("presspermit_file_filtering_regen_key");
        $utility_url = $regen_key ? site_url("index.php?action=presspermit-attachment-utility&key=$regen_key") : '';
        $back_url = "admin.php?page=presspermit-settings&pp_tab=file_access";
        ?>
        <style>
            .container { max-width: 900px; margin: 0 auto; padding: 0 1.5rem; justify-self: center; }
            .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e0e0e0; }
            .header h1 { font-size: 1.75rem; font-weight: 600; color: #1e1e1e; }
            .back-link { display: inline-flex; align-items: center; color: #3858e9; text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
            .back-link:hover { color: #2d4bd1; }
            .back-link svg { margin-right: 0.5rem; width: 16px; height: 16px; }
            .card { background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); padding: 2rem; margin-bottom: 2rem; max-width: 900px; }
            .card h2 { font-size: 1.25rem; margin-bottom: 1.5rem; color: #1e1e1e; font-weight: 600; }
            .card p { margin: 0.5rem 0; color: #757575; }
            .requirements { margin: 1rem 0; }
            .requirement-item { display: flex; align-items: flex-start; margin-bottom: 1rem; }
            .requirement-number { background-color: #3858e9; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; margin-right: 1rem; flex-shrink: 0; }
            .requirement-text { color: #757575; }
            .btn { display: inline-block; background-color: #3858e9; color: white; padding: 0.75rem 1.5rem; border-radius: 4px; text-decoration: none; font-weight: 500; transition: background-color 0.2s; border: none; cursor: pointer; font-size: 0.9rem; }
            .btn:hover { background-color: #2d4bd1; }
            .btn-block { display: block; width: 100%; text-align: center; }
            .url-section { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e0e0e0; }
            .url-label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: #757575; }
            .url-input { width: 100%; padding: 0.75rem; border: 1px solid #e0e0e0; border-radius: 4px; font-family: monospace; font-size: 0.9rem; background-color: #f5f5f5; color: #1e1e1e; }
            .notice { padding: 1rem; border-radius: 4px; margin: 0.5rem 0 1rem; background-color: #e3f2fd; color: #0d47a1; border-left: 4px solid #3858e9; }
            .scan-progress { margin: 1rem 0; padding: 1rem; background-color: #f5f5f5; border-radius: 4px; font-family: monospace }
            .results-container { margin-top: 1rem }
            .result-item { padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #2196f3; background-color: #f8fafd; border-radius: 0 4px 4px 0 }
            .result-item strong { color: #1e1e1e }
            .result-item a { color: #3858e9; word-break: break-all }
            .summary { margin-top: 0.5rem; padding: 1rem; background-color: #f0f7f0; border-left: 4px solid #4caf50; border-radius: 0 4px 4px 0 }
            .summary h3 { margin: 0.5rem 0; color: #4caf50 }
            .status-badge { display: inline-block; padding: .25rem .5rem; border-radius: 4px; font-size: .75rem; font-weight: 600; margin-left: .5rem }
            .status-running { background-color: #fff3e0; color: #e65100 }
            .status-complete { background-color: #e8f5e9; color: #4caf50 }
            @media (max-width: 768px) {
              .container { padding: 0 1rem; }
              .header { flex-direction: column; align-items: flex-start; }
              .header h1 { margin-bottom: 1rem; }
            }
        </style>
        <div class="container">
            <div class="header">
                <h1><?php esc_html_e('Attachments Utility', 'presspermit-pro'); ?></h1>
                <a href="<?php echo esc_url($back_url); ?>" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <?php esc_html_e('Back to Permissions Settings', 'presspermit-pro'); ?>
                </a>
            </div>

            <div class="card">
                <h2><?php esc_html_e('File Access for Files Uploaded via FTP', 'presspermit-pro'); ?></h2>
                <div class="notice">
                <?php SettingsAdmin::echoStr('attachments_util_postmeta_link'); ?>
                </div>
                <form action="" method="post">
                    <?php wp_nonce_field('pp_run_utility'); ?>
                    <?php if (empty($_POST['pp_run_utility'])) : ?>
                        <button type="submit" name="pp_run_utility" value="1" class="btn"><?php esc_html_e('Find Files Uploaded via FTP', 'presspermit-pro'); ?></button>
                    <?php endif; ?>
                </form>
                <?php
                if (!empty($_POST['pp_run_utility']) && check_admin_referer('pp_run_utility')) {
                    if (is_multisite() && is_super_admin() && PWP::isNetworkActivated()) {
                        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/AttachmentScanBatch.php');

                        if (\PublishPress\Permissions\FileAccess\AttachmentScanBatch::start()) {
                            echo '<div class="notice"><p>' . esc_html__('The network attachment scan has been queued and will continue in the background via WP-Cron.', 'presspermit-pro') . '</p></div>';
                        } else {
                            echo '<div class="notice"><p>' . esc_html__('A network attachment scan is already in progress or could not be scheduled.', 'presspermit-pro') . '</p></div>';
                        }
                    } else {
                        self::attachLinkedUploads(true);
                    }
                }
                ?>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Site Configuration Requirements', 'presspermit-pro'); ?></h2>
                <p>
                <?php esc_html_e('For proper access control of direct file URLs, your site must meet these requirements:', 'presspermit-pro'); ?>
                </p>
                <div class="requirements">
                <div class="requirement-item">
                    <div class="requirement-number">1</div>
                    <div class="requirement-text">
                    <?php SettingsAdmin::echoStr('attachments_util_wp_tree'); ?>
                    </div>
                </div>
                <div class="requirement-item">
                    <div class="requirement-number">2</div>
                    <div class="requirement-text">
                    <?php
                    if (false !== strpos($upload_path, 'http://www.')) {
                        $www_msg = SettingsAdmin::getStr('attachments_util_www');
                    } else {
                        $www_msg = SettingsAdmin::getStr('attachments_util_no_www');
                    }

                    printf( // 'Files linked from WP Posts and Pages must be in %1$s (or a subdirectory of it) to be filtered. After moving files, you may use %2$s a search and replace plugin%3$s to conveniently update the URLs stored in your Post / Page content. %4$s'
                        esc_html(SettingsAdmin::getStr('attachments_util_search_replace')),
                        '<strong>' . esc_url($uploads['baseurl']) . '</strong>', 
                        "<a href='". esc_url($search_replace_url) . "'>", '</a>', 
                        esc_url($www_msg)
                    );
                    ?>
                    </div>
                </div>
                </div>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Automated Processing', 'presspermit-pro'); ?></h2>
                <p>
                <?php
                if ($regen_key) {
                    SettingsAdmin::echoStr('attachments_util_cron_task');
                } else {
                    printf(
                    esc_html(SettingsAdmin::getStr('attachments_util_cron_task_need_regen_key')),
                    "<a href='admin.php?page=presspermit-settings&pp_tab=file_access' target='_blank'>",
                    '</a>'
                    );
                }
                ?>
                </p>
                <?php if ($regen_key && $utility_url) : ?>
                <div class="url-section">
                    <label class="url-label"><?php esc_html_e('Utility Endpoint URL:', 'presspermit-pro'); ?></label>
                    <input type="text" class="url-input" value="<?php echo esc_attr($utility_url); ?>" readonly />
                    <button type="button" onclick="copyUrl()" class="btn" style="margin-top: 1rem"><?php esc_html_e('Copy URL', 'presspermit-pro'); ?></button>
                </div>
                <?php endif; ?>
                <?php if (!$regen_key) : ?>
                <div class="url-section">
                    <a href="admin.php?page=presspermit-settings&pp_tab=file_access" class="btn" style="margin-top: 1rem"><?php esc_html_e('Go to Settings', 'presspermit-pro'); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <script>
        function copyUrl() {
            const urlInput = document.querySelector(".url-input");
            urlInput.select();
            document.execCommand("copy");
            alert("URL copied to clipboard!");
        }
        </script>
        <?php
    }

    public static function attachLinkedUploads($echo = false, $network_wide = true)
    {
        global $wpdb;

        $blog_id = get_current_blog_id();

        if ($network_wide && is_multisite() && is_super_admin() && PWP::isNetworkActivated()) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/AttachmentScanBatch.php');

            return \PublishPress\Permissions\FileAccess\AttachmentScanBatch::start();
        }

        $blog_ids = [$blog_id];

        $uploads = \PublishPress\Permissions\FileAccess::getUploadInfo();
        $upload_path = $uploads['baseurl'];
        $upload_dir = $uploads['basedir'];

        foreach ($blog_ids as $id) {
            if (count($blog_ids) > 1) {
                switch_to_blog($id);

                if ($echo) {
                	printf(
                        esc_html__("%ssite %d :%s"),
                        '<br /><strong>',
                        esc_html($id),
                        '</strong><br />'
                    );
            	}
            }

            $site_url = untrailingslashit(get_option('siteurl'));
            if (false === strpos($uploads['baseurl'], $site_url)) {
                if ($echo) {
                    SettingsAdmin::echoStr('attachments_util_external_content_dir');
                    echo '<br /><br />';
                    SettingsAdmin::echoStr('attachments_util_terminated');
                }

                return false;
            }

            $post_types = array_diff(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or'), ['attachment']);
            $types_csv = implode("','", array_map('sanitize_key', $post_types));

            // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($post_ids = $wpdb->get_col(
                "SELECT ID FROM $wpdb->posts WHERE post_type IN ('$types_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                . "ORDER BY post_type, post_title"
            )) {
                $stored_attachments = [];

                // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($results = $wpdb->get_results("SELECT post_parent, guid FROM $wpdb->posts WHERE post_type = 'attachment'")) {
                    foreach ($results as $row) {
                        $stored_attachments[$row->post_parent][$row->guid] = true;
                    }
                }

                // for reasonable memory usage, only hold 10 posts in memory at a time
                $found_links = 0;
                $num_inserted = 0;
                $num_posts = count($post_ids);
                $bite_size = 10;
                $num_bites = $num_posts / $bite_size;

                if ($num_posts % $bite_size) {
                    $num_bites++;
                }

                if ($echo) {
                    echo '<div class="scan-progress">';
                    printf(
                        esc_html(SettingsAdmin::getStr('attachments_util_checking_posts_pages')),
                        (int) $num_posts
                    );
                    echo ' <span class="status-badge status-running">' . esc_html__('RUNNING', 'presspermit-pro') . '</span>';
                    echo '</div>';
                }

                for ($i = 0; $i < $num_bites; $i++) {
                    $id_csv = implode("','", array_map('intval', array_slice($post_ids, $i * $bite_size, $bite_size)));

                    // phpcs Note: Direct query on posts table during admin operation to query to retrieve posts without further filtering

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    if (!$results = $wpdb->get_results(
                        "SELECT ID, post_content, post_author, post_title, post_type FROM $wpdb->posts WHERE ID IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    )) {
                        continue;
                    }

                    foreach ($results as $row) {
                        $linked_uploads = [];

                        // preg_match technique learned from https://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php
                        $tags = ['img' => [], 'a' => []];

                        $content = $row->post_content;

                        preg_match_all('/<img[^>]+>/i', $row->post_content, $tags['img']);
                        preg_match_all('/<a[^>]+>/i', $row->post_content, $tags['a']);  // don't care that this will terminate with any enclosed tags (i.e. img)

                        foreach (array_keys($tags) as $tag_type) {
                            foreach ($tags[$tag_type]['0'] as $found_tag) {
                                $found_attribs = ['src' => '', 'href' => '', 'title' => '', 'alt' => ''];

                                if (!preg_match_all('/(alt|title|src|href)=("[^"]*")/i', $found_tag, $tag_attributes)) {
                                    continue;
                                }

                                foreach ($tag_attributes[1] as $key => $attrib_name) {
                                    $found_attribs[$attrib_name] = trim($tag_attributes[2][$key], "'" . '"');
                                }

                                if (!$found_attribs['href'] && !$found_attribs['src']) {
                                    continue;
                                }

                                $file_url = ($found_attribs['src']) ? $found_attribs['src'] : $found_attribs['href'];

                                if (!strpos($file_url, '.')) {
                                    continue;
                                }

                                if (is_multisite() && strpos($uploads['url'], 'blogs.dir')) {
                                    $file_url = str_replace('/files/', "/wp-content/blogs.dir/$blog_id/files/", $file_url);
                                }

                                // links can't be registered as attachments unless they're in the WP uploads path
                                if (false === strpos($file_url, $upload_path)) {
                                    if ($echo) {
                                        printf( // '%1$s skipping unfilterable file in %2$s "%3$s":%4$s %5$s'
                                            esc_html(SettingsAdmin::getStr('attachments_util_skipping_unfilterable')),
                                            '<span class="pp-brown">',
                                            esc_html($row->post_type), 
                                            esc_html($row->post_title), 
                                            '</span>',
                                            esc_url($file_url)
                                        );
                                        
                                        echo '<br /><br />';
                                    }

                                    continue;
                                }

                                // make sure the linked file actually exists
                                if (!file_exists(str_replace($upload_path, $upload_dir, $file_url))) {
                                    if ($echo) {
                                        printf(  // '%1$s skipping missing file in %2$s "%3$s":%4$s %5$s' 
                                            esc_html(SettingsAdmin::getStr('attachments_util_skipping_missing')),
                                            '<span class="pp-red">',
                                            esc_html($row->post_type), 
                                            esc_html($row->post_title), 
                                            '</span>',
                                            esc_html($file_url)
                                        );

                                        echo '<br /><br />';
                                    }

                                    continue;
                                }

                                $caption = ($found_attribs['title']) ? $found_attribs['title'] : $found_attribs['alt'];

                                // we might find the same file sourced in both link and img tags
                                if (!isset($linked_uploads[$file_url]) || !$linked_uploads[$file_url]) {
                                    $found_links++;
                                    $linked_uploads[$file_url] = $caption;
                                }

                            } // end foreach found tag
                        } // end foreach loop on 'img' and 'a'

                        if (!$linked_uploads) continue;
                        echo '<div class="results-container">';
                        foreach ($linked_uploads as $file_url => $caption) {
                            $unsuffixed_file_url = preg_replace("/-[0-9]{2,4}x[0-9]{2,4}./", '.', $file_url);

                            $file_info = wp_check_filetype($unsuffixed_file_url);

                            if (!isset($stored_attachments[$row->ID][$unsuffixed_file_url])) {
                                $att = [];
                                $att['guid'] = $unsuffixed_file_url;

                                $info = pathinfo($unsuffixed_file_url);

                                if (isset($info['filename'])) {
                                    $att['post_name'] = $info['filename'];
                                    $att['post_title'] = $info['filename'];
                                }

                                $att['post_excerpt'] = $caption;
                                $att['post_author'] = $row->post_author;
                                $att['post_parent'] = $row->ID;
                                $att['post_category'] = wp_get_post_categories($row->ID);

                                if (isset($file_info['type'])) {
                                    $att['post_mime_type'] = $file_info['type'];
                                }

                                $num_inserted++;

                                if ($echo) {
                                    printf( // '%1$s attachment OK in %2$s "%3$s":%4$s %5$s'
                                        '<div class="result-item">%s</div>',
                                        sprintf(
                                            esc_html(SettingsAdmin::getStr('attachments_util_new_attachment')),
                                            '<strong>',
                                            esc_html(ucwords($row->post_type)),
                                            esc_html($row->post_title), 
                                            '</strong><br />',
                                            sprintf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url($file_url),
                                                esc_html($file_url)
                                            )
                                        )
                                    );
                                }

                                wp_insert_attachment($att);
                            } else {
                                if ($echo) {
                                    printf( // '%1$s attachment OK in %2$s "%3$s":%4$s %5$s'
                                        '<div class="result-item">%s</div>',
                                        sprintf(
                                            esc_html(SettingsAdmin::getStr('attachments_util_attachment_ok')),
                                            '<strong>',
                                            esc_html(ucwords($row->post_type)),
                                            esc_html($row->post_title), 
                                            '</strong><br />',
                                            sprintf(
                                                '<a href="%s" target="_blank">%s</a>',
                                                esc_url($file_url),
                                                esc_html($file_url)
                                            )
                                        )
                                    );
                                }
                            }
                        } // end foreach linked_uploads
                        echo '</div>'; // close results-container
                    } // end foreach post in this bite

                } // endif for each 10-post bite

                if ($echo) {
                    echo '<div class="summary">';
                    echo '<h3>' . esc_html__('Operation complete', 'presspermit-pro') . '</h3>';
                    echo '<p>';
                    printf( // "%s linked uploads were found in your post / page content."
                        esc_html(SettingsAdmin::getStr('attachments_util_linked_uploads_found')),
                        (int) $found_links
                    );
                    echo '</p>';

                    if ($num_inserted) {
                        echo '<p>';
                        printf( // '<strong>%s attachment records were added to the database.</strong>'
                            esc_html(SettingsAdmin::getStr('attachments_util_files_added')),
                            (int) $num_inserted
                        );
                        echo '</p>';
                    } elseif ($found_links) {
                        echo '<p>';
                        echo esc_html(SettingsAdmin::getStr('attachments_util_already_registered'));
                        echo '</p>';
                    }
                    echo '</div>'; // close summary
                    ?>
                    <br />
                    <form action="" method="post">
                        <?php wp_nonce_field('pp_run_utility'); ?>
                        <?php if (!empty($_POST['pp_run_utility']) && check_admin_referer('pp_run_utility')) : ?>
                            <button type="submit" name="pp_run_utility" value="1" class="btn btn-block"><?php esc_html_e('Scan Again', 'presspermit-pro'); ?></button>
                        <?php endif; ?>
                    </form>
                    <?php
                }
            }

            if ($echo && (count($blog_ids) > 1)) {
                echo '<hr />';
            }

        } // end foreach site

        if (count($blog_ids) > 1) {
            switch_to_blog($blog_id);
        }

        return true;
    }
}
