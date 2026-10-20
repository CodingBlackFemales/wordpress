<?php

namespace PublishPress\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('pp_manage_settings')) {
    wp_die(esc_html(PWP::__wp('Cheatin&#8217; uh?')));
}

$site = preg_replace('#^https?://#i', '', untrailingslashit(site_url('')));
$renewal_url = add_query_arg(
    [
        'pkg' => 'presspermit-pro',
        'site' => $site,
    ],
    'https://publishpress.com/pricing/'
);

wp_redirect(esc_url_raw($renewal_url));
exit;
