<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Switcher_Field extends PMXI_Addon_Field {

    public function generateValue($slug, $default = null) {
        // If this is a repeater field, 
        // get the value from the parent
        if ($this->parent && $this->repeater_row_index !== null) {
            $switchers = $this->view->options[$this->addon->slug . '_' . $slug];
            $parent_value = $switchers[$this->parent->key] ?? null;
            return $parent_value['rows'][$this->repeater_row_index][$this->key] ?? null;
        }

        return $this->view->getAddonValue($this->key, $slug, $default);
    }

    public function getSwitcherValue() {
        return $this->generateValue('switchers', 'yes');
    }

    public function getMultipleValue() {
        $val = $this->generateValue('multiple');
        $multiple = $this->data['multiple'] ?? false;

        if ($multiple && !is_array($val)) {
            return [$val];
        }

        return $val;
    }

    public function getMultipleName() {
        $multiple = $this->data['multiple'] ?? false;
        $prefix = $this->getParentsPrefix();
        $suffix = $multiple ? '[]' : '';
        return $this->addon->slug . '_multiple' . $prefix . "[$this->key]" . $suffix;
    }

    public function getParentsPrefixSafeForHtmlId() {
        return str_replace(['[', ']'], ['_', ''], $this->getParentsPrefix());
    }

    /**
     * Get field HTML
     * @return string
     */
    public function html() {
        $params = $this->params();
        $yes_name = $this->getMultipleName();
        $yes_value = $this->getMultipleValue();
        $multiple = $this->data['multiple'] ?? false;
        $yes_type = $multiple ? 'checkbox' : $this->type;

        $yes_params = array_merge(
            $params,
            [
                'html_name' => $yes_name,
                'field_value' => $yes_value
            ]
        );

        $prefix = $this->addon->slug . '_switchers';
        $id = $prefix . $this->getParentsPrefixSafeForHtmlId() . $this->key;
        $name = $prefix . $this->getParentsPrefix() . "[$this->key]";

        return view('switcher', array_merge($params, [
            'switcher_id' => $id,
            'switcher_name' => $name,
            'switcher_value' => $this->getSwitcherValue(),

            'yes_label' => __('Select value for all records', 'wp-all-import-pro'),
            'no_label' => __('Set with XPath', 'wp-all-import-pro'),

            'yes_input' => $this->getView($yes_type, $yes_params),
            'no_input' => view('fields/text', $params, null, false),
        ]), null, false);
    }
}
