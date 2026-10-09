/**
 * Settings printed by EditorAssets::config().
 */
const config = {
	namespace: 'cbf-glossary/v1',
	canCreate: false,
	autoAppend: false,
	indexMinEntries: 8,
	...( window.cbfGlossary || {} ),
};

export default config;

/** Name of the inline reference format. */
export const FORMAT_NAME = 'cbf/glossary-ref';

/** Name of the Glossary block. */
export const BLOCK_NAME = 'cbf/glossary';

/** Class marking a reference in stored content. */
export const REF_CLASS = 'glossary-ref';
