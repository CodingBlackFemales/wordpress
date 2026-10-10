/**
 * ESLint config for cbf-semantic-glossary.
 *
 * Extends @wordpress/scripts' defaults. `@wordpress/*` packages are WordPress
 * script dependencies, loaded at runtime and mapped to `wp.*` globals at build
 * time, so they are not installed here and the import checks skip them.
 */
'use strict';

const defaults = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaults,
	{ ignores: [ 'assets/**' ] },
	{
		rules: {
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			'import/no-extraneous-dependencies': 'off',
		},
	},
];
