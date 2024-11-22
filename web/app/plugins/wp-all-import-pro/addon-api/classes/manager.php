<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Manager {
    use Singleton;

    /**
     * @return PMXI_Addon_Base[]
     */
    public static function get_addons() {
        return apply_filters( 'pmxi_new_addons', [] );
    }

    public static function get_addon( $addon ) {
        $addons = self::get_addons();

        return $addons[ $addon ];
    }

    public static function get_owner_addon_for_type( $options ): ?PMXI_Addon_Base {
        $addons = self::get_addons();

        foreach ( $addons as $addon ) {
            if ( $addon->ownsImportType( $options['custom_type'], $options ) ) {
                return $addon;
            }
        }

        return null;
    }
}
