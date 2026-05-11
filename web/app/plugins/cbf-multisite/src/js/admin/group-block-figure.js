/* global window */
/* eslint-disable prettier/prettier */
(function (wp) {
  "use strict";

  console.log("group-block-figure.js loaded");

  if (!wp || !wp.hooks || !wp.blocks) {
    return;
  }

  console.log("wp.hooks", wp.hooks);

  // --- Part 1: extend tagName attribute enum for serialisation/validation ---
  // NOTE: the Group block's HTML element dropdown is hardcoded in its edit
  // component and does NOT read from tagName.enum, so this alone won't change
  // the dropdown UI — Part 2 handles that via editor.BlockEdit.
  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "cbf-multisite/group-block-figure",
    function (settings, name) {
      if (name !== "core/group") {
        return settings;
      }

      var attributes =
        settings && settings.attributes ? settings.attributes : {};
      var tagName = attributes.tagName;
      var existingEnum =
        tagName && Array.isArray(tagName.enum) ? tagName.enum : [];
      var fallbackEnum = [
        "div",
        "section",
        "main",
        "article",
        "aside",
        "header",
        "footer",
      ];
      var baseEnum = existingEnum.length ? existingEnum : fallbackEnum;

      console.log("tagName", tagName);
      console.log("existingEnum", existingEnum);
      console.log("baseEnum", baseEnum);
      console.log("settings", settings);

      if (baseEnum.indexOf("figure") !== -1) {
        return settings;
      }

      var extendedSettings = Object.assign({}, settings, {
        attributes: Object.assign({}, attributes, {
          tagName: Object.assign(
            { type: "string", default: "div", enum: fallbackEnum },
            tagName || {},
            { enum: baseEnum.concat("figure") }
            // eslint-disable-next-line
          ),
        }),
      });

      console.log("extendedTagName", extendedSettings.attributes.tagName);

      return extendedSettings;
    }
    // eslint-disable-next-line
  );

  // --- Part 2: inject <figure> into the editor UI via editor.BlockEdit HOC ---
  // The Group block's HTML element SelectControl is hardcoded — it doesn't read
  // from tagName.enum. We add our own SelectControl with the full list plus
  // <figure>. The original is hidden via CSS targeting the .cbf-html-element-control
  // class added here as an adjacent-sibling marker (see Admin/Assets.php).
  if (!wp.element || !wp.compose || !wp.blockEditor || !wp.components) {
    console.log("group-block-figure: missing deps for BlockEdit HOC", {
      element: !!wp.element,
      compose: !!wp.compose,
      blockEditor: !!wp.blockEditor,
      components: !!wp.components,
    });
    return;
  }

  var HTML_ELEMENT_DESCRIPTIONS = {
    div: "The <div> element should only be used if the block is a design element with no semantic meaning.",
    header:
      "The <header> element should represent introductory content, typically a group of introductory or navigational aids.",
    main: "The <main> element should be used for the primary content of your document only.",
    section:
      "The <section> element should represent a standalone portion of the document that can't be better represented by another element.",
    article:
      "The <article> element should represent a self-contained, syndicatable portion of the document.",
    aside:
      "The <aside> element should represent a portion of a document whose content is only indirectly related to the document's main content.",
    footer:
      "The <footer> element should represent a footer for its nearest sectioning element (eg <section>, <article>, <main>, etc).",
    figure:
      "The <figure> element should represent self-contained content, frequently with a caption, and is typically referenced as a single unit.",
  };

  var HTML_ELEMENT_OPTIONS = [
    { label: "Default (<div>)", value: "div" },
    { label: "<header>", value: "header" },
    { label: "<main>", value: "main" },
    { label: "<section>", value: "section" },
    { label: "<article>", value: "article" },
    { label: "<aside>", value: "aside" },
    { label: "<footer>", value: "footer" },
    { label: "<figure>", value: "figure" },
  ];

  var withGroupFigureControl = wp.compose.createHigherOrderComponent(function (
    BlockEdit
  ) {
    return function GroupBlockWithFigure(props) {
      if (props.name !== "core/group") {
        return wp.element.createElement(BlockEdit, props);
      }

      console.log("GroupBlockWithFigure props.attributes", props.attributes);

      var currentTag = props.attributes.tagName || "div";

      return wp.element.createElement(
        wp.element.Fragment,
        null,
        wp.element.createElement(BlockEdit, props),
        wp.element.createElement(
          wp.blockEditor.InspectorAdvancedControls,
          null,
          wp.element.createElement(wp.components.SelectControl, {
            // className propagates to the .components-base-control wrapper,
            // used as the CSS hook to hide the original Group block control.
            className: "cbf-html-element-control",
            __next40pxDefaultSize: true,
            label: "HTML element",
            value: currentTag,
            options: HTML_ELEMENT_OPTIONS,
            help: HTML_ELEMENT_DESCRIPTIONS[currentTag] || "",
            onChange: function (value) {
              console.log("GroupBlockWithFigure tagName changed to", value);
              props.setAttributes({ tagName: value });
            },
          })
        )
      );
    };
  }, "withGroupFigureControl");

  wp.hooks.addFilter(
    "editor.BlockEdit",
    "cbf-multisite/group-block-figure-edit",
    withGroupFigureControl
  );
})(window.wp);
