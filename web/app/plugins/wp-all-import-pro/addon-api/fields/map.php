<?php

namespace Wpai\AddonAPI;

abstract class PMXI_Addon_Map_Provider_Field extends PMXI_Addon_Field {
    abstract public function getGeocodeProviderName();

    abstract public function getGeoCodeProviderSlug();

    abstract public function getLanguage($custom_language = null);

    abstract public function getRegion($custom_region = null);

    abstract public function getApiKey( $use_custom = false, $custom_key = null );

    abstract public function getLocationData( $logger, $apiKey, $address = null, $lat = null, $lng = null );

    /**
     * @param mixed $value
     * @param "string"|"array"|"address" $format
     *
     * @return mixed
     */
    abstract public function formatValue( $value, string $format );
}

class PMXI_Addon_Map_Field extends PMXI_Addon_Map_Provider_Field {

    private $baseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
    private $table = 'pmxi_geocoding';

    public function getGeocodeProviderName() {
        return 'Google Maps';
    }

    public function getGeoCodeProviderSlug() {
        return 'google_maps';
    }

    public function getLanguage( $custom_language = null ) {
        return $custom_language ?? '';
    }

    public function getRegion( $custom_region = null ) {
        return $custom_region ?? '';
    }

    public function getApiKey( $use_custom = false, $custom_key = null ) {
        return $use_custom ? $custom_key : ''; // It's up to the add-on to provide the API key
    }

    private function buildApiUrl( $apiKey, $address = null, $lat = null, $lng = null, $params = [] ) {
        $queryArgs = [ 'key' => $apiKey ];

        if ( $address ) {
            $queryArgs['address'] = urlencode( $address );
        } elseif ( $lat && $lng ) {
            $queryArgs['latlng'] = "{$lat},{$lng}";
        } else {
            return null;
        }

        $params = array_filter( $params );
        $queryArgs = array_merge( $queryArgs, $params );

        return add_query_arg( $queryArgs, $this->baseUrl );
    }

    public function getCachedData( $address = null, $lat = null, $lng = null ) {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;

        if ( $address ) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE address = %s LIMIT 1",
                $address
            );
        } else if ( $lat && $lng ) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE latitude = %s AND longitude = %s LIMIT 1",
                $lat,
                $lng
            );
        } else {
            return null;
        }

        $row = $wpdb->get_row( $query );

        return $row ? json_decode( $row->raw_data, true ) : null;
    }

    public function cacheData( $data, $address = null, $lat = null, $lng = null ) {
        global $wpdb;
        $table = $wpdb->prefix . $this->table;

        if ( ! isset( $address ) && ! isset( $lat ) && ! isset( $lng ) ) {
            return false;
        }

        $wpdb->insert( $table, [
            'address'    => $address,
            'latitude'   => $lat,
            'longitude'  => $lng,
            'raw_data'   => json_encode( $data ),
            'provider'   => $this->getGeoCodeProviderSlug(),
            'created_at' => date( 'Y-m-d H:i:s' )
        ] );

        return true;
    }

    public function getLocationData( $logger, $apiKey, $address = null, $lat = null, $lng = null, $params = [] ) {
        $url = $this->buildApiUrl( $apiKey, $address, $lat, $lng, $params );
        $lookup_value = $address ?? $lat . ',' . $lng;

        if ( ! $url ) {
            $logger and call_user_func( $logger, '- <b>WARNING</b>: You must provide either an address or latitude and longitude.' );
            return null;
        }

        // Check if data is already cached
        $cachedData = $this->getCachedData( $address, $lat, $lng );

        if ( $cachedData ) {
            $logger and call_user_func( $logger, '- Google Maps Geocoding: Using cached data for ' . $lookup_value );
            return $cachedData['results'][0];
        }

		if(empty($apiKey)){
			$logger and call_user_func( $logger, '- <b>WARNING</b>: You must provide a Google Maps Geocoding API Key.' );
			return null;
		}

        // Fetch data from API
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            $logger and call_user_func( $logger, '- <b>WARNING</b>: Google Maps Geocoding: ' . $lookup_value . ' - ' . $response->get_error_message() );

            return null;
        }

        $logger and call_user_func( $logger, '- Google Maps Geocoding: Searching geolocation data for: ' . $lookup_value );

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data['status'] !== 'OK' ) {
            $message = isset($data['error_message']) ? ' - ' . $data['error_message'] : '';
            $logger and call_user_func( $logger, '- <b>WARNING</b>: Error fetching geocoding data for: ' . $lookup_value . ' - ' . $data['status'] . $message );

            return null;
        }

        $this->cacheData( $data, $address, $lat, $lng );

        $logger and call_user_func( $logger, '- Google Maps Geocoding: Found geolocation data for ' . $lookup_value );

        return $data['results'][0];
    }

    public function formatValue( $value, $format = null ) {
        if ( ! $value ) {
            return null;
        }

        $geometry = $value['geometry']['location'];

        if ( $format === 'string' ) {
            return $geometry['lat'] . ',' . $geometry['lng'];
        } elseif ( $format === 'address' ) {
            return $value['formatted_address'];
        } elseif ( $format === 'array' ) {
            $geometry['address'] = $value['formatted_address'];

            return $geometry;
        } else {
            return $value;
        }
    }

    public function beforeImport( $postId, $value, $data, $logger, $rawData ) {

		// Short circuit if we don't have the data to process further.
		if(('by_address' === $value['search_logic'] && empty($value['address'])) || ('by_coordinates' === $value['search_logic'] && (empty($value['lat']) || empty($value['lng'])))){
			return '';
		}

        $search_by    = $value['search_logic'] ?? 'by_address';
        $value_format = $this->args['value_format'] ?? null;
        $api_key      = $this->getApiKey( $value['use_custom_api_key'] ?? false, $value['custom_api_key'] ?? null );
        $region       = $this->getRegion( $value['custom_region'] ?? null );
        $language     = $this->getLanguage( $value['custom_language'] ?? null );

        $params = [
            'region'   => $region,
            'language' => $language
        ];

        if ( $search_by === 'manual' ) {
	        $geo_data = [
		        'geometry' => [
			        'location' => [
				        'lat' => $value['manual_lat'] ?? '',
				        'lng' => $value['manual_lng'] ?? ''
			        ]
		        ],
		        'formatted_address' => $value['manual_location'] ?? ''
	        ];

            return $this->formatValue($geo_data, $value_format);
        }

        switch ( $search_by ) {
            case 'by_address':
                $location_data = $this->getLocationData( $logger, $api_key, $value['address'], null, null, $params );
                break;
            case 'by_coordinates':
                $location_data = $this->getLocationData( $logger, $api_key, null, $value['lat'], $value['lng'], $params );
                break;
            default:
                return $value;
        }

        return $this->formatValue( $location_data, $value_format );
    }
}
