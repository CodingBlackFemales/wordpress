<?php

namespace Wpai\WordPress;


/**
 * Class AdminDismissibleNotice
 * @package Wpai\WordPress
 */
class AdminDismissibleNotice extends AdminNotice {
    /**
     * @var
     */
    private $noticeId;

    /**
     * AdminDismissibleNotice constructor.
     * @param $message
     * @param $noticeId
     */
    public function __construct($message, $noticeId) {
        parent::__construct($message);
        $this->noticeId = $noticeId;
    }

    /**
     *
     */
    public function showNotice() {
        ?>
        <div class="<?php echo $this->getType();?> wpallimport-dismissible" style="position: relative;" rel="wpai_dismiss_warnings_<?php echo $this->noticeId; ?>"><p>
                <?php echo $this->message; ?>
            </p>
            <button class="notice-dismiss wpai-general-notice-dismiss" type="button" data-noticeId="<?php echo $this->noticeId; ?>"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
        <?php
    }

    /**
     *
     */
    public function render() {
        add_action('admin_notices', array($this, 'showNotice'));
    }

    /**
     * @return string
     */
    public function getType() {
        return 'error';
    }

    /**
     * @return false|mixed|void
     */
    public function isDismissed() {
        $optionName = 'wpai_dismiss_warnings_'.$this->noticeId.'_notice_ignore';
        $oldOptionName = 'wpai_dismiss_warnings_'.$this->noticeId;
        return get_option($optionName, false) || get_option($oldOptionName, false);

    }
}