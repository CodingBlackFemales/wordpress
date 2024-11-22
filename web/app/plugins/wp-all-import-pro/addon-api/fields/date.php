<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Date_Field extends PMXI_Addon_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        $timestamp = strtotime($value);
        $formatted_date = date("Y-m-d", $timestamp);
        $is_timestamp = $this->args['is_timestamp'] ?? false;
        return $is_timestamp ? $timestamp : $formatted_date;
    }
}
