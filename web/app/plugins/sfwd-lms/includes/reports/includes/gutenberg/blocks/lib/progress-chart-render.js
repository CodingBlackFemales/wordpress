/* eslint-disable -- TODO: Fix linting issues */

/**
 * Rendering Block for Charts
 *
 * ServerSideRender doesn't allow us to interact with the response state in a meaningful way after it has been created. So our next best option was to either create something very similar to ServerSideRender or to copy it and make the changes we needed. We've opted for that second approach.
 *
 * To find what was added, compare this to https://github.com/WordPress/gutenberg/blob/v16.2.1/packages/server-side-render/src/server-side-render.js
 */

/**
 * External dependencies
 */
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * WordPress dependencies
 */
import { useDebounce, usePrevious } from '@wordpress/compose';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Placeholder, Spinner } from '@wordpress/components';
import { __experimentalSanitizeBlockAttributes } from '@wordpress/blocks';

const EMPTY_OBJECT = {};

export function rendererPath( block, attributes = null, urlQueryArgs = {} ) {
	return addQueryArgs( `/wp/v2/block-renderer/${ block }`, {
		context: 'edit',
		...( null !== attributes ? { attributes } : {} ),
		...urlQueryArgs,
	} );
}

export function removeBlockSupportAttributes( attributes ) {
	const {
		backgroundColor,
		borderColor,
		fontFamily,
		fontSize,
		gradient,
		textColor,
		className,
		...restAttributes
	} = attributes;

	const { border, color, elements, spacing, typography, ...restStyles } =
		attributes?.style || EMPTY_OBJECT;

	return {
		...restAttributes,
		style: restStyles,
	};
}

function DefaultEmptyResponsePlaceholder( { className } ) {
	return (
		<Placeholder className={ className }>
			{ __( 'Block rendered as empty.' ) }
		</Placeholder>
	);
}

function DefaultErrorResponsePlaceholder( { response, className } ) {
	const errorMessage = sprintf(
		// translators: %s: error message describing the problem
		__( 'Error loading block: %s' ),
		response.errorMsg
	);
	return <Placeholder className={ className }>{ errorMessage }</Placeholder>;
}

function DefaultLoadingResponsePlaceholder( { children, showLoader } ) {
	return (
		<div style={ { position: 'relative' } }>
			{ showLoader && (
				<div
					style={ {
						position: 'absolute',
						top: '50%',
						left: '50%',
						marginTop: '-9px',
						marginLeft: '-9px',
					} }
				>
					<Spinner />
				</div>
			) }
			<div style={ { opacity: showLoader ? '0.3' : 1 } }>
				{ children }
			</div>
		</div>
	);
}

