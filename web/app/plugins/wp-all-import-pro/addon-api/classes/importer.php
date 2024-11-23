<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Importer {

    public PMXI_Addon_Base $addon;

    public function __construct( $addon ) {
        $this->addon = $addon;
    }

    /**
     * Get the default options for the addon
     */
    public function defaults(
        string $type,
        string $subtype = null
    ) {
        $addon_options = [];
        $options       = [];

        $fields = $this->addon->fields( $type, $subtype );

        foreach ( $fields as $field ) {
            $addon_options[ $field['key'] ] = '';
        }

        $options[ $this->addon->slug ]                = $addon_options;
        $options[ $this->addon->slug . '_groups' ]    = [];
        $options[ $this->addon->slug . '_switchers' ] = [];
        $options[ $this->addon->slug . '_multiple' ]  = [];

        if ( ! isset( $options['update_addons'] ) ) {
            $options['update_addons'] = [];
        }

        $options['update_addons'][ $this->addon->slug ] = $this->addon->defaultUpdateOptions();

        return $options;
    }

    public function isFieldEmpty( $value ) {
        return $value == '' || $value == null || $value == [];
    }

    public function isRepeatable( $field ) {
        return $field['type'] === 'repeater' || ( $field['args']['is_cloneable'] ?? false );
    }

    /*
     * Return data for the given record index
     */
    public function unwrapValue( $field, $value, $index ) {
        if ( is_array( $value ) ) {
            if ( isAssocArray( $value ) ) {
                if ( empty( $value ) ) {
                    return null;
                }

                return $value[ $index ];
            }

            $unwrappedData = [];

            foreach ( $value as $key => $item ) {
                if ( $this->isRepeatable( $field ) && $key === "rows" ) {
                    $unwrappedData[ $key ] = [];
                    foreach ( $item as $rowIndex => $row ) {
                        $unwrappedData[ $key ][ $rowIndex ] = $this->unwrapValue( $field, $row, $index );
                    }
                } elseif ( is_array( $item ) ) {
                    if ( isset( $item[ $index ] ) ) {
                        $unwrappedData[ $key ] = $item[ $index ];
                    } else {
                        $unwrappedData[ $key ] = $this->unwrapValue( $field, $item, $index );
                    }
                } else {
                    $unwrappedData[ $key ] = $item;
                }
            }

            return $unwrappedData;
        } else {
            return $value;
        }
    }

    public function castValue( $field, $value, $post_id, $post_type ) {
        if ( isset( $this->addon->casts[ $field['type'] ] ) ) {
            $cast = new $this->addon->casts[ $field['type'] ];

            return $cast( $field, $value, $post_id, $post_type );
        }

        return $value;
    }

    public function getImportFields( $type, $subtype, $groups, $data ) {
        // Filter out fields that don't exist in the parsed data
        $fields = array_filter(
            $this->addon->fields( $type, $subtype ),
            fn( $field ) => isset( $data[ $field['key'] ] )
        );

        // Filter out fields from disabled groups
        $fields = array_filter(
            $fields,
            fn( $field ) => in_array( $field['group'], $groups )
        );

        return array_values( $fields );
    }

    /**
     * Simplify import data
     * TODO: Make this more functional
     *
     * @return array|null
     */
    public function simplify( array $importData, array $parsedData ) {
        $options = $importData['import']['options'];
        $type    = $options['custom_type'];
        $subtype = $options['taxonomy_type'];
        $view    = PMXI_Addon_View::create(
            $this->addon->slug,
            $type,
            $subtype
        );

        if ( ! $this->addon->isAvailableForType( $type, $options ) ) {
            return null;
        }

        if ( $importData['logger'] ) {
            call_user_func( $importData['logger'], __( sprintf( '<strong>%s:</strong>', strtoupper( $this->addon->name() ) ), 'wp-all-import-pro' ) );
        }

        if ( ! array_key_exists( $this->addon->slug, $options ) ) {
            call_user_func( $importData['logger'], __( 'No options found for this addon, skipping...', 'wp-all-import-pro' ) );

            return null;
        }

        $import_options = $options[ $this->addon->slug ];
        $switchers      = $options[ $this->addon->slug . '_switchers' ];
        $multiples      = $options[ $this->addon->slug . '_multiple' ];
        $groups         = $options[ $this->addon->slug . '_groups' ];

        if ( empty( $parsedData ) ) {
            return null;
        }

        $data    = [];
        $post_id = $importData['pid'];
        $index   = $importData['i'];

        $fields  = $this->getImportFields( $type, $subtype, $groups, $parsedData );

        foreach ( $fields as $field_index => $field ) {
            $field_slug  = $field['key'];
            $field_value = $this->unwrapValue( $field, $parsedData[ $field_slug ], $index );

            if ( ! $this->canUpdateField( $field, $options ) ) {
	            call_user_func( $importData['logger'], __( '- Field `'.$field['key'].'` skipped due to import settings.', 'wp-all-import-pro' ) );
				unset($fields[$field_index]);
                continue;
            }

            if (
                isset( $switchers[ $field_slug ] ) &&
                isset( $multiples[ $field_slug ] ) &&
                $switchers[ $field_slug ] == 'yes'
            ) {
                $field_value = $multiples[ $field_slug ];
            }

            // Handle nested switchers
            if ( $this->isRepeatable( $field ) && isset( $field_value['rows'] ) ) {
                foreach ( $field_value['rows'] as $rowIndex => $row ) {
                    foreach ( $field['subfields'] as $subfield ) {
                        $subfield_slug     = $subfield['key'];
                        $subfield_switcher = $switchers[ $field_slug ]['rows'][ $rowIndex ][ $subfield_slug ] ?? 'no';

                        if ( $subfield_switcher == 'yes' ) {
                            $field_value['rows'][ $rowIndex ][ $subfield_slug ] = $multiples[ $field_slug ]['rows'][ $rowIndex ][ $subfield_slug ] ?? 0;
                        }
                    }
                }
            }

            // If the field is empty and has a default value, use that instead
            if ( $this->isFieldEmpty( $field_value ) ) {
                if ( isset( $field['default'] ) ) {
                    $field_value = $field['default'];
                } else {
                    continue;
                }
            }

            // Apply before import filters
            $field_instance = PMXI_Addon_Field::from( $field, $view );

            $data[ $field_slug ] = $field_instance->beforeImport(
                $post_id,
                $field_value,
                $importData,
                $importData['logger'],
                $import_options[ $field_slug ] ?? []
            );

            // Cast the value to a new value if a cast class exists
            if ( $this->isRepeatable( $field ) ) {
                foreach ( $data[ $field_slug ] as $rowIndex => $row ) {
                    foreach ( $field['subfields'] as $subfield ) {
                        $subfield_slug = $subfield['key'];

                        if ( isset( $row[ $subfield_slug ] ) ) {
                            $data[ $field_slug ][ $rowIndex ][ $subfield_slug ] = $this->castValue(
                                $subfield,
                                $row[ $subfield_slug ],
                                $post_id,
                                $type
                            );
                        }
                    }
                }
            }

            $data[ $field_slug ] = $this->castValue( $field, $data[ $field_slug ], $post_id, $type );
            // --------------------

            // Apply mapping rules if they exist
            if ( ! empty( $import_options['mapping'][ $field_slug ] ) ) {
                $mapping_rules = json_decode( $import_options['mapping'][ $field_slug ], true );

                if ( ! empty( $mapping_rules ) and is_array( $mapping_rules ) ) {
                    foreach ( $mapping_rules as $rule_number => $map_to ) {
                        if ( isset( $map_to[ trim( $data[ $field_slug ] ) ] ) ) {
                            $data[ $field_slug ] = trim( $map_to[ trim( $data[ $field_slug ] ) ] );
                            break;
                        }
                    }
                }
            }
            // --------------------
        }

        // If no fields are found, skip the import
        if ( empty( $data ) ) {
            call_user_func( $importData['logger'], __( 'No options found for this addon, skipping...', 'wp-all-import-pro' ) );

            return null;
        }

        return [ $post_id, $fields, $data, $importData['import'], $importData['articleData'], $importData['logger'] ];
    }


	/**
	 * @param $field
	 * @param array $import_options
	 *
	 * @return bool
	 */
	public function canDeleteField( $field, $import_options ) {
		if ( $import_options['is_keep_former_posts'] === 'yes' ) {
			return false;
		}

		return $this->canUpdateField( $field, $import_options );
	}


	/**
	 * @param $field
	 * @param array $import_options
	 *
	 * @return bool
	 */
	public function canUpdateField( $field, $import_options ) {
		$rules = $import_options['update_addons'][ $this->addon->slug ] ?? null;

		// Support for old templates
		if ( ! isset( $rules ) ) {
			return true;
		}

		if ( $import_options['update_all_data'] === 'yes' ) {
			return true;
		}

		if ( ! $rules['is_update'] ) {
			return false;
		}

		if ( $rules['update_logic'] === "full_update" ) {
			return true;
		}

		if (
			$rules['update_logic'] === "only" &&
			! empty( $rules['fields_list'] ) &&
			is_array( $rules['fields_list'] ) &&
			in_array( $field['key'], $rules['fields_list'] )
		) {
			return true;
		}

		if (
			$rules['update_logic'] === "all_except" &&
			(
				empty( $rules['fields_list'] ) ||
				! in_array( $field['key'], $rules['fields_list'] )
			)
		) {
			return true;
		}

		return false;
	}

    public static function from( PMXI_Addon_Base $addon ) {
        return new static( $addon );
    }
}
