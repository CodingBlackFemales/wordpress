/**
 * LearnDash Block ld-materials
 *
 * @since 4.0
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
 import {
	ldlms_get_post_edit_meta,
	ldlms_get_block_legacy_support_panel,
 } from "../ldlms.js";

/**
 * Internal block libraries
 */
import { __, _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-materials';
const block_title = __( 'LearnDash Materials', 'learndash' );

registerBlockType(block_key, {
	title: block_title,
	description: __(
		"This block displays the materials for a specific LearnDash related post.",
		"learndash"
	),
	icon: "text",
	category: "learndash-blocks",
	supports: {
		customClassName: false,
	},
	attributes: {
		post_id: {
			type: "string",
			default: "",
		},
		autop: {
			type: "string",
			default: 'true',
		},
		preview_show: {
			type: "boolean",
			default: 1,
		},
		editing_post_meta: {
			type: "object",
		},
	},
	edit: (props) => {
		const {
			attributes: { post_id, autop, preview_show },
			setAttributes,
		} = props;

		const field_post_id = (
			<TextControl
				label={__("Post ID", "learndash")}
				help={__(
					"Enter a Post ID of the LearnDash post that you want to display materials for.",
					"learndash"
				)}
				value={post_id || ""}
				type={'number'}
				onChange={ function( new_post_id ) {
					if ( new_post_id != "" && new_post_id < 0 ) {
						setAttributes({ post_id: "0" });
					} else {
						setAttributes({ post_id: new_post_id });
					}
				}}			/>
		);

		const field_autop = (
			<ToggleControl
				label={__("Auto Paragraph", "learndash")}
				help={__(
					"Whether to format materials content using wpautop.",
					"learndash"
				)}
				checked={!!autop}
				onChange={(autop) => setAttributes({ autop })}
			/>
		);

		const panel_preview = (
			<PanelBody title={__("Preview", "learndash")} initialOpen={false}>
				<ToggleControl
					label={__("Show Preview", "learndash")}
					checked={!!preview_show}
					onChange={(preview_show) => setAttributes({ preview_show })}
				/>
			</PanelBody>
		);

		const inspectorControls = (
			<InspectorControls key="controls">
				{ldlms_get_block_legacy_support_panel()}
				<PanelBody title={__("Settings", "learndash")}>
					{field_post_id}
					{field_autop}
				</PanelBody>
				{panel_preview}
			</InspectorControls>
		);

		function get_default_message() {
			return sprintf(
				// translators: placeholder: block_title.
				_x(
					"%s block output shown here",
					"placeholder: block_title",
					"learndash"
				),
				block_title
			);
		}

		function empty_response_placeholder_function(props) {
			return get_default_message();
		}

		function do_serverside_render(attributes) {
			if (attributes.preview_show == true) {
				// We add the meta so the server knowns what is being edited.
				attributes.editing_post_meta = ldlms_get_post_edit_meta();

				return (
					<ServerSideRender
						block={block_key}
						attributes={attributes}
						key={block_key}
						EmptyResponsePlaceholder={empty_response_placeholder_function}
					/>
				);
			} else {
				return get_default_message();
			}
		}

		return [
			inspectorControls,
			useMemo(() => do_serverside_render(props.attributes), [props.attributes]),
		];
	},
	save: function (props) {
		delete props.attributes.example_show;
		delete props.attributes.editing_post_meta;
	},
});
