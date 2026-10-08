<?php
namespace PublishPress\Capabilities;

class EditorFeaturesCustom {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new EditorFeaturesCustom();
        }

        return self::$instance;
    }

    function __construct() {
        // late registration to push Custom Items to bottom, just above entry form
        add_filter('pp_capabilities_post_feature_elements', [$this, 'fltCustomElements'], 50);
        add_filter('pp_capabilities_post_feature_elements_classic', [$this, 'fltCustomElementsClassic'], 50);
        //add editor feature section title
        add_filter('pp_capabilities_editor_features_titles', [$this, 'editorFeatureTitles']);
    }

    /**
     * Fetch our customs post feature gutenberg options.
     *
     * @return mixed
     *
     * @since 2.1.1
     */
    public static function getData()
    {
        $data = (array)get_option('ppc_feature_post_gutenberg_custom_data');
        $data = array_filter($data);

        if (empty($data) && empty(get_option('ppc_editor_feature_custom_demo_installed'))) {
            $data = [];
            //add demo data 1
            $element_id = uniqid(true) . 11;
            $data[$element_id]= ['label' => __('Hide the Patterns tab in the block editor', 'capabilities-pro'), 'elements' => '.block-editor-tabbed-sidebar #tabs-3-patterns'];
            update_option('ppc_feature_post_gutenberg_custom_data', $data);
            update_option('ppc_editor_feature_custom_demo_installed', 1);
        }

        return $data;
    }

    /**
     * Fetch our customs post feature classic options.
     *
     * @return mixed
     *
     * @since 2.1.1
     */
    public static function getClassicData()
    {
        $data = (array)get_option('ppc_feature_post_classic_custom_data');
        $data = array_filter($data);

        if (empty($data) && empty(get_option('ppc_editor_feature_custom_classic_demo_installed'))) {
            $data = [];
            //add demo data 1
            $element_id = uniqid(true) . 21;
            $data[$element_id]= ['label' => __('Hide the Insert/edit link button in the Classic Editor', 'capabilities-pro'), 'elements' => '.wp-editor-container .mce-container-body div.mce-btn:has(button .mce-i-link)'];
            update_option('ppc_feature_post_classic_custom_data', $data);
            update_option('ppc_editor_feature_custom_classic_demo_installed', 1);
        }

        return $data;
    }

    function fltCustomElements($elements) {
        $data = self::getData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $added_element[$name] = [
                    'label'          => $restrict_data['label'],
                    'elements'       => $restrict_data['elements'],
                    'custom_element' => true,
                    'button_class'   => 'ppc-custom-features-delete',
                    'button_data_id' => $name,
                    'button_data_parent' => 'gutenberg',
                    'element_label'  => $restrict_data['label'],
                    'element_items'  => $restrict_data['elements'],
                ];
            }
        }

        $elements['Custom Gutenberg Restrictions'] = $added_element;

        return $elements;
    }

    function fltCustomElementsClassic($elements) {
        $data = self::getClassicData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $added_element[$name] = [
                    'label'          => $restrict_data['label'],
                    'elements'       => $restrict_data['elements'],
                    'custom_element' => true,
                    'button_class'   => 'ppc-custom-features-delete',
                    'button_data_id' => $name,
                    'button_data_parent' => 'classic',
                    'element_label'  => $restrict_data['label'],
                    'element_items'  => $restrict_data['elements'],
                ];
            }
        }

        $elements['Custom Classic Editor Restrictions'] = $added_element;

        return $elements;
    }


    /**
     * Editor features title filter
     *
     * @param array $titles
     *
     * @return array $titles
     *
     * @since 2.3.1
     */
    function editorFeatureTitles($titles) {

        $titles['Custom Classic Editor Restrictions']     = __('Custom Classic Editor Restrictions', 'capabilities-pro');
        $titles['Custom Gutenberg Restrictions']          = __('Custom Gutenberg Restrictions', 'capabilities-pro');

        return $titles;
    }
}
