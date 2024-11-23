<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Admin {
    use Singleton;

    public string $url = WP_ALL_IMPORT_ROOT_URL . '/addon-api';

    public function __construct() {
        add_action( 'pmxi_extend_options_custom_fields', [ $this, 'render' ], 10, 2 );
        add_action( 'pmxi_reimport', [ $this, 'renderUpdateScreen' ], 10, 2 );
        add_action( 'pmxi_confirm_data_to_import', [ $this, 'renderConfirmDataToImport' ], 10, 2 );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'script_loader_tag', [ $this, 'add_type_attribute' ], 10, 3 );
    }

    public function enqueue() {
        wp_enqueue_script( 'pmxi-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.3.5/air-datepicker.min.js' );
        wp_enqueue_style( 'pmxi-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.3.5/air-datepicker.min.css' );

        wp_enqueue_style( 'pmxi-addon-admin-style', $this->url . '/static/css/admin.css' );
        wp_enqueue_script( 'pmxi-addon-admin-script', $this->url . '/static/js/admin.js' );
        wp_localize_script( 'pmxi-addon-admin-script', 'pmxiAddon', [
            'ajaxUrl' => get_rest_url( null, 'wp-all-import/v1/addon/fields' ),
        ] );
    }

    public function add_type_attribute( $tag, $handle, $src ) {
        if ( 'pmxi-addon-admin-script' !== $handle ) {
            return $tag;
        }
        // Change the script tag by adding type="module" and return it.
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';

        return $tag;
    }

    /**
     * Render something on the import page
     *
     * @param string $type
     * @param array $importOptions
     *
     * @return void
     */
    public function render( string $type, array $importOptions ) {
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderTabs( $importOptions );
        }
    }

    /**
     * Render the update screen
     *
     * @param string $type
     * @param array $importOptions
     *
     * @return void
     */

    public function renderUpdateScreen( string $type, array $importOptions ) {
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderUpdate( $importOptions );
        }
    }

    public function renderConfirmDataToImport( bool $isWizard, array $importOptions ) {
        $type    = $importOptions['custom_type'];
        $subtype = $importOptions['taxonomy_type'];
        $addons  = PMXI_Addon_Manager::get_addons();

        if ( empty( $addons ) ) {
            return;
        }

        foreach ( $addons as $addon ) {
            $view = PMXI_Addon_View::create( $addon->slug, $type, $subtype );
            $view->renderConfirmDataToImport( $importOptions );
        }
    }
}
