<?php

namespace WPMailSMTP\Pro\ProductApi;

/**
 * Product API authentication credentials.
 *
 * @since 4.4.0
 */
class Credentials {

	/**
	 * Site ID.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	private $site_id;

	/**
	 * Public key.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private $public_key;

	/**
	 * Token.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param int    $site_id    Site ID.
	 * @param string $public_key Public key.
	 * @param string $token      Token.
	 */
	public function __construct( $site_id = 0, $public_key = '', $token = '' ) {

		$this->site_id    = $site_id;
		$this->public_key = $public_key;
		$this->token      = $token;
	}

	/**
	 * Whether credentials are valid.
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_valid() {

		return ! empty( $this->site_id ) && ! empty( $this->public_key ) && ! empty( $this->token );
	}

	/**
	 * Get site ID.
	 *
	 * @since 4.4.0
	 *
	 * @return int
	 */
	public function get_site_id() {

		return $this->site_id;
	}

	/**
	 * Get public key.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_public_key() {

		return $this->public_key;
	}

	/**
	 * Get token.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_token() {

		return $this->token;
	}

	/**
	 * Get credentials as array.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	public function to_array() {

		return [
			'site_id'    => $this->site_id,
			'public_key' => $this->public_key,
			'token'      => $this->token,
		];
	}

	/**
	 * Create credentials from array.
	 *
	 * @since 4.4.0
	 *
	 * @param array $data Credentials data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ) {

		return new self(
			! empty( $data['site_id'] ) ? (int) $data['site_id'] : '',
			! empty( $data['public_key'] ) ? $data['public_key'] : '',
			! empty( $data['token'] ) ? $data['token'] : ''
		);
	}
}
