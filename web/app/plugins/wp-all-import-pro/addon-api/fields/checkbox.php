<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Checkbox_Field extends PMXI_Addon_Switcher_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        if ($this->multiple) {
            $value = maybe_unserialize($value) ?? [];
            $value = is_string($value) ? array_values(array_filter(explode(',', $value))) : $value;
        }

        return $value;
    }
}
