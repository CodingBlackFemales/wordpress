<?php
/**
 * ChatGPT service class.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Services;

use Exception;
use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Modules\AI\ChatGPT_Summarizer;

/**
 * ChatGPT client class.
 *
 * @since 4.6.0
 */
class ChatGPT {
	/**
	 * Role key for system.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public static $role_system = 'system';

	/**
	 * Role key for assistant.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public static $role_assistant = 'assistant';

	/**
	 * Role key for user.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public static $role_user = 'user';

	/**
	 * Default OpenAI model.
	 *
	 * @since 4.13.0
	 *
	 * @see https://platform.openai.com/docs/models/gpt-3-5-turbo
	 *
	 * @var string
	 */
	private const MODEL = 'gpt-3.5-turbo';

	/**
	 * Max context window tokens. Default max context length tokens is 16,385 according to the default model
	 * gpt-3.5-turbo. Context window tokens is the total length of input tokens and generated tokens.
	 *
	 * A ChatGPT max tokens is the number of tokens it can handle as part of the same prompt. Suppose you type in
	 * a prompt that contains 50 tokens and receive a response with 150 tokens. In that case, the chat will
	 * consume a total of 200 tokens.
	 *
	 * @since 4.13.0
	 *
	 * @see https://platform.openai.com/docs/models/gpt-3-5-turbo
	 *
	 * @var int
	 */
	private const MODEL_MAX_CONTEXT_WINDOW_TOKENS = 16385;

	/**
	 * Default max tokens.
	 *
	 * The maximum number of tokens that can be generated in the chat completion.
	 *
	 * @since 4.13.0
	 *
	 * @see https://platform.openai.com/docs/models/gpt-3-5-turbo
	 *
	 * @var int
	 */
	private const MAX_TOKENS = 4096;

	/**
	 * Default temperature. The default temperature is 0.9 so that the model will be less repetitive, but not too random which results in response that does not make sense.
	 *
	 * Temperature is a number between 0 and 2 that controls randomness in boltzmann distribution. Lower temperature
	 * results in less random completions. As the temperature approaches zero, the model will become deterministic
	 * and repetitive. Higher temperature results in more random completions.
	 *
	 * @since 4.13.0
	 *
	 * @var float
	 */
	private const TEMPERATURE = 0.9;

	/**
	 * Default top p. The default top p is 0.7 so that the model will be less repetitive.
	 *
	 * Top p is a number between 0 and 1 that controls diversity via nucleus sampling: 0.5 means half of all
	 * likelihood-weighted options are considered.
	 *
	 * @since 4.13.0
	 *
	 * @var float
	 */
	private const TOP_P = 0.7;

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
	 * A collection of system, user and ChatGPT response messages.
	 *
	 * A collection of chat message objects. ChatGPT needs the history of the
	 * conversation to generate a response that understands the context of a conversation.
	 * This property only accept objects which have roles supported by ChatGPT.
	 *
	 * @since 4.13.0
	 *
	 * @var Chat_Message[]
	 */
	private array $messages = [];

	/**
	 * Instruction for the ChatGPT.
	 *
	 * System message used to set the initial instruction for the ChatGPT. ChatGPT accepts custom system message
	 * or instruction that can direct the way ChatGPT generates response.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private string $instruction = '';

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param string $api_key OpenAI API key.
	 *
	 * @return void
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Returns API key.
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
				'timeout' => 2 * MINUTE_IN_SECONDS,
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
					$error_message .= '. ' . esc_html__( 'The request has timed out. Please try it again with lower number of items. If the issue persists, please check your website server or Open AI service status.', 'learndash' );
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
	 * @param string               $command Command for the request.
	 * @param array<string, mixed> $args    Additional arguments if any.
	 *
	 * @return string
	 */
	public function send_command( $command, array $args = [] ): string {
		$this->construct_messages_from_command( $command );

		$args = wp_parse_args(
			$args,
			[
				'role' => self::$role_user,
			]
		);

		/**
		 * Filters the data to send to ChatGPT API.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, mixed> $data    Data to send to ChatGPT API.
		 * @param string               $command Command for the request.
		 * @param array<string, mixed> $args    Additional arguments if any.
		 *
		 * @return array<string, mixed>
		 */
		$data = apply_filters(
			'learndash_service_chatgpt_send_command_data',
			[
				'model'       => self::MODEL,
				'messages'    => $this->map_messages_to_request_format(),
				'max_tokens'  => self::MAX_TOKENS,
				'temperature' => self::TEMPERATURE,
				'top_p'       => self::TOP_P,
			],
			$command,
			$args
		);

		$response = $this->request(
			'POST',
			$data
		);

		$response_data = json_decode( $response, true );

		return is_array( $response_data )
			   && isset( $response_data['choices'][0]['message']['content'] )
			? $response_data['choices'][0]['message']['content']
			: '';
	}

