<?php

namespace Wpai\AddonAPI;

abstract class PMXI_Addon_Base {
    use HasError, HasRegistration;

    public PMXI_Addon_Importer $importer;

    public $slug = 'not-implemented'; // Must be implemented by end-user
    public $version = '0.0.0'; // Must be implemented by end-user
    public $rootDir = ''; // Must be implemented by end-user

    // Extra fields created by the addon
    public $fields = [];

    // Cast values to something else without having to create a custom field
    public $casts = [];

    // Add tooltips to fields
    public $hints = [];

    // Getters
    abstract public function name(): string;

    abstract public function description(): string;

    public function __construct() {
        $this->preflight();
        $this->registerAsAddon();

        $this->importer = PMXI_Addon_Importer::from( $this );
        $this->initEed();

        $this->hints = [
            'time'        => __( 'Use any format supported by the PHP strtotime function.', 'wp-all-import-pro' ),
            'date'        => __( 'Use any format supported by the PHP strtotime function.', 'wp-all-import-pro' ),
            'datetime'    => __( 'Use any format supported by the PHP strtotime function.', 'wp-all-import-pro' ),
            'iconpicker'  => __( 'Specify the icon class name - e.g. fa-user.', 'wp-all-import-pro' ),
            'colorpicker' => __( 'Specify the hex code the color preceded with a # - e.g. #ea5f1a.', 'wp-all-import-pro' ),
            'media'       => __( 'Specify the URL to the image or file.', 'wp-all-import-pro' ),
            'post'        => __( 'Enter the ID, slug, or Title. Separate multiple entries with separator character.', 'wp-all-import-pro' ),
            'user'        => __( 'Enter the ID, username, or email for the existing user.', 'wp-all-import-pro' ),
	        'map'         => __( 'WP All Import will first try to get your Google Maps API key from the add-on you\'re using. If that fails you must enter the key under \'Google Maps Settings\' below.')
        ];

        add_filter( 'wp_all_import_addon_parse', [ $this, 'registerParseFunction' ] );
        add_filter( 'wp_all_import_addon_import', [ $this, 'registerImportFunction' ] );
        add_filter( 'wp_all_import_addon_saved_post', [ $this, 'registerPostSavedFunction' ] );
        add_filter( 'pmxi_custom_types', [ $this, 'registerCustomTypes' ], 2, 5 );
        add_filter( 'pmxi_options_options', [ $this, 'registerDefaultOptions' ] );
        add_filter( 'pmxi_save_options', [ $this, 'updateOptions' ] );
        add_filter( 'pmxi_custom_field_to_delete', [ $this, 'canDeleteField' ], 99, 5 );
        add_filter( 'pmxi_visible_template_sections', [ $this, 'getVisibleSections' ], 99, 2 );
        add_filter( 'pmxi_hidden_data_to_update_options', [ $this, 'getHiddenChooseDataToUpdateOptions' ], 99, 2 );
	    add_filter( 'pmxi_disabled_delete_missing_options', [ $this, 'getDisabledDeleteMissingOptions' ], 99, 2 );
	    add_filter( 'pmxi_hidden_delete_missing_options', [ $this, 'getHiddenDeleteMissingOptions' ], 99, 2 );
	    add_filter( 'pmxi_status_of_removed_options', [ $this, 'getStatusOfRemovedOptions' ], 99, 2 );
        add_filter( 'pmxi_fire_hooks', [ $this, 'shouldFirePostHooks' ], 10, 2 );
        add_filter( 'pmxi_types_current_type_supports_title', [ $this, 'supportsTitle' ], 99, 2 );
    }

    /**
     * Path to the plugin file relative to the plugins directory.
     */
    public function getPluginPath() {
        return $this->rootDir . '/plugin.php';
    }

    /**
     * Do stuff before the plugin is activated
     *
     * @return void
     */
    public function preflight() {
        $results = $this->canRun();

        if ( is_wp_error( $results ) ) {
            $this->showErrorAndDeactivate( $results->get_error_message() );
        }
    }

    /**
     * Determine if the addon can be used for the current import type.
     *
     * @param string $importType
     * @param $options
     *
     * @return bool
     */
    public function isAvailableForType( string $importType, $options ) {
        $customTypes = array_keys($this->getCustomTypes());
        $types       = $this->availableForTypes();

        $unprefixedTypes = array_values( array_filter( $types, fn( $type ) => $type[0] !== '-' ) );
        // If the type is prefixed with a dash, it means the addon not available for it
        $shouldSkip = in_array( '-' . $importType, $types );


        if ( in_array( $importType, $customTypes ) ) {
            return true;
        }

        if ( $importType === 'taxonomies' ) {
            $taxonomy = $options['taxonomy_type'];

            return count( $unprefixedTypes ) === 0 || in_array( 'taxonomy:' . $taxonomy, $types ) || in_array( 'taxonomies', $types );
        }

        if ( $shouldSkip ) {
            return false;
        }

        return count( $unprefixedTypes ) === 0 || in_array( $importType, $types );
    }

