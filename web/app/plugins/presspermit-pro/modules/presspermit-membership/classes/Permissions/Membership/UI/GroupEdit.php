<?php
namespace PublishPress\Permissions\Membership\UI;

class GroupEdit
{
    private $isTableClosed = false;

    function __construct() 
    {
        add_action( 'presspermit_admin_ui', [$this, 'actAdminUI'] );
        add_filter( 'presspermit_override_agent_select_js', [$this, 'fltOverrideAgentSelectScript']);

        add_filter( 'presspermit_suppress_agents_selection_label', [$this, 'fltAgentsSelectionLabel'], 10, 3);

        add_filter( 'presspermit_agents_selection_ui_attribs', [$this, 'fltAgentsSelectionAttribs'], 10, 4 );
        add_filter( 'presspermit_agents_selection_ui_csv', [$this, 'fltAgentsSelectionCSV'], 10, 3 );
        add_action( '_presspermit_agents_selection_ui_select_pre', [$this, 'actAgentsSelectionPreTable'] );
        add_action( 'presspermit_agents_selection_ui_select_pre', [$this, 'actAgentsSelectionPreDateFields'] );

        add_filter( 'presspermit_group_members_orderby', [$this, 'fltGroupMembersOrderby']);

        // work around conflict with bundled js on some sites
        if ( defined( 'PPM_DATEPICKER_WORKAROUND' ) ) {
            add_action( 'admin_print_scripts', [$this, 'actScripts'], 20 );
        }
    }

    function actScripts() {
        ?>
        <script type="text/javascript" src="<?php echo esc_url(site_url('')) . '/wp-includes/js/jquery/ui/datepicker.min.js';?>"></script>
        <?php
    }

    function actAdminUI() {
        if ( in_array( presspermitPluginPage(), ['presspermit-edit-permissions', 'presspermit-group-new'], true ) ) {
            $path = plugins_url( '', PRESSPERMIT_MEMBERSHIP_FILE );
            wp_enqueue_style( 'presspermit-membership', $path . '/common/css/membership.css', [], PRESSPERMIT_MEMBERSHIP_VERSION );
            wp_enqueue_style( 'pp-datepicker', $path . '/common/css/jquery/datepicker.css', [], PRESSPERMIT_MEMBERSHIP_VERSION );
            
            if ( ! defined( 'PPM_DATEPICKER_WORKAROUND' ) ) {
                wp_enqueue_script( 'jquery-ui-datepicker', '', ['jquery-ui-core'], false, true );
            }
        }
    }

    function fltOverrideAgentSelectScript() {
        $path = plugins_url( '', PRESSPERMIT_MEMBERSHIP_FILE );
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        wp_enqueue_script( 
            'presspermit-agent-select', 
            $path . "/common/js/agent-select{$suffix}.js", ['jquery', 'jquery-form'], 
            PRESSPERMIT_MEMBERSHIP_VERSION, 
            true 
        );
        
        return true;
    }

    function fltGroupMembersOrderby( $orderby ) {
        return "ORDER BY FIELD(u2g.status, 'active', 'scheduled', 'expired'), u.user_login";
    }

    function actAgentsSelectionPreTable( $id_suffix ) {
        if ( 'member' == $id_suffix ) :
        ?>
            </table>
            <table>
        <?php 
            $this->isTableClosed = true;
        endif;
    }

    function actAgentsSelectionPreDateFields( $id_suffix ) {
        if ( 'member' == $id_suffix ) :
            if ( $this->isTableClosed ) :
            ?>
                <td>
                <table>
                <tr>
                <td style="vertical-align: middle;"><?php esc_html_e('From:', 'presspermit');?></td> 
                <td><input type="text" id="pp_member_start" name="pp_member_start" size="9" placeholder="<?php esc_attr_e('Select Date', 'presspermit');?>" title="<?php echo esc_attr('Select membership start date or enter number of days to delay. Blank or zero value means immediate membership.', 'presspermit');?>" /></td>
                </tr>
                
                <tr>
                <td style="vertical-align: middle;"><?php esc_html_e('To:', 'presspermit');?></td>
                <td><input type="text" id="pp_member_end" name="pp_member_end" size="9" placeholder="<?php esc_attr_e('Select Date', 'presspermit');?>" title="<?php echo esc_attr('Select membership end date or enter duration in days. Blank or zero value means perpetual membership.', 'presspermit');?>" /></td>
                </tr>
                </table>
                </td>
            <?php else : ?>
                <span class="pp-member-select-from-date">
                <?php esc_html_e('From:', 'presspermit');?>
                <input type="text" id="pp_member_start" name="pp_member_start" size="9" placeholder="<?php esc_attr_e('Select Date', 'presspermit');?>" title="<?php echo esc_attr('Select membership start date or enter number of days to delay. Blank or zero value means immediate membership.', 'presspermit');?>" />
                </span>

                <span class="pp-member-select-to-date">
                <?php esc_html_e('To:', 'presspermit');?>
                <input type="text" id="pp_member_end" name="pp_member_end" size="9" placeholder="<?php esc_attr_e('Select Date', 'presspermit');?>" title="<?php echo esc_attr('Select membership end date or enter duration in days. Blank or zero value means perpetual membership.', 'presspermit');?>" />
                </span>
            <?php 
            endif;
        endif;
    }

