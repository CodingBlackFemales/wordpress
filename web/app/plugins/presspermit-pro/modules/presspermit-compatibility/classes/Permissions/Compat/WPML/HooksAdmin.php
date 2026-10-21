<?php
namespace PublishPress\Permissions\Compat\WPML;

class HooksAdmin
{
    function __construct()
    {
        global $pagenow;

        // Additional filtering with PP "Edit Permissions"
        if (strpos(PWP::SERVER_url('REQUEST_URI'), 'presspermit-edit-permissions')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/WPML/GroupEdit.php');
            new GroupEdit();
        }

        if (defined('PRESSPERMIT_HIDE_LANGUAGE_COUNTS')) {
        	add_action( 'admin_head', [$this, 'actHideTranslationCounts'] );
        }

        // prevent manual role edit for translations if mirroring from source post/term
        add_action('presspermit_disable_exception_ui', [$this, 'fltDisableExceptionEdit'], 10, 4);
        add_action('presspermit_disable_exception_edit', [$this, 'fltDisableExceptionEdit'], 10, 4);

        // options UI for mirroring of source post roles to translations
        add_filter('presspermit_section_captions', [$this, 'fltSectionCaptions'], 20);
        add_filter('presspermit_option_captions', [$this, 'fltOptionCaptions'], 20);
        add_filter('presspermit_option_sections', [$this, 'fltOptionSections'], 20);
        add_action('presspermit_editing_options_ui', [$this, 'actOptionsUI'], 20);

        add_filter('query', [$this, 'fltForceJobRetrieval']);
        add_filter('query', [$this, 'fltPostCountQuery']);
    }

    function actHideTranslationCounts() {
		if ( ! presspermit()->isContentAdministrator() ) :
		?>
<style type="text/css">
div.wrap ul.subsubsub span.count, div.wrap #icl_subsubsub span.count, div.icl_subsubsub span.count {display:none;}
</style>
		<?php 
		endif;
	}

    function fltPostCountQuery($query) {
        global $wpdb;

        if (!defined('PRESSPERMIT_HIDE_LANGUAGE_COUNTS') && !presspermit()->isContentAdministrator() && strpos($query, "language_code, COUNT(p.ID) AS")) {
            $match = preg_match("/WHERE p.post_type='([a-zA-Z0-9\-_]*)'/", $query, $matches);

            if (!empty($matches[1])) {
                $where = apply_filters(
                    'presspermit_posts_where', 
                    $matches[0], 
                    [
                        'post_types' => [$matches[1]],
                        'source_alias' => 'p',
                        'required_operation' => 'edit',
                    ]
                );

                $query = str_replace($matches[0], $where, $query);
            }
        }

        return $query;
    }

    function fltForceJobRetrieval($query)
    {
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // simulate this hack in wpml function get_translation_job() :

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // }elseif($job->translator_id != @intval($this->current_translator->translator_id) && !defined('XMLRPC_REQUEST') && $job->manager_id != $current_user->ID && empty($this->force_job_retrieval) ){    // force_job_retrieval: kevinB addition for PressPermit

        if (strpos($query, 'j.rid, j.translator_id, j.translated, j.manager_id') && strpos($query, 'ELECT ') 
        && strpos($query, 'WHERE j.job_id =') && !empty($this->force_job_retrieval)
        ) {    
            static $in_progress;
            if ($in_progress) {
                return $query;
            }
            $in_progress = true;

            global $wpdb, $current_user;

            // phpcs Note: Direct query on WPML table to support permissions filtering

            // phpcs Note: For this WPML workaround, need to pre-execute WPML query and investigate results

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($job = $wpdb->get_row(
                $query   // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )) {
                $this->buffer_user_id = $current_user->ID;
                $current_user->ID = $job->manager_id;
                add_filter('icl_job_elements', [$this, 'flt_restore_user_id']);
            }
            $in_progress = false;
        }

        return $query;
    }

    function fltRestoreUserId($elements)
    {
        global $current_user;
        $current_user->ID = $this->buffer_user_id;
        return $elements;
    }

    // suppress exception editing on post.php when editing a translation and mirroring is enabled
    private function postDisableCheck($disable, $item_id = 0)
    {
        global $sitepress, $wpdb;

        if ($disable || empty($sitepress))
            return $disable;

        $post_type = PWP::findPostType();

        if (!$item_id) {
            $item_id = PWP::getPostID();
        }

        $trid = $sitepress->get_element_trid($item_id, 'post_' . $post_type);

        // phpcs Note: Direct query on WPML table to support permissions filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $translation_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = %d"
                . " AND element_type = %s AND language_code != source_language_code"
                . " AND source_language_code != ''", 
                
                $trid, 
                "post_{$post_type}"
            )
        );

