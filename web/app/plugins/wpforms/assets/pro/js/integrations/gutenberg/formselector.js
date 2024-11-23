/* jshint es3: false, esversion: 6 */

import education from '../../../../js/integrations/gutenberg/modules/education.js';
import common from '../../../../js/integrations/gutenberg/modules/common.js';
import themesPanel from '../../../../js/integrations/gutenberg/modules/themes-panel.js';
import containerStyles from '../../../../js/integrations/gutenberg/modules/container-styles.js';
import backgroundStyles from '../../../../js/integrations/gutenberg/modules/background-styles.js';
import fieldStyles from '../../../../js/integrations/gutenberg/modules/field-styles.js';
import stockPhotos from '../../../../pro/js/integrations/gutenberg/modules/stock-photos.js';
import buttonStyles from '../../../../js/integrations/gutenberg/modules/button-styles.js';
import advancedSettings from '../../../../js/integrations/gutenberg/modules/advanced-settings.js';

/**
 * Gutenberg editor block for Pro.
 *
 * @since 1.8.8
 */
const WPForms = window.WPForms || {};

WPForms.FormSelector = WPForms.FormSelector || ( function() {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.8
	 *
	 * @type {Object}
	 */
	const app = {
		/**
		 * Common module object.
		 *
		 * @since 1.8.8
		 *
		 * @type {Object}
		 */
		common: {},

		/**
		 * Panel modules objects.
		 *
		 * @since 1.8.8
		 *
		 * @type {Object}
		 */
		panels: {},

		/**
		 * Stock Photos module object.
		 *
		 * @since 1.8.8
		 *
		 * @type {Object}
		 */
		stockPhotos: {},

		/**
		 * Start the engine.
		 *
		 * @since 1.8.8
		 */
		init() {
			app.education = education;
			app.common = common;
			app.panels.themes = themesPanel;
			app.panels.container = containerStyles;
			app.panels.background = backgroundStyles;
			app.panels.field = fieldStyles;
			app.stockPhotos = stockPhotos;
			app.panels.buttons = buttonStyles;
			app.panels.advanced = advancedSettings;

			const blockOptions = {
				panels: app.panels,
				stockPhotos: app.stockPhotos,
				getThemesPanel: app.panels.themes.getThemesPanel,
				getFieldStyles: app.panels.field.getFieldStyles,
				getContainerStyles: app.panels.container.getContainerStyles,
				getButtonStyles: app.panels.buttons.getButtonStyles,
				getBackgroundStyles: app.panels.background.getBackgroundStyles,
				getCommonAttributes: app.getCommonAttributes,
				setStylesHandlers: app.getStyleHandlers(),
				education: app.education,
			};

			// Initialize Advanced Settings module.
			app.panels.advanced.init( app.common );

			// Initialize block.
			app.common.init( blockOptions );
		},

		/**
		 * Get style handlers.
		 *
		 * @since 1.8.8
		 *
		 * @return {Object} Style handlers.
		 */
		getCommonAttributes() {
			return {
				...app.panels.field.getBlockAttributes(),
				...app.panels.container.getBlockAttributes(),
				...app.panels.buttons.getBlockAttributes(),
				...app.panels.background.getBlockAttributes(),
			};
		},

		/**
		 * Get style handlers.
		 *
		 * @since 1.8.8
		 *
		 * @return {Object} Style handlers.
		 */
		getStyleHandlers() {
			return {
				'background-image': app.panels.background.setContainerBackgroundImage,
				'background-position': app.panels.background.setContainerBackgroundPosition,
				'background-repeat': app.panels.background.setContainerBackgroundRepeat,
				'background-width': app.panels.background.setContainerBackgroundWidth,
				'background-height': app.panels.background.setContainerBackgroundHeight,
				'background-color': app.panels.background.setBackgroundColor,
				'background-url': app.panels.background.setBackgroundUrl,
			};
		},
	};

	// Provide access to public functions/properties.
	return app;
}() );

// Initialize.
WPForms.FormSelector.init();
