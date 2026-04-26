<?php
/**
 * Virtual Instructor chat session class file.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use Exception;
use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\App;
use LearnDash\Core\Models\Virtual_Instructor;
use LearnDash\Core\Modules\AI\Chat_Message;
use LearnDash\Core\Modules\AI\ChatGPT_Summarizer;
use LearnDash\Core\Services\ChatGPT;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Custom_Label;
use WP_Post;
use WP_User;

/**
 * Virtual Instructor chat session class.
 *
 * This class is responsible for managing a chat session with a virtual instructor. It makes it possible to have simultaneous chat sessions with different users.
 *
 * Example:
 *
 * // Create a new or get an existing chat session.
 * $session = Chat_Session::get_instance([
 *      'model_id'  => $model_id, // Virtual instructor post ID.
 *      'user_id'   => $user_id, // WP_User ID.
 *      'course_id' => $course_id, // LearnDash course ID.
 * ]);
 *
 * // Send a message to the virtual instructor, including getting a response and storing the session in database so that it can be retrieved later.
 * $response = $session->send( $message );
 *
 * @since 4.13.0
 *
 * @phpstan-type Chat_Session_Args array{
 *     model_id: int,
 *     user_id: int,
 *     course_id: int,
 *     messages?: array<array{
 *         content: string,
 *         role: string,
 *         is_error?: int
 *     }>,
 *     summarized_messages?: array<array{
 *         content: string,
 *         role: string,
 *         is_error?: int
 *     }>,
 *     learndash_version?: string
 * }
 */
class Chat_Session {
	/**
	 * Session storage key prefix.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private const SESSION_STORAGE_KEY_PREFIX = 'learndash_virtual_instructor_chat_session_';

	/**
	 * Default session storage period in seconds.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private const DEFAULT_SESSION_STORAGE_PERIOD = HOUR_IN_SECONDS;

	/**
	 * Maximum message length that user can send.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private const MAX_MESSAGE_LENGTH = 500;

	/**
	 * Session storage period in seconds.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private int $session_storage_period;

	/**
	 * ChatGPT service instance.
	 *
	 * @since 4.13.0
	 *
	 * @var ChatGPT
	 */
	private ChatGPT $chatgpt;

	/**
	 * Virtual Instructor model instance.
	 *
	 * @since 4.13.0
	 *
	 * @var Virtual_Instructor
	 */
	private Virtual_Instructor $model;

	/**
	 * User ID.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Course ID.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private int $course_id;

	/**
	 * Message objects in a chat session.
	 *
	 * Original user and AI assistant messages.
	 *
	 * @since 4.13.0
	 *
	 * @var Chat_Message[]
	 */
	private array $messages;

	/**
	 * Message objects for AI provider request.
	 *
	 * This is used to store the messages that are returned by AI provider. For example when the messages are too
	 * long and need to be summarized, we store the summarized messages here while keeping the original messages
	 * in $messages property.
	 *
	 * @since 4.13.0
	 *
	 * @var Chat_Message[]
	 */
	private array $summarized_messages;

	/**
	 * Transient key.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private string $transient_key;

	/**
	 * LearnDash version.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private string $learndash_version; // @phpstan-ignore-line -- Necessary to compare the version in the future when there is an update.

	/**
	 * Returns an existing chat session instance or create a new one.
	 *
	 * @since 4.13.0
	 *
	 * @throws Exception Can't retrieve chat session.
	 *
	 * @param Chat_Session_Args $args Chat session arguments.
	 *
	 * @return self
	 */
	public static function get_instance( array $args ): self { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- Docblock uses phpstan custom type.
		$transient_key     = self::get_transient_key( $args );
		$chat_session_args = get_transient( $transient_key );

		/**
		 * Chat session class arguments.
		 *
		 * @var Chat_Session_Args $args Chat session arguments.
		 */
		$args = $chat_session_args !== false && is_array( $chat_session_args )
			? $chat_session_args
			: $args;

		/**
		 * ChatGPT object instance.
		 *
		 * @var ChatGPT $chatgpt ChatGPT object instance.
		 */
		$chatgpt = App::make( ChatGPT::class );

		return new self( $args, $chatgpt );
	}

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 *
	 * @throws InvalidArgumentException If model post ID is invalid.
	 *
	 * @param Chat_Session_Args $args    Virtual Instructor model instance.
	 * @param ChatGPT           $chatgpt ChatGPT service instance.
	 */
	public function __construct( array $args, ChatGPT $chatgpt ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- Docblock uses phpstan custom type.
		$this->validate_args( $args );

		$transient_key = self::get_transient_key( $args );
		$post          = get_post( Cast::to_int( $args['model_id'] ) );

		// @phpstan-ignore-next-line -- $args has been validated in validate_args() method.
		$this->model               = Virtual_Instructor::create_from_post( $post );
		$this->course_id           = Cast::to_int( $args['course_id'] );
		$this->user_id             = Cast::to_int( $args['user_id'] );
		$this->learndash_version   = $args['learndash_version'] ?? LEARNDASH_VERSION;
		$this->chatgpt             = $chatgpt;
		$this->transient_key       = $transient_key;
		$this->messages            =
			isset( $args['messages'] )
				? $this->map_messages_arrays_to_objects( $args['messages'] )
				: [];
		$this->summarized_messages =
			isset( $args['summarized_messages'] )
				? $this->map_messages_arrays_to_objects( $args['summarized_messages'] )
				: [];

		$this->set_session_storage_period( self::DEFAULT_SESSION_STORAGE_PERIOD );
		$this->set_initial_message();
		$this->chatgpt->set_instruction( $this->model->get_custom_instruction() );
	}

