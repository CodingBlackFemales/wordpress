<?php
$opt_val = get_option('rvy_edd_key');
$renewal_token = (!is_array($opt_val) || count($opt_val) < 2) ? '' : substr($opt_val['license_key'], 0, 16);

$url = site_url('');
$arr_url = wp_parse_url($url);
$site = urlencode(str_replace($arr_url['scheme'] . '://', '', $url));

wp_redirect('https://publishpress.com/pricing/?pkg=revisionary&site=' . $site . '&publishpress_account=' . $renewal_token);
exit;