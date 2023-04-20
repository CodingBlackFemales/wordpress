/**
 * LearnDash Block ld-group
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_custom_label,
	ldlms_get_integer_value
} from '../ldlms.js';

/**
 * Internal block libraries
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

const block_key   = 'learndash/ld-group';
const block_title = sprintf(
	// translators: placeholder: Group.
	_x('LearnDash %s', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group')
);

registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholder: Group.
			_x( 'This block shows the content if the user is enrolled into the %s.', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group')),
		icon: 'groups',
		category: 'learndash-blocks',
		supports: {
			customClassName: false,
		},
		attributes: {
			group_id: {
				type: 'string',
			},
			user_id: {
				type: 'string',
				default: '',
			},
			autop: {
				type: 'boolean',
				default: true
			},
		},
		edit: props => {
			const { attributes: { group_id, user_id, autop }, className, setAttributes } = props;

			const inspectorControls = (
				<InspectorControls key="controls">
					<PanelBody
						title={__('Settings', 'learndash')}
					>
						<TextControl
							label={sprintf(
								// translators: placeholder: Group.
								_x('%s ID', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group'))}
							help={sprintf(
								// translators: placeholder: Group.
								_x('%s ID (required)', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group'))}
							value={group_id || ''}
							type={'number'}
							onChange={ function( new_group_id ) {
								if ( new_group_id != "" && new_group_id < 0 ) {
									setAttributes({ group_id: "0" });
								} else {
									setAttributes({ group_id: new_group_id });
								}
							}}						/>
						<TextControl
							label={__('User ID', 'learndash')}
							help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
							value={user_id || ''}
							type={'number'}
							onChange={ function( new_user_id ) {
								if ( new_user_id != "" && new_user_id < 0 ) {
									setAttributes({ user_id: "0" });
								} else {
									setAttributes({ user_id: new_user_id });
								}
							}}						/>
						<ToggleControl
							label={__('Auto Paragraph', 'learndash')}
							checked={!!autop}
							onChange={autop => setAttributes({ autop })}
						/>
					</PanelBody>
				</InspectorControls>
			);

			let ld_block_error_message = '';
			let preview_group_id = ldlms_get_integer_value(group_id);
			if (preview_group_id == 0) {
				ld_block_error_message = sprintf(
					// translators: placeholder: Group.
					_x('%s ID is required.', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group'));
			}

			if (ld_block_error_message.length) {
				ld_block_error_message = (<span className="learndash-block-error-message">{ld_block_error_message}</span>);
			}

			const outputBlock = (
				<div className={className} key='learndash/ld-group'>
					<span className="learndash-inner-header">{block_title}</span>
					<div className="learndash-block-inner">
						{ld_block_error_message}
						<InnerBlocks />
					</div>
				</div>
			);

			return [
				inspectorControls,
				outputBlock
			];
		},
		save: props => {
			return (
				<InnerBlocks.Content />
			);
		}
	},
);
