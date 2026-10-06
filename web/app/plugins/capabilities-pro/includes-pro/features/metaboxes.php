<?php
namespace PublishPress\Capabilities;

class EditorFeaturesMetaboxes {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new EditorFeaturesMetaboxes();
        }

        return self::$instance;
    }

    function __construct() {
        add_filter('pp_capabilities_post_feature_elements', [$this, 'fltElements']);
        add_filter('pp_capabilities_post_feature_elements_classic', [$this, 'fltElements']);
    }

    /**
     * Array list of ids excluded for metaboxes section.
     *
     * @return array
     *
     * @since 2.1.1
     */
    private static function getExcludedMetaboxes()
    {
        $data = [
            'postimagediv',//Featured image
            'commentstatusdiv',//Discussion
            'postexcerpt',//Excerpt
            'submitdiv',//Publish
            'slugdiv',
            'authordiv',
            'pageparentdiv',
            'trackbacksdiv',
            'postcustom',
            'formatdiv',
        ];

        return $data;
    }

    /**
     * Fetch our metaboxes post feature options.
     *
     * @return array
     *
     * @since 2.1.1
     */
    public static function getData()
    {
        $data = (array)get_option('ppc_feature_post_metaboxes_data');
        $data = array_filter($data);

        return $data;
    }

    /**
     * Get an additional element selector for 
     * metabox. Some metabox Gutenberg selector 
     * are custom element other than their ID and 
     * the alternative solution so far is adding 
     * custom support to known issues/metabox
     *
     * @return array
     *
     * @since 2.4.0
     */
    public static function getAdditionalMetaboxElements()
    {
        $metabox_element = [
            'revisionsdiv'            => '.components-panel__body.edit-post-last-revision__panel',
            'monsterinsights-metabox' => '.components-panel__body.monsterinsights-skip-tracking-wrapper',
        ];

        return apply_filters('pp_capabilities_additional_metabox_elements', $metabox_element);
    }

    /**
     * Filter post features element and add metaboxes items
     *
     * @param array $elements Post screen elements.
     *
     * @since 2.1.1
     */
    function fltElements($elements)
    {
        $post_metaboxes_data = self::getData();
        $added_element       = [];
        $included_metabox    = [];
        $excluded_ids        = self::getExcludedMetaboxes();

        $new_elements = [];

        if (count($post_metaboxes_data) > 0) {
            foreach ($post_metaboxes_data as $name => $restrict_data) {
                foreach ($post_metaboxes_data as $post_type => $post_meta_values) {
                    if (is_array($post_meta_values) && count($post_meta_values) > 0) {
                        foreach ($post_meta_values as $post_meta_value) {
                            if (!$post_meta_value) {
                                continue;
                            }
                            $metabox_id = $post_meta_value['id'];
                            if (!in_array($metabox_id, $included_metabox) && !in_array($metabox_id, $excluded_ids)) {
                                
                                // prep for alpha sorting of autodetected metaboxes
                                if (!empty(trim($post_meta_value['title'])) && !empty(trim($metabox_id))) {
                                    $element_id = '#' . $metabox_id;
                                    if (array_key_exists($metabox_id, self::getAdditionalMetaboxElements())) {
                                        $element_id .= ', ' . self::getAdditionalMetaboxElements()[$metabox_id];
                                    }
                                    $element_id .= ', #screen-options-wrap label[for='.$metabox_id.'-hide]';
                                    $element_id .= ', #screen-options-wrap label[for='.$metabox_id.'div-hide]';

                                    $new_elements[$post_meta_value['title']] = [
                                        $metabox_id => [
                                            'label'        => $post_meta_value['title'],
                                            'elements'     => $element_id,
                                            'support_key'  => [$post_type],
                                            'support_type' => 'metabox'
                                        ]
                                    ];
                                }
                                
                                $included_metabox[]         = $metabox_id;
                            } elseif (in_array($metabox_id, $included_metabox) 
                                && isset($new_elements[$post_meta_value['title']])
                                && isset($new_elements[$post_meta_value['title']][$metabox_id])
                                && isset($new_elements[$post_meta_value['title']][$metabox_id]['support_key'])
                                && !in_array($post_type, (array)$new_elements[$post_meta_value['title']][$metabox_id]['support_key'])
                                ) {
                                if (is_array($new_elements[$post_meta_value['title']][$metabox_id]['support_key'])) {
                                    $new_elements[$post_meta_value['title']][$metabox_id]['support_key'][] = $post_type;
                                } else {
                                    $new_elements[$post_meta_value['title']][$metabox_id]['support_key'] = [$post_type];
                                }
                            }
                        }
                    }
                }
            }
        }

        ksort($new_elements);

        foreach($new_elements as $elem) {
            foreach($elem as $metabox_id => $v) {
                $added_element[$metabox_id] = $v;
            }
        }

        $elements[__('Metaboxes', 'capabilities-pro')] = $added_element;

        return $elements;
    }
}