export default function ProgressChart( props ) {
	const {
		attributes,
		block,
		className,
		httpMethod = 'GET',
		urlQueryArgs,
		skipBlockSupportAttributes = false,
		EmptyResponsePlaceholder = DefaultEmptyResponsePlaceholder,
		ErrorResponsePlaceholder = DefaultErrorResponsePlaceholder,
		LoadingResponsePlaceholder = DefaultLoadingResponsePlaceholder,
	} = props;

	const blockRef = useRef();
	const isMountedRef = useRef( true );
	const [ showLoader, setShowLoader ] = useState( false );
	const fetchRequestRef = useRef();
	const chartFetchRequestRef = useRef();
	const [ response, setResponse ] = useState( null );
	const prevProps = usePrevious( props );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ chartData, setChartData ] = useState( {} );

	function fetchData() {
		if ( ! isMountedRef.current ) {
			return;
		}

		setIsLoading( true );

		let sanitizedAttributes =
			attributes &&
			__experimentalSanitizeBlockAttributes( block, attributes );

		if ( skipBlockSupportAttributes ) {
			sanitizedAttributes =
				removeBlockSupportAttributes( sanitizedAttributes );
		}

		// If httpMethod is 'POST', send the attributes in the request body instead of the URL.
		// This allows sending a larger attributes object than in a GET request, where the attributes are in the URL.
		const isPostRequest = 'POST' === httpMethod;
		const urlAttributes = isPostRequest
			? null
			: sanitizedAttributes ?? null;
		const path = rendererPath( block, urlAttributes, urlQueryArgs );
		const data = isPostRequest
			? { attributes: sanitizedAttributes ?? null }
			: null;

		// Store the latest fetch request so that when we process it, we can
		// check if it is the current request, to avoid race conditions on slow networks.
		const fetchRequest = ( fetchRequestRef.current = apiFetch( {
			path,
			data,
			method: isPostRequest ? 'POST' : 'GET',
		} )
			.then( ( fetchResponse ) => {
				if (
					isMountedRef.current &&
					fetchRequest === fetchRequestRef.current &&
					fetchResponse
				) {

					setResponse( fetchResponse.rendered );

					drawCharts();

				}
			} )
			.catch( ( error ) => {
				if (
					isMountedRef.current &&
					fetchRequest === fetchRequestRef.current
				) {

					setResponse( {
						error: true,
						errorMsg: error.message,
					} );

					setIsLoading( false );
				}
			} ) );

		return fetchRequest;
	}

	function drawCharts() {

		let sanitizedAttributes =
			attributes &&
			__experimentalSanitizeBlockAttributes( block, attributes );

		if ( skipBlockSupportAttributes ) {
			sanitizedAttributes =
				removeBlockSupportAttributes( sanitizedAttributes );
		}

		const isPostRequest = 'POST' === httpMethod;
		const data = isPostRequest
			? { attributes: sanitizedAttributes ?? null }
			: null;
		const path = addQueryArgs( ajaxurl, {
			action: 'learndash_propanel_get_progress_charts_data',
			...urlQueryArgs
		} );

		const fetchRequest = ( chartFetchRequestRef.current = fetch(
			path,
			{
				body: data,
				credentials: 'include',
				headers: {
					Accept: 'application/json, */*;q=0.1',
				},
				method: isPostRequest ? 'POST' : 'GET',
			} )
			.then( ( fetchResponse ) => {
				if (
					isMountedRef.current &&
					fetchRequest === chartFetchRequestRef.current &&
					fetchResponse
				) {

					return fetchResponse.json();

				}
			} )
			.then( ( fetchResponse ) => {

				setIsLoading( false );

				// Set the response to our State.
				setChartData( fetchResponse );

				// Update the response state with toggled visibility.
				toggleDOM( fetchResponse, attributes.clientId );

			} )
			.catch( ( error ) => {
				if (
					isMountedRef.current &&
					fetchRequest === chartFetchRequestRef.current
				) {
					setResponse( {
						error: true,
						errorMsg: error.message,
					} );
				}
			} )
			.finally( () => {
				if (
					isMountedRef.current &&
					fetchRequest === chartFetchRequestRef.current
				) {
					setIsLoading( false );
				}
			} )
		);

	}

	function toggleDOM( fetchResponse, clientId ) {
		const progressAllDefaultMessage = querySelectorAll( '#block-' + clientId + ' #proPanelProgressAllDefaultMessage');
		const progressAllChart = querySelectorAll( '#block-' + clientId + ' #proPanelProgressAll' );

		if ( progressAllDefaultMessage.length > 0 && progressAllChart.length > 0 ) {
			if ( typeof fetchResponse?.data?.all_progress?.data?.datasets !== 'undefined' && fetchResponse?.data?.all_progress?.data?.datasets.length > 0 ) {
				progressAllDefaultMessage[0].style.display = 'none';
			} else {
				progressAllDefaultMessage[0].style.display = 'block';
				progressAllChart[0].style.display = 'none';
				progressAllChart[0].style.height = 0;
				progressAllChart[0].style.width = 0;
			}
		}

		const proPanelProgressInMotionDefaultMessage = querySelectorAll( '#block-' + clientId + ' #proPanelProgressInMotionDefaultMessage');
		const proPanelProgressInMotionChart = querySelectorAll( '#block-' + clientId + ' #proPanelProgressInMotion' );

		if ( proPanelProgressInMotionDefaultMessage.length > 0 && proPanelProgressInMotionChart.length > 0 ) {
			if ( typeof fetchResponse?.data?.all_percentages?.data?.datasets !== 'undefined' && fetchResponse?.data?.all_percentages?.data?.datasets.length > 0 ) {
				proPanelProgressInMotionDefaultMessage[0].style.display = 'none';
			} else {
				proPanelProgressInMotionDefaultMessage[0].style.display = 'block';
				proPanelProgressInMotionChart[0].style.display = 'none';
				proPanelProgressInMotionChart[0].style.height = 0;
				proPanelProgressInMotionChart[0].style.width = 0;
			}
		}

		const updatedResponseElements = querySelectorAll( '#block-' + attributes.clientId + ' .propanel-admin-row' );
		if ( updatedResponseElements.length > 0 ) {
			setResponse( updatedResponseElements[0].outerHTML );
		}

	}

	function drawProgressAllChart( clientId ) {

		var ctxProPanelProgressAll = querySelectorAll( '#block-' + clientId + ' #proPanelProgressAll' );

		if ( typeof ctxProPanelProgressAll === 'undefined' || typeof ctxProPanelProgressAll[0] === 'undefined' ) {
			return;
		}

		ctxProPanelProgressAll = ctxProPanelProgressAll[0].getContext( '2d' );

		if ( typeof ctxProPanelProgressAll !== 'undefined' ) {

			new Chart( ctxProPanelProgressAll, {
				type: 'doughnut',
				data: chartData?.data?.all_progress?.data,
				options: chartData?.data?.all_progress?.options
			} );

		}

	}

	function drawProgressAllPercentagesChart( clientId ) {

		var ctxProPanelProgressInMotion = querySelectorAll( '#block-' + clientId + ' #proPanelProgressInMotion' );

		if ( typeof ctxProPanelProgressInMotion === 'undefined' || typeof ctxProPanelProgressInMotion[0] === 'undefined' ) {
			return;
		}

		ctxProPanelProgressInMotion = ctxProPanelProgressInMotion[0].getContext( '2d' );

		if ( typeof ctxProPanelProgressInMotion !== 'undefined' ) {

			new Chart( ctxProPanelProgressInMotion, {
				type: 'doughnut',
				data: chartData?.data?.all_percentages?.data,
				options: chartData?.data?.all_percentages?.options
			} );

		}

	}

	/**
	 * Get the correct document context from the block ref.
	 *
	 * Blocks load within an iFrame in WP 6.9+, so we need to use the ownerDocument to ensure we're querying
	 * the correct document context.
	 *
	 * @since 4.25.7
	 *
	 * @return {Document|null} The document context
	 */
	function getDocumentContext() {
		if ( ! blockRef.current ) {
			return null;
		}

		return blockRef.current.ownerDocument;
	}

	/**
	 * Query selector within the correct document context.
	 *
	 * @since 4.17.0
	 *
	 * @param  {string} selector Selector to search for
	 *
	 * @return {NodeList} NodeList of found results
	 */
	function querySelectorAll( selector ) {
		const doc = getDocumentContext();

		if ( ! doc ) {
			return [];
		}

		return doc.querySelectorAll( selector );
	}

	const debouncedFetchData = useDebounce( fetchData, 500 );

	// When the component mounts, set isMountedRef to true.
	// When it unmounts, set it to false. This will let the async fetch callbacks know when to stop.
	useEffect(
		() => {
			isMountedRef.current = true;
			return () => {
				isMountedRef.current = false;
			};
		},
		[]
	);

	useEffect( () => {
		// Don't debounce the first fetch. This ensures that the first render
		// shows data as soon as possible.
		if ( prevProps === undefined ) {
			fetchData();
		} else if ( ! fastDeepEqual( prevProps, props ) ) {
			debouncedFetchData();
		}
	} );

	/**
	 * Effect to handle showing the loading placeholder.
	 * Show it only if there is no previous response or
	 * the request takes more than one second.
	 */
	useEffect( () => {
		if ( ! isLoading ) {
			return;
		}
		const timeout = setTimeout( () => {
			setShowLoader( true );
		}, 1000 );
		return () => clearTimeout( timeout );
	}, [ isLoading ] );

	/**
	 * Draw the chart only after the response has been updated based on Chart Data.
	 * Waiting for this is necessary to ensure that we are drawing to the existing Canvas rather than one may be in the process of being overwritten.
	 */
	useEffect( () => {
		if ( isLoading || ! blockRef.current ) {
			return;
		}

		if ( typeof chartData.data !== 'undefined' ) {
			// Toggle DOM visibility first
			toggleDOM( chartData, attributes.clientId );

			// Then draw the charts
			drawProgressAllChart( attributes.clientId );
			drawProgressAllPercentagesChart( attributes.clientId );
		}
	}, [ response, chartData, isLoading ] );

	const hasResponse = !! response;
	const hasEmptyResponse = response === '';
	const hasError = response?.error;

	if ( isLoading ) {
		return (
			<div ref={ blockRef }>
				<LoadingResponsePlaceholder { ...props } showLoader={ showLoader }>
					{ hasResponse && (
						<RawHTML className={ className }>{ response }</RawHTML>
					) }
				</LoadingResponsePlaceholder>
			</div>
		);
	}

	if ( hasEmptyResponse || ! hasResponse ) {
		return (
			<div ref={ blockRef }>
				<EmptyResponsePlaceholder { ...props } />
			</div>
		);
	}

	if ( hasError ) {
		return (
			<div ref={ blockRef }>
				<ErrorResponsePlaceholder response={ response } { ...props } />
			</div>
		);
	}

	return (
		<div ref={ blockRef }>
			<RawHTML className={ className }>{ response }</RawHTML>
		</div>
	);
}
