<form class="basic-settings" action="<?php echo esc_attr($context['form_action']); ?>" method="post">
    <?php
    settings_fields($context['options_group_name']);
    do_settings_sections($context['options_group_name']);

    wp_nonce_field( 'edit-publishpress-settings' );
    ?>

    <input type="hidden" name="publishpress_module_name[]" value="<?php echo esc_attr($context['module_name']); ?>"/>
    <input type="hidden" name="action" value="update"/>

    <?php submit_button(); ?>
</form>
