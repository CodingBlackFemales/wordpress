import {
	PanelBody,
	TextControl,
} from "@wordpress/components";

/**
 * LearnDash Block Functions
 *
 * This is a collection of common functions used within the LeanDash blocks
 *
 * @since 2.5.9
 * @package LearnDash
 */

import { __, _x } from "@wordpress/i18n";

import '../../../src/assets/js/main';

const ldlms_get_custom_label = learndash.customLabel.get,
	ldlms_get_custom_label_lower = learndash.customLabel.getLower,
	ldlms_get_custom_label_slug = learndash.customLabel.getSlug;

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @param {string} token Token to return from meta array. If not provided will array is returned.
 */
export function ldlms_get_post_edit_meta(token = "") {
	if (token !== "") {
		if (typeof ldlms_settings.meta.post[token] !== "undefined") {
			return ldlms_settings.meta.post[token];
		}
	}
	if (typeof ldlms_settings.meta.post !== "undefined") {
		return ldlms_settings.meta.post;
	}
}

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @param {string} token         Token to return from meta array. If not provided will array is returned.
 * @param {any}    default_value
 */
export function ldlms_get_setting(token = "", default_value) {
	if (token !== "" && typeof ldlms_settings.settings[token] !== "undefined") {
		return ldlms_settings.settings[token];
	}
	return default_value;
}

export {
	ldlms_get_custom_label,
	ldlms_get_custom_label_lower,
	ldlms_get_custom_label_slug
};

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @param {string} token Token to return from meta array. If not provided will array is returned.
 */
export function ldlms_get_per_page(token = "") {
	if (token !== "") {
		if (typeof ldlms_settings.settings.per_page[token] !== "undefined") {
			return ldlms_settings.settings.per_page[token];
		}
	} else if (typeof ldlms_settings.meta.posts_per_page !== "undefined") {
		return ldlms_settings.meta.posts_per_page;
	}
}

/**
 * Returns integer value for variable.
 *
 * @param {any} var_value Variable to determine integer from.
 * @return {number} value of zero.
 */
export function ldlms_get_integer_value(var_value) {
	if (typeof var_value === "undefined") {
		return 0;
	}
	const var_value_tmp = parseInt(var_value);
	if (isNaN(var_value_tmp)) {
		return 0;
	}

	return var_value_tmp;
}

/**
 * Retrieve the active template key/slug.
 *
 * @since 4.0.0
 */
export function ldlms_templates_get_active_key() {
	if (typeof ldlms_settings.templates.active !== "undefined") {
		return ldlms_settings.templates.active;
	}
	return "";
}

/**
 * Retrieve the active template name.
 *
 * @since 4.0.0
 */
export function ldlms_templates_get_active_name() {
	if (typeof ldlms_settings.templates.list !== "undefined") {
		let active_template = ldlms_templates_get_active_key();
		if (typeof active_template !== "undefined" && active_template !== "") {
			if (
				typeof ldlms_settings.templates.list[active_template] !== "undefined"
			) {
				return ldlms_settings.templates.list[active_template];
			}
		}
	}
	return "";
}

/**
 * Retrieve the Legacy template not supported message block panel.
 *
 * @since 4.0.0
 */
export function ldlms_get_block_legacy_support_panel() {
	let message_text = ldlms_get_legacy_not_supported_message();
	if (message_text !== "") {
		return (
			<PanelBody title={__("Warning", "learndash")} opened={true}>
				<TextControl
					help={message_text}
					value={""}
					type={"hidden"}
					className={"notice notice-error"}
				/>
			</PanelBody>
		);
	}
	return "";
}

/**
 * Retrieve the Legacy template not supported message text.
 *
 * @since 4.0.0
 */
export function ldlms_get_legacy_not_supported_message() {
	let active_template_key = ldlms_templates_get_active_key();
	if (active_template_key == "legacy") {
		let active_template_name = ldlms_templates_get_active_name();

		return sprintf(
			// translators: placeholder: current template name.
			_x(
				'The current LearnDash template "%s" does not support this block. Please select a different template.',
				"placeholder: current template name",
				"learndash"
			),
			active_template_name
		);
	}
	return "";
}

/**
 * Returns the post type slug for a given token.
 *
 * @since 4.0.0
 *
 * @param {string} token The post_type common key like 'course', 'lesson', 'quiz', etc.
 * @return {string} The post_type slug like 'sfwd-courses', 'ld-exam'. Empty string if not found.
 */
export function ldlms_get_post_type_slug(token = "") {
	if (typeof ldlms_settings.post_types !== "undefined" && token !== "") {
		if (typeof ldlms_settings.post_types[token] !== "undefined") {
			return ldlms_settings.post_types[token];
		}
	}
	return "";
}
