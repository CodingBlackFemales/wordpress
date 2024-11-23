<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fire to add support for third party plugin
 *
 * @since BuddyBoss 1.2.2
 */
function buddyboss_theme_helper_plugins_loaded_callback() {

	if ( function_exists( 'is_plugin_active' ) ) {

		/**
		 * Include plugin when plugin is activated.
		 *
		 * Support MemberPress + BuddyPress Integration.
		 */
		if ( function_exists( 'buddypress' ) && is_plugin_active( 'memberpress-buddypress/main.php' ) ) {
			/**
			 * This action is use when admin bar is Disable.
			 */
			add_action( 'buddyboss_theme_after_bb_profile_menu', 'buddyboss_theme_helper_add_buddyboss_menu_for_memberpress_buddypress', 100 );
		}

		/**
		 * Include plugin when plugin is activated.
		 *
		 * @since 2.5.80
		 *
		 * Support LearnDash Course Reviews.
		 */
		if ( is_plugin_active( 'learndash-course-reviews/learndash-course-reviews.php' ) && class_exists( 'LearnDash_Course_Reviews' ) ) {
			/**
			 * Remove extra `div` tag to avoid breaking UI.
			 */
			add_action( 'learndash_course_reviews_review_reply', 'bb_output_review_reply_template', 9, 1 );
		}

		/**
		 * Support WPML Multilingual CMS.
		 *
		 * This code provides support for WPML Multilingual CMS by adding necessary filters to modify
		 * navigation menu attributes and icons when the WPML plugin is active.
		 */
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			add_filter( 'bb_buddypanel_nav_menu_link_attributes', 'bb_theme_set_wpml_nav_menu_link_attributes' );
			add_filter( 'bb_theme_nav_menu_item_add_icon', 'bb_theme_add_wpml_nav_menu_item_icon', 10, 2 );
			add_filter( 'bb_theme_buddypanel_nav_menu_item_icon', 'bb_theme_set_wpml_nav_menu_item_icon_class', 10, 2 );
			add_filter( 'bb_theme_sub_nav_menu_wrap_item_icon', 'bb_theme_set_wpml_nav_menu_item_icon_class', 10, 2 );
		}
	}
}

add_action( 'init', 'buddyboss_theme_helper_plugins_loaded_callback', 100 );

/**
 * Add Menu in Admin section for MemberPress + BuddyPress Integration plugin
 *
 * @since BuddyBoss 1.2.2
 *
 * @param $menus
 */
function buddyboss_theme_helper_add_buddyboss_menu_for_memberpress_buddypress() {
	global $bp;

	$main_slug = apply_filters( 'mepr-bp-info-main-nav-slug', 'mp-membership' );
	$name      = apply_filters( 'mepr-bp-info-main-nav-name', _x( 'Membership', 'ui', 'buddyboss-theme' ) );
	?>
	<li id="wp-admin-bar-mp-membership" class="menupop">
		<a class="ab-item" aria-haspopup="true" href="<?php echo $bp->loggedin_user->domain . $main_slug . '/'; ?>">
			<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo $name; ?>
		</a>
		<div class="ab-sub-wrapper">
			<ul id="wp-admin-bar-mp-membership-default" class="ab-submenu">
				<li id="wp-admin-bar-mp-info">
					<a class="ab-item" href="<?php echo $bp->loggedin_user->domain . $main_slug . '/'; ?>">
						<?php echo _x( 'Info', 'ui', 'buddyboss-theme' ); ?>
					</a>
				</li>
				<li id="wp-admin-bar-mp-subscriptions">
					<a class="ab-item" href="<?php echo $bp->loggedin_user->domain . $main_slug . '/mp-subscriptions/'; ?>">
						<?php echo _x( 'Subscriptions', 'ui', 'buddyboss-theme' ); ?>
					</a>
				</li>
				<li id="wp-admin-bar-mp-payments">
					<a class="ab-item" href="<?php echo $bp->loggedin_user->domain . $main_slug . '/mp-payments/'; ?>">
						<?php echo _x( 'Payments', 'ui', 'buddyboss-theme' ); ?>
					</a>
				</li>
			</ul>
		</div>
	</li>
	<?php
}

/**
 * Fire to add support for learndash course review plugin.
 *
 * @since 2.5.80
 *
 * @param int $course_id Course ID.
 */
function bb_output_review_reply_template( $course_id ) {
	bb_theme_remove_class_action( 'learndash_course_reviews_review_reply', 'LearnDash_Course_Reviews_Loader', 'output_review_reply_template' )
	?>
	<div id="learndash-course-reviews-reply" style="display: none">
		<h3 id="learndash-course-reviews-reply-heading" class="learndash-course-reviews-heading">
			<?php esc_html_e( 'Leave a reply', 'buddyboss-theme' ); ?>
			<small>
				<a rel="nofollow" id="cancel-comment-reply-link" href="#">
					<?php esc_html_e( 'Cancel reply', 'buddyboss-theme' ); ?>
				</a>
			</small>
		</h3>
		<form action="" method="post" name="">
			<div class="grid-container full">
				<div class="grid-x">
					<div class="small-12 cell">
						<label for="learndash-course-reviews-review">
							<?php esc_html_e( 'Comment', 'buddyboss-theme' ); ?> <span class="required">*</span>
						</label>
						<textarea
							id="learndash-course-reviews-reply"
							name="learndash-course-reviews-reply"
							cols="45"
							rows="8"
							aria-required="true"
							required="required"
						></textarea>
					</div>
				</div>
				<div class="grid-x">
					<div class="small-12 cell">
						<input
							type="submit"
							class="button primary expanded"
							value="<?php esc_attr_e( 'Post Reply', 'buddyboss-theme' ); ?>"
						/>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Function to compatible with WPML menu tooltips.
 *
 * @since 2.5.60
 *
 * @param array $atts The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
 *
 * @return array The array of menu item attributes.
 */
function bb_theme_set_wpml_nav_menu_link_attributes( $atts ) {

	if ( ! empty( $atts['data-balloon'] ) ) {
		$atts['data-balloon'] = wp_strip_all_tags( $atts['data-balloon'] );
	}

	return $atts;
}

/**
 * Function to add icon to WPML menu item.
 *
 * @since 2.5.60
 *
 * @param string  $icon Menu icon.
 * @param integer $id   Menu item ID.
 *
 * @return string The menu item icon.
 */
function bb_theme_add_wpml_nav_menu_item_icon( $icon, $id ) {
	if ( ! empty( $id ) && str_contains( $id, 'wpml-' ) ) {
		$icon = '<i class="_mi _before buddyboss bb-icon-l bb-icon-globe"></i>';
	}

	return $icon;
}

/**
 * Function to set icon to WPML menu item.
 *
 * @since 2.5.60
 *
 * @param string  $icon Menu icon.
 * @param WP_Post $item Menu item data object.
 */
function bb_theme_set_wpml_nav_menu_item_icon_class( $icon, $item ) {
	if ( isset( $item->classes ) && is_array( $item->classes ) && in_array( 'wpml-ls-item', $item->classes, true ) ) {
		$icon = 'bb-icon-globe';
	}

	return $icon;
}

/**
 * Function to disable page transition for Elementor Pro.
 *
 * @since 2.6.10
 *
 * @return string
 */
function bb_elementor_pro_disable_page_transition() {
	return buddyboss_theme()->elementor_pro_helper() ? 'data-e-disable-page-transition="true"' : '';
}
