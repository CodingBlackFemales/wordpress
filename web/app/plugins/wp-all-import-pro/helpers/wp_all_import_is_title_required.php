<?php

function wp_all_import_is_title_required( $custom_type ) {
    $types_title_not_required = array('shop_order', 'import_users', 'shop_customer', 'comments', 'woo_reviews');
    $supports_title = !in_array($custom_type, $types_title_not_required);

    return apply_filters('pmxi_types_current_type_supports_title', $supports_title, $custom_type);
}