(function($) {

	$('.site-header--beaver-builder .header-search-link').on('click', function (e) {
		e.preventDefault();
		$('body').toggleClass('search-visible-fl');
		if ( ! navigator.userAgent.match(/(iPod|iPhone|iPad)/)) {
			setTimeout(function () {
				$('body').find('.header-search-wrap--beaver-builder .search-field-top').focus();
			}, 90);
		}
	});

	// Hide Search
	$('.site-header--beaver-builder .close-search').on('click', function (e) {
		e.preventDefault();
		$('body').removeClass('search-visible-fl');
		$('.header-search-wrap--beaver-builder input.search-field-top').val('');
	});

	$(document).click(function (e) {
		var container = $('.header-search-wrap--beaver-builder, .site-header--beaver-builder .header-search-link');
		if (!container.is(e.target) && container.has(e.target).length === 0) {
			$('body').removeClass('search-visible-fl');
		}
	});

	$(document).keyup(function (e) {
		if (e.keyCode === 27) {
			$('body').removeClass('search-visible-fl');
		}
	});

	// Fix user mention position
	$(document).ready(function() {
		if ( $( '.site-header--beaver-builder .bp-suggestions-mention' ).length ) {
			var userMentionText = $( '.site-header--beaver-builder .bp-suggestions-mention' ).text();
			$( '.site-header--beaver-builder .sub-menu .user-mention' ).append( document.createTextNode( userMentionText ) );
			$( '.site-header--beaver-builder .bp-suggestions-mention' ).hide();
		}
	});

	//Replace icons
	function iconReplace( iSelector, iClass, data ) {
		var iVar = $( iSelector );
		var dataSearchValue = $( '.site-header--beaver-builder' ).data( data );
		if ( $( '.site-header--beaver-builder' ).data( data ) !== '' ) {
			iVar.removeClass();
			iVar.addClass( dataSearchValue );
		} else {
			iVar.addClass( iClass );
		}
	}

	$(document).ready(function() {
		iconReplace( '.site-header--beaver-builder .header-search-link i', 'bb-icon-search', 'search-icon' );
		iconReplace( '.site-header--beaver-builder #header-messages-dropdown-elem .notification-link i', 'bb-icon-inbox', 'messages-icon' );
		iconReplace( '.site-header--beaver-builder #header-notifications-dropdown-elem .notification-link i', 'bb-icon-bell', 'notifications-icon' );
		iconReplace( '.site-header--beaver-builder a.header-cart-link i', 'bb-icon-shopping-cart', 'cart-icon' );
		setTimeout(function () {
			iconReplace( '.site-header--beaver-builder a.header-cart-link i', 'bb-icon-shopping-cart', 'cart-icon' );
		}, 300);
		setTimeout(function () {
			iconReplace( '.site-header--beaver-builder a.header-cart-link i', 'bb-icon-shopping-cart', 'cart-icon' );
		}, 600);
	});

})(jQuery);