    function fltAgentsSelectionLabel($suppress, $id_suffix, $args) {
        if ( 'member' == $id_suffix ) {
            $url = add_query_arg( 'pp_refresh_member_status', '1' );

            printf( 
                esc_html__( 'Current Selections: %1$s' ), 
                '<span style="float:right;display:flex;align-items:center;gap:4px;"><small><a href="' . esc_url($url) . '" class="button button-secondary" title="' . esc_attr__('refresh member status', 'presspermit') . '" style="display:flex;align-items:center;font-weight: normal;"><span class="dashicons dashicons-update" style="font-size:18px;line-height:1;margin-right:2px;"></span>' . esc_html__('Refresh', 'presspermit') . '</a></small>', 
                '</span>' 
            );

            return true;
        }

        return $suppress;
    }

    function fltAgentsSelectionCSV( $csv, $id_suffix, $current_selections ) {
        if ( 'member' == $id_suffix ) {
            $csv = '';
            foreach ( $current_selections as $agent ) {
                $start_caption = '';
                $end_caption = '';
                
                // phpcs Note: time zone is accounted for

                // phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date

                if ( $agent->date_limited ) {
                    if ( $start_stamp = strtotime( $agent->start_date_gmt ) )
                        $start_caption = date( 'Y/m/d', $start_stamp );
                    
                    if ( $end_stamp = strtotime( $agent->end_date_gmt ) )
                        $end_caption = date( 'Y/m/d', $end_stamp );
                }

                $csv .= $agent->ID . '|' . str_replace( '/', '-', $start_caption ) . '|' . str_replace( '/', '-', $end_caption ) . ',';
            }
        }

        return $csv;
    }

    function fltAgentsSelectionAttribs( $return, $agent_type, $id_suffix, $stored_agent ) {
        if ( 'member' == $id_suffix ) {
            $display_property = (( 'user' == $agent_type ) && ! defined( 'PP_USER_RESULTS_DISPLAY_NAME' )) 
            ? 'user_login' 
            : 'display_name'; 
        
            $class = '';
            $mindate_attrib = '';
            $maxdate_attrib = '';
            $start_caption = '';
            $end_caption = '';
            
            $_title = (( 'user' == $agent_type ) && ( empty($stored_agent->display_name) || defined('PP_USER_RESULTS_DISPLAY_NAME') )) 
            ? $stored_agent->user_login 
            : $stored_agent->display_name;
            
            if ( $stored_agent->date_limited ) {
                $start_stamp = strtotime( $stored_agent->start_date_gmt );

                if ($start_stamp && (!in_array($stored_agent->start_date_gmt, [constant('PRESSPERMIT_MIN_DATE_STRING'), '0000-00-00 00:00:00']))) {
                    $start_caption = date( 'Y/m/d', $start_stamp );
                    $mindate_attrib = str_replace('/', '-', $start_caption );
                }
                
                $end_stamp = strtotime( $stored_agent->end_date_gmt );
                if ( $end_stamp && ( $end_stamp < strtotime(PRESSPERMIT_MAX_DATE_STRING) ) ) {
                    $end_caption = date( 'Y/m/d', $end_stamp );
                    $maxdate_attrib = str_replace('/', '-', $end_caption );
                }

                $return['user_caption'] = sprintf( 
                    __( '%1$s (%2$s - %3$s)', 'presspermit-pro' ), 
                    $stored_agent->$display_property, 
                    $start_caption, 
                    $end_caption 
                );
                
                $_title = sprintf( __( '%1$s (%2$s - %3$s)', 'presspermit-pro' ), $_title, $start_caption, $end_caption );
                
                if ( 'scheduled' == $stored_agent->status ) { 
                    $class = ' class="pp-scheduled"';
                    $title = sprintf( __('SCHEDULED: %s', 'presspermit'), $_title );
                } elseif ( 'expired' == $stored_agent->status ) { 
                    $class = ' class="pp-expired"';
                    $title = sprintf( __('EXPIRED: %s', 'presspermit'), $_title );
                } elseif ( $end_caption ) {
                    $title = sprintf( __('ACTIVE with future expiration: %s', 'presspermit'), $_title );
                } else {
                    $title = sprintf( __('ACTIVE: %s', 'presspermit'), $_title );
                }
            } else {
                $return['user_caption'] = $stored_agent->$display_property;
                $title = sprintf( __('ACTIVE: %s', 'presspermit'), $_title );
            }
            
            $return['title'] = $title;
            $return['class'] = $class;
            $return['data-startdate'] = $mindate_attrib;
            $return['data-enddate'] = $maxdate_attrib;
        }
        
        return $return;
    }
}
