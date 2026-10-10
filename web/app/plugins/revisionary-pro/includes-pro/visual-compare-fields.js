(function (window, document, wp) {
	'use strict';
	const { element, i18n } = wp;
	const { createElement: el, createInterpolateElement, useEffect, useRef, useState } = element;
	const { __, sprintf } = i18n;
	let showDiffTooltip = function () {};
	let hideDiffTooltip = function () {};

	function isPlainObject(value) {
		return value && typeof value === 'object' && !Array.isArray(value);
	}

	function fieldValuesEqual(a, b) {
		return JSON.stringify(a) === JSON.stringify(b);
	}

	function compactFieldValue(value, depth = 0) {
		if (depth >= 3 && value && typeof value === 'object') return '[Array]';
		if (value === null || typeof value === 'undefined' || value === '') return '—';
		if (typeof value === 'boolean') return value ? __('Yes', 'revisionary') : __('No', 'revisionary');
		if (Array.isArray(value)) return value.map((item) => compactFieldValue(item, depth + 1));
		if (isPlainObject(value)) {
			return Object.keys(value).reduce((result, key) => {
				result[key] = compactFieldValue(value[key], depth + 1);
				return result;
			}, {});
		}
		return String(value);
	}

	function ignoredFieldsConfig() {
		return window.revisionaryCompareFields || {};
	}

	function providerFields(providers) {
		return (providers || []).flatMap((provider) => (provider.groups || [])
			.flatMap((group) => group.fields || []));
	}

	function providerSelectCaption(provider) {
		const providerName = providerNameForCaption(provider);

		return sprintf(__('Select all %s fields', 'revisionary'), providerName);
	}

	function providerNameForCaption(provider) {
		return provider.id === 'acf'
			? __('ACF', 'revisionary')
			: (provider.id === 'wordpress' ? __('WordPress', 'revisionary') : provider.label);
	}

	function providersForKeys(providers, keys) {
		const selected = new Set(keys || []);
		return (providers || []).map((provider) => ({
			...provider,
			groups: (provider.groups || []).map((group) => ({
				...group,
				fields: (group.fields || []).filter((field) => selected.has(field.key)),
			})).filter((group) => group.fields.length),
		})).filter((provider) => provider.groups.length);
	}

	function fieldDefinitionsForKeys(providers, keys) {
		return providersForKeys(providers, keys).map((provider) => ({
			id: provider.id,
			label: provider.label,
			groups: (provider.groups || []).map((group) => ({
				label: group.label,
				fields: (group.fields || []).map((field) => ({ key: field.key, label: field.label })),
			})),
		}));
	}

	function updateIgnoredFields(operation, fields, providers, listType = 'ignored') {
		const config = ignoredFieldsConfig();
		const body = new window.FormData();
		body.append('action', 'revisionary_update_ignored_meta_fields');
		body.append('nonce', config.nonce || '');
		body.append('operation', operation);
		body.append('listType', listType);
		(fields || []).forEach((field) => body.append('fields[]', field));
		body.append('providers', JSON.stringify(fieldDefinitionsForKeys(providers, fields)));
		return window.fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		}).then((response) => response.json()).then((response) => {
			if (!response || !response.success) {
				throw new Error(response && response.data && response.data.message
					? response.data.message
					: __('Unable to update ignored fields.', 'revisionary'));
			}
			if (listType === 'suppressed') config.suppressedFields = response.data.ignoredFields || [];
			else config.ignoredFields = response.data.ignoredFields || [];
			window.dispatchEvent(new window.CustomEvent('revisionary-ignored-fields-updated', {
				detail: response.data,
			}));
			return response.data;
		});
	}

	function FieldImagePreview({ image, originalImage, label, children }) {
		const [hovered, setHovered] = useState(false);
		const [pinned, setPinned] = useState(false);
		const triggerRef = useRef(null);
		const visible = hovered || pinned;
		useEffect(() => {
			if (!pinned) return undefined;
			const outsideClick = (event) => {
				if (triggerRef.current && !triggerRef.current.contains(event.target)) setPinned(false);
			};
			document.addEventListener('mousedown', outsideClick);
			return () => document.removeEventListener('mousedown', outsideClick);
		}, [pinned]);
		return el('span', {
			ref: triggerRef,
			className: 'visual-post-compare-fields__image-trigger',
			tabIndex: 0,
			onMouseEnter: () => setHovered(true),
			onMouseLeave: () => setHovered(false),
			onFocus: () => setHovered(true),
			onBlur: () => setHovered(false),
			onClick: (event) => { event.stopPropagation(); setPinned((isPinned) => !isPinned); },
		},
			children,
			visible ? el('span', { className: 'visual-post-compare-fields__image-tooltip' },
				el('span', null, el('small', null, label), el('a', {
					href: originalImage || image,
					target: '_blank',
					rel: 'noopener noreferrer',
					onClick: (event) => event.stopPropagation(),
				}, el('img', { src: image, alt: '' })))
			) : null
		);
	}

	function FieldScalar({ removed, added, currentImage, revisionImage, currentImageOriginal, revisionImageOriginal, tooltipCaptions }) {
		const hasRemoved = typeof removed !== 'undefined';
		const hasAdded = typeof added !== 'undefined';
		const hasImageTooltip = Boolean(currentImage || revisionImage);
		const caption = hasRemoved && hasAdded
			? tooltipCaptions.modify
			: (hasAdded ? tooltipCaptions.add : tooltipCaptions.remove);
		const tooltipProps = hasImageTooltip || !caption ? {} : {
			'data-visual-post-compare-tooltip': caption,
			onMouseEnter: (event) => showDiffTooltip(event.currentTarget, caption),
			onMouseLeave: () => hideDiffTooltip(),
			onFocus: (event) => showDiffTooltip(event.currentTarget, caption),
			onBlur: () => hideDiffTooltip(),
			onClick: (event) => showDiffTooltip(event.currentTarget, caption),
			tabIndex: 0,
		};
		const removedValue = hasRemoved ? el('del', { className: 'revision-diff-removed' }, removed) : null;
		const addedValue = hasAdded ? el('ins', { className: 'revision-diff-added' }, added) : null;
		return el('div', { className: 'visual-post-compare-fields__scalar', ...tooltipProps },
			removedValue ? (currentImage ? el(FieldImagePreview, { image: currentImage, originalImage: currentImageOriginal, label: __('Revision version', 'revisionary') }, removedValue) : removedValue) : null,
			addedValue ? (revisionImage ? el(FieldImagePreview, { image: revisionImage, originalImage: revisionImageOriginal, label: __('Current version', 'revisionary') }, addedValue) : addedValue) : null
		);
	}

	function arrayItemMatchScore(current, revision) {
		if (fieldValuesEqual(compactFieldValue(current), compactFieldValue(revision))) return 10000;
		if (!isPlainObject(current) || !isPlainObject(revision)) return 0;

		const sharedKeys = Object.keys(current).filter((key) => Object.prototype.hasOwnProperty.call(revision, key));
		const identifierKeys = sharedKeys.filter((key) => /^(?:id|ID|key|uuid|option_id)$|(?:_|-)id$/i.test(key));
		if (identifierKeys.some((key) => {
			const left = current[key];
			const right = revision[key];
			return left !== null && typeof left !== 'undefined' && String(left) !== '' && String(left) === String(right);
		})) return 5000;

		let identical = 0;
		let different = 0;
		sharedKeys.forEach((key) => {
			const left = current[key];
			const right = revision[key];
			if ((left && typeof left === 'object') || (right && typeof right === 'object')) return;
			if (fieldValuesEqual(compactFieldValue(left), compactFieldValue(right))) identical += 1;
			else different += 1;
		});

		return identical >= 2 && identical > different ? 100 + identical * 10 - different : 0;
	}

	function matchArrayItems(currentItems, revisionItems) {
		const availableRevision = new Set(revisionItems.map((item, index) => index));
		const matches = [];
		const unmatchedCurrent = [];

		currentItems.forEach((current, currentIndex) => {
			let bestIndex = -1;
			let bestScore = 0;
			let bestScoreTied = false;
			availableRevision.forEach((revisionIndex) => {
				const score = arrayItemMatchScore(current, revisionItems[revisionIndex]);
				if (score > bestScore) {
					bestScore = score;
					bestIndex = revisionIndex;
					bestScoreTied = false;
				} else if (score > 0 && score === bestScore) {
					bestScoreTied = true;
				}
			});
			if (bestIndex >= 0 && bestScore > 0 && (!bestScoreTied || bestScore >= 5000)) {
				matches.push({ current, revision: revisionItems[bestIndex], currentIndex, revisionIndex: bestIndex });
				availableRevision.delete(bestIndex);
			} else {
				unmatchedCurrent.push({ item: current, index: currentIndex });
			}
		});

		return {
			matches,
			unmatchedCurrent,
			unmatchedRevision: Array.from(availableRevision).map((index) => ({ item: revisionItems[index], index })),
		};
	}

	function countFieldValueChanges(current, revision, depth = 0) {
		const left = compactFieldValue(current, depth);
		const right = compactFieldValue(revision, depth);
		if (fieldValuesEqual(left, right)) return 0;

		const currentValueMissing = current === null || typeof current === 'undefined' || current === '';
		const revisionValueMissing = revision === null || typeof revision === 'undefined' || revision === '';
		if ((Array.isArray(left) || Array.isArray(right)) && !isPlainObject(left) && !isPlainObject(right)) {
			const oldItems = currentValueMissing ? [] : (Array.isArray(current) ? current : [current]);
			const newItems = revisionValueMissing ? [] : (Array.isArray(revision) ? revision : [revision]);
			const matched = matchArrayItems(oldItems, newItems);
			return matched.matches.reduce((total, pair) => total + countFieldValueChanges(pair.current, pair.revision, depth + 1), 0)
				+ matched.unmatchedCurrent.reduce((total, item) => total + countFieldValueChanges(item.item, undefined, depth + 1), 0)
				+ matched.unmatchedRevision.reduce((total, item) => total + countFieldValueChanges(undefined, item.item, depth + 1), 0);
		}

		if (isPlainObject(left) || isPlainObject(right)) {
			const oldObject = isPlainObject(current) ? current : {};
			const newObject = isPlainObject(revision) ? revision : {};
			return Array.from(new Set([...Object.keys(oldObject), ...Object.keys(newObject)]))
				.reduce((total, key) => total + countFieldValueChanges(oldObject[key], newObject[key], depth + 1), 0);
		}

		return 1;
	}

	function arrayIdentifierKeys(current, revision) {
		const currentObject = isPlainObject(current) ? current : {};
		const revisionObject = isPlainObject(revision) ? revision : {};
		const keys = Array.from(new Set([...Object.keys(currentObject), ...Object.keys(revisionObject)]));
		const preferred = ['option_id', 'option_name'].filter((key) => keys.includes(key));
		if (preferred.length) return preferred;
		return keys.filter((key) => /(?:^|[_-])(?:id|name)(?:$|[_-])/i.test(key));
	}

	function ArrayItemIdentifiers({ current, revision, keys, tooltipCaptions, contextLabel = '' }) {
		if (!keys.length) return null;
		return el('dl', { className: 'visual-post-compare-fields__values visual-post-compare-fields__array-identifiers' },
			contextLabel ? el('dt', { className: 'visual-post-compare-fields__new-entry', key: 'new-entry' }, contextLabel) : null,
			...keys.flatMap((key) => {
				const currentValue = isPlainObject(current) ? current[key] : undefined;
				const revisionValue = isPlainObject(revision) ? revision[key] : undefined;
				const left = compactFieldValue(currentValue);
				const right = compactFieldValue(revisionValue);
				const value = fieldValuesEqual(left, right)
					? el('span', { className: 'visual-post-compare-fields__identifier-value' }, left)
					: el(FieldValueTree, { current: currentValue, revision: revisionValue, tooltipCaptions });
				return [
					el('dt', { key: key + '-key' }, key),
					el('dd', { key: key + '-value' }, value),
				];
			})
		);
	}

	function arrayItemContent(current, revision, arrayKey, tooltipCaptions, depth) {
		const identifierKeys = arrayKey ? [] : arrayIdentifierKeys(current, revision);
		const currentObject = isPlainObject(current) ? current : {};
		const revisionObject = isPlainObject(revision) ? revision : {};
		const itemKeys = Array.from(new Set([...Object.keys(currentObject), ...Object.keys(revisionObject)]));
		const hasNonIdentifierChange = itemKeys.some((key) => (
			!identifierKeys.includes(key)
			&& !fieldValuesEqual(
				compactFieldValue(currentObject[key], depth),
				compactFieldValue(revisionObject[key], depth)
			)
		));
		const displayedIdentifierKeys = identifierKeys.filter((key) => (
			hasNonIdentifierChange
			|| !fieldValuesEqual(
				compactFieldValue(currentObject[key], depth),
				compactFieldValue(revisionObject[key], depth)
			)
		));
		const newEntryLabel = typeof current === 'undefined' ? __('[New entry:]', 'revisionary') : '';
		const identifiers = displayedIdentifierKeys.length
			? el(ArrayItemIdentifiers, { current, revision, keys: displayedIdentifierKeys, tooltipCaptions, contextLabel: newEntryLabel, key: 'identifiers' })
			: null;
		const changes = FieldValueTree({
			current,
			revision,
			tooltipCaptions,
			omitKeys: identifierKeys,
			depth,
			contextLabel: identifiers ? '' : newEntryLabel,
		});
		if (!identifiers && !changes) return [];
		return [
			arrayKey ? el('div', { className: 'visual-post-compare-fields__array-key', key: 'array-key' }, arrayKey) : null,
			identifiers,
			changes,
		].filter(Boolean);
	}

	function renderArrayItem(current, revision, arrayKey, tooltipCaptions, depth, key) {
		const content = arrayItemContent(current, revision, arrayKey, tooltipCaptions, depth);
		return content.length
			? el('li', { className: 'visual-post-compare-fields__array-item', key }, ...content)
			: null;
	}

	function FieldValueTree({ current, revision, currentImage, revisionImage, currentImageOriginal, revisionImageOriginal, tooltipCaptions, arrayKey, omitKeys = [], depth = 0, contextLabel = '' }) {
		const left = compactFieldValue(current, depth);
		const right = compactFieldValue(revision, depth);
		const currentValueMissing = current === null || typeof current === 'undefined' || current === '';
		const revisionValueMissing = revision === null || typeof revision === 'undefined' || revision === '';
		if (fieldValuesEqual(left, right)) return null;

		if ((Array.isArray(left) || Array.isArray(right)) && !isPlainObject(left) && !isPlainObject(right)) {
			const oldItems = currentValueMissing ? [] : (Array.isArray(current) ? current : [current]);
			const newItems = revisionValueMissing ? [] : (Array.isArray(revision) ? revision : [revision]);
			const matched = matchArrayItems(oldItems, newItems);
			const changedMatches = matched.matches.filter(({ current: oldItem, revision: newItem }) => !fieldValuesEqual(
				compactFieldValue(oldItem, depth + 1),
				compactFieldValue(newItem, depth + 1)
			));
			return el('ul', { className: 'visual-post-compare-fields__values' },
				...changedMatches.map(({ current: oldItem, revision: newItem, currentIndex, revisionIndex }) => renderArrayItem(oldItem, newItem, arrayKey, tooltipCaptions, depth + 1, 'matched-' + currentIndex + '-' + revisionIndex)).filter(Boolean),
				...matched.unmatchedCurrent.map(({ item, index }) => renderArrayItem(item, undefined, arrayKey, tooltipCaptions, depth + 1, 'old-' + index)).filter(Boolean),
				...matched.unmatchedRevision.map(({ item, index }) => renderArrayItem(undefined, item, arrayKey, tooltipCaptions, depth + 1, 'new-' + index)).filter(Boolean)
			);
		}

		if (isPlainObject(left) || isPlainObject(right)) {
			const oldObject = isPlainObject(left) ? left : {};
			const newObject = isPlainObject(right) ? right : {};
			const keys = Array.from(new Set([...Object.keys(oldObject), ...Object.keys(newObject)]))
				.filter((key) => !omitKeys.includes(key));
			const rows = keys.filter((key) => !fieldValuesEqual(oldObject[key], newObject[key])).flatMap((key) => {
				const value = FieldValueTree({
						current: oldObject[key],
						revision: newObject[key],
						tooltipCaptions,
						arrayKey: (Array.isArray(oldObject[key]) || Array.isArray(newObject[key])) ? key : undefined,
						depth: depth + 1,
					});
				return value ? [
					el('dt', { key: key + '-key' }, key),
					el('dd', { key: key + '-value' }, value),
				] : [];
			});
			if (!rows.length) return null;
			if (contextLabel) {
				rows.unshift(el('dt', { className: 'visual-post-compare-fields__new-entry', key: 'new-entry' }, contextLabel));
			}
			return el('dl', { className: 'visual-post-compare-fields__values' }, ...rows);
		}

		const scalar = el(FieldScalar, {
			removed: currentValueMissing ? undefined : left,
			added: revisionValueMissing ? undefined : right,
			currentImage,
			revisionImage,
			currentImageOriginal,
			revisionImageOriginal,
			tooltipCaptions,
		});
		return contextLabel ? el('dl', { className: 'visual-post-compare-fields__values' },
			el('dt', { className: 'visual-post-compare-fields__new-entry' }, contextLabel),
			el('dd', null, scalar)
		) : scalar;
	}

	function FieldsSection({ comparison }) {
		const fieldsByRevision = comparison.fieldComparisons || {};
		const providers = fieldsByRevision[String(comparison.revision.id)] || [];
		const config = ignoredFieldsConfig();
		const [ignoredFields, setIgnoredFields] = useState(config.ignoredFields || []);
		const [bulkSelect, setBulkSelect] = useState(false);
		const [selectedFields, setSelectedFields] = useState([]);
		const [openMenu, setOpenMenu] = useState('');
		const [saving, setSaving] = useState(false);
		const [managerOpen, setManagerOpen] = useState(false);
		const visibleProviders = getVisibleFieldProviders(providers, ignoredFields);
		const promo = (comparison.presentation || {}).fieldsPromo;
		const historicalComparison = Boolean(comparison.isPastComparison && comparison.compareToCurrent === false);
		const tooltipCaptions = historicalComparison ? {
			remove: __('This field was removed', 'revisionary'),
			add: __('This field was added', 'revisionary'),
			modify: __('This field was modified', 'revisionary'),
		} : comparison.revision.isPastRevision ? {
			remove: __('Revision Restore will remove this field', 'revisionary'),
			add: __('Revision Restore will add this field', 'revisionary'),
			modify: __('Revision Restore will modify this field', 'revisionary'),
		} : {
			remove: __('Revision publication will remove this field', 'revisionary'),
			add: __('Revision publication will add this field', 'revisionary'),
			modify: __('Revision publication will modify this field', 'revisionary'),
		};
		useEffect(() => {
			setSelectedFields([]);
			setBulkSelect(false);
			setOpenMenu('');
			setIgnoredFields(ignoredFieldsConfig().ignoredFields || []);
		}, [comparison.revision.id]);
		useEffect(() => {
			if (!openMenu) return undefined;
			const close = () => setOpenMenu('');
			document.addEventListener('mousedown', close);
			return () => document.removeEventListener('mousedown', close);
		}, [openMenu]);

		const toggleField = (key, checked) => setSelectedFields((selected) => checked
			? Array.from(new Set([...selected, key]))
			: selected.filter((item) => item !== key));
		const toggleProvider = (provider, checked) => {
			const keys = providerFields([provider]).map((field) => field.key);
			setSelectedFields((selected) => checked
				? Array.from(new Set([...selected, ...keys]))
				: selected.filter((key) => !keys.includes(key)));
		};
		const updateFieldList = (keys, listType = 'ignored') => {
			if (!keys.length || saving) return;
			setSaving(true);
			updateIgnoredFields('add', keys, providers, listType).then((data) => {
				if (listType === 'ignored') setIgnoredFields(data.ignoredFields || []);
				setSelectedFields([]);
				setOpenMenu('');
			}).catch((error) => window.alert(error.message)).finally(() => setSaving(false));
		};
		const fieldLabel = (field) => el('div', { className: 'visual-post-compare-fields__label-row' },
			bulkSelect ? el('input', {
				type: 'checkbox',
				className: 'visual-post-compare-fields__select-field',
				checked: selectedFields.includes(field.key),
				onChange: (event) => toggleField(field.key, event.target.checked),
				'aria-label': sprintf(__('Select field: %s', 'revisionary'), field.label),
			}) : null,
			el('div', { className: 'visual-post-compare-fields__label' }, field.label),
			config.canManage ? el('div', { className: 'visual-post-compare-fields__actions' },
				el('button', {
					type: 'button',
					className: 'visual-post-compare-fields__actions-toggle',
					'aria-expanded': openMenu === field.key,
					'aria-label': sprintf(__('Field actions: %s', 'revisionary'), field.label),
					onMouseDown: (event) => event.stopPropagation(),
					onClick: (event) => { event.stopPropagation(); setOpenMenu(openMenu === field.key ? '' : field.key); },
				}, el('span', { 'aria-hidden': true }, '\u22ee')),
				openMenu === field.key ? el('div', {
					className: 'visual-post-compare-fields__actions-menu',
					onMouseDown: (event) => event.stopPropagation(),
				},
					el('button', { type: 'button', onClick: () => updateFieldList([field.key]) }, __('Ignore this field', 'revisionary')),
					config.canSuppress ? el('button', { type: 'button', onClick: () => updateFieldList([field.key], 'suppressed') }, __('Suppress this field for all users', 'revisionary')) : null,
					el('button', { type: 'button', onClick: () => { setBulkSelect(true); setOpenMenu(''); } }, __('Bulk-select fields to ignore', 'revisionary'))
				) : null
			) : null
		);
		return el('section', { className: 'visual-post-compare-fields', id: 'visual-post-compare-fields' },
			config.canManage ? el('button', {
				type: 'button',
				className: 'visual-post-compare-fields__manage-button',
				'aria-label': __('Manage comparison fields', 'revisionary'),
				title: __('Manage comparison fields', 'revisionary'),
				onClick: () => setManagerOpen(true),
			}, el('span', { className: 'dashicons dashicons-admin-generic', 'aria-hidden': true })) : null,
			managerOpen ? el(FieldsManagementModal, {
				providers,
				ignoredFields,
				onIgnoredFieldsChange: setIgnoredFields,
				onClose: () => setManagerOpen(false),
			}) : null,
			promo ? el('div', { className: 'visual-post-compare-fields__promo pp-revisions-pro-promo-right-sidebar' },
				el('div', { className: 'postbox-container' },
					el('div', { className: 'meta-box-sortables' },
						el('div', { className: 'advertisement-box-content postbox' },
							el('div', { className: 'postbox-header' },
								el('h3', { className: 'advertisement-box-header hndle is-non-sortable' }, __('Compare Custom Fields', 'revisionary'))
							),
							el('div', { className: 'inside' },
								el('p', null, __('Revisions Pro displays cleanly formatted field changes for:', 'revisionary')),
								el('ul', null,
									...[
										{ label: __('Featured Image', 'revisionary') },
										{ label: __('Core WordPress fields', 'revisionary') },
										{ label: 'Advanced Custom Fields' },
										{ label: 'Pods' },
										{ label: 'PublishPress Cart' },
										{ label: 'WooCommerce' },
										{ label: 'Yoast SEO' },
										{ label: __('Many other popular plugins', 'revisionary'), integrationLink: true },
										{ label: __('Many popular themes', 'revisionary'), integrationLink: true },
									].map((item) => el('li', { key: item.label },
										item.integrationLink && promo.integrationUrl
											? el('a', { className: 'visual-post-compare-fields__integration-link', href: promo.integrationUrl }, item.label)
											: item.label
									))
								),
								el('div', { className: 'pp-pro-badge-banner' },
									el('a', { className: 'pp-upgrade-btn', href: promo.url, target: '_blank', rel: 'noopener noreferrer' }, __('Upgrade to Pro', 'revisionary'))
								)
							)
						)
					)
				)
			) : visibleProviders.length ? el(element.Fragment, null,
				...visibleProviders.map((provider) => el('div', {
				className: 'visual-post-compare-fields__provider',
				id: 'visual-post-compare-fields-provider-' + provider.id,
				key: provider.id,
			},
				el('h3', { className: 'visual-post-compare-fields__headline' }, provider.id === 'wordpress' ? __('Field Changes', 'revisionary') : provider.label),
				...(provider.groups || []).map((group) => el('div', { className: 'visual-post-compare-fields__group', key: group.label },
					(provider.id === 'pods' && String(group.label).toLowerCase() === 'fields')
					|| (provider.id === 'other' && String(group.label).toLowerCase() === 'other')
						? null
						: el('h4', null, group.label),
					...(group.fields || []).map((field) => el('div', { className: 'visual-post-compare-fields__field', key: field.key },
						fieldLabel(field),
						el(FieldValueTree, { current: field.current, revision: field.revision, currentImage: field.currentImage, revisionImage: field.revisionImage, currentImageOriginal: field.currentImageOriginal, revisionImageOriginal: field.revisionImageOriginal, tooltipCaptions })
					))
					)),
				bulkSelect ? el('label', { className: 'visual-post-compare-fields__provider-select' },
					el('input', {
						type: 'checkbox',
						checked: providerFields([provider]).every((field) => selectedFields.includes(field.key)),
						onChange: (event) => toggleProvider(provider, event.target.checked),
					}),
					providerSelectCaption(provider)
				) : null
			)),
			visibleProviders.length && bulkSelect ? el('div', { className: 'visual-post-compare-fields__bulk-actions' },
				el('button', {
					type: 'button',
					className: 'button button-primary',
					disabled: !selectedFields.length || saving,
					onClick: () => updateFieldList(selectedFields),
				}, __('Ignore Selected', 'revisionary'))
			) : null
			) : el('p', { className: 'visual-post-compare-fields__empty' }, __('No field changes detected.', 'revisionary'))
		);
	}

	function ArchiveFieldsNotice({ notice }) {
		if (!notice || !notice.url) return null;
		const message = notice.publication
			? __('Custom field archiving at revision publication is <link>currently disabled</link>. Enable this plugin setting to compare field changes on this screen.', 'revisionary')
			: __('Custom field archiving at post edit is <link>currently disabled</link>. Enable this plugin setting to compare field changes on this screen.', 'revisionary');

		return el('section', { className: 'visual-post-compare-fields' },
			el('div', { className: 'visual-post-compare-fields__promo pp-revisions-pro-promo-right-sidebar' },
				el('div', { className: 'postbox-container' },
					el('div', { className: 'meta-box-sortables' },
						el('div', { className: 'advertisement-box-content postbox' },
							el('div', { className: 'postbox-header' },
								el('h3', { className: 'advertisement-box-header hndle is-non-sortable' }, __('Compare Custom Fields', 'revisionary'))
							),
							el('div', { className: 'inside' },
								el('p', null, createInterpolateElement(message, {
									link: el('a', {
										className: 'visual-post-compare-fields__archive-notice-link',
										href: notice.url,
									}),
								}))
							)
						)
					)
				)
			)
		);
	}

	function mergeProviderDefinitions(...sets) {
		const providers = new Map();
		sets.flat().forEach((provider) => {
			if (!provider || !provider.id) return;
			if (!providers.has(provider.id)) providers.set(provider.id, { id: provider.id, label: provider.label, groups: new Map() });
			const target = providers.get(provider.id);
			(provider.groups || []).forEach((group) => {
				const label = group.label || '';
				if (!target.groups.has(label)) target.groups.set(label, new Map());
				(group.fields || []).forEach((field) => target.groups.get(label).set(field.key, { key: field.key, label: field.label }));
			});
		});
		return Array.from(providers.values()).map((provider) => ({
			id: provider.id,
			label: provider.label,
			groups: Array.from(provider.groups.entries()).map(([label, fields]) => ({ label, fields: Array.from(fields.values()) })),
		}));
	}

	function FieldsManagementModal({ providers, ignoredFields, onIgnoredFieldsChange, onClose }) {
		const config = ignoredFieldsConfig();
		const [ignoredSelection, setIgnoredSelection] = useState(ignoredFields || []);
		const [suppressedSelection, setSuppressedSelection] = useState(config.suppressedFields || []);
		const [savingList, setSavingList] = useState('');
		const currentDefinitions = fieldDefinitionsForKeys(providers, providerFields(providers).map((field) => field.key));
		const [ignoredProviders] = useState(() => providersForKeys(
			mergeProviderDefinitions(config.ignoredProviders || [], currentDefinitions),
			ignoredFields || []
		));
		const [suppressedProviders] = useState(() => providersForKeys(
			mergeProviderDefinitions(config.suppressedProviders || [], currentDefinitions),
			config.suppressedFields || []
		));
		const save = () => {
			setSavingList('all');
			updateIgnoredFields('replace', ignoredSelection, ignoredProviders, 'ignored').then((data) => {
				onIgnoredFieldsChange(data.ignoredFields || []);
				if (!config.canSuppress) return null;
				return updateIgnoredFields('replace', suppressedSelection, suppressedProviders, 'suppressed').then((suppressedData) => {
					config.suppressedFields = suppressedData.ignoredFields || [];
					config.suppressedProviders = suppressedData.providers || [];
				});
			}).then(() => onClose()).catch((error) => window.alert(error.message)).finally(() => setSavingList(''));
		};

		return el('div', { className: 'visual-post-compare-fields__manager-modal' },
			el('div', { className: 'visual-post-compare-fields__manager-backdrop', onClick: onClose }),
			el('div', { className: 'visual-post-compare-fields__manager-frame', role: 'dialog', 'aria-modal': true, 'aria-label': __('Manage comparison fields', 'revisionary') },
				el('button', { type: 'button', className: 'visual-post-compare-fields__manager-close', onClick: onClose, 'aria-label': __('Close', 'revisionary') },
					el('span', { className: 'dashicons dashicons-no-alt', 'aria-hidden': true })
				),
				el('div', { className: 'visual-post-compare-fields__manager-columns' },
					el('div', { className: 'visual-post-compare-fields__manager-column' },
						el(ManagerHeadline, {
							label: __('Ignore Fields', 'revisionary'),
							tooltip: __('Changes to these fields will be hidden from the current user.', 'revisionary'),
						}),
						el('div', { className: 'visual-post-compare-fields__manager-scroll' },
							el(IgnoredFieldsReview, { providers: ignoredProviders, ignoredFields: ignoredSelection, onSelectionChange: setIgnoredSelection, emptyCaption: __('No ignored fields.', 'revisionary') })
						)
					),
					config.canSuppress ? el('div', { className: 'visual-post-compare-fields__manager-column' },
						el(ManagerHeadline, {
							label: __('Suppress Fields', 'revisionary'),
							tooltip: __('Changes to these fields will be hidden from all users.', 'revisionary'),
						}),
						el('div', { className: 'visual-post-compare-fields__manager-scroll' },
							el(IgnoredFieldsReview, { providers: suppressedProviders, ignoredFields: suppressedSelection, onSelectionChange: setSuppressedSelection, emptyCaption: __('No suppressed fields.', 'revisionary') })
						)
					) : null
				),
				el('div', { className: 'visual-post-compare-fields__manager-actions' },
					el('button', { type: 'button', className: 'button button-primary', disabled: Boolean(savingList), onClick: save }, __('Update', 'revisionary'))
				)
			)
		);
	}

	function ManagerHeadline({ label, tooltip }) {
		return el('h2', {
			className: 'visual-post-compare-fields__manager-headline',
			tabIndex: 0,
			'data-visual-post-compare-tooltip': tooltip,
			onMouseEnter: (event) => showDiffTooltip(event.currentTarget, tooltip),
			onMouseLeave: () => hideDiffTooltip(),
			onFocus: (event) => showDiffTooltip(event.currentTarget, tooltip),
			onBlur: () => hideDiffTooltip(),
			onClick: (event) => showDiffTooltip(event.currentTarget, tooltip),
		}, label);
	}

	function getVisibleFieldProviders(providers, ignoredFields = ignoredFieldsConfig().ignoredFields || []) {
		const ignored = new Set([...(ignoredFields || []), ...(ignoredFieldsConfig().suppressedFields || [])]);
		return (providers || []).map((provider) => ({
			...provider,
			groups: (provider.groups || []).map((group) => ({
				...group,
				fields: (group.fields || []).filter((field) => !ignored.has(field.key) && !fieldValuesEqual(
					compactFieldValue(field.current),
					compactFieldValue(field.revision)
				)),
			})).filter((group) => group.fields.length),
		})).filter((provider) => provider.groups.length);
	}

	function IgnoredFieldsReview({ providers, ignoredFields, onSelectionChange, emptyCaption = __('No ignored fields.', 'revisionary') }) {
		const [selected, setSelected] = useState(ignoredFields || []);
		const [openProviderMenu, setOpenProviderMenu] = useState('');
		const reviewRef = useRef(null);
		useEffect(() => onSelectionChange(selected), [selected]);
		useEffect(() => {
			if (!openProviderMenu) return undefined;
			const closeMenu = (event) => {
				if (!event.target.closest('.visual-post-compare-fields__actions')) setOpenProviderMenu('');
			};
			document.addEventListener('mousedown', closeMenu);
			return () => document.removeEventListener('mousedown', closeMenu);
		}, [openProviderMenu]);
		const toggle = (key, checked) => setSelected((current) => checked
			? Array.from(new Set([...current, key]))
			: current.filter((item) => item !== key));
		const toggleProvider = (provider, checked) => {
			const keys = providerFields([provider]).map((field) => field.key);
			setSelected((current) => checked
				? Array.from(new Set([...current, ...keys]))
				: current.filter((key) => !keys.includes(key)));
		};
		return el('section', { ref: reviewRef, className: 'visual-post-compare-fields visual-post-compare-fields--review' },
			!(providers || []).length ? el('p', { className: 'visual-post-compare-fields__empty' }, emptyCaption) : null,
			...(providers || []).map((provider) => el('div', { className: 'visual-post-compare-fields__provider', key: provider.id },
				el('div', { className: 'visual-post-compare-fields__provider-header' },
					el('h3', { className: 'visual-post-compare-fields__headline' }, provider.id === 'wordpress' ? __('Field Changes', 'revisionary') : provider.label),
					el('div', { className: 'visual-post-compare-fields__actions' },
						el('button', {
							type: 'button',
							className: 'visual-post-compare-fields__actions-toggle',
							'aria-expanded': openProviderMenu === provider.id,
							'aria-label': sprintf(__('Field provider actions: %s', 'revisionary'), providerNameForCaption(provider)),
							onClick: () => setOpenProviderMenu(openProviderMenu === provider.id ? '' : provider.id),
						}, el('span', { 'aria-hidden': true }, '\u22ee')),
						openProviderMenu === provider.id ? el('div', { className: 'visual-post-compare-fields__actions-menu' },
							el('button', {
								type: 'button',
								onClick: () => {
									const allSelected = providerFields([provider]).every((field) => selected.includes(field.key));
									toggleProvider(provider, !allSelected);
									setOpenProviderMenu('');
								},
							}, providerFields([provider]).every((field) => selected.includes(field.key))
								? sprintf(__('Unselect all %s fields', 'revisionary'), providerNameForCaption(provider))
								: sprintf(__('Select all %s fields', 'revisionary'), providerNameForCaption(provider)))
						) : null
					)
				),
				...(provider.groups || []).map((group) => el('div', { className: 'visual-post-compare-fields__group', key: group.label },
					provider.id === 'pods' && String(group.label).toLowerCase() === 'fields' ? null : el('h4', null, group.label),
					...(group.fields || []).map((field) => el('div', { className: 'visual-post-compare-fields__field', key: field.key },
						el('div', { className: 'visual-post-compare-fields__label-row' },
							el('input', {
								type: 'checkbox',
								checked: selected.includes(field.key),
								onChange: (event) => toggle(field.key, event.target.checked),
							}),
							el('div', { className: 'visual-post-compare-fields__label' },
								el('span', { className: 'visual-post-compare-fields__ignored-caption' }, field.label)
							)
						)
					))
				))
			))
		);
	}

	window.RevisionaryVisualCompareFields = {
		updateIgnoredFields,
		getSidebarNotice: function (comparison) {
			const notices = comparison.fieldArchiveNotices || {};
			const notice = notices[String(comparison.revision.id)] || null;
			return notice ? el(ArchiveFieldsNotice, { notice: notice }) : null;
		},
		renderIgnoredReview: function (root, providers, ignoredFields, onSelectionChange) {
			const reviewRoot = element.createRoot(root);
			reviewRoot.render(el(IgnoredFieldsReview, {
				providers,
				ignoredFields,
				onSelectionChange,
			}));
			return reviewRoot;
		},
		hasChanges: function (comparison) {
			const fieldsByRevision = comparison.fieldComparisons || {};
			const providers = fieldsByRevision[String(comparison.revision.id)] || [];
			return getVisibleFieldProviders(providers).length > 0;
		},
		getSummary: function (comparison) {
			const fieldsByRevision = comparison.fieldComparisons || {};
			const providers = fieldsByRevision[String(comparison.revision.id)] || [];
			return getVisibleFieldProviders(providers).map((provider) => ({
				id: provider.id,
				label: provider.id === 'wordpress' ? __('WordPress Fields', 'revisionary') : provider.label,
				count: (provider.groups || []).reduce((providerTotal, group) => providerTotal
					+ (group.fields || []).reduce((groupTotal, field) => groupTotal
						+ countFieldValueChanges(field.current, field.revision), 0), 0),
			}));
		},
		render: function (comparison, showTooltip, hideTooltip) {
			showDiffTooltip = typeof showTooltip === 'function' ? showTooltip : function () {};
			hideDiffTooltip = typeof hideTooltip === 'function' ? hideTooltip : function () {};
			return el(FieldsSection, { comparison: comparison });
		}
	};
})(window, document, window.wp);
