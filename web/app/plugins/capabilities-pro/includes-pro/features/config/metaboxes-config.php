<?php
namespace PublishPress\Capabilities;

class EditorFeaturesMetaboxesConfig {
    function __construct() {
        require_once (dirname(PUBLISHPRESS_CAPS_PRO_FILE) . '/includes-pro/features/metaboxes.php');
        EditorFeaturesMetaboxes::instance();
    }

    /**
     * Retrieve current post screen metaboxes
     *
     * @param string $screen Screen id
     *
     * @return string
     *
     * @since 2.1.1
     */
    private function getMetaBoxes($screen = null)
    {
        global $wp_meta_boxes;

        $meta_boxes = false;

        if (empty($screen)) {
            $screen = get_current_screen();

        } elseif (is_string($screen)) {
            $screen = convert_to_screen($screen);
        }

        if ($screen) {
            $page       = $screen->id;
            $meta_boxes = $wp_meta_boxes[$page];
        }

        return $meta_boxes;
    }

    /**
     * Capture metaboxes for post features
     *
     * @param array $post_types Post type.
     * @param array $elements All elements.
     * @param array $post_disabled All disabled post type element.
     *
     * @since 2.1.1
     */
    function capturePostFeatureMetaboxes($post_type)
    {
        $current_meta_box = [];
        $fetch_metaboxes  = $this->getMetaBoxes($post_type);

        if (is_array($fetch_metaboxes) && count($fetch_metaboxes) > 0) {
            foreach ($fetch_metaboxes as $post_metabox_locations => $post_metabox_values) {
                foreach ($post_metabox_values as $post_metabox_priority => $post_metabox_datas) {
                    if (count($post_metabox_datas) > 0) {
                        foreach ($post_metabox_datas as $meta_key => $meta_values) {
                            if (!is_array($meta_values)) {
                                continue;
                            }

                            if (isset($meta_values['args']) && is_array($meta_values['args']) && isset($meta_values['args']['taxonomy']) && !empty($meta_values['args']['taxonomy'])) {
                                //exclude taxonomy since they've been covered by another section
                                continue;
                            }

                            if(is_array($meta_values)){// fix 'Trying to access array offset on value of type bool' warning
                                $current_meta_box[$meta_key] = [
                                    'id'        => $meta_values['id'],
                                    'title'     => $meta_values['title'],
                                    'locations' => $post_metabox_locations,
                                    'priority'  => $post_metabox_priority
                                ];
                            }
                        }
                    }
                }
            }
        }

        $post_metaboxes_data = EditorFeaturesMetaboxes::getData();

        if (!isset($post_metaboxes_data[$post_type]) || ($post_metaboxes_data[$post_type] !== $current_meta_box)) {
            //save the result
            $post_metaboxes_data[$post_type] = $current_meta_box;
            update_option('ppc_feature_post_metaboxes_data', $post_metaboxes_data);
        }
    }
}
