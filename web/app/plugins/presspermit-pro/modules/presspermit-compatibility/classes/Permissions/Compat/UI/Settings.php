<?php
namespace PublishPress\Permissions\Compat\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

/**
 * PP Compatibility Pack Settings
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2024, PublishPress
 * 
 */

class Settings
{
    function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 10);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 20);

        add_action('presspermit_network_options_pre_ui', [$this, 'network_options_pre_ui']);
        add_action('presspermit_network_options_ui', [$this, 'network_options_ui']);

        add_action('presspermit_teaser_text_ui', [$this, 'teaser_text_ui'], 20);
        add_filter('presspermit_teaser_enable_options', [$this, 'teaser_enable_options'], 10, 3);

        add_action('presspermit_teaser_type_row', [$this, 'act_teaser_type_row'], 10, 2);

        add_filter('presspermit_constants', [$this, 'flt_pp_constants'], 12);
    }

    function teaser_disable_bbp($types, $args)
    {
        unset($types['forum']);
        return $types;
    }

    function teaser_enable_options($options, $post_type, $current_setting)
    {
        if ('forum' == $post_type) {
            $options = array_intersect_key($options, ['0' => true, '1' => true]);
            $options[1] = esc_html__("Teaser text", 'presspermit-pro');
        }
        return $options;
    }

    function optionTabs($tabs)
    {
        if (is_multisite() && is_main_site())
            $tabs['network'] = esc_html__('Network', 'presspermit-pro');

        return $tabs;
    }

    function sectionCaptions($sections)
    {
        // Network tab
        if (is_multisite() && is_main_site()) {
            $new = [
                'groups' => esc_html__('Groups', 'presspermit-pro'),
            ];

            $key = 'network';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        if (function_exists('bbp_get_version')) {
            $new = [
                'forum_teaser' => esc_html__('Forum Teaser', 'presspermit-pro'),
            ];

            $key = 'teaser';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        return $sections;
    }

    function optionCaptions($captions)
    {
        $opt = [];

        if (is_multisite() && is_main_site()) {
            $opt['netwide_groups'] = esc_html__('Network-wide groups', 'presspermit-pro');
        }

        if (function_exists('bbp_get_version')) {
            $opt['topics_teaser'] = esc_html__('Teaser Topics', 'presspermit-pro');
            $opt['forum_teaser_hide_author_link'] = esc_html__('Hide Topic / Reply Author Link', 'presspermit-pro');
        }

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        if (is_multisite() && is_main_site()) {
            $new = [
                'groups' => ['netwide_groups'],
            ];

            $key = 'network';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        /* NOTE: all teaser options follow scope setting of do_teaser */
        if (function_exists('bbp_get_version')) {
            $sections['teaser']['forum_teaser'] = ['tease_topic_replace_content', 'forum_teaser_hide_author_link'];
        }

        return $sections;
    }

    function network_options_pre_ui()
    {
        if ( false && presspermit()->getOption('display_hints')) :
            ?>
        <div class="pp-optionhint">
            <?php // 'Additional settings provided by the %s module.'
            printf(
                esc_html(SettingsAdmin::getStr('module_settings_tagline')), 
                esc_html__('Compatibility Pack', 'presspermit-pro')
            );
            ?>
        </div>
    <?php
    endif;
    }

    function network_options_ui()
    {
        $ui = SettingsAdmin::instance(); 
        $tab = 'network';

        if (is_multisite() && is_main_site()) {
            $section = 'groups';                                    // --- GROUPS SECTION ---
            if (!empty($ui->form_options[$tab][$section])) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]); ?></th>
                        <td>
                            <?php
                            $ui->optionCheckbox('netwide_groups', $tab, $section, true, '');
                            ?>
                        </td>
                    </tr>
                <?php
            endif; // any options accessable in this section
        } // endif multisite
    }

    function teaser_text_ui()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'teaser';

        $pp = presspermit();

        if (!function_exists('bbp_get_version'))
            return;

        $section = 'forum_teaser';                                // --- FORUM TEASER SECTION ---

        
            $tease_post_types = (array)$pp->getOption('tease_post_types');
            $topics_teaser = $pp->getOption('topics_teaser');

            $tr_style = (!empty($tease_post_types['forum']) && $topics_teaser) ? '' : "display:none";

            ?>
            <div id="pp-teaser-text-forum" style="<?php echo esc_attr($tr_style); ?>">
            <hr style="margin: 40px 0 40px;">
            <h2 class="title"><?php echo esc_html($ui->section_captions[$tab][$section]);?></h2>
            <div>

            <?php
            // now draw the teaser replacement / prefix / suffix input boxes
            $user_suffixes = ['_anon', ''];

            $types_display = [
                'topic' => __('Topic Teaser Text (%s):', 'presspermit-pro'), 
                'reply' => __('Reply Teaser Text (%s):', 'presspermit-pro')
            ];

            $items_display = [
                'content' => __('First Reply', 'presspermit-pro'), 
                'other_content' => __('Other Replies', 'presspermit-pro')
            ];

            $div_display = ($tr_style) ? 'none' : 'block';

            echo "<div id='topics-teaserdef' style='margin-top: 2em;'>";

            foreach ($types_display as $type => $type_caption) {
                if ('topic' == $type)
                    $div_display = (in_array($topics_teaser, [1, '1', 'tease_topics'])) ? 'block' : 'none';
                else
                    $div_display = ($topics_teaser) ? 'block' : 'none';

                echo "<div id='" . esc_attr($type) . "_teaserdef' style='display:" . esc_attr($div_display) . ";'>";

                // separate input boxes to specify teasers for anon users and unpermitted logged in users
                foreach ($user_suffixes as $anon) {
                    $user_descript = ($anon) ?  __('Not Logged In', 'presspermit-pro') : __('Logged In Users', 'presspermit-pro');

                    echo '<h4>';
                    printf(esc_html($type_caption), esc_html($user_descript));
                    echo '</h4>';
                    echo ('<ul class="pp-textentries ppp-textentries">');

                    $action = 'replace';

                    echo ('<li>');
                    echo '<table class="form-table"><tbody>';

                    foreach (['content', 'other_content'] as $item) {
                        $option_name = "tease_{$type}_{$action}_{$item}{$anon}";
                        if (!$opt_val = $pp->getOption($option_name))
                            $opt_val = '';

                        $ui->all_options[] = $option_name;

                        $id = $option_name;
                        $name = $option_name;

                        echo "<tr><th><label for='" . esc_attr($id) . "'>";
                        echo esc_html($items_display[$item]) . ':';
                        echo '</label>';
                        ?>
                    </th>
                    <td>

                    <?php if ('content' == $item) : ?>
                        <textarea style="width:100%" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>"><?php echo esc_html($opt_val); ?></textarea>
                        <p class="pp-add-login-form">
                        <?php printf(
                            esc_html__( 'Insert a login form by using %s[login_form]%s shortcode.', 'presspermit-pro' ),
                            '<a href="#">',
                            '</a>'
                        );?>
                        </p>
                    <?php else : ?>
                        <input name="<?php echo esc_attr($name); ?>" type="text" class="regular-text" id="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($opt_val); ?>" />
                    <?php endif; ?>

                    </td>
                    </tr>
                    <?php
                    } // end foreach items

                    echo ('</tbody></table></li>');

                    echo ("</ul><br />");
                } // end foreach user_suffixes

                echo '</div>';
            } // end foreach types


        if (in_array('forum_teaser_hide_author_link', $ui->form_options[$tab][$section], true)) :
            $hint =  '';
            $ui->optionCheckbox('forum_teaser_hide_author_link', $tab, $section, $hint, '');
        endif;
        ?>

        </div>
        </div>
    <?php
    }

    function act_teaser_type_row($post_type, $teaser_setting)
    {
        if ('forum' == $post_type) {
            echo '<div style="margin-top:10px;">';
            
            $style = (!$teaser_setting) ? "display:none;" : '';
            echo '<select name="topics_teaser" id="topics_teaser" autocomplete="off" style="' . esc_attr($style) . '">';

            $topics_teaser = presspermit()->getOption('topics_teaser');
            $captions = [
                0 => esc_html__("Hide Topics and Replies", 'presspermit-pro'), 
                1 => esc_html__("Tease Topics and Replies", 'presspermit-pro'), 
                'tease_replies' => esc_html__("Show Topics, Tease Replies", 'presspermit-pro')
            ];
            
            foreach ($captions as $key => $value) {
                echo "\n\t<option value='" . esc_attr($key) . "' ";
                
                if ($topics_teaser == $key) echo ' selected ';

                echo ">" . esc_html($captions[$key]) . "</option>";
            }
            echo '</select>&nbsp;</div>';

            ?>
            <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                var ppNavMenuHideLinksTaxonomy = '<?php echo esc_attr(get_option("presspermit_teaser_hide_links_taxonomy", ''));?>';
        
                $('#teaser_usage-post select[name!="topics_teaser"]').on('change', function()
                {
                    var otype = $(this).next().html();
                    
                    if ('forum' == otype) {
                        $('#topics_teaser').toggle($(this).val() != 0);
                        $('#pp-teaser-text-forum').toggle($(this).val() != 0);
                    }
                });
            });
            /* ]]> */
            </script>

            <div id="pp_bbp_forum_teaser_template_notice" style="margin-top:5px;<?php if ($topics_teaser) echo 'display:none;'; ?>">
            <span class="pp-subtext">
            <?php
            esc_html_e('The single forum teaser may be customized by STYLESHEETPATH/press-permit/teaser-content-forum.php', 'presspermit-pro');
            echo '</span></div>';

            \PublishPress\Permissions\UI\SettingsAdmin::instance()->all_options[] = 'topics_teaser';
        }
    }

    function flt_pp_constants($pp_constants)
    {
        if (is_multisite()) {
            $type = 'user-selection';
            $consts = [
                'PP_NETWORK_GROUPS_SITE_USERS_ONLY',
                'PP_NETWORK_GROUPS_MAIN_SITE_ALL_USERS',
            ];
            foreach ($consts as $k) $pp_constants[$k] = (object)['descript' => SettingsAdmin::getConstantStr($k), 'type' => $type];
        }

        if (class_exists('BuddyPress', false)) {
            $type = 'buddypress';
            $consts = [
                'PPBP_GROUP_MODERATORS_ONLY',
                'PPBP_GROUP_ADMINS_ONLY',
            ];
            foreach ($consts as $k) $pp_constants[$k] = (object)['descript' => SettingsAdmin::getConstantStr($k), 'type' => $type];
        }

        if (defined('CMS_TPV_VERSION')) {
            $type = 'cms-tree-page-view';
            $consts = [
                'PP_CMS_TREE_NO_ADD',
                'PP_CMS_TREE_NO_ADD_PAGE',
                'PP_CMS_TREE_NO_ADD_CUSTOM_POST_TYPE_NAME_HERE',
            ];
            foreach ($consts as $k) $pp_constants[$k] = (object)['descript' => SettingsAdmin::getConstantStr($k), 'type' => $type];
        }

        if (class_exists('NestedPages')) {
            $type = 'nested-pages';
            $consts = [
                'PP_NESTED_PAGES_DISABLE_FILTERING',
                'PP_NESTED_PAGES_ENABLE_FILTERING',
                'PP_NESTED_PAGES_QUICKEDIT_ROLES',
                'PP_NESTED_PAGES_CONTEXT_MENU_ROLES',
                'PP_NESTED_PAGES_NO_CONTEXT_MENU_ALLOWANCE',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_LINK',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_PAGE',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_BEFORE',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_AFTER',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_TOP',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_BOTTOM',
                'PRESSPERMIT_NESTED_PAGES_LIMIT_CLONE',
            ];
            foreach ($consts as $k) $pp_constants[$k] = (object)['descript' => SettingsAdmin::getConstantStr($k), 'type' => $type];
        }

        return $pp_constants;
    }
}
