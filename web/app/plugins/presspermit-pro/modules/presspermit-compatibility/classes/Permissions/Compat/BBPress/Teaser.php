<?php
namespace PublishPress\Permissions\Compat\BBPress;

class Teaser 
{
    function __construct() 
    {
        add_filter('presspermit_teaser_no_tease_types', [$this, 'no_tease_types']);
        add_filter('bbp_user_can_view_forum', [$this, 'user_can_view_forum'], 10, 3);
        add_filter('presspermit_teaser_types', [$this, 'teaser_forum_passthrough'], 20, 2);
        add_filter('user_has_cap', [$this, 'teaser_private_forums_cap'], 50, 3);
        add_filter('presspermit_posts_teaser', [$this, 'posts_teaser'], 60, 3);
        add_filter('bbp_get_topic_content', [$this, 'get_topic_content'], 10, 2);
        add_filter('bbp_get_reply_content', [$this, 'get_reply_content'], 10, 2);
        add_filter('posts_results', [$this, 'posts_hide_unreadable_private'], 101, 2);
        add_filter('bbp_get_reply_author_link', [$this, 'get_reply_author_link'], 10, 2);
    }

    function no_tease_types($types)
    {
        $types = array_diff_key($types, array_fill_keys(['forum', 'topic', 'reply'], 1));
        return $types;
    }

    function user_can_view_forum($retval, $forum_id, $user_id)
    {
        if (($user_id == presspermit()->getUser()->ID) 
        && apply_filters('presspermit_teaser_enabled', false, 'post', 'forum')
        ) {
            $status = get_post_field('post_status', $forum_id);

            if ('publish' == $status) {
                return true;
            }

            if ($hidden_stati = apply_filters('presspermit_teaser_hidden_statuses', 'forum')) {
                if (!in_array($status, $hidden_stati, true)) {
                    return true;
                }
            }
        }

        return $retval;
    }

    function teaser_forum_passthrough($types, $args)
    {
        if (!empty($args['context']) && ('results_query' != $args['context'])) {
            $types = array_diff($types, ['forum']);
        } elseif (!is_search()) {
            $types = array_unique(array_merge($types, ['reply']));
        }

        return $types;
    }

    function teaser_private_forums_cap($wp_sitecaps, $orig_reqd_caps, $args)
    {
        global $current_user;

        if ($args[1] != $current_user->ID) {
            return $wp_sitecaps;
        }

        $cap = reset($orig_reqd_caps);

        if (('read_private_forums' == $cap) && apply_filters('presspermit_teaser_enabled', false, 'post', 'forum')) {
            $wp_sitecaps['read_private_forums'] = true;
        }

        return $wp_sitecaps;
    }

    function posts_teaser($results, $post_types = [], $args = [])
    {
        global $wp_query;

        $post_type = (isset($wp_query->query_vars['post_type'])) ? $wp_query->query_vars['post_type'] : '';

        if (!function_exists('bbp_is_search') || (!in_array($post_type, ['forum', 'topic', 'reply'], true) && !bbp_is_search())
        ) {
            return $results;
        }

        $pp = presspermit();

        if (bbp_is_search()) {
            foreach (array_keys($results) as $key) {
                $forum_id = ('forum' == $results[$key]->post_type) 
                ? $results[$key]->ID 
                : get_post_meta($results[$key]->ID, '_bbp_forum_id', true);

                if (!current_user_can('read_post', $forum_id)) {
                    $topics_teaser = $pp->getOption('topics_teaser');

                    if (!in_array($results[$key]->post_type, ['topic', 'reply'], true)) {
                        continue;
                    }

                    if (!$topics_teaser) {
                        unset($results[$key]);

                    } elseif (('reply' == $results[$key]->post_type) || ('tease_replies' == $topics_teaser)) {
                        do_action('presspermit_teaser_init_template');
                        $results[$key]->post_content = apply_filters('presspermit_get_teaser_text', '', 'replace', 'content', 'post', 'forum');
                    }
                }
            }
        } else {
            $topics_teaser = $pp->getOption('topics_teaser');

            if (!$topics_teaser && (count($results) == 1)) {
                foreach ($results as $key => $p) {
                    if ('forum' == $p->post_type) {
                        add_action("bbp_template_after_single_forum", [$this, 'forum_teaser']);
                        add_filter('bbp_get_template_part', [$this, 'suppress_notopics_msg'], 50, 3);
                    }
                }
            }

            foreach ($results as $key => $p) {
                if (in_array($p->post_type, ['topic', 'reply'], true)) {
                    if ($forum_id = get_post_meta($p->ID, '_bbp_forum_id', true)) {
                        if (!current_user_can('read_post', $forum_id)) {
                            if ($topics_teaser) {
                                // in case a custom theme takes theme/reply content from here
                                $results[$key]->post_content = ('topic' == $p->post_type) 
                                ? _pp_bbp_get_topic_content($p->post_content, $p->ID) 
                                : _pp_bbp_get_reply_content($p->post_content, $p->ID);
                            } else {
                                unset($results[$key]);
                            }
                        }
                    }
                }

            }
        }

        return $results;
    }

