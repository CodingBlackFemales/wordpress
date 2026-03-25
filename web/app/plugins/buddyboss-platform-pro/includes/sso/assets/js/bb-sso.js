/* jshint browser: true */
/* global bbSSOVars, containerId, formId, separatorPosition, orString, BroadcastChannel */
/* @version 1.0.0 */

/**
 * Used when Cross-Origin-Opener-Policy blocked the access to the opener.
 * We can't have a reference to the opened windows,
 * so we should attempt to refresh only the windows that have opened popups.
 */
window._bbSSOHasOpenedPopup = false;
window._bbSSOWebViewNoticeElement = null;

window.BBSSOPopup = function ( url, title, w, h ) {
	var userAgent    = navigator.userAgent,
	    mobile       = function () {
		    return /\b(iPhone|iP[ao]d)/.test( userAgent ) ||
		           /\b(iP[ao]d)/.test( userAgent ) ||
		           /Android/i.test( userAgent ) ||
		           /Mobile/i.test( userAgent );
	    },
	    screenX      = window.screenX !== undefined ? window.screenX : window.screenLeft,
	    screenY      = window.screenY !== undefined ? window.screenY : window.screenTop,
	    outerWidth   = window.outerWidth !== undefined ? window.outerWidth : document.documentElement.clientWidth,
	    outerHeight  = window.outerHeight !== undefined ? window.outerHeight : document.documentElement.clientHeight - 22,
	    targetWidth  = mobile() ? null : w,
	    targetHeight = mobile() ? null : h,
	    left         = parseInt( screenX + (
		    outerWidth - targetWidth
	    ) / 2, 10 ),
	    right        = parseInt( screenY + (
		    outerHeight - targetHeight
	    ) / 2.5, 10 ),
	    features     = [];
	if ( targetWidth !== null ) {
		features.push( 'width=' + targetWidth );
	}
	if ( targetHeight !== null ) {
		features.push( 'height=' + targetHeight );
	}
	features.push( 'left=' + left );
	features.push( 'top=' + right );
	features.push( 'scrollbars=1' );

	var newWindow = window.open( url, title, features.join( ',' ) );

	if ( window.focus ) {
		newWindow.focus();
	}

	window._bbSSOHasOpenedPopup = true;

	return newWindow;
};

