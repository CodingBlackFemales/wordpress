<?php

namespace Wpai\Reviews;

class ReviewsUI
{

    private $reviewLogic;

    public function __construct()
    {
        $this->reviewLogic = new ReviewLogic();
    }

    public function render()
    {

        if($this->reviewLogic->shouldShowReviewModal()) {

            ?>
            <style type="text/css">
                .wpallimport-plugin .wpai-reviews-notice {
                    margin-top: 40px;
                    padding-top: 18px;
                    padding-bottom: 22px;
                }

                .wpai-reviews-notice h1 {
                    color: #435F9A;
                    font-size: 1.4em;
                    font-weight: 500;
                    padding: 0;
                }

                .wpai-buttons-container {
                    margin-top: 10px;
                }

                .wpai-reviews-notice .wpai-buttons-container button, #wpai-feedback button {
                    padding: 6px 10px;
                    margin-right: 9px;
                    position: relative;
                    text-decoration: none;
                    border: 1px solid #435F9A;
                    border-radius: 2px;
                    text-shadow: none;
                    font-weight: 500;
                    font-size: 1.1em;
                    line-height: normal;
                    color: #435F9A;
                    cursor: pointer;
                    background-color: white;
                }

                .wpai-reviews-notice .wpai-buttons-container button:hover, #wpai-feedback button:hover {
                    background: #f0f0f1;
                    border-color: #0a4b78;
                    color: #0a4b78;
                }
                .wpai-reviews-notice .wpai-buttons-container button:focus, #wpai-feedback button:focus {
                    border: 1px solid rgba(0, 0, 0, 0.5);
                    outline: none;
                }

                .wpai-reviews-notice button:hover {
                    background-color: #FAFAFA;
                }

                #wpai-review {
                    display: none;
                    justify-content: flex-start;
                    align-items: baseline;
                }

                #wpai-review p, #wpai-feedback p {
                    display: block;
                    font-size: 1.1em;
                }

                #wpai-review .wpai-buttons-container {
                    justify-content: flex-start;

                }

                #wpai-feedback {
                    display: none;
                    justify-content: flex-start;
                    align-items: baseline;
                }

                #wpai-feedback textarea {
                    width: 100%;
                    height: 100px;
                }

                #wpai-feedback .wpai-submit-feedback {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    margin-top: 10px;
                }

                #wpai-feedback .wpai-submit-feedback button {
                    margin-right: 10px;
                }

                .wpai-reviews-notice .notice-dismiss {
                    position: relative;
                    float: right;
                    top: -15px;
                    right: -10px;
                }
                .wpai-reviews-notice .notice-dismiss:focus {
                    border: none;
                    box-shadow: none;
                }


                .wpai-submit-confirmation {
                    padding-top: 20px;
                    padding-bottom: 20px;
                    display: none;
                }
            </style>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('.wpai-review-buttons button').on('click', function (e) {

                        e.preventDefault();
                        var val = jQuery(this).data('review');

                        if (val === 'good') {
                            jQuery('#wpai-ask-for-review').fadeOut(function () {
                                jQuery('#wpai-review').fadeIn();
                            });
                        } else {
                            jQuery('#wpai-ask-for-review').fadeOut(function () {
                                jQuery('#wpai-feedback').fadeIn();
                            });
                        }

                        return false;
                    });

                    jQuery('.wpai-reviews-notice .notice-dismiss').on('click', function(e){

                        e.preventDefault();
                        e.stopImmediatePropagation();
                        var request = {
                            action: 'wpai_dismiss_review_modal',
                            security: wp_all_import_security,
                            modal_type: jQuery('#wpai-modal-type').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {},
                            dataType: "json"
                        });

                        jQuery('.wpai-reviews-notice').slideUp();
                    });

                    jQuery('.review-link').on('click', function(){

                        var request = {
                            action: 'wpai_dismiss_review_modal',
                            security: wp_all_import_security,
                            modal_type: jQuery('#wpai-modal-type').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {},
                            dataType: "json"
                        });

                        jQuery('.wpai-reviews-notice').slideUp();

                    });

                    jQuery('.wpai-submit-feedback button').on('click', function(){

                        jQuery(this).prop("disabled", true);

                        var request = {
                            action: 'wpai_send_feedback',
                            modal_type: jQuery('#wpai-modal-type').val(),
                            security: wp_all_import_security,
                            plugin: jQuery('#wpai-modal-type').val(),
                            message: jQuery('#wpai-feedback-message').val()
                        };

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            data: request,
                            success: function(response) {
                                jQuery('.wpai-submit-confirmation').show();
                                jQuery('.wpai-review-form').hide();

                            },
                            dataType: "json"
                        });

                    });
                });
            </script>
            <input type="hidden" id="wpai-modal-type" value="<?php esc_attr_e($this->reviewLogic->getModalType()) ;?>" />
            <div style="" class="notice notice-info wpai-reviews-notice">
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                <div id="wpai-ask-for-review">
                    <h1><?php esc_html_e($this->reviewLogic->getModalText(), 'wp-all-import-plugin'); ?></h1>

                    <div class="wpai-buttons-container wpai-review-buttons">
                        <button data-review="good"><?php esc_html_e('Good', 'wp-all-import-plugin'); ?></button>
                        <button data-review="ok"><?php esc_html_e('Just Ok', 'wp-all-import-plugin'); ?></button>
                        <button data-review="bad"><?php esc_html_e('Bad', 'wp-all-import-plugin'); ?></button>
                    </div>
                </div>
                <div id="wpai-review">
                    <h1><?php esc_html_e('That is great to hear, thank you for the feedback!', 'wp-all-import-plugin'); ?></h1>
                    <p>
                        <?php esc_html_e("Would you be willing to do us a small favor? Unhappy customers are quick to publicly complain, but happy customers rarely speak up and share their good experiences.", 'wp-all-import-plugin'); ?>
                        </br>
                        <?php esc_html_e("If you have a moment, we would love for you to review our add-on in the WordPress.org plugin repository.", 'wp-all-import-plugin'); ?>
                    </p>
                    <div class="wpai-buttons-container">
                        <a class="review-link" href="<?php echo esc_attr($this->reviewLogic->getReviewLink()); ?>" target="_blank">
                            <button><?php printf(esc_html__('Review %s', 'wp-all-import-plugin'), $this->reviewLogic->getPluginName() ); ?></button>
                        </a>
                    </div>
                </div>
                <div id="wpai-feedback">
                    <div class="wpai-review-form">
                        <h1><?php esc_html_e('Thank you for your feedback, it really helps us improve our products.', 'wp-all-import-plugin'); ?></h1>
                        <p><?php esc_html_e('If you could improve one thing about WP All Import, what would it be?', 'wp-all-import-plugin'); ?></p>
                        <textarea id="wpai-feedback-message"></textarea>
                        <div class="wpai-submit-feedback">
                            <button><?php esc_html_e('Submit', 'wp-all-import-plugin'); ?></button>
                        </div>
                    </div>
                    <div class="wpai-submit-confirmation">
                        Thank you for your feedback. Your message was emailed to support@wpallimport.com from <?php echo get_option('admin_email'); ?>. If you do not receive a confirmation email, it means we didn't receive your message for some reason.
                    </div>

                </div>
            </div>
            <?php
        }
    }

}