(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, catch: function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/* global wpforms_gutenberg_form_selector, Choices, JSX, DOM */
/* jshint es3: false, esversion: 6 */

/**
 * Gutenberg editor block.
 *
 * @since 1.8.1
 */
var WPForms = window.WPForms || {};
WPForms.FormSelector = WPForms.FormSelector || function (document, window, $) {
  var _wp = wp,
    _wp$serverSideRender = _wp.serverSideRender,
    ServerSideRender = _wp$serverSideRender === void 0 ? wp.components.ServerSideRender : _wp$serverSideRender;
  var _wp$element = wp.element,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment,
    useState = _wp$element.useState,
    createInterpolateElement = _wp$element.createInterpolateElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var _ref = wp.blockEditor || wp.editor,
    InspectorControls = _ref.InspectorControls,
    InspectorAdvancedControls = _ref.InspectorAdvancedControls,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    ToggleControl = _wp$components.ToggleControl,
    PanelBody = _wp$components.PanelBody,
    Placeholder = _wp$components.Placeholder,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl,
    TextareaControl = _wp$components.TextareaControl,
    Button = _wp$components.Button,
    Modal = _wp$components.Modal;
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults,
    sizes = _wpforms_gutenberg_fo.sizes,
    urls = _wpforms_gutenberg_fo.urls,
    isPro = _wpforms_gutenberg_fo.isPro;
  var defaultStyleSettings = defaults;
  var __ = wp.i18n.__;

  /**
   * List of forms.
   *
   * Default value is localized in FormSelector.php.
   *
   * @since 1.8.4
   *
   * @type {Object}
   */
  var formList = wpforms_gutenberg_form_selector.forms;

  /**
   * Blocks runtime data.
   *
   * @since 1.8.1
   *
   * @type {Object}
   */
  var blocks = {};

  /**
   * Whether it is needed to trigger server rendering.
   *
   * @since 1.8.1
   *
   * @type {boolean}
   */
  var triggerServerRender = true;

  /**
   * Popup container.
   *
   * @since 1.8.3
   *
   * @type {Object}
   */
  var $popup = {};

  /**
   * Track fetch status.
   *
   * @since 1.8.4
   *
   * @type {boolean}
   */
  var isFetching = false;

  /**
   * Public functions and properties.
   *
   * @since 1.8.1
   *
   * @type {Object}
   */
  var app = {
    /**
     * Start the engine.
     *
     * @since 1.8.1
     */
    init: function init() {
      app.initDefaults();
      app.registerBlock();
      $(app.ready);
    },
    /**
     * Document ready.
     *
     * @since 1.8.1
     */
    ready: function ready() {
      app.events();
    },
    /**
     * Events.
     *
     * @since 1.8.1
     */
    events: function events() {
      $(window).on('wpformsFormSelectorEdit', _.debounce(app.blockEdit, 250)).on('wpformsFormSelectorFormLoaded', _.debounce(app.formLoaded, 250));
    },
    /**
     * Get fresh list of forms via REST-API.
     *
     * @since 1.8.4
     *
     * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
     */
    getForms: function getForms() {
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        var response;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (!isFetching) {
                _context.next = 2;
                break;
              }
              return _context.abrupt("return");
            case 2:
              // Set the flag to true indicating a fetch is in progress.
              isFetching = true;
              _context.prev = 3;
              _context.next = 6;
              return wp.apiFetch({
                path: '/wpforms/v1/forms/',
                method: 'GET',
                cache: 'no-cache'
              });
            case 6:
              response = _context.sent;
              // Update the form list.
              formList = response.forms;
              _context.next = 13;
              break;
            case 10:
              _context.prev = 10;
              _context.t0 = _context["catch"](3);
              // eslint-disable-next-line no-console
              console.error(_context.t0);
            case 13:
              _context.prev = 13;
              isFetching = false;
              return _context.finish(13);
            case 16:
            case "end":
              return _context.stop();
          }
        }, _callee, null, [[3, 10, 13, 16]]);
      }))();
    },
    /**
     * Open builder popup.
     *
     * @since 1.6.2
     *
     * @param {string} clientID Block Client ID.
     */
    openBuilderPopup: function openBuilderPopup(clientID) {
      if ($.isEmptyObject($popup)) {
        var tmpl = $('#wpforms-gutenberg-popup');
        var parent = $('#wpwrap');
        parent.after(tmpl);
        $popup = parent.siblings('#wpforms-gutenberg-popup');
      }
      var url = wpforms_gutenberg_form_selector.get_started_url,
        $iframe = $popup.find('iframe');
      app.builderCloseButtonEvent(clientID);
      $iframe.attr('src', url);
      $popup.fadeIn();
    },
    /**
     * Close button (inside the form builder) click event.
     *
     * @since 1.8.3
     *
     * @param {string} clientID Block Client ID.
     */
    builderCloseButtonEvent: function builderCloseButtonEvent(clientID) {
      $popup.off('wpformsBuilderInPopupClose').on('wpformsBuilderInPopupClose', function (e, action, formId, formTitle) {
        if (action !== 'saved' || !formId) {
          return;
        }

        // Insert a new block when a new form is created from the popup to update the form list and attributes.
        var newBlock = wp.blocks.createBlock('wpforms/form-selector', {
          formId: formId.toString() // Expects string value, make sure we insert string.
        });

        // eslint-disable-next-line camelcase
        formList = [{
          ID: formId,
          post_title: formTitle
        }];

        // Insert a new block.
        wp.data.dispatch('core/block-editor').removeBlock(clientID);
        wp.data.dispatch('core/block-editor').insertBlocks(newBlock);
      });
    },
    /**
     * Register block.
     *
     * @since 1.8.1
     */
    // eslint-disable-next-line max-lines-per-function
    registerBlock: function registerBlock() {
      registerBlockType('wpforms/form-selector', {
        title: strings.title,
        description: strings.description,
        icon: app.getIcon(),
        keywords: strings.form_keywords,
        category: 'widgets',
        attributes: app.getBlockAttributes(),
        supports: {
          customClassName: app.hasForms()
        },
        example: {
          attributes: {
            preview: true
          }
        },
        edit: function edit(props) {
          // Get fresh list of forms.
          app.getForms();
          var attributes = props.attributes;
          var formOptions = app.getFormOptions();
          var handlers = app.getSettingsFieldsHandlers(props);

          // Store block clientId in attributes.
          if (!attributes.clientId) {
            // We just want client ID to update once.
            // The block editor doesn't have a fixed block ID, so we need to get it on the initial load, but only once.
            props.setAttributes({
              clientId: props.clientId
            });
          }

          // Main block settings.
          var jsx = [app.jsxParts.getMainSettings(attributes, handlers, formOptions)];

          // Block preview picture.
          if (!app.hasForms()) {
            jsx.push(app.jsxParts.getEmptyFormsPreview(props));
            return jsx;
          }
          var sizeOptions = app.getSizeOptions();

          // Form style settings & block content.
          if (attributes.formId) {
            jsx.push(app.jsxParts.getStyleSettings(props, handlers, sizeOptions), app.jsxParts.getAdvancedSettings(props, handlers), app.jsxParts.getBlockFormContent(props));
            handlers.updateCopyPasteContent();
            $(window).trigger('wpformsFormSelectorEdit', [props]);
            return jsx;
          }

          // Block preview picture.
          if (attributes.preview) {
            jsx.push(app.jsxParts.getBlockPreview());
            return jsx;
          }

          // Block placeholder (form selector).
          jsx.push(app.jsxParts.getBlockPlaceholder(props.attributes, handlers, formOptions));
          return jsx;
        },
        save: function save() {
          return null;
        }
      });
    },
    /**
     * Init default style settings.
     *
     * @since 1.8.1
     */
    initDefaults: function initDefaults() {
      ['formId', 'copyPasteJsonValue'].forEach(function (key) {
        return delete defaultStyleSettings[key];
      });
    },
    /**
     * Check if site has forms.
     *
     * @since 1.8.3
     *
     * @return {boolean} Whether site has at least one form.
     */
    hasForms: function hasForms() {
      return formList.length >= 1;
    },
    /**
     * Block JSX parts.
     *
     * @since 1.8.1
     *
     * @type {Object}
     */
    jsxParts: {
      /**
       * Get main settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} attributes  Block attributes.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} formOptions Form selector options.
       *
       * @return {JSX.Element} Main setting JSX code.
       */
      getMainSettings: function getMainSettings(attributes, handlers, formOptions) {
        if (!app.hasForms()) {
          return app.jsxParts.printEmptyFormsNotice(attributes.clientId);
        }
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-inspector-main-settings"
        }, /*#__PURE__*/React.createElement(PanelBody, {
          className: "wpforms-gutenberg-panel",
          title: strings.form_settings
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.form_selected,
          value: attributes.formId,
          options: formOptions,
          onChange: function onChange(value) {
            return handlers.attrChange('formId', value);
          }
        }), attributes.formId ? /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-form-selector-actions"
        }, /*#__PURE__*/React.createElement("a", {
          href: urls.form_url.replace('{ID}', attributes.formId),
          rel: "noreferrer",
          target: "_blank"
        }, strings.form_edit), isPro && /*#__PURE__*/React.createElement(React.Fragment, null, "\xA0\xA0|\xA0\xA0", /*#__PURE__*/React.createElement("a", {
          href: urls.entries_url.replace('{ID}', attributes.formId),
          rel: "noreferrer",
          target: "_blank"
        }, strings.form_entries))) : null, /*#__PURE__*/React.createElement(ToggleControl, {
          label: strings.show_title,
          checked: attributes.displayTitle,
          onChange: function onChange(value) {
            return handlers.attrChange('displayTitle', value);
          }
        }), /*#__PURE__*/React.createElement(ToggleControl, {
          label: strings.show_description,
          checked: attributes.displayDesc,
          onChange: function onChange(value) {
            return handlers.attrChange('displayDesc', value);
          }
        }), /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice"
        }, /*#__PURE__*/React.createElement("strong", null, strings.panel_notice_head), strings.panel_notice_text, /*#__PURE__*/React.createElement("a", {
          href: strings.panel_notice_link,
          rel: "noreferrer",
          target: "_blank"
        }, strings.panel_notice_link_text))));
      },
      /**
       * Print empty forms notice.
       *
       * @since 1.8.3
       *
       * @param {string} clientId Block client ID.
       *
       * @return {JSX.Element} Field styles JSX code.
       */
      printEmptyFormsNotice: function printEmptyFormsNotice(clientId) {
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-inspector-main-settings"
        }, /*#__PURE__*/React.createElement(PanelBody, {
          className: "wpforms-gutenberg-panel",
          title: strings.form_settings
        }, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-empty-form-notice",
          style: {
            display: 'block'
          }
        }, /*#__PURE__*/React.createElement("strong", null, __('You haven’t created a form, yet!', 'wpforms-lite')), __('What are you waiting for?', 'wpforms-lite')), /*#__PURE__*/React.createElement("button", {
          type: "button",
          className: "get-started-button components-button is-secondary",
          onClick: function onClick() {
            app.openBuilderPopup(clientId);
          }
        }, __('Get Started', 'wpforms-lite'))));
      },
      /**
       * Get Field styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props       Block properties.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} sizeOptions Size selector options.
       *
       * @return {Object} Field styles JSX code.
       */
      getFieldStyles: function getFieldStyles(props, handlers, sizeOptions) {
        // eslint-disable-line max-lines-per-function
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.field_styles
        }, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-use-modern-notice"
        }, /*#__PURE__*/React.createElement("strong", null, strings.use_modern_notice_head), strings.use_modern_notice_text, " ", /*#__PURE__*/React.createElement("a", {
          href: strings.use_modern_notice_link,
          rel: "noreferrer",
          target: "_blank"
        }, strings.learn_more)), /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-lead-form-notice",
          style: {
            display: 'none'
          }
        }, /*#__PURE__*/React.createElement("strong", null, strings.lead_forms_panel_notice_head), strings.lead_forms_panel_notice_text), /*#__PURE__*/React.createElement(Flex, {
          gap: 4,
          align: "flex-start",
          className: 'wpforms-gutenberg-form-selector-flex',
          justify: "space-between"
        }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: props.attributes.fieldSize,
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldSize', value);
          }
        })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
          label: strings.border_radius,
          value: props.attributes.fieldBorderRadius,
          isUnitSelectTabbable: true,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldBorderRadius', value);
          }
        }))), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.fieldBackgroundColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldBackgroundColor', value);
            },
            label: strings.background
          }, {
            value: props.attributes.fieldBorderColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldBorderColor', value);
            },
            label: strings.border
          }, {
            value: props.attributes.fieldTextColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldTextColor', value);
            },
            label: strings.text
          }]
        })));
      },
      /**
       * Get Label styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props       Block properties.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} sizeOptions Size selector options.
       *
       * @return {Object} Label styles JSX code.
       */
      getLabelStyles: function getLabelStyles(props, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.label_styles
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: props.attributes.labelSize,
          className: "wpforms-gutenberg-form-selector-fix-bottom-margin",
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('labelSize', value);
          }
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.labelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelColor', value);
            },
            label: strings.label
          }, {
            value: props.attributes.labelSublabelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelSublabelColor', value);
            },
            label: strings.sublabel_hints.replace('&amp;', '&')
          }, {
            value: props.attributes.labelErrorColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelErrorColor', value);
            },
            label: strings.error_message
          }]
        })));
      },
      /**
       * Get Button styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props       Block properties.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} sizeOptions Size selector options.
       *
       * @return {Object}  Button styles JSX code.
       */
      getButtonStyles: function getButtonStyles(props, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.button_styles
        }, /*#__PURE__*/React.createElement(Flex, {
          gap: 4,
          align: "flex-start",
          className: 'wpforms-gutenberg-form-selector-flex',
          justify: "space-between"
        }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: props.attributes.buttonSize,
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonSize', value);
          }
        })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonBorderRadius', value);
          },
          label: strings.border_radius,
          isUnitSelectTabbable: true,
          value: props.attributes.buttonBorderRadius
        }))), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.buttonBackgroundColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('buttonBackgroundColor', value);
            },
            label: strings.background
          }, {
            value: props.attributes.buttonTextColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('buttonTextColor', value);
            },
            label: strings.text
          }]
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-legend wpforms-button-color-notice"
        }, strings.button_color_notice)));
      },
      /**
       * Get Page Indicator styles JSX code.
       *
       * @since 1.8.7
       *
       * @param {Object} props    Block properties.
       * @param {Object} handlers Block event handlers.
       *
       * @return {Object} Page Indicator styles JSX code.
       */
      getPageIndicatorStyles: function getPageIndicatorStyles(props, handlers) {
        if (!app.hasPageBreak(formList, props.attributes.formId)) {
          return null;
        }
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.other_styles
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.pageBreakColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('pageBreakColor', value);
            },
            label: strings.page_break
          }]
        })));
      },
      /**
       * Get style settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props       Block properties.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} sizeOptions Size selector options.
       *
       * @return {Object} Inspector controls JSX code.
       */
      getStyleSettings: function getStyleSettings(props, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-style-settings"
        }, app.jsxParts.getFieldStyles(props, handlers, sizeOptions), app.jsxParts.getLabelStyles(props, handlers, sizeOptions), app.jsxParts.getButtonStyles(props, handlers, sizeOptions), app.jsxParts.getPageIndicatorStyles(props, handlers));
      },
      /**
       * Get advanced settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props    Block properties.
       * @param {Object} handlers Block event handlers.
       *
       * @return {Object} Inspector advanced controls JSX code.
       */
      getAdvancedSettings: function getAdvancedSettings(props, handlers) {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        var _useState = useState(false),
          _useState2 = _slicedToArray(_useState, 2),
          isOpen = _useState2[0],
          setOpen = _useState2[1];
        var openModal = function openModal() {
          return setOpen(true);
        };
        var closeModal = function closeModal() {
          return setOpen(false);
        };
        return /*#__PURE__*/React.createElement(InspectorAdvancedControls, null, /*#__PURE__*/React.createElement("div", {
          className: app.getPanelClass(props)
        }, /*#__PURE__*/React.createElement(TextareaControl, {
          label: strings.copy_paste_settings,
          rows: "4",
          spellCheck: "false",
          value: props.attributes.copyPasteJsonValue,
          onChange: function onChange(value) {
            return handlers.pasteSettings(value);
          }
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-legend",
          dangerouslySetInnerHTML: {
            __html: strings.copy_paste_notice
          }
        }), /*#__PURE__*/React.createElement(Button, {
          className: "wpforms-gutenberg-form-selector-reset-button",
          onClick: openModal
        }, strings.reset_style_settings)), isOpen && /*#__PURE__*/React.createElement(Modal, {
          className: "wpforms-gutenberg-modal",
          title: strings.reset_style_settings,
          onRequestClose: closeModal
        }, /*#__PURE__*/React.createElement("p", null, strings.reset_settings_confirm_text), /*#__PURE__*/React.createElement(Flex, {
          gap: 3,
          align: "center",
          justify: "flex-end"
        }, /*#__PURE__*/React.createElement(Button, {
          isSecondary: true,
          onClick: closeModal
        }, strings.btn_no), /*#__PURE__*/React.createElement(Button, {
          isPrimary: true,
          onClick: function onClick() {
            closeModal();
            handlers.resetSettings();
          }
        }, strings.btn_yes_reset))));
      },
      /**
       * Get block content JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props Block properties.
       *
       * @return {JSX.Element} Block content JSX code.
       */
      getBlockFormContent: function getBlockFormContent(props) {
        if (triggerServerRender) {
          return /*#__PURE__*/React.createElement(ServerSideRender, {
            key: "wpforms-gutenberg-form-selector-server-side-renderer",
            block: "wpforms/form-selector",
            attributes: props.attributes
          });
        }
        var clientId = props.clientId;
        var block = app.getBlockContainer(props);

        // In the case of empty content, use server side renderer.
        // This happens when the block is duplicated or converted to a reusable block.
        if (!block || !block.innerHTML) {
          triggerServerRender = true;
          return app.jsxParts.getBlockFormContent(props);
        }
        blocks[clientId] = blocks[clientId] || {};
        blocks[clientId].blockHTML = block.innerHTML;
        blocks[clientId].loadedFormId = props.attributes.formId;
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-form-html"
        }, /*#__PURE__*/React.createElement("div", {
          dangerouslySetInnerHTML: {
            __html: blocks[clientId].blockHTML
          }
        }));
      },
      /**
       * Get block preview JSX code.
       *
       * @since 1.8.1
       *
       * @return {JSX.Element} Block preview JSX code.
       */
      getBlockPreview: function getBlockPreview() {
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_preview_url,
          style: {
            width: '100%'
          },
          alt: ""
        }));
      },
      /**
       * Get block empty JSX code.
       *
       * @since 1.8.3
       *
       * @param {Object} props Block properties.
       * @return {JSX.Element} Block empty JSX code.
       */
      getEmptyFormsPreview: function getEmptyFormsPreview(props) {
        var clientId = props.clientId;
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-empty"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-no-form-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_empty_url,
          alt: ""
        }), /*#__PURE__*/React.createElement("p", null, createInterpolateElement(__('You can use <b>WPForms</b> to build contact forms, surveys, payment forms, and more with just a few clicks.', 'wpforms-lite'), {
          b: /*#__PURE__*/React.createElement("strong", null)
        })), /*#__PURE__*/React.createElement("button", {
          type: "button",
          className: "get-started-button components-button is-primary",
          onClick: function onClick() {
            app.openBuilderPopup(clientId);
          }
        }, __('Get Started', 'wpforms-lite')), /*#__PURE__*/React.createElement("p", {
          className: "empty-desc"
        }, createInterpolateElement(__('Need some help? Check out our <a>comprehensive guide.</a>', 'wpforms-lite'), {
          // eslint-disable-next-line jsx-a11y/anchor-has-content
          a: /*#__PURE__*/React.createElement("a", {
            href: wpforms_gutenberg_form_selector.wpforms_guide,
            target: "_blank",
            rel: "noopener noreferrer"
          })
        })), /*#__PURE__*/React.createElement("div", {
          id: "wpforms-gutenberg-popup",
          className: "wpforms-builder-popup"
        }, /*#__PURE__*/React.createElement("iframe", {
          src: "about:blank",
          width: "100%",
          height: "100%",
          id: "wpforms-builder-iframe",
          title: "WPForms Builder Popup"
        }))));
      },
      /**
       * Get block placeholder (form selector) JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} attributes  Block attributes.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} formOptions Form selector options.
       *
       * @return {JSX.Element} Block placeholder JSX code.
       */
      getBlockPlaceholder: function getBlockPlaceholder(attributes, handlers, formOptions) {
        return /*#__PURE__*/React.createElement(Placeholder, {
          key: "wpforms-gutenberg-form-selector-wrap",
          className: "wpforms-gutenberg-form-selector-wrap"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.logo_url,
          alt: ""
        }), /*#__PURE__*/React.createElement(SelectControl, {
          key: "wpforms-gutenberg-form-selector-select-control",
          value: attributes.formId,
          options: formOptions,
          onChange: function onChange(value) {
            return handlers.attrChange('formId', value);
          }
        }));
      }
    },
    /**
     * Determine if the form has a Pagebreak field.
     *
     * @since 1.8.7
     *
     * @param {Object}        forms  The forms' data object.
     * @param {number|string} formId Form ID.
     *
     * @return {boolean} True when the form has a Pagebreak field, false otherwise.
     */
    hasPageBreak: function hasPageBreak(forms, formId) {
      var _JSON$parse;
      var currentForm = forms.find(function (form) {
        return parseInt(form.ID, 10) === parseInt(formId, 10);
      });
      if (!currentForm.post_content) {
        return false;
      }
      var fields = (_JSON$parse = JSON.parse(currentForm.post_content)) === null || _JSON$parse === void 0 ? void 0 : _JSON$parse.fields;
      return Object.values(fields).some(function (field) {
        return field.type === 'pagebreak';
      });
    },
    /**
     * Get Style Settings panel class.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     *
     * @return {string} Style Settings panel class.
     */
    getPanelClass: function getPanelClass(props) {
      var cssClass = 'wpforms-gutenberg-panel wpforms-block-settings-' + props.clientId;
      if (!app.isFullStylingEnabled()) {
        cssClass += ' disabled_panel';
      }
      return cssClass;
    },
    /**
     * Determine whether the full styling is enabled.
     *
     * @since 1.8.1
     *
     * @return {boolean} Whether the full styling is enabled.
     */
    isFullStylingEnabled: function isFullStylingEnabled() {
      return wpforms_gutenberg_form_selector.is_modern_markup && wpforms_gutenberg_form_selector.is_full_styling;
    },
    /**
     * Get block container DOM element.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     *
     * @return {Element} Block container.
     */
    getBlockContainer: function getBlockContainer(props) {
      var blockSelector = "#block-".concat(props.clientId, " > div");
      var block = document.querySelector(blockSelector);

      // For FSE / Gutenberg plugin we need to take a look inside the iframe.
      if (!block) {
        var editorCanvas = document.querySelector('iframe[name="editor-canvas"]');
        block = editorCanvas && editorCanvas.contentWindow.document.querySelector(blockSelector);
      }
      return block;
    },
    /**
     * Get settings fields event handlers.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     *
     * @return {Object} Object that contains event handlers for the settings fields.
     */
    getSettingsFieldsHandlers: function getSettingsFieldsHandlers(props) {
      // eslint-disable-line max-lines-per-function
      return {
        /**
         * Field style attribute change event handler.
         *
         * @since 1.8.1
         *
         * @param {string} attribute Attribute name.
         * @param {string} value     New attribute value.
         */
        styleAttrChange: function styleAttrChange(attribute, value) {
          var block = app.getBlockContainer(props),
            container = block.querySelector("#wpforms-".concat(props.attributes.formId)),
            property = attribute.replace(/[A-Z]/g, function (letter) {
              return "-".concat(letter.toLowerCase());
            }),
            setAttr = {};
          if (container) {
            switch (property) {
              case 'field-size':
              case 'label-size':
              case 'button-size':
                for (var key in sizes[property][value]) {
                  container.style.setProperty("--wpforms-".concat(property, "-").concat(key), sizes[property][value][key]);
                }
                break;
              default:
                container.style.setProperty("--wpforms-".concat(property), value);
            }
          }
          setAttr[attribute] = value;
          props.setAttributes(setAttr);
          triggerServerRender = false;
          this.updateCopyPasteContent();
          $(window).trigger('wpformsFormSelectorStyleAttrChange', [block, props, attribute, value]);
        },
        /**
         * Field regular attribute change event handler.
         *
         * @since 1.8.1
         *
         * @param {string} attribute Attribute name.
         * @param {string} value     New attribute value.
         */
        attrChange: function attrChange(attribute, value) {
          var setAttr = {};
          setAttr[attribute] = value;
          props.setAttributes(setAttr);
          triggerServerRender = true;
          this.updateCopyPasteContent();
        },
        /**
         * Reset Form Styles settings to defaults.
         *
         * @since 1.8.1
         */
        resetSettings: function resetSettings() {
          for (var key in defaultStyleSettings) {
            this.styleAttrChange(key, defaultStyleSettings[key]);
          }
        },
        /**
         * Update content of the "Copy/Paste" fields.
         *
         * @since 1.8.1
         */
        updateCopyPasteContent: function updateCopyPasteContent() {
          var content = {};
          var atts = wp.data.select('core/block-editor').getBlockAttributes(props.clientId);
          for (var key in defaultStyleSettings) {
            content[key] = atts[key];
          }
          props.setAttributes({
            copyPasteJsonValue: JSON.stringify(content)
          });
        },
        /**
         * Paste settings handler.
         *
         * @since 1.8.1
         *
         * @param {string} value New attribute value.
         */
        pasteSettings: function pasteSettings(value) {
          var pasteAttributes = app.parseValidateJson(value);
          if (!pasteAttributes) {
            wp.data.dispatch('core/notices').createErrorNotice(strings.copy_paste_error, {
              id: 'wpforms-json-parse-error'
            });
            this.updateCopyPasteContent();
            return;
          }
          pasteAttributes.copyPasteJsonValue = value;
          props.setAttributes(pasteAttributes);
          triggerServerRender = true;
        }
      };
    },
    /**
     * Parse and validate JSON string.
     *
     * @since 1.8.1
     *
     * @param {string} value JSON string.
     *
     * @return {boolean|object} Parsed JSON object OR false on error.
     */
    parseValidateJson: function parseValidateJson(value) {
      if (typeof value !== 'string') {
        return false;
      }
      var atts;
      try {
        atts = JSON.parse(value);
      } catch (error) {
        atts = false;
      }
      return atts;
    },
    /**
     * Get WPForms icon DOM element.
     *
     * @since 1.8.1
     *
     * @return {DOM.element} WPForms icon DOM element.
     */
    getIcon: function getIcon() {
      return createElement('svg', {
        width: 20,
        height: 20,
        viewBox: '0 0 612 612',
        className: 'dashicon'
      }, createElement('path', {
        fill: 'currentColor',
        d: 'M544,0H68C30.445,0,0,30.445,0,68v476c0,37.556,30.445,68,68,68h476c37.556,0,68-30.444,68-68V68 C612,30.445,581.556,0,544,0z M464.44,68L387.6,120.02L323.34,68H464.44z M288.66,68l-64.26,52.02L147.56,68H288.66z M544,544H68 V68h22.1l136,92.14l79.9-64.6l79.56,64.6l136-92.14H544V544z M114.24,263.16h95.88v-48.28h-95.88V263.16z M114.24,360.4h95.88 v-48.62h-95.88V360.4z M242.76,360.4h255v-48.62h-255V360.4L242.76,360.4z M242.76,263.16h255v-48.28h-255V263.16L242.76,263.16z M368.22,457.3h129.54V408H368.22V457.3z'
      }));
    },
    /**
     * Get block attributes.
     *
     * @since 1.8.1
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      // eslint-disable-line max-lines-per-function
      return {
        clientId: {
          type: 'string',
          default: ''
        },
        formId: {
          type: 'string',
          default: defaults.formId
        },
        displayTitle: {
          type: 'boolean',
          default: defaults.displayTitle
        },
        displayDesc: {
          type: 'boolean',
          default: defaults.displayDesc
        },
        preview: {
          type: 'boolean'
        },
        fieldSize: {
          type: 'string',
          default: defaults.fieldSize
        },
        fieldBorderRadius: {
          type: 'string',
          default: defaults.fieldBorderRadius
        },
        fieldBackgroundColor: {
          type: 'string',
          default: defaults.fieldBackgroundColor
        },
        fieldBorderColor: {
          type: 'string',
          default: defaults.fieldBorderColor
        },
        fieldTextColor: {
          type: 'string',
          default: defaults.fieldTextColor
        },
        labelSize: {
          type: 'string',
          default: defaults.labelSize
        },
        labelColor: {
          type: 'string',
          default: defaults.labelColor
        },
        labelSublabelColor: {
          type: 'string',
          default: defaults.labelSublabelColor
        },
        labelErrorColor: {
          type: 'string',
          default: defaults.labelErrorColor
        },
        buttonSize: {
          type: 'string',
          default: defaults.buttonSize
        },
        buttonBorderRadius: {
          type: 'string',
          default: defaults.buttonBorderRadius
        },
        buttonBackgroundColor: {
          type: 'string',
          default: defaults.buttonBackgroundColor
        },
        buttonTextColor: {
          type: 'string',
          default: defaults.buttonTextColor
        },
        pageBreakColor: {
          type: 'string',
          default: defaults.pageBreakColor
        },
        copyPasteJsonValue: {
          type: 'string',
          default: defaults.copyPasteJsonValue
        }
      };
    },
    /**
     * Get form selector options.
     *
     * @since 1.8.1
     *
     * @return {Array} Form options.
     */
    getFormOptions: function getFormOptions() {
      var formOptions = formList.map(function (value) {
        return {
          value: value.ID,
          label: value.post_title
        };
      });
      formOptions.unshift({
        value: '',
        label: strings.form_select
      });
      return formOptions;
    },
    /**
     * Get size selector options.
     *
     * @since 1.8.1
     *
     * @return {Array} Size options.
     */
    getSizeOptions: function getSizeOptions() {
      return [{
        label: strings.small,
        value: 'small'
      }, {
        label: strings.medium,
        value: 'medium'
      }, {
        label: strings.large,
        value: 'large'
      }];
    },
    /**
     * Event `wpformsFormSelectorEdit` handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e     Event object.
     * @param {Object} props Block properties.
     */
    blockEdit: function blockEdit(e, props) {
      var block = app.getBlockContainer(props);
      if (!block || !block.dataset) {
        return;
      }
      app.initLeadFormSettings(block.parentElement);
    },
    /**
     * Init Lead Form Settings panels.
     *
     * @since 1.8.1
     *
     * @param {Element} block Block element.
     */
    initLeadFormSettings: function initLeadFormSettings(block) {
      if (!block || !block.dataset) {
        return;
      }
      if (!app.isFullStylingEnabled()) {
        return;
      }
      var clientId = block.dataset.block;
      var $form = $(block.querySelector('.wpforms-container'));
      var $panel = $(".wpforms-block-settings-".concat(clientId));
      if ($form.hasClass('wpforms-lead-forms-container')) {
        $panel.addClass('disabled_panel').find('.wpforms-gutenberg-panel-notice.wpforms-lead-form-notice').css('display', 'block');
        $panel.find('.wpforms-gutenberg-panel-notice.wpforms-use-modern-notice').css('display', 'none');
        return;
      }
      $panel.removeClass('disabled_panel').find('.wpforms-gutenberg-panel-notice.wpforms-lead-form-notice').css('display', 'none');
      $panel.find('.wpforms-gutenberg-panel-notice.wpforms-use-modern-notice').css('display', null);
    },
    /**
     * Event `wpformsFormSelectorFormLoaded` handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e Event object.
     */
    formLoaded: function formLoaded(e) {
      app.initLeadFormSettings(e.detail.block);
      app.updateAccentColors(e.detail);
      app.loadChoicesJS(e.detail);
      app.initRichTextField(e.detail.formId);
      $(e.detail.block).off('click').on('click', app.blockClick);
    },
    /**
     * Click on the block event handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e Event object.
     */
    blockClick: function blockClick(e) {
      app.initLeadFormSettings(e.currentTarget);
    },
    /**
     * Update accent colors of some fields in GB block in Modern Markup mode.
     *
     * @since 1.8.1
     *
     * @param {Object} detail Event details object.
     */
    updateAccentColors: function updateAccentColors(detail) {
      if (!wpforms_gutenberg_form_selector.is_modern_markup || !window.WPForms || !window.WPForms.FrontendModern || !detail.block) {
        return;
      }
      var $form = $(detail.block.querySelector("#wpforms-".concat(detail.formId))),
        FrontendModern = window.WPForms.FrontendModern;
      FrontendModern.updateGBBlockPageIndicatorColor($form);
      FrontendModern.updateGBBlockIconChoicesColor($form);
      FrontendModern.updateGBBlockRatingColor($form);
    },
    /**
     * Init Modern style Dropdown fields (<select>).
     *
     * @since 1.8.1
     *
     * @param {Object} detail Event details object.
     */
    loadChoicesJS: function loadChoicesJS(detail) {
      if (typeof window.Choices !== 'function') {
        return;
      }
      var $form = $(detail.block.querySelector("#wpforms-".concat(detail.formId)));
      $form.find('.choicesjs-select').each(function (idx, el) {
        var $el = $(el);
        if ($el.data('choice') === 'active') {
          return;
        }
        var args = window.wpforms_choicesjs_config || {},
          searchEnabled = $el.data('search-enabled'),
          $field = $el.closest('.wpforms-field');
        args.searchEnabled = 'undefined' !== typeof searchEnabled ? searchEnabled : true;
        args.callbackOnInit = function () {
          var self = this,
            $element = $(self.passedElement.element),
            $input = $(self.input.element),
            sizeClass = $element.data('size-class');

          // Add CSS-class for size.
          if (sizeClass) {
            $(self.containerOuter.element).addClass(sizeClass);
          }

          /**
           * If a multiple select has selected choices - hide a placeholder text.
           * In case if select is empty - we return placeholder text back.
           */
          if ($element.prop('multiple')) {
            // On init event.
            $input.data('placeholder', $input.attr('placeholder'));
            if (self.getValue(true).length) {
              $input.removeAttr('placeholder');
            }
          }
          this.disable();
          $field.find('.is-disabled').removeClass('is-disabled');
        };
        try {
          var choicesInstance = new Choices(el, args);

          // Save Choices.js instance for future access.
          $el.data('choicesjs', choicesInstance);
        } catch (e) {} // eslint-disable-line no-empty
      });
    },
    /**
     * Initialize RichText field.
     *
     * @since 1.8.1
     *
     * @param {number} formId Form ID.
     */
    initRichTextField: function initRichTextField(formId) {
      // Set default tab to `Visual`.
      $("#wpforms-".concat(formId, " .wp-editor-wrap")).removeClass('html-active').addClass('tmce-active');
    }
  };

  // Provide access to public functions/properties.
  return app;
}(document, window, jQuery);

