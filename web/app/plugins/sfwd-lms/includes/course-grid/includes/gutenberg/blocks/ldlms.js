/**
 * LearnDash Block Functions
 *
 * This is a collection of common functions used within the LeanDash blocks
 *
 * @since 4.21.4
 */

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @since 4.21.4
 *
 * @param {string} token Token to return from meta array. If not provided will array is returned.
 *
 * @return {any} Meta information about the post being edited.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_post_edit_meta( token ) {
	// eslint-disable-next-line eqeqeq
	if ( typeof token !== 'undefined' && token != '' ) {
		// eslint-disable-next-line camelcase, no-undef
		if ( typeof ldlms_settings.meta.post[ token ] !== 'undefined' ) {
			// eslint-disable-next-line camelcase, no-undef
			return ldlms_settings.meta.post[ token ];
		}
		// eslint-disable-next-line camelcase, no-undef
	} else if ( typeof ldlms_settings.meta.post !== 'undefined' ) {
		// eslint-disable-next-line camelcase, no-undef
		return ldlms_settings.meta.post;
	}
}

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @since 4.21.4
 *
 * @param {string} token         Token to return from meta array. If not provided will array is returned.
 * @param {string} default_value
 *
 * @return {any} Meta information about the post being edited.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_setting( token, default_value ) {
	if (
		typeof token !== 'undefined' &&
		// eslint-disable-next-line eqeqeq
		token != '' &&
		// eslint-disable-next-line camelcase, no-undef
		typeof ldlms_settings.settings[ token ] !== 'undefined'
	) {
		// eslint-disable-next-line camelcase, no-unused-vars, no-undef
		const token_value = ldlms_settings.settings[ token ];
		// eslint-disable-next-line camelcase, no-undef
		return ldlms_settings.settings[ token ];
	}
	// eslint-disable-next-line camelcase
	return default_value;
}

/**
 * Returns the label for custom label element
 *
 * @since 4.21.4
 *
 * @param {string} token Will represent the custom label field to retrieve Course, Courses, Lesson, Quiz.
 *
 * @return {string} Label for custom label element.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_custom_label( token ) {
	// eslint-disable-next-line camelcase, no-undef, eqeqeq
	if ( typeof ldlms_settings.meta.post !== 'undefined' && token != '' ) {
		if (
			// eslint-disable-next-line camelcase, no-undef
			typeof ldlms_settings.settings.custom_labels[ token ] !==
			'undefined'
		) {
			// eslint-disable-next-line camelcase, no-undef
			token = ldlms_settings.settings.custom_labels[ token ];
		}
	}
	return token;
}

/**
 * Returns the lowercase label for custom label element
 *
 * @since 4.21.4
 *
 * @param {string} token Will represent the custom label field to retrieve Course, Courses, Lesson, Quiz.
 *
 * @return {string} Label for custom label element.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_custom_label_lower( token ) {
	// eslint-disable-next-line camelcase, no-undef, eqeqeq
	if ( typeof ldlms_settings.meta.post !== 'undefined' && token != '' ) {
		if (
			// eslint-disable-next-line camelcase, no-undef
			typeof ldlms_settings.settings.custom_labels[ token + '_lower' ] !==
			'undefined'
		) {
			// eslint-disable-next-line camelcase, no-undef
			token = ldlms_settings.settings.custom_labels[ token + '_lower' ];
		}
	}
	return token;
}

/**
 * Returns the slug for custom label element
 *
 * @since 4.21.4
 *
 * @param {string} token Will represent the custom label field to retrieve Course, Courses, Lesson, Quiz.
 *
 * @return {string} Slug for custom label element.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_custom_label_slug( token ) {
	// eslint-disable-next-line eqeqeq
	if ( token != '' ) {
		if (
			// eslint-disable-next-line camelcase, no-undef
			typeof ldlms_settings.settings.custom_labels[ token + '_slug' ] !==
			'undefined'
		) {
			// eslint-disable-next-line camelcase, no-undef
			token = ldlms_settings.settings.custom_labels[ token + '_slug' ];
		}
	}
	return token;
}

/**
 * Will retrieve meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks
 * are made to ensure that a post is being edited.
 *
 * @since 4.21.4
 *
 * @param {string} token Token to return from meta array. If not provided will array is returned.
 *
 * @return {number|void} Per page value or void if not found.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_per_page( token ) {
	// eslint-disable-next-line eqeqeq
	if ( typeof token !== 'undefined' && token != '' ) {
		if (
			// eslint-disable-next-line camelcase, no-undef
			typeof ldlms_settings.settings.per_page[ token ] !== 'undefined'
		) {
			// eslint-disable-next-line camelcase, no-undef
			return ldlms_settings.settings.per_page[ token ];
		}
		// eslint-disable-next-line camelcase, no-undef
	} else if ( typeof ldlms_settings.meta.posts_per_page !== 'undefined' ) {
		// eslint-disable-next-line camelcase, no-undef
		return ldlms_settings.meta.posts_per_page;
	}
}

/**
 * Returns integer value for variable.
 *
 * @since 4.21.4
 *
 * @param {any} var_value Variable to determine integer from.
 *
 * @return {number} Value or zero.
 */
// eslint-disable-next-line camelcase
export function ldlms_get_integer_value( var_value ) {
	// eslint-disable-next-line camelcase
	if ( typeof var_value === 'undefined' ) {
		// eslint-disable-next-line camelcase
		var_value = 0;
	}
	// eslint-disable-next-line camelcase
	var_value = parseInt( var_value );
	if ( isNaN( var_value ) ) {
		// eslint-disable-next-line camelcase
		var_value = 0;
	}

	// eslint-disable-next-line camelcase
	return var_value;
}
