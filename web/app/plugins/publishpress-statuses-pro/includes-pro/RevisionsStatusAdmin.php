<?php
namespace PublishPress_Statuses;

class RevisionsStatusAdmin {

    function __construct() {
        // Statuses UI
        add_filter('publishpress_statuses_admin_title', [$this, 'fltStatusesAdminTitle'], 10, 2);

        add_action(
            'publishpress_statuses_table_tabs',
            function ($status_type) {
                ?>
                <a href="<?php
                echo esc_url(\PublishPress_Statuses::getLink(['action' => 'statuses', 'status_type' => 'revision'])); ?>"
                    class="nav-tab<?php
                    if ('revision' == $status_type) {
                        echo ' nav-tab-active';
                    } ?>"><?php
                    _e('Revision', 'publishpress-statuses-pro'); ?>
                </a>
                <?php	
            }
        );

        // Status List Table
        add_action(
            'publishpress_statuses_table_list',
            function ($key, $args) {
                if (($key == '_revision-status') && (\PP_Statuses_Functions::empty_REQUEST('status_type') || 'revision' == \PP_Statuses_Functions::REQUEST_key('status_type'))) :?>
                    <li class="ui-sortable-placeholder revision-status ui-temp-placeholder" style="height: 50px;">
                    <div class="row tpl-default">
                        <div class="child-toggle" style="padding-left: 0">
                            <div class="child-toggle-spacer"></div>
                        </div>
        
                        <div class="row-inner">
                            <table class="status-row" style="width:100%"><tbody><tr>
                            <td colspan="7" style="text-align: center"><?php _e('Drop any status here to include it in main workflow for post revisions.', 'publishpress-statuses-pro');?></td>
                            </tr></tbody></table>
                        </div>
                    </div>
                    </li>
                <?php endif;
            },
            10, 2
        );
    
        add_action(
            'publishpress_statuses_table_alternate_list',
            function ($key, $args) {
                $options = \PublishPress_Statuses::instance()->options;

                if ('_revision-alternate' == $key) {
                    if (\PP_Statuses_Functions::empty_REQUEST('status_type') || 'revision' == \PP_Statuses_Functions::REQUEST_key('status_type')) :?>
                    <li class="ui-sortable-placeholder <?php if (empty($options->hide_manual_status_selectors)) echo 'alternate-revision-status'; else echo 'disabled-status';?> ui-temp-placeholder" style="height: 50px;">
                    <div class="row tpl-default">
                        <div class="child-toggle" style="padding-left: 0">
                            <div class="child-toggle-spacer"></div>
                        </div>
        
                        <div class="row-inner">
                            <table class="status-row" style="width:100%"><tbody><tr>
                            <td colspan="7" style="text-align: center">
                            <?php 
                            if (empty($options->hide_manual_status_selectors)) {
                                _e('Drop any status here to make it manually selectable outside the main revision workflow.', 'publishpress-statuses-pro');
                            } else {
                                _e('Drop any status here to disable.', 'publishpress-statuses-pro');
                            }
                            ?>
                            </td>
                            </tr></tbody></table>
                        </div>
                    </div>
                    </li>
    
                    <?php endif;
                }
            },
            10, 2
        );
    
        add_filter(
            'publishpress_statuses_table_alternate_row_hidden',
            function ($hidden, $item, $status_type) {
                if (!empty($item->for_revision)) {
                    $options = \PublishPress_Statuses::instance()->options;
                    
                    $hidden = ('revision' != $status_type) || (!empty($options->hide_manual_status_selectors) && in_array($item->name, ['revision-deferred', 'revision-needs-work', 'revision-rejected']));
                }
                
                return $hidden;
            },
            10, 3
        );
    
        add_filter(
            'publishpress_statuses_table_alternate_row_class',
            function ($class, $item, $status_type) {
                if (!empty($item->for_revision)) {
                    $options = \PublishPress_Statuses::instance()->options;

                    if (empty($item->revision_alternate)) {
                        $class .= ' main-section';
                    }

                    $class .= ' revision-status';
        
                    if (!empty($item->revision_alternate)) {
                        $class .= (empty($options->hide_manual_status_selectors)) ? ' alternate-revision-status' : ' disabled-status';
                    }
                }
                
                return $class;
            },
            10, 3
        );
    
        // Status Add / Edit UI
        add_filter(
            'publishpress_statuses_edit_status_url',
            function ($url, $status) {
                if (!empty($status->public) || !empty($status->private) || ('future' == $status->name)) {
                    $url = add_query_arg('status_type', 'visibility', $url);
        
                } elseif ('pp_revision_status' == $status->taxonomy) {
                    $url = add_query_arg('status_type', 'revision', $url);
                }
                  
                   return $url;
              },
              10, 2
        );
    
        add_filter(
            'publishpress_statuses_management_title',
            function ($title, $status_type) {
                switch ($status_type) {
                    case 'visibility' :
                        $title = __('Add New Visibility Status', 'publishpress-statuses-pro');
                        break;
    
                    case 'revision' :
                        $title = __('Add New Revision Status', 'publishpress-statuses-pro');
                        break;
                }
            
                return $title;	
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_management_descript',
            function ($descript, $status_type) {
                switch ($status_type) {
                    case 'visibility' :
                        $descript = __('This status can be assigned to a post as a different form of Private Publication with its own capability requirements.', 'publishpress-statuses-pro');
                        break;
    
                    case 'revision' :
                        $descript = __('This status can be assigned to revisions of published posts.', 'publishpress-statuses-pro');
                        break;
                }
            
                return $descript;	
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_editable_taxonomies',
            function ($editable_taxonomies) {
                $editable_taxonomies []= 'pp_revision_status';
                            
                return $editable_taxonomies;	
            }
        );

        add_filter(
            'publishpress_statuses_status_type_to_taxonomy',
            function ($taxonomy, $status_type) {
                if ('revision' == $status_type) {
                    $taxonomy = 'pp_revision_status';	
                }
            
                return $taxonomy;	
            },
            10, 2
        );
    }

    // Statuses UI
    function fltStatusesAdminTitle($title, $status_type) {
        if ('revision' == $status_type) {
            if ($tx_obj = get_taxonomy('pp_revision_status')) {
                if (!empty($tx_obj->label)) {
                    return $tx_obj->label;
                }
            }
        }

        return $title;
    }
    
}
