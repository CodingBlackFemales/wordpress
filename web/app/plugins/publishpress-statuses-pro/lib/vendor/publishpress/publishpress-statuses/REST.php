<?php
namespace PublishPress_Statuses;

class REST
{
    var $route = '';
    var $endpoint_class = '';
    var $post_type = '';
    var $post_id = 0;
    var $post_status = '';
    var $is_posts_request = false;
    var $params = [];
    var $referer = '';

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new REST();
            \PublishPress_Statuses::instance()->doing_rest = true;
        }
        return self::$instance;
    }

    public static function getPostType()
    {
        return self::instance()->post_type;
    }

    public static function getPostID()
    {
        return self::instance()->post_id;
    }

    function pre_dispatch($rest_response, $rest_server, $request)
    {
        $method = $request->get_method();
        $path   = $request->get_route();
        $routes = $rest_server->get_routes();
        
        $post_endpoints = apply_filters('presspermit_rest_post_endpoints', []);

        $post_endpoints[]= 'WP_REST_Posts_Controller';
        $post_endpoints[]= 'WP_REST_Autosaves_Controller';
		
		foreach ( $routes as $route => $handlers ) {
			$match = preg_match( '@^' . $route . '$@i', $path, $matches );

			if ( ! $match ) {
				continue;
			}

			$args = [];
			foreach ( $matches as $param => $value ) {
				if ( ! is_int( $param ) ) {
					$args[ $param ] = $value;
				}
			}

			foreach ( $handlers as $handler ) {
                if (!is_array($handler['callback']) || !isset($handler['callback'][0])) {
                    continue;
                }

                if (is_object($handler['callback'][0])) {
					$this->endpoint_class = get_class($handler['callback'][0]);

                } elseif (is_string($handler['callback'][0])) {
                    $this->endpoint_class = $handler['callback'][0];
                } else {
                    continue;
                }
				
                if (!in_array($this->endpoint_class, $post_endpoints, true)
                ) {
                    continue;
                }
				
                $this->route = $route;

                $this->params = $request->get_params();
                
                $headers = $request->get_headers();
                $this->referer = (isset($headers['referer'])) ? $headers['referer'] : '';
                if (is_array($this->referer)) {
                    $this->referer = reset($this->referer);
                }

                if (in_array($this->endpoint_class, $post_endpoints)) {
                    $this->post_type = (!empty($args['post_type'])) ? $args['post_type'] : '';
                    
                    if (!$this->post_type && !empty($endpoint_post_types[$this->endpoint_class])) {
                        $this->post_type = $endpoint_post_types[$this->endpoint_class];
                        $this->params['post_type'] = $this->post_type;
                    }
                
                    if ( ! $this->post_id = (!empty($args['id'])) ? $args['id'] : 0 ) {
                        $this->post_id = (!empty($this->params['id'])) ? $this->params['id'] : 0;
                    }

                    // @todo: is this still needed?
                    if (('revision' != $this->post_type) && apply_filters('publishpress_statuses_default_visibility', '', $this->post_type)) {
                        if (false === get_post_meta($this->post_id, '_pp_original_status')) {
                            global $wpdb;

                            // phpcs Note: When imposing a default privacy, ensure retrieval of stored original status, not newly updated value

                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                            if ( $post_status = $wpdb->get_var( 
                                $wpdb->prepare(
                                    "SELECT post_status FROM $wpdb->posts WHERE ID = %s", 
                                    $this->post_id
                                )
                            )) {
                                update_post_meta($this->post_id, '_pp_original_status', $post_status);
                            }
                        }
                    }

                    if (!$this->post_type) {
                        if (!$this->post_type = get_post_field('post_type', $this->post_id)) {
                            return $rest_response;
                        }
                    } elseif (!empty($args['post_type'])) {
                        $this->post_type = $args['post_type'];
                    }

                    $this->is_posts_request = true;
                }
            }
        }

        if ($this->is_posts_request) {
            add_filter('presspermit_rest_post_type', [$this, 'fltRestPostType'], 5);
            add_filter('presspermit_rest_post_id', [$this, 'fltRestPostID'], 5);
        }

        return $rest_response;
    }  // end function pre_dispatch

    function fltRestPostType($post_type)
    {
        return ($this->post_type) ? $this->post_type : $post_type;
    }

    function fltRestPostID($post_id)
    {
        return ($this->post_id) ? $this->post_id : $post_id;
    }
}
