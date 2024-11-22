<?php

namespace Wpai\AddonAPI;

class PMXI_Addon_Parser {
    use Singleton;

    public PMXI_Addon_Base $addon;
    public array $options;
    public array $data = [];
    public array $defaults;

    public function __construct(
        $addon,
        $options,
        $defaults
    ) {
        $this->addon    = $addon;
        $this->options  = $options;
        $this->defaults = $defaults;
    }

    public function transformArray( $xml, $cxpath, $value ) {
        $data = [];
        foreach ( $value as $key => $v ) {
            if ( is_string( $v ) && $v != "" ) {
                $data[ $key ] = \XmlImportParser::factory( $xml, $cxpath, (string) $v, $file )->parse();
                $tmp_files[]  = $file;
            } elseif ( is_array( $v ) ) {
                $data[ $key ] = $this->transformArray( $xml, $cxpath, $v );
            }
        }

        return $data;
    }

    /**
     * Parse xpath variables to the specified variable in the import file if needed
     * Extracted from https://github.com/soflyy/wp-all-import-rapid-addon/blob/master/rapid-addon.php#L985
     */
    public function transform() {
        extract( $this->options );

        $data   = [];
        $slug   = $this->addon->slug;
        $values = $import->options[ $slug ] ?? [];

        if ( empty( $values ) ) {
            return $this;
        }

        $cxpath    = $xpath_prefix . $import->xpath;
        $tmp_files = [];

        foreach ( $this->defaults[ $slug ] as $option_name => $option_value ) {
            if ( isset( $values[ $option_name ] ) and $values[ $option_name ] != '' ) {
                $value = $values[ $option_name ];
                if ( $value == "xpath" ) {
                    if ( $values['xpaths'][ $option_name ] == "" ) {
                        $count and $data[ $option_name ] = array_fill( 0, $count, "" );
                    } else {
                        $data[ $option_name ] = \XmlImportParser::factory( $xml, $cxpath, (string) $values['xpaths'][ $option_name ], $file )->parse();
                        $tmp_files[]          = $file;
                    }
                } else if ( is_array( $value ) ) {
                    $data[ $option_name ] = $this->transformArray( $xml, $cxpath, $value );
                } else {
                    $data[ $option_name ] = \XmlImportParser::factory( $xml, $cxpath, (string) $value, $file )->parse();
                    $tmp_files[]          = $file;
                }
            } else {
                $data[ $option_name ] = array_fill( 0, $count, "" );
            }
        }

        foreach ( $tmp_files as $file ) {
            unlink( $file );
        }

        $this->data = $data;

        return $this;
    }

    public function toArray() {
        return $this->data;
    }

    public static function from( PMXI_Addon_Base $addon, array $data, array $defaults ) {
        $parser = new static( $addon, $data, $defaults );

        return $parser->transform()->toArray();
    }
}
