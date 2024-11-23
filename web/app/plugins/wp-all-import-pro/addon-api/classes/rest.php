<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Rest {
    use Singleton;

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register' ) );
    }

    public function register() {
        register_rest_route( 'wp-all-import/v1', '/addon/fields', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'fields' ],
            'permission_callback' => fn() => current_user_can( \PMXI_Plugin::$capabilities )
        ] );
    }

    public function validateRequest( \WP_REST_Request $request ) {
        $params = [ 'addon', 'type', 'group' ];
        $addon  = $request->get_param( 'addon' );

        foreach ( $params as $param ) {
            if ( ! $request->get_param( $param ) ) {
                return new \WP_Error(
                    'missing_param',
                    __( sprintf( "Missing %s parameter", $param ), 'wp-all-import-pro' ),
                    [ 'status' => 400 ]
                );
            }
        }

        if ( ! PMXI_Addon_Manager::get_addon( $addon ) ) {
            return new \WP_Error(
                'addon_not_found',
                __( 'Addon not found', 'wp-all-import-pro' ),
                [ 'status' => 400 ]
            );
        }

        return true;
    }

    /**
     * Fields endpoint
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function fields( \WP_REST_Request $request ) {
        if ( is_wp_error( $error = $this->validateRequest( $request ) ) ) {
            return $error;
        }

        $addonId = $request->get_param( 'addon' );
        $type    = $request->get_param( 'type' );
        $subtype = $request->get_param( 'subtype' );
        $groupId = $request->get_param( 'group' );

        $html = PMXI_Addon_View::create(
            $addonId,
            $type,
            $subtype,
            $groupId
        )->getFieldsHtml();

        return rest_ensure_response( [ 'html' => $html ] );
    }
}
