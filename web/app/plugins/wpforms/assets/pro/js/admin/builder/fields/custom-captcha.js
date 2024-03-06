/* global wpforms_builder_custom_captcha, wpforms_builder, WPFormsBuilder */

/**
 * WPForms Custom Captcha admin builder function.
 *
 * @since 1.8.7
 */
const WPFormsCaptcha = window.WPFormsCaptcha || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.7
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Document events on which removeEmptyQuestions() should be fired.
		 *
		 * @since 1.8.7
		 */
		removeEmptyQuestionsEvents: 'wpformsPanelSwitch wpformsFieldTabToggle wpformsFieldOptionGroupToggle',

		/**
		 * Start the engine.
		 *
		 * @since 1.8.7
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Initialize once the DOM is fully loaded.
		 *
		 * @since 1.8.7
		 */
		ready() {
			$( '#wpforms-builder' )

				// Type (format) toggle.
				.on( 'change', '.wpforms-field-option-captcha .wpforms-field-option-row-format select', app.formatToggle )

				// Add new captcha question.
				.on( 'click', '.wpforms-field-option-row-questions .add', app.addQuestion )

				// Remove captcha question.
				.on( 'click', '.wpforms-field-option-row-questions .remove', app.removeQuestion )

				// Captcha sample question update.
				.on( 'input', '.wpforms-field-option-row-questions .question', app.updateQuestion )

				// Captcha sample answer update.
				.on( 'input', '.wpforms-field-option-row-questions .answer', app.updateAnswer )

				// Remove empty questions before saving the form.
				.on( 'wpformsBeforeSave', app.removeEmptyQuestions );

			// Validate questions before panel switch and field tab toggle.
			$( document ).on( app.removeEmptyQuestionsEvents, app.removeEmptyQuestions );
		},

		/**
		 * Format toggle event handler.
		 *
		 * @since 1.8.7
		 */
		formatToggle() {
			const $this = $( this ),
				value = $this.val(),
				id = $this.parent().data( 'field-id' ),
				$questions = $( `#wpforms-field-option-row-${ id }-questions` ),
				$size = $( `#wpforms-field-option-row-${ id }-size` );

			if ( value === 'math' ) {
				$questions.hide().addClass( 'wpforms-hidden' );
				$size.hide();
				return;
			}

			$questions.show().removeClass( 'wpforms-hidden' );
			$size.show();
		},

		/**
		 * Add new captcha question event handler.
		 *
		 * @since 1.8.7
		 *
		 * @param {Object} event Event object.
		 */
		addQuestion( event ) {
			event.preventDefault();

			const $this = $( this ),
				$choice = $this.closest( 'li' ),
				$choicesList = $this.closest( '.choices-list' ),
				fieldID = $choicesList.data( 'field-id' ),
				$question = $choice.clone().insertAfter( $choice );
			let id = $choicesList.attr( 'data-next-id' );
			const name = 'fields[' + fieldID + '][questions][' + id + ']';

			$question.attr( 'data-key', id );
			$question.find( 'input.question' ).val( '' ).attr( 'name', name + '[question]' );
			$question.find( 'input.answer' ).val( '' ).attr( 'name', name + '[answer]' );
			$choicesList.attr( 'data-next-id', ++id );
		},

		/**
		 * Remove captcha question event handler.
		 *
		 * @since 1.8.7
		 *
		 * @param {Object} event Event object.
		 */
		removeQuestion( event ) {
			event.preventDefault();

			const $this = $( this ),
				$choice = $this.closest( 'li' ),
				$choicesList = $this.closest( '.choices-list' ),
				$questions = $choicesList.find( '.question' ),
				fieldID = $choicesList.data( 'field-id' ),
				total = app.getTotalNotEmptyQuestions( $questions );

			// We can delete a choice if at least one non-empty question will remain.
			if (
				total > 1 ||
				( total === 1 && $choice.find( '.question' ).val().trim().length === 0 )
			) {
				$choice.remove();
				$( `#wpforms-field-${ fieldID }` ).find( '.wpforms-question' ).text( $choicesList.find( '.question' ).val() );

				return;
			}

			app.showAlert( null );
		},

		/**
		 * Remove questions with empty question or answer values before saving the form.
		 *
		 * @since 1.8.7
		 *
		 * @param {Event} event The `wpformsBeforeSave` event object.
		 */
		removeEmptyQuestions( event ) {
			const $captchaFields = $( '.wpforms-field-option-captcha' );

			$captchaFields.each( function( _fieldIndex, field ) {
				const id = $( field ).data( 'field-id' ),
					$choicesList = $( `#wpforms-field-option-row-${ id }-questions .choices-list` ),
					$choices = $choicesList.find( 'li' ),
					$notEmptyChoices = app.getNotEmptyChoices( $choices );

				// If all choices are considered empty, show and alert and stop.
				if ( $notEmptyChoices.length === 0 ) {
					app.showAlert( function() {
						const currentAlert = this.$el;

						// Close any other popups if active. The main goal is to close Marketing Panel popup.
						$( '.jconfirm' ).map( function() {
							const $jConfirm = $( this );
							if ( ! $( currentAlert ).is( $jConfirm ) ) {
								$jConfirm.find( '.btn-default' ).trigger( 'click' );
							}
							return null; // Add a return statement to satisfy the eslint rule.
						} );

						$( document ).off( app.removeEmptyQuestionsEvents, app.removeEmptyQuestions );

						// We're not on Fields panel, activate it.
						if ( $( '#wpforms-panels-toggle .active' ).data( 'panel' ) !== 'fields' ) {
							WPFormsBuilder.panelSwitch( 'fields' );
						}

						// Activate Field Options > General.
						WPFormsBuilder.fieldTabToggle( id );
						$( `#wpforms-field-option-${ id } .wpforms-field-option-group` ).removeClass( 'active' );
						$( `#wpforms-field-option-basic-${ id }` ).addClass( 'active' );

						$( document ).on( app.removeEmptyQuestionsEvents, app.removeEmptyQuestions );
					} );

					// Stop saving the form.
					event.preventDefault();

					return;
				}

				// We're good to go, let's remove empty choices.
				$choices.each( function( _choiceIndex, choice ) {
					const $choice = $( choice );

					if ( app.isEmptyChoice( $choice ) ) {
						$choice.remove();
					}
				} );

				// Update preview, if needed.
				$( `#wpforms-field-${ id }` ).find( '.wpforms-question' ).text( $choices.find( '.question' ).val() );
			} );
		},

		/**
		 * Captcha sample question update event handler.
		 *
		 * @since 1.8.7
		 */
		updateQuestion() {
			const $this = $( this ),
				value = $this.val().trim();

			$this.toggleClass( 'wpforms-error', ! value.length );

			if ( ! value.length ) {
				return;
			}

			const $choicesList = $this.closest( '.choices-list' ),
				$questions = $choicesList.find( '.question' ),
				fieldID = $choicesList.data( 'field-id' );

			$this.data( 'prev-value', value );

			if ( $this.is( $questions[ 0 ] ) ) {
				$( `#wpforms-field-${ fieldID }` ).find( '.wpforms-question' ).text( value );
			}
		},

		/**
		 * Captcha sample answer update event handler.
		 *
		 * @since 1.8.7
		 */
		updateAnswer() {
			const $this = $( this ),
				value = $this.val().trim();

			$this.toggleClass( 'wpforms-error', ! value.length );
		},

		/**
		 * Show alert notifying that at least one not empty choice should remain.
		 *
		 * @since 1.8.7
		 *
		 * @param {Function|null} action Callback action attached to the confirm button.
		 */
		showAlert( action ) {
			$.alert( {
				title:   false,
				content: wpforms_builder_custom_captcha.error_not_empty_question,
				icon:   'fa fa-exclamation-circle',
				type:   'orange',
				buttons: {
					confirm: {
						text:     wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys:     [ 'enter' ],
						action,
					},
				},
			} );
		},

		/**
		 * Count all non-empty questions.
		 *
		 * @since 1.8.7
		 *
		 * @param {jQuery} $questions Questions choices jQuery object.
		 *
		 * @return {number} Number of total not empty questions.
		 */
		getTotalNotEmptyQuestions( $questions ) {
			return $questions.filter( function() {
				return this.value.trim().length;
			} ).length;
		},

		/**
		 * Filter out all choices that have either empty question or empty answer.
		 *
		 * @since 1.8.7
		 *
		 * @param {jQuery} $choices All question/answer choices.
		 *
		 * @return {jQuery} Choices containing only non-empty question and answer.
		 */
		getNotEmptyChoices( $choices ) {
			return $choices.filter( function() {
				return ! app.isEmptyChoice( $( this ) );
			} );
		},

		/**
		 * Determine whether a question/answer choice is empty.
		 * A choice is considered "empty" if either question or answer is empty.
		 *
		 * @since 1.8.7
		 *
		 * @param {jQuery} $choice List item containing both question and answer fields.
		 *
		 * @return {boolean} Whether the choice is empty.
		 */
		isEmptyChoice( $choice ) {
			return $choice.find( '.question' ).val().trim().length === 0 || $choice.find( '.answer' ).val().trim().length === 0;
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsCaptcha.init();
