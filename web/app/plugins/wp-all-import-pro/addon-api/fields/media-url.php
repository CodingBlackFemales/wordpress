<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Media_Url_field extends PMXI_Addon_Media_Field {

    public function beforeImport( $postId, $value, $data, $logger, $rawData ) {
        $id = parent::beforeImport( $postId, $value, $data, $logger, $rawData );

        return $id ? wp_get_attachment_url( $id ) : '';
    }

}
