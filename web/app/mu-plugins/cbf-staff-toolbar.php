<?php
/**
 * Show the front-end toolbar to content staff.
 *
 * BuddyBoss decides who sees the toolbar on the front end with two site-wide
 * settings: one for administrators and one for every other logged-in member.
 * The setting for other members is off, so learners don't see the toolbar,
 * but that also hides it from Content Editors, Reviewers and internal staff,
 * who use it to reach the editor from a lesson.
 *
 * This shows the toolbar to anyone who can edit LearnDash courses
 * (edit_courses) or other people's posts (edit_others_posts), which covers
 * those roles and no learner role. It still respects each user's own "Show
 * Toolbar when viewing site" preference. It runs after BuddyBoss's filter,
 * and before the BuddyBoss theme's, which hides the toolbar in the mobile app.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'show_admin_bar',
	static function ( bool $show ): bool {
		if ( ! is_user_logged_in() || ! ( current_user_can( 'edit_courses' ) || current_user_can( 'edit_others_posts' ) ) ) {
			return $show;
		}

		return _get_admin_bar_pref();
	},
	50
);
