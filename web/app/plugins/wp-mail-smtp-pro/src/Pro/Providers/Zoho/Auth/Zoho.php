<?php

namespace WPMailSMTP\Pro\Providers\Zoho\Auth;

use WPMailSMTP\Vendor\League\OAuth2\Client\Provider\AbstractProvider;
use WPMailSMTP\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use WPMailSMTP\Vendor\League\OAuth2\Client\Token\AccessToken;
use WPMailSMTP\Vendor\Psr\Http\Message\ResponseInterface;

/**
 * Class Zoho - OAuth2 client provider for Zoho Mail.
 *
 * @since 2.3.0
 *
 * @link https://github.com/shahariaazam/zoho-oauth2/blob/master/src/Provider/Zoho.php
 */
class Zoho extends AbstractProvider {

	/**
	 * Available account endpoints.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	protected $account_endpoints = [
		'com'    => 'https://accounts.zoho.',
		'eu'     => 'https://accounts.zoho.',
		'in'     => 'https://accounts.zoho.',
		'com.cn' => 'https://accounts.zoho.',
		'com.au' => 'https://accounts.zoho.',
		'jp'     => 'https://accounts.zoho.',
		'ca'     => 'https://accounts.zohocloud.',
	];

	/**
	 * Available Zoho Mail API endpoints.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	public static $mail_endpoints = [
		'com'    => 'https://mail.zoho.',
		'eu'     => 'https://mail.zoho.',
		'in'     => 'https://mail.zoho.',
		'com.cn' => 'https://mail.zoho.',
		'com.au' => 'https://mail.zoho.',
		'jp'     => 'https://mail.zoho.',
		'ca'     => 'https://mail.zohocloud.',
	];

	/**
	 * The root endpoint of the zoho authentication URL.
	 *
	 * The domain extension will be added later since the Zoho Mail API is divided by regions/domains.
	 *
	 * @since 4.2.0 Will be defined in the constructor based on the domain.
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * The access type attribute for the Zoho OAuth2 request.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	protected $access_type = 'offline';

	/**
	 * The Zoho API domain for particular Zoho user account.
	 *
	 * Currently available option: 'com', 'eu', 'in', 'com.cn', 'com.au', 'ca'.
	 *
	 * @since 4.2.0 Added the CA (Canada) domain.
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * The default Zoho Mail scopes.
	 *
	 * @since 2.3.0
	 *
	 * @var array
	 */
	public $default_scopes = [
		'ZohoMail.messages.CREATE',
		'ZohoMail.accounts.READ',
	];

	/**
	 * Zoho constructor.
	 *
	 * @since 2.3.0
	 *
	 * @param array $options       The provider options.
	 * @param array $collaborators The array of collaborators that may be used to
	 *                             override this provider's default behavior. Collaborators include
	 *                             `grantFactory`, `requestFactory`, and `httpClient`.
	 *
	 * @throws \InvalidArgumentException If the required options are not present.
	 */
	public function __construct( array $options = [], array $collaborators = [] ) {

		parent::__construct( $options, $collaborators );

		foreach ( [ 'domain', 'clientId', 'clientSecret', 'redirectUri' ] as $key ) {
			if ( ! isset( $options[ $key ] ) ) {
				throw new \InvalidArgumentException( $key . ' is missing' );
			}
		}

		// Check if the domain is set. Default to 'com' if not set.
		if ( ! array_key_exists( $options['domain'], $this->account_endpoints ) ) {
			$options['domain'] = 'com';
		}

		// Set the endpoint based on the domain.
		$this->endpoint = $this->account_endpoints[ $options['domain'] ];
	}

	/**
	 * Returns the base URL for authorizing a client.
	 *
	 * Eg. https://accounts.zoho.eu/oauth/v2/auth
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl() {

		return $this->endpoint . $this->domain . '/oauth/v2/auth';
	}

	/**
	 * Returns the base URL for requesting an access token.
	 *
	 * Eg. https://accounts.zoho.eu/oauth/v2/token
	 *
	 * @since 2.3.0
	 *
	 * @param array $params The token query parameters.
	 *
	 * @return string
	 */
	public function getBaseAccessTokenUrl( array $params ) {

		return $this->endpoint . $this->domain . '/oauth/v2/token';
	}