// Adding overlay after redirecting to the social login page.
window._bbssoDOMReady( function () {
	var scriptOptionsData = JSON.parse( bbSSOVars.scriptOptions );
	window.bbSSORedirect  = function ( url ) {
		if ( scriptOptionsData._redirectOverlay ) {
			var overlay               = document.createElement( 'div' );
			overlay.id                = 'bb-sso-redirect-overlay';
			var overlayHTML           = '';
			var overlayContainer      = '<div id=\'bb-sso-redirect-overlay-container\'>',
			    overlayContainerClose = '</div>',
			    overlaySpinner        = '<div id=\'bb-sso-redirect-overlay-spinner\'></div>',
			    overlayTitle          = '<p id=\'bb-sso-redirect-overlay-title\'>' + scriptOptionsData._localizedStrings.redirect_overlay_title + '</p>',
			    overlayText           = '<p id=\'bb-sso-redirect-overlay-text\'>' + scriptOptionsData._localizedStrings.redirect_overlay_text + '</p>';

			switch ( scriptOptionsData._redirectOverlay ) {
				case 'overlay-only':
					break;
				case 'overlay-with-spinner':
					overlayHTML = overlayContainer + overlaySpinner + overlayContainerClose;
					break;
				default:
					overlayHTML = overlayContainer + overlaySpinner + overlayTitle + overlayText + overlayContainerClose;
					break;
			}

			overlay.insertAdjacentHTML( 'afterbegin', overlayHTML );
			document.body.appendChild( overlay );
		}

		window.location = url;
	};

	var targetWindow = scriptOptionsData._targetWindow || 'prefer-popup',
	    lastPopup    = false;


	var buttonLinks = document.querySelectorAll( ' a[data-plugin="bb-sso"][data-action="connect"], a[data-plugin="bb-sso"][data-action="link"]' );
	buttonLinks.forEach( function ( buttonLink ) {
		buttonLink.addEventListener( 'click', function ( e ) {
			if ( lastPopup && ! lastPopup.closed ) {
				e.preventDefault();
				lastPopup.focus();
			} else {

				var href    = this.href,
				    success = false;
				if ( href.indexOf( '?' ) !== - 1 ) {
					href += '&';
				} else {
					href += '?';
				}

				var redirectTo = this.dataset.redirect;
				if ( redirectTo === 'current' ) {
					href += 'redirect=' + encodeURIComponent( window.location.href ) + '&';
				} else if ( redirectTo && redirectTo !== '' ) {
					href += 'redirect=' + encodeURIComponent( redirectTo ) + '&';
				}

				if ( targetWindow !== 'prefer-same-window' && checkWebView() ) {
					targetWindow = 'prefer-same-window';
				}

				if ( targetWindow === 'prefer-popup' ) {
					lastPopup = window.BBSSOPopup( href + 'display=popup', 'bb-sso-social-connect', this.dataset.popupwidth, this.dataset.popupheight );
					if ( lastPopup ) {
						success = true;
						e.preventDefault();
					}
				} else if ( targetWindow === 'prefer-new-tab' ) {
					var newTab = window.open( href + 'display=popup', '_blank' );
					if ( newTab ) {
						if ( window.focus ) {
							newTab.focus();
						}
						success                     = true;
						window._bbSSOHasOpenedPopup = true;
						e.preventDefault();
					}
				}

				if ( ! success ) {
					window.location = href;
					e.preventDefault();
				}
			}
		} );
	} );
	var buttonCountChanged = false;

	var googleLoginButtons = document.querySelectorAll( ' a[data-plugin="bb-sso"][data-provider="google"]' );
	if ( googleLoginButtons.length && checkWebView() ) {
		googleLoginButtons.forEach( function ( googleLoginButton ) {
			googleLoginButton.remove();
			buttonCountChanged = true;
		} );
	}

	var facebookLoginButtons = document.querySelectorAll( ' a[data-plugin="bb-sso"][data-provider="facebook"]' );
	if ( facebookLoginButtons.length && checkWebView() && /Android/.test( window.navigator.userAgent ) && ! isAllowedWebViewForUserAgent( 'facebook' ) ) {
		facebookLoginButtons.forEach( function ( facebookLoginButton ) {
			facebookLoginButton.remove();
			buttonCountChanged = true;
		} );
	}

	var separators = document.querySelectorAll( 'div.bb-sso-separator' );
	if ( buttonCountChanged && separators.length ) {
		separators.forEach( function ( separator ) {
			var separatorParentNode = separator.parentNode;
			if ( separatorParentNode ) {
				var separatorButtonContainer = separatorParentNode.querySelector( 'div.bb-sso-container-buttons' );
				if ( separatorButtonContainer && ! separatorButtonContainer.hasChildNodes() ) {
					separator.remove();
				}
			}
		} );
	}
} );

// For add separator in the login/register form.
window._bbssoDOMReady( function () {
	if ( 'undefined' === typeof containerId ) {
		return;
	}
	var container = document.getElementById( containerId );
	if ( ! container ) {
		return;
	}

	var form = document.querySelector( formId );

	if ( ! form ) {
		form = container.closest( 'form' );
		if ( ! form ) {
			form = container.parentNode;
		}
	}

	if ( container && form ) {
		// Create the clear div and insert at the top or bottom depending on position.
		var clear = document.createElement( 'div' );
		clear.classList.add( 'bb-sso-clear' );

		if ( 'above-separator' === separatorPosition ) {
			form.insertBefore( clear, form.firstChild );
		} else {
			form.insertBefore( clear, null );
		}

		// Remove existing separator if any.
		var separatorToRemove = container.querySelector( '.bb-sso-separator' );
		if ( separatorToRemove ) {
			separatorToRemove.remove();
		}

		// Create the separator element.
		var separator = document.createElement( 'div' );
		separator.classList.add( 'bb-sso-separator' );
		separator.innerHTML = orString;

		// Insert separator above or below based on the position.
		if ( 'above-separator' === separatorPosition ) {
			container.appendChild( separator );
		} else {
			container.insertBefore( separator, container.firstChild );
		}
	}

	var innerContainer = container.querySelector( '.bb-sso-container' );
	if ( innerContainer ) {
		var layoutClass = (
			separatorPosition === 'above-separator'
		) ? 'bb-sso-container-buddypress-register-layout-above-separator' : 'bb-sso-container-buddypress-login-layout-below-separator';

		innerContainer.classList.add( layoutClass );
		innerContainer.style.display = 'block';
	}

	// Move the container to the correct position in the form.
	if ( 'above-separator' === separatorPosition ) {
		form.insertBefore( container, form.firstChild );
	} else {
		form.appendChild( container );
	}
} );

