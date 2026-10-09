<?php
class RevisionaryProSettingsUI {
    function __construct() {
        add_action('revisionary_settings_ui', [$this, 'actSettingsUI']);

        add_action('revisionary_option_ui_pending_revisions', [$this, 'actPendingRevisionsUI'], 10, 3);
        add_action('revisionary_option_ui_preview_options', [$this, 'actPreviewOptionsUI'], 10, 3);
        add_action('revisionary_option_ui_revision_queue_options', [$this, 'actQueueOptionsUI'], 10, 3);
        add_action('revisionary_option_ui_revision_options', [$this, 'actRevisionOptionsUI'], 10, 3);

        add_filter('revisionary_option_captions', [$this, 'fltOptionCaptions']);
        add_filter('revisionary_option_sections', [$this, 'fltOptionSections']);
    }

    function actSettingsUI($ui) {
        $ui->option_captions['unfiltered_preview_links'] = esc_html__('Use unfiltered preview links', 'revisionary-pro');

        $ui->section_captions = $ui->section_captions + ['license' => esc_html__('License Key', 'revisionary-pro')];

        $ui->form_options['features']['preview'] []= 'unfiltered_preview_links';
    }

    function fltOptionCaptions($captions) {
        $captions['pending_revision_unpublished'] = (rvy_get_option('revision_statuses_noun_labels')) ? esc_html__('Change Requests for Unpublished Posts', 'revisionary-pro') :  esc_html__('Revision Submission for Unpublished Posts', 'revisionary-pro');
        $captions['publish_by_revision'] = (rvy_get_option('revision_statuses_noun_labels')) ? esc_html__('Publish by Change Request', 'revisionary-pro') :  esc_html__('Publish by Revision', 'revisionary-pro');

        if (class_exists('FLBuilderUserAccess')) {
            $captions['beaver_builder_revisor_access'] = esc_html__('Allow Revisors to launch Beaver Builder', 'revisionary-pro');
        }
        
        if (class_exists('ACF')) {
            $captions['prevent_rest_revisions'] = esc_html__('Prevent Redundant Revisions', 'revisionary-pro');
        }

        return $captions;
    }

    function fltOptionSections($sections) {
        $sections['features']['pending_revisions'][] = 'pending_revision_unpublished';
        $sections['features']['pending_revisions'][] = 'publish_by_revision';

        if (class_exists('FLBuilderUserAccess')) {
            $sections['features']['revisions'][] = 'beaver_builder_revisor_access';
        }

        $sections['features']['preview'][] = 'unfiltered_preview_links';

        if (class_exists('ACF')) {
            $sections['features']['revisions'][] = 'prevent_rest_revisions';
        }

        return $sections;
    }

    function actPendingRevisionsUI($settings_ui, $sitewide = false, $customize_defaults = false) {
        $hint = '';
        $settings_ui->option_checkbox('pending_revision_unpublished', 'features', 'pending_revisions', $hint, '');

        $hint = (rvy_get_option('revision_statuses_noun_labels')) 
        ? esc_html__('Approval of a Change Request to an unpublished post causes it to be published.', 'revisionary-pro')
        : esc_html__('Approval of a Revision to an unpublished post causes it to be published.', 'revisionary-pro');

        
        $args = [];
        $args['style'] = (rvy_get_option('pending_revision_unpublished')) ? '' : 'display: none;';

        $settings_ui->option_checkbox('publish_by_revision', 'features', 'pending_revisions', $hint, '', $args);

        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready( function($) {
            $('#pending_revision_unpublished').on('click', function() {
                $('#publish_by_revision').closest('div').toggle( $('#pending_revision_unpublished').prop('checked'));
            });
        });
        /* ]]> */
        </script>
    <?php
    }

    function actPreviewOptionsUI($settings_ui, $sitewide = false, $customize_defaults = false) {
        $hint = esc_html__('Some third party plugins (including WPML with language subdomains) may require this for compatibility.', 'revisionary-pro');
		$settings_ui->option_checkbox('unfiltered_preview_links', 'features', 'preview', $hint, '');

        return $settings_ui;
    }

    function actRevisionOptionsUI($settings_ui, $sitewide = false, $customize_defaults = false) {
        if (class_exists('ACF')) {
            $hint = esc_html__( 'Prevent REST requests from generating revisions (which may be stored without ACF fields).', 'revisionary-pro' );
            $settings_ui->option_checkbox('prevent_rest_revisions', 'features', 'revisions', $hint, '');
        }

        if (class_exists('FLBuilderUserAccess')) {
            $hint = esc_html__('Add the Revisor role to Beaver Builder Builder Access. Existing access for other roles is preserved.', 'revisionary-pro');
            $settings_ui->option_checkbox('beaver_builder_revisor_access', 'features', 'revisions', $hint, '');
        }
    }

    function actQueueOptionsUI($settings_ui, $sitewide = false, $customize_defaults = false) {
        echo "<br />";

        $hint = esc_html__('Revisors can request the deletion of a published post. The Deletion Queue is not shown until a deletion request exists.', 'revisionary-pro');
		$settings_ui->option_checkbox('deletion_queue', 'features', 'working_copy', $hint, '');
    }
}
