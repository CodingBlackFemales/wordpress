<?php

namespace Wpai\AddonAPI;

/**
 * Takes a value and passes it to a callback, then returns the value.
 *
 * @param mixed $value
 * @param callable $callback
 *
 * @return mixed
 */
function tap( $value, $callback ) {
    $callback( $value );

    return $value;
}

/**
 * Takes a value and passes it to a list of callbacks, then returns the value.
 *
 * @param mixed $value
 * @param callable[] $callbacks
 *
 * @return mixed
 */
function pipe( $value, $callbacks ) {
    return array_reduce( $callbacks, function ( $value, $callback ) {
        return $callback( $value );
    }, $value );
}

/**
 * Renders a view.
 *
 * @param string $viewPath
 * @param array $data
 * @param string|null $defaultView
 * @param bool $echo
 * @param string|null $path
 *
 * @return string|null
 * @throws \Exception
 */
function view(
    string $viewPath,
    array $data,
    $defaultView = null,
    $echo = true,
    $path = null
) {
    $path            = $path ?: WP_ALL_IMPORT_ROOT_DIR . '/addon-api' . '/templates/';
    $filePath        = $path . $viewPath . '.php';
    $defaultFilePath = $path . $defaultView . '.php';

    extract( $data );

    $view = $filePath;

    if ( ! is_file( $view ) ) {
        if ( $defaultView ) {
            $view = $defaultFilePath;
        } else {
            throw new \Exception( "The requested template file $filePath was not found." );
        }
    }

    if ( $echo ) {
        include $view;
    } else {
        ob_start();
        include $view;

        return ob_get_clean();
    }
}

function searchExistingInAttachedFile( $url, $post_id, $file_type, $importData, $logger ) {
    $articleData = $importData['articleData'];
    $image_name  = basename( $url );

    $current_xml_node = false;
    if ( ! empty( $importData['current_xml_node'] ) ) {
        $current_xml_node = $importData['current_xml_node'];
    }

    $import_id = false;
    if ( ! empty( $importData['import'] ) ) {
        $import_id = $importData['import']->id;
    }

    $uploads   = wp_upload_dir();
    $uploads   = apply_filters( 'wp_all_import_images_uploads_dir', $uploads, $articleData, $current_xml_node, $import_id );
    $targetDir = $uploads['path'];

    if ( empty( $attch ) ) {
        $logger and call_user_func( $logger, sprintf( __( '- Searching for existing image `%s` by `_wp_attached_file` `%s`...', 'wp-all-import-pro' ), $url, $image_name ) );
        $attch = wp_all_import_get_image_from_gallery( $image_name, $targetDir, $file_type );
    }

    if ( ! empty( $attch ) ) {
        $logger and call_user_func( $logger, sprintf( __( '- Existing image was found by `_wp_attached_file` ...', 'wp-all-import-pro' ), $url ) );
        $imageRecord = new \PMXI_Image_Record();
        $imageRecord->getBy( [
            'attachment_id' => $attch->ID
        ] );
        $imageRecord->isEmpty() and $imageRecord->set( [
            'attachment_id'  => $attch->ID,
            'image_url'      => $url,
            'image_filename' => $image_name
        ] )->insert();
        // Attach media to current post if it's currently unattached.
        if ( empty( $attch->post_parent ) ) {
            wp_update_post(
                array(
                    'ID'          => $attch->ID,
                    'post_parent' => $post_id
                )
            );
        }

        return $attch->ID;
    }
}

function searchExistingImage( $url, $post_id, $mode, $file_type, $import_data, $logger ) {
    $imageList = new \PMXI_Image_List();

    switch ( $mode ) {
        case 'by_url':
            // trying to find existing image in images table
            $logger and call_user_func( $logger, sprintf( __( '- Searching for existing image `%s` by URL...', 'wp-all-import-pro' ), rawurldecode( $url ) ) );
            $attch = $imageList->getExistingImageByUrl( $url );

            if ( $attch ) {
                $logger and call_user_func( $logger, sprintf( __( 'Existing image was found by URL `%s`...', 'wp-all-import-pro' ), $url ) );

                return $attch->ID;
            }

            break;
        default:
            $logger and call_user_func( $logger, sprintf( __( '- Searching for existing image `%s` by Filename...', 'wp-all-import-pro' ), rawurldecode( $url ) ) );
            $attch = $imageList->getExistingImageByFilename( basename( $url ) );

            if ( $attch ) {
                $logger and call_user_func( $logger, sprintf( __( 'Existing image was found by Filename `%s`...', 'wp-all-import-pro' ), basename( $url ) ) );

                return $attch->ID;
            }

            $attch = searchExistingInAttachedFile( $url, $post_id, $file_type, $import_data, $logger );

            if ( $attch ) {
                return $attch->ID;
            }
    }

    return null;
}

/**
 * Checks if an array is associative.
 *
 * @param array $arr
 *
 * @return bool
 */
function isAssocArray( array $arr ) {
    if ( $arr === [] ) {
        return true;
    }

    return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
}

/**
 * Gets the current value of a field for a post.
 *
 * @param string $post_type
 * @param int $post_id
 * @param string $key
 * @param mixed $default
 *
 * @return mixed
 */
function currentValue( $post_type, $post_id, $key, $default ) {
    switch ( $post_type ) {
        case 'import_users':
        case 'shop_customer':
            $value = get_user_meta( $post_id, $key, true );
            break;
        case 'taxonomies':
            $value = get_term_meta( $post_id, $key, true );
            break;
        default:
            $value = get_post_meta( $post_id, $key, true );
            break;
    }

    return $value ?: $default;
}

/**
 * @param array $field
 *
 * @return string
 */
function getFieldClass( $field ) {
    $class = 'Wpai\AddonAPI\PMXI_Addon_' . ucfirst( $field['type'] ) . '_Field';

    return class_exists( $class ) ? $class : PMXI_Addon_Field::class;
}

// Class Helpers

trait Singleton {
    /** @var self|null */
    private static $instance = null;

    /**
     * @return self
     */
    final public static function getInstance(): self {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Prevent cloning of the instance
    public function __clone() {
    }

    // Prevent deserialization of the instance
    public function __wakeup() {
    }
}

trait Updatable {
    public ?PMXI_Addon_Updater $updater;

    public function initEed() {
        $api_url     = $this->getApiUrl();
        $plugin_file = $this->getPluginFile();

        if ( empty( $api_url ) ) {
            return;
        }

        $this->updater = new PMXI_Addon_Updater(
            $api_url,
            $plugin_file,
            [
                'version'   => $this->version,       // current version number
                'license'   => false,                // license key (used get_option above to retrieve from DB)
                'item_name' => $this->getEddName(),  // name of this plugin
                'author'    => $this->getEddAuthor() // author of this plugin
            ]
        );
    }

    public function getEddName() {
        return $this->name();
    }

    public function getEddAuthor() {
        return 'Soflyy';
    }

    public function getPluginFile() {
        return $this->rootDir . '/plugin.php';
    }

    public function getApiUrl() {
        $options = get_option( 'PMXI_Plugin_Options' );
        $api_url = null;

        if ( ! empty( $options['info_api_url_new'] ) ) {
            $api_url = $options['info_api_url_new'];
        } elseif ( ! empty( $options['info_api_url'] ) ) {
            $api_url = $options['info_api_url'];
        }

        return $api_url;
    }
}
