<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressPro\Notifications\Workflow\Step\Receiver;

use PublishPress\Notifications\Workflow\Step\Receiver\Receiver_Interface;
use PublishPress\Notifications\Workflow\Step\Receiver\Simple_Checkbox;

class Network_Admin extends Simple_Checkbox implements Receiver_Interface
{
    const META_KEY = '_psppno_tonetworkadmin';

    const META_VALUE = 'network_admin';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name        = 'network_admin';
        $this->label       = __('Network Administrator', 'publishpress-pro');
        $this->option_name = 'receiver_network_admin';

        parent::__construct();
    }

    /**
     * Filters the list of receivers for the workflow. Returns the list of IDs.
     *
     * @param array $receivers
     * @param \WP_Post $workflow
     * @param array $args
     *
     * @return array
     */
    public function filter_workflow_receivers($receivers, $workflow, $args)
    {
        // If checked, add the authors to the list of receivers
        if ($this->is_selected($workflow->ID)) {

            $mainSiteId = get_main_site_id();
            switch_to_blog($mainSiteId);
            $admin_email = get_site_option('admin_email');
            restore_current_blog();

            $receivers[] = [
                'receiver' => $admin_email,
                'channel'  => 'email',
                'group'    => self::META_VALUE
            ];

            /**
             * Filters the list of receivers, but triggers only when the site admin is selected.
             *
             * @param array $receivers
             * @param \WP_Post $workflow
             * @param array $args
             */
            $receivers = apply_filters('publishpress_notif_workflow_receiver_site_admin', $receivers, $workflow, $args);
        }

        return $receivers;
    }

    /**
     * Add the respective value to the column in the workflow list
     *
     * @param array $values
     * @param int $post_id
     *
     * @return array
     */
    public function filter_receivers_column_value($values, $post_id)
    {
        if ($this->is_selected($post_id)) {
            $values[] = esc_html__('Network Administrator', 'publishpress-pro');
        }

        return $values;
    }
}
