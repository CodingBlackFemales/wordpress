(function (window, document) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		const config = window.revisionaryIgnoredFieldsSettings || {};
		const renderer = window.RevisionaryVisualCompareFields;
		const openButton = document.querySelector('.revisionary-review-fields');
		const modal = document.querySelector('.revisionary-ignored-fields-modal');
		if (!openButton || !modal || !renderer || typeof renderer.renderIgnoredReview !== 'function') return;

		const closeButton = modal.querySelector('.revisionary-ignored-fields-modal__close');
		const backdrop = modal.querySelector('.revisionary-ignored-fields-modal__backdrop');
		const content = modal.querySelector('.revisionary-ignored-fields-modal__content');
		const updateButton = modal.querySelector('.revisionary-update-ignored-fields');
		const suppressedContent = modal.querySelector('.revisionary-suppressed-fields-modal__content');
		const profileRow = openButton.closest('.revisionary-profile-comparison-fields');
		let selectedFields = config.ignoredFields || [];
		let selectedSuppressedFields = config.suppressedFields || [];
		let rendered = false;
		let reviewRoot = null;
		let suppressedReviewRoot = null;

		const updateCaption = function () {
			const count = (config.ignoredFields || []).length
				+ (config.canSuppress ? (config.suppressedFields || []).length : 0);
			openButton.textContent = String(config.reviewCaption || 'Review %d ignored / suppressed fields').replace('%d', count);
			openButton.hidden = 0 === count;
			if (profileRow) profileRow.hidden = 0 === count;
		};
		const close = function () {
			modal.hidden = true;
			document.body.classList.remove('revisionary-ignored-fields-modal-open');
		};
		const open = function () {
			modal.hidden = false;
			document.body.classList.add('revisionary-ignored-fields-modal-open');
			if (!rendered) {
				 reviewRoot = renderer.renderIgnoredReview(content, config.providers || [], config.ignoredFields || [], function (selection) {
					selectedFields = selection;
				});
				if (config.canSuppress && suppressedContent) {
					suppressedReviewRoot = renderer.renderIgnoredReview(suppressedContent, config.suppressedProviders || [], config.suppressedFields || [], function (selection) {
						selectedSuppressedFields = selection;
					});
				}
				rendered = true;
			}
		};

		openButton.addEventListener('click', open);
		closeButton.addEventListener('click', close);
		backdrop.addEventListener('click', close);
		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && !modal.hidden) close();
		});
		updateButton.addEventListener('click', function () {
			if (updateButton.disabled) return;
			updateButton.disabled = true;
			renderer.updateIgnoredFields('replace', selectedFields, config.providers || []).then(function (data) {
				config.ignoredFields = data.ignoredFields || [];
				config.providers = data.providers || [];
				if (!config.canSuppress) return null;
				return renderer.updateIgnoredFields('replace', selectedSuppressedFields, config.suppressedProviders || [], 'suppressed');
			}).then(function (data) {
				if (data) {
					config.suppressedFields = data.ignoredFields || [];
					config.suppressedProviders = data.providers || [];
				}
				updateCaption();
				if (reviewRoot && typeof reviewRoot.unmount === 'function') reviewRoot.unmount();
				if (suppressedReviewRoot && typeof suppressedReviewRoot.unmount === 'function') suppressedReviewRoot.unmount();
				reviewRoot = null;
				suppressedReviewRoot = null;
				rendered = false;
				close();
			}).catch(function (error) {
				window.alert(error.message);
			}).finally(function () {
				updateButton.disabled = false;
			});
		});
	});
})(window, document);