        if (in_array($item_id, $translation_ids)) {
            return true;
        }

        // for post-new.php UI
        $lang = PWP::sanitizeEntry(PWP::REQUEST_key('lang'));
        $source_lang = PWP::sanitizeEntry(PWP::REQUEST_key('source_lang', false));

        if ($lang && $source_lang && ($lang != $source_lang)) {
            return true;
        }

        // for post-new.php submission
        if (!PWP::empty_REQUEST('icl_post_language')) {
            if ($sitepress->get_default_language() != PWP::REQUEST_key('icl_post_language', false)) {
                return true;
            }
        }

        return $disable;
    }

    function fltDisableExceptionEdit($disable, $via_item_source, $item_id = 0, $post_type = '')
    {
        if (presspermit()->getOption("mirror_{$via_item_source}_translation_exceptions")) {
            switch ($via_item_source) {
                case 'post':
                    $disable = $this->postDisableCheck($disable, $item_id);
                    break;
                case 'term':
                    $disable = $this->termDisableCheck($disable, $item_id);
                    break;
            }
        }
        return $disable;
    }

    private function termDisableCheck($disable, $tt_id)
    {
        global $sitepress, $wpdb, $taxonomy;

        if (!$tt_id || empty($sitepress) || $disable)
            return $disable;

        $set_taxonomy = (!empty($taxonomy)) ? $taxonomy : '';

        if (is_object($tt_id)) {
            if (isset($tt_id->term_taxonomy_id)) {
                $tt_id = $tt_id->term_taxonomy_id;
            } else {
                return $disable;
            }
        }

        if (empty($set_taxonomy)) {
            // phpcs Note: Direct query on terms table to support WPML filtering (without further filter application)

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if (!$set_taxonomy = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", 
                    $tt_id
                )
            )) {
                return $disable;
            }
        }

        if (is_object($set_taxonomy)) {
            if (isset($set_taxonomy->name)) {
                $set_taxonomy = $set_taxonomy->name;
            } else {
                return $disable;
            }
        }

        $trid = $sitepress->get_element_trid($tt_id, 'tax_' . $set_taxonomy);

        // phpcs Note: Direct query on WPML table to support permissions filtering

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $translation_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = %d"
                . " AND element_type = %s AND language_code != source_language_code"
                . " AND source_language_code != ''",
                
                $trid, 
                "tax_{$set_taxonomy}"
            )
        );

        if (in_array($tt_id, $translation_ids)) {
            $disable = true;
        }

        return $disable;
    }

    function fltSectionCaptions($sec_captions)
    {
        $sec_captions['editing']['wpml_integration'] = esc_html__('WPML Integration', 'wpml_pp');
        return $sec_captions;
    }

    function fltOptionCaptions($opt_captions)
    {
        $opt_captions['mirror_post_translation_exceptions'] = esc_html__('Mirror Post Permissions to translations', 'wpml_pp');
        $opt_captions['mirror_term_translation_exceptions'] = esc_html__('Mirror Term Permissions to translations', 'wpml_pp');
        return $opt_captions;
    }

    function fltOptionSections($sections)
    {
        $sections['editing']['wpml_integration'] = ['mirror_post_translation_exceptions', 'mirror_term_translation_exceptions'];
        return $sections;
    }

    function actOptionsUI()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'editing';

        $section = 'wpml_integration';
        if (!empty($ui->form_options[$tab][$section])) :
            ?>
            <tr>
                <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]);?></th>
                <td>
                    <?php
                    $hint = '';
                    $ret = $ui->optionCheckbox('mirror_post_translation_exceptions', $tab, $section, $hint, '');
                    $ret = $ui->optionCheckbox('mirror_term_translation_exceptions', $tab, $section, $hint, '');
                    ?>
                </td>
            </tr>
        <?php
        endif; // any options accessable in this section
    }
}
