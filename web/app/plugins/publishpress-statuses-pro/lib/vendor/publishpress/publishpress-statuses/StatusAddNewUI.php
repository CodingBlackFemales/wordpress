<?php
/** Form for adding a new Custom Status term **/ 
?>

<form class='add:the-list:' action="<?php esc_url(\PublishPress_Statuses::getLink()); ?>"
method='post' id='addstatus' name='addstatus'>

    <?php
    wp_nonce_field('custom-status-add-nonce');

    require_once(__DIR__ . '/StatusEditUI.php');
    \PublishPress_Statuses\StatusEditUI::mainTabContent();
    ?>
    <input type="hidden" name="page" value="publishpress-statuses" />
    <input type="hidden" name="action" value="add-status" />

    <?php 
    if (!$_taxonomy = \PP_Statuses_Functions::REQUEST_key('taxonomy')) {
        if ('visibility' == \PP_Statuses_Functions::REQUEST_key('status_type')) {
            $_taxonomy = 'post_visibility_pp';
        }
    }
    
    if ('post_status' != $_taxonomy) :?>
    <input type="hidden" name="taxonomy" value="<?php echo esc_attr($_taxonomy);?>" />
    <?php endif;?>

    <p class='submit'><?php
        submit_button(
            __('Add New Status', 'publishpress-statuses'),
            'primary',
            'submit',
            false
        ); ?>&nbsp;</p>
</form>