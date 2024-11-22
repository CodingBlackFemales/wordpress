<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Text_Field extends PMXI_Addon_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        return trim($value);
    }
}
