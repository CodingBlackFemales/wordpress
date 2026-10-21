<?php
namespace PublishPress\Permissions\Compat;

class WooCommerce
{
    function __construct() 
    {
        add_filter('woocommerce_product_is_visible', [$this, 'woo_visibility_fix'], 10, 2);
        add_filter('woocommerce_is_purchasable', [$this, 'woo_visibility_fix'], 10, 2);
    }

    function woo_visibility_fix($visible, $product)
    {
        if ($visible) {
            return $visible;
        }

        if (is_scalar($product)) {
            $product_id = $product;

        } elseif (is_object($product) && !empty($product->post) && !empty($product->post->post_status)) {
            $product_id = $product->post->ID;

        } elseif (is_object($product) && !empty($product->ID)) {
            $product_id = $product->ID;
        }

        if (!empty($product_id)) {
            $visible = current_user_can('read_post', $product_id);
        }

        return $visible;
    }
}
