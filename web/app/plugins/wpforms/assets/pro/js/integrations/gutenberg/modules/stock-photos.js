/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */

/**
 * @param wpforms_gutenberg_form_selector.stockPhotos.pictures
 * @param wpforms_gutenberg_form_selector.stockPhotos.urlPath
 * @param strings.stockInstallTheme
 * @param strings.stockInstallBg
 * @param strings.stockInstall
 * @param strings.heads_up
 * @param strings.uhoh
 * @param strings.commonError
 * @param strings.picturesTitle
 * @param strings.picturesSubTitle
 */

/**
 * Gutenberg editor block.
 *
 * Themes panel module.
 *
 * @since 1.8.8
 */
export default ( function( document, window, $ ) {
	/**
	 * Localized data aliases.
	 *
	 * @since 1.8.8
	 */
	const strings = wpforms_gutenberg_form_selector.strings;
	const routeNamespace = wpforms_gutenberg_form_selector.route_namespace;
	const pictureUrlPath = wpforms_gutenberg_form_selector.stockPhotos?.urlPath;

	/**
	 * Spinner markup.
	 *
	 * @since 1.8.8
	 *
	 * @type {string}
	 */
	const spinner = '<i class="wpforms-loading-spinner wpforms-loading-white wpforms-loading-inline"></i>';

	/**
	 * Runtime state.
	 *
	 * @since 1.8.8
	 *
	 * @type {Object}
	 */
	const state = {};

	/**
	 * Stock photos pictures' list.
	 *
	 * @since 1.8.8
	 *
	 * @type {Array}
	 */
	let pictures = wpforms_gutenberg_form_selector.stockPhotos?.pictures;

	/**
	 * Stock photos picture selector markup.
	 *
	 * @since 1.8.8
	 *
	 * @type {string}
	 */
	let picturesMarkup = '';

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.8
	 *
	 * @type {Object}
	 */
	const app = {
		/**
		 * Initialize.
		 *
		 * @since 1.8.8
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.8
		 */
		ready() {},

		/**
		 * Open stock photos modal.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object}   props                    Block properties.
		 * @param {Object}   handlers                 Block handlers.
		 * @param {string}   from                     From where the modal was triggered, `themes` or `bg-styles`.
		 * @param {Function} setShowBackgroundPreview Function to show/hide the background preview.
		 */
		openModal( props, handlers, from, setShowBackgroundPreview ) {
			// Set opener block properties.
			state.blockProps = props;
			state.blockHandlers = handlers;
			state.setShowBackgroundPreview = setShowBackgroundPreview;

			if ( app.isPicturesAvailable() ) {
				app.picturesModal();

				return;
			}

			app.installModal( from );
		},

		/**
		 * Open stock photos install modal on select theme.
		 *
		 * @since 1.8.8
		 *
		 * @param {string} themeSlug      The theme slug.
		 * @param {Object} blockProps     Block properties.
		 * @param {Object} themesModule   Block properties.
		 * @param {Object} commonHandlers Common handlers.
		 */
		onSelectTheme( themeSlug, blockProps, themesModule, commonHandlers ) {
			state.themesModule = themesModule;
			state.commonHandlers = commonHandlers;
			state.themeSlug = themeSlug;
			state.blockProps = blockProps;

			if ( app.isPicturesAvailable() ) {
				return;
			}

			// Check only WPForms themes.
			if ( ! themesModule?.isWPFormsTheme( themeSlug ) ) {
				return;
			}

			const theme = themesModule?.getTheme( themeSlug );
			const bgUrl = theme.settings?.backgroundUrl;

			if ( bgUrl?.length && bgUrl !== 'url()' ) {
				app.installModal( 'themes' );
			}
		},

		/**
		 * Open a modal prompting to download and install the Stock Photos.
		 *
		 * @since 1.8.8
		 *
		 * @param {string} from From where the modal was triggered, `themes` or `bg-styles`.
		 */
		installModal( from ) {
			const installStr = from === 'themes' ? strings.stockInstallTheme : strings.stockInstallBg;

			$.confirm( {
				title: strings.heads_up,
				content: installStr + ' ' + strings.stockInstall,
				icon: 'wpforms-exclamation-circle',
				type: 'orange',
				buttons: {
					continue: {
						text: strings.continue,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action() {
							// noinspection JSUnresolvedReference
							this.$$continue.prop( 'disabled', true )
								.html( spinner + strings.installing );

							// noinspection JSUnresolvedReference
							this.$$cancel
								.prop( 'disabled', true );

							app.install( this, from );

							return false;
						},
					},
					cancel: {
						text: strings.cancel,
						keys: [ 'esc' ],
					},
				},
			} );
		},

		/**
		 * Display the modal window with an error message.
		 *
		 * @since 1.8.8
		 *
		 * @param {string} error Error message.
		 */
		errorModal( error ) {
			$.alert( {
				title: strings.uhoh,
				content: error || strings.commonError,
				icon: 'fa fa-exclamation-circle',
				type: 'red',
				buttons: {
					cancel: {
						text    : strings.close,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * Display the modal window with pictures.
		 *
		 * @since 1.8.8
		 */
		picturesModal() {
			state.picturesModal = $.alert( {
				title : `${ strings.picturesTitle }<p>${ strings.picturesSubTitle }</p>`,
				content: app.getPictureMarkup(),
				type: 'picture-selector',
				boxWidth: '800px',
				closeIcon: true,
				animation: 'opacity',
				closeAnimation: 'opacity',
				buttons: false,
				onOpen() {
					this.$content
						.off( 'click' )
						.on( 'click', '.wpforms-gutenberg-stock-photos-picture', app.selectPicture );
				},
			} );
		},

		/**
		 * Install stock photos.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} modal The jQuery-confirm modal window object.
		 * @param {string} from  From where the modal was triggered, `themes` or `bg-styles`.
		 */
		install( modal, from ) {
			// If a fetch is already in progress, exit the function.
			if ( state.isInstalling ) {
				return;
			}

			// Set the flag to true indicating a fetch is in progress.
			state.isInstalling = true;

			try {
				// Fetch themes data.
				wp.apiFetch( {
					path: routeNamespace + 'stock-photos/install/',
					method: 'POST',
					cache: 'no-cache',
				} ).then( ( response ) => {
					if ( ! response.result ) {
						app.errorModal( response.error );

						return;
					}

					// Store the pictures' data.
					pictures = response.pictures || [];

					// Update block theme or open the picture selector modal.
					if ( from === 'themes' ) {
						state.commonHandlers.styleAttrChange( 'backgroundUrl', '' );
						state.themesModule?.setBlockTheme( state.blockProps, state.themeSlug );
					} else {
						app.picturesModal();
					}
				} ).catch( ( error ) => {
					// eslint-disable-next-line no-console
					console.error( error?.message );
					app.errorModal( `<p>${ strings.commonError }</p><p>${ error?.message }</p>` );
				} ).finally( () => {
					state.isInstalling = false;

					// Close the modal window.
					modal.close();
				} );
			} catch ( error ) {
				state.isInstalling = false;
				// eslint-disable-next-line no-console
				console.error( error );
				app.errorModal( strings.commonError + '<br>' + error );
			}
		},

		/**
		 * Detect whether pictures' data available.
		 *
		 * @since 1.8.8
		 *
		 * @return {boolean} True if pictures' data available, false otherwise.
		 */
		isPicturesAvailable() {
			return Boolean( pictures?.length );
		},

		/**
		 * Generate the pictures' selector markup.
		 *
		 * @since 1.8.8
		 *
		 * @return {string} Pictures' selector markup.
		 */
		getPictureMarkup() {
			if ( ! app.isPicturesAvailable() ) {
				return '';
			}

			if ( picturesMarkup !== '' ) {
				return picturesMarkup;
			}

			pictures.forEach( ( picture ) => {
				const pictureUrl = pictureUrlPath + picture;

				picturesMarkup += `<div class="wpforms-gutenberg-stock-photos-picture"
					data-url="${ pictureUrl }"
					style="background-image: url( '${ pictureUrl }' )"
				></div>`;
			} );

			picturesMarkup = `<div class="wpforms-gutenberg-stock-photos-pictures-wrap">${ picturesMarkup }</div>`;

			return picturesMarkup;
		},

		/**
		 * Select picture event handler.
		 *
		 * @since 1.8.8
		 */
		selectPicture() {
			const pictureUrl = $( this ).data( 'url' );
			const bgUrl = `url( ${ pictureUrl } )`;

			// Update the block properties.
			state.blockHandlers.styleAttrChange( 'backgroundUrl', bgUrl );

			// Close the modal window.
			state.picturesModal?.close();

			// Show the background preview.
			state.setShowBackgroundPreview( true );
		},
	};

	app.init();

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );
