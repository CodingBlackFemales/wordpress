<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_View {

    public string $path = WP_ALL_IMPORT_ROOT_DIR . '/addon-api';

    public PMXI_Addon_Base $addon;
    public array $groups;
    public array $group;
    public array $fields = [];
    public array $values = [];
    public array $options = [];

    public string $addonId;
    public string $type;
    public ?string $subtype;
    public ?string $groupId;

    public function __construct(
        $addonId,
        $type,
        $subtype = null,
        $groupId = null
    ) {
        $this->addonId = $addonId;
        $this->type    = $type;
        $this->subtype = $subtype;
        $this->groupId = $groupId;

        // Cache all the data we need to render the view
        $this->addon   = PMXI_Addon_Manager::get_addon( $addonId );
        $this->groups  = $this->addon->groups( $type, $subtype );
        $this->options = $this->getOptions();
        $this->values  = $this->getValues();

        // If a group is selected, cache the group and its fields
        if ( $groupId ) {
            $this->group  = $this->addon->getGroupById( $groupId, $type, $subtype );
            $this->fields = $this->addon->getFieldsByGroup( $groupId, $type, $subtype );
        }
    }

    public function getOptions() {
        $import    = new \PMXI_Import_Record();
        $input     = new \PMXI_Input();
        $import_id = $input->get( 'id' ) ?? $input->get( 'import_id' );

        // Get import ID from CLI arguments.
        if ( empty( $import_id ) && \PMXI_Plugin::getInstance()->isCli() ) {
            $import_id = wp_all_import_get_import_id();
        }

        if ( ! empty( $import_id ) ) {
            $import->getById( $import_id );
        }

        $session    = new \PMXI_Handler();
        $templateId = ! empty( $session->is_loaded_template ) ? $session->is_loaded_template : false;

        if ( $templateId ) {
            $defaults = $this->addon->defaultOptions( $this->type, $this->subtype );
            $template = new \PMXI_Template_Record();

            if ( ! $template->getById( $templateId )->isEmpty() ) {
                $templateOptions = ! empty( $template->options ) ? $template->options : [];
                $options         = $templateOptions + $defaults;
            }
        } elseif ( ! $import->isEmpty() ) {
            $options = $import->options;
        } else {
            $options = $session->options;
        }

        return $options;
    }

    public function getAddonValue( $key, $suffix = null, $default = null ) {
        $suffix = $suffix ? '_' . $suffix : '';

        return $this->options[ $this->addon->slug . $suffix ][ $key ] ?? $default;
    }

    public function getValues() {
        $options = $this->getOptions();

        return $options[ $this->addon->slug ] ?? [];
    }

    public function existingMetaKeys( $type, $subtype ) {
        return array_map( fn( $field ) => $field['key'], $this->addon->fields( $type, $subtype ) );
    }

    /**
     * @param array $importOptions
     *
     * @return void
     * @throws \Exception
     */
    public function renderTabs( array $importOptions ) {
        $subtype = $importOptions['taxonomy_type'];

        if ( ! $this->addon->isAvailableForType( $this->type, $importOptions ) ) {
            return;
        }

        view( 'accordion', [
            'addon'         => $this->addon,
            'groups'        => $this->groups,
            'type'          => $this->type,
            'subtype'       => $subtype,
            'importOptions' => $importOptions,
        ] );
    }

    public function renderUpdate( array $importOptions ) {
        $subtype          = $importOptions['taxonomy_type'];
        $updateOptions    = $importOptions['update_addons'][ $this->addon->slug ] ?? $this->addon->defaultUpdateOptions();
        $existingMetaKeys = $this->existingMetaKeys( $this->type, $subtype );

        view( 'screens/update', [
            'addon'              => $this->addon,
            'groups'             => $this->groups,
            'type'               => $this->type,
            'subtype'            => $subtype,
            'options'            => $updateOptions,
            'prefix'             => 'update_addons[' . $this->addon->slug . ']',
            'prefix_id'          => 'update_addons_' . $this->addon->slug,
            'existing_meta_keys' => $existingMetaKeys,
        ] );
    }

    public function renderConfirmDataToImport( array $importOptions ) {
        $subtype       = $importOptions['taxonomy_type'];
        $updateOptions = $importOptions['update_addons'][ $this->addon->slug ] ?? $this->addon->defaultUpdateOptions();

        view( 'screens/confirm-data-to-import', [
            'addon'   => $this->addon,
            'groups'  => $this->groups,
            'type'    => $this->type,
            'subtype' => $subtype,
            'post'    => $updateOptions
        ] );
    }

    /**
     * Return the fields inside the accordion container
     * @return string
     * @throws \Exception
     */
    public function getFieldsHtml() {
        ob_start();

        view( 'container-start', [ 'group' => $this->group ] );

        foreach ( $this->fields as $field ) {
            PMXI_Addon_Field::from( $field, $this )->show();
        }

        if ( ! count( $this->fields ) ) {
            view( 'no-fields', [] );
        }

        view( 'container-end', [ 'group' => $this->group ] );

        return ob_get_clean();
    }

    /**
     * Create a new instance of the view
     */
    public static function create(
        string $addonId,
        string $type,
        string $subtype = null,
        string $groupId = null
    ) {
        return new self( $addonId, $type, $subtype, $groupId );
    }
}
