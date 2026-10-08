<?php

?>

<form class='basic-settings'
        action="<?php
        echo esc_url(
            \PublishPress_Statuses::getLink(['page' => 'publishpress-statuses-settings'])
        ); ?>"
        method='post'>

    <?php
    settings_fields(\PublishPress_Statuses::SETTINGS_SLUG); ?>
    <?php
    do_settings_sections(\PublishPress_Statuses::SETTINGS_SLUG); ?>
    <?php
    echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
            'publishpress_statuses'
        ) . '" />'; ?>

    <br />

    <input type='hidden' name='pp_tab' value='<?php !empty($_REQUEST['pp_tab']) ? esc_attr(sanitize_key(str_replace('#', '', wp_unslash($_REQUEST['pp_tab'])))) : ""; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>' />
    <?php
    submit_button(); 
    ?>

    <?php
    wp_nonce_field('edit-publishpress-settings'); ?>
</form>