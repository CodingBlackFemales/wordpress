<?php
namespace PublishPress\Permissions\FileAccess;

// NOTE: This class is currently not needed because attached file attachments are not applied until Post Save.
// It is left as a pattern for possible third party integration issues.

class REST
{
    var $is_view_method = false;
    var $endpoint_class = '';
    var $taxonomy = '';
    var $post_type = '';
    var $post_id = 0;
    var $is_posts_request = false;
    var $is_terms_request = false;
    var $operation = '';
    var $params = [];
    var $referer = '';

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new REST();
            FileAccess::instance()->doing_rest = true;
        }
        return self::$instance;
    }

    private function __construct()
    {
        
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
		
		foreach ( $rest_server->get_routes() as $route => $handlers ) {
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
				$this->endpoint_class = get_class($handler['callback'][0]);

                switch ($this->endpoint_class) {
                    case 'WP_REST_Attachments_Controller':
                        $this->is_view_method = in_array($method, [\WP_REST_Server::READABLE, 'GET']);
                        $this->params = $request->get_params();

                        $headers = $request->get_headers();
                        $this->referer = (isset($headers['referer'])) ? $headers['referer'] : '';
                        if (is_array($this->referer)) {
                            $this->referer = reset($this->referer);
                        }

                        $this->post_type = (!empty($args['post_type'])) ? $args['post_type'] : 'attachment';
                        
                        if (!$this->post_id = (!empty($args['id'])) ? $args['id'] : 0 ) {
                            if (!$this->post_id = (!empty($this->params['id'])) ? $this->params['id'] : 0) {
                                $this->post_id = PWP::REQUEST_int('post');
                            }
                        }

                        $this->is_posts_request = true;

                        if (!$this->operation = PWP::REQUEST_key('context')) {
                            $this->operation = PWP::REQUEST_key('action');
                        }

                        if ('edit' == $this->operation) {
                            if (strpos($this->referer, 'post.php?') && strpos($this->referer, 'action=edit')) {
                                $matches = [];
                                preg_match("/post=([0-9]+)/", $this->referer, $matches);
                                $attached_to_post_id = (!empty($matches[1])) ? $matches[1] : 0;

                                do_action('presspermit_attach_media', $this->post_id, $attached_to_post_id); 
                            }
                        }
                        break;
                    default:
                } // end switch
            }
        }

        if ($this->is_posts_request) {
            add_filter('presspermit_rest_post_type', [$this, 'fltRestPostType']);
            add_filter('presspermit_rest_post_id', [$this, 'fltRestPostID']);
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
