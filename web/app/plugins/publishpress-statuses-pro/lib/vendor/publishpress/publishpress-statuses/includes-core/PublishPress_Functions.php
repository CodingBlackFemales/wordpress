<?php
if (!class_exists('PublishPress_Functions')) {

/**
 * PublishPress_Functions
 */
class PublishPress_Functions 
{

    /**
     * Based on Edit Flow's \Block_Editor_Compatible::should_apply_compat method.
     *
     * @return bool
     */
    public static function isBlockEditorActive($args = [])
    {
        if (isset($args['force'])) {
            if ('classic' === $args['force']) {
                return false;
            }

            if ('gutenberg' === $args['force']) {
                return true;
            }
        }

        // If the editor is being accessed in this request, we have an easy and reliable test
        if ((did_action('load-post.php') || did_action('load-post-new.php')) && did_action('admin_enqueue_scripts')) {
            return did_action('enqueue_block_editor_assets');
        }

        // For other requests (or if the decision needs to be made prior to admin_enqueue_scripts action), proceed with other logic...

        $pluginsState = [
            'classic-editor' => self::isPluginActive('classic-editor/classic-editor.php'),
            'gutenberg' => self::isPluginActive('gutenberg/gutenberg.php'),
            'gutenberg-ramp' => self::isPluginActive('gutenberg-ramp/gutenberg-ramp.php'),
            'disable-gutenberg' => class_exists('DisableGutenberg'),
        ];


        if (function_exists('get_post_type')) {
            $postType = get_post_type();
        }

        if (! isset($postType) || empty($postType)) {
            $postType = 'post';
        }

        /**
         * If show_in_rest is not true for the post type, the block editor is not available.
         */
        if (
            ($postTypeObject = get_post_type_object($postType))
            && empty($postTypeObject->show_in_rest)
        ) {
            return false;
        }

        // Divi: Classic Editor option
		if (function_exists('et_get_option') && ( 'on' == et_get_option( 'et_enable_classic_editor', 'off' ))) {
			return false;
		}

        $conditions = [];

        /**
         * 5.0:
         *
         * Classic editor either disabled or enabled (either via an option or with GET argument).
         * It's a hairy conditional :(
         */
        $conditions[] = self::isWp5()
            && ! $pluginsState['classic-editor']
            && ! $pluginsState['gutenberg-ramp']
            && ! $pluginsState['disable-gutenberg']
            && apply_filters('use_block_editor_for_post_type', true, $postType, PHP_INT_MAX);

        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        $conditions[] = self::isWp5() && $pluginsState['classic-editor'] && (get_option('classic-editor-replace') === 'block' && ! isset($_GET['classic-editor__forget']));

        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        $conditions[] = self::isWp5() && $pluginsState['classic-editor'] && (get_option('classic-editor-replace') === 'classic' && isset($_GET['classic-editor__forget']));

        /**
         * < 5.0 but Gutenberg plugin is active.
         */
        $conditions[] = ! self::isWp5() && ($pluginsState['gutenberg'] || $pluginsState['gutenberg-ramp']);

        $conditions[] = $pluginsState['disable-gutenberg'] && !self::disableGutenberg(self::getPostID());

        // Returns true if at least one condition is true.
        return count(
                array_filter(
                    $conditions,
                    function ($c) {
                        return (bool)$c;
                    }
                )
            ) > 0;
    }

    // Port function from Disable Gutenberg plugin due to problematic early is_plugin_active() function call
    private static function disableGutenberg($post_id = false) {
	
        if (function_exists('disable_gutenberg_whitelist_id') && disable_gutenberg_whitelist_id($post_id)) return false;
        
        if (function_exists('disable_gutenberg_whitelist_slug') && disable_gutenberg_whitelist_slug($post_id)) return false;
        
        if (function_exists('disable_gutenberg_whitelist_title') && disable_gutenberg_whitelist_title($post_id)) return false;
        
        if (isset($_GET['block-editor'])) return false;     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        
        if (isset($_GET['classic-editor'])) return true;    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        
        if (isset($_POST['classic-editor'])) return true;   // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        
        if (function_exists('disable_gutenberg_disable_all') && disable_gutenberg_disable_all()) return true;
        
        if (function_exists('disable_gutenberg_disable_user_role') && disable_gutenberg_disable_user_role()) return true;
        
        if (function_exists('disable_gutenberg_disable_post_type') && disable_gutenberg_disable_post_type()) return true;
        
        if (function_exists('disable_gutenberg_disable_templates') && disable_gutenberg_disable_templates()) return true;
        
        if (function_exists('disable_gutenberg_disable_ids') && disable_gutenberg_disable_ids($post_id)) return true;
        
        return false;
    }

    public static function isWp5()
    {
        global $wp_version;

        return version_compare($wp_version, '5.0', '>=') || substr($wp_version, 0, 2) === '5.';
    }

    public static function findPostType($post_id = 0, $return_default = true)
    {
        global $typenow, $post;

        if (!$post_id && defined('REST_REQUEST') && REST_REQUEST) {
            if ($_post_type = apply_filters('presspermit_rest_post_type', '')) {
                return $_post_type;
            }
        }

        if (!$post_id && !empty($typenow)) {
            return $typenow;
        }

        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        if ($post_id && !empty($post) && ($post->ID == $post_id)) {
            return $post->post_type;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) { // todo: separate static function to eliminate redundancy with PostFilters::fltPostsClauses()
            $ajax_post_types = apply_filters('pp_ajax_post_types', ['ai1ec_doing_ajax' => 'ai1ec_event']);

            foreach (array_keys($ajax_post_types) as $arg) {
                if (!self::empty_REQUEST($arg) || self::is_REQUEST('action', $arg)) {
                    return $ajax_post_types[$arg];
                }
            }
        }

        if ($post_id) { // note: calling static function already compared post_id to global $post
            if ($_post = get_post($post_id)) {
                $_type = $_post->post_type;
            }

            if (!empty($_type)) {
                return $_type;
            }
        }

        // no post id was passed in, or we couldn't retrieve it for some reason, so check $_REQUEST args
        global $pagenow, $wp_query;

        if (!empty($wp_query->queried_object)) {
            if (isset($wp_query->queried_object->post_type)) {
                $object_type = $wp_query->queried_object->post_type;

            } elseif (isset($wp_query->queried_object->name)) {
                if (post_type_exists($wp_query->queried_object->name)) {  // bbPress forums list
                    $object_type = $wp_query->queried_object->name;
                }
            }
        } elseif (in_array($pagenow, ['post-new.php', 'edit.php'])) {
            $object_type = self::GET_key('post_type') ? self::GET_key('post_type') : 'post';

        } elseif (in_array($pagenow, ['edit-tags.php'])) {
            $object_type = !self::empty_REQUEST('taxonomy') ? self::REQUEST_key('taxonomy') : 'category';

        } elseif (in_array($pagenow, ['admin-ajax.php']) && !self::empty_REQUEST('taxonomy')) {
            $object_type = self::REQUEST_key('taxonomy');

        } else {
            if ($_post_id = !self::empty_REQUEST('post_ID')) {
                $_post_id = self::REQUEST_int('post_ID');

                if ($_post = get_post($_post_id)) {
                    $object_type = $_post->post_type;
                }
            } elseif ($id = self::GET_int('post')) {  // post.php
                if ($_post = get_post($id)) {
                    $object_type = $_post->post_type;
                }
            }
        }

        if (empty($object_type)) {
            if ($return_default) { // default to post type
                return 'post';
            }
        } elseif ('any' != $object_type) {
            return $object_type;
        }
    }

    public static function getPostID()
    {
        global $post, $wp_query;

        if (defined('REST_REQUEST') && REST_REQUEST) {
            if ($_post_id = apply_filters('presspermit_rest_post_id', 0)) {
                return $_post_id;
            }
        }

        if (!empty($post) && is_object($post)) {
            if ('auto-draft' == $post->post_status)
                return 0;
            else
                return $post->ID;

        } elseif (!is_admin() && !empty($wp_query) && is_singular()) {
            if (!empty($wp_query)) {
                if (!empty($wp_query->query_vars) && !empty($wp_query->query_vars['p'])) {
                    return (int) $wp_query->query_vars['p'];
                
                } 

                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /* @todo: review usage with other plugins
                elseif (!empty($wp_query->query['post_type']) && !empty($wp_query->query['name'])) {
                    global $wpdb;
                    
                    return $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_name = %s LIMIT 1",
                            $wp_query->query['post_type'],
                            $wp_query->query['name']
                        )
                    );
                }
                */
            }
        } elseif (self::is_REQUEST('post')) {
            return self::REQUEST_int('post');

        } elseif (self::is_REQUEST('post_ID')) {
            return self::REQUEST_int('post_ID');

        } elseif (self::is_REQUEST('post_id')) {
            return self::REQUEST_int('post_id');

        } elseif (defined('WOOCOMMERCE_VERSION') && !self::empty_REQUEST('product_id')) {
            return self::REQUEST_int('product_id');
        }
    }

    /**
     * Checks for the current post type
     *
     * @return string|null $post_type The post type we've found, or null if no post type
     */
    public static function getPostType()
    {
        global $post, $typenow, $pagenow, $current_screen;

        if ($post && $post->post_type) {
            $post_type = $post->post_type;

        } elseif (!empty($typenow)) {
            $post_type = $typenow;

        } elseif ($current_screen && !empty($current_screen->post_type)) {
            $post_type = $current_screen->post_type;

        } elseif (!self::empty_REQUEST('post_type')) {
            $post_type = self::REQUEST_key('post_type');

        } else {
            // get_post() needs a variable
            $post_id = self::getPostID();

            if (!empty($pagenow) && ('post.php' == $pagenow) && $post_id && !empty(get_post($post_id)->post_type)) {
                $post_type = get_post($post_id)->post_type;

            } elseif (!empty($pagenow) && ('edit.php' == $pagenow)) {
                $post_type = 'post';

            } else {
                $post_type = null;
            }
        }

        return $post_type;
    }

    public static function isPluginActive($plugin) {
        return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || self::is_plugin_active_for_network( $plugin );
    }

    private static function is_plugin_active_for_network( $plugin ) {
        if ( ! is_multisite() ) {
            return false;
        }
    
        $plugins = get_site_option( 'active_sitewide_plugins' );
        if ( isset( $plugins[ $plugin ] ) ) {
            return true;
        }
    
        return false;
    }

    public static function orderTypes($types, $args = [])
    {
        $defaults = ['order_property' => '', 'item_type' => '', 'labels_property' => ''];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if ('post' == $item_type) {
            $post_types = get_post_types([], 'object');
        } elseif ('taxonomy' == $item_type) {
            $taxonomies = get_taxonomies([], 'object');
        }

        $ordered_types = [];
        foreach (array_keys($types) as $name) {
            if ('post' == $item_type) {
                $ordered_types[$name] = (isset($post_types[$name]->labels->singular_name))
                    ? $post_types[$name]->labels->singular_name
                    : '';
            } elseif ('taxonomy' == $item_type) {
                $ordered_types[$name] = (isset($taxonomies[$name]->labels->singular_name))
                    ? $taxonomies[$name]->labels->singular_name
                    : '';
            } else {
                if (!is_object($types[$name])) {
                    return $types;
                }

                if ($order_property) {
                    $ordered_types[$name] = (isset($types[$name]->$order_property))
                        ? $types[$name]->$order_property
                        : '';
                } else {
                    $ordered_types[$name] = (isset($types[$name]->labels->$labels_property))
                        ? $types[$name]->labels->$labels_property
                        : '';
                }
            }
        }

        asort($ordered_types);

        foreach (array_keys($ordered_types) as $name) {
            $ordered_types[$name] = $types[$name];
        }

        return $ordered_types;
    }

    /**
     * Take a status and a message, JSON encode and print
     *
     * @param string $status Whether it was a 'success' or an 'error'
     * @param string $message
     * @param array $data
     *
     * @since 0.7
     *
     */
    public static function printAjaxResponse($status, $message = '', $data = null, $params = null)
    {
        header('Content-type: application/json;');

        $result = [
            'status'  => $status,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $result['data'] = $data;
        }

        if (!is_null($params)) {
            $result['params'] = $params;
        }

        echo wp_json_encode($result);

        exit;
    }

    public static function getRoles($translate = false) {
        $wp_roles_obj = wp_roles();
    
        $roles = $wp_roles_obj->get_names();
        if ( $translate ) {
            foreach ($roles as $k => $r) {
                $roles[$k] = _x($r, 'User role');
            }
            asort($roles);
            return $roles;
        } else {
            $roles = array_keys($roles);
            asort($roles);
            return $roles;
        }
    }

    public static function getPluginPage() {
        global $plugin_page, $pagenow;

        if (!is_admin()) {
            return '';

        } elseif (!empty($plugin_page)) {
            return (is_string($plugin_page)) ? $plugin_page : '';

        } elseif (empty($pagenow) || ('admin.php' != $pagenow)) {
            return '';

        } else {
            return self::REQUEST_key('page');
        }
    }

    public static function isEditableRole($role_name, $args = []) {
        static $editable_roles;
    
        if (!function_exists('wp_roles')) {
            return false;
        }
    
        if (!isset($editable_roles) || !empty($args['force_refresh'])) {
            $all_roles = wp_roles()->roles;
            $editable_roles = apply_filters('editable_roles', $all_roles, $args);
        }
    
        return apply_filters('publishpress_statuses_editable_role', isset($editable_roles[$role_name]), $role_name);
    }


    /**** $_REQUEST / $_POST / $_GET Analysis Functions for URL qualification ***
     *
     *  These are used for convenience and code clarity, mostly by controller files to select and load the proper CRUD handler.
     *  Nonce checks, where applicable, are included at the top of that request-specific file.
     * 
     *  A secondary use is for determination of basic request parameters like post ID or status name (not nonce scenarios).
     * 
     *  For actions that require nonce verifications, the subsequent input values are taken directly from $_REQUEST, $_POST or $_GET.
     */
    public static function empty_REQUEST($var = false) {
        if (false === $var) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return empty($_REQUEST);
        } else {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return empty($_REQUEST[$var]);
        }
    }
    
    public static function is_REQUEST($var, $match = false) {
        if (false === $match) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return isset($_REQUEST[$var]);
            
        } elseif (is_array($match)) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return isset($_REQUEST[$var]) && in_array($_REQUEST[$var], $match);
        } else {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return isset($_REQUEST[$var]) && ($_REQUEST[$var] == $match);
        }
    }
    
    public static function REQUEST_key($var) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        if (empty($_REQUEST[$var])) {
            return '';
        }
    
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        return (is_array($_REQUEST[$var])) ? array_map('sanitize_key', $_REQUEST[$var]) : sanitize_key($_REQUEST[$var]);
    }
    
    public static function REQUEST_int($var) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        return (!empty($_REQUEST[$var])) ? intval($_REQUEST[$var]) : 0;
    }

    public static function GET_key($var) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        if (empty($_GET[$var])) {
            return '';
        }
    
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        return (is_array($_GET[$var])) ? array_map('sanitize_key', $_GET[$var]) : sanitize_key($_GET[$var]);
    }
    
    public static function GET_int($var) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        return (!empty($_GET[$var])) ? intval($_GET[$var]) : 0;
    }
    
    public static function empty_POST($var = false) {
        if (false === $var) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return empty($_POST);
        } else {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return empty($_POST[$var]);
        }
    }

    public static function POST_key($var) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        if (empty($_POST) || empty($_POST[$var])) {
            return '';
        }
    
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        return (is_array($_POST[$var])) ? array_map('sanitize_key', $_POST[$var]) : sanitize_key($_POST[$var]);
    }

    public static function is_POST($var, $match = false) {
        // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        if (empty($_POST)) {
            return false;
        }
        
        if (false == $match) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return (isset($_POST[$var]));
        
        } elseif (is_array($match)) {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return (isset($_POST[$var]) && in_array($_POST[$var], $match));
        } else {
            // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            return (isset($_POST[$var]) && ($_POST[$var] == $match));
        }
    }
}
}
