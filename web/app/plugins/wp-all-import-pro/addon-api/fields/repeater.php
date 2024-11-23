<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Repeater_Field extends PMXI_Addon_Field {

    /*
     * Parse the repeater and its subfields
     */
    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        $mode = $value['mode'] ?? 'fixed';
        $ignore_blanks = (bool) ($value['ignore_blanks'] ?? false);

        $parseFunction = [
            'variable-xml' => 'parseVariableXml',
            'variable-csv' => 'parseVariableCsv',
            'fixed' => 'parseFixed'
        ][$mode];

        $rows = $this->$parseFunction($postId, $value, $data, $logger, $rawData);

        return $ignore_blanks ? $this->filterOutBlankRows($rows) : $rows;
    }

    /*
     * Determine whether a row can be imported by checking if it has any non-empty values
     * @param $row array
     * @return bool
     */
    public static function canImportRow($row) {
        $filtered = array_filter($row, fn ($value) => !empty($value));
        return count($filtered) > 0;
    }

    /*
     * Get the row field value given a xpath.
     * @param $xml string
     * @param $rootNodeXPath string
     * @param $xpath string|array
     * @return mixed
     */
    public function getRowFieldValue($xml, $rootNodeXPath, $xpath, $index) {
        if (is_array($xpath)) {
            $values = [];
            foreach ($xpath as $key => $nestedXpath) {
                // For some reason, the XML parsed doesn't like empty strings and will go into an infinite loop.
                $values[$key] = $nestedXpath ? $this->getRowFieldValue($xml, $rootNodeXPath, $nestedXpath, $index) : $nestedXpath;
            }
            return $values;
        }

        $file = false;
        $parsed = \XmlImportParser::factory($xml, $rootNodeXPath . '[' . ($index + 1) . ']', $xpath, $file)->parse();
        @unlink($file);

        return $parsed[0] ?? '';
    }

    /*
     * Parse XML rows
     * @param $xml string
     * @param $cxpath string
     * @param $template array
     * @return array
     */
    public function parseXmlRows($xml, $rootNodeXPath, $template) {
        $file = false;
        $parsed_rows = \XmlImportParser::factory($xml, $rootNodeXPath, "{.}", $file)->parse();
        @unlink($file);

        $rows = [];

        for ($i = 0; $i < count($parsed_rows); $i++) {
            $row = [];

            foreach ($template as $key => $xpath) {
                $row[$key] = $this->getRowFieldValue($xml, $rootNodeXPath, $xpath, $i);
            }

            $rows[$i] = $row;
        }

        return $rows;
    }

    /*
     * Parse Variable XML
     * @param $postId int
     * @param $value array
     * @param $data array
     * @param $logger callable
     * @param $rawData array
     * @return array
     */
    public function parseVariableXml($postId, $value, $data, $logger, $rawData) {
        $template_rows = $rawData['rows'] ?? [];
        $template = array_shift($template_rows);
        $xml_for_each = ltrim(trim($rawData['foreach'], '{}!'), '/');

        // xpaths
        $repeater_xpath = '[' . ($data['i'] + 1) . ']/' . $xml_for_each;
        $xpath = $data['xpath_prefix'] . $data['import']->xpath . $repeater_xpath;

        $row_data = [];
        $rows = $this->parseXmlRows($data['xml'], $xpath, $template);

        foreach ($rows as $index => $row) {
            foreach ($this->subfields as $subfield) {
                $field_instance = PMXI_Addon_Field::from($subfield, $this->view, $this);
                $field_value = $row[$subfield['key']];
                $field_value_raw = $template[$subfield['key']];

                $row_data[$index][$subfield['key']] = $field_instance->beforeImport(
                    $postId,
                    $field_value,
                    $data,
                    $logger,
                    $field_value_raw
                );
            }
        }

        return $row_data;
    }

    /*
     * Handle complex field types like Media, Gallery, and Posts.
     */
    public function formatFieldValue($value, $delimiter, $repeater_path) {
        if (isset($repeater_path)) {
            $nested_path_values = explode($delimiter, $value[$repeater_path]);
            $new_value = [];

            foreach ($nested_path_values as $nested_value) {
                $new_value[] = array_merge(
                    $value,
                    [$repeater_path => $nested_value]
                );
            }

            return $new_value;
        }

        if (is_array($value)) {
            return $value;
        }

        return explode($delimiter, $value);
    }

    /*
     * Parse Variable CSV
     * @param $postId int
     * @param $value array
     * @param $data array
     * @param $logger callable
     * @param $rawData array
     * @return array
     */
    public function parseVariableCsv($postId, $value, $data, $logger, $rawData) {
        $rows = $value['rows'] ?? [];
        $row = array_shift($rows);
        $template_rows = $rawData['rows'] ?? [];
        $template = array_shift($template_rows);
        $delimiter = $value['separator'] ?? '|';
        $row_data = [];

        foreach ($this->subfields as $subfield) {
            if (empty($row)) continue;
            if (empty($row[$subfield['key']])) continue;

            $field_instance = PMXI_Addon_Field::from($subfield, $this->view, $this);
            $field_value = $row[$subfield['key']];
            $field_value = $this->formatFieldValue(
                $field_value,
                $delimiter,
                $field_instance::$repeater_path
            );
            $field_value_raw = $template[$subfield['key']];

            // Loop through each value and parse it as a field.
            $current_row = array_map(
                fn ($value) =>
                $field_instance->beforeImport(
                    $postId,
                    $value,
                    $data,
                    $logger,
                    $field_value_raw
                ),
                $field_value
            );


            foreach ($current_row as $index => $v) {
                $row_data[$index][$subfield['key']] = $v;
            }
        }

        return $row_data;
    }

    /*
     * Parse Fixed Mode
     * @param $postId int
     * @param $value array
     * @param $data array
     * @param $logger callable
     * @param $rawData array
     * @return array
     */
    public function parseFixed($postId, $value, $data, $logger, $rawData) {
        $template_rows = $rawData['rows'] ?? [];
        $template = array_shift($template_rows);
        $rows = $value['rows'] ?? [];
        $row_data = [];

        foreach ($rows as $index => $row) {
            foreach ($this->subfields as $subfield) {
                if (empty($row)) continue;

                $field_instance = PMXI_Addon_Field::from($subfield, $this->view, $this);
                $field_value = $row[$subfield['key']];
                $field_value_raw = $template[$subfield['key']];

                $current_row = $field_instance->beforeImport(
                    $postId,
                    $field_value,
                    $data,
                    $logger,
                    $field_value_raw
                );

                $row_data[$index][$subfield['key']] = $current_row;
            }
        }

        return $row_data;
    }

    public function filterOutBlankRows($rows) {
        return array_values(
            array_filter(
                $rows,
                [__CLASS__, 'canImportRow']
            )
        );
    }
}
