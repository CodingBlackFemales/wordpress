<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Field {
    public array $data;
    public PMXI_Addon_View $view;
    public ?PMXI_Addon_Field $parent;

    public $addon;
    public $repeater_row_index;
    public static $repeater_path;
    public static $locate_template_in = null;
    public static $virtual = false; // Removes the field's parent from the hierarchy

    public function __construct(
        $data,
        $view,
        $parent = null
    ) {
        $this->data = $data;
        $this->view = $view;
        $this->parent = $parent;
        $this->addon = $view->addon;
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    public function params() {
        $hint = $this->addon->hints[$this->type] ?? null;
        $placeholder = $this->data['placeholder'] ?? $this->data['default'] ?? '';

        return [
            'field' => array_merge(
                [
                    'hint' => $hint
                ],
                $this->data
            ),
            'html_name' => $this->getName(),
            'html_placeholder' => $placeholder,
            'field_value' => $this->getValue(),
            'addon' => $this->addon,
            'field_class' => $this,
            'row_index' => $this->repeater_row_index
        ];
    }

    public function isSeparator() {
        return $this->type === 'separator' || $this->type === 'separator-end';
    }

    /**
     * Show field HTML
     */
    public function show() {
        echo $this->beginHtml();
        echo $this->html();
        echo $this->endHtml();
    }

    public function beginHtml() {
        $viewMapping = [
            'separator-end' => 'separator-end',
            'separator' => 'separator',
        ];

        $view = $viewMapping[$this->type] ?? 'field-start';

        return view(
            $view,
            $this->params(),
            null,
            false
        );
    }

    /**
     * Get field HTML
     * @return string
     */
    public function html() {
        if ($this->isSeparator()) return;
        return $this->getView($this->type, $this->params());
    }

    public function endHtml() {
        if ($this->isSeparator()) return;
        return view('field-end', $this->params(), null, false);
    }

    public function getView($type, $params) {
        if (static::$locate_template_in) {
            if ($view = $this->locateViewOverride()) {
                return $view;
            } else {
                throw new \Exception("View not found");
            }
        }
        
        return view(
            'fields/' . $type,
            $params,
            'fields/unsupported',
            null,
            false
        );
    }

    /**
     * Get the field's name to be used in the HTML input
     */
    public function getName() {
        $prefix = $this->getParentsPrefix();
        $name = $this->addon->slug . $prefix . "[$this->key]";
        return apply_filters("wp_all_import_addon_field_name", $name, $this);
    }

    /**
     * Get the current value of the field
     * This might be undefined if the import has not been run yet.
     */
    public function getValue() {
        if ($this->parent) {
            if ($this->repeater_row_index !== null) {
                // Multi-field repeater
                if ($this->repeater_row_index === '__index__') {
                    return null;
                }
    
                $parent_value = $this->parent->getValue();
    
                if (isset($parent_value)) {
                    return $parent_value['rows'][$this->repeater_row_index][$this->key] ?? null;
                }
            } else {
                // Single-field repeater
                $parent_value = $this->parent->getValue();
    
                if (isset($parent_value) && isset($parent_value[$this->key])) {
                    return $parent_value[$this->key];
                }
            }
        }

        // Normal field
        return $this->view->getAddonValue($this->key);
    }

    /**
     * Return HTML attributes for field
     * @param array $attributes
     * @return string
     */
    public function renderAttributes(array $attributes) {
        $output = '';

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }

            $output .= sprintf(' %s="%s"', $key, esc_attr($value));
        }

        return $output;
    }

    /**
     * Transform the field's value before saving it
     *
     * @param int $postId
     * @param mixed $value
     * @param array $data
     * @param callable $logger
     * @param $rawData
     *
     * @return mixed
     */
    public function beforeImport($postId, $value, array $data, $logger, $rawData) {
        return $value;
    }

    /**
     * Get the field's class
     * @param array $field
     * @param PMXI_Addon_View $view
     * @param PMXI_Addon_Field|null $parent
     * @return PMXI_Addon_Field
     */
    public static function from($field, $view, $parent = null, $resolve = true) {
        $extra_fields = $view->addon->fields;
        $class = getFieldClass($field);

        if (isset($extra_fields[$field['type']])) {
            $class = $extra_fields[$field['type']];
        }

        if ($resolve) {
            $class = $view->addon->resolveFieldClass($field, $class);
        }

        return new $class($field, $view, $parent);
    }

    /*
     * Repeater Parent
     */
    protected function getParentsPrefix() {
        $parents = join('', array_map(function ($field) {
            return '[' . $field->key . ']';
        }, $this->getParents()));

        if ($this->repeater_row_index !== null) {
            $parents .= '[rows][' . $this->repeater_row_index . ']';
        }

        return $parents;
    }

    /**
     * @return array
     */
    protected function getParents() {
        $field = $this;
        $parents = [];

        while ($field->parent) {
            $field = $field->parent;
            $parents[] = $field;
        }

        if (static::$virtual) {
            array_pop($parents);
        }

        return array_reverse($parents);
    }

    public function setRowIndex($row_index) {
        $this->repeater_row_index = $row_index;
        return $this;
    }

    protected function locateViewOverride() {
        return view(
            'html',
            $this->params(),
            null,
            false,
            static::$locate_template_in . '/',
        );
    }
}