var isWebView = null;

function checkWebView() {
	if ( isWebView === null ) {

		var options        = {},
		    nav            = window.navigator || {},
		    ua             = nav.userAgent || '',
		    os             = _detectOS( ua ),
		    browser        = _detectBrowser( ua ),
		    browserVersion = _detectBrowserVersion( ua, browser );

		isWebView = _isWebView( ua, os, browser, browserVersion, options );
	}

	return isWebView;
}

function _detectOS( ua ) {
	if ( /Android/.test( ua ) ) {
		return 'Android';
	} else if ( /iPhone|iPad|iPod/.test( ua ) ) {
		return 'iOS';
	} else if ( /Windows/.test( ua ) ) {
		return 'Windows';
	} else if ( /Mac OS X/.test( ua ) ) {
		return 'Mac';
	} else if ( /CrOS/.test( ua ) ) {
		return 'Chrome OS';
	} else if ( /Firefox/.test( ua ) ) {
		return 'Firefox OS';
	}
	return '';
}

function _detectBrowser( ua ) {
	var android = /Android/.test( ua );

	if ( /Opera Mini/.test( ua ) || / OPR/.test( ua ) || / OPT/.test( ua ) ) {
		return 'Opera';
	} else if ( /CriOS/.test( ua ) ) {
		return 'Chrome for iOS';
	} else if ( /Edge/.test( ua ) ) {
		return 'Edge';
	} else if ( android && /Silk\//.test( ua ) ) {
		return 'Silk';
	} else if ( /Chrome/.test( ua ) ) {
		return 'Chrome';
	} else if ( /Firefox/.test( ua ) ) {
		return 'Firefox';
	} else if ( android ) {
		return 'AOSP';
	} else if ( /MSIE|Trident/.test( ua ) ) {
		return 'IE';
	} else if ( /Safari\//.test( ua ) ) {
		return 'Safari';
	} else if ( /AppleWebKit/.test( ua ) ) {
		return 'WebKit';
	}
	return '';
}

function _detectBrowserVersion( ua, browser ) {
	if ( browser === 'Opera' ) {
		return /Opera Mini/.test( ua ) ? _getVersion( ua, 'Opera Mini/' ) :
			/ OPR/.test( ua ) ? _getVersion( ua, ' OPR/' ) :
				_getVersion( ua, ' OPT/' );
	} else if ( browser === 'Chrome for iOS' ) {
		return _getVersion( ua, 'CriOS/' );
	} else if ( browser === 'Edge' ) {
		return _getVersion( ua, 'Edge/' );
	} else if ( browser === 'Chrome' ) {
		return _getVersion( ua, 'Chrome/' );
	} else if ( browser === 'Firefox' ) {
		return _getVersion( ua, 'Firefox/' );
	} else if ( browser === 'Silk' ) {
		return _getVersion( ua, 'Silk/' );
	} else if ( browser === 'AOSP' ) {
		return _getVersion( ua, 'Version/' );
	} else if ( browser === 'IE' ) {
		return /IEMobile/.test( ua ) ? _getVersion( ua, 'IEMobile/' ) :
			/MSIE/.test( ua ) ? _getVersion( ua, 'MSIE ' )
				:
				_getVersion( ua, 'rv:' );
	} else if ( browser === 'Safari' ) {
		return _getVersion( ua, 'Version/' );
	} else if ( browser === 'WebKit' ) {
		return _getVersion( ua, 'WebKit/' );
	}
	return '0.0.0';
}

function _getVersion( ua, token ) {
	try {
		return _normalizeSemverString( ua.split( token )[1].trim().split( /[^\w\.]/ )[0] );
	} catch ( o_O ) {
	}
	return '0.0.0';
}

