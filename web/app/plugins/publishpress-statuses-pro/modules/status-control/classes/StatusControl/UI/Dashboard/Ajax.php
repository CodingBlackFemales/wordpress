<?php
namespace PublishPress\Statuses\StatusControl\UI\Dashboard;

class Ajax
{
    // ported out due to PHP Warning and "headers already sent" when custom statuses are present
    public static function wp_ajax_find_posts()
    {
        global $wpdb;

        check_ajax_referer('find-posts');

        $post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects', 'or');
        unset($post_types['attachment']);

        $s = (!empty($_POST['ps'])) ? wp_unslash(sanitize_text_field(wp_unslash($_POST['ps']))) : '';
        $searchand = $search = '';
        $args = [
            'post_type' => array_keys($post_types),
            'post_status' => 'any',
            'posts_per_page' => 50,
        ];
        if ('' !== $s)
            $args['s'] = $s;

        $posts = get_posts($args);

        if (!$posts)
            wp_die(esc_html__('No items found.'));

        $html = '<table class="widefat"><thead><tr><th class="found-radio"><br /></th><th>' . esc_html__('Title') 
        . '</th><th class="no-break">' . esc_html__('Type') 
        . '</th><th class="no-break">' . esc_html__('Date') 
        . '</th><th class="no-break">' . esc_html__('Status') 
        . '</th></tr></thead><tbody>';
        
        $alt = '';
        foreach ($posts as $post) {
            $title = trim($post->post_title) ? $post->post_title : esc_html__('(no title)');
            $alt = ('alternate' == $alt) ? '' : 'alternate';

            switch ($post->post_status) {
                case 'publish' :
                case 'private' :
                    $stat = esc_html__('Published');
                    break;
                case 'future' :
                    $stat = esc_html__('Scheduled');
                    break;
                case 'pending' :
                    $stat = esc_html__('Pending Review');
                    break;
                case 'draft' :
                    $stat = esc_html__('Draft');
                    break;
                default :  // kevinB modification
                    if ($status_obj = get_post_status_object($post->post_status))
                        $stat = $status_obj->label;
                    else
                        $stat = $post->post_status;
            }

            if (in_array($post->post_date, [constant('PRESSPERMIT_MIN_DATE_STRING'), '0000-00-00 00:00:00'])) {
                $time = '';
            } else {
                /* translators: date format in table columns, see http://php.net/date */
                $time = mysql2date(__('Y/m/d'), $post->post_date);
            }

            $html .= '<tr class="' . trim('found-posts ' . $alt) . '">'
            . '<td class="found-radio"><input type="radio" id="found-' . $post->ID . '" name="found_post_id" value="' . esc_attr($post->ID) . '"></td>';
            
            $html .= '<td><label for="found-' . $post->ID . '">' . esc_html($title) . '</label></td>'
            . '<td class="no-break">' . esc_html($post_types[$post->post_type]->labels->singular_name) . '</td>'
            . '<td class="no-break">' . esc_html($time) . '</td><td class="no-break">' . esc_html($stat) . ' </td></tr>' . "\n\n";
        }

        $html .= '</tbody></table>';

        wp_send_json_success($html);
    }
}
