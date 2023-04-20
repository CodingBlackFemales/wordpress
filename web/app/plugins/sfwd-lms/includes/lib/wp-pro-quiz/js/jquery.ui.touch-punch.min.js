/*!
 * jQuery UI Touch Punch 0.2.2
 *
 * Copyright 2011, Dave Furfero
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Depends:
 *  jquery.ui.widget.js
 *  jquery.ui.mouse.js
 */
!function(o){if(o.support.touch="ontouchend"in document,o.support.touch){var t,e=o.ui.mouse.prototype,n=e._mouseInit;e._touchStart=function(o){!t&&this._mouseCapture(o.originalEvent.changedTouches[0])&&(t=!0,this._touchMoved=!1,u(o,"mouseover"),u(o,"mousemove"),u(o,"mousedown"))},e._touchMove=function(o){t&&(this._touchMoved=!0,u(o,"mousemove"))},e._touchEnd=function(o){t&&(u(o,"mouseup"),u(o,"mouseout"),this._touchMoved||u(o,"click"),t=!1)},e._mouseInit=function(){var t=this;t.element.bind("touchstart",o.proxy(t,"_touchStart")).bind("touchmove",o.proxy(t,"_touchMove")).bind("touchend",o.proxy(t,"_touchEnd")),n.call(t)}}function u(o,t){if(!(o.originalEvent.touches.length>1)){o.preventDefault();var e=o.originalEvent.changedTouches[0],n=document.createEvent("MouseEvents");n.initMouseEvent(t,!0,!0,window,1,e.screenX,e.screenY,e.clientX,e.clientY,!1,!1,!1,!1,0,null),o.target.dispatchEvent(n)}}}(jQuery);