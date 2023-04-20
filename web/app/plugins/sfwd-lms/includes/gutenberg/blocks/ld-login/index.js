/**
 * LearnDash Block ld-login
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * Internal block libraries
 */
import { __, _x, sprintf} from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-login';
const block_title = __('LearnDash Login', 'learndash');

registerBlockType(
	block_key,
	{
		title: block_title,
		description: __('This block adds the login button on any page', 'learndash'),
		icon: 'admin-network',
		category: 'learndash-blocks',
		example: {
			attributes: {
				example_show: 1,
			},
		},
		supports: {
			customClassName: false,
		},
		attributes: {
			login_url: {
				type: 'string',
				default: '',
			},
			login_label: {
				type: 'string',
				default: '',
			},
			login_placement: {
				type: 'string',
				default: '',
			},
			login_button: {
				type: 'string',
				default: '',
			},
			logout_url: {
				type: 'string',
				default: '',
			},
			logout_label: {
				type: 'string',
				default: '',
			},
			logout_placement: {
				type: 'string',
				default: 'right',
			},
			logout_button: {
				type: 'string',
				default: '',
			},
			preview_show: {
				type: 'boolean',
				default: true
			},
			preview_action: {
				type: 'string',
				default: ''
			},
			example_show: {
				type: 'boolean',
				default: 0
			},
		},
		edit: function( props ) {
			const { attributes: { login_url, login_label, login_placement, login_button, logout_url, logout_label, logout_placement, logout_button, preview_show, preview_action, example_show },
				setAttributes } = props;

			const panelbody_login = (
				<PanelBody
					title={__('Login Settings', 'learndash')}
				>
					<TextControl
						label={__('Login URL', 'learndash')}
						help={__('Override default login URL', 'learndash')}
						value={login_url || ''}
						onChange={login_url => setAttributes({ login_url })}
					/>
					<TextControl
						label={__('Login Label', 'learndash')}
						help={__('Override default label "Login"', 'learndash')}
						value={login_label || ''}
						onChange={login_label => setAttributes({ login_label })}
					/>

					<SelectControl
						key="login_placement"
						label={__('Login Icon Placement', 'learndash')}
						value={login_placement}
						options={[
							{
								label: __('Left - To left of label', 'learndash'),
								value: '',
							},
							{
								label: __('Right - To right of label', 'learndash'),
								value: 'right',
							},
							{
								label: __('None - No icon', 'learndash'),
								value: 'none',
							},
						]}
						onChange={login_placement => setAttributes({ login_placement })}
					/>
					<SelectControl
						key="login_button"
						label={__('Login Displayed as', 'learndash')}
						help={__('Display as Button or link', 'learndash')}
						value={login_button}
						options={[
							{
								label: __('Button', 'learndash'),
								value: '',
							},
							{
								label: __('Link', 'learndash'),
								value: 'link',
							},
						]}
						onChange={login_button => setAttributes({ login_button })}
					/>
				</PanelBody>
			);

			const panelbody_logout = (
				<PanelBody
					title={__('Logout Settings', 'learndash')}
				>
					<TextControl
						label={__('Logout URL', 'learndash')}
						help={__('Override default logout URL', 'learndash')}
						value={logout_url || ''}
						onChange={logout_url => setAttributes({ logout_url })}
					/>
					<TextControl
						label={__('Logout Label', 'learndash')}
						help={__('Override default label "Logout"', 'learndash')}
						value={logout_label || ''}
						onChange={logout_label => setAttributes({ logout_label })}
					/>

					<SelectControl
						key="logout_placement"
						label={__('Logout Icon Placement', 'learndash')}
						value={logout_placement}
						options={[
							{
								label: __('Left - To left of label', 'learndash'),
								value: 'left',
							},
							{
								label: __('Right - To right of label', 'learndash'),
								value: 'right',
							},
							{
								label: __('None - No icon', 'learndash'),
								value: 'none',
							},
						]}
						onChange={logout_placement => setAttributes({ logout_placement })}
					/>
					<SelectControl
						key="logout_button"
						label={__('Logout Displayed as', 'learndash')}
						help={__('Display as Button or link', 'learndash')}
						value={logout_button}
						options={[
							{
								label: __('Button', 'learndash'),
								value: '',
							},
							{
								label: __('Link', 'learndash'),
								value: 'link',
							},
						]}
						onChange={logout_button => setAttributes({ logout_button })}
					/>
				</PanelBody>
			);

			const panel_preview = (
				<PanelBody title={__("Preview", "learndash")} initialOpen={false}>
					<ToggleControl
						label={__("Show Preview", "learndash")}
						checked={!!preview_show}
						onChange={(preview_show) => setAttributes({ preview_show })}
					/>
					<PanelRow className="learndash-block-error-message">
						{__("Preview settings are not saved.", "learndash")}
					</PanelRow>
					<SelectControl
						key="preview_action"
						label={__("Preview Action", "learndash")}
						value={preview_action}
						options={[
							{
								label: __("Login", "learndash"),
								value: "login",
							},
							{
								label: __("Logout", "learndash"),
								value: "logout",
							},
						]}
						onChange={(preview_action) => setAttributes({ preview_action })}
					/>
				</PanelBody>
			);


			const inspectorControls = (
				<InspectorControls key="controls">
					{ panelbody_login }
					{ panelbody_logout }
					{ panel_preview }
				</InspectorControls>
			);

			function get_default_message() {
				return sprintf(
					// translators: placeholder: block_title.
					_x('%s block output shown here', 'placeholder: block_title', 'learndash'), block_title
				);
			}

			function empty_response_placeholder_function(props) {
				return get_default_message();
			}

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					return <ServerSideRender
						block={block_key}
						attributes={ attributes }
						key={block_key}
						EmptyResponsePlaceholder={ empty_response_placeholder_function }
					/>
				} else {
					return get_default_message();
				}
			}

			return [
				inspectorControls,
				useMemo(() => do_serverside_render(props.attributes), [props.attributes]),
			];
		},
		save: props => {
		}
	},
);
