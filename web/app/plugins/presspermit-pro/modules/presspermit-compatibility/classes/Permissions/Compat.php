<?php
namespace PublishPress\Permissions;

class Compat {
    public static function mirrorExceptionItems($via_item_source, $source_id, $target_ids, $postmeta_key = false)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/Mirror.php');
        return Compat\Mirror::mirrorExceptionItems($via_item_source, $source_id, $target_ids, $postmeta_key);
    }
}
