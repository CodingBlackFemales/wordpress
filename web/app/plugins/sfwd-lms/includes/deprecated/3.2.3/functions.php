<?php
/**
 * Deprecated functions from LD 3.2.3
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'add_essays_data_columns' ) ) {
	/**
	 * Adds custom columns to the essay post type listing in admin.
	 *
	 * Fires on `manage_edit-sfwd-essays_columns` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 *
	 * @param array $cols An array of admin columns for a post type.
	 *
	 * @return array $cols An array of admin columns for a post type.
	 */
	function add_essays_data_columns( $cols ) {

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}

		return $cols;
	}
}

if ( ! function_exists( 'learndash_essay_bulk_actions' ) ) {
	/**
	 * Adds 'Approve' option next to certain selects on the Essay edit screen in the admin.
	 *
	 * Fires on `admin_footer` hook.
	 *
	 * @todo  check if needed, jQuery selector seems incorrect
	 *
	 * @since 2.3.0
	 * @deprecated 3.2.3
	 */
	function learndash_essay_bulk_actions() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essay_inline_actions' ) ) {
	/**
	 * Adds inline actions to assignments on post listing hover in the admin.
	 *
	 * Fires on `post_row_actions` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 *
	 * @param array   $actions An array of post actions.
	 * @param WP_Post $post    The `WP_Post` object.
	 *
	 * @return array $actions An array of post actions.
	 */
	function learndash_essay_inline_actions( $actions, $post ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}

		return $actions;
	}
}

if ( ! function_exists( 'learndash_modify_admin_essay_listing_query' ) ) {
	/**
	 * Adjust essay post type query in admin
	 *
	 * Essay query should only include essays with a 'graded' and 'not_graded' post status.
	 * Fires on `pre_get_posts` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param WP_Query $essay_query The `WP_Query` instance (passed by reference).
	 */
	function learndash_modify_admin_essay_listing_query( $essay_query ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essays_remove_subbmitdiv_metabox' ) ) { // cspell:disable-line.
	/**
	 * Removes the default submitdiv meta box from the essay post type in the admin edit screen.
	 *
	 * Fires on `admin_menu` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 */
	function learndash_essays_remove_subbmitdiv_metabox() { // cspell:disable-line.
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_register_essay_upload_metabox' ) ) {
	/**
	 * Registers the essay upload metabox.
	 *
	 * Fires on `add_meta_boxes_sfwd-essays` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 */
	function learndash_register_essay_upload_metabox() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essay_upload_meta_box' ) ) {
	/**
	 * Prints the essay upload metabox content.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param WP_Post $essay The `WP_Post` essay object.
	 */
	function learndash_essay_upload_meta_box( $essay ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_register_essay_grading_response_metabox' ) ) {
	/**
	 * Registers the essay grading response metabox.
	 *
	 * Used when a grader wants to respond to a essay submitted by the user.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 */
	function learndash_register_essay_grading_response_metabox() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essay_grading_response_meta_box' ) ) {
	/**
	 * Prints the essay grading response metabox content.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param WP_Post $essay The `WP_Post` essay object.
	 */
	function learndash_essay_grading_response_meta_box( $essay ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_save_essay_grading_response' ) ) {
	/**
	 * Saves the essay grading response to the post meta.
	 *
	 * Fires on `save_post_sfwd-essays` metabox.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param int     $essay_id ID of the essay to be saved.
	 * @param WP_Post $essay    The `WP_Post` essay object.
	 * @param boolean $update   Whether this is an existing post being updated or not.
	 */
	function learndash_save_essay_grading_response( $essay_id, $essay, $update ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_register_essay_grading_metabox' ) ) {
	/**
	 * Registers the essay grading metabox.
	 *
	 * Replaces the submitdiv meta box that comes with every post type.
	 * Fires on `add_meta_boxes_sfwd-essays` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 */
	function learndash_register_essay_grading_metabox() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essay_grading_meta_box' ) ) {
	/**
	 * Prints the essay grading metabox content.
	 *
	 * Copied/modified version of submitdiv from core.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param WP_Post $essay The `WP_Post` essay object.
	 */
	function learndash_essay_grading_meta_box( $essay ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_save_essay_status_metabox_data' ) ) {
	/**
	 * Updates the user's essay and quiz data on post save.
	 *
	 * Fires on `save_post_sfwd-essays` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param int     $essay_id ID of the essay to be saved.
	 * @param WP_Post $essay    The `WP_Post` essay object.
	 * @param boolean $update   Whether this is an existing post being updated or not.
	 */
	function learndash_save_essay_status_metabox_data( $essay_id, $essay, $update ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_restrict_essay_listings_for_group_admins' ) ) {
	/**
	 * Restricts the assignment listings view to group leader only.
	 *
	 * Fires on `parse_query` hook.
	 *
	 * @since 2.2.0
	 * @deprecated 3.2.3
	 *
	 * @param object $query The `WP_Query` instance (passed by reference).
	 */
	function learndash_restrict_essay_listings_for_group_admins( $query ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_essay_bulk_actions_approve' ) ) {
	/**
	 * Handles the approval of the essay in bulk.
	 *
	 * Fires on `load-edit.php` hook.
	 *
	 * @since 2.3.0
	 * @deprecated 3.2.3
	 */
	function learndash_essay_bulk_actions_approve() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_assignment_bulk_actions' ) ) {
	/**
	 * Adds a 'Approve' option next to certain selects on assignment edit screen in admin.
	 *
	 * Fires on `admin_footer` hook.
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @todo  check if needed, jQuery selector seems incorrect
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 */
	function learndash_assignment_bulk_actions() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_assignment_bulk_actions_approve' ) ) {
	/**
	 * Handles approval of assignments in bulk.
	 *
	 * Fires on `load-edit.php` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 */
	function learndash_assignment_bulk_actions_approve() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_restrict_assignment_listings' ) ) {
	/**
	 * Restricts assignment listings view to group leaders only.
	 *
	 * Fires on `parse_query` hook.
	 *
	 * @global string $pagenow
	 * @global string $typenow
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 *
	 * @param WP_Query $query  The WP_Query query object.
	 */
	function learndash_restrict_assignment_listings( $query ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_assignment_approval_link' ) ) {
	/**
	 * Gets assignment approval URL.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 *
	 * @param int $assignment_id Assignment ID.
	 *
	 * @return string Returns assignment approval url.
	 */
	function learndash_assignment_approval_link( $assignment_id ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}

		return '';
	}
}

if ( ! function_exists( 'learndash_assignment_metabox' ) ) {
	/**
	 * Registers assignment metabox.
	 *
	 * Fires on `add_meta_boxes` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 */
	function learndash_assignment_metabox() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_assignment_metabox_content' ) ) {
	/**
	 * Adds approval Link to assignment metabox.
	 *
	 * @global WP_Post  $post     Global post object.
	 * @global SFWD_LMS $sfwd_lms Global SFWD_LMS object.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 */
	function learndash_assignment_metabox_content() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}

if ( ! function_exists( 'learndash_assignment_save_metabox_content' ) ) {
	/**
	 * Updates assignment points and approval status.
	 *
	 * Fires on `save_post` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.2.3
	 *
	 * @param int $assignment_id Assignment ID.
	 */
	function learndash_assignment_save_metabox_content( $assignment_id ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.3' );
		}
	}
}
