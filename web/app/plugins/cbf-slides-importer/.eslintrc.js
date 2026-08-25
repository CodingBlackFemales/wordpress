/**
 * ESLint config for cbf-slides-importer.
 *
 * The plugin targets modern browsers (Chrome / Firefox latest) and uses
 * async/await (ES2017), so we set ecmaVersion accordingly.
 */
'use strict';

module.exports = {
	parserOptions: {
		ecmaVersion: 2020,
	},
	env: {
		browser: true,
		es2020: true,
	},
	// Globals are declared via /* global ... */ JSDoc comments in each file.
};