    /**
     * Provide an interface for developers to create custom importers for Non-WordPress data.
     *
     * @param $options
     *
     * @return class-string<PMXI_Addon_Importer>|null
     */
    public function getCustomImporter( $options ) {
        return null;
    }

    /**
     * List of post types and taxonomies the plugin is available for.
     * Leave empty to make it available for all post types.
     *
     * @return string[]
     */
    public function availableForTypes() {
        return [];
    }

    /**
     * Was this import type created by this addon?
     *
     * @param string $importType
     * @param $options
     *
     * @return bool
     */
    public function ownsImportType( string $importType, $options ) {
        $types = array_keys( $this->getCustomTypes() );
        return in_array( $importType, $types );
    }

    /**
     * Register Custom Types that are not part of WordPress core.
     * - This is useful for plugins that have their own data structures.
     * - Prefix the key with the plugin slug to avoid conflicts.
     *
     * @return string[]
     */
    public function getCustomTypes() {
        return [];
    }

    /**
     * The function called by the add_filter hook.
     *
     * @return string[]
     */
    public function registerCustomTypes( $types, $section ) {
        return array_merge( $types, $this->getCustomTypes() );
    }

    /**
     * Show or hide sections based on the import type.
     *
     * @param $sections
     * @param $type
     *
     * @return mixed
     */
    public function getVisibleSections( $sections, $type ) {
        return $sections;
    }

    /**
     * Show or hide sections based on the import type in "Choose Which Data to Update" options.
     *
     * @param $options
     * @param $type
     *
     * @return mixed
     */
    public function getHiddenChooseDataToUpdateOptions( $options, $type ) {
        return $options;
    }

	/**
	 * Enable or Disable delete sections based on the import type in "What do you want to do with those..." options.
	 *
	 * @param $options
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getDisabledDeleteMissingOptions( $options, $type ) {
		return $options;
	}

	/**
	 * Show or hide delete sections based on the import type in "What do you want to do with those..." options.
	 *
	 * @param $options
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getHiddenDeleteMissingOptions( $options, $type ) {
		return $options;
	}

	/**
	 * Modify statuses based on the import type in "Change status of removed...".
	 *
	 * @param $options
	 * @param $type
	 *
	 * @return mixed
	 */
	public function getStatusOfRemovedOptions( $options, $type ) {
		return $options;
	}

    /**
     * Decide if we should fire important hooks after custom fields are added.
     * This is only applicable custom post type.
     *
     * @param bool $should_fire
     * @param string $type
     *
     * @return bool
     */
    public function shouldFirePostHooks( bool $should_fire, string $type ) {
        return $should_fire;
    }

    /**
     * Determine if the import type supports a title field.
     *
     * @param bool $supports
     * @param string $type
     *
     * @return bool
     */
    public function supportsTitle(bool $supports, string $type) {
        return $supports;
    }

    /**
     * @return true
     */
    public function isAccordionClosed(string $type, string $subtype = null) {
        return true;
    }

    /**
     * Allow addons to initialize their own EED classes. Empty by default.
     *
     * @return void
     */
    public function initEed() {}

    /**
     * Determine if the plugin can run on the current site otherwise disable it.
     *
     * @return bool|\WP_Error
     */
    abstract public function canRun();

    /**
     * Get fields by import type (post, term, user, etc.) and taxonomy (if applicable)
     *
     * @param string $type
     * @param string|null $subtype
     *
     * @return mixed
     */
    abstract public static function fields( string $type, string $subtype = null );

    /**
     * Get groups by import type (post, term, user, etc.) and taxonomy (if applicable)
     *
     * @param string $type
     * @param string|null $subtype
     *
     * @return mixed
     */
    abstract public static function groups( string $type, string $subtype = null );

    /**
     * Import fields to the database
     */
    abstract public static function import(
        int   $id,
        array $fields,
        array $values,
        \PMXI_Import_Record $record,
        array $post,
        $logger
    );

    /**
     * Potentially change the class of a field at runtime
     *
     * @param array $field
     * @param class-string<PMXI_Addon_Field> $class
     */
    public function resolveFieldClass( $field, $class ) {
        return $class;
    }

    /**
     * Internal method to simplify the import function for end-users.
     *
     * @param array $importData
     * @param array $parsedData
     */
    public function transformImport( array $importData, array $parsedData ) {
        $params = $this->importer->simplify( $importData, $parsedData );
        if ( ! $params ) {
            return;
        }
        call_user_func_array( [ $this, 'import' ], $params );
    }

    /**
     * Parse the data from the XML file
     *
     * @param array $data
     *
     * @return array
     */
    public function parse( array $data ) {
        $type     = $data['import']->options['custom_type'];
        $subtype  = $data['import']->options['taxonomy_type'];
        $defaults = $this->importer->defaults( $type, $subtype );

        return PMXI_Addon_Parser::from( $this, $data, $defaults );
    }

    /**
     * Called after the post has been saved
     */
    public function postSaved( array $importData ) {
    }

    public function defaultOptions( string $type, string $subtype = null ) {
        return $this->importer->defaults( $type, $subtype );
    }

