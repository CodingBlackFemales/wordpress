( function( $ ) {
	var WidgetHeaderBarHandler = function( $scope, $ ) {
		
		$('.site-header--elementor .header-search-link').on('click', function (e) {
			e.preventDefault();
			$( this ).closest('.elementor-widget-header-bar').addClass('search-visible-el');
			if ( ! navigator.userAgent.match(/(iPod|iPhone|iPad)/)) {
				setTimeout(function () {
					$('body').find('.header-search-wrap--elementor .search-field-top').focus();
				}, 90);
			}
		});

		// Hide Search
		$('.site-header--elementor .close-search').on('click', function (e) {
			e.preventDefault();
			$( this ).closest('.elementor-widget-header-bar').removeClass('search-visible-el');
			$('.header-search-wrap--elementor input.search-field-top').val('');
		});

		$(document).click(function (e) {
			var container = $('.header-search-wrap--elementor, .site-header--elementor .header-search-link');
			if (!container.is(e.target) && container.has(e.target).length === 0) {
				$('body').removeClass('search-visible-el');
			}
		});

		$(document).keyup(function (e) {
			if (e.keyCode === 27) {
				$('body').removeClass('search-visible-el');
			}
		});

		//Replace icons
		function iconReplace( iSelector, iClass, data ) {
			var iVar = $( iSelector );
			var dataSearchValue = $( '.site-header--elementor' ).data( data );
			if ( $( '.site-header--elementor' ).data( data ) !== '' ) {
				iVar.removeClass( iClass );
				iVar.addClass( dataSearchValue );
			} else {
				iVar.addClass( iClass );
			}
			$( '.site-header--elementor' ).removeClass('icon-fill-in');
		}

		iconReplace( '.site-header--elementor .header-search-link i', 'bb-icon-search', 'search-icon' );
		iconReplace( '.site-header--elementor #header-messages-dropdown-elem .notification-link i', 'bb-icon-inbox', 'messages-icon' );
		iconReplace( '.site-header--elementor #header-notifications-dropdown-elem .notification-link i', 'bb-icon-bell', 'notifications-icon' );
		iconReplace( '.site-header--elementor a.header-cart-link i', 'bb-icon-shopping-cart', 'cart-icon' );
		iconReplace( 'body:not(.bb-dark-theme) .site-header--elementor a#bb-toggle-theme i', 'bb-icon-moon', 'dark-icon' );
		iconReplace( '.bb-dark-theme .site-header--elementor a#bb-toggle-theme i', 'bb-icon-sun', 'dark-icon' );
		iconReplace( '.site-header--elementor a.header-maximize-link i', 'bb-icon-expand', 'sidebartoggle-icon' );
		iconReplace( '.site-header--elementor a.header-minimize-link i', 'bb-icon-merge', 'sidebartoggle-icon' );


		// NB - Duplicated as per theme main.js sidePanel()
		// whenever we hover over a menu item that has a submenu
		$('.user-wrap li.parent, .user-wrap .menu-item-has-children').on('mouseover', function() {
			var $menuItem = $(this),
				$submenuWrapper = $('> .wrapper', $menuItem);

			// grab the menu item's position relative to its positioned parent
			var menuItemPos = $menuItem.position();

			// place the submenu in the correct position relevant to the menu item
			$submenuWrapper.css({
				top: menuItemPos.top
			});
		});

		// Fix user mention position
		$(document).ready(function() {
			var mention = false;
			$( '.site-header--elementor' ).each(function(){
				var $this = $( this );
				var $mention_suggestion = $( this ).find( '.bp-suggestions-mention' );
				var $user_mention = $( this ).find( '.user-mention' )

				if ( $mention_suggestion.length ) {	
					$mention_suggestion.appendTo( $user_mention );
				}
	
			});
		});

		// User sub menu dropdown on smaller screens
		$( '.site-header--elementor .user-wrap > .sub-menu .ab-sub-wrapper' ).on( 'click', function (e) {
			var window_width = $( window ).width();
			if ( window_width < 380 ) {
				$( this ).find( '.ab-submenu' ).slideToggle();
				$( this ).parent().siblings().find( '.ab-submenu' ).slideUp();
        		return false;
			}
		});

		$( '.elementor-widget-header-bar .user-link').on('click', function (e) {
			$( '.elementor-widget-header-bar' ).not( $( this ).closest( '.elementor-widget-header-bar' ) ).removeClass( 'is-active' );
			$( this ).closest( '.elementor-widget-header-bar' ).addClass( 'is-active' );
		});

	};

	var WidgetBBP_MembersHandler = function( $scope, $ ) {
		
		$('.bb-members .bb-members__tab').on('click', function (e) {
			e.preventDefault();
			var $tabItem = $(this);
			var $mType = $tabItem.data('type');
			var $bbContainer = $tabItem.closest('.bb-members')
			$('.bb-members .bb-members__tab').removeClass('selected');
			$tabItem.toggleClass('selected');

			$bbContainer.find('.bb-members-list').removeClass('active');
			$bbContainer.find('.bb-members-list--' + $mType + '').addClass('active');
		});

	};

	var WidgetBBP_Profile_CompletionHandler = function( $scope, $ ) {

		var readyStateProfile = true;

		$('.profile_bit').click(function(event) {
			event.stopPropagation();
			
			if ( !$( this ).find( '.profile_bit__details' ).is(':visible') && readyStateProfile ) {
				$( this ).find( '.profile_bit__details' ).slideDown();
				$( this ).addClass('active');
				setTimeout( function(){
					readyStateProfile = false;
				},300);
			} else if( $( this ).find( '.profile_bit__details' ).is(':visible') && !readyStateProfile ) {
				$( this ).find( '.profile_bit__details' ).slideUp();
				$( this ).removeClass('active');
				setTimeout( function(){
					readyStateProfile = true;
				},300);
			}
		});

		$('.profile_bit').hover(function(){
			if ( ! $( this ).find( '.profile_bit__details' ).is(':visible') && readyStateProfile ) {
				$( this ).find( '.profile_bit__details' ).slideDown();
				$( this ).addClass('active');
				setTimeout( function(){
					readyStateProfile = false;
				},300);
			}
		}, function(){
			if ($( this ).find( '.profile_bit__details' ).is(':visible') && !readyStateProfile ) {
				$( this ).find( '.profile_bit__details' ).slideUp();
				$( this ).removeClass('active');
				setTimeout( function(){
					readyStateProfile = true;
				},300);
			}
		});

	};

	var WidgetLd_ActivityHandler = function( $scope, $ ) {

		$('.bb-ldactivity').each(function(){

			var $slickIns = $(this);
			var $slickRun = $slickIns.find('.bb-la.bb-la--isslick');
			var $switchDots = $slickIns.find('.bb-la').data('dots');

			$slickRun.not('.slick-initialized').slick({
				infinite: true,
				slidesToShow: 1,
				slidesToScroll: 1,
				dots: $switchDots,
				fade: !0,
				cssEase: 'linear',
				prevArrow: '<a class="bb-slide-prev"><i class="bb-icon-l bb-icon-angle-left"></i></a>',
				nextArrow: '<a class="bb-slide-next"><i class="bb-icon-l bb-icon-angle-right"></i></a>',
			});

		});

	};

	var WidgetBB_TabsHandler = function( $scope, $ ) {

		$('.bb-tabs').each(function(){
			
			var $slickIns = $(this);
			var $slickRun = $slickIns.find('.bb-tabs__run');
			var $slickNav = $slickIns.find('.bb-tabs__nav');
			var $tabsNum = $slickIns.find('.bb-tabs__nav').data('num');
			var $switchNav = $slickIns.find('.bb-tabs__run').data('nav');
			var $switchDots = $slickIns.find('.bb-tabs__run').data('dots');

			$slickRun.not('.slick-initialized').slick({
				slidesToShow: 1,
				slidesToScroll: 1,
				arrows: $switchNav,
				dots: $switchDots,
				fade: true,
				asNavFor: $slickNav,
				prevArrow: '<a class="bb-slide-prev"><i class="bb-icon-l bb-icon-arrow-left"></i></a>',
				nextArrow: '<a class="bb-slide-next"><i class="bb-icon-l bb-icon-arrow-right"></i></a>',
				rtl: false,
			});

			$slickNav.not('.slick-initialized').slick({
				slidesToShow: $tabsNum,
				slidesToScroll: 1,
				asNavFor: $slickRun,
				dots: true,
				focusOnSelect: true,
				variableWidth: true,
				rtl: false,
			});

		});

	};

	var WidgetBB_GalleryHandler = function( $scope, $ ) {

		$('.bb-gallery').each(function(){
			
			var $slickIns = $(this);
			var $slickRun = $slickIns.find('.bb-gallery__run');
			var $switchNav = $slickIns.find('.bb-gallery__run').data('nav');
			var $switchDots = $slickIns.find('.bb-gallery__run').data('dots');
			var $switchLoop = $slickIns.find('.bb-gallery__run').data('loop');
			var $slMargin = $slickIns.find('.bb-gallery__run').data('margin');

			$slickRun.not('.slick-initialized').slick({
				centerMode: true,
				centerPadding: $slMargin,
				slidesToShow: 1,
				prevArrow: '<a class="bb-slide-prev"><i class="bb-icon-l bb-icon-angle-left"></i></a>',
				nextArrow: '<a class="bb-slide-next"><i class="bb-icon-l bb-icon-angle-right"></i></a>',
				arrows: $switchNav,
				dots: $switchDots,
				infinite: $switchLoop,
				rtl: false,
				responsive: [
					{
						breakpoint: 768,
						settings: {
							centerPadding: '0px',
						}
					},
				],
			});

		});

		$('.bb-gallery__image.is-video').on('click', function (e) {
			e.preventDefault();
			var $imgOverLay = $(this);
			var $mediaVideo = $imgOverLay.find('.bb-gallery__video');
			var $slideBody = $imgOverLay.closest('.bb-gallery__block').find('.bb-gallery__body');
			$imgOverLay.addClass('is-active');
			$imgOverLay.find('.media-container').fadeTo( 'slow', 0 );
			$mediaVideo.css({'z-index': 10});
			$slideBody.fadeTo( 'slow', 0 );
			$slideBody.css({'z-index': 5});
		});

		$('.bb-gallery').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
			var $slickCurrent = $('.slick-current');
			var $slickBody = $slickCurrent.find('.bb-gallery__body');
			player = $slickCurrent.find('iframe').get(0);
			slideType = $slickCurrent.find('.bb-gallery__image').attr('class').split(' ')[0];
      
			if (slideType == 'vimeo') {
			  command = {
				'method': 'pause',
				'value': 'true'
			  };
			} else {
			  command = {
				'event': 'command',
				'func': 'pauseVideo'
			  };
			}

			if (player != undefined) {
				player.contentWindow.postMessage(JSON.stringify(command), '*');
			}

			$('.slick-current iframe').attr('src', $('.slick-current iframe').attr('src'));
			$slickCurrent.find('.bb-gallery__image').removeClass('is-active');
			$slickCurrent.find('.media-container').fadeTo( 'slow', 1 );
			$slickCurrent.find('.bb-gallery__video').css({'z-index': 5});
			$slickBody.fadeTo( 'slow', 1 );
			$slickBody.css({'z-index': 15});
		});

	};

	var WidgetBB_ReviewHandler = function( $scope, $ ) {

		$('.bb-review__image-overlay').on('click', function (e) {
			e.preventDefault();
			var $imgOverLay = $(this);
			var $mediaVideo = $imgOverLay.closest('.media-video');
			var $video = $mediaVideo.find('iframe.elementor-video-iframe');
			$imgOverLay.remove();
			$video[0].src += "&autoplay=1";
		});

	};

	var WidgetBBP_ActivityHandler = function( $scope, $ ) {
		$('.elementor-activity-widget li.activity-item').each(function(){
			var _findtext  = $( this ).find( '.activity-inner > p' ).removeAttr( 'br' ).removeAttr( 'a' ).text();
			var	_url       = '',
				_newString = '',
				startIndex = '',
				_is_exist  = 0;
			if ( 0 <= _findtext.indexOf( 'http://' )) {
				startIndex = _findtext.indexOf( 'http://' );
				_is_exist  = 1;
			} else if (0 <= _findtext.indexOf( 'https://' )) {
				startIndex = _findtext.indexOf( 'https://' );
				_is_exist  = 1;
			} else if (0 <= _findtext.indexOf( 'www.' )) {
				startIndex = _findtext.indexOf( 'www' );
				_is_exist  = 1;
			}
			if ( 1 === _is_exist ) {
				for ( var i = startIndex; i < _findtext.length; i ++ ) {
					if ( _findtext[i] === ' ' || _findtext[i] === '\n' ) {
						break;
					} else {
						_url += _findtext[i];
					}
				}

				if ( _url !== '' ) {
					_newString = $.trim( _findtext.replace( _url, '' ) );
				}
				if (0 >= _newString.length) {
					if ( $( this ).find( '.activity-inner > .activity-link-preview-container ' ).length || $( this ).hasClass( 'wp-link-embed' ) ) {
						$( this ).find( '.activity-inner > p:first a' ).hide();
					}
				}
			}
		});

		//Replace dummy image with original image by faking scroll event
		$(document).ready(function() {
			$( window ).scroll();
			$( '.bbel-list-flow' ).scroll(function() {
				$( window ).scroll();
			});
		});

	};

	var WidgetBB_GroupsHandler = function( $scope, $ ) {
		
		$('.bb-groups .bb-groups__tab').on('click', function (e) {
			e.preventDefault();
			var $tabItem = $(this);
			var $gType = $tabItem.data('type');
			var $bbContainer = $tabItem.closest('.bb-groups')
			$('.bb-groups .bb-groups__tab').removeClass('selected');
			$tabItem.toggleClass('selected');

			$bbContainer.find('.bb-groups-list').removeClass('active');
			$bbContainer.find('.bb-groups-list--' + $gType + '').addClass('active');
		});

	};

	//Fix for floating buttons in "Learndash Activity" and "Forum Activity"
	var ElementorClasses = function( $scope, $ ) {
		$( '.bb-ldactivity' ).closest( 'section.elementor-element' ).addClass('bb-ldactivity-main-section');
		$( '.bb-forums-activity-wrapper' ).closest( 'section.elementor-element' ).addClass('bb-forums-activity-main-section');
		$( '.elementor-heading-title' ).each( function() {
			if( !$( this ).closest( '.elementor-element' ).siblings().length ) {
				$( this ).closest( 'section.elementor-element' ).addClass('elementor-heading-title-main-section');
			}
		});
		$( '.bb-ldactivity' ).closest( 'section.elementor-element' ).prev('.elementor-heading-title-main-section').addClass('elementor-max-50');
		$( '.bb-forums-activity-wrapper' ).closest( 'section.elementor-element' ).prev('.elementor-heading-title-main-section').addClass('elementor-max-50');
	};

	//Fix double dropdown issue by hiding dropdown on scroll if header is sticky
	$( window ).scroll( function() {
		$( '.elementor-sticky:not(.elementor-sticky--active) .cart-wrap.header-cart-link-wrap.selected' ).removeClass( 'selected' );
		$( '.elementor-sticky:not(.elementor-sticky--active) #header-messages-dropdown-elem.selected' ).removeClass( 'selected' );
		$( '.elementor-sticky:not(.elementor-sticky--active) #header-notifications-dropdown-elem.selected' ).removeClass( 'selected' );
	});



	// Make sure you run this code under Elementor..
	$( window ).on( 'elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/header-bar.default', WidgetHeaderBarHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bbp-members.default', WidgetBBP_MembersHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bbp-profile-completion.default', WidgetBBP_Profile_CompletionHandler );
		// elementorFrontend.hooks.addAction( 'frontend/element_ready/ld-activity.default', WidgetLd_ActivityHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/global', WidgetLd_ActivityHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bb-tabs.default', WidgetBB_TabsHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bb-gallery.default', WidgetBB_GalleryHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bb-review.default', WidgetBB_ReviewHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bbp-activity.default', WidgetBBP_ActivityHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/bb-groups.default', WidgetBB_GroupsHandler );
		elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', ElementorClasses );

	} );
} )( jQuery );