	/**
	 * Adds a new chat message object into messages property.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message $message Chat message object.
	 *
	 * @return void
	 */
	public function add_message( Chat_Message $message ): void {
		if ( empty( $this->messages ) ) {
			$this->messages = [
				new Chat_Message(
					$this->get_instruction(),
					self::$role_system
				),
			];
		}

		$this->messages = array_merge( $this->get_messages(), [ $message ] );
	}

	/**
	 * Sets messages property.
	 *
	 * It can be used to restore contextual messages from a saved state or a session.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message[] $messages Messages to set.
	 *
	 * @return void
	 */
	public function set_messages( array $messages ): void {
		$system_message = [
			new Chat_Message(
				$this->get_instruction(),
				self::$role_system
			),
		];

		$this->messages = array_merge( $system_message, $messages );
	}

	/**
	 * Returns messages property.
	 *
	 * @since 4.13.0
	 *
	 * @return Chat_Message[]
	 */
	public function get_messages(): array {
		return $this->messages;
	}

	/**
	 * Sets instruction.
	 *
	 * @since 4.13.0
	 *
	 * @param string $instruction Instruction for the ChatGPT.
	 *
	 * @return void
	 */
	public function set_instruction( string $instruction ): void {
		$this->instruction = $instruction;
	}

	/**
	 * Returns ChatGPT instruction.
	 *
	 * It returns default generic instruction if the instruction is not set.
	 *
	 * @since 4.13.0
	 *
	 * @return string
	 */
	public function get_instruction(): string {
		return ! empty( $this->instruction )
			? $this->instruction
			: __( 'You are a helpful assistant.', 'learndash' );
	}

	/**
	 * Returns model max context window tokens.
	 *
	 * @since 4.13.0
	 *
	 * @return int
	 */
	public static function get_model_max_context_window_tokens(): int {
		/**
		 * Filters the maximum context window tokens for ChatGPT model.
		 *
		 * @since 4.13.0
		 *
		 * @param int $max_context_window_tokens Maximum context window tokens for a ChatGPT model.
		 *
		 * @return int Maximum context window tokens for a ChatGPT model.
		 */
		return apply_filters(
			'learndash_service_chatgpt_model_max_context_window_tokens',
			self::MODEL_MAX_CONTEXT_WINDOW_TOKENS
		);
	}

	/**
	 * Maps chat message objects into chatgpt messages array.
	 *
	 * @since 4.13.0
	 *
	 * @return array<array{
	 *     content: string,
	 *     role: string
	 * }> ChatGPT messages array.
	 */
	private function map_messages_to_request_format(): array {
		$chatgpt_messages = [];

		foreach ( $this->messages as $message ) {
			$chatgpt_messages[] = [
				'content' => $message->content,
				'role'    => $message->role,
			];
		}

		return $chatgpt_messages;
	}

	/**
	 * Constructs messages for the request from user command.
	 *
	 * @since 4.13.0
	 *
	 * @param string $command User command.
	 *
	 * @return void
	 */
	private function construct_messages_from_command( string $command ): void {
		$chatgpt_summarizer = new ChatGPT_Summarizer( $this->get_messages() );

		if ( $chatgpt_summarizer->messages_need_summarization() ) {
			$summary = $chatgpt_summarizer->summarize_messages();

			if ( ! empty( $summary ) ) {
				// Re-construct the messages to include the summary.
				$messages = array_merge(
					[ new Chat_Message( $summary, self::$role_user ) ],
					$chatgpt_summarizer->get_preserved_messages(),
				);
				$this->set_messages( $messages );
			}
		}

		$this->add_message( new Chat_Message( $command, self::$role_user ) );
	}
}
