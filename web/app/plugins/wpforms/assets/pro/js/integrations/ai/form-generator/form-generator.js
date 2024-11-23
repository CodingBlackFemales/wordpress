/* global wpforms_ai_form_generator, wpf, wpforms_ai_chat_element, WPFormsBuilder */

/**
 * @param strings.dismissed.installAddons
 * @param strings.isLicenseActive
 * @param strings.modules.main
 * @param strings.newFormUrl
 * @param strings.templateCard.buttonTextInit
 * @param strings.templateCard.imageSrc
 * @param window.WPFormsAIFormGenerator
 * @param wpforms_builder.is_ai_disabled
 */

// noinspection ES6ConvertVarToLetConst
/**
 * WPForms AI Form Generator.
 *
 * @since 1.9.2
 */
// eslint-disable-next-line no-var
var WPFormsAIFormGenerator = window.WPFormsAIFormGenerator || ( function( document, window, $ ) {
	/**
	 * Localized strings.
	 *
	 * @since 1.9.2
	 *
	 * @type {Object}
	 */
	const strings = wpforms_ai_form_generator;

	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.2
	 *
	 * @type {Object}
	 */
	const app = {
		/**
		 * State data holder.
		 *
		 * @since 1.9.2
		 *
		 * @type {Object}
		 */
		state: {},

		/**
		 * Main module.
		 *
		 * @since 1.9.2
		 *
		 * @type {Object}
		 */
		main: null,

		/**
		 * The form preview module.
		 *
		 * @since 1.9.2
		 *
		 * @type {Object}
		 */
		preview: null,

		/**
		 * Start the engine.
		 *
		 * @since 1.9.2
		 */
		init() {
			if ( window.wpforms_builder?.is_ai_disabled || app.isLoaded ) {
				return;
			}

			app.updateLocationUrl();
			app.events();

			app.isLoaded = true;
		},

		/**
		 * Events.
		 *
		 * @since 1.9.2
		 */
		events() {
			$( document ).on( 'wpformsSetupPanelBeforeInitTemplatesList', app.addTemplateCard );
			$( '#wpforms-builder' )
				.on( 'wpformsBuilderReady', app.maybeSaveForm )
				.on( 'wpformsBuilderPanelLoaded', app.panelLoaded );

			// We are on the Form Templates page, not in the Form Builder.
			if ( ! window.wpforms_builder ) {
				$( document ).on( 'click', '.wpforms-template-generate, .wpforms-template-generate-install-addons', app.openNewFormGenerator );
			}
		},

		/**
		 * Panel loaded event.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} e     Event object.
		 * @param {string} panel Panel name.
		 */
		panelLoaded( e, panel ) {
			if ( panel !== 'setup' || ! strings.isLicenseActive ) {
				return;
			}

			// Load generator modules and run the main module.
			Promise.all( [
				import( strings.modules.main ),
				import( strings.modules.preview ),
				import( strings.modules.modals ),
			] )
				.then( ( [ moduleMain, modulePreview, moduleModals ] ) => {
					app.main = moduleMain.default( app, $ );
					app.preview = modulePreview.default( app, $ );
					app.modals = moduleModals.default( app, $ );

					// Run the main module.
					app.main.init();
				} );
		},

		/**
		 * Open the New Form Generator.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} e Event object.
		 */
		openNewFormGenerator( e ) {
			e.preventDefault();

			window.location = strings.newFormUrl;
		},

		/**
		 * Add the generator template card to the list.
		 *
		 * At this point, before the list is rendered, we can add our card.
		 * The card will be added to the top of the list.
		 * Event handlers will be attached later by the main module.
		 *
		 * @since 1.9.2
		 */
		addTemplateCard() {
			if ( ! $( '#wpforms-template-generate' ).length ) {
				$( '#wpforms-setup-templates-list .list' ).prepend( app.renderTemplateCard() );
			}
		},

		/**
		 * Render the template card HTML.
		 *
		 * @since 1.9.2
		 *
		 * @return {string} The card markup.
		 */
		renderTemplateCard() {
			const cardClass = window.wpforms_builder?.template_slug === 'generate' ? 'selected' : '';

			let buttonAttr = '';
			let buttonClass = ! Object.keys( strings.addonsData ).length || strings.dismissed.installAddons
				? 'wpforms-template-generate'
				: 'wpforms-template-generate-install-addons';

			if ( ! strings.isLicenseActive ) {
				const url = window.location.href + '&ai-form';
				buttonAttr = `data-action="license" data-field-name="AI Forms" data-utm-content="AI Forms" data-redirect-url="${ url }"`;
				buttonClass = 'education-modal';
			}

			return `
				<div class="wpforms-template ${ cardClass }" id="wpforms-template-generate">
					<div class="wpforms-template-thumbnail">
						<div class="wpforms-template-thumbnail-placeholder">
							<img src="${ strings.templateCard.imageSrc }" alt="${ strings.templateCard.name }" loading="lazy">
						</div>
					</div>
					<div class="wpforms-template-name-wrap">
						<h3 class="wpforms-template-name categories has-access favorite slug subcategories fields" data-categories="all,new" data-subcategories="" data-fields="" data-has-access="1" data-favorite="" data-slug="generate">
							${ strings.templateCard.name }
						</h3>
						<span class="wpforms-badge wpforms-badge-sm wpforms-badge-inline wpforms-badge-purple wpforms-badge-rounded">${ strings.templateCard.new }</span>
					</div>
					<p class="wpforms-template-desc">
						${ strings.templateCard.desc }
					</p>
					<div class="wpforms-template-buttons">
						<a href="#" class="${ buttonClass } wpforms-btn wpforms-btn-md wpforms-btn-purple-dark" ${ buttonAttr }>
							${ strings.templateCard.buttonTextInit }
						</a>
					</div>
				</div>
			`;
		},

		/**
		 * Save the form when the generated form opened.
		 *
		 * @since 1.9.2
		 */
		maybeSaveForm() {
			// Only in case the generated form was used, we have a chat session in the localized vars.
			if ( wpforms_ai_chat_element.forms?.chatHtml ) {
				WPFormsBuilder.formSave( false );
			}
		},

		/**
		 * Remove the session from URL.
		 *
		 * @since 1.9.2
		 */
		updateLocationUrl() {
			history.replaceState( {}, null, wpf.updateQueryString( 'session', null ) );
		},
	};

	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsAIFormGenerator.init();