	/**
	 * Returns maximum message length that user can send.
	 *
	 * @since 4.13.0
	 *
	 * @return int
	 */
	public static function get_max_message_length(): int {
		/**
		 * Filters maximum message length that user can send.
		 *
		 * @since 4.13.0
		 *
		 * @param int $max_message_length Maximum message length that user can send.
		 *
		 * @return int Maximum message length that user can send.
		 */
		return apply_filters(
			'learndash_module_ai_virtual_instructor_chat_session_max_message_length',
			self::MAX_MESSAGE_LENGTH
		);
	}

	/**
	 * Gets messages in a session.
	 *
	 * @since 4.13.0
	 *
	 * @return Chat_Message[]
	 */
	public function get_messages(): array {
		return $this->messages;
	}

	/**
	 * Sends message to AI provider from an active session.
	 *
	 * @since 4.13.0
	 *
	 * @throws Exception If message text is invalid.
	 *
	 * @param string $message Message to send.
	 *
	 * @return array{
	 *     success: bool,
	 *     message: string,
	 * }
	 */
	public function send_message( string $message ): array {
		$response = [
			'success' => true,
		];

		try {
			/**
			 * Filters message object before sending it to AI provider.
			 *
			 * @since 4.13.0
			 *
			 * @var Chat_Message $chat_message Chat message object.
			 *
			 * @param Chat_Message       $chat_message Chat message object.
			 * @param Virtual_Instructor $model        Virtual Instructor model instance.
			 *
			 * @return Chat_Message Chat message object.
			 */
			$chat_message = apply_filters(
				'learndash_module_ai_virtual_instructor_chat_session_message',
				new Chat_Message( $message, $this->chatgpt::$role_user ),
				$this->model
			);

			if ( ! $this->message_is_valid( $chat_message ) ) {
				throw new Exception( $this->model->get_error_message() );
			}

			$this->chatgpt->set_messages( $this->map_messages() );

			$this->add_message( $chat_message );

			$response_message = $this->chatgpt->send_command(
				$chat_message->content
			);

			$chat_message_response = new Chat_Message(
				$response_message,
				$this->chatgpt::$role_assistant
			);

			$this->add_message( $chat_message_response );

			$this->set_summarized_messages(
				array_merge(
					$this->chatgpt->get_messages(),
					[ $chat_message_response ]
				)
			);

			$response['message'] = $response_message;
		} catch ( Exception $e ) {
			$response['success'] = false;
			$response['message'] = $e->getMessage();
		}

		$this->store();

		return $response;
	}

	/**
	 * Creates and returns transient key from chat session arguments.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Session_Args $args Chat session arguments.
	 *
	 * @return string
	 */
	private static function get_transient_key( array $args ): string { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- Docblock uses phpstan custom type.
		return sprintf(
			self::SESSION_STORAGE_KEY_PREFIX . '%d_%d_%d',
			$args['user_id'],
			$args['model_id'],
			$args['course_id']
		);
	}

	/**
	 * Add a message to a session.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message $message Chat message object instance.
	 *
	 * @return void
	 */
	private function add_message( Chat_Message $message ): void {
		$this->messages[] = $message;
	}

	/**
	 * Stores chat session arguments in database so that it can be retrieved later.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	private function store(): void {
		set_transient(
			$this->transient_key,
			[
				'user_id'             => $this->user_id,
				'model_id'            => $this->model->get_id(),
				'course_id'           => $this->course_id,
				'messages'            => $this->map_messages_objects_to_arrays( $this->messages ),
				'summarized_messages' => $this->map_messages_objects_to_arrays( $this->summarized_messages ),
				'learndash_version'   => $this->learndash_version,
			],
			$this->session_storage_period
		);
	}

	/**
	 * Checks if message is valid.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message $message Chat message object.
	 *
	 * @return bool
	 */
	private function message_is_valid( Chat_Message $message ): bool {
		$is_valid = ! $this->model->message_contains_banned_words( $message->content )
			&& mb_strlen( $message->content ) <= self::get_max_message_length();

		/**
		 * Filters whether a message is valid.
		 *
		 * @since 4.13.0
		 *
		 * @param bool               $is_valid Whether a message object is valid.
		 * @param Chat_Message       $message  Message text string.
		 * @param Virtual_Instructor $model    Virtual Instructor model instance.
		 *
		 * @return bool Whether a message is valid.
		 */
		return apply_filters(
			'learndash_module_ai_virtual_instructor_chat_session_message_is_valid',
			$is_valid,
			$message,
			$this->model
		);
	}

