<?php
namespace PublishPress\Statuses\StatusControl\UI;

class StatusHelper
{
    public static function getUrlProperties(&$url, &$referer, &$redirect)
    {
        $url = apply_filters( 'presspermit_permits_base_url', 'admin.php' );

        if (\PP_Statuses_Functions::empty_REQUEST() && \PP_Statuses_Functions::SERVER_url('REQUEST_URI')) {
            $referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr(stripslashes(esc_url_raw(\PP_Statuses_Functions::SERVER_url('REQUEST_URI')))) . '" />';
       
        } elseif ($wp_http_referer = \PP_Statuses_Functions::REQUEST_url('wp_http_referer')) {
            $redirect = esc_url_raw(remove_query_arg(['wp_http_referer', 'updated', 'delete_count'], stripslashes(esc_url_raw($wp_http_referer))));
            $referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr($redirect) . '" />';
        } else {
            $redirect = "$url?page=publishpress-statuses";
            $referer = '';
        }
    }
}
