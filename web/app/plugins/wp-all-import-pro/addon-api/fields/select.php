<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Select_Field extends PMXI_Addon_Switcher_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        if ($this->multiple) {
            return is_string($value) ?
                array_values(
                    array_filter(
                        explode(',', $value)
                    )
                ) :
                $value;
        }

        return $value;
    }
}
