<?php
return ['woocommerce_stripe' => ['label' => 'WooCommerce Stripe', 'classification_prefixes' => ['_stripe_unexpected_charge_flagged_' => 'internal'], 'classifications' => array_merge(
    array_fill_keys(['_stripe_status_before_hold', '_stripe_status_before_refund', '_wc_stripe_agentic_commerce_sync_excluded'], 'internal'),
    array_fill_keys(['_stripe_source_id', '_stripe_refund_id', '_stripe_intent_id', '_stripe_setup_intent', '_stripe_checkout_session_id', '_stripe_customer_id', '_transaction_id'], 'sensitive')
)]];
