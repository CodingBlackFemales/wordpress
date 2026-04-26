/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!***********************!*\
  !*** ./src/remote.js ***!
  \***********************/
// eslint-disable-next-line no-undef
jQuery(function ($) {
  $('.ld-banner-dismiss').on('click', function () {
    const target = $(this).data('target');
    $.ajax({
      type: 'POST',
      // eslint-disable-next-line no-undef
      url: ajaxurl,
      data: {
        action: 'flag_remote_dismiss',
        slug: target,
        // eslint-disable-next-line camelcase, no-undef
        _wpnonce: ld_hub_remote.nonce
      },
      beforeSend() {
        $('#' + target).hide();
      }
    });
  });
});
/******/ })()
;