/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';
// Used in the 'edit' and 'save' functions.
const { useBlockProps } = wp.blockEditor;
const { serverSideRender: ServerSideRender } = wp;

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import json from '../block.json';

// Destructure the json file to get the name and settings for the block.
const { name } = json;

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(
	name,
	{
		edit: () => {
			return [
				<div { ...useBlockProps() }>
					<ServerSideRender
						block={name}
						key={name}
					/>
				</div>
			];
		},
		save() {
			return null; // Nothing to save here..
		}
	}
);
