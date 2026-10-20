(function (window, wp) {
    'use strict';
    const documents = new WeakMap();
    wp.hooks.addAction('revisionary.builderCompare.prepareRuntime', 'revisionary-elementor-runtime', (builder, doc) => {
        if (builder.name !== 'elementor' || documents.has(doc)) return;
        const state = { started: false };
        documents.set(doc, state);
        doc.addEventListener('alpine:initialized', () => { state.started = true; }, { once: true });
    });
    wp.hooks.addAction('revisionary.builderCompare.initializeRuntime', 'revisionary-elementor-runtime', (builder, doc, root) => {
        if (builder.name !== 'elementor') return;
        const runtime = doc.defaultView.elementorV2;
        const state = documents.get(doc);
        if (!state || !runtime?.frontendHandlers || !runtime?.alpinejs?.Alpine) return;
        // Elementor's initial atomic scan can precede late widget registration.
        // Its render lifecycle cleans up existing handlers before binding again.
        root.querySelectorAll('[data-e-type][data-id]').forEach((element) => {
            doc.defaultView.dispatchEvent(new doc.defaultView.CustomEvent('elementor/element/render', {
                detail: { element, type: element.dataset.eType, id: element.dataset.id }
            }));
        });
        const alpine = runtime.alpinejs.Alpine;
        // Elementor 4.3.3 waits only for DOMContentLoaded to start Alpine. The
        // compare iframe has already fired it before executing deferred assets.
        // Initialize after all atomic handlers have registered their x-data.
        if (!state.started) {
            state.started = true;
            alpine.start();
        } else {
            alpine.initTree(root);
        }
    });
})(window, window.wp);