	/**
	 * Returns the URL for requesting the resource owner's details.
	 *
	 * @since 2.3.0
	 * @since 4.2.0 Added the region specific mail endpoint.
	 *
	 * @param AccessToken $token The access token.
	 *
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl( AccessToken $token ) {

		// Use region specific mail API endpoint.
		$api_root_url = self::$mail_endpoints[ $this->domain ];

		// Set up the URL.
		return sprintf(
			'%s%s/api/accounts',
			untrailingslashit( $api_root_url ), // Example: 'https://mail.zoho.'.
			untrailingslashit( $this->domain )  // Example: 'eu'.
		);
	}

	/**
	 * Get the Authorization header.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed|null $token The access token.
	 *
	 * @return array
	 */
	protected function getAuthorizationHeaders( $token = null ) {

		return [
			'Authorization' => 'Zoho-oauthtoken ' . $token,
		];
	}

	/**
	 * Get the authorization parameters.
	 *
	 * @since 2.3.0
	 *
	 * @param array $options The client provider options.
	 *
	 * @return array
	 */
	protected function getAuthorizationParameters( array $options ) {

		if ( empty( $options['state'] ) ) {
			$options['state'] = $this->state;
		}

		if ( empty( $options['scope'] ) ) {
			$options['scope'] = $this->getDefaultScopes();
		}

		$options += [
			'response_type' => 'code',
			'prompt'        => 'consent',
		];

		if ( is_array( $options['scope'] ) ) {
			$separator        = $this->getScopeSeparator();
			$options['scope'] = implode( $separator, $options['scope'] );
		}

		// Store the state as it may need to be accessed later on.
		$this->state = $options['state'];

		// Business code layer might set a different redirect_uri parameter depending on the context, leave it as-is.
		if ( ! isset( $options['redirect_uri'] ) ) {
			$options['redirect_uri'] = $this->redirectUri; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		$options['client_id'] = $this->clientId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if (
			isset( $this->access_type ) &&
			in_array( $this->access_type, [ 'offline', 'online' ], true )
		) {
			$options['access_type'] = $this->access_type;
		}

		return $options;
	}

	/**
	 * Returns the default scopes used by this provider.
	 *
	 * This should only be the scopes that are required to request the details
	 * of the resource owner, rather than all the available scopes.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	protected function getDefaultScopes() {

		return $this->default_scopes;
	}

	/**
	 * Checking response to see if provider returned any error.
	 *
	 * @since 2.3.0
	 *
	 * @param ResponseInterface $response The response object.
	 * @param array|string      $data     Parsed response data.
	 *
	 * @return void
	 *
	 * @throws IdentityProviderException If the response has an error.
	 */
	protected function checkResponse( ResponseInterface $response, $data ) {

		// A non-JSON body is parsed to a string; there is nothing to inspect.
		if ( ! is_array( $data ) ) {
			return;
		}

		// OAuth token endpoint errors use a top-level "error" key.
		if ( array_key_exists( 'error', $data ) ) {
			throw new IdentityProviderException( $data['error'], $response->getStatusCode(), $response ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// The Zoho Mail API reports the real status in the body's `status.code`,
		// often alongside an HTTP 200.
		if ( isset( $data['status']['code'] ) && (int) $data['status']['code'] >= 400 ) {
			$message = '';

			if ( isset( $data['data']['moreInfo'] ) && is_string( $data['data']['moreInfo'] ) ) {
				$message = $data['data']['moreInfo'];
			} elseif ( isset( $data['status']['description'] ) && is_string( $data['status']['description'] ) ) {
				$message = $data['status']['description'];
			}

			throw new IdentityProviderException( $message, (int) $data['status']['code'], $response ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Generates a resource owner object from a successful resource owner
	 * details request.
	 *
	 * @since 2.3.0
	 *
	 * @param array       $response The response data.
	 * @param AccessToken $token    The access token.
	 *
	 * @return ZohoUser
	 */
	protected function createResourceOwner( array $response, AccessToken $token ) {

		return new ZohoUser( $response );
	}
}
