<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Number_Field extends PMXI_Addon_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        return is_string($value) ? trim($value) : $value;
    }
}
