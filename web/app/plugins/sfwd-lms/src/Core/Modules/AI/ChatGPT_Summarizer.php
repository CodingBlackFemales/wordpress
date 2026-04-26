<?php
/**
 * AI chat summarizer class file.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

use LearnDash\Core\App;
use LearnDash\Core\Services\ChatGPT;
use LearnDash\Core\Utilities\Cast;

/**
 * Chat_Summarizer class.
 *
 * TODO: add wpunit tests for all methods and integration tests for the functionalities with the summarization stuff.
 *
 * @since 4.13.0
 */
class ChatGPT_Summarizer {
	/**
	 * Default last preserved messages count.
	 *
	 * Last preserved messages count to be kept unsummarized to provide more context to AI provider.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private const LAST_PRESERVED_MESSAGES_COUNT = 10;

	/**
	 * Maximum context characters count threshold allowed before chat messages are summarized.
	 *
	 * We use 1:1 ratio of tokens to characters and 50% of AI model max context length tokens to be very safe in
	 * any language such as Russian, Japanese, Arabic, etc.
	 * Currently, we don't have a reliable package to count tokens in PHP, so we use characters instead.
	 * For example, GPT-3 has a max context length of 4096 tokens, so we set the max characters to 2048.
	 *
	 * @since 4.13.0
	 *
	 * @var int
	 */
	private int $max_context_characters;

	/**
	 * Chat messages.
	 *
	 * @since 4.13.0
	 *
	 * @var Chat_Message[]
	 */
	private array $messages;

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message[] $messages Chat messages.
	 */
	public function __construct( array $messages ) {
		$this->messages               = $messages;
		$this->max_context_characters = $this->get_max_context_characters();
	}

	/**
	 * Summarizes chat messages.
	 *
	 * The summarization logic is as follows:
	 *
	 * We keep the first system message + the last user message which has the latest question + the last 10
	 * unsummarized messages (5 pairs of user and assistant messages). We summarize the rest of the messages and
	 * return the summary to include it in the next request. We keep the last 10 unsummarized messages to provide
	 * more context to AI provider.
	 *
	 * In the end we will get 1 first system message + 1 messages summary + 10 last unsummarized messages + 1
	 * last user message in that particular order.
	 *
	 * @since 4.13.0
	 *
	 * @return string Summarized chat messages.
	 */
	public function summarize_messages(): string {
		$messages_to_summarize = array_slice( $this->messages, 1, $this->get_last_preserved_messages_count() * -1 );

		$summary = $this->generate_summary( $messages_to_summarize );

		/**
		 * Filters the summary of the previous conversation.
		 *
		 * @since 4.13.0
		 *
		 * @param string         $content  Summary content of the previous conversation to be passed to AI provider including the message prefix to describe what it is.
		 * @param string         $summary  Raw summary of the previous conversation.
		 * @param Chat_Message[] $messages Original chat messages.
		 *
		 * @return string Summary content of the previous conversation.
		 */
		return apply_filters(
			'learndash_module_ai_chatgpt_summarizer_messages_summary_content',
			sprintf(
				// translators: %s: Summary of the previous conversation.
				__( 'Here is the summary of the previous conversation: %s', 'learndash' ),
				$summary
			),
			$summary,
			$this->messages
		);
	}

	/**
	 * Checks if messages need summarization.
	 *
	 * @since 4.13.0
	 *
	 * @return bool
	 */
	public function messages_need_summarization(): bool {
		// Skip summarization if the messages count is less than or equal to the last preserved messages count.
		if ( count( $this->messages ) <= $this->get_last_preserved_messages_count() ) {
			return false;
		}

		$total_chars = 0;

		foreach ( $this->messages as $message ) {
			$total_chars += mb_strlen( $message->content );

			if ( $total_chars > $this->max_context_characters ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets preserved messages.
	 *
	 * The preserved messages are the last messages to be kept unsummarized to provide more context to AI provider.
	 *
	 * @since 4.13.0
	 *
	 * @return Chat_Message[]
	 */
	public function get_preserved_messages(): array {
		return array_slice( $this->messages, $this->get_last_preserved_messages_count() * -1 );
	}

	/**
	 * Generates summary of provided chat messages.
	 *
	 * @since 4.13.0
	 *
	 * @param Chat_Message[] $messages Messages to summarize.
	 *
	 * @return string ChatGPT chat messages summary.
	 */
	private function generate_summary( array $messages ): string {
		$messages_string = wp_json_encode( $messages );

		if ( $messages_string === false ) {
			return '';
		}

		/**
		 * Filters the command to generate summary of chat messages.
		 *
		 * @since 4.13.0
		 *
		 * @param string         $command  Command to generate summary of chat messages.
		 * @param Chat_Message[] $messages Original messages.
		 *
		 * @return string Command to generate summary of chat messages.
		 */
		$command = apply_filters(
			'learndash_module_ai_chatgpt_summarizer_command_generate_summary',
			sprintf(
				// translators: %1$d: Maximum characters count, %2$s: Messages in JSON format.
				__( 'Summarize these chat messages which are in JSON format to be maximum below %1$d characters and return it as string: %2$s', 'learndash' ),
				$this->max_context_characters,
				$messages_string
			),
			$messages
		);

		/**
		 * ChatGPT service instance.
		 *
		 * @var ChatGPT $chatgpt ChatGPT service instance.
		 */
		$chatgpt = App::make( ChatGPT::class );

		return $chatgpt->send_command( $command );
	}

	/**
	 * Returns the last preserved messages count.
	 *
	 * @since 4.13.0
	 *
	 * @return int
	 */
	private function get_last_preserved_messages_count(): int {
		/**
		 * Filters the kept last messages count to be used in summarization.
		 *
		 * The kept last messages count is the number of last messages to be kept unsummarized to provide more
		 * context to AI provider.
		 *
		 * @since 4.13.0
		 *
		 * @param int $last_preserved_messages_count Last preserved messages count.
		 *
		 * @return int Last preserved messages count.
		 */
		return apply_filters(
			'learndash_module_ai_chatgpt_summarizer_last_preserved_messages_count',
			self::LAST_PRESERVED_MESSAGES_COUNT
		);
	}

	/**
	 * Returns the maximum context characters count threshold allowed before chat messages are summarized.
	 *
	 * @since 4.13.0
	 *
	 * @return int
	 */
	private function get_max_context_characters(): int {
		/**
		 * Filters the maximum context characters count threshold allowed before chat messages are summarized.
		 *
		 * We use 1:1 ratio of tokens to characters and 50% of AI model max context length tokens to be very safe
		 * in any language such as Russian, Japanese, Arabic, etc.
		 * Currently, we don't have a reliable package to count tokens in PHP, so we use characters instead.
		 * For example, GPT-3 has a max context length of 4096 tokens, so we set the max characters to 2048.
		 *
		 * @since 4.13.0
		 *
		 * @param int $max_context_characters Maximum context characters count threshold allowed before chat messages are summarized.
		 *
		 * @return int Maximum context characters count threshold allowed before chat messages are summarized.
		 */
		return apply_filters(
			'learndash_module_ai_chatgpt_summarizer_max_context_characters',
			Cast::to_int(
				ceil( ChatGPT::get_model_max_context_window_tokens() / 2 )
			)
		);
	}
}
