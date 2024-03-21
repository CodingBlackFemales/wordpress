/* global resume_manager_ajax_filters */
jQuery( document ).ready( function ( $ ) {
	const xhr = [];

	$( '.resumes' ).on( 'update_results', function ( event, page, append ) {
		let data = '';
		const target = $( this );
		const form = target.find( '.resume_filters' );
		const showing = target.find( '.showing_resumes' );
		const results = target.find( '.resumes' );
		const per_page = target.data( 'per_page' );
		const orderby = target.data( 'orderby' );
		const order = target.data( 'order' );
		const featured = target.data( 'featured' );
		const index = $( 'div.resumes' ).index( this );
		const is_rand = [ 'rand', 'rand_featured' ].includes( orderby );

		if ( xhr[ index ] ) {
			xhr[ index ].abort();
		}

		if ( append ) {
			$( '.load_more_resumes', target ).addClass( 'loading' );
		} else {
			$( results ).addClass( 'loading' );
			$( 'li.resume, li.no_resumes_found', results ).css(
				'visibility',
				'hidden'
			);
		}

		if ( true == target.data( 'show_filters' ) ) {
			const categories = form
				.find( ':input[name^="search_categories"]' )
				.map( function () {
					return $( this ).val();
				} )
				.get();
			let keywords = '';
			let location = '';
			let skills = '';
			const $keywords = form.find( ':input[name="search_keywords"]' );
			const $location = form.find( ':input[name="search_location"]' );
			const $skills = form.find( ':input[name="search_skills"]' );

			// Workaround placeholder scripts
			if ( $keywords.val() != $keywords.attr( 'placeholder' ) )
				keywords = $keywords.val();

			if ( $location.val() != $location.attr( 'placeholder' ) )
				location = $location.val();

			if ( $skills.val() != $skills.attr( 'placeholder' ) )
				skills = $skills.val();

			data = {
				action: 'resume_manager_get_resumes',
				search_keywords: keywords,
				search_location: location,
				search_categories: categories,
				search_skills: skills,
				per_page,
				orderby,
				order,
				page,
				featured,
				show_pagination: target.data( 'show_pagination' ),
				form_data: form.serialize(),
			};
		} else {
			data = {
				action: 'resume_manager_get_resumes',
				search_categories: target.data( 'categories' ).split( ',' ),
				search_keywords: target.data( 'keywords' ),
				search_location: target.data( 'location' ),
				search_skills: target.data( 'skills' ),
				per_page,
				orderby,
				order,
				featured,
				page,
				show_pagination: target.data( 'show_pagination' ),
			};
		}

		// Reset loaded_ids for a new filter.
		if ( 1 === page ) {
			target.removeData( 'loaded_ids' );
		}

		if ( is_rand ) {
			data.exclude_ids = target.data( 'loaded_ids' );
		}

		xhr[ index ] = $.ajax( {
			type: 'POST',
			url: resume_manager_ajax_filters.ajax_url,
			data,
			success( result ) {
				if ( result ) {
					try {
						// Set loaded IDs.
						const loadedIds = target.data( 'loaded_ids' ) || [];
						target.data( 'loaded_ids', [
							...loadedIds,
							...result.post_ids,
						] );

						if ( result.showing ) {
							$( showing )
								.show()
								.html( '' )
								.append(
									'<span>' +
										result.showing +
										'</span>' +
										result.showing_links
								);
						} else {
							$( showing ).hide();
						}

						if ( result.html ) {
							if ( append ) {
								$( results ).append( result.html );
							} else {
								$( results ).html( result.html );
							}
						}

						if ( true == target.data( 'show_pagination' ) ) {
							target.find( '.job-manager-pagination' ).remove();

							if ( result.pagination ) {
								target.append( result.pagination );
							}
						} else {
							if (
								// Check pagination without random order.
								( ! is_rand &&
									( ! result.found_resumes ||
										result.max_num_pages === page ) ) ||
								// Check pagination with random order.
								( is_rand &&
									( ! result.found_resumes ||
										1 === result.max_num_pages ) )
							) {
								$( '.load_more_resumes', target ).hide();
							} else {
								$( '.load_more_resumes', target )
									.show()
									.data( 'page', page );
							}
							$( '.load_more_resumes', target ).removeClass(
								'loading'
							);
							$( 'li.resume', results ).css(
								'visibility',
								'visible'
							);
						}

						$( results ).removeClass( 'loading' );

						target.triggerHandler( 'updated_results', result );
					} catch ( err ) {
						//console.log(err);
					}
				}
			},
		} );
	} );

	$(
		'#search_keywords, #search_location, #search_categories, #search_skills'
	)
		.change( function () {
			const target = $( this ).closest( 'div.resumes' );

			target.triggerHandler( 'update_results', [ 1, false ] );
		} )
		.change();

	$( '.resume_filters' ).on( 'click', '.reset', function () {
		const target = $( this ).closest( 'div.resumes' );
		const form = $( this ).closest( 'form' );

		form.find( ':input[name="search_keywords"]' )
			.not( ':input[type="hidden"]' )
			.val( '' );
		form.find( ':input[name="search_location"]' )
			.not( ':input[type="hidden"]' )
			.val( '' );
		form.find( ':input[name^="search_categories"]' )
			.not( ':input[type="hidden"]' )
			.val( 0 )
			.trigger( 'chosen:updated' )
			.trigger( 'change.select2' );
		form.find( ':input[name="search_skills"]' )
			.not( ':input[type="hidden"]' )
			.val( '' );

		target.triggerHandler( 'reset' );
		target.triggerHandler( 'update_results', [ 1, false ] );

		return false;
	} );

	$( '.load_more_resumes' ).click( function () {
		const target = $( this ).closest( 'div.resumes' );
		let page = $( this ).data( 'page' );

		if ( ! page ) {
			page = 1;
		} else {
			page = parseInt( page );
		}

		$( this ).data( 'page', page + 1 );

		target.triggerHandler( 'update_results', [ page + 1, true ] );

		return false;
	} );

	$( 'div.resumes' ).on( 'click', '.job-manager-pagination a', function () {
		const target = $( this ).closest( 'div.resumes' );
		const page = $( this ).data( 'page' );

		target.triggerHandler( 'update_results', [ page, false ] );

		$( 'html' ).animate(
			{
				scrollTop: target.offset().top - 32,
			},
			600
		);

		return false;
	} );

	if ( $.isFunction( $.fn.select2 ) ) {
		const select2_args = {
			allowClear: true,
			minimumResultsForSearch: 10,
		};
		if ( 1 === parseInt( resume_manager_ajax_filters.is_rtl, 10 ) ) {
			select2_args.dir = 'rtl';
		}
		$( 'select[name^="search_categories"]:visible' ).select2(
			select2_args
		);
	} else if ( $.isFunction( $.fn.chosen ) ) {
		$( 'select[name^="search_categories"]:visible' ).chosen();
	}
} );