// Initialize.
WPForms.FormSelector.init();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfcmVnZW5lcmF0b3JSdW50aW1lIiwiZSIsInQiLCJyIiwiT2JqZWN0IiwicHJvdG90eXBlIiwibiIsImhhc093blByb3BlcnR5IiwibyIsImRlZmluZVByb3BlcnR5IiwidmFsdWUiLCJpIiwiU3ltYm9sIiwiYSIsIml0ZXJhdG9yIiwiYyIsImFzeW5jSXRlcmF0b3IiLCJ1IiwidG9TdHJpbmdUYWciLCJkZWZpbmUiLCJlbnVtZXJhYmxlIiwiY29uZmlndXJhYmxlIiwid3JpdGFibGUiLCJ3cmFwIiwiR2VuZXJhdG9yIiwiY3JlYXRlIiwiQ29udGV4dCIsIm1ha2VJbnZva2VNZXRob2QiLCJ0cnlDYXRjaCIsInR5cGUiLCJhcmciLCJjYWxsIiwiaCIsImwiLCJmIiwicyIsInkiLCJHZW5lcmF0b3JGdW5jdGlvbiIsIkdlbmVyYXRvckZ1bmN0aW9uUHJvdG90eXBlIiwicCIsImQiLCJnZXRQcm90b3R5cGVPZiIsInYiLCJ2YWx1ZXMiLCJnIiwiZGVmaW5lSXRlcmF0b3JNZXRob2RzIiwiZm9yRWFjaCIsIl9pbnZva2UiLCJBc3luY0l0ZXJhdG9yIiwiaW52b2tlIiwiX3R5cGVvZiIsInJlc29sdmUiLCJfX2F3YWl0IiwidGhlbiIsImNhbGxJbnZva2VXaXRoTWV0aG9kQW5kQXJnIiwiRXJyb3IiLCJkb25lIiwibWV0aG9kIiwiZGVsZWdhdGUiLCJtYXliZUludm9rZURlbGVnYXRlIiwic2VudCIsIl9zZW50IiwiZGlzcGF0Y2hFeGNlcHRpb24iLCJhYnJ1cHQiLCJyZXR1cm4iLCJUeXBlRXJyb3IiLCJyZXN1bHROYW1lIiwibmV4dCIsIm5leHRMb2MiLCJwdXNoVHJ5RW50cnkiLCJ0cnlMb2MiLCJjYXRjaExvYyIsImZpbmFsbHlMb2MiLCJhZnRlckxvYyIsInRyeUVudHJpZXMiLCJwdXNoIiwicmVzZXRUcnlFbnRyeSIsImNvbXBsZXRpb24iLCJyZXNldCIsImlzTmFOIiwibGVuZ3RoIiwiZGlzcGxheU5hbWUiLCJpc0dlbmVyYXRvckZ1bmN0aW9uIiwiY29uc3RydWN0b3IiLCJuYW1lIiwibWFyayIsInNldFByb3RvdHlwZU9mIiwiX19wcm90b19fIiwiYXdyYXAiLCJhc3luYyIsIlByb21pc2UiLCJrZXlzIiwicmV2ZXJzZSIsInBvcCIsInByZXYiLCJjaGFyQXQiLCJzbGljZSIsInN0b3AiLCJydmFsIiwiaGFuZGxlIiwiY29tcGxldGUiLCJmaW5pc2giLCJjYXRjaCIsIl9jYXRjaCIsImRlbGVnYXRlWWllbGQiLCJhc3luY0dlbmVyYXRvclN0ZXAiLCJnZW4iLCJyZWplY3QiLCJfbmV4dCIsIl90aHJvdyIsImtleSIsImluZm8iLCJlcnJvciIsIl9hc3luY1RvR2VuZXJhdG9yIiwiZm4iLCJzZWxmIiwiYXJncyIsImFyZ3VtZW50cyIsImFwcGx5IiwiZXJyIiwidW5kZWZpbmVkIiwiV1BGb3JtcyIsIndpbmRvdyIsIkZvcm1TZWxlY3RvciIsImRvY3VtZW50IiwiJCIsIl93cCIsIndwIiwiX3dwJHNlcnZlclNpZGVSZW5kZXIiLCJzZXJ2ZXJTaWRlUmVuZGVyIiwiU2VydmVyU2lkZVJlbmRlciIsImNvbXBvbmVudHMiLCJfd3AkZWxlbWVudCIsImVsZW1lbnQiLCJjcmVhdGVFbGVtZW50IiwiRnJhZ21lbnQiLCJ1c2VTdGF0ZSIsImNyZWF0ZUludGVycG9sYXRlRWxlbWVudCIsInJlZ2lzdGVyQmxvY2tUeXBlIiwiYmxvY2tzIiwiX3JlZiIsImJsb2NrRWRpdG9yIiwiZWRpdG9yIiwiSW5zcGVjdG9yQ29udHJvbHMiLCJJbnNwZWN0b3JBZHZhbmNlZENvbnRyb2xzIiwiUGFuZWxDb2xvclNldHRpbmdzIiwiX3dwJGNvbXBvbmVudHMiLCJTZWxlY3RDb250cm9sIiwiVG9nZ2xlQ29udHJvbCIsIlBhbmVsQm9keSIsIlBsYWNlaG9sZGVyIiwiRmxleCIsIkZsZXhCbG9jayIsIl9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2wiLCJUZXh0YXJlYUNvbnRyb2wiLCJCdXR0b24iLCJNb2RhbCIsIl93cGZvcm1zX2d1dGVuYmVyZ19mbyIsIndwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IiLCJzdHJpbmdzIiwiZGVmYXVsdHMiLCJzaXplcyIsInVybHMiLCJpc1BybyIsImRlZmF1bHRTdHlsZVNldHRpbmdzIiwiX18iLCJpMThuIiwiZm9ybUxpc3QiLCJmb3JtcyIsInRyaWdnZXJTZXJ2ZXJSZW5kZXIiLCIkcG9wdXAiLCJpc0ZldGNoaW5nIiwiYXBwIiwiaW5pdCIsImluaXREZWZhdWx0cyIsInJlZ2lzdGVyQmxvY2siLCJyZWFkeSIsImV2ZW50cyIsIm9uIiwiXyIsImRlYm91bmNlIiwiYmxvY2tFZGl0IiwiZm9ybUxvYWRlZCIsImdldEZvcm1zIiwiX2NhbGxlZSIsInJlc3BvbnNlIiwiX2NhbGxlZSQiLCJfY29udGV4dCIsImFwaUZldGNoIiwicGF0aCIsImNhY2hlIiwidDAiLCJjb25zb2xlIiwib3BlbkJ1aWxkZXJQb3B1cCIsImNsaWVudElEIiwiaXNFbXB0eU9iamVjdCIsInRtcGwiLCJwYXJlbnQiLCJhZnRlciIsInNpYmxpbmdzIiwidXJsIiwiZ2V0X3N0YXJ0ZWRfdXJsIiwiJGlmcmFtZSIsImZpbmQiLCJidWlsZGVyQ2xvc2VCdXR0b25FdmVudCIsImF0dHIiLCJmYWRlSW4iLCJvZmYiLCJhY3Rpb24iLCJmb3JtSWQiLCJmb3JtVGl0bGUiLCJuZXdCbG9jayIsImNyZWF0ZUJsb2NrIiwidG9TdHJpbmciLCJJRCIsInBvc3RfdGl0bGUiLCJkYXRhIiwiZGlzcGF0Y2giLCJyZW1vdmVCbG9jayIsImluc2VydEJsb2NrcyIsInRpdGxlIiwiZGVzY3JpcHRpb24iLCJpY29uIiwiZ2V0SWNvbiIsImtleXdvcmRzIiwiZm9ybV9rZXl3b3JkcyIsImNhdGVnb3J5IiwiYXR0cmlidXRlcyIsImdldEJsb2NrQXR0cmlidXRlcyIsInN1cHBvcnRzIiwiY3VzdG9tQ2xhc3NOYW1lIiwiaGFzRm9ybXMiLCJleGFtcGxlIiwicHJldmlldyIsImVkaXQiLCJwcm9wcyIsImZvcm1PcHRpb25zIiwiZ2V0Rm9ybU9wdGlvbnMiLCJoYW5kbGVycyIsImdldFNldHRpbmdzRmllbGRzSGFuZGxlcnMiLCJjbGllbnRJZCIsInNldEF0dHJpYnV0ZXMiLCJqc3giLCJqc3hQYXJ0cyIsImdldE1haW5TZXR0aW5ncyIsImdldEVtcHR5Rm9ybXNQcmV2aWV3Iiwic2l6ZU9wdGlvbnMiLCJnZXRTaXplT3B0aW9ucyIsImdldFN0eWxlU2V0dGluZ3MiLCJnZXRBZHZhbmNlZFNldHRpbmdzIiwiZ2V0QmxvY2tGb3JtQ29udGVudCIsInVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQiLCJ0cmlnZ2VyIiwiZ2V0QmxvY2tQcmV2aWV3IiwiZ2V0QmxvY2tQbGFjZWhvbGRlciIsInNhdmUiLCJwcmludEVtcHR5Rm9ybXNOb3RpY2UiLCJSZWFjdCIsImNsYXNzTmFtZSIsImZvcm1fc2V0dGluZ3MiLCJsYWJlbCIsImZvcm1fc2VsZWN0ZWQiLCJvcHRpb25zIiwib25DaGFuZ2UiLCJhdHRyQ2hhbmdlIiwiaHJlZiIsImZvcm1fdXJsIiwicmVwbGFjZSIsInJlbCIsInRhcmdldCIsImZvcm1fZWRpdCIsImVudHJpZXNfdXJsIiwiZm9ybV9lbnRyaWVzIiwic2hvd190aXRsZSIsImNoZWNrZWQiLCJkaXNwbGF5VGl0bGUiLCJzaG93X2Rlc2NyaXB0aW9uIiwiZGlzcGxheURlc2MiLCJwYW5lbF9ub3RpY2VfaGVhZCIsInBhbmVsX25vdGljZV90ZXh0IiwicGFuZWxfbm90aWNlX2xpbmsiLCJwYW5lbF9ub3RpY2VfbGlua190ZXh0Iiwic3R5bGUiLCJkaXNwbGF5Iiwib25DbGljayIsImdldEZpZWxkU3R5bGVzIiwiZ2V0UGFuZWxDbGFzcyIsImZpZWxkX3N0eWxlcyIsInVzZV9tb2Rlcm5fbm90aWNlX2hlYWQiLCJ1c2VfbW9kZXJuX25vdGljZV90ZXh0IiwidXNlX21vZGVybl9ub3RpY2VfbGluayIsImxlYXJuX21vcmUiLCJsZWFkX2Zvcm1zX3BhbmVsX25vdGljZV9oZWFkIiwibGVhZF9mb3Jtc19wYW5lbF9ub3RpY2VfdGV4dCIsImdhcCIsImFsaWduIiwianVzdGlmeSIsInNpemUiLCJmaWVsZFNpemUiLCJzdHlsZUF0dHJDaGFuZ2UiLCJib3JkZXJfcmFkaXVzIiwiZmllbGRCb3JkZXJSYWRpdXMiLCJpc1VuaXRTZWxlY3RUYWJiYWJsZSIsImNvbG9ycyIsIl9fZXhwZXJpbWVudGFsSXNSZW5kZXJlZEluU2lkZWJhciIsImVuYWJsZUFscGhhIiwic2hvd1RpdGxlIiwiY29sb3JTZXR0aW5ncyIsImZpZWxkQmFja2dyb3VuZENvbG9yIiwiYmFja2dyb3VuZCIsImZpZWxkQm9yZGVyQ29sb3IiLCJib3JkZXIiLCJmaWVsZFRleHRDb2xvciIsInRleHQiLCJnZXRMYWJlbFN0eWxlcyIsImxhYmVsX3N0eWxlcyIsImxhYmVsU2l6ZSIsImxhYmVsQ29sb3IiLCJsYWJlbFN1YmxhYmVsQ29sb3IiLCJzdWJsYWJlbF9oaW50cyIsImxhYmVsRXJyb3JDb2xvciIsImVycm9yX21lc3NhZ2UiLCJnZXRCdXR0b25TdHlsZXMiLCJidXR0b25fc3R5bGVzIiwiYnV0dG9uU2l6ZSIsImJ1dHRvbkJvcmRlclJhZGl1cyIsImJ1dHRvbkJhY2tncm91bmRDb2xvciIsImJ1dHRvblRleHRDb2xvciIsImJ1dHRvbl9jb2xvcl9ub3RpY2UiLCJnZXRQYWdlSW5kaWNhdG9yU3R5bGVzIiwiaGFzUGFnZUJyZWFrIiwib3RoZXJfc3R5bGVzIiwicGFnZUJyZWFrQ29sb3IiLCJwYWdlX2JyZWFrIiwiX3VzZVN0YXRlIiwiX3VzZVN0YXRlMiIsIl9zbGljZWRUb0FycmF5IiwiaXNPcGVuIiwic2V0T3BlbiIsIm9wZW5Nb2RhbCIsImNsb3NlTW9kYWwiLCJjb3B5X3Bhc3RlX3NldHRpbmdzIiwicm93cyIsInNwZWxsQ2hlY2siLCJjb3B5UGFzdGVKc29uVmFsdWUiLCJwYXN0ZVNldHRpbmdzIiwiZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUwiLCJfX2h0bWwiLCJjb3B5X3Bhc3RlX25vdGljZSIsInJlc2V0X3N0eWxlX3NldHRpbmdzIiwib25SZXF1ZXN0Q2xvc2UiLCJyZXNldF9zZXR0aW5nc19jb25maXJtX3RleHQiLCJpc1NlY29uZGFyeSIsImJ0bl9ubyIsImlzUHJpbWFyeSIsInJlc2V0U2V0dGluZ3MiLCJidG5feWVzX3Jlc2V0IiwiYmxvY2siLCJnZXRCbG9ja0NvbnRhaW5lciIsImlubmVySFRNTCIsImJsb2NrSFRNTCIsImxvYWRlZEZvcm1JZCIsInNyYyIsImJsb2NrX3ByZXZpZXdfdXJsIiwid2lkdGgiLCJhbHQiLCJibG9ja19lbXB0eV91cmwiLCJiIiwid3Bmb3Jtc19ndWlkZSIsImlkIiwiaGVpZ2h0IiwibG9nb191cmwiLCJfSlNPTiRwYXJzZSIsImN1cnJlbnRGb3JtIiwiZm9ybSIsInBhcnNlSW50IiwicG9zdF9jb250ZW50IiwiZmllbGRzIiwiSlNPTiIsInBhcnNlIiwic29tZSIsImZpZWxkIiwiY3NzQ2xhc3MiLCJpc0Z1bGxTdHlsaW5nRW5hYmxlZCIsImlzX21vZGVybl9tYXJrdXAiLCJpc19mdWxsX3N0eWxpbmciLCJibG9ja1NlbGVjdG9yIiwiY29uY2F0IiwicXVlcnlTZWxlY3RvciIsImVkaXRvckNhbnZhcyIsImNvbnRlbnRXaW5kb3ciLCJhdHRyaWJ1dGUiLCJjb250YWluZXIiLCJwcm9wZXJ0eSIsImxldHRlciIsInRvTG93ZXJDYXNlIiwic2V0QXR0ciIsInNldFByb3BlcnR5IiwiY29udGVudCIsImF0dHMiLCJzZWxlY3QiLCJzdHJpbmdpZnkiLCJwYXN0ZUF0dHJpYnV0ZXMiLCJwYXJzZVZhbGlkYXRlSnNvbiIsImNyZWF0ZUVycm9yTm90aWNlIiwiY29weV9wYXN0ZV9lcnJvciIsInZpZXdCb3giLCJmaWxsIiwiZGVmYXVsdCIsIm1hcCIsInVuc2hpZnQiLCJmb3JtX3NlbGVjdCIsInNtYWxsIiwibWVkaXVtIiwibGFyZ2UiLCJkYXRhc2V0IiwiaW5pdExlYWRGb3JtU2V0dGluZ3MiLCJwYXJlbnRFbGVtZW50IiwiJGZvcm0iLCIkcGFuZWwiLCJoYXNDbGFzcyIsImFkZENsYXNzIiwiY3NzIiwicmVtb3ZlQ2xhc3MiLCJkZXRhaWwiLCJ1cGRhdGVBY2NlbnRDb2xvcnMiLCJsb2FkQ2hvaWNlc0pTIiwiaW5pdFJpY2hUZXh0RmllbGQiLCJibG9ja0NsaWNrIiwiY3VycmVudFRhcmdldCIsIkZyb250ZW5kTW9kZXJuIiwidXBkYXRlR0JCbG9ja1BhZ2VJbmRpY2F0b3JDb2xvciIsInVwZGF0ZUdCQmxvY2tJY29uQ2hvaWNlc0NvbG9yIiwidXBkYXRlR0JCbG9ja1JhdGluZ0NvbG9yIiwiQ2hvaWNlcyIsImVhY2giLCJpZHgiLCJlbCIsIiRlbCIsIndwZm9ybXNfY2hvaWNlc2pzX2NvbmZpZyIsInNlYXJjaEVuYWJsZWQiLCIkZmllbGQiLCJjbG9zZXN0IiwiY2FsbGJhY2tPbkluaXQiLCIkZWxlbWVudCIsInBhc3NlZEVsZW1lbnQiLCIkaW5wdXQiLCJpbnB1dCIsInNpemVDbGFzcyIsImNvbnRhaW5lck91dGVyIiwicHJvcCIsImdldFZhbHVlIiwicmVtb3ZlQXR0ciIsImRpc2FibGUiLCJjaG9pY2VzSW5zdGFuY2UiLCJqUXVlcnkiXSwic291cmNlcyI6WyJmYWtlXzRkOTFmOTg5LmpzIl0sInNvdXJjZXNDb250ZW50IjpbIi8qIGdsb2JhbCB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLCBDaG9pY2VzLCBKU1gsIERPTSAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4vKipcbiAqIEd1dGVuYmVyZyBlZGl0b3IgYmxvY2suXG4gKlxuICogQHNpbmNlIDEuOC4xXG4gKi9cbmNvbnN0IFdQRm9ybXMgPSB3aW5kb3cuV1BGb3JtcyB8fCB7fTtcblxuV1BGb3Jtcy5Gb3JtU2VsZWN0b3IgPSBXUEZvcm1zLkZvcm1TZWxlY3RvciB8fCAoIGZ1bmN0aW9uKCBkb2N1bWVudCwgd2luZG93LCAkICkge1xuXHRjb25zdCB7IHNlcnZlclNpZGVSZW5kZXI6IFNlcnZlclNpZGVSZW5kZXIgPSB3cC5jb21wb25lbnRzLlNlcnZlclNpZGVSZW5kZXIgfSA9IHdwO1xuXHRjb25zdCB7IGNyZWF0ZUVsZW1lbnQsIEZyYWdtZW50LCB1c2VTdGF0ZSwgY3JlYXRlSW50ZXJwb2xhdGVFbGVtZW50IH0gPSB3cC5lbGVtZW50O1xuXHRjb25zdCB7IHJlZ2lzdGVyQmxvY2tUeXBlIH0gPSB3cC5ibG9ja3M7XG5cdGNvbnN0IHsgSW5zcGVjdG9yQ29udHJvbHMsIEluc3BlY3RvckFkdmFuY2VkQ29udHJvbHMsIFBhbmVsQ29sb3JTZXR0aW5ncyB9ID0gd3AuYmxvY2tFZGl0b3IgfHwgd3AuZWRpdG9yO1xuXHRjb25zdCB7IFNlbGVjdENvbnRyb2wsIFRvZ2dsZUNvbnRyb2wsIFBhbmVsQm9keSwgUGxhY2Vob2xkZXIsIEZsZXgsIEZsZXhCbG9jaywgX19leHBlcmltZW50YWxVbml0Q29udHJvbCwgVGV4dGFyZWFDb250cm9sLCBCdXR0b24sIE1vZGFsIH0gPSB3cC5jb21wb25lbnRzO1xuXHRjb25zdCB7IHN0cmluZ3MsIGRlZmF1bHRzLCBzaXplcywgdXJscywgaXNQcm8gfSA9IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3I7XG5cdGNvbnN0IGRlZmF1bHRTdHlsZVNldHRpbmdzID0gZGVmYXVsdHM7XG5cdGNvbnN0IHsgX18gfSA9IHdwLmkxOG47XG5cblx0LyoqXG5cdCAqIExpc3Qgb2YgZm9ybXMuXG5cdCAqXG5cdCAqIERlZmF1bHQgdmFsdWUgaXMgbG9jYWxpemVkIGluIEZvcm1TZWxlY3Rvci5waHAuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguNFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0bGV0IGZvcm1MaXN0ID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5mb3JtcztcblxuXHQvKipcblx0ICogQmxvY2tzIHJ1bnRpbWUgZGF0YS5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBibG9ja3MgPSB7fTtcblxuXHQvKipcblx0ICogV2hldGhlciBpdCBpcyBuZWVkZWQgdG8gdHJpZ2dlciBzZXJ2ZXIgcmVuZGVyaW5nLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICpcblx0ICogQHR5cGUge2Jvb2xlYW59XG5cdCAqL1xuXHRsZXQgdHJpZ2dlclNlcnZlclJlbmRlciA9IHRydWU7XG5cblx0LyoqXG5cdCAqIFBvcHVwIGNvbnRhaW5lci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4zXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRsZXQgJHBvcHVwID0ge307XG5cblx0LyoqXG5cdCAqIFRyYWNrIGZldGNoIHN0YXR1cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC40XG5cdCAqXG5cdCAqIEB0eXBlIHtib29sZWFufVxuXHQgKi9cblx0bGV0IGlzRmV0Y2hpbmcgPSBmYWxzZTtcblxuXHQvKipcblx0ICogUHVibGljIGZ1bmN0aW9ucyBhbmQgcHJvcGVydGllcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBhcHAgPSB7XG5cblx0XHQvKipcblx0XHQgKiBTdGFydCB0aGUgZW5naW5lLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0aW5pdCgpIHtcblx0XHRcdGFwcC5pbml0RGVmYXVsdHMoKTtcblx0XHRcdGFwcC5yZWdpc3RlckJsb2NrKCk7XG5cblx0XHRcdCQoIGFwcC5yZWFkeSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEb2N1bWVudCByZWFkeS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdHJlYWR5KCkge1xuXHRcdFx0YXBwLmV2ZW50cygpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFdmVudHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKi9cblx0XHRldmVudHMoKSB7XG5cdFx0XHQkKCB3aW5kb3cgKVxuXHRcdFx0XHQub24oICd3cGZvcm1zRm9ybVNlbGVjdG9yRWRpdCcsIF8uZGVib3VuY2UoIGFwcC5ibG9ja0VkaXQsIDI1MCApIClcblx0XHRcdFx0Lm9uKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvckZvcm1Mb2FkZWQnLCBfLmRlYm91bmNlKCBhcHAuZm9ybUxvYWRlZCwgMjUwICkgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGZyZXNoIGxpc3Qgb2YgZm9ybXMgdmlhIFJFU1QtQVBJLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC40XG5cdFx0ICpcblx0XHQgKiBAc2VlIGh0dHBzOi8vZGV2ZWxvcGVyLndvcmRwcmVzcy5vcmcvYmxvY2stZWRpdG9yL3JlZmVyZW5jZS1ndWlkZXMvcGFja2FnZXMvcGFja2FnZXMtYXBpLWZldGNoL1xuXHRcdCAqL1xuXHRcdGFzeW5jIGdldEZvcm1zKCkge1xuXHRcdFx0Ly8gSWYgYSBmZXRjaCBpcyBhbHJlYWR5IGluIHByb2dyZXNzLCBleGl0IHRoZSBmdW5jdGlvbi5cblx0XHRcdGlmICggaXNGZXRjaGluZyApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBTZXQgdGhlIGZsYWcgdG8gdHJ1ZSBpbmRpY2F0aW5nIGEgZmV0Y2ggaXMgaW4gcHJvZ3Jlc3MuXG5cdFx0XHRpc0ZldGNoaW5nID0gdHJ1ZTtcblxuXHRcdFx0dHJ5IHtcblx0XHRcdFx0Ly8gRmV0Y2ggZm9ybXMuXG5cdFx0XHRcdGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgd3AuYXBpRmV0Y2goIHtcblx0XHRcdFx0XHRwYXRoOiAnL3dwZm9ybXMvdjEvZm9ybXMvJyxcblx0XHRcdFx0XHRtZXRob2Q6ICdHRVQnLFxuXHRcdFx0XHRcdGNhY2hlOiAnbm8tY2FjaGUnLFxuXHRcdFx0XHR9ICk7XG5cblx0XHRcdFx0Ly8gVXBkYXRlIHRoZSBmb3JtIGxpc3QuXG5cdFx0XHRcdGZvcm1MaXN0ID0gcmVzcG9uc2UuZm9ybXM7XG5cdFx0XHR9IGNhdGNoICggZXJyb3IgKSB7XG5cdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25zb2xlXG5cdFx0XHRcdGNvbnNvbGUuZXJyb3IoIGVycm9yICk7XG5cdFx0XHR9IGZpbmFsbHkge1xuXHRcdFx0XHRpc0ZldGNoaW5nID0gZmFsc2U7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE9wZW4gYnVpbGRlciBwb3B1cC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjYuMlxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElEIEJsb2NrIENsaWVudCBJRC5cblx0XHQgKi9cblx0XHRvcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJRCApIHtcblx0XHRcdGlmICggJC5pc0VtcHR5T2JqZWN0KCAkcG9wdXAgKSApIHtcblx0XHRcdFx0Y29uc3QgdG1wbCA9ICQoICcjd3Bmb3Jtcy1ndXRlbmJlcmctcG9wdXAnICk7XG5cdFx0XHRcdGNvbnN0IHBhcmVudCA9ICQoICcjd3B3cmFwJyApO1xuXG5cdFx0XHRcdHBhcmVudC5hZnRlciggdG1wbCApO1xuXG5cdFx0XHRcdCRwb3B1cCA9IHBhcmVudC5zaWJsaW5ncyggJyN3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cCcgKTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgdXJsID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5nZXRfc3RhcnRlZF91cmwsXG5cdFx0XHRcdCRpZnJhbWUgPSAkcG9wdXAuZmluZCggJ2lmcmFtZScgKTtcblxuXHRcdFx0YXBwLmJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApO1xuXHRcdFx0JGlmcmFtZS5hdHRyKCAnc3JjJywgdXJsICk7XG5cdFx0XHQkcG9wdXAuZmFkZUluKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENsb3NlIGJ1dHRvbiAoaW5zaWRlIHRoZSBmb3JtIGJ1aWxkZXIpIGNsaWNrIGV2ZW50LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4zXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gY2xpZW50SUQgQmxvY2sgQ2xpZW50IElELlxuXHRcdCAqL1xuXHRcdGJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApIHtcblx0XHRcdCRwb3B1cFxuXHRcdFx0XHQub2ZmKCAnd3Bmb3Jtc0J1aWxkZXJJblBvcHVwQ2xvc2UnIClcblx0XHRcdFx0Lm9uKCAnd3Bmb3Jtc0J1aWxkZXJJblBvcHVwQ2xvc2UnLCBmdW5jdGlvbiggZSwgYWN0aW9uLCBmb3JtSWQsIGZvcm1UaXRsZSApIHtcblx0XHRcdFx0XHRpZiAoIGFjdGlvbiAhPT0gJ3NhdmVkJyB8fCAhIGZvcm1JZCApIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2sgd2hlbiBhIG5ldyBmb3JtIGlzIGNyZWF0ZWQgZnJvbSB0aGUgcG9wdXAgdG8gdXBkYXRlIHRoZSBmb3JtIGxpc3QgYW5kIGF0dHJpYnV0ZXMuXG5cdFx0XHRcdFx0Y29uc3QgbmV3QmxvY2sgPSB3cC5ibG9ja3MuY3JlYXRlQmxvY2soICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdFx0XHRmb3JtSWQ6IGZvcm1JZC50b1N0cmluZygpLCAvLyBFeHBlY3RzIHN0cmluZyB2YWx1ZSwgbWFrZSBzdXJlIHdlIGluc2VydCBzdHJpbmcuXG5cdFx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGNhbWVsY2FzZVxuXHRcdFx0XHRcdGZvcm1MaXN0ID0gWyB7IElEOiBmb3JtSWQsIHBvc3RfdGl0bGU6IGZvcm1UaXRsZSB9IF07XG5cblx0XHRcdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2suXG5cdFx0XHRcdFx0d3AuZGF0YS5kaXNwYXRjaCggJ2NvcmUvYmxvY2stZWRpdG9yJyApLnJlbW92ZUJsb2NrKCBjbGllbnRJRCApO1xuXHRcdFx0XHRcdHdwLmRhdGEuZGlzcGF0Y2goICdjb3JlL2Jsb2NrLWVkaXRvcicgKS5pbnNlcnRCbG9ja3MoIG5ld0Jsb2NrICk7XG5cdFx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUmVnaXN0ZXIgYmxvY2suXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKi9cblx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXHRcdHJlZ2lzdGVyQmxvY2soKSB7XG5cdFx0XHRyZWdpc3RlckJsb2NrVHlwZSggJ3dwZm9ybXMvZm9ybS1zZWxlY3RvcicsIHtcblx0XHRcdFx0dGl0bGU6IHN0cmluZ3MudGl0bGUsXG5cdFx0XHRcdGRlc2NyaXB0aW9uOiBzdHJpbmdzLmRlc2NyaXB0aW9uLFxuXHRcdFx0XHRpY29uOiBhcHAuZ2V0SWNvbigpLFxuXHRcdFx0XHRrZXl3b3Jkczogc3RyaW5ncy5mb3JtX2tleXdvcmRzLFxuXHRcdFx0XHRjYXRlZ29yeTogJ3dpZGdldHMnLFxuXHRcdFx0XHRhdHRyaWJ1dGVzOiBhcHAuZ2V0QmxvY2tBdHRyaWJ1dGVzKCksXG5cdFx0XHRcdHN1cHBvcnRzOiB7XG5cdFx0XHRcdFx0Y3VzdG9tQ2xhc3NOYW1lOiBhcHAuaGFzRm9ybXMoKSxcblx0XHRcdFx0fSxcblx0XHRcdFx0ZXhhbXBsZToge1xuXHRcdFx0XHRcdGF0dHJpYnV0ZXM6IHtcblx0XHRcdFx0XHRcdHByZXZpZXc6IHRydWUsXG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0fSxcblx0XHRcdFx0ZWRpdCggcHJvcHMgKSB7XG5cdFx0XHRcdFx0Ly8gR2V0IGZyZXNoIGxpc3Qgb2YgZm9ybXMuXG5cdFx0XHRcdFx0YXBwLmdldEZvcm1zKCk7XG5cblx0XHRcdFx0XHRjb25zdCB7IGF0dHJpYnV0ZXMgfSA9IHByb3BzO1xuXHRcdFx0XHRcdGNvbnN0IGZvcm1PcHRpb25zID0gYXBwLmdldEZvcm1PcHRpb25zKCk7XG5cdFx0XHRcdFx0Y29uc3QgaGFuZGxlcnMgPSBhcHAuZ2V0U2V0dGluZ3NGaWVsZHNIYW5kbGVycyggcHJvcHMgKTtcblxuXHRcdFx0XHRcdC8vIFN0b3JlIGJsb2NrIGNsaWVudElkIGluIGF0dHJpYnV0ZXMuXG5cdFx0XHRcdFx0aWYgKCAhIGF0dHJpYnV0ZXMuY2xpZW50SWQgKSB7XG5cdFx0XHRcdFx0XHQvLyBXZSBqdXN0IHdhbnQgY2xpZW50IElEIHRvIHVwZGF0ZSBvbmNlLlxuXHRcdFx0XHRcdFx0Ly8gVGhlIGJsb2NrIGVkaXRvciBkb2Vzbid0IGhhdmUgYSBmaXhlZCBibG9jayBJRCwgc28gd2UgbmVlZCB0byBnZXQgaXQgb24gdGhlIGluaXRpYWwgbG9hZCwgYnV0IG9ubHkgb25jZS5cblx0XHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHsgY2xpZW50SWQ6IHByb3BzLmNsaWVudElkIH0gKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBNYWluIGJsb2NrIHNldHRpbmdzLlxuXHRcdFx0XHRcdGNvbnN0IGpzeCA9IFtcblx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRNYWluU2V0dGluZ3MoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApLFxuXHRcdFx0XHRcdF07XG5cblx0XHRcdFx0XHQvLyBCbG9jayBwcmV2aWV3IHBpY3R1cmUuXG5cdFx0XHRcdFx0aWYgKCAhIGFwcC5oYXNGb3JtcygpICkge1xuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Y29uc3Qgc2l6ZU9wdGlvbnMgPSBhcHAuZ2V0U2l6ZU9wdGlvbnMoKTtcblxuXHRcdFx0XHRcdC8vIEZvcm0gc3R5bGUgc2V0dGluZ3MgJiBibG9jayBjb250ZW50LlxuXHRcdFx0XHRcdGlmICggYXR0cmlidXRlcy5mb3JtSWQgKSB7XG5cdFx0XHRcdFx0XHRqc3gucHVzaChcblx0XHRcdFx0XHRcdFx0YXBwLmpzeFBhcnRzLmdldFN0eWxlU2V0dGluZ3MoIHByb3BzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMgKSxcblx0XHRcdFx0XHRcdFx0YXBwLmpzeFBhcnRzLmdldEFkdmFuY2VkU2V0dGluZ3MoIHByb3BzLCBoYW5kbGVycyApLFxuXHRcdFx0XHRcdFx0XHRhcHAuanN4UGFydHMuZ2V0QmxvY2tGb3JtQ29udGVudCggcHJvcHMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdGhhbmRsZXJzLnVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQoKTtcblxuXHRcdFx0XHRcdFx0JCggd2luZG93ICkudHJpZ2dlciggJ3dwZm9ybXNGb3JtU2VsZWN0b3JFZGl0JywgWyBwcm9wcyBdICk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gQmxvY2sgcHJldmlldyBwaWN0dXJlLlxuXHRcdFx0XHRcdGlmICggYXR0cmlidXRlcy5wcmV2aWV3ICkge1xuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRCbG9ja1ByZXZpZXcoKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gQmxvY2sgcGxhY2Vob2xkZXIgKGZvcm0gc2VsZWN0b3IpLlxuXHRcdFx0XHRcdGpzeC5wdXNoKFxuXHRcdFx0XHRcdFx0YXBwLmpzeFBhcnRzLmdldEJsb2NrUGxhY2Vob2xkZXIoIHByb3BzLmF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApLFxuXHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRyZXR1cm4ganN4O1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRzYXZlOiAoKSA9PiBudWxsLFxuXHRcdFx0fSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBJbml0IGRlZmF1bHQgc3R5bGUgc2V0dGluZ3MuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKi9cblx0XHRpbml0RGVmYXVsdHMoKSB7XG5cdFx0XHRbICdmb3JtSWQnLCAnY29weVBhc3RlSnNvblZhbHVlJyBdLmZvckVhY2goICgga2V5ICkgPT4gZGVsZXRlIGRlZmF1bHRTdHlsZVNldHRpbmdzWyBrZXkgXSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBDaGVjayBpZiBzaXRlIGhhcyBmb3Jtcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguM1xuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciBzaXRlIGhhcyBhdCBsZWFzdCBvbmUgZm9ybS5cblx0XHQgKi9cblx0XHRoYXNGb3JtcygpIHtcblx0XHRcdHJldHVybiBmb3JtTGlzdC5sZW5ndGggPj0gMTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQmxvY2sgSlNYIHBhcnRzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7T2JqZWN0fVxuXHRcdCAqL1xuXHRcdGpzeFBhcnRzOiB7XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IG1haW4gc2V0dGluZ3MgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGF0dHJpYnV0ZXMgIEJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gZm9ybU9wdGlvbnMgRm9ybSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge0pTWC5FbGVtZW50fSBNYWluIHNldHRpbmcgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldE1haW5TZXR0aW5ncyggYXR0cmlidXRlcywgaGFuZGxlcnMsIGZvcm1PcHRpb25zICkge1xuXHRcdFx0XHRpZiAoICEgYXBwLmhhc0Zvcm1zKCkgKSB7XG5cdFx0XHRcdFx0cmV0dXJuIGFwcC5qc3hQYXJ0cy5wcmludEVtcHR5Rm9ybXNOb3RpY2UoIGF0dHJpYnV0ZXMuY2xpZW50SWQgKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEluc3BlY3RvckNvbnRyb2xzIGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItaW5zcGVjdG9yLW1haW4tc2V0dGluZ3NcIj5cblx0XHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWxcIiB0aXRsZT17IHN0cmluZ3MuZm9ybV9zZXR0aW5ncyB9PlxuXHRcdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5mb3JtX3NlbGVjdGVkIH1cblx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IGF0dHJpYnV0ZXMuZm9ybUlkIH1cblx0XHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgZm9ybU9wdGlvbnMgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdmb3JtSWQnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0eyBhdHRyaWJ1dGVzLmZvcm1JZCA/IChcblx0XHRcdFx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWFjdGlvbnNcIj5cblx0XHRcdFx0XHRcdFx0XHRcdDxhIGhyZWY9eyB1cmxzLmZvcm1fdXJsLnJlcGxhY2UoICd7SUR9JywgYXR0cmlidXRlcy5mb3JtSWQgKSB9IHJlbD1cIm5vcmVmZXJyZXJcIiB0YXJnZXQ9XCJfYmxhbmtcIj5cblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLmZvcm1fZWRpdCB9XG5cdFx0XHRcdFx0XHRcdFx0XHQ8L2E+XG5cdFx0XHRcdFx0XHRcdFx0XHR7IGlzUHJvICYmIChcblx0XHRcdFx0XHRcdFx0XHRcdFx0PD5cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQmbmJzcDsmbmJzcDt8Jm5ic3A7Jm5ic3A7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0PGEgaHJlZj17IHVybHMuZW50cmllc191cmwucmVwbGFjZSggJ3tJRH0nLCBhdHRyaWJ1dGVzLmZvcm1JZCApIH0gcmVsPVwibm9yZWZlcnJlclwiIHRhcmdldD1cIl9ibGFua1wiPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLmZvcm1fZW50cmllcyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0PC9hPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHQ8Lz5cblx0XHRcdFx0XHRcdFx0XHRcdCkgfVxuXHRcdFx0XHRcdFx0XHRcdDwvcD5cblx0XHRcdFx0XHRcdFx0KSA6IG51bGwgfVxuXHRcdFx0XHRcdFx0XHQ8VG9nZ2xlQ29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5zaG93X3RpdGxlIH1cblx0XHRcdFx0XHRcdFx0XHRjaGVja2VkPXsgYXR0cmlidXRlcy5kaXNwbGF5VGl0bGUgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdkaXNwbGF5VGl0bGUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PFRvZ2dsZUNvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2hvd19kZXNjcmlwdGlvbiB9XG5cdFx0XHRcdFx0XHRcdFx0Y2hlY2tlZD17IGF0dHJpYnV0ZXMuZGlzcGxheURlc2MgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdkaXNwbGF5RGVzYycsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2VcIj5cblx0XHRcdFx0XHRcdFx0XHQ8c3Ryb25nPnsgc3RyaW5ncy5wYW5lbF9ub3RpY2VfaGVhZCB9PC9zdHJvbmc+XG5cdFx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLnBhbmVsX25vdGljZV90ZXh0IH1cblx0XHRcdFx0XHRcdFx0XHQ8YSBocmVmPXsgc3RyaW5ncy5wYW5lbF9ub3RpY2VfbGluayB9IHJlbD1cIm5vcmVmZXJyZXJcIiB0YXJnZXQ9XCJfYmxhbmtcIj57IHN0cmluZ3MucGFuZWxfbm90aWNlX2xpbmtfdGV4dCB9PC9hPlxuXHRcdFx0XHRcdFx0XHQ8L3A+XG5cdFx0XHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdFx0XHQ8L0luc3BlY3RvckNvbnRyb2xzPlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBQcmludCBlbXB0eSBmb3JtcyBub3RpY2UuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4zXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElkIEJsb2NrIGNsaWVudCBJRC5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJuIHtKU1guRWxlbWVudH0gRmllbGQgc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRwcmludEVtcHR5Rm9ybXNOb3RpY2UoIGNsaWVudElkICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxJbnNwZWN0b3JDb250cm9scyBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWluc3BlY3Rvci1tYWluLXNldHRpbmdzXCI+XG5cdFx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsXCIgdGl0bGU9eyBzdHJpbmdzLmZvcm1fc2V0dGluZ3MgfT5cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlIHdwZm9ybXMtd2FybmluZyB3cGZvcm1zLWVtcHR5LWZvcm0tbm90aWNlXCIgc3R5bGU9eyB7IGRpc3BsYXk6ICdibG9jaycgfSB9PlxuXHRcdFx0XHRcdFx0XHRcdDxzdHJvbmc+eyBfXyggJ1lvdSBoYXZlbuKAmXQgY3JlYXRlZCBhIGZvcm0sIHlldCEnLCAnd3Bmb3Jtcy1saXRlJyApIH08L3N0cm9uZz5cblx0XHRcdFx0XHRcdFx0XHR7IF9fKCAnV2hhdCBhcmUgeW91IHdhaXRpbmcgZm9yPycsICd3cGZvcm1zLWxpdGUnICkgfVxuXHRcdFx0XHRcdFx0XHQ8L3A+XG5cdFx0XHRcdFx0XHRcdDxidXR0b24gdHlwZT1cImJ1dHRvblwiIGNsYXNzTmFtZT1cImdldC1zdGFydGVkLWJ1dHRvbiBjb21wb25lbnRzLWJ1dHRvbiBpcy1zZWNvbmRhcnlcIlxuXHRcdFx0XHRcdFx0XHRcdG9uQ2xpY2s9e1xuXHRcdFx0XHRcdFx0XHRcdFx0KCkgPT4ge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRhcHAub3BlbkJ1aWxkZXJQb3B1cCggY2xpZW50SWQgKTtcblx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdD5cblx0XHRcdFx0XHRcdFx0XHR7IF9fKCAnR2V0IFN0YXJ0ZWQnLCAnd3Bmb3Jtcy1saXRlJyApIH1cblx0XHRcdFx0XHRcdFx0PC9idXR0b24+XG5cdFx0XHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdFx0XHQ8L0luc3BlY3RvckNvbnRyb2xzPlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgRmllbGQgc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyAgICAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHNpemVPcHRpb25zIFNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJuIHtPYmplY3R9IEZpZWxkIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0RmllbGRTdHlsZXMoIHByb3BzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIHByb3BzICkgfSB0aXRsZT17IHN0cmluZ3MuZmllbGRfc3R5bGVzIH0+XG5cdFx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Ugd3Bmb3Jtcy11c2UtbW9kZXJuLW5vdGljZVwiPlxuXHRcdFx0XHRcdFx0XHQ8c3Ryb25nPnsgc3RyaW5ncy51c2VfbW9kZXJuX25vdGljZV9oZWFkIH08L3N0cm9uZz5cblx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX3RleHQgfSA8YSBocmVmPXsgc3RyaW5ncy51c2VfbW9kZXJuX25vdGljZV9saW5rIH0gcmVsPVwibm9yZWZlcnJlclwiIHRhcmdldD1cIl9ibGFua1wiPnsgc3RyaW5ncy5sZWFybl9tb3JlIH08L2E+XG5cdFx0XHRcdFx0XHQ8L3A+XG5cblx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZSB3cGZvcm1zLXdhcm5pbmcgd3Bmb3Jtcy1sZWFkLWZvcm0tbm90aWNlXCIgc3R5bGU9eyB7IGRpc3BsYXk6ICdub25lJyB9IH0+XG5cdFx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX2hlYWQgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MubGVhZF9mb3Jtc19wYW5lbF9ub3RpY2VfdGV4dCB9XG5cdFx0XHRcdFx0XHQ8L3A+XG5cblx0XHRcdFx0XHRcdDxGbGV4IGdhcD17IDQgfSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9eyAnd3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1mbGV4JyB9IGp1c3RpZnk9XCJzcGFjZS1iZXR3ZWVuXCI+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5zaXplIH1cblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5maWVsZFNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkU2l6ZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyX3JhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuZmllbGRCb3JkZXJSYWRpdXMgfVxuXHRcdFx0XHRcdFx0XHRcdFx0aXNVbml0U2VsZWN0VGFiYmFibGVcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkQm9yZGVyUmFkaXVzJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHQ8L0ZsZXg+XG5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb2xvci1waWNrZXJcIj5cblx0XHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbnRyb2wtbGFiZWxcIj57IHN0cmluZ3MuY29sb3JzIH08L2Rpdj5cblx0XHRcdFx0XHRcdFx0PFBhbmVsQ29sb3JTZXR0aW5nc1xuXHRcdFx0XHRcdFx0XHRcdF9fZXhwZXJpbWVudGFsSXNSZW5kZXJlZEluU2lkZWJhclxuXHRcdFx0XHRcdFx0XHRcdGVuYWJsZUFscGhhXG5cdFx0XHRcdFx0XHRcdFx0c2hvd1RpdGxlPXsgZmFsc2UgfVxuXHRcdFx0XHRcdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGFuZWxcIlxuXHRcdFx0XHRcdFx0XHRcdGNvbG9yU2V0dGluZ3M9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkQmFja2dyb3VuZENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MuYmFja2dyb3VuZCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkQm9yZGVyQ29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRCb3JkZXJDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmJvcmRlcixcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkVGV4dENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkVGV4dENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MudGV4dCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IExhYmVsIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBoYW5kbGVycyAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzaXplT3B0aW9ucyBTaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7T2JqZWN0fSBMYWJlbCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldExhYmVsU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIHByb3BzICkgfSB0aXRsZT17IHN0cmluZ3MubGFiZWxfc3R5bGVzIH0+XG5cdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5sYWJlbFNpemUgfVxuXHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZpeC1ib3R0b20tbWFyZ2luXCJcblx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnbGFiZWxTaXplJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHQvPlxuXG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXsgW1xuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5sYWJlbENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2xhYmVsQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5sYWJlbCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmxhYmVsU3VibGFiZWxDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdsYWJlbFN1YmxhYmVsQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5zdWJsYWJlbF9oaW50cy5yZXBsYWNlKCAnJmFtcDsnLCAnJicgKSxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmxhYmVsRXJyb3JDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdsYWJlbEVycm9yQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5lcnJvcl9tZXNzYWdlLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDwvUGFuZWxCb2R5PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgQnV0dG9uIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBoYW5kbGVycyAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzaXplT3B0aW9ucyBTaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7T2JqZWN0fSAgQnV0dG9uIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0QnV0dG9uU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIHByb3BzICkgfSB0aXRsZT17IHN0cmluZ3MuYnV0dG9uX3N0eWxlcyB9PlxuXHRcdFx0XHRcdFx0PEZsZXggZ2FwPXsgNCB9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT17ICd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXgnIH0ganVzdGlmeT1cInNwYWNlLWJldHdlZW5cIj5cblx0XHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLnNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvblNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2J1dHRvblNpemUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHQ8X19leHBlcmltZW50YWxVbml0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQm9yZGVyUmFkaXVzJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyX3JhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRpc1VuaXRTZWxlY3RUYWJiYWJsZVxuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlclJhZGl1cyB9IC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PC9GbGV4PlxuXG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXsgW1xuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5idXR0b25CYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQmFja2dyb3VuZENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MuYmFja2dyb3VuZCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvblRleHRDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdidXR0b25UZXh0Q29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy50ZXh0LFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdIH0gLz5cblx0XHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWxlZ2VuZCB3cGZvcm1zLWJ1dHRvbi1jb2xvci1ub3RpY2VcIj5cblx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MuYnV0dG9uX2NvbG9yX25vdGljZSB9XG5cdFx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBQYWdlIEluZGljYXRvciBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC43XG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7T2JqZWN0fSBQYWdlIEluZGljYXRvciBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldFBhZ2VJbmRpY2F0b3JTdHlsZXMoIHByb3BzLCBoYW5kbGVycyApIHtcblx0XHRcdFx0aWYgKCAhIGFwcC5oYXNQYWdlQnJlYWsoIGZvcm1MaXN0LCBwcm9wcy5hdHRyaWJ1dGVzLmZvcm1JZCApICkge1xuXHRcdFx0XHRcdHJldHVybiBudWxsO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGFwcC5nZXRQYW5lbENsYXNzKCBwcm9wcyApIH0gdGl0bGU9eyBzdHJpbmdzLm90aGVyX3N0eWxlcyB9PlxuXHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBpY2tlclwiPlxuXHRcdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29udHJvbC1sYWJlbFwiPnsgc3RyaW5ncy5jb2xvcnMgfTwvZGl2PlxuXHRcdFx0XHRcdFx0XHQ8UGFuZWxDb2xvclNldHRpbmdzXG5cdFx0XHRcdFx0XHRcdFx0X19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyXG5cdFx0XHRcdFx0XHRcdFx0ZW5hYmxlQWxwaGFcblx0XHRcdFx0XHRcdFx0XHRzaG93VGl0bGU9eyBmYWxzZSB9XG5cdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb2xvci1wYW5lbFwiXG5cdFx0XHRcdFx0XHRcdFx0Y29sb3JTZXR0aW5ncz17IFtcblx0XHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU6IHByb3BzLmF0dHJpYnV0ZXMucGFnZUJyZWFrQ29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAncGFnZUJyZWFrQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5wYWdlX2JyZWFrLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdIH0gLz5cblx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDwvUGFuZWxCb2R5PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgc3R5bGUgc2V0dGluZ3MgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gc2l6ZU9wdGlvbnMgU2l6ZSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge09iamVjdH0gSW5zcGVjdG9yIGNvbnRyb2xzIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRTdHlsZVNldHRpbmdzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxJbnNwZWN0b3JDb250cm9scyBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXN0eWxlLXNldHRpbmdzXCI+XG5cdFx0XHRcdFx0XHR7IGFwcC5qc3hQYXJ0cy5nZXRGaWVsZFN0eWxlcyggcHJvcHMsIGhhbmRsZXJzLCBzaXplT3B0aW9ucyApIH1cblx0XHRcdFx0XHRcdHsgYXBwLmpzeFBhcnRzLmdldExhYmVsU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkgfVxuXHRcdFx0XHRcdFx0eyBhcHAuanN4UGFydHMuZ2V0QnV0dG9uU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkgfVxuXHRcdFx0XHRcdFx0eyBhcHAuanN4UGFydHMuZ2V0UGFnZUluZGljYXRvclN0eWxlcyggcHJvcHMsIGhhbmRsZXJzICkgfVxuXHRcdFx0XHRcdDwvSW5zcGVjdG9yQ29udHJvbHM+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBhZHZhbmNlZCBzZXR0aW5ncyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBoYW5kbGVycyBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJuIHtPYmplY3R9IEluc3BlY3RvciBhZHZhbmNlZCBjb250cm9scyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0QWR2YW5jZWRTZXR0aW5ncyggcHJvcHMsIGhhbmRsZXJzICkge1xuXHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgcmVhY3QtaG9va3MvcnVsZXMtb2YtaG9va3Ncblx0XHRcdFx0Y29uc3QgWyBpc09wZW4sIHNldE9wZW4gXSA9IHVzZVN0YXRlKCBmYWxzZSApO1xuXHRcdFx0XHRjb25zdCBvcGVuTW9kYWwgPSAoKSA9PiBzZXRPcGVuKCB0cnVlICk7XG5cdFx0XHRcdGNvbnN0IGNsb3NlTW9kYWwgPSAoKSA9PiBzZXRPcGVuKCBmYWxzZSApO1xuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEluc3BlY3RvckFkdmFuY2VkQ29udHJvbHM+XG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT17IGFwcC5nZXRQYW5lbENsYXNzKCBwcm9wcyApIH0+XG5cdFx0XHRcdFx0XHRcdDxUZXh0YXJlYUNvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuY29weV9wYXN0ZV9zZXR0aW5ncyB9XG5cdFx0XHRcdFx0XHRcdFx0cm93cz1cIjRcIlxuXHRcdFx0XHRcdFx0XHRcdHNwZWxsQ2hlY2s9XCJmYWxzZVwiXG5cdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmNvcHlQYXN0ZUpzb25WYWx1ZSB9XG5cdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMucGFzdGVTZXR0aW5ncyggdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1sZWdlbmRcIiBkYW5nZXJvdXNseVNldElubmVySFRNTD17IHsgX19odG1sOiBzdHJpbmdzLmNvcHlfcGFzdGVfbm90aWNlIH0gfT48L2Rpdj5cblxuXHRcdFx0XHRcdFx0XHQ8QnV0dG9uIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItcmVzZXQtYnV0dG9uXCIgb25DbGljaz17IG9wZW5Nb2RhbCB9Pnsgc3RyaW5ncy5yZXNldF9zdHlsZV9zZXR0aW5ncyB9PC9CdXR0b24+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblxuXHRcdFx0XHRcdFx0eyBpc09wZW4gJiYgKFxuXHRcdFx0XHRcdFx0XHQ8TW9kYWwgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctbW9kYWxcIlxuXHRcdFx0XHRcdFx0XHRcdHRpdGxlPXsgc3RyaW5ncy5yZXNldF9zdHlsZV9zZXR0aW5ncyB9XG5cdFx0XHRcdFx0XHRcdFx0b25SZXF1ZXN0Q2xvc2U9eyBjbG9zZU1vZGFsIH0+XG5cblx0XHRcdFx0XHRcdFx0XHQ8cD57IHN0cmluZ3MucmVzZXRfc2V0dGluZ3NfY29uZmlybV90ZXh0IH08L3A+XG5cblx0XHRcdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyAzIH0gYWxpZ249XCJjZW50ZXJcIiBqdXN0aWZ5PVwiZmxleC1lbmRcIj5cblx0XHRcdFx0XHRcdFx0XHRcdDxCdXR0b24gaXNTZWNvbmRhcnkgb25DbGljaz17IGNsb3NlTW9kYWwgfT5cblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLmJ0bl9ubyB9XG5cdFx0XHRcdFx0XHRcdFx0XHQ8L0J1dHRvbj5cblxuXHRcdFx0XHRcdFx0XHRcdFx0PEJ1dHRvbiBpc1ByaW1hcnkgb25DbGljaz17ICgpID0+IHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0Y2xvc2VNb2RhbCgpO1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRoYW5kbGVycy5yZXNldFNldHRpbmdzKCk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9IH0+XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5idG5feWVzX3Jlc2V0IH1cblx0XHRcdFx0XHRcdFx0XHRcdDwvQnV0dG9uPlxuXHRcdFx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdFx0PC9Nb2RhbD5cblx0XHRcdFx0XHRcdCkgfVxuXHRcdFx0XHRcdDwvSW5zcGVjdG9yQWR2YW5jZWRDb250cm9scz5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIGNvbnRlbnQgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEJsb2NrIGNvbnRlbnQgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrRm9ybUNvbnRlbnQoIHByb3BzICkge1xuXHRcdFx0XHRpZiAoIHRyaWdnZXJTZXJ2ZXJSZW5kZXIgKSB7XG5cdFx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHRcdDxTZXJ2ZXJTaWRlUmVuZGVyXG5cdFx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3Itc2VydmVyLXNpZGUtcmVuZGVyZXJcIlxuXHRcdFx0XHRcdFx0XHRibG9jaz1cIndwZm9ybXMvZm9ybS1zZWxlY3RvclwiXG5cdFx0XHRcdFx0XHRcdGF0dHJpYnV0ZXM9eyBwcm9wcy5hdHRyaWJ1dGVzIH1cblx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0KTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGNvbnN0IGNsaWVudElkID0gcHJvcHMuY2xpZW50SWQ7XG5cdFx0XHRcdGNvbnN0IGJsb2NrID0gYXBwLmdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApO1xuXG5cdFx0XHRcdC8vIEluIHRoZSBjYXNlIG9mIGVtcHR5IGNvbnRlbnQsIHVzZSBzZXJ2ZXIgc2lkZSByZW5kZXJlci5cblx0XHRcdFx0Ly8gVGhpcyBoYXBwZW5zIHdoZW4gdGhlIGJsb2NrIGlzIGR1cGxpY2F0ZWQgb3IgY29udmVydGVkIHRvIGEgcmV1c2FibGUgYmxvY2suXG5cdFx0XHRcdGlmICggISBibG9jayB8fCAhIGJsb2NrLmlubmVySFRNTCApIHtcblx0XHRcdFx0XHR0cmlnZ2VyU2VydmVyUmVuZGVyID0gdHJ1ZTtcblxuXHRcdFx0XHRcdHJldHVybiBhcHAuanN4UGFydHMuZ2V0QmxvY2tGb3JtQ29udGVudCggcHJvcHMgKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXSA9IGJsb2Nrc1sgY2xpZW50SWQgXSB8fCB7fTtcblx0XHRcdFx0YmxvY2tzWyBjbGllbnRJZCBdLmJsb2NrSFRNTCA9IGJsb2NrLmlubmVySFRNTDtcblx0XHRcdFx0YmxvY2tzWyBjbGllbnRJZCBdLmxvYWRlZEZvcm1JZCA9IHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkO1xuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEZyYWdtZW50IGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtZm9ybS1odG1sXCI+XG5cdFx0XHRcdFx0XHQ8ZGl2IGRhbmdlcm91c2x5U2V0SW5uZXJIVE1MPXsgeyBfX2h0bWw6IGJsb2Nrc1sgY2xpZW50SWQgXS5ibG9ja0hUTUwgfSB9IC8+XG5cdFx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIHByZXZpZXcgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEJsb2NrIHByZXZpZXcgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrUHJldmlldygpIHtcblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stcHJldmlld1wiPlxuXHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX3ByZXZpZXdfdXJsIH0gc3R5bGU9eyB7IHdpZHRoOiAnMTAwJScgfSB9IGFsdD1cIlwiIC8+XG5cdFx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguM1xuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSB7XG5cdFx0XHRcdGNvbnN0IGNsaWVudElkID0gcHJvcHMuY2xpZW50SWQ7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stZW1wdHlcIj5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1uby1mb3JtLXByZXZpZXdcIj5cblx0XHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX2VtcHR5X3VybCB9IGFsdD1cIlwiIC8+XG5cdFx0XHRcdFx0XHRcdDxwPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J1lvdSBjYW4gdXNlIDxiPldQRm9ybXM8L2I+IHRvIGJ1aWxkIGNvbnRhY3QgZm9ybXMsIHN1cnZleXMsIHBheW1lbnQgZm9ybXMsIGFuZCBtb3JlIHdpdGgganVzdCBhIGZldyBjbGlja3MuJyxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQnd3Bmb3Jtcy1saXRlJ1xuXHRcdFx0XHRcdFx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0YjogPHN0cm9uZyAvPixcblx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0KVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzc05hbWU9XCJnZXQtc3RhcnRlZC1idXR0b24gY29tcG9uZW50cy1idXR0b24gaXMtcHJpbWFyeVwiXG5cdFx0XHRcdFx0XHRcdFx0b25DbGljaz17XG5cdFx0XHRcdFx0XHRcdFx0XHQoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGFwcC5vcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJZCApO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0XHRcdHsgX18oICdHZXQgU3RhcnRlZCcsICd3cGZvcm1zLWxpdGUnICkgfVxuXHRcdFx0XHRcdFx0XHQ8L2J1dHRvbj5cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwiZW1wdHktZGVzY1wiPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J05lZWQgc29tZSBoZWxwPyBDaGVjayBvdXQgb3VyIDxhPmNvbXByZWhlbnNpdmUgZ3VpZGUuPC9hPicsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J3dwZm9ybXMtbGl0ZSdcblx0XHRcdFx0XHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBqc3gtYTExeS9hbmNob3ItaGFzLWNvbnRlbnRcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRhOiA8YSBocmVmPXsgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci53cGZvcm1zX2d1aWRlIH0gdGFyZ2V0PVwiX2JsYW5rXCIgcmVsPVwibm9vcGVuZXIgbm9yZWZlcnJlclwiIC8+LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHQpXG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHQ8L3A+XG5cblx0XHRcdFx0XHRcdFx0eyAvKiBUZW1wbGF0ZSBmb3IgcG9wdXAgd2l0aCBidWlsZGVyIGlmcmFtZSAqLyB9XG5cdFx0XHRcdFx0XHRcdDxkaXYgaWQ9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cFwiIGNsYXNzTmFtZT1cIndwZm9ybXMtYnVpbGRlci1wb3B1cFwiPlxuXHRcdFx0XHRcdFx0XHRcdDxpZnJhbWUgc3JjPVwiYWJvdXQ6YmxhbmtcIiB3aWR0aD1cIjEwMCVcIiBoZWlnaHQ9XCIxMDAlXCIgaWQ9XCJ3cGZvcm1zLWJ1aWxkZXItaWZyYW1lXCIgdGl0bGU9XCJXUEZvcm1zIEJ1aWxkZXIgUG9wdXBcIj48L2lmcmFtZT5cblx0XHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0XHQ8L0ZyYWdtZW50PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgYmxvY2sgcGxhY2Vob2xkZXIgKGZvcm0gc2VsZWN0b3IpIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBhdHRyaWJ1dGVzICBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGZvcm1PcHRpb25zIEZvcm0gc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJuIHtKU1guRWxlbWVudH0gQmxvY2sgcGxhY2Vob2xkZXIgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrUGxhY2Vob2xkZXIoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApIHtcblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8UGxhY2Vob2xkZXJcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3Itd3JhcFwiXG5cdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXdyYXBcIj5cblx0XHRcdFx0XHRcdDxpbWcgc3JjPXsgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5sb2dvX3VybCB9IGFsdD1cIlwiIC8+XG5cdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXNlbGVjdC1jb250cm9sXCJcblx0XHRcdFx0XHRcdFx0dmFsdWU9eyBhdHRyaWJ1dGVzLmZvcm1JZCB9XG5cdFx0XHRcdFx0XHRcdG9wdGlvbnM9eyBmb3JtT3B0aW9ucyB9XG5cdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdmb3JtSWQnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0PC9QbGFjZWhvbGRlcj5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERldGVybWluZSBpZiB0aGUgZm9ybSBoYXMgYSBQYWdlYnJlYWsgZmllbGQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljdcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSAgICAgICAgZm9ybXMgIFRoZSBmb3JtcycgZGF0YSBvYmplY3QuXG5cdFx0ICogQHBhcmFtIHtudW1iZXJ8c3RyaW5nfSBmb3JtSWQgRm9ybSBJRC5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgd2hlbiB0aGUgZm9ybSBoYXMgYSBQYWdlYnJlYWsgZmllbGQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRoYXNQYWdlQnJlYWsoIGZvcm1zLCBmb3JtSWQgKSB7XG5cdFx0XHRjb25zdCBjdXJyZW50Rm9ybSA9IGZvcm1zLmZpbmQoICggZm9ybSApID0+IHBhcnNlSW50KCBmb3JtLklELCAxMCApID09PSBwYXJzZUludCggZm9ybUlkLCAxMCApICk7XG5cblx0XHRcdGlmICggISBjdXJyZW50Rm9ybS5wb3N0X2NvbnRlbnQgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgZmllbGRzID0gSlNPTi5wYXJzZSggY3VycmVudEZvcm0ucG9zdF9jb250ZW50ICk/LmZpZWxkcztcblxuXHRcdFx0cmV0dXJuIE9iamVjdC52YWx1ZXMoIGZpZWxkcyApLnNvbWUoICggZmllbGQgKSA9PiBmaWVsZC50eXBlID09PSAncGFnZWJyZWFrJyApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgU3R5bGUgU2V0dGluZ3MgcGFuZWwgY2xhc3MuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7c3RyaW5nfSBTdHlsZSBTZXR0aW5ncyBwYW5lbCBjbGFzcy5cblx0XHQgKi9cblx0XHRnZXRQYW5lbENsYXNzKCBwcm9wcyApIHtcblx0XHRcdGxldCBjc3NDbGFzcyA9ICd3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbCB3cGZvcm1zLWJsb2NrLXNldHRpbmdzLScgKyBwcm9wcy5jbGllbnRJZDtcblxuXHRcdFx0aWYgKCAhIGFwcC5pc0Z1bGxTdHlsaW5nRW5hYmxlZCgpICkge1xuXHRcdFx0XHRjc3NDbGFzcyArPSAnIGRpc2FibGVkX3BhbmVsJztcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGNzc0NsYXNzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgd2hldGhlciB0aGUgZnVsbCBzdHlsaW5nIGlzIGVuYWJsZWQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFdoZXRoZXIgdGhlIGZ1bGwgc3R5bGluZyBpcyBlbmFibGVkLlxuXHRcdCAqL1xuXHRcdGlzRnVsbFN0eWxpbmdFbmFibGVkKCkge1xuXHRcdFx0cmV0dXJuIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuaXNfbW9kZXJuX21hcmt1cCAmJiB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmlzX2Z1bGxfc3R5bGluZztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIGNvbnRhaW5lciBET00gZWxlbWVudC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtFbGVtZW50fSBCbG9jayBjb250YWluZXIuXG5cdFx0ICovXG5cdFx0Z2V0QmxvY2tDb250YWluZXIoIHByb3BzICkge1xuXHRcdFx0Y29uc3QgYmxvY2tTZWxlY3RvciA9IGAjYmxvY2stJHsgcHJvcHMuY2xpZW50SWQgfSA+IGRpdmA7XG5cdFx0XHRsZXQgYmxvY2sgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCBibG9ja1NlbGVjdG9yICk7XG5cblx0XHRcdC8vIEZvciBGU0UgLyBHdXRlbmJlcmcgcGx1Z2luIHdlIG5lZWQgdG8gdGFrZSBhIGxvb2sgaW5zaWRlIHRoZSBpZnJhbWUuXG5cdFx0XHRpZiAoICEgYmxvY2sgKSB7XG5cdFx0XHRcdGNvbnN0IGVkaXRvckNhbnZhcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICdpZnJhbWVbbmFtZT1cImVkaXRvci1jYW52YXNcIl0nICk7XG5cblx0XHRcdFx0YmxvY2sgPSBlZGl0b3JDYW52YXMgJiYgZWRpdG9yQ2FudmFzLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQucXVlcnlTZWxlY3RvciggYmxvY2tTZWxlY3RvciApO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gYmxvY2s7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzZXR0aW5ncyBmaWVsZHMgZXZlbnQgaGFuZGxlcnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBPYmplY3QgdGhhdCBjb250YWlucyBldmVudCBoYW5kbGVycyBmb3IgdGhlIHNldHRpbmdzIGZpZWxkcy5cblx0XHQgKi9cblx0XHRnZXRTZXR0aW5nc0ZpZWxkc0hhbmRsZXJzKCBwcm9wcyApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uXG5cdFx0XHRyZXR1cm4ge1xuXG5cdFx0XHRcdC8qKlxuXHRcdFx0XHQgKiBGaWVsZCBzdHlsZSBhdHRyaWJ1dGUgY2hhbmdlIGV2ZW50IGhhbmRsZXIuXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAcGFyYW0ge3N0cmluZ30gYXR0cmlidXRlIEF0dHJpYnV0ZSBuYW1lLlxuXHRcdFx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsdWUgICAgIE5ldyBhdHRyaWJ1dGUgdmFsdWUuXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRzdHlsZUF0dHJDaGFuZ2UoIGF0dHJpYnV0ZSwgdmFsdWUgKSB7XG5cdFx0XHRcdFx0Y29uc3QgYmxvY2sgPSBhcHAuZ2V0QmxvY2tDb250YWluZXIoIHByb3BzICksXG5cdFx0XHRcdFx0XHRjb250YWluZXIgPSBibG9jay5xdWVyeVNlbGVjdG9yKCBgI3dwZm9ybXMtJHsgcHJvcHMuYXR0cmlidXRlcy5mb3JtSWQgfWAgKSxcblx0XHRcdFx0XHRcdHByb3BlcnR5ID0gYXR0cmlidXRlLnJlcGxhY2UoIC9bQS1aXS9nLCAoIGxldHRlciApID0+IGAtJHsgbGV0dGVyLnRvTG93ZXJDYXNlKCkgfWAgKSxcblx0XHRcdFx0XHRcdHNldEF0dHIgPSB7fTtcblxuXHRcdFx0XHRcdGlmICggY29udGFpbmVyICkge1xuXHRcdFx0XHRcdFx0c3dpdGNoICggcHJvcGVydHkgKSB7XG5cdFx0XHRcdFx0XHRcdGNhc2UgJ2ZpZWxkLXNpemUnOlxuXHRcdFx0XHRcdFx0XHRjYXNlICdsYWJlbC1zaXplJzpcblx0XHRcdFx0XHRcdFx0Y2FzZSAnYnV0dG9uLXNpemUnOlxuXHRcdFx0XHRcdFx0XHRcdGZvciAoIGNvbnN0IGtleSBpbiBzaXplc1sgcHJvcGVydHkgXVsgdmFsdWUgXSApIHtcblx0XHRcdFx0XHRcdFx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eShcblx0XHRcdFx0XHRcdFx0XHRcdFx0YC0td3Bmb3Jtcy0keyBwcm9wZXJ0eSB9LSR7IGtleSB9YCxcblx0XHRcdFx0XHRcdFx0XHRcdFx0c2l6ZXNbIHByb3BlcnR5IF1bIHZhbHVlIF1bIGtleSBdLFxuXHRcdFx0XHRcdFx0XHRcdFx0KTtcblx0XHRcdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdFx0XHRicmVhaztcblxuXHRcdFx0XHRcdFx0XHRkZWZhdWx0OlxuXHRcdFx0XHRcdFx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eSggYC0td3Bmb3Jtcy0keyBwcm9wZXJ0eSB9YCwgdmFsdWUgKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRzZXRBdHRyWyBhdHRyaWJ1dGUgXSA9IHZhbHVlO1xuXG5cdFx0XHRcdFx0cHJvcHMuc2V0QXR0cmlidXRlcyggc2V0QXR0ciApO1xuXG5cdFx0XHRcdFx0dHJpZ2dlclNlcnZlclJlbmRlciA9IGZhbHNlO1xuXG5cdFx0XHRcdFx0dGhpcy51cGRhdGVDb3B5UGFzdGVDb250ZW50KCk7XG5cblx0XHRcdFx0XHQkKCB3aW5kb3cgKS50cmlnZ2VyKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclN0eWxlQXR0ckNoYW5nZScsIFsgYmxvY2ssIHByb3BzLCBhdHRyaWJ1dGUsIHZhbHVlIF0gKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogRmllbGQgcmVndWxhciBhdHRyaWJ1dGUgY2hhbmdlIGV2ZW50IGhhbmRsZXIuXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAcGFyYW0ge3N0cmluZ30gYXR0cmlidXRlIEF0dHJpYnV0ZSBuYW1lLlxuXHRcdFx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsdWUgICAgIE5ldyBhdHRyaWJ1dGUgdmFsdWUuXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRhdHRyQ2hhbmdlKCBhdHRyaWJ1dGUsIHZhbHVlICkge1xuXHRcdFx0XHRcdGNvbnN0IHNldEF0dHIgPSB7fTtcblxuXHRcdFx0XHRcdHNldEF0dHJbIGF0dHJpYnV0ZSBdID0gdmFsdWU7XG5cblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCBzZXRBdHRyICk7XG5cblx0XHRcdFx0XHR0cmlnZ2VyU2VydmVyUmVuZGVyID0gdHJ1ZTtcblxuXHRcdFx0XHRcdHRoaXMudXBkYXRlQ29weVBhc3RlQ29udGVudCgpO1xuXHRcdFx0XHR9LFxuXG5cdFx0XHRcdC8qKlxuXHRcdFx0XHQgKiBSZXNldCBGb3JtIFN0eWxlcyBzZXR0aW5ncyB0byBkZWZhdWx0cy5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRyZXNldFNldHRpbmdzKCkge1xuXHRcdFx0XHRcdGZvciAoIGNvbnN0IGtleSBpbiBkZWZhdWx0U3R5bGVTZXR0aW5ncyApIHtcblx0XHRcdFx0XHRcdHRoaXMuc3R5bGVBdHRyQ2hhbmdlKCBrZXksIGRlZmF1bHRTdHlsZVNldHRpbmdzWyBrZXkgXSApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogVXBkYXRlIGNvbnRlbnQgb2YgdGhlIFwiQ29weS9QYXN0ZVwiIGZpZWxkcy5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHR1cGRhdGVDb3B5UGFzdGVDb250ZW50KCkge1xuXHRcdFx0XHRcdGNvbnN0IGNvbnRlbnQgPSB7fTtcblx0XHRcdFx0XHRjb25zdCBhdHRzID0gd3AuZGF0YS5zZWxlY3QoICdjb3JlL2Jsb2NrLWVkaXRvcicgKS5nZXRCbG9ja0F0dHJpYnV0ZXMoIHByb3BzLmNsaWVudElkICk7XG5cblx0XHRcdFx0XHRmb3IgKCBjb25zdCBrZXkgaW4gZGVmYXVsdFN0eWxlU2V0dGluZ3MgKSB7XG5cdFx0XHRcdFx0XHRjb250ZW50WyBrZXkgXSA9IGF0dHNbIGtleSBdO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHsgY29weVBhc3RlSnNvblZhbHVlOiBKU09OLnN0cmluZ2lmeSggY29udGVudCApIH0gKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogUGFzdGUgc2V0dGluZ3MgaGFuZGxlci5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0cGFzdGVTZXR0aW5ncyggdmFsdWUgKSB7XG5cdFx0XHRcdFx0Y29uc3QgcGFzdGVBdHRyaWJ1dGVzID0gYXBwLnBhcnNlVmFsaWRhdGVKc29uKCB2YWx1ZSApO1xuXG5cdFx0XHRcdFx0aWYgKCAhIHBhc3RlQXR0cmlidXRlcyApIHtcblx0XHRcdFx0XHRcdHdwLmRhdGEuZGlzcGF0Y2goICdjb3JlL25vdGljZXMnICkuY3JlYXRlRXJyb3JOb3RpY2UoXG5cdFx0XHRcdFx0XHRcdHN0cmluZ3MuY29weV9wYXN0ZV9lcnJvcixcblx0XHRcdFx0XHRcdFx0eyBpZDogJ3dwZm9ybXMtanNvbi1wYXJzZS1lcnJvcicgfVxuXHRcdFx0XHRcdFx0KTtcblxuXHRcdFx0XHRcdFx0dGhpcy51cGRhdGVDb3B5UGFzdGVDb250ZW50KCk7XG5cblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRwYXN0ZUF0dHJpYnV0ZXMuY29weVBhc3RlSnNvblZhbHVlID0gdmFsdWU7XG5cblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCBwYXN0ZUF0dHJpYnV0ZXMgKTtcblxuXHRcdFx0XHRcdHRyaWdnZXJTZXJ2ZXJSZW5kZXIgPSB0cnVlO1xuXHRcdFx0XHR9LFxuXHRcdFx0fTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUGFyc2UgYW5kIHZhbGlkYXRlIEpTT04gc3RyaW5nLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsdWUgSlNPTiBzdHJpbmcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufG9iamVjdH0gUGFyc2VkIEpTT04gb2JqZWN0IE9SIGZhbHNlIG9uIGVycm9yLlxuXHRcdCAqL1xuXHRcdHBhcnNlVmFsaWRhdGVKc29uKCB2YWx1ZSApIHtcblx0XHRcdGlmICggdHlwZW9mIHZhbHVlICE9PSAnc3RyaW5nJyApIHtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHRsZXQgYXR0cztcblxuXHRcdFx0dHJ5IHtcblx0XHRcdFx0YXR0cyA9IEpTT04ucGFyc2UoIHZhbHVlICk7XG5cdFx0XHR9IGNhdGNoICggZXJyb3IgKSB7XG5cdFx0XHRcdGF0dHMgPSBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGF0dHM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBXUEZvcm1zIGljb24gRE9NIGVsZW1lbnQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge0RPTS5lbGVtZW50fSBXUEZvcm1zIGljb24gRE9NIGVsZW1lbnQuXG5cdFx0ICovXG5cdFx0Z2V0SWNvbigpIHtcblx0XHRcdHJldHVybiBjcmVhdGVFbGVtZW50KFxuXHRcdFx0XHQnc3ZnJyxcblx0XHRcdFx0eyB3aWR0aDogMjAsIGhlaWdodDogMjAsIHZpZXdCb3g6ICcwIDAgNjEyIDYxMicsIGNsYXNzTmFtZTogJ2Rhc2hpY29uJyB9LFxuXHRcdFx0XHRjcmVhdGVFbGVtZW50KFxuXHRcdFx0XHRcdCdwYXRoJyxcblx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRmaWxsOiAnY3VycmVudENvbG9yJyxcblx0XHRcdFx0XHRcdGQ6ICdNNTQ0LDBINjhDMzAuNDQ1LDAsMCwzMC40NDUsMCw2OHY0NzZjMCwzNy41NTYsMzAuNDQ1LDY4LDY4LDY4aDQ3NmMzNy41NTYsMCw2OC0zMC40NDQsNjgtNjhWNjggQzYxMiwzMC40NDUsNTgxLjU1NiwwLDU0NCwweiBNNDY0LjQ0LDY4TDM4Ny42LDEyMC4wMkwzMjMuMzQsNjhINDY0LjQ0eiBNMjg4LjY2LDY4bC02NC4yNiw1Mi4wMkwxNDcuNTYsNjhIMjg4LjY2eiBNNTQ0LDU0NEg2OCBWNjhoMjIuMWwxMzYsOTIuMTRsNzkuOS02NC42bDc5LjU2LDY0LjZsMTM2LTkyLjE0SDU0NFY1NDR6IE0xMTQuMjQsMjYzLjE2aDk1Ljg4di00OC4yOGgtOTUuODhWMjYzLjE2eiBNMTE0LjI0LDM2MC40aDk1Ljg4IHYtNDguNjJoLTk1Ljg4VjM2MC40eiBNMjQyLjc2LDM2MC40aDI1NXYtNDguNjJoLTI1NVYzNjAuNEwyNDIuNzYsMzYwLjR6IE0yNDIuNzYsMjYzLjE2aDI1NXYtNDguMjhoLTI1NVYyNjMuMTZMMjQyLjc2LDI2My4xNnogTTM2OC4yMiw0NTcuM2gxMjkuNTRWNDA4SDM2OC4yMlY0NTcuM3onLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdCksXG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgYmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdCAqL1xuXHRcdGdldEJsb2NrQXR0cmlidXRlcygpIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uXG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRjbGllbnRJZDoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6ICcnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmb3JtSWQ6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5mb3JtSWQsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGRpc3BsYXlUaXRsZToge1xuXHRcdFx0XHRcdHR5cGU6ICdib29sZWFuJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5kaXNwbGF5VGl0bGUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGRpc3BsYXlEZXNjOiB7XG5cdFx0XHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmRpc3BsYXlEZXNjLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRwcmV2aWV3OiB7XG5cdFx0XHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZFNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZFNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQm9yZGVyUmFkaXVzOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCb3JkZXJSYWRpdXMsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQmFja2dyb3VuZENvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQm9yZGVyQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZEJvcmRlckNvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZFRleHRDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmZpZWxkVGV4dENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRsYWJlbFNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbFNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGxhYmVsQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRsYWJlbFN1YmxhYmVsQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbFN1YmxhYmVsQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGxhYmVsRXJyb3JDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmxhYmVsRXJyb3JDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uU2l6ZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvblNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJ1dHRvbkJvcmRlclJhZGl1czoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvbkJvcmRlclJhZGl1cyxcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uQmFja2dyb3VuZENvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYnV0dG9uQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b25UZXh0Q29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5idXR0b25UZXh0Q29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdHBhZ2VCcmVha0NvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMucGFnZUJyZWFrQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGNvcHlQYXN0ZUpzb25WYWx1ZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmNvcHlQYXN0ZUpzb25WYWx1ZSxcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBmb3JtIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge0FycmF5fSBGb3JtIG9wdGlvbnMuXG5cdFx0ICovXG5cdFx0Z2V0Rm9ybU9wdGlvbnMoKSB7XG5cdFx0XHRjb25zdCBmb3JtT3B0aW9ucyA9IGZvcm1MaXN0Lm1hcCggKCB2YWx1ZSApID0+IChcblx0XHRcdFx0eyB2YWx1ZTogdmFsdWUuSUQsIGxhYmVsOiB2YWx1ZS5wb3N0X3RpdGxlIH1cblx0XHRcdCkgKTtcblxuXHRcdFx0Zm9ybU9wdGlvbnMudW5zaGlmdCggeyB2YWx1ZTogJycsIGxhYmVsOiBzdHJpbmdzLmZvcm1fc2VsZWN0IH0gKTtcblxuXHRcdFx0cmV0dXJuIGZvcm1PcHRpb25zO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgc2l6ZSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtBcnJheX0gU2l6ZSBvcHRpb25zLlxuXHRcdCAqL1xuXHRcdGdldFNpemVPcHRpb25zKCkge1xuXHRcdFx0cmV0dXJuIFtcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLnNtYWxsLFxuXHRcdFx0XHRcdHZhbHVlOiAnc21hbGwnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHR7XG5cdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MubWVkaXVtLFxuXHRcdFx0XHRcdHZhbHVlOiAnbWVkaXVtJyxcblx0XHRcdFx0fSxcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmxhcmdlLFxuXHRcdFx0XHRcdHZhbHVlOiAnbGFyZ2UnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JFZGl0YCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZSAgICAgRXZlbnQgb2JqZWN0LlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqL1xuXHRcdGJsb2NrRWRpdCggZSwgcHJvcHMgKSB7XG5cdFx0XHRjb25zdCBibG9jayA9IGFwcC5nZXRCbG9ja0NvbnRhaW5lciggcHJvcHMgKTtcblxuXHRcdFx0aWYgKCAhIGJsb2NrIHx8ICEgYmxvY2suZGF0YXNldCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRhcHAuaW5pdExlYWRGb3JtU2V0dGluZ3MoIGJsb2NrLnBhcmVudEVsZW1lbnQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBMZWFkIEZvcm0gU2V0dGluZ3MgcGFuZWxzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0VsZW1lbnR9IGJsb2NrIEJsb2NrIGVsZW1lbnQuXG5cdFx0ICovXG5cdFx0aW5pdExlYWRGb3JtU2V0dGluZ3MoIGJsb2NrICkge1xuXHRcdFx0aWYgKCAhIGJsb2NrIHx8ICEgYmxvY2suZGF0YXNldCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoICEgYXBwLmlzRnVsbFN0eWxpbmdFbmFibGVkKCkgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgY2xpZW50SWQgPSBibG9jay5kYXRhc2V0LmJsb2NrO1xuXHRcdFx0Y29uc3QgJGZvcm0gPSAkKCBibG9jay5xdWVyeVNlbGVjdG9yKCAnLndwZm9ybXMtY29udGFpbmVyJyApICk7XG5cdFx0XHRjb25zdCAkcGFuZWwgPSAkKCBgLndwZm9ybXMtYmxvY2stc2V0dGluZ3MtJHsgY2xpZW50SWQgfWAgKTtcblxuXHRcdFx0aWYgKCAkZm9ybS5oYXNDbGFzcyggJ3dwZm9ybXMtbGVhZC1mb3Jtcy1jb250YWluZXInICkgKSB7XG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5hZGRDbGFzcyggJ2Rpc2FibGVkX3BhbmVsJyApXG5cdFx0XHRcdFx0LmZpbmQoICcud3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlLndwZm9ybXMtbGVhZC1mb3JtLW5vdGljZScgKVxuXHRcdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ2Jsb2NrJyApO1xuXG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5maW5kKCAnLndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZS53cGZvcm1zLXVzZS1tb2Rlcm4tbm90aWNlJyApXG5cdFx0XHRcdFx0LmNzcyggJ2Rpc3BsYXknLCAnbm9uZScgKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQucmVtb3ZlQ2xhc3MoICdkaXNhYmxlZF9wYW5lbCcgKVxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy1sZWFkLWZvcm0tbm90aWNlJyApXG5cdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ25vbmUnICk7XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy11c2UtbW9kZXJuLW5vdGljZScgKVxuXHRcdFx0XHQuY3NzKCAnZGlzcGxheScsIG51bGwgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JGb3JtTG9hZGVkYCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZSBFdmVudCBvYmplY3QuXG5cdFx0ICovXG5cdFx0Zm9ybUxvYWRlZCggZSApIHtcblx0XHRcdGFwcC5pbml0TGVhZEZvcm1TZXR0aW5ncyggZS5kZXRhaWwuYmxvY2sgKTtcblx0XHRcdGFwcC51cGRhdGVBY2NlbnRDb2xvcnMoIGUuZGV0YWlsICk7XG5cdFx0XHRhcHAubG9hZENob2ljZXNKUyggZS5kZXRhaWwgKTtcblx0XHRcdGFwcC5pbml0UmljaFRleHRGaWVsZCggZS5kZXRhaWwuZm9ybUlkICk7XG5cblx0XHRcdCQoIGUuZGV0YWlsLmJsb2NrIClcblx0XHRcdFx0Lm9mZiggJ2NsaWNrJyApXG5cdFx0XHRcdC5vbiggJ2NsaWNrJywgYXBwLmJsb2NrQ2xpY2sgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQ2xpY2sgb24gdGhlIGJsb2NrIGV2ZW50IGhhbmRsZXIuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBlIEV2ZW50IG9iamVjdC5cblx0XHQgKi9cblx0XHRibG9ja0NsaWNrKCBlICkge1xuXHRcdFx0YXBwLmluaXRMZWFkRm9ybVNldHRpbmdzKCBlLmN1cnJlbnRUYXJnZXQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogVXBkYXRlIGFjY2VudCBjb2xvcnMgb2Ygc29tZSBmaWVsZHMgaW4gR0IgYmxvY2sgaW4gTW9kZXJuIE1hcmt1cCBtb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZGV0YWlsIEV2ZW50IGRldGFpbHMgb2JqZWN0LlxuXHRcdCAqL1xuXHRcdHVwZGF0ZUFjY2VudENvbG9ycyggZGV0YWlsICkge1xuXHRcdFx0aWYgKFxuXHRcdFx0XHQhIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuaXNfbW9kZXJuX21hcmt1cCB8fFxuXHRcdFx0XHQhIHdpbmRvdy5XUEZvcm1zIHx8XG5cdFx0XHRcdCEgd2luZG93LldQRm9ybXMuRnJvbnRlbmRNb2Rlcm4gfHxcblx0XHRcdFx0ISBkZXRhaWwuYmxvY2tcblx0XHRcdCkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0ICRmb3JtID0gJCggZGV0YWlsLmJsb2NrLnF1ZXJ5U2VsZWN0b3IoIGAjd3Bmb3Jtcy0keyBkZXRhaWwuZm9ybUlkIH1gICkgKSxcblx0XHRcdFx0RnJvbnRlbmRNb2Rlcm4gPSB3aW5kb3cuV1BGb3Jtcy5Gcm9udGVuZE1vZGVybjtcblxuXHRcdFx0RnJvbnRlbmRNb2Rlcm4udXBkYXRlR0JCbG9ja1BhZ2VJbmRpY2F0b3JDb2xvciggJGZvcm0gKTtcblx0XHRcdEZyb250ZW5kTW9kZXJuLnVwZGF0ZUdCQmxvY2tJY29uQ2hvaWNlc0NvbG9yKCAkZm9ybSApO1xuXHRcdFx0RnJvbnRlbmRNb2Rlcm4udXBkYXRlR0JCbG9ja1JhdGluZ0NvbG9yKCAkZm9ybSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBJbml0IE1vZGVybiBzdHlsZSBEcm9wZG93biBmaWVsZHMgKDxzZWxlY3Q+KS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGRldGFpbCBFdmVudCBkZXRhaWxzIG9iamVjdC5cblx0XHQgKi9cblx0XHRsb2FkQ2hvaWNlc0pTKCBkZXRhaWwgKSB7XG5cdFx0XHRpZiAoIHR5cGVvZiB3aW5kb3cuQ2hvaWNlcyAhPT0gJ2Z1bmN0aW9uJyApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCAkZm9ybSA9ICQoIGRldGFpbC5ibG9jay5xdWVyeVNlbGVjdG9yKCBgI3dwZm9ybXMtJHsgZGV0YWlsLmZvcm1JZCB9YCApICk7XG5cblx0XHRcdCRmb3JtLmZpbmQoICcuY2hvaWNlc2pzLXNlbGVjdCcgKS5lYWNoKCBmdW5jdGlvbiggaWR4LCBlbCApIHtcblx0XHRcdFx0Y29uc3QgJGVsID0gJCggZWwgKTtcblxuXHRcdFx0XHRpZiAoICRlbC5kYXRhKCAnY2hvaWNlJyApID09PSAnYWN0aXZlJyApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRjb25zdCBhcmdzID0gd2luZG93LndwZm9ybXNfY2hvaWNlc2pzX2NvbmZpZyB8fCB7fSxcblx0XHRcdFx0XHRzZWFyY2hFbmFibGVkID0gJGVsLmRhdGEoICdzZWFyY2gtZW5hYmxlZCcgKSxcblx0XHRcdFx0XHQkZmllbGQgPSAkZWwuY2xvc2VzdCggJy53cGZvcm1zLWZpZWxkJyApO1xuXG5cdFx0XHRcdGFyZ3Muc2VhcmNoRW5hYmxlZCA9ICd1bmRlZmluZWQnICE9PSB0eXBlb2Ygc2VhcmNoRW5hYmxlZCA/IHNlYXJjaEVuYWJsZWQgOiB0cnVlO1xuXHRcdFx0XHRhcmdzLmNhbGxiYWNrT25Jbml0ID0gZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0Y29uc3Qgc2VsZiA9IHRoaXMsXG5cdFx0XHRcdFx0XHQkZWxlbWVudCA9ICQoIHNlbGYucGFzc2VkRWxlbWVudC5lbGVtZW50ICksXG5cdFx0XHRcdFx0XHQkaW5wdXQgPSAkKCBzZWxmLmlucHV0LmVsZW1lbnQgKSxcblx0XHRcdFx0XHRcdHNpemVDbGFzcyA9ICRlbGVtZW50LmRhdGEoICdzaXplLWNsYXNzJyApO1xuXG5cdFx0XHRcdFx0Ly8gQWRkIENTUy1jbGFzcyBmb3Igc2l6ZS5cblx0XHRcdFx0XHRpZiAoIHNpemVDbGFzcyApIHtcblx0XHRcdFx0XHRcdCQoIHNlbGYuY29udGFpbmVyT3V0ZXIuZWxlbWVudCApLmFkZENsYXNzKCBzaXplQ2xhc3MgKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvKipcblx0XHRcdFx0XHQgKiBJZiBhIG11bHRpcGxlIHNlbGVjdCBoYXMgc2VsZWN0ZWQgY2hvaWNlcyAtIGhpZGUgYSBwbGFjZWhvbGRlciB0ZXh0LlxuXHRcdFx0XHRcdCAqIEluIGNhc2UgaWYgc2VsZWN0IGlzIGVtcHR5IC0gd2UgcmV0dXJuIHBsYWNlaG9sZGVyIHRleHQgYmFjay5cblx0XHRcdFx0XHQgKi9cblx0XHRcdFx0XHRpZiAoICRlbGVtZW50LnByb3AoICdtdWx0aXBsZScgKSApIHtcblx0XHRcdFx0XHRcdC8vIE9uIGluaXQgZXZlbnQuXG5cdFx0XHRcdFx0XHQkaW5wdXQuZGF0YSggJ3BsYWNlaG9sZGVyJywgJGlucHV0LmF0dHIoICdwbGFjZWhvbGRlcicgKSApO1xuXG5cdFx0XHRcdFx0XHRpZiAoIHNlbGYuZ2V0VmFsdWUoIHRydWUgKS5sZW5ndGggKSB7XG5cdFx0XHRcdFx0XHRcdCRpbnB1dC5yZW1vdmVBdHRyKCAncGxhY2Vob2xkZXInICk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0dGhpcy5kaXNhYmxlKCk7XG5cdFx0XHRcdFx0JGZpZWxkLmZpbmQoICcuaXMtZGlzYWJsZWQnICkucmVtb3ZlQ2xhc3MoICdpcy1kaXNhYmxlZCcgKTtcblx0XHRcdFx0fTtcblxuXHRcdFx0XHR0cnkge1xuXHRcdFx0XHRcdGNvbnN0IGNob2ljZXNJbnN0YW5jZSA9IG5ldyBDaG9pY2VzKCBlbCwgYXJncyApO1xuXG5cdFx0XHRcdFx0Ly8gU2F2ZSBDaG9pY2VzLmpzIGluc3RhbmNlIGZvciBmdXR1cmUgYWNjZXNzLlxuXHRcdFx0XHRcdCRlbC5kYXRhKCAnY2hvaWNlc2pzJywgY2hvaWNlc0luc3RhbmNlICk7XG5cdFx0XHRcdH0gY2F0Y2ggKCBlICkge30gLy8gZXNsaW50LWRpc2FibGUtbGluZSBuby1lbXB0eVxuXHRcdFx0fSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBJbml0aWFsaXplIFJpY2hUZXh0IGZpZWxkLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge251bWJlcn0gZm9ybUlkIEZvcm0gSUQuXG5cdFx0ICovXG5cdFx0aW5pdFJpY2hUZXh0RmllbGQoIGZvcm1JZCApIHtcblx0XHRcdC8vIFNldCBkZWZhdWx0IHRhYiB0byBgVmlzdWFsYC5cblx0XHRcdCQoIGAjd3Bmb3Jtcy0keyBmb3JtSWQgfSAud3AtZWRpdG9yLXdyYXBgICkucmVtb3ZlQ2xhc3MoICdodG1sLWFjdGl2ZScgKS5hZGRDbGFzcyggJ3RtY2UtYWN0aXZlJyApO1xuXHRcdH0sXG5cdH07XG5cblx0Ly8gUHJvdmlkZSBhY2Nlc3MgdG8gcHVibGljIGZ1bmN0aW9ucy9wcm9wZXJ0aWVzLlxuXHRyZXR1cm4gYXBwO1xufSggZG9jdW1lbnQsIHdpbmRvdywgalF1ZXJ5ICkgKTtcblxuLy8gSW5pdGlhbGl6ZS5cbldQRm9ybXMuRm9ybVNlbGVjdG9yLmluaXQoKTtcbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7OytDQUNBLHFKQUFBQSxtQkFBQSxZQUFBQSxvQkFBQSxXQUFBQyxDQUFBLFNBQUFDLENBQUEsRUFBQUQsQ0FBQSxPQUFBRSxDQUFBLEdBQUFDLE1BQUEsQ0FBQUMsU0FBQSxFQUFBQyxDQUFBLEdBQUFILENBQUEsQ0FBQUksY0FBQSxFQUFBQyxDQUFBLEdBQUFKLE1BQUEsQ0FBQUssY0FBQSxjQUFBUCxDQUFBLEVBQUFELENBQUEsRUFBQUUsQ0FBQSxJQUFBRCxDQUFBLENBQUFELENBQUEsSUFBQUUsQ0FBQSxDQUFBTyxLQUFBLEtBQUFDLENBQUEsd0JBQUFDLE1BQUEsR0FBQUEsTUFBQSxPQUFBQyxDQUFBLEdBQUFGLENBQUEsQ0FBQUcsUUFBQSxrQkFBQUMsQ0FBQSxHQUFBSixDQUFBLENBQUFLLGFBQUEsdUJBQUFDLENBQUEsR0FBQU4sQ0FBQSxDQUFBTyxXQUFBLDhCQUFBQyxPQUFBakIsQ0FBQSxFQUFBRCxDQUFBLEVBQUFFLENBQUEs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
},{}]},{},[1])