(function($){

	FLBuilder.registerModuleHelper('header-bar', {
		
		init: function()
		{
			var form = $('.fl-builder-settings');
			
			form.find('input[name=search_icon]').on('change', this._toggleDataIco);
			form.find('input[name=messages_icon]').on('change', this._toggleDataIco);
			form.find('input[name=notifications_icon]').on('change', this._toggleDataIco);
			form.find('input[name=cart_icon]').on('change', this._toggleDataIco);
		},

		_toggleDataIco: function()
		{
			var form = $('.fl-builder-settings');
			var $search_icon = form.find('input[name=search_icon]').val();
			var $messages_icon = form.find('input[name=messages_icon]').val();
			var $notifications_icon = form.find('input[name=notifications_icon]').val();
			var $cart_icon = form.find('input[name=cart_icon]').val();

			$( '.site-header--beaver-builder' ).data('search-icon', $search_icon);
			$( '.site-header--beaver-builder' ).data('messages-icon', $messages_icon);
			$( '.site-header--beaver-builder' ).data('notifications-icon', $notifications_icon);
			$( '.site-header--beaver-builder' ).data('cart-icon', $cart_icon);

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

			iconReplace( '.site-header--beaver-builder .header-search-link i', 'bb-icon-search', 'search-icon' );
			iconReplace( '.site-header--beaver-builder #header-messages-dropdown-elem .notification-link i', 'bb-icon-inbox', 'messages-icon' );
			iconReplace( '.site-header--beaver-builder #header-notifications-dropdown-elem .notification-link i', 'bb-icon-bell', 'notifications-icon' );
			iconReplace( '.site-header--beaver-builder a.header-cart-link i', 'bb-icon-shopping-cart', 'cart-icon' );
		}

	});

})(jQuery);