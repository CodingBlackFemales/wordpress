<?php
/**
 * ChatGPT service class.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Services;

use Exception;

/**
 * ChatGPT client class.
 *
 * @since 4.6.0
 */
class ChatGPT {
	/**
	 * OpenAI API key.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * ChatGPT API url.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	private $api_url = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Constructor.
	 *
	 * @param string $api_key    OpenAI API key.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get API key.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/**
	 * Send request to ChatGPT API url.
	 *
	 * @since 4.6.0
	 *
	 * @throws Exception Throw exception with error message and response code if request failed for any reason.
	 *
	 * @param string               $method  Request method.
	 * @param array<string, mixed> $body    Request body.
	 * @param array<string, mixed> $headers Request headers.
	 * @param array<string, mixed> $args    Request args.
	 *
	 * @return string         Response.
	 */
	public function request( $method = 'GET', $body = [], $headers = [], $args = [] ): string {
		$body    = wp_parse_args( $body, [] );
		$api_key = $this->api_key;

		$headers = wp_parse_args(
			$headers,
			[
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			]
		);

		$args = wp_parse_args(
			$args,
			[
				'method'  => $method,
				'body'    => wp_json_encode( $body ),
				'headers' => $headers,
				'timeout' => 30,
			]
		);

		$response = wp_remote_request( $this->api_url, $args );

		unset( $api_key );
		unset( $headers );
		unset( $args );

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) || $code !== 200 ) {
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();

				if ( mb_stristr( $error_message, 'curl error 28' ) ) {
					$error_message .= '. ' . esc_html__( 'You might have entered too many lessons. Please try it again with maximum 20-30 lessons. If the issue persists, please check your server or Open AI service status.', 'learndash' );
				}
			} else {
				$body          = json_decode( $body, true );
				$error_message = is_array( $body ) && isset( $body['error']['message'] ) ? $body['error']['message'] : '';

				if ( empty( $error_message ) ) {
					$error_code = is_array( $body ) && isset( $body['error']['code'] ) ? $body['error']['code'] : '';

					switch ( $error_code ) {
						case 'invalid_api_key':
							$error_message = __( 'Invalid OpenAI API key. Please check your API key.', 'learndash' );
							break;

						default:
							$error_message = wp_sprintf(
								// translators: Error code.
								__( 'Code: %s', 'learndash' ),
								$error_code
							);
							break;
					}
				}
			}

			$code = is_int( $code ) ? $code : 500;

			throw new Exception(
				sprintf(
					// translators: Error message.
					esc_html__( 'Error: %s', 'learndash' ),
					$error_message
				),
				$code
			);
		}

		return $body;
	}

	/**
	 * Send request.
	 *
	 * @since 4.6.0
	 *
	 * @throws Exception Throw exception with error message and response code if request failed for any reason.
	 *
	 * @param string $command Command for the request.
	 *
	 * @return string
	 */
	public function send_command( $command ): string {
		// Extract values from the command.
		preg_match( "/Outline (\d+) lessons for a '(.+)' course on the topic of '(.+)'./", $command, $matches );
		$lesson_count = $matches[1];
		$course_name  = $matches[2];
		$course_idea  = $matches[3];

		$prompt = "Create a numbered bullet list with {$lesson_count} lesson titles for a '{$course_name}' course on the topic of '{$course_idea}'.";

		$messages = [
			[
				'role'    => 'system',
				'content' => 'You are a helpful assistant.',
			],
			[
				'role'    => 'user',
				'content' => $prompt,
			],
		];

		/**
		 * Filters the data to send to ChatGPT API.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, mixed> $data    Data to send to ChatGPT API.
		 * @param string               $command Command for the request.
		 *
		 * @return array<string, mixed>
		 */
		$data = apply_filters(
			'learndash_service_chatgpt_send_command_data',
			[
				'model'       => 'gpt-3.5-turbo',
				'messages'    => $messages,
				'max_tokens'  => 3000,
				'temperature' => 0.9,
				'top_p'       => 0.7,
			],
			$command
		);

		$response = $this->request(
			'POST',
			$data
		);

		$response_data = json_decode( $response, true );
		$response_text = is_array( $response_data ) && isset( $response_data['choices'][0]['message']['content'] ) ? $response_data['choices'][0]['message']['content'] : '';

		return $response_text;
	}
}
