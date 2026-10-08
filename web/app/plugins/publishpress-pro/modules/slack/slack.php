<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

if (! class_exists('PP_Slack')) {
    /**
     * class PP_Slack
     */
    #[\AllowDynamicProperties]
    class PP_Slack extends PP_Module
    {

        const METADATA_TAXONOMY = 'pp_slack_meta';

        const METADATA_POSTMETA_KEY = '_pp_slack_meta';

        const SETTINGS_SLUG = 'pp-slack-settings';

        const THEME_FULL = 'full';

        const THEME_CLEAN = 'clean';

        const SLACK_API_URL = 'https://slack.com/api/';

        const CACHE_GROUP = 'publishpres-pro-slack';

        const ONE_HOUR_IN_SECONDS = 60 * 60;

        const DEFAULT_SLACK_CHANNEL = '#general';

        public $module_name = 'slack';

        protected $requirement_instances;

        public $module;

        /**
         * Construct the PP_Slack class
         */
        public function __construct()
        {
            $this->viewsPath = dirname(dirname(dirname(__FILE__))) . '/views';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title' => __('Slack', 'publishpress-pro'),
                'short_description' => false,
                'extended_description' => false,
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-feedback',
                'slug' => 'slack',
                'default_options' => [
                    'enabled' => 'on',
                    'post_types' => ['post'],
                    'show_warning_icon_submit' => 'no',
                    'license_key' => '',
                    'license_status' => '',
                    'channel' => 'email',
                    'notification_theme' => self::THEME_FULL,
                ],
                'configure_page_cb' => 'print_configure_view',
                'options_page' => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_slack_requirements_default_options', $args['default_options']);

            $this->module = PublishPress()->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            if (is_admin()) {
                add_action('admin_init', [$this, 'register_settings']);

                add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

                add_action('wp_ajax_publishpress_pro_send_slack_test_message', [$this, 'handle_send_slack_test_message']
                );
                add_action(
                    'wp_ajax_publishpress_pro_collect_slack_oauth_token',
                    [$this, 'handle_collect_slack_oauth_token']
                );
                add_action(
                    'wp_ajax_publishpress_pro_delete_slack_oauth_token',
                    [$this, 'handle_delete_slack_oauth_token']
                );
            }

            add_filter('publishpress_notif_workflow_steps_channel', [$this, 'filter_workflow_steps_channel']);

            /**
             * Filters the option to enable or not notifications. Allow to block
             * the notifications to customize the workflow or process.
             *
             * @param bool $enable_notifications
             */
            $enable_notifications = apply_filters('publishpress_slack_enable_notifications', true);

            if ($enable_notifications) {
                // Cancel the PublishPress notifications
                remove_all_actions('pp_send_notification_status_update');
                remove_all_actions('pp_send_notification_comment');

                // Registers the Slack notifications
                add_action('pp_send_notification_status_update', [$this, 'send_notification'], 9);
                add_action('pp_send_notification_comment', [$this, 'send_notification'], 9);
            }

            add_filter('publishpress_slack_message_text', [$this, 'filter_slack_text']);
        }

        /**
         * Load default editorial metadata the first time the module is loaded
         *
         * @since 0.7
         */
        public function install()
        {
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
        }

        /**
         * Filters the list of classes to be loaded at the Channel step for
         * the notification workflows
         *
         * @param array $classes
         */
        public function filter_workflow_steps_channel($classes)
        {
            $classes[] = '\\PublishPress\\Addon\\Slack\\Workflow\\Step\\Channel\\Slack';

            return $classes;
        }

        /**
         * Generate a link to one of the editorial metadata actions
         *
         * @param array $args (optional) Action and any query args to add to the URL
         *
         * @return string $link Direct link to complete the action
         * @since 0.7
         *
         */
        protected function get_link($args = [])
        {
            $args['page'] = 'pp-modules-settings';
            $args['module'] = 'pp-slack-settings';

            return add_query_arg($args, get_admin_url(null, 'admin.php'));
        }

        /**
         * Print the content of the configure tab.
         */
        public function print_configure_view()
        {
            $view = new \PublishPress\Core\View();

            echo $view->render(
                'settings-tab',
                [
                    'form_action' => menu_page_url($this->module->settings_slug, false),
                    'options_group_name' => $this->module->options_group_name,
                    'module_name' => $this->module->slug,
                ],
                PUBLISHPRESS_PRO_DIR_PATH . '/views'
            );
        }

        public function enqueue_admin_scripts()
        {
            // https://app.fontastic.me/#publish/font/Aw4bWwT5h9eHfjGnhA29v7
            wp_register_style(
                'publishpress-slack-icon-font',
                PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/css/ppp-slack-icons.css',
                [],
                PUBLISHPRESS_PRO_VERSION,
                'screen'
            );

            if (
                isset($_GET['page'])
                && $_GET['page'] === 'pp-notif-log'
            ) {
                wp_enqueue_style('publishpress-slack-icon-font');
            }

            if (
                isset($_GET['page'])
                && isset($_GET['settings_module'])
                && $_GET['page'] === 'pp-modules-settings'
                && $_GET['settings_module'] === 'pp-slack-settings'
            ) {
                global $wp_scripts;


                wp_enqueue_style(
                    'publishpress-slack-notifications',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/css/settings.css',
                    ['publishpress-slack-icon-font'],
                    PUBLISHPRESS_PRO_VERSION,
                    'screen'
                );


                if (! isset($wp_scripts->queue['react'])) {
                    wp_enqueue_script(
                        'react',
                        PUBLISHPRESS_URL . 'common/js/react.min.js',
                        [],
                        PUBLISHPRESS_VERSION,
                        true
                    );
                    wp_enqueue_script(
                        'react-dom',
                        PUBLISHPRESS_URL . 'common/js/react-dom.min.js',
                        ['react'],
                        PUBLISHPRESS_VERSION,
                        true
                    );
                }

                wp_enqueue_script(
                    'publishpress-slack-oauth',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/js/slack-oauth.min.js',
                    [
                        'react',
                        'react-dom',
                        'jquery',
                    ],
                    PUBLISHPRESS_PRO_VERSION,
                    true
                );

                $token = $this->getSlackOAuthToken();
                $legacyWebhook = isset($this->module->options->service_url) ? $this->module->options->service_url : '';

                $params = [
                    'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
                    'nonce' => wp_create_nonce('publishpress_pro_slack_oauth'),
                    'nonce_test' => wp_create_nonce('publishpress_pro_test_slack'),
                    'clientNonce' => wp_create_nonce('publishpress_pro_slack_client_nonce'),
                    'middlewareUrl' => $this->get_slack_middleware_url(
                        'request',
                        'publishpress_pro_slack_client_nonce'
                    ),
                    'tokenIsStored' => ! empty($token),
                    'legacyWebhookUrlIsStored' => ! empty($legacyWebhook),
                    'text' => [
                        'connectButtonLabel' => esc_html__('Click to connect to Slack', 'publishpress-pro'),
                        'reconnectButtonLabel' => esc_html__('Click to reconnect to Slack', 'publishpress-pro'),
                        'collectButtonLabel' => esc_html__('Continue', 'publishpress-pro'),
                        'cancelButtonLabel' => esc_html__('Cancel', 'publishpress-pro'),
                        'success' => esc_html__('Success', 'publishpress-pro'),
                        'error' => esc_html__('Error', 'publishpress-pro'),
                        'seconds' => esc_html__('seconds', 'publishpress-pro'),
                        'elapsedTime' => esc_html__('Elapsed time', 'publishpress-pro'),
                        'msgIdleState' => esc_html__(
                            'Clicking to connect, a new window will open so you can authorize the PublishPress App on Slack.',
                            'publishpress-pro'
                        ),
                        'msgWaiting' => esc_html__(
                            'Paste below the temporary authentication code you received on the page that loaded, and click on Continue.',
                            'publishpress-pro'
                        ),
                        'msgTokenIsStored' => esc_html__('Slack authentication info is stored.', 'publishpress-pro'),
                        'resetButtonLabel' => esc_html__('Delete stored authentication info and cache'),
                        'confirmDeleteMsg' => esc_html__(
                            'Are you sure you want to delete stored authentication info and cache?'
                        ),
                        'newAPIWarning' => esc_html__(
                            'Slack has a new API and you have a legacy configuration that might stop working soon. Please, reconnect to Slack to avoid having the notifications interrupted.',
                            'publishpress-pro'
                        ),
                        'msgConnectedSuccessfuly' => esc_html__(
                            'Congrats! Slack authentication info is stored.',
                            'publishpress-pro'
                        ),
                        'msgTokenDeletedSuccessfuly' => esc_html__(
                            'Authentication info and cache were deleted successfully.',
                            'publishpress-pro'
                        ),
                        'test' => [
                            'sendButtonIdle' => esc_html__(
                                'Click for sending a test message to the default channel on Slack',
                                'publishpress-pro'
                            ),
                            'sendButtonLoading' => esc_html__(
                                'Trying to send the message to Slack...',
                                'publishpress-pro'
                            ),
                            'generalError' => esc_html__('Unknown error found.', 'publishpress-pro'),
                            'nonceError' => esc_html__('Invalid nonce or referer.', 'publishpress-pro'),
                            'msgSuccess' => esc_html__(
                                'Congrats! A test message was sent to the channel %s.',
                                'publishpress-pro'
                            ),
                        ],
                    ]
                ];

                wp_localize_script('publishpress-slack-oauth', 'publishpressProSlackOAuthParams', $params);

                if (! empty($token)) {
                    wp_enqueue_script(
                        'publishpress-slack-test',
                        PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/js/slack-test.min.js',
                        [
                            'react',
                            'react-dom',
                            'jquery',
                        ],
                        PUBLISHPRESS_PRO_VERSION,
                        true
                    );

                    wp_localize_script('publishpress-slack-test', 'publishpressProSlackParams', $params);
                }
            }
        }

        private function get_slack_middleware_url($action, $nonce_action)
        {
            return add_query_arg(
                [
                    'publishpress_slack_action' => sanitize_key($action),
                    'client_nonce' => wp_create_nonce($nonce_action),
                ],
                PP_SLACK_OAUTH_URL
            );
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         */
        public function register_settings()
        {
            /**
             *
             * Post types
             */
            $section_id = $this->module->options_group_name . '_slack';

            add_settings_section(
                $section_id,
                '',//__('General:', 'publishpress-pro'),
                '__return_false',
                $this->module->options_group_name
            );

            add_settings_field(
                'slack_oauth',
                __('Authentication:', 'publishpress-pro'),
                [$this, 'settings_slack_oauth'],
                $this->module->options_group_name,
                $section_id
            );

            add_settings_field(
                'default_slack_channel',
                __('Default Slack Channel/User:', 'publishpress-pro'),
                [$this, 'settings_slack_channel'],
                $this->module->options_group_name,
                $section_id
            );

            add_settings_field(
                'notification_theme',
                __('Notification theme:', 'publishpress-pro'),
                [$this, 'settings_notification_theme'],
                $this->module->options_group_name,
                $section_id
            );

            $section_id = $this->module->options_group_name . '_slack_debug';
        }

        /**
         * Displays the field to authenticate to Slack using OAuth.
         *
         * @param array
         */
        public function settings_slack_oauth($args = [])
        {
            $id = $this->module->options_group_name . '_slack_oauth';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<div id="publishpress_prosettings_slack_oauth_container"></div>';
            echo '</label>';
        }

        /**
         * Displays the field to select the notification theme.
         *
         * @param array
         */
        public function settings_notification_theme($args = [])
        {
            $id = $this->module->options_group_name . '_notification_theme';
            $selectedOption = isset($this->module->options->notification_theme) ? $this->module->options->notification_theme : self::THEME_FULL;

            $options = [
                self::THEME_FULL => [
                    'label' => esc_html__('Full - Notification body with action buttons', 'publishpress-pro'),
                    'preview' => PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/img/theme-preview-full.png?version=' . PUBLISHPRESS_PRO_VERSION,
                ],
                self::THEME_CLEAN => [
                    'label' => esc_html__('Clean - Only the notification body', 'publishpress-pro'),
                    'preview' => PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/slack/assets/img/theme-preview-clean.png?version=' . PUBLISHPRESS_PRO_VERSION,
                ],
            ];

            foreach ($options as $value => $option) {
                echo '<label class="pp-slack-theme-preview" for="' . esc_attr(
                        $id . '_' . $value
                    ) . '"><input type="radio" '
                    . checked(
                        $selectedOption,
                        $value,
                        false
                    ) . ' id="' . esc_attr($id . '_' . $value) . '" value="' . esc_attr($value) . '" name="' . esc_attr(
                        $this->module->options_group_name
                    ) . '[notification_theme]" />';
                echo esc_html($option['label']) . '<img alt="Slack theme: ' . esc_attr($value) . '" src="' . esc_url(
                        $option['preview']
                    ) . '"></label><br>';
            }
        }

        /**
         * Displays the field to select the channel
         * close to the submit button
         *
         * @param array
         */
        public function settings_slack_channel($args = [])
        {
            $id = $this->module->options_group_name . '_default_slack_channel';
            $value = isset($this->module->options->default_slack_channel) ? $this->module->options->default_slack_channel : '';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="text" style="min-width: 200px" value="' . esc_attr($value) . '" id="' . esc_attr(
                    $id
                ) . '" name="' . esc_attr(
                    $this->module->options_group_name
                ) . '[default_slack_channel]" placeholder="' . self::DEFAULT_SLACK_CHANNEL . '" />';
            echo '<div class="info">' . esc_html__(
                    'Default Slack channel in which notifications will be sent to. For example #general, @username, user@email.com, or the channel ID. Users can override this on their profile.',
                    'publishpress-pro'
                ) . '</div>';
            echo '</label>';
        }

        /**
         * Validate data entered by the user
         *
         * @param array $new_options New values that have been entered by the user
         *
         * @return array $new_options Form values after they've been sanitized
         */
        public function settings_validate($new_options)
        {
            return apply_filters('pp_slack_validate_settings', $new_options);
        }

        private function get_theme()
        {
            $theme = isset($this->module->options->notification_theme) && ! empty($this->module->options->notification_theme)
                ? $this->module->options->notification_theme : self::THEME_FULL;

            // Make sure we have a valid theme set.
            if (! in_array($theme, [self::THEME_CLEAN, self::THEME_FULL])) {
                $theme = self::THEME_FULL;
            }

            return $theme;
        }

        private function get_post_edit_link($post_id)
        {
            $admin_path = 'post.php?post=' . $post_id . '&action=edit';

            return htmlspecialchars_decode(admin_url($admin_path));
        }

        private function get_post_view_link($post_id, $status)
        {
            if ($status != 'publish') {
                $view_link = add_query_arg(['preview' => 'true'], wp_get_shortlink($post_id));
            } else {
                $view_link = htmlspecialchars_decode(get_permalink($post_id));
            }

            return $view_link;
        }

        public function send_notification($args)
        {
            $post_id = $args['event_args']['params']['post_id'];

            $edit_link = $this->get_post_edit_link($post_id);
            $view_link = $this->get_post_view_link($post_id, $args['event_args']['params']['new_status']);

            $theme = $this->get_theme();

            $actions = [];
            $body = $this->sanitize_html($args['body']);

            if ($theme == self::THEME_FULL) {
                $comment_link = $edit_link . '#editorialcomments/add';

                $actions = [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => esc_html__('Comment', 'publishpress-pro'),
                                'emoji' => true,
                            ],
                            'value' => 'comment',
                            'url' => esc_url($comment_link),
                            'action_id' => 'add_comment',
                        ],
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => esc_html__('Edit', 'publishpress-pro'),
                                'emoji' => true,
                            ],
                            'value' => 'edit',
                            'url' => esc_url($edit_link),
                            'action_id' => 'edit_post',
                        ],
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => esc_html__('View', 'publishpress-pro'),
                                'emoji' => true,
                            ],
                            'value' => 'view',
                            'url' => esc_url($view_link),
                            'action_id' => 'view_post',
                        ],
                    ],
                ];
            }

            /**
             * @param array $actions
             * @param array $args Indexes: channel, content, action, post_id, post_title, post_type, current_user.
             *
             * @return array
             * @deprecated 3.7.2
             *
             */
            $actions = apply_filters_deprecated(
                'publishpress_slack_actions',
                [$actions, $args],
                '3.7.2',
                'publishpress_slack_message_actions'
            );

            /**
             * @param array $actions
             * @param array $args Indexes: channel, content, action, post_id, post_title, post_type, current_user.
             *
             * @return array
             */
            $actions = apply_filters('publishpress_slack_message_actions', $actions, $args);

            /**
             * @param string $text
             * @param array $args Indexes: channel, content, action, post_id, post_title, post_type, current_user.
             *
             * @return string
             * @deprecated 3.7.2
             *
             */
            $text = apply_filters_deprecated(
                'publishpress_slack_text',
                [$body, $args],
                '3.7.2',
                'publishpress_slack_message_text'
            );

            /**
             * @param string $text
             * @param array $args Indexes: channel, content, action, post_id, post_title, post_type, current_user.
             *
             * @return string
             */
            $text = apply_filters('publishpress_slack_message_text', $body, $args);

            /**
             * @param string $channel
             * @param array $args Indexes: channel, content, action, post_id, post_title, post_type, current_user.
             *
             * @return string
             */
            $channel = apply_filters('publishpress_slack_message_channel', $args['channel'], $args);

            return $this->send_slack_message($text, $channel, $actions);
        }

        private function sanitize_html($text)
        {
            $text = preg_replace('|<a[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]+)</a>|i', '::::$1|$2;;;;', $text);
            $text = wp_strip_all_tags($text);
            $text = html_entity_decode($text);

            return str_replace(['::::', ';;;;'], ['<', '>'], $text);
        }

        public function filter_slack_text($text)
        {
            //replace mentioned user id
            $text = preg_replace('/(@[A-Z0-9]+)/', '<$1>', $text);
            //replace mentioned user name
            $text = preg_replace_callback(
                '({{[a-zA-Z0-9? @.,_-]{2,}}})',
                function ($match) {
                    $mentioned_user = $match[0];
                    $mentioned_user = '@' . str_replace(["{{", "}}"], '', $mentioned_user);
                    return ! empty($mentioned_user) ? '<@' . $this->get_slack_user_id_by_name_or_email(
                            $mentioned_user
                        ) . '>' : '';
                },
                $text
            );
            //replace {{ }} token
            $text = str_replace("{{", '<', $text);
            $text = str_replace("}}", '>', $text);

            return $text;
        }

        private function call_slack_api($endPoint, $data = [], $method = 'POST')
        {
            $token = $this->getSlackOAuthToken();

            if (empty($token)) {
                $error = new WP_Error();
                $error->add(
                    '',
                    esc_html__(
                        'No token found. Please, go to PublishPress > Settings > Slack and reconnect to Slack.',
                        'publishpress-pro'
                    )
                );

                return [
                    'ok' => false,
                    'error' => $error
                ];
            }

            $requestArgs = [
                'user-agent' => sprintf('%s/%s', PP_SLACK_NAME, PUBLISHPRESS_PRO_VERSION),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ];

            $requestUrl = self::SLACK_API_URL . $endPoint;

            if ($method === 'POST') {
                $requestArgs['body'] = json_encode($data);
            }

            if ($method === 'GET') {
                $requestUrl = add_query_arg(
                    $data,
                    $requestUrl
                );
            }

            if ((int)ini_get('max_execution_time') < 300) {
                @set_time_limit(300);
            }

            $response = wp_remote_post($requestUrl, $requestArgs);

            if (is_wp_error($response)) {
                return [
                    'ok' => false,
                    'error' => $response,
                ];
            }

            $responseStatus = intval(wp_remote_retrieve_response_code($response));

            if (200 !== $responseStatus) {
                $message = wp_remote_retrieve_body($response);

                $error = new WP_Error();
                $error->add('slack_unexpected_response', $message);

                return [
                    'ok' => false,
                    'error' => $error,
                ];
            }

            return [
                'ok' => true,
                'body' => json_decode(wp_remote_retrieve_body($response), true),
            ];
        }

        private function call_get_slack_api($endPoint, $args = [])
        {
            return $this->call_slack_api($endPoint, $args, $method = 'GET');
        }

        private function call_post_slack_api($endPoint, $body)
        {
            return $this->call_slack_api($endPoint, $body, $method = 'POST');
        }

        private function get_slack_channel_id_by_name($channelName)
        {
            $channelName = str_replace('#', '', $channelName);

            $channelID = false;
            $channelIDsByName = wp_cache_get('slack_channels_by_name', self::CACHE_GROUP);

            if (empty($channelIDsByName)) {
                $result = $this->call_get_slack_api('conversations.list');

                if (! $result['ok']) {
                    $error = new WP_Error();
                    $error->add('slack_unexpected_response', wp_remote_retrieve_body($result));

                    return $error;
                }

                $channelIDsByName = array_column($result['body']['channels'], 'id', 'name');

                wp_cache_set(
                    'slack_channels_by_name',
                    $channelIDsByName,
                    self::CACHE_GROUP,
                    12 * self::ONE_HOUR_IN_SECONDS
                );
            }

            if (isset($channelIDsByName[$channelName])) {
                $channelID = $channelIDsByName[$channelName];
            }

            return $channelID;
        }

        private function get_slack_user_id_by_name_or_email($userReference)
        {
            // Looking for username or email?
            $pos = strpos($userReference, '@');
            if ($pos === 0) {
                // If username, removes the @.
                $userReference = str_replace('@', '', $userReference);
            }

            $referencedProperty = substr_count($userReference, '@') ? 'email' : 'name';

            $userIDsByName = wp_cache_get('slack_users_by_name', self::CACHE_GROUP);
            $userIDsByEmail = wp_cache_get('slack_users_by_email', self::CACHE_GROUP);

            if (empty($userIDsByName) || empty($userIDsByEmail)) {
                $result = $this->call_get_slack_api('users.list');

                if (! $result['ok']) {
                    $error = new WP_Error();
                    $error->add('slack_unexpected_response', wp_remote_retrieve_body($result));

                    return $error;
                }

                $userIDsByName = [];
                $userIDsByEmail = [];
                foreach ($result['body']['members'] as $member) {
                    if (isset($member['profile']['email']) && ! empty($member['profile']['email'])) {
                        $userIDsByEmail[$member['profile']['email']] = $member['id'];
                    }

                    if (isset($member['name']) && ! empty($member['name'])) {
                        $userIDsByName[$member['name']] = $member['id'];
                    }
                    if (isset($member['profile']['real_name']) && ! empty($member['profile']['real_name'])) {
                        $userIDsByName[$member['profile']['real_name']] = $member['id'];
                    }
                    if (isset($member['profile']['display_name']) && ! empty($member['profile']['display_name'])) {
                        $userIDsByName[$member['profile']['display_name']] = $member['id'];
                    }
                }

                wp_cache_set('slack_users_by_name', $userIDsByName, self::CACHE_GROUP, 12 * self::ONE_HOUR_IN_SECONDS);
                wp_cache_set(
                    'slack_users_by_email',
                    $userIDsByEmail,
                    self::CACHE_GROUP,
                    12 * self::ONE_HOUR_IN_SECONDS
                );
            }

            $list = $referencedProperty === 'name' ? $userIDsByName : $userIDsByEmail;

            if (isset($list[$userReference])) {
                return $list[$userReference];
            }

            return false;
        }

        public function send_slack_message($text, $channel, $actions = null)
        {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

            $token = $this->getSlackOAuthToken();

            if (empty($token)) {
                $error = new WP_Error();
                $error->add(
                    '',
                    esc_html__(
                        'Please, go to PublishPress > Settings > Slack and reconnect to Slack',
                        'publishpress-pro'
                    )
                );

                error_log(
                    __('Slack OAuth token not found on PublishPress. Please, reconnect to Slack', 'publishpress-pro')
                );

                return [
                    'response' => $error,
                    'payload' => [],
                ];
            }

            // Check if "channel" is a user or channel by name.
            if (substr_count($channel, '@')) {
                $channelID = $this->get_slack_user_id_by_name_or_email($channel);
            } else {
                $channelID = $this->get_slack_channel_id_by_name($channel);
            }

            // Channel not found, we assume it is the channel ID.
            if (empty($channelID)) {
                $channelID = $channel;
            }

            $body = [
                'channel' => $channelID,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $text,
                        ]
                    ]
                ]
            ];

            if (! empty($actions)) {
                $body['blocks'][] = $actions;
            }

            $response = $this->call_post_slack_api('chat.postMessage', $body);

            if (! $response['ok']) {
                return $response;
            }

            if ($response['body']['ok'] === false) {
                $error = new WP_Error();
                $error->add('publishpress_slack_api_error', $response['body']['error'], 'publishpress-pro');

                return [
                    'ok' => false,
                    'error' => $error,
                ];
            }

            return $response;
        }

        public static function get_default_slack_channel()
        {
            $publishpress = publishpress();

            $defaultChannel = isset($publishpress->slack->module->options->default_slack_channel) ? $publishpress->slack->module->options->default_slack_channel : '';

            if (empty($defaultChannel)) {
                $defaultChannel = self::DEFAULT_SLACK_CHANNEL;
            }

            return $defaultChannel;
        }

        public function handle_send_slack_test_message()
        {
            check_ajax_referer('publishpress_pro_test_slack');

            if (! current_user_can('manage_options')) {
                $errors = new WP_Error();
                $errors->add('access_denied', __('Access denied', 'publishpress-pro'));

                wp_send_json_error($errors, 403);
            }

            $token = $this->getSlackOAuthToken();

            if (empty($token)) {
                $errors = new WP_Error();
                $errors->add('invalid_oauth_token', __('Invalid OAuth token', 'publishpress-pro'));

                wp_send_json_error($errors, 403);
            }

            $text = esc_html__(
                'Hey, congrats! Everything looks good on PublishPress Pro\'s Slack settings.',
                'publishpress-pro'
            );

            $slackChannel = self::get_default_slack_channel();

            $result = $this->send_slack_message($text, $slackChannel, []);

            if (! $result['ok']) {
                $errors = new WP_Error();

                $errorMessage = esc_html__(
                        'Error from Slack: ',
                        'publishpress-pro'
                    ) . $result['error']->get_error_message();

                if ($result['error']->get_error_code() === 'slack_channel_not_found') {
                    $errorMessage .= ' Channel: ' . $slackChannel;
                }

                $errors->add('slack_error', $errorMessage);


                wp_send_json_error($errors, 400);
            }

            wp_send_json_success(['channel' => $slackChannel]);
        }

        public function handle_delete_slack_oauth_token()
        {
            check_ajax_referer('publishpress_pro_slack_oauth');

            if (! current_user_can('manage_options')) {
                $errors = new WP_Error();
                $errors->add('', __('Access denied', 'publishpress-pro'));

                wp_send_json_error($errors, 403);
            }

            delete_option('publishpress_pro_slack_token');
            delete_option('publishpress_pro_slack_scope');
            delete_option('publishpress_pro_slack_authed_user');

            $this->removeLegacySlackServiceURL();

            wp_send_json_success();
        }

        public function handle_collect_slack_oauth_token()
        {
            check_ajax_referer('publishpress_pro_slack_oauth');

            if (! current_user_can('manage_options')) {
                $errors = new WP_Error();
                $errors->add('', __('Access denied', 'publishpress-pro'));

                wp_send_json_error($errors, 403);
            }

            $collectCode = sanitize_key($_POST['collect_code']);

            $url = add_query_arg(
                [
                    'publishpress_slack_action' => 'collect',
                    'client_nonce' => wp_create_nonce('publishpress_pro_slack_client_nonce'),
                    'collect_nonce' => $collectCode,
                ],
                PP_SLACK_OAUTH_URL
            );

            $args = [];

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $args['sslverify'] = false;
            }

            // TODO: Refactor this to use the private method
            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $result['response'] = $response;
            } else {
                $responseStatus = intval(wp_remote_retrieve_response_code($response));

                if (200 !== $responseStatus) {
                    $message = wp_remote_retrieve_body($response);
                    $errors = new WP_Error();
                    $errors->add('publishpress_unexpected_response', $message);

                    $result['response'] = $errors;
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);

                    if (! $body['success']) {
                        $errors = new WP_Error();
                        $errors->add('publishpress_unexpected_response', $body['error']);

                        $result['response'] = $errors;
                    } else {
                        $data = $body['data'];

                        update_option('publishpress_pro_slack_token', sanitize_text_field($data['access_token']), true);
                        update_option('publishpress_pro_slack_scope', sanitize_text_field($data['scope']), true);
                        update_option(
                            'publishpress_pro_slack_authed_user',
                            sanitize_text_field($data['authed_user']),
                            true
                        );
                        $this->removeLegacySlackServiceURL();

                        $result['response'] = true;
                    }
                }
            }

            if (is_wp_error($result['response'])) {
                wp_send_json_error(
                    [
                        'error' => esc_html__(
                                'Error from PublshPress.com: ',
                                'publishpress-pro'
                            ) . $result['response']->get_error_message()
                    ]
                );
            }

            wp_send_json_success();
        }

        private function removeLegacySlackServiceURL()
        {
            $this->module->options->service_url = '';
            update_option('publishpress_slack_options', $this->module->options, true);
        }

        private function getSlackOAuthToken()
        {
            return trim(get_option('publishpress_pro_slack_token'));
        }

        private function userIsLoggedIn()
        {
            return is_user_logged_in();
        }
    }
}
