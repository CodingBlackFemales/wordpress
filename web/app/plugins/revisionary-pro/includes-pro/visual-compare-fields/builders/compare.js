(function (window, wp) {
    'use strict';
    const { createElement: el, useEffect, useRef, useState } = wp.element;
    const { __ } = wp.i18n;
    const prefix = 'revisionary-builder-compare';
    const SELECT = 'visual-post-compare:builder-select';
    let selectedDetails = null;
    const sharedContentAttributes = new Set(['title', 'text', 'description_text', 'title_text', 'tab_title', 'tab_content']);
    const elementContentAttributes = {
        'text-editor': new Set(['editor']),
        alert: new Set(['alert_title', 'alert_description']),
        container: new Set(['content']),
        counter: new Set(['starting_number', 'ending_number']),
        html: new Set(['html']),
        image: new Set(['caption']),
        progress: new Set(['inner_text']),
        testimonial: new Set(['testimonial_content', 'testimonial_name', 'testimonial_job']),
    };
    const css = `
html,body{margin:0;min-height:100%;}body{padding:18px 28px 18px 18px;box-sizing:border-box;}
*,*:before,*:after{box-sizing:border-box;}[data-rvy-status]{position:relative;cursor:pointer;}
[data-rvy-status="added"]{outline:3px solid #008a20;outline-offset:-3px;}
[data-rvy-status="removed"]{outline:3px solid #cc1818;outline-offset:-3px;opacity:.4;}
[data-rvy-status="modified"]{outline:3px solid #9a7000;outline-offset:-3px;}
ins.rvy-text{background:#c5efcb;color:inherit;text-decoration:none;cursor:pointer;}del.rvy-text{background:#f8d5d5;color:inherit;cursor:pointer;}
.rvy-image{position:relative;display:inline-block;max-width:100%;}.rvy-image img{max-width:100%;}
.rvy-image .rvy-old-image{display:none;opacity:.4;}.rvy-image.rvy-show-old .rvy-old-image{display:block;}
.rvy-image.rvy-show-old .rvy-new-image{display:none;}.rvy-image:after{content:'↶';position:absolute;right:4px;bottom:4px;background:#fff;border-radius:50%;color:#9a7000;padding:2px 6px;}
#rvy-builder-tooltip{position:fixed;z-index:2147483647;background:#000c0c;color:#fff;padding:16px;min-width:200px;max-width:min(340px,calc(100vw - 24px));border:1px solid #d5d5d5;border-radius:10px;box-shadow:0 2px 5px rgba(0,0,0,.1),0 0 56px rgba(0,0,0,.08);font:normal 13px/1.5 sans-serif;text-align:center;white-space:pre-line;}
#rvy-builder-tooltip:after{content:"";position:absolute;top:100%;left:var(--rvy-tooltip-arrow,50%);width:12px;height:12px;background:#000c0c;transform:translate(-50%,-50%) rotate(45deg);border-right:1px solid #d5d5d5;border-bottom:1px solid #d5d5d5;}
#rvy-builder-tooltip[data-placement="bottom"]:after{top:0;transform:translate(-50%,-50%) rotate(225deg);}
#rvy-builder-markers{position:fixed;top:0;bottom:0;right:0;width:15px;z-index:2147483646;background:#f0f0f0;}
#rvy-builder-markers button{position:absolute;left:2px;width:11px;min-height:5px;border:0;padding:0;cursor:pointer;}
.rvy-no-changes{background:beige;padding:6px 10px;font:14px sans-serif;}
.elementor-tab-content,.fl-accordion-content,.et_pb_toggle_content{display:block!important;}
`;

    function canonical(value) {
        if (Array.isArray(value)) return '[' + value.map(canonical).join(',') + ']';
        if (value && typeof value === 'object') return '{' + Object.keys(value).sort().map((key) => JSON.stringify(key) + ':' + canonical(value[key])).join(',') + '}';
        return JSON.stringify(value);
    }

    function attributes(current, previous, elementType = '') {
        const result = [];
        const isContentAttribute = (key) => sharedContentAttributes.has(key) || (elementContentAttributes[elementType] || new Set()).has(key);
        const containsContentAttribute = (value) => {
            if (!value || typeof value !== 'object') return false;
            return Object.entries(value).some(([key, nested]) => isContentAttribute(key) || containsContentAttribute(nested));
        };
        const walk = (a, b, path) => {
            if (canonical(a) === canonical(b)) return;
            const aObject = a && typeof a === 'object';
            const bObject = b && typeof b === 'object';
            if ((aObject || bObject) && (!path || Array.isArray(a) || Array.isArray(b) || containsContentAttribute(a) || containsContentAttribute(b))) {
                const aKeys = aObject ? Object.keys(a) : [];
                const bKeys = bObject ? Object.keys(b) : [];
                [...new Set([...aKeys, ...bKeys])].forEach((key) => walk(a?.[key], b?.[key], path ? path + '.' + key : key));
            } else {
                const show = (value) => value === undefined || value === null ? '' : (typeof value === 'object' ? value : String(value));
                if (!window.RevisionaryCompareAttributes || window.RevisionaryCompareAttributes.visible(a, b)) result.push({ label: path, current: show(a), previous: show(b) });
            }
        };
        walk(current || {}, previous || {}, '');
        return result;
    }

    function sanitize(doc, html) {
        const holder = doc.createElement('div');
        holder.innerHTML = html || '';
        holder.querySelectorAll('script,base,meta,link,object,embed').forEach((node) => node.remove());
        holder.querySelectorAll('*').forEach((node) => {
            Array.from(node.attributes).forEach((attr) => {
                if (/^on/i.test(attr.name) || attr.name === 'srcdoc' || /^(?:javascript|vbscript):/i.test(attr.value.trim())) node.removeAttribute(attr.name);
            });
        });
        return holder;
    }

    function collect(root, source) {
        root.setAttribute('data-rvy-key', '__root__');
        const nodes = new Map([['__root__', root]]);
        if (source.selector) root.querySelectorAll(source.selector).forEach((node) => {
            const id = node.getAttribute(source.idAttribute);
            if (id && source.nodes[id] && !nodes.has(id)) {
                node.setAttribute('data-rvy-key', id);
                nodes.set(id, node);
            }
        });
        return nodes;
    }

    function ownNodes(root, selector) {
        return Array.from(root.querySelectorAll(selector)).filter((node) => node.closest('[data-rvy-key]') === root);
    }

    function textNodes(root) {
        const result = [];
        const walker = root.ownerDocument.createTreeWalker(root, 4);
        let node;
        while ((node = walker.nextNode())) {
            const parent = node.parentElement;
            if (parent.closest('[data-rvy-key]') === root && !parent.closest('style,script,textarea,svg,[hidden]') && node.textContent.trim()) result.push(node);
        }
        return result;
    }

    function ownText(root) { return textNodes(root).map((node) => node.textContent.replace(/\s+/g, ' ').trim()).join(' '); }

    // Bounded token LCS: large or unrelated text uses an honest full replacement.

    function groupTextChanges(parts, unchanged) {
        const result = [];
        let removed = '', added = '';
        const flush = () => {
            if (removed) result.push({ type: 'removed', value: removed });
            if (added) result.push({ type: 'added', value: added });
            removed = ''; added = '';
        };
        parts.forEach((part, index) => {
            if (part.type !== unchanged) {
                if (part.type === 'removed') removed += part.value;
                else added += part.value;
            } else if (!part.value.trim() && (removed || added) && parts[index + 1] && parts[index + 1].type !== unchanged) {
                removed += part.value; added += part.value;
            } else { flush(); result.push(part); }
        });
        flush();
        return result;
    }

    function tokensDiff(a, b) {
        const before = a.match(/\s+|[^\s]+/g) || [];
        const after = b.match(/\s+|[^\s]+/g) || [];
        if (a.replace(/\s+/g, ' ').trim() === b.replace(/\s+/g, ' ').trim()) return [{ type: 'same', value: b }];
        if (before.length * after.length > 250000) return [{ type: 'removed', value: a }, { type: 'added', value: b }];
        const rows = Array.from({ length: before.length + 1 }, () => new Uint32Array(after.length + 1));
        for (let i = before.length - 1; i >= 0; i--) for (let j = after.length - 1; j >= 0; j--) {
            rows[i][j] = before[i] === after[j] ? rows[i + 1][j + 1] + 1 : Math.max(rows[i + 1][j], rows[i][j + 1]);
        }
        let i = 0, j = 0;
        const result = [];
        const push = (type, value) => {
            const last = result[result.length - 1];
            if (last && last.type === type) last.value += value;
            else result.push({ type, value });
        };
        while (i < before.length || j < after.length) {
            if (i < before.length && j < after.length && before[i] === after[j]) push('same', after[j++]), i++;
            else if (i < before.length && (j === after.length || rows[i + 1][j] >= rows[i][j + 1])) push('removed', before[i++]);
            else push('added', after[j++]);
        }
        return groupTextChanges(result, 'same');
    }

    function decorateText(current, previous) {
        const currentTexts = textNodes(current);
        const previousTexts = textNodes(previous);
        // Preserve native inline markup. Align equal text first, then pair only
        // remaining adjacent text leaves; unmatched leaves are explicit changes.
        const used = new Set();
        const matched = currentTexts.map((node) => {
            const index = previousTexts.findIndex((old, oldIndex) => !used.has(oldIndex) && old.textContent === node.textContent);
            if (index >= 0) used.add(index);
            return index;
        });
        currentTexts.forEach((node, index) => {
            let match = matched[index];
            if (match < 0) match = previousTexts.findIndex((old, oldIndex) => !used.has(oldIndex));
            const old = match >= 0 ? previousTexts[match].textContent : '';
            if (match >= 0) used.add(match);
            const parts = tokensDiff(old, node.textContent);
            if (parts.every((part) => part.type === 'same')) return;
            const fragment = node.ownerDocument.createDocumentFragment();
            parts.forEach((part) => {
                if (part.type === 'same' || !part.value.trim()) fragment.appendChild(node.ownerDocument.createTextNode(part.value));
                else {
                    const mark = node.ownerDocument.createElement(part.type === 'added' ? 'ins' : 'del');
                    mark.className = 'rvy-text';
                    mark.textContent = part.value;
                    fragment.appendChild(mark);
                }
            });
            node.replaceWith(fragment);
        });
        previousTexts.forEach((node, index) => {
            if (used.has(index)) return;
            const mark = current.ownerDocument.createElement('del');
            mark.className = 'rvy-text';
            mark.textContent = node.textContent;
            const following = previousTexts.slice(index + 1).findIndex((old) => currentTexts.some((text) => text.textContent === old.textContent));
            const anchor = following >= 0 ? currentTexts.find((text) => text.textContent === previousTexts[index + 1 + following].textContent && current.contains(text)) : null;
            if (anchor) anchor.parentNode.insertBefore(mark, anchor);
            else current.appendChild(mark);
        });
    }

    function decorateImages(current, previous) {
        const images = ownNodes(current, 'img');
        const oldImages = ownNodes(previous, 'img');
        const used = new Set();
        const matched = images.map((image) => {
            const index = oldImages.findIndex((old, oldIndex) => !used.has(oldIndex) && old.getAttribute('src') === image.getAttribute('src'));
            if (index >= 0) used.add(index);
            return index;
        });
        images.forEach((image, index) => {
            let match = matched[index];
            if (match < 0) match = oldImages.findIndex((old, oldIndex) => !used.has(oldIndex));
            if (match < 0) { image.dataset.rvyImageChange = 'added'; image.style.outline = '3px solid #008a20'; image.title = __('Add image', 'revisionary-pro'); return; }
            used.add(match);
            const old = oldImages[match];
            if (['src', 'srcset', 'alt', 'title', 'width', 'height'].every((key) => old.getAttribute(key) === image.getAttribute(key))) return;
            const wrapper = image.ownerDocument.createElement('span');
            wrapper.className = 'rvy-image'; wrapper.dataset.rvyImageChange = 'modified';
            image.replaceWith(wrapper);
            image.classList.add('rvy-new-image');
            wrapper.appendChild(image);
            const backup = image.ownerDocument.importNode(old, true);
            backup.classList.add('rvy-old-image');
            wrapper.appendChild(backup);
            let locked = false;
            wrapper.addEventListener('mouseenter', () => wrapper.classList.add('rvy-show-old'));
            wrapper.addEventListener('mouseleave', () => { if (!locked) wrapper.classList.remove('rvy-show-old'); });
            wrapper.addEventListener('click', (event) => { event.preventDefault(); locked = !locked; wrapper.classList.toggle('rvy-show-old', locked); });
        });
        oldImages.forEach((old, index) => {
            if (used.has(index)) return;
            const removed = current.ownerDocument.importNode(old, true);
            removed.dataset.rvyImageChange = 'removed'; removed.style.opacity = '.4';
            removed.style.outline = '3px solid #cc1818';
            removed.title = __('Remove image', 'revisionary-pro');
            const following = oldImages.slice(index + 1).find((image) => images.some((newImage) => image.getAttribute('src') === newImage.getAttribute('src')));
            const anchor = following && images.find((image) => image.getAttribute('src') === following.getAttribute('src'));
            if (anchor && current.contains(anchor)) anchor.parentNode.insertBefore(removed, anchor);
            else current.appendChild(removed);
        });
    }

    function matchNodes(current, previous, now, old, positional) {
        const matches = new Map([['__root__', '__root__']]);
        const used = new Set(['__root__']);
        const definition = (data, id) => data.nodes[id] || { label: 'Content', attributes: {} };
        if (positional) {
            // Divi positions shift after insertion. Preserve identical modules
            // first, including duplicates, before pairing edited modules.
            current.forEach((node, id) => {
                if (id === '__root__') return;
                const item = definition(now, id);
                const fingerprint = canonical(item.attributes) + '|' + ownText(node);
                const match = Array.from(previous).find(([oldId, oldNode]) => !used.has(oldId)
                    && definition(old, oldId).label === item.label
                    && canonical(definition(old, oldId).attributes) + '|' + ownText(oldNode) === fingerprint);
                if (match) { matches.set(id, match[0]); used.add(match[0]); }
            });
        }
        current.forEach((node, id) => {
            if (matches.has(id)) return;
            const item = definition(now, id);
            if (previous.has(id) && !used.has(id) && definition(old, id).label === item.label) {
                matches.set(id, id); used.add(id); return;
            }
            if (!positional) return;
            const candidates = Array.from(previous).filter(([oldId]) => !used.has(oldId) && definition(old, oldId).label === item.label);
            // Unique remaining same-type pairs are safe enough to show as edited;
            // ambiguous unmatched modules remain explicit additions/removals.
            if (candidates.length === 1) { matches.set(id, candidates[0][0]); used.add(candidates[0][0]); }
        });
        return { matches, used };
    }

    function compare(doc, now, old, showChanges) {
        const root = sanitize(doc, now.html);
        root.id = 'rvy-builder-layout';
        const before = sanitize(doc, old ? old.html : '');
        const current = collect(root, now);
        const previous = collect(before, old || { nodes: {} });
        const changes = [];
        if (!showChanges || !old) return { root, changes, current };
        const { matches, used } = matchNodes(current, previous, now, old, now.identity === 'positional');
        const info = (data, id) => data.nodes[id] || { label: __('Content', 'revisionary-pro'), type: '', attributes: {} };
        const elementType = (details) => {
            const explicit = String(details.type || '').toLowerCase();
            if (explicit) return explicit;
            const label = String(details.label || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            return elementContentAttributes[label] ? label : '';
        };
        const attributeRows = (currentValues, previousValues, type = '') => attributes(currentValues, previousValues, type).filter((row) => !(now.ignoredAttributes || old.ignoredAttributes || []).includes(row.label));
        const normalizeVisibleText = (value) => {
            if (value === undefined || value === null || typeof value === 'object') return '';
            const holder = doc.createElement('div');
            holder.innerHTML = String(value);
            const walker = doc.createTreeWalker(holder, 4);
            const parts = [];
            let textNode;
            while ((textNode = walker.nextNode())) {
                const parent = textNode.parentElement;
                if (!parent.closest('script,style,textarea,svg,[hidden]') && textNode.textContent.trim()) {
                    parts.push(textNode.textContent.replace(/\s+/g, ' ').trim());
                }
            }
            return parts.join(' ');
        };
        const matchesCanvasText = (value, element) => {
            const text = normalizeVisibleText(value);
            if (!text) return false;
            const visible = textNodes(element).map((textNode) => textNode.textContent.replace(/\s+/g, ' ').trim());
            return text === ownText(element) || visible.includes(text);
        };
        const suppressDuplicateContentRows = (rows, details, currentNode, previousNode) => {
            let duplicateContent = false;
            const filtered = rows.filter((row) => {
                // Elementor stores a Container's child elements in its
                // `content` setting. Those elements are compared separately
                // by their own data IDs, so reporting the serialized tree on
                // the parent creates a duplicate Container-level change.
                if (details.type === 'container' && row.label.split('.')[0] === 'content') {
                    duplicateContent = true;
                    return false;
                }
                const key = row.label.split('.').pop();
                const contentField = sharedContentAttributes.has(key) || (elementContentAttributes[details.type] || new Set()).has(key);
                if (!contentField) return true;
                const currentText = normalizeVisibleText(row.current);
                const previousText = normalizeVisibleText(row.previous);
                const duplicatesCanvas = (!currentText || matchesCanvasText(row.current, currentNode))
                    && (!previousText || matchesCanvasText(row.previous, previousNode))
                    && (currentText || previousText);
                if (duplicatesCanvas) duplicateContent = true;
                return !duplicatesCanvas;
            });
            return { rows: filtered, duplicateContent };
        };
        const parentId = (node) => node.parentElement && node.parentElement.closest('[data-rvy-key]')?.getAttribute('data-rvy-key');
        const previousToCurrent = new Map(Array.from(matches, ([a, b]) => [b, a]));
        const siblingOrder = (nodes, map) => {
            const groups = new Map();
            nodes.forEach((node, id) => {
                const translated = map.get(id);
                if (!translated || id === '__root__') return;
                const parent = parentId(node);
                if (!groups.has(parent)) groups.set(parent, []);
                groups.get(parent).push(translated);
            });
            return groups;
        };
        const currentOrder = siblingOrder(current, new Map(Array.from(matches.keys(), (id) => [id, id])));
        const previousOrder = siblingOrder(previous, previousToCurrent);
        const mark = (node, status, details, rows) => {
            node.dataset.rvyStatus = status;
            changes.push({ node, status, label: details.label, attributes: rows || [] });
        };
        current.forEach((node, id) => {
            if ((now.ignoredNodes || []).includes(id)) return;
            const oldId = matches.get(id);
            if (oldId === undefined) {
                if (!node.parentElement?.closest('[data-rvy-status="added"]')) {
                    const details = info(now, id);
                    const typedDetails = { ...details, type: elementType(details) };
                    const rows = attributeRows(details.attributes, {}, typedDetails.type);
                    mark(node, 'added', details, suppressDuplicateContentRows(rows, typedDetails, node, doc.createElement('div')).rows);
                }
                return;
            }
            const prior = previous.get(oldId);
            const details = info(now, id);
            const priorDetails = info(old, oldId);
            const typedDetails = { ...details, type: elementType(details) || elementType(priorDetails) };
            let rows = attributeRows(details.attributes, priorDetails.attributes, typedDetails.type);
            if (id === '__root__') {
                // Conditional/hidden modules have source settings even when
                // their engine emits no selectable wrapper on either side.
                [...new Set([...Object.keys(now.nodes), ...Object.keys(old.nodes)])].forEach((key) => {
                    if (key === '__root__' || current.has(key) || previous.has(key)) return;
                    const details = info(now.nodes[key] ? now : old, key);
                    attributeRows(now.nodes[key]?.attributes, old.nodes[key]?.attributes, details.type).forEach((row) => rows.push({ ...row, label: details.label + ' (' + key + ').' + row.label }));
                });
            }
            const oldParent = parentId(prior);
            const newParent = parentId(node);
            if (oldParent && newParent && matches.get(newParent) !== oldParent) rows.push({ label: __('Parent module', 'revisionary-pro'), previous: oldParent, current: newParent });
            else if (oldParent && newParent) {
                const oldIndex = (previousOrder.get(oldParent) || []).indexOf(id);
                const newIndex = (currentOrder.get(newParent) || []).indexOf(id);
                if (oldIndex !== newIndex) rows.push({ label: __('Position', 'revisionary-pro'), previous: String(oldIndex + 1), current: String(newIndex + 1) });
            }
            const changedText = ownText(node) !== ownText(prior);
            const suppressedRows = suppressDuplicateContentRows(rows, typedDetails, node, prior);
            rows = suppressedRows.rows;
            const duplicateContent = suppressedRows.duplicateContent;
            const isContainer = typedDetails.type === 'container';
            // Container text and images are rendered by its child content.
            // Keep the detailed inline/image markers, but don't outline the
            // aggregate parent for changes already represented below it.
            if (changedText && !isContainer && !(now.ignoredAttributes || []).includes('Content') && !duplicateContent) {
                rows.push({ label: __('Content', 'revisionary-pro'), previous: ownText(prior), current: ownText(node), canvasContent: true });
            }
            const imageValues = (element) => ownNodes(element, 'img').map((image) => ['src', 'srcset', 'alt', 'title', 'width', 'height'].map((key) => image.getAttribute(key)));
            const changedImages = canonical(imageValues(node)) !== canonical(imageValues(prior));
            if (!rows.length && (isContainer || (!changedText && !changedImages))) {
                if (isContainer) {
                    if (changedText) decorateText(node, prior);
                    if (changedImages) decorateImages(node, prior);
                    // A Container is an aggregate wrapper, so anchor the
                    // tooltip and rail marker to the actual changed text or
                    // image fragment instead of the outer Container.
                    const inlineChanges = Array.from(node.querySelectorAll('ins.rvy-text,del.rvy-text,[data-rvy-image-change]'))
                        .filter((changedNode) => changedNode.closest('[data-rvy-key]') === node);
                    inlineChanges.forEach((changedNode) => {
                        let status = 'modified';
                        if (changedNode.matches('ins.rvy-text,[data-rvy-image-change="added"]')) {
                            status = 'added';
                        } else if (changedNode.matches('del.rvy-text,[data-rvy-image-change="removed"]')) {
                            status = 'removed';
                        }
                        changes.push({ node: changedNode, status, label: info(now, id).label, attributes: [] });
                    });
                }
                return;
            }
            mark(node, 'modified', info(now, id), rows);
            decorateText(node, prior);
            decorateImages(node, prior);

        });
        previous.forEach((node, id) => {
            if (used.has(id)) return;
            const oldParent = parentId(node);
            // One removed subtree, rather than duplicate copies of its children.
            if (oldParent && !used.has(oldParent)) return;
            const newParentId = Array.from(matches).find((entry) => entry[1] === oldParent)?.[0];
            let container = current.get(newParentId) || root;
            const oldContainer = previous.get(oldParent);
            const path = [];
            let cursor = node.parentElement;
            while (cursor && oldContainer && cursor !== oldContainer) {
                path.unshift(Array.from(cursor.parentElement.children).indexOf(cursor));
                cursor = cursor.parentElement;
            }
            if (cursor === oldContainer) {
                const nested = path.reduce((parent, index) => parent && parent.children[index], container);
                if (nested) container = nested;
            }
            const removed = doc.importNode(node, true);
            let following = node.nextElementSibling, anchor = null;
            while (following && !anchor) {
                const key = following.getAttribute('data-rvy-key');
                const match = Array.from(matches).find((entry) => entry[1] === key);
                if (match) anchor = current.get(match[0]);
                following = following.nextElementSibling;
            }
            if (anchor && anchor.parentElement === container) container.insertBefore(removed, anchor);
            else container.appendChild(removed);
            const details = info(old, id);
            const typedDetails = { ...details, type: elementType(details) };
            const rows = attributeRows({}, details.attributes, typedDetails.type);
            mark(removed, 'removed', details, suppressDuplicateContentRows(rows, typedDetails, doc.createElement('div'), removed).rows);
        });
        return { root, changes, current };
    }

    function changeCaption(change, captions) {
        const lines = [captions[change.status] + ' ' + change.label];
        const count = (selector) => change.node.querySelectorAll(selector).length;
        if ('modified' !== change.status) {
            if (change.node.textContent.trim()) lines.push('• ' + (change.status === 'added' ? __('Add text', 'revisionary') : __('Remove text', 'revisionary')));
            const images = count('img') + (change.node.matches('img') ? 1 : 0);
            if (images) lines.push('• ' + (change.status === 'added' ? __('Add image', 'revisionary') : __('Remove image', 'revisionary')) + ': ' + images);
            return lines.join('\n');
        }
        [
            ['ins.rvy-text', __('Add text', 'revisionary')],
            ['del.rvy-text', __('Remove text', 'revisionary')],
            ['[data-rvy-image-change="added"]', __('Add image', 'revisionary')],
            ['[data-rvy-image-change="removed"]', __('Remove image', 'revisionary')],
            ['[data-rvy-image-change="modified"]', __('Modify image', 'revisionary')],
        ].forEach(([selector, label]) => {
            const total = count(selector) + (change.node.matches(selector) ? 1 : 0);
            if (total) lines.push('• ' + label + ': ' + total);
        });
        return lines.join('\n');
    }

    function viewportStyles(doc) {
        let viewport = doc.querySelector('meta[name="viewport"]');
        if (!viewport) { viewport = doc.createElement('meta'); viewport.name = 'viewport'; doc.head.appendChild(viewport); }
        viewport.content = 'width=device-width, initial-scale=1';
        const update = (target, get, set) => {
            if (!target) return;
            const before = get();
            const after = before.replace(/\bdevice-width\b/gi, 'width');
            if (after !== before) set(after);
        };
        doc.querySelectorAll('link[rel="stylesheet"][media],style[media]').forEach((node) => {
            update(node, () => node.media, (value) => { node.media = value; });
        });
        const visit = (sheet, seen) => {
            if (!sheet || seen.has(sheet)) return;
            seen.add(sheet);
            try {
                if (sheet.media && sheet.media.mediaText) update(sheet.media, () => sheet.media.mediaText, (value) => { sheet.media.mediaText = value; });
                Array.from(sheet.cssRules || []).forEach((rule) => {
                    if (rule.media && rule.media.mediaText) update(rule.media, () => rule.media.mediaText, (value) => { rule.media.mediaText = value; });
                    if (rule.styleSheet) visit(rule.styleSheet, seen);
                    else if (rule.cssRules) visit(rule, seen);
                });
            } catch (error) { /* Cross-origin stylesheets keep their native media rules. */ }
        };
        const seen = new Set();
        Array.from(doc.styleSheets).forEach((sheet) => visit(sheet, seen));
    }

    function NativePreview({ comparison, context }) {
        const frame = useRef(null);
        const [previewError, setPreviewError] = useState('');
        const builder = comparison.builderCompare;
        const now = builder.documents[String(context.displayedPost.id)];
        const old = builder.documents[String(context.comparisonBase.id)];
        const showChanges = !context.isCurrentReference && !context.lacksPreviousRevision;
        useEffect(() => {
            setPreviewError('');
            selectedDetails = null;
            window.dispatchEvent(new CustomEvent(SELECT, { detail: null }));
            const iframe = frame.current;
            if (!iframe || !now || now.error || (showChanges && (!old || old.error))) return;
            const canvas = iframe.closest('.visual-post-compare-revision__canvas');
            if (canvas) canvas.classList.add('visual-post-compare-revision__canvas--builder');
            let active = true;
            let dispose = () => {};
            let initializedDocument = null;
            const initialize = () => {
                if (!active) return;
                if (!iframe.contentDocument || !iframe.contentDocument.body) { setPreviewError(__('The frontend builder preview could not be accessed.', 'revisionary-pro')); return; }
            const doc = iframe.contentDocument;
            const shell = now.frontendUrl && doc.getElementById('rvy-builder-preview-root');
            if (now.frontendUrl && (!shell || shell.getAttribute('data-rvy-snapshot') !== String(context.displayedPost.id))) {
                setPreviewError(__('The frontend builder preview could not be loaded. Reload the comparison to retry.', 'revisionary-pro'));
                return;
            }
            // An already loaded iframe can also deliver a queued load event.
            // Never replace its widget DOM after scripts attach their handlers.
            if (initializedDocument === doc) return;
            dispose();
            if (!now.frontendUrl) { doc.open(); doc.write('<!doctype html><html><head></head><body></body></html>'); doc.close(); }
            initializedDocument = doc;
            const base = doc.createElement('base'); base.href = now.baseUrl; doc.head.appendChild(base);
            [...new Set([...(old?.styles || []), ...(now.styles || [])])].forEach((url) => {
                const link = doc.createElement('link'); link.rel = 'stylesheet'; link.href = url; doc.head.appendChild(link);
            });
            const style = doc.createElement('style'); style.textContent = (old?.inline_styles || '') + '\n' + (old?.css || '') + '\n' + (now.inline_styles || '') + '\n' + now.css + '\n' + css; doc.head.appendChild(style);
            if (!now.frontendUrl) doc.body.className = now.body_class || '';
            const pageScripts = now.javascript ? Array.from(doc.querySelectorAll('script[data-rvy-script]')).filter((script) => !script.rvyActivated) : [];
            if (now.javascript && now.scripts) {
                const scripts = doc.createElement('template'); scripts.innerHTML = now.scripts;
                pageScripts.push(...scripts.content.querySelectorAll('script'));
            }
            if (shell) {
                // Keep theme ancestors and their classes/IDs, while removing
                // surrounding headers, footers and unrelated page content.
                let branch = shell;
                while (branch.parentElement && branch !== doc.body) {
                    const parent = branch.parentElement;
                    Array.from(parent.children).forEach((sibling) => { if (sibling !== branch) sibling.remove(); });
                    branch = parent;
                }
            }
            if (shell && shell.rvyNativeHTML === undefined) shell.rvyNativeHTML = shell.innerHTML;
            doc.querySelectorAll('#rvy-builder-markers,#rvy-builder-tooltip,p.rvy-no-changes').forEach((node) => node.remove());
            const renderedNow = shell ? { ...now, html: shell.rvyNativeHTML } : now;
            const result = compare(doc, renderedNow, old, showChanges);
            if (shell) shell.replaceChildren(result.root);
            else doc.body.appendChild(result.root);
            if (showChanges && !result.changes.length) {
                const notice = doc.createElement('p'); notice.className = 'rvy-no-changes'; notice.textContent = __('No changes detected.', 'revisionary'); doc.body.prepend(notice);
            }
            const tooltip = doc.createElement('div'); tooltip.id = 'rvy-builder-tooltip'; tooltip.hidden = true; doc.body.appendChild(tooltip);
            let locked = false;
            let activeTooltipChange = null;
            let activeTooltipLocked = false;
            const captions = { added: __('Add', 'revisionary'), removed: __('Remove', 'revisionary'), modified: __('Modify', 'revisionary') };
            const caption = (change) => captions[change.status] + ' ' + change.label;
            const show = (change, lock) => {
                if (!change) return;
                activeTooltipChange = change;
                activeTooltipLocked = lock;
                locked = lock;
                tooltip.textContent = changeCaption(change, captions);
                tooltip.hidden = false;
                const rect = change.node.getBoundingClientRect();
                const below = rect.top < tooltip.offsetHeight + 12;
                tooltip.dataset.placement = below ? 'bottom' : 'top';
                tooltip.style.top = Math.max(0, Math.min(doc.documentElement.clientHeight - tooltip.offsetHeight - 8, below ? rect.bottom + 12 : rect.top - tooltip.offsetHeight - 12)) + 'px';
                const left = Math.max(12, Math.min(doc.documentElement.clientWidth - tooltip.offsetWidth - 12, rect.left + rect.width / 2 - tooltip.offsetWidth / 2));
                tooltip.style.left = left + 'px';
                tooltip.style.setProperty('--rvy-tooltip-arrow', Math.max(16, Math.min(tooltip.offsetWidth - 16, rect.left + rect.width / 2 - left)) + 'px');
            };
            const hideTooltip = () => {
                locked = false;
                activeTooltipChange = null;
                tooltip.hidden = true;
            };
            const repositionTooltip = () => {
                if (!tooltip.hidden && activeTooltipChange) show(activeTooltipChange, activeTooltipLocked);
            };
            const select = (change) => {
                selectedDetails = {
                    label: change.label,
                    status: change.status,
                    attributes: change.attributes,
                    caption: 'modified' === change.status ? changeCaption(change, captions) : '',
                };
                window.dispatchEvent(new CustomEvent(SELECT, { detail: selectedDetails }));
                const tab = window.document.querySelector('.visual-post-compare-revision__sidebar > .visual-post-compare-revision__sidebar-tabs button:last-child');
                if (tab) tab.click();
            };
            const markers = doc.createElement('nav'); markers.id = 'rvy-builder-markers'; markers.setAttribute('aria-label', __('Content changes', 'revisionary-pro')); doc.body.appendChild(markers);
            result.changes.forEach((change) => {
                change.node.addEventListener('mouseenter', () => { if (!locked) show(change, false); });
                change.node.addEventListener('mouseleave', () => { if (!locked) hideTooltip(); });
                change.node.addEventListener('click', (event) => { if (!now.javascript) { event.stopPropagation(); event.preventDefault(); } show(change, true); select(change); }, true);
                const marker = doc.createElement('button'); marker.type = 'button'; marker.setAttribute('aria-label', caption(change));
                marker.style.background = { added: '#008a20', removed: '#cc1818', modified: 'repeating-linear-gradient(-45deg,#9a7000 0 3px,#eadbb7 3px 6px)' }[change.status];
                marker.addEventListener('mouseenter', () => { if (!locked) show(change, false); });
                marker.addEventListener('mouseleave', () => { if (!locked) hideTooltip(); });
                marker.addEventListener('click', () => { change.node.scrollIntoView({ block: 'center' }); show(change, true); select(change); });
                markers.appendChild(marker); change.marker = marker;
            });
            const updateMarkers = () => {
                viewportStyles(doc);
                const height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight, 1);
                result.changes.forEach((change) => {
                    const rect = change.node.getBoundingClientRect();
                    change.marker.style.top = Math.min(99, 100 * (rect.top + doc.documentElement.scrollTop) / height) + '%';
                    change.marker.style.height = Math.max(.1, Math.min(40, 100 * rect.height / height)) + '%';
                });
            };
            // Layout can change after styles, web fonts and images have loaded.
            const observer = new ResizeObserver(updateMarkers); observer.observe(doc.body);
            const styleObserver = new doc.defaultView.MutationObserver(updateMarkers);
            styleObserver.observe(doc.head, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['media'] });
            window.addEventListener('resize', updateMarkers);
            doc.addEventListener('scroll', repositionTooltip, true);
            doc.defaultView.addEventListener('scroll', repositionTooltip, true);
            doc.addEventListener('load', updateMarkers, true);
            updateMarkers();
            doc.addEventListener('click', (event) => {
                if (!event.target.closest('[data-rvy-status],#rvy-builder-markers')) hideTooltip();
                const node = event.target.closest('[data-rvy-key]');
                const key = node && node.getAttribute('data-rvy-key');
                if (node && !(now.ignoredNodes || []).includes(key) && !node.hasAttribute('data-rvy-status') && result.current.get(key) === node) {
                    const definition = now.nodes[key] || { label: __('Content', 'revisionary-pro'), attributes: {} };
                    select({ label: definition.label, status: 'unchanged', attributes: attributes(definition.attributes, {}) });
                }
            }, true);
            // Comparison is read-only: links, forms and builder widgets do not
            // navigate, submit, or run a builder editing session.
            doc.addEventListener('click', (event) => { if (event.target.closest('a')) event.preventDefault(); }, true);
            doc.addEventListener('submit', (event) => event.preventDefault(), true);
            // Run scripts in dependency order after selection capture handlers
            // and final comparison markup exist. Theme bubbling handlers cannot
            // prevent the attributes tab from selecting or refreshing.
            const runScripts = async () => {
                wp.hooks.doAction('revisionary.builderCompare.prepareRuntime', builder, doc, result.root);
                let heldReady = null;
                const holdReady = () => {
                    const jquery = doc.defaultView?.jQuery;
                    if (!heldReady && typeof jquery?.holdReady === 'function') { jquery.holdReady(true); heldReady = jquery; }
                };
                holdReady();
                try {
                    let moduleIndex = 0;
                    for (const source of pageScripts) {
                        if (!active) return;
                        source.rvyActivated = true;
                        const script = doc.createElement('script');
                        Array.from(source.attributes).forEach((attr) => {
                            if (!['type', 'data-rvy-script', 'data-rvy-script-type', 'async', 'defer'].includes(attr.name)) script.setAttribute(attr.name, attr.value);
                        });
                        const type = source.getAttribute('data-rvy-script-type') || (source.hasAttribute('data-rvy-script') ? '' : source.getAttribute('type'));
                        if (type) script.type = type;
                        script.textContent = source.textContent;
                        script.async = false;
                        if (script.src) await new Promise((resolve) => { script.onload = resolve; script.onerror = resolve; doc.body.appendChild(script); });
                        else if (type === 'module') await new Promise((resolve) => {
                            // Inline modules do not emit a load event. Signal
                            // evaluation completion from inside the module so
                            // later scripts and widget initialization can run.
                            const eventName = 'revisionary-builder-module-ready-' + Date.now() + '-' + (++moduleIndex);
                            let timer;
                            const finish = () => {
                                doc.removeEventListener(eventName, finish);
                                doc.defaultView.clearTimeout(timer);
                                resolve();
                            };
                            doc.addEventListener(eventName, finish, { once: true });
                            script.onerror = finish;
                            // A runtime exception before the signal must not
                            // leave the rest of the preview permanently inert.
                            timer = doc.defaultView.setTimeout(finish, 5000);
                            script.textContent += '\n;document.dispatchEvent(new Event(' + JSON.stringify(eventName) + '));';
                            doc.body.appendChild(script);
                        });
                        else doc.body.appendChild(script);
                        // Scripts mount into an already ready iframe; defer jQuery
                        // ready callbacks until all widget registrations are loaded.
                        holdReady();
                    }
                } finally { if (heldReady) heldReady.holdReady(false); }
                if (active) {
                    wp.hooks.doAction('revisionary.builderCompare.initializeRuntime', builder, doc, result.root);
                    updateMarkers();
                }
            };
            runScripts();
            dispose = () => { observer.disconnect(); styleObserver.disconnect(); window.removeEventListener('resize', updateMarkers); doc.removeEventListener('scroll', repositionTooltip, true); doc.defaultView.removeEventListener('scroll', repositionTooltip, true); doc.removeEventListener('load', updateMarkers, true); };
            };
            if (now.frontendUrl) {
                iframe.addEventListener('load', initialize);
                if (iframe.contentDocument?.getElementById('rvy-builder-preview-root')) initialize();
            } else initialize();
            return () => {
                active = false;
                iframe.removeEventListener('load', initialize);
                dispose();
                if (canvas) canvas.classList.remove('visual-post-compare-revision__canvas--builder');
                selectedDetails = null;
                window.dispatchEvent(new CustomEvent(SELECT, { detail: null }));
            };
        }, [now, old, showChanges, comparison.revision.id]);
        if (previewError || !now || now.error || (showChanges && (!old || old.error))) return el('div', { className: 'notice notice-warning inline' }, el('p', null, previewError || now?.error || old?.error || __('The saved builder preview is unavailable.', 'revisionary-pro')));
        return el('iframe', { ref: frame, src: now.frontendUrl || undefined, key: now.frontendUrl || context.displayedPost.id, title: builder.label + ' ' + __('visual comparison', 'revisionary-pro'), sandbox: now.javascript ? 'allow-same-origin allow-scripts' : 'allow-same-origin', style: { width: '100%', height: '100%', minHeight: '0', border: 0, display: 'block' } });
    }

    function BuilderDetails({ comparison }) {
        const [selected, setSelected] = useState(selectedDetails);
        useEffect(() => {
            const handler = (event) => setSelected(event.detail);
            window.addEventListener(SELECT, handler);
            setSelected(selectedDetails);
            return () => window.removeEventListener(SELECT, handler);
        }, [comparison.revision.id]);
        if (!selected) return el('p', null, __('Select a builder change to view its attributes.', 'revisionary-pro'));
        if (selected.status === 'unchanged') return el('div', { className: 'visual-post-compare-revision__block-details' }, el('strong', null, selected.label), el('h2', { className: 'visual-post-compare-revision__no-attribute-changes' }, __('No attribute changes', 'revisionary')));
        return el('div', { className: 'visual-post-compare-revision__block-details' },
            el('strong', null, selected.label),
            selected.status === 'modified' && selected.caption ? el('div', { className: 'visual-post-compare-revision__block-details-caption' }, selected.caption) : null,
            selected.attributes.length ? el('div', { className: 'visual-post-compare-revision__changed-attributes' },
                el('h2', null, selected.status === 'modified' ? __('Changed attributes', 'revisionary') : __('Attributes', 'revisionary')),
                ...selected.attributes.map((row, index) => window.RevisionaryCompareAttributes.renderRow(row.label, row.current, row.previous, selected.status === 'unchanged', index)).filter(Boolean)
            ) : null);
    }

    wp.hooks.addFilter('visual_post_compare.show_no_changes_notice', prefix, (fallback, comparison) => comparison.builderCompare ? false : fallback);
    wp.hooks.addFilter('visual_post_compare.render_content', prefix, (fallback, comparison, context) => comparison.builderCompare ? el(NativePreview, { comparison, context }) : fallback);
    wp.hooks.addFilter('visual_post_compare.render_content_details', prefix, (fallback, comparison) => comparison.builderCompare ? el(BuilderDetails, { comparison }) : fallback);
    wp.hooks.addFilter('visual_post_compare.render_markers', prefix, (fallback, comparison) => comparison.builderCompare ? null : fallback);
    wp.hooks.addFilter('visual_post_compare.has_content_changes', prefix, (fallback, comparison) => {
        if (!comparison.builderCompare) return fallback;
        const documents = comparison.builderCompare.documents;
        const now = documents[String(comparison.revision.id)];
        const old = documents[String((comparison.comparisonBase || comparison.current).id)];
        // A render failure is not evidence that no content changed.
        return !now || !old || Boolean(now.error || old.error) || now.signature !== old.signature;
    });
    window.RevisionaryBuilderCompare = { compare, tokensDiff, attributes, sanitize, changeCaption, viewportStyles };
})(window, window.wp);
