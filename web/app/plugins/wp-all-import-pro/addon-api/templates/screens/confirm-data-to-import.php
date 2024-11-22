<?php
/**
 * @var PMXI_Addon_Base $addon
 * @var string $prefix
 * @var string $prefix_id
 * @var array $options
 */

use Wpai\AddonAPI\PMXI_Addon_Base;

?>

<ul style="padding-left: 35px;">
    <?php if ( ! empty( $post['is_update'] ) ): ?>
        <li>
            <?php
            switch ( $post['update_logic'] ) {
                case 'full_update':
                    printf(esc_html__( 'all %s fields', 'wp-all-import-pro' ), $addon->name());
                    break;
                case 'only':
                    printf( __( 'only these %s fields : %s', 'wp-all-import-pro' ), $addon->name(), $post['fields_only_list'] );
                    break;
                case 'all_except':
                    printf( __( 'all %s fields except these: %s', 'wp-all-import-pro' ), $addon->name(), $post['fields_except_list'] );
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>
