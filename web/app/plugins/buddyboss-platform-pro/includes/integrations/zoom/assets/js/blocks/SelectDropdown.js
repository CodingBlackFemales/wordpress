import { TextControl, SelectControl } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';


export default function SelectDropdown( { options, setTimezone, timezone, clientId } ) {
	const [ currentValue, setCurrentValue ] = useState( timezone );
	const [ currentData, setCurrentData ] = useState( options );
	const [ showing, setShowing ] = useState( false );
	const suggestionsListRef = useRef( null );

	/**
	 * Filter items on user input
	 */
	const filterSuggestions = ( val ) => {
		if ( val !== '' ) {
			const result = options.filter( item => item.label.toLowerCase().includes( val.toLowerCase() ) );
			setCurrentData( result );
		} else {
			setCurrentData( options );
		}
	};

	/**
	 * Select item from dropdown and select same for native select
	 */
	const selectItem = ( item ) => {
		setCurrentValue( item );
		setTimezone( item );
		setShowing( false );
	};

	/**
	 * Open dropdown and set data
	 */
	const showDropdown = () => {
		setCurrentData( options );
		setShowing( true );
	};

	const wrapperRef = useRef( null );

	useEffect( () => {
		/**
		 * Hide Dropdown when clicked outside
		 */
		function handleClickOutside( event ) {
			if ( wrapperRef.current && ! wrapperRef.current.contains( event.target ) ) {
				setShowing( false );
			}
		}

		// Bind the event listener.
		document.addEventListener( 'click', handleClickOutside );
		return () => {
			// Unbind the event listener on clean up.
			document.removeEventListener( 'click', handleClickOutside );
		};
	}, [ wrapperRef ] );

	/**
	 * Scroll to active item
	 */
	useEffect( () => {
		if ( showing ) {
			let suggestionsList = document.querySelector( '#block-' + clientId + ' .Select_suggestions-list' );
			let suggestionsListItem = suggestionsList.querySelector( '.active' );
			suggestionsListItem ? suggestionsList.scrollTop = (suggestionsListItem.offsetTop - 70) : '';
		}
	}, [ showing ] );

	return (
		<div className="select_main" ref={ wrapperRef }>
			<div className="select_option" onClick={ () => showDropdown() }>
				<SelectControl
					//label={ __( 'Timezone', 'buddyboss-pro' ) }
					label={bpZoomMeetingBlock.block_zoom_timezone}
					value={ currentValue }
					options={ options }
					onChange={ setTimezone }
					className="bb_inline_selectBox"
				/>
			</div>
			{ showing && (
				<div className="Select_suggestions-wrapper">
					<TextControl
						onChange={ ( val ) => filterSuggestions( val ) }
						label={bpZoomMeetingBlock.block_zoom_search_timezone}
						hideLabelFromVision={ true }
						autoComplete="off"
					/>
					<ul className="Select_suggestions-list" ref={ suggestionsListRef }>
						{
							currentData.length ? currentData.map( item => (
								<li key={ item.value } onClick={ () => selectItem( item.value ) }
								    className={ `Select_suggestions-list-item ${ currentValue === item.value ? 'active' : '' }` }>{ item.label }</li>) ) : (
								<li className="Select_suggestions-list-item no-result">{bpZoomMeetingBlock.block_zoom_no_results}</li>)
						}
					</ul>
				</div>
			) }
		</div>
	);
}
