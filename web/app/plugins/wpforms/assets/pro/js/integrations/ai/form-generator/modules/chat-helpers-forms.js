/* global WPFormsAIChatHTMLElement, WPFormsAIFormGenerator, wpf, wpforms_builder */

/**
 * @param chat.modeStrings.footerFirst
 * @param chat.modeStrings.inactiveAnswerTitle
 * @param chat.preventResizeInput
 * @param response.form_title
 * @param wpforms_builder.allowed_label_html_tags
 */

/**
 * The WPForms AI chat element.
 *
 * Forms mode helpers module.
 *
 * @since 1.9.2
 *
 * @param {WPFormsAIChatHTMLElement} chat The chat element.
 *
 * @return {Object} Forms helpers object.
 */
export default function( chat ) { // eslint-disable-line no-unused-vars, max-lines-per-function
	/**
	 * The input (textarea) height.
	 *
	 * @type {Object}
	 */
	const inputHeight = {
		min: 54,
		max: 95,
	};

	/**
	 * The default `forms` mode helpers object.
	 *
	 * @since 1.9.2
	 */
	const forms = {
		/**
		 * Init `forms` mode.
		 *
		 * @since 1.9.2
		 */
		init() {
			chat.input.addEventListener( 'keydown', chat.modeHelpers.keyDown );
			chat.input.addEventListener( 'keyup', chat.modeHelpers.resizeInput );

			// Set the initial form generator state.
			if ( chat.sessionId ) {
				WPFormsAIFormGenerator.state.chatStart = true;

				// Remove the selected state from the current template card.
				WPFormsAIFormGenerator.main.el.$templateCard
					.next( '.selected' ).removeClass( 'selected' );
			}
		},

		/**
		 * Detect the Enter key press.
		 * Prevent resizing the input if Enter key pressed without Shift.
		 *
		 * @since 1.9.2
		 *
		 * @param {KeyboardEvent} e The keyboard event.
		 */
		keyDown( e ) {
			chat.preventResizeInput = e.code === 'Enter' && ! e.shiftKey;

			if ( chat.preventResizeInput ) {
				e.preventDefault();
				forms.setInputHeight( inputHeight.min );
			}
		},

		/**
		 * Resize textarea while added new lines.
		 *
		 * @since 1.9.2
		 */
		resizeInput() {
			if ( chat.preventResizeInput ) {
				return;
			}

			// Reset style to get the correct scroll height.
			chat.input.style.height = '';
			chat.input.style.paddingTop = '10px';
			chat.input.style.paddingBottom = '10px';

			let height;
			const scrollHeight = chat.input.scrollHeight;

			// Calculate the height based on the scroll height.
			height = Math.min( scrollHeight, inputHeight.max );
			height = Math.max( height, inputHeight.min );

			forms.setInputHeight( height );
		},

		/**
		 * Reset the message input field.
		 *
		 * @since 1.9.2
		 */
		resetInput() {
			forms.resizeInput();
		},

		/**
		 * Set textarea height.
		 *
		 * @since 1.9.2
		 *
		 * @param {number} height The height.
		 */
		setInputHeight( height ) {
			// Adjust padding based on the height.
			if ( height <= inputHeight.min ) {
				chat.input.style.paddingTop = '';
				chat.input.style.paddingBottom = '';
			}

			// Set the height.
			chat.input.style.height = height + 'px';
			chat.style.setProperty( '--wpforms-ai-chat-input-height', height + 'px' );
		},

		/**
		 * Get the answer based on AI response data.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} response The AI response data.
		 *
		 * @return {string} HTML markup.
		 */
		getAnswer( response ) {
			if ( ! response ) {
				return '';
			}

			const rnd = Math.floor( Math.random() * chat.modeStrings.footer.length );
			const footer = chat.modeStrings.footer[ rnd ];
			const answer = response.explanation || ( response.form_title ?? '' );

			return `
				<h4>${ answer }</h4>
				<span>${ footer }</span>
			`;
		},

		/**
		 * Get the answer pre-buttons HTML markup.
		 *
		 * @since 1.9.2
		 *
		 * @return {string} The answer pre-buttons HTML markup.
		 */
		getAnswerButtonsPre() {
			return `
				<button type="button" class="wpforms-ai-chat-use-form wpforms-ai-chat-answer-action wpforms-btn-sm wpforms-btn-orange" >
					<span>${ chat.modeStrings.useForm }</span>
				</button>
			`;
		},

		/**
		 * The answer was added.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLElement} element The answer element.
		 */
		addedAnswer( element ) { // eslint-disable-line no-unused-vars
			forms.updateInactiveAnswers();
		},

		/**
		 * Set active answer.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLElement} element The answer element.
		 */
		setActiveAnswer( element ) {
			forms.updateInactiveAnswers();

			element.querySelector( '.wpforms-chat-item-content' ).setAttribute( 'title', '' );
		},

		/**
		 * Update inactive answers.
		 *
		 * @since 1.9.2
		 */
		updateInactiveAnswers() {
			chat.messageList.querySelectorAll( '.wpforms-chat-item-answer:not(.active) .wpforms-chat-item-content' )
				.forEach( ( el ) => {
					// Set title attribute for inactive answers.
					el.setAttribute( 'title', chat.modeStrings.inactiveAnswerTitle );
				} );
		},

		/**
		 * Determine whether the Welcome Screen should be displayed.
		 *
		 * @since 1.9.2
		 *
		 * @return {boolean} Display the Welcome Screen or not.
		 */
		isWelcomeScreen() {
			return true;
		},

		/**
		 * Get the message input field HTML.
		 *
		 * @since 1.9.2
		 *
		 * @return {string} The message input field markup.
		 */
		getMessageInputField() {
			return `<textarea placeholder="${ chat.modeStrings.placeholder }"></textarea>`;
		},

		/**
		 * Sanitize response.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} response The response data to sanitize.
		 *
		 * @return {Object} The sanitized response.
		 */
		sanitizeResponse( response ) {
			if ( ! response.explanation ) {
				return response;
			}

			// Sanitize explanation string.
			response.explanation = wpf.sanitizeHTML( response.explanation, wpforms_builder.allowed_label_html_tags );

			return response;
		},
	};

	return forms;
}