    function get_topic_content($content, $topic_id)
    {
        if ('tease_replies' === presspermit()->getOption('topics_teaser')) {
            return $content;
        }

        static $done_once;
        $option_variable = (!empty($done_once)) ? 'other_content' : 'content';
        $done_once = true;

        return _pp_bbp_get_content($content, $topic_id, 'topic', $option_variable);
    }

    function get_reply_content($content, $reply_id)
    {
        $post_type = get_post_field('post_type', $reply_id);

        if ('reply' == $post_type) {
            static $done_once;
            $option_variable = (!empty($done_once)) ? 'other_content' : 'content';
            $done_once = true;
        } else {
            if ('tease_replies' === presspermit()->getOption('topics_teaser')) {
                return $content;
            }

            $option_variable = 'content';
        }

        return _pp_bbp_get_content($content, $reply_id, $post_type, $option_variable);
    }

    function get_content($content, $reply_id, $post_type, $option_variable)
    {
        if ($forum_id = get_post_meta($reply_id, '_bbp_forum_id', true)) {

            if (!current_user_can('read_post', $forum_id)) {
                do_action('presspermit_teaser_init_template');

                $teaser_operation = 'replace';
                $object_type = 'forum';
                $variable = 'content';

                global $current_user;
                $user = $current_user;

                $anon = ($user->ID == 0) ? '_anon' : '';

                if ($msg = presspermit()->getOption("tease_{$post_type}_replace_{$option_variable}{$anon}", true)) {
                    if (defined('PP_TRANSLATE_TEASER')) {
                        $msg = translate($msg, 'presspermit-pro');

                        if (!empty($msg) && !is_null($msg) && is_string($msg))
                            $msg = htmlspecialchars_decode($msg);
                    }

                    if ('content' == $variable) {
                        $msg = str_replace('[login_form]', is_singular() ? wp_login_form(['echo' => false,]) : '', $msg);
                    }

                    $content = apply_filters(
                        'presspermit_teaser_text', 
                        $msg, 
                        $teaser_operation, 
                        $variable, 
                        $object_type, 
                        (bool)$anon
                    );

                    $first_pass = ('content' == $option_variable);
                    $content = apply_filters("pp_{$post_type}_teaser_text", $content, $reply_id, $first_pass);
                }
            }
        }

        return $content;
    }

    function suppress_notopics_msg($templates, $slug, $name)
    {
        if (('feedback' == $slug) && ('no-topics' == $name))
            return ['pp-teaser-no-template'];
        elseif (('form' == $slug) && ('topic' == $name) && !current_user_can('publish_topics'))
            return ['pp-teaser-no-template'];

        return $templates;
    }

    function forum_teaser()
    {
        if (!locate_template(["press-permit/teaser-content-forum.php"], true)) :
            ?>
            <div class="pp-bbp-teaser">
                <?php
                do_action('presspermit_teaser_init_template');
                return apply_filters('presspermit_get_teaser_text', '', 'replace', 'content', 'post', 'forum');
                ?>
            </div>
        <?php
        endif;
    }

    function posts_hide_unreadable_private($results, $_query_obj)
    {
        if (apply_filters('presspermit_teaser_enabled', false, 'post', 'forum')) {
            return $results;
        }

        foreach (array_keys($results) as $key) {
            if ('private' == $results[$key]->post_status) {
                if ($teased_posts = apply_filters('presspermit_teased_posts', [])) { 
                    if (!empty($teased_posts[$results[$key]->ID])) {
                        unset($results[$key]);
                    }
                }
            }
        }

        return $results;
    }

    function get_reply_author_link($author_link, $r)
    {
        if (presspermit()->getOption('forum_teaser_hide_author_link')) {
            $dom = new DOMDocument;
            @$dom->loadHTML($author_link);
            $images = $dom->getElementsByTagName('img');

            $img_html = '';

            foreach ($images as $img) {
                $img_html .= $img->ownerDocument->saveXML($img);
            }

            $author_link = ($img_html) ? $img_html : '';
        }
        return $author_link;
    }
}
