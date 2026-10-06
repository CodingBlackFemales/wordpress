<?php
namespace PublishPress;

if (!class_exists('PublishPress\PPP_Module_Base')) {
    /**
     * PublishPress_Module
     */
    class PPP_Module_Base 
    {
        public $title = '';
        public $short_description = '';

        public $module_url;
        private $version;

        public $options_group_name = '';
        public $default_options = [];
        public $options = [];
        public $post_type_support = '';

        public $messages = [];

        public function __construct() {
            add_action('publishpress_plugin_screen', [$this, 'actLoadPluginScreen']);
        }

        function load_options($options_group_name) {
            $this->options_group_name = $options_group_name;
            
            $this->options = get_option(
                $options_group_name,
                new \stdClass()
            );

            foreach ($this->default_options as $default_key => $default_value) {
                if (! isset($this->options->$default_key)) {
                    $this->options->$default_key = $default_value;
                }
            }

            if (!empty($this->options->default_privacy)) {
               $this->options->default_privacy = array_intersect_key($this->options->default_privacy, array_fill_keys($this->get_enabled_post_types(), true));
            }
        }

        /**
         * Collect all of the active post types for a given module
         *
         * @param object $module Module's data
         *
         * @return array $post_types
         *
         */
        public function get_enabled_post_types()
        {
            return (empty($this->options->post_types)) 
            ? [] 
            : array_keys(
                array_filter(
                    $this->options->post_types,
                    function($k) {
                        return !empty($k);
                    }
                )
            );
        }

        function actLoadPluginScreen($module) {
            require_once(__DIR__ . '/ModuleAdminUI_Base.php');
            ModuleAdminUI_Base::instance($module);
        }
    }
}