function _normalizeSemverString( version ) {
	var ary = version.split( /[\._]/ );
	return (
		       parseInt( ary[0], 10 ) || 0
	       ) + '.' +
	       (
		       parseInt( ary[1], 10 ) || 0
	       ) + '.' +
	       (
		       parseInt( ary[2], 10 ) || 0
	       );
}

function _isWebView( ua, os, browser, version, options ) {
	switch ( os + browser ) {
		case 'iOSSafari':
			return false;
		case 'iOSWebKit':
			return _isWebView_iOS( options );
		case 'AndroidAOSP':
			return false;
		case 'AndroidChrome':
			return parseFloat( version ) >= 42 ? /; wv/.test( ua ) : /\d{2}\.0\.0/.test( version ) ? true : _isWebView_Android( options );
	}
	return false;
}

function _isWebView_iOS( options ) {
	var document = (
		window.document || {}
	);

	if ( 'WEB_VIEW' in options ) {
		return options.WEB_VIEW;
	}
	return ! (
		'fullscreenEnabled' in document || 'webkitFullscreenEnabled' in document || false
	);
}

function _isWebView_Android( options ) {
	if ( 'WEB_VIEW' in options ) {
		return options.WEB_VIEW;
	}
	return ! (
		'requestFileSystem' in window || 'webkitRequestFileSystem' in window || false
	);
}

function isAllowedWebViewForUserAgent( provider ) {
	var facebookAllowedWebViews = [
		'Instagram',
		'FBAV',
		'FBAN'
	];
	var whitelist               = [];

	if ( provider && provider === 'facebook' ) {
		whitelist = facebookAllowedWebViews;
	}

	var nav = window.navigator || {},
	    ua  = nav.userAgent || '';

	if ( whitelist.length && ua.match( new RegExp( whitelist.join( '|' ) ) ) ) {
		return true;
	}

	return false;
}

window.bbSSOShowMessage = function ( message, type ) {
	if ( ! message ) {
		return;
	}
	// Check for an existing error container by class name within the bb-hello-sso container.
	var parentContainer = document.querySelector( '.bb-hello-sso' );
	var ssoClassName = 'bb-hello-success';
	if ( 'error' === type ) {
		ssoClassName = 'bb-hello-error';
	}
	var errorContainers = parentContainer ? parentContainer.getElementsByClassName( ssoClassName ) : [];
	var errorContainer  = errorContainers.length > 0 ? errorContainers[0] : null;

	// If the container doesn't exist, create it.
	if ( ! errorContainer ) {
		// Create the error container div
		errorContainer           = document.createElement( 'div' );
		errorContainer.className = ssoClassName;

		// Create the icon element.
		var iconName = 'bb-icon-rf bb-icon-check';
		if ( 'error' === type ) {
			iconName = 'bb-icon-rf bb-icon-exclamation';
		}
		var icon       = document.createElement( 'i' );
		icon.className = iconName;

		// Append the icon to the error container.
		errorContainer.appendChild( icon );

		// Create a text node with the error message.
		var textNode = document.createTextNode( ' ' + message );
		errorContainer.appendChild( textNode );
		errorContainer.style.display = 'none';

		// Insert the error container before the form fields.
		if ( parentContainer ) {
			var formFields = parentContainer.querySelector( '.form-fields' );
			if ( formFields ) {
				formFields.parentNode.insertBefore( errorContainer, formFields );
			}
		}
	}

	// Show the error container.
	errorContainer.style.display = 'flex';
};

/**
 * Cross-Origin-Opener-Policy blocked the access to the opener
 */
if ( typeof BroadcastChannel === 'function' ) {
	var _bbSSOLoginBroadCastChannel       = new BroadcastChannel( 'bb_sso_login_broadcast_channel' );
	_bbSSOLoginBroadCastChannel.onmessage = function ( event ) {
		if ( window && window._bbSSOHasOpenedPopup && event.data && event.data.action === 'redirect' ) {
			window._bbSSOHasOpenedPopup = false;

			var url = event.data && event.data.href ? event.data.href : null;
			_bbSSOLoginBroadCastChannel.close();

			if ( typeof window.bbSSORedirect === 'function' ) {
				window.bbSSORedirect( url );
			} else {
				window.opener.location = url;
			}
		}
	};
}