    public function defaultUpdateOptions() {
        return [
            'is_update'          => true,
            'update_logic'       => 'full_update',
            'fields_list'        => [],
            'fields_only_list'   => [],
            'fields_except_list' => [],
        ];
    }

    public function updateOptions( $options ) {
        if ( ! isset( $options['update_addons'][ $this->slug ] ) ) {
            return $options;
        }

        $post = $options['update_addons'][ $this->slug ];

        if ( $post['update_logic'] === 'only' && ! empty( $post['fields_only_list'] ) ) {
            $post['fields_list'] = explode( ",", $post['fields_only_list'] );
        } elseif ( $post['update_logic'] == 'all_except' && ! empty( $post['fields_except_list'] ) ) {
            $post['fields_list'] = explode( ",", $post['fields_except_list'] );
        }

        $options['update_addons'][ $this->slug ] = $post;

        return $options;
    }

    /**
     * @param bool $field_to_delete
     * @param int $pid
     * @param string $post_type
     * @param array $options
     * @param string $cur_meta_key
     *
     * @return bool
     */
    public function canDeleteField( $field_to_delete, $pid, $post_type, $options, $cur_meta_key ) {
        $type    = $options['custom_type'];
        $subtype = $options['taxonomy_type'];
        $data    = $options[ $this->slug ] ?? null;
        $groups  = $options[ $this->slug . '_groups' ] ?? [];

        $fields = $this->getImportFields( $type, $subtype, $groups, $data );
        $field  = $this->getFieldByKey( $fields, $cur_meta_key );

        if ( ! $field ) {
            return apply_filters( "pmxi_is_{$this->slug}_update_allowed", $field_to_delete, $cur_meta_key, $options );
        }

        $canDelete = $this->importer->canDeleteField( $field, $options );

        return apply_filters( "pmxi_is_{$this->slug}_update_allowed", $canDelete, $cur_meta_key, $options );
    }

    public function getImportFields( $type, $subtype, $groups, $data ) {
        // Filter out fields that don't exist in the parsed data
        $fields = array_filter(
            $this->fields( $type, $subtype ),
            fn( $field ) => isset( $data[ $field['key'] ] )
        );

        // Filter out fields from disabled groups
        $fields = array_filter(
            $fields,
            fn( $field ) => in_array( $field['group'], $groups )
        );

        return array_values( $fields );
    }

    // Fields and groups helpers

    public function getFieldByKey( $fields, $key ) {
        $index = array_column( $fields, 'key' );
        $map   = array_flip( $index );

        return $fields[ $map[ $key ] ?? null ] ?? null;
    }

    /**
     * @param string $groupId
     * @param string $type
     * @param string|null $subtype
     *
     * @return array
     */
    public static function getFieldsByGroup( string $groupId, string $type, string $subtype = null ) {
        return pipe( static::fields( $type, $subtype ), [
            fn( $fields ) => array_filter( $fields, fn( $field ) => $field['group'] === $groupId ),
            fn( $fields ) => array_values( $fields )
        ] );
    }

    public static function getGroupById( string $groupId, string $type, string $subtype = null ) {
        return pipe( static::groups( $type, $subtype ), [
            fn( $groups ) => array_filter( $groups, fn( $group ) => $group['id'] === $groupId ),
            fn( $groups ) => array_values( $groups )[0]
        ] );
    }
}

trait HasRegistration {
    // Todo: Maybe refactor this by using the PMXI_Admin_Addons class
    public function registerAsAddon() {
        add_filter(
            'pmxi_new_addons',
            function ( $addons ) {
                $addons[ $this->slug ] = $this;

                return $addons;
            }
        );

        add_filter( 'pmxi_addons', function ( $addons ) {
            if ( empty( $addons[ $this->slug ] ) ) {
                $addons[ $this->slug ] = 1;
            }

            return $addons;
        } );
    }

    public function registerParseFunction( $functions ) {
        $functions[ $this->slug ] = [ $this, 'parse' ];

        return $functions;
    }

    public function registerImportFunction( $functions ) {
        $functions[ $this->slug ] = [ $this, 'transformImport' ];

        return $functions;
    }

    public function registerPostSavedFunction( $functions ) {
        $functions[ $this->slug ] = [ $this, 'postSaved' ];

        return $functions;
    }

    public function registerDefaultOptions( $options ) {
        $options = $options + $this->defaultOptions( $options['custom_type'], $options['taxonomy_type'] );

        return $options;
    }
}

trait HasError {
    public function showErrorAndDeactivate( string $msg ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $notice = new \Wpai\WordPress\AdminErrorNotice( $msg );
        $notice->render();

        deactivate_plugins( $this->getPluginPath() );
    }

    public function getMissingDependencyError( $pluginName, $pluginUrl ) {
        return new \WP_Error( 'missing_dependency', __(
            sprintf( "<b>%s Plugin</b>: <a target=\"_blank\" href=\"%s\">%s</a> must be installed", $this->name(), $pluginUrl, $pluginName ),
            'wp-all-import-pro'
        ) );
    }
}