	/**
	 * Sets session storage period.
	 *
	 * @since 4.13.0
	 *
	 * @param int $period Session storage period in seconds.
	 *
	 * @return void
	 */
	private function set_session_storage_period( int $period ): void {
		/**
		 * Filters session storage period.
		 *
		 * @since 4.13.0
		 *
		 * @param int  $period Session storage period in seconds.
		 *
		 * @return int Session storage period in seconds.
		 */
		$this->session_storage_period = apply_filters(
			'learndash_module_ai_virtual_instructor_chat_session_storage_period',
			$period
		);
	}

	/**
	 * Sets up initial message if there is no message in a session.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	private function set_initial_message(): void {
		if ( ! empty( $this->messages ) ) {
			return;
		}

		/**
		 * Filters initial message.
		 *
		 * @since 4.13.0
		 *
		 * @param string             $message Initial message.
		 * @param Virtual_Instructor $model   Virtual Instructor model instance.
		 *
		 * @return string Initial message.
		 */
		$message = apply_filters(
			'learndash_module_ai_virtual_instructor_chat_session_initial_message',
			sprintf(
				// translators: %1$s: Virtual instructor name, %2$s: virtual instructor label.
				__( 'Hello! My name is %1$s. I am your %2$s. How can I help you?', 'learndash' ),
				$this->model->get_name(),
				LearnDash_Custom_Label::label_to_lower( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR )
			),
			$this->model
		);

		$this->add_message(
			new Chat_Message(
				$message,
				ChatGPT::$role_assistant
			)
		);
	}

	/**
	 * Validates chat session arguments.
	 *
	 * @since 4.13.0
	 *
	 * @throws InvalidArgumentException If model post ID, user ID or course ID is invalid.
	 *
	 * @param Chat_Session_Args $args Chat session arguments.
	 *
	 * @return void
	 */
	private function validate_args( array $args ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- Docblock uses phpstan custom type.
		if ( ! get_post( Cast::to_int( $args['model_id'] ) ) instanceof WP_Post ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid model post ID.', 'learndash' ) );
		}

		if ( ! get_user_by( 'ID', Cast::to_int( $args['user_id'] ) ) instanceof WP_User ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid user ID.', 'learndash' ) );
		}

		if ( ! get_post( Cast::to_int( $args['course_id'] ) ) instanceof WP_Post ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid course ID.', 'learndash' ) );
		}
	}

	/**
	 * Maps messages to a format that can be used by AI provider.
	 *
	 * @since 4.13.0
	 *
	 * @return Chat_Message[]
	 */
	private function map_messages(): array {
		$messages        = [];
		$mapped_messages = ! empty( $this->summarized_messages )
			? $this->summarized_messages
			: $this->messages;

		foreach ( $mapped_messages as $message ) {
			if (
				$message->is_error
				|| $message->role === $this->chatgpt::$role_system
			) {
				continue;
			}

			$messages[] = $message;
		}

		return $messages;
	}

	/**
	 * Sets summarized messages.
	 *
	 * Summarized messages are messages that contains summarized version of the original $messages property.
	 * We use this property for the request if it's not empty.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message[] $summarized_messages Summarized messages.
	 *
	 * @return void
	 */
	private function set_summarized_messages( array $summarized_messages ): void {
		$this->summarized_messages = $summarized_messages;
	}

	/**
	 * Maps messages arrays to Chat_Message objects.
	 *
	 * @since 4.13.0
	 *
	 * @param array<array{content: string, role: string, is_error?: int}> $messages Chat message array.
	 *
	 * @return Chat_Message[]
	 */
	private function map_messages_arrays_to_objects( array $messages ): array {
		return array_map(
			function ( array $message_args ): Chat_Message {
				$message = new Chat_Message(
					$message_args['content'],
					$message_args['role']
				);

				if (
					isset( $message_args['is_error'] )
					&& $message_args['is_error']
				) {
					$message->mark_as_error();
				}

				return $message;
			},
			$messages
		);
	}

	/**
	 * Maps Chat_Message objects to arrays.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message[] $messages Chat message objects.
	 *
	 * @return array<array{content: string, role: string, is_error: bool}> Chat message array.
	 */
	private function map_messages_objects_to_arrays( array $messages ): array {
		return array_map(
			function ( Chat_Message $message ): array {
				return (array) $message;
			},
			$messages
		);
	}
}
