<?php
namespace PublishPress\Permissions\Compat;

class WPML
{
    function __construct()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/WPML/Hooks.php');
        new WPML\Hooks();

        if (is_admin()) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/WPML/HooksAdmin.php');
            new WPML\HooksAdmin();
        }
    }
}