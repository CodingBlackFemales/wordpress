/**
 * TinyMCE plugin: an inline-code button for the definition editor.
 *
 * TinyMCE has a built-in "code" inline format but no toolbar button for it;
 * definitions need one (a definition of HEAD that cannot set `.git` in code is
 * worse). Loaded through mce_external_plugins as `glossary_code`.
 */

/* global tinymce */

tinymce.PluginManager.add( 'glossary_code', ( editor ) => {
	editor.addButton( 'glossary_code', {
		text: '</>',
		tooltip: 'Inline code',
		stateSelector: 'code',
		onclick: () => editor.execCommand( 'mceToggleFormat', false, 'code' ),
	} );
} );
