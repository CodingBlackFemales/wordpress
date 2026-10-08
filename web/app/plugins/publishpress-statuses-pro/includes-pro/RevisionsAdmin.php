<?php
namespace PublishPress_Statuses;

class RevisionsAdmin {

    function __construct() {
        global $pagenow;

        if (in_array($pagenow, ['post.php', 'post-new.php'])) {
            require_once(__DIR__ . '/RevisionsPostEdit.php');
            new \PublishPress_Statuses\RevisionsPostEdit();

            require_once(__DIR__ . '/RevisionsPostEditClassic.php');
            new \PublishPress_Statuses\RevisionsPostEditClassic();

        } elseif ('admin.php' == $pagenow) {
            $plugin_page = \PP_Statuses_Functions::getPluginPage();

            if (in_array($plugin_page, ['publishpress-statuses', 'publishpress-statuses-add-new'])) {
                require_once(__DIR__ . '/RevisionsStatusAdmin.php');
                new \PublishPress_Statuses\RevisionsStatusAdmin();

            } elseif ('pp-capabilities' == $plugin_page) {
                add_action('admin_print_scripts',
                    function() {
                    ?>
                    <script type="text/javascript">
                    /* <![CDATA[ */
                    jQuery(document).ready(function ($) {           
                        var url = $('#cme-cap-type-tables-revision-status td.cme-status-footer a').attr('href');
                        
                        if (typeof url !== 'undefined') {
                            url = url.replace('ppr_tab=revisions', 'ppr_tab=revision_statuses')

                            $('#cme-cap-type-tables-revision-status td.cme-status-footer a').attr(
                                'href', 
                                url
                            );
                        }
                    });
                    /* ]]> */
                    </script>
                    <?php
                }, 50
                );
            }
        }

        add_filter(
            'publishpress_statuses_available_moderation_statuses',
            function ($statuses, $post) {
                if (is_object($post) && ('future-revision' == $post->post_mime_type)) {
                    $statuses = array_intersect_key(
                        $statuses,
                        ['future-revision' => true]
                    );
                } else {
                    unset($statuses['future-revision']);
                }

                return $statuses;
            },
            10, 2
        );
    }
}
