<?php
namespace PublishPress\Statuses\StatusControl;

class RESTFields
{
    static function registerRESTFields() 
    {
		foreach(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or') as $post_type) {
            // Thanks to Josh Pollock for demonstrating this:
            // https://torquemag.io/2015/07/working-with-post-meta-data-using-the-wordpress-rest-api/
            
            register_rest_field( $post_type, 'pp_force_visibility', array(
                'get_callback' => [__CLASS__, 'getForceVisibility'],
                'update_callback' => [__CLASS__, 'updateForceVisibility'],
                'schema' => [
                    'description'   => 'Locked Visibility',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );
            
            register_rest_field( $post_type, 'pp_subpost_visibility', array(
                'get_callback' => [__CLASS__, 'getSubpostVisibility'],
                'update_callback' => [__CLASS__, 'updateSubpostVisibility'],
                'schema' => [
                    'description'   => 'Subpost Visibility',
                    'type'          => 'string',
                    'context'       =>  ['view', 'edit']
                    ]
                )
            );

            register_rest_field( $post_type, 'pp_inherited_force_visibility', array(
                'get_callback' => [__CLASS__, 'getInheritedForceVisibility'],
                'update_callback' => [__CLASS__, 'updateInheritedForceVisibility'],
                'schema' => [
                    'description'   => 'Inherited Locked Visibility',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );
            
            register_rest_field( $post_type, 'pp_inherited_subpost_visibility', array(
                'get_callback' => [__CLASS__, 'getInheritedSubpostVisibility'],
                'update_callback' => [__CLASS__, 'updateInheritedSubpostVisibility'],
                'schema' => [
                    'description'   => 'Inherited Subpost Visibility',
                    'type'          => 'string',
                    'context'       =>  ['view', 'edit']
                    ]
                )
            );
        }
    }

    public static function getForceVisibility( $object ) {
        $attributes = PPS::attributes();

        return ( isset( $object['id'] ) ) 
        ? $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'item', 'id' => $object['id']])
        : '';
    }

    public static function updateForceVisibility( $value, $object ) {
        return false;
    }

    public static function getSubpostVisibility( $object ) {
        $attributes = PPS::attributes();

        return ( isset( $object['id'] ) ) 
        ? $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'children', 'id' => $object['id']])
        : '';
    }

    public static function updateSubpostVisibility($value, $post) {
        $attributes = PPS::attributes();

        if (!is_object($post) || empty($post->ID)) {
            return false;
        }

        if (!current_user_can('edit_post', $post->ID)) {
            return false;
        }

        if (!\PublishPress_Statuses::haveStatusPermission('set_status', $post->post_type, $value)) {
            return false;
        }

        $attributes = PPS::attributes();

        if ($force_status = $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'item', 'id' => $post->ID])) {
            if ($value != $force_status) {
                return $force_status;
            }
        }

        if ($value) {
            PPS::setItemCondition(
                'force_visibility', 
                'object', 
                'post', 
                $post->ID, 
                $value, 
                'children'
            );
        } else {
            PPS::clearItemCondition(
                'force_visibility', 
                'object', 
                'post', 
                $post->ID, 
                'children'
            );
        }

        return true;
    }

    public static function getInheritedForceVisibility( $object ) {
        $attributes = PPS::attributes();

        return ( isset( $object['id'] ) ) 
        ? $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'item', 'id' => $object['id'], 'inherited_only' => true])
        : '';
    }

    public static function updateInheritedForceVisibility( $value, $object ) {
        return false;
    }

    public static function getInheritedSubpostVisibility( $object ) {
        $attributes = PPS::attributes();

        return ( isset( $object['id'] ) ) 
        ? $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'children', 'id' => $object['id'], 'inherited_only' => true])
        : '';
    }

    public static function updateInheritedForceSubpostVisibility( $value, $object ) {
        return false;
    }
}
