/**
 * Custom ColorPalette component.
 *
 * @since 4.21.4
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	ColorPalette as GBColorPalette,
} from '@wordpress/components';

class ColorPalette extends Component {
	// eslint-disable-next-line no-useless-constructor
	constructor( props ) {
		super( props );
	}

	render() {
		// eslint-disable-next-line camelcase
		const { name, value, label, display_state, setAttributes } = this.props;

		return (
			// eslint-disable-next-line @wordpress/no-base-control-with-label-without-id
			<BaseControl
				className={
					// eslint-disable-next-line camelcase
					typeof display_state[ name ] !== 'undefined' &&
					// eslint-disable-next-line camelcase
					! display_state[ name ]
						? 'hide color-picker'
						: 'show color-picker'
				}
				label={ label }
			>
				<div className="color-wrapper">
					<GBColorPalette
						colors={ [] }
						value={ value || '' }
						// eslint-disable-next-line camelcase
						onChange={ ( new_value ) => {
							// eslint-disable-next-line camelcase
							setAttributes( { [ name ]: new_value } );
						} }
						clearable={ false }
					/>
					<Button
						className="clear-button"
						variant="tertiary"
						onClick={ () => {
							setAttributes( {
								[ name ]: null,
							} );
						} }
					>
						{ __( 'Clear', 'learndash' ) }
					</Button>
					<div className="clear"></div>
				</div>
			</BaseControl>
		);
	}
}

export default ColorPalette;
