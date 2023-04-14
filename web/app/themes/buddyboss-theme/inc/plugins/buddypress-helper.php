<?php

/**
 * BuddyPress Helper Functions
 */

namespace BuddyBossTheme;

use DOMDocument;
use WP_Admin_Bar;
use BP_Search;

if ( ! class_exists( '\BuddyBossTheme\BuddyPressHelper' ) ) {

	class BuddyPressHelper {

		protected $_is_active = false;

		/**
		 * Constructor
		 */
		public function __construct() {

			// Avatar Sizes.
			if ( ! defined( 'BP_AVATAR_THUMB_WIDTH' ) ) {
				define( 'BP_AVATAR_THUMB_WIDTH', 150 );
			}

			if ( ! defined( 'BP_AVATAR_THUMB_HEIGHT' ) ) {
				define( 'BP_AVATAR_THUMB_HEIGHT', 150 );
			}

			if ( ! defined( 'BP_AVATAR_FULL_WIDTH' ) ) {
				define( 'BP_AVATAR_FULL_WIDTH', 300 );
			}

			if ( ! defined( 'BP_AVATAR_FULL_HEIGHT' ) ) {
				define( 'BP_AVATAR_FULL_HEIGHT', 300 );
			}

			add_action( 'bp_init', array( $this, 'set_active' ) );
		}

		public function set_active() {
			$this->_is_active = true;

			add_action( THEME_HOOK_PREFIX . 'header_user_menu_items', array( $this, 'render_header_menu' ), 8 );
			add_action( 'bp_after_member_header', array( $this, 'change_sitewide_notices' ) );

			if (
				function_exists( 'bp_disable_advanced_profile_search' ) &&
				false === bp_disable_advanced_profile_search() &&
				function_exists( 'bp_profile_search_show_form' )
			) {
				// Remove profile search form.
				remove_action( 'bp_before_directory_members', 'bp_profile_search_show_form' );
				add_action( THEME_HOOK_PREFIX . 'before_members_widgets', 'bp_profile_search_show_form' );
			}

			add_filter( 'bp_get_message_thread_excerpt', array( $this, 'get_message_thread_excerpt' ), 10, 2 );
			add_filter( 'bp_before_has_message_threads_parse_args', array( $this, 'has_message_threads_parse_args' ), 10 );

			add_filter( 'bp_search_js_settings', array( $this, 'filter_search_js_settings' ) );
			add_filter( 'bp_search_results_group_start_html', array( $this, 'filter_bp_search_results_group_start_html' ), 10, 2 );
			add_action( 'bp_before_search_members_html', array( $this, 'action_before_search_member' ) );
			add_action( 'bp_after_search_members_html', array( $this, 'action_after_search_member' ) );
			add_action( 'bp_before_search_groups_html', array( $this, 'action_before_search_group' ) );
			add_action( 'bp_after_search_groups_html', array( $this, 'action_after_search_group' ) );
			add_action( 'bp_before_search_activity_html', array( $this, 'action_before_search_activity' ) );
			add_action( 'bp_after_search_activity_html', array( $this, 'action_after_search_activity' ) );
			add_action( 'wp_footer', array( $this, 'admin_toolbar_cloner' ) );

			add_filter( 'heartbeat_received', array( $this, 'heartbeat_unread_notifications' ), 11 );
			add_filter( 'heartbeat_nopriv_received', array( $this, 'heartbeat_unread_notifications' ), 11 );

			add_action( 'admin_footer', array( $this, 'buddyboss_theme_header_menu_admin_js' ), 999 ); // For back-end.

			add_filter( 'comments_template_query_args', array( $this, 'buddyboss_theme_template_query_arguments' ), 999, 1 );
			add_filter( 'comments_template', array( $this, 'buddyboss_theme_comments_template' ), 999, 1 );

			add_filter( 'bp_nouveau_get_single_item_subnav_classes', array( $this, 'buddyboss_theme_single_item_subnav_classes' ), 10, 1 );
		}

		public function is_active() {
			return $this->_is_active;
		}

		/**
		 * @todo Complete this
		 * @param type $slug
		 */
		protected function _get_tooltip_for_menu_item( $slug = '' ) {
			$text = ! empty( $slug ) ? ucfirst( $slug ) : '';
			return esc_attr( $text );
		}

		protected function _get_icon_for_menu_item( $slug = '' ) {
			$icons = array(
				'activity'      => 'bb-icon-l bb-icon-home',
				'profile'       => 'bb-icon-l bb-icon-user',
				'messages'      => 'bb-icon-l bb-icon-inbox',
				'notifications' => 'bb-icon-l bb-icon-bell',
			);

			return isset( $icons[ $slug ] ) ? $icons[ $slug ] : '';
		}

		protected function _get_count_from_nav_name( $name ) {
			$count = '';

			$start = strpos( $name, '<span class="count"' );
			if ( $start ) {
				$count_html = substr( $name, $start );
				$count      = strip_tags( $count );
			}

			return $count;
		}

		function render_header_menu() {
			get_template_part( 'template-parts/header-profile-menu' );
		}

		function init_admin_bar() {
			global $wp_admin_bar;

			/* Load the admin bar class code ready for instantiation */
			require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

			/**
			 * Filters the admin bar class to instantiate.
			 *
			 * @since 3.1.0
			 *
			 * @param string $wp_admin_bar_class Admin bar class to use. Default 'WP_Admin_Bar'.
			 */
			$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
			if ( class_exists( $admin_bar_class ) ) {
				$wp_admin_bar = new $admin_bar_class();
			} else {
				return false;
			}

			$wp_admin_bar->initialize();
			$wp_admin_bar->add_menus();
		}

		function memory_admin_bar_nodes() {
			static $bb_memory_admin_bar_step = null;
			global $menu_template;

			// Avoid multiple run.
			if ( ! empty( $menu_template ) ) {
				return false;
			}

			if ( is_null( $bb_memory_admin_bar_step ) ) {
				$bb_memory_admin_bar_step = 1;
				ob_start();
			} else {
				$menu_template = ob_get_clean();
				echo $menu_template;
			}
		}

		function admin_bar_in_header() {
			if ( ! is_admin() ) {
				remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
				add_action( THEME_HOOK_PREFIX . 'before_header', 'wp_admin_bar_render' );
			}
		}

		function group_members( $group_id = false, $role = array() ) {

			if ( ! $group_id ) {
				return '';
			}

			$members = new \BP_Group_Member_Query(
				array(
					'group_id'     => $group_id,
					'per_page'     => 3,
					'page'         => 1,
					'group_role'   => $role,
					'exclude'      => false,
					'search_terms' => false,
					'type'         => 'active',
				)
			);

			$total   = $members->total_users;
			$members = array_values( $members->results );

			if ( ! empty( $members ) ) {
				?><span class="bs-group-members">
				<?php
				foreach ( $members as $member ) {
					$avatar = bp_core_fetch_avatar(
						array(
							'item_id'    => $member->ID,
							'avatar_dir' => 'avatars',
							'object'     => 'user',
							'type'       => 'thumb',
							'html'       => false,
						)
					);
					?>
					<img src="<?php echo $avatar; ?>"
						 alt="<?php echo esc_attr( bp_core_get_user_displayname( $member->ID ) ); ?>" class="round"/>
					<?php
				}
				?>
				</span>
				<?php
				if ( $total - sizeof( $members ) != 0 ) {
					$member_count = $total - sizeof( $members );
					?>
					<span class="members">
						<span class="members-count-g">+<?php echo esc_html( $member_count ); ?></span> <?php printf( _n( 'member', 'members', $member_count, 'buddyboss-theme' ) ); ?>
					</span>
					<?php
				}
			}

		}

		function get_message_thread_excerpt( $excerpt ) {
			return strip_tags( bp_create_excerpt( $excerpt, 140, array( 'ending' => '&hellip;' ) ) );
		}

		function change_sitewide_notices() {
			$bp = buddypress();

			if ( ! empty( $bp->template_message ) && ! empty( $bp->template_message_type ) && $bp->template_message_type == 'bp-sitewide-notice' ) {
				$bp->template_message = array();
			}
		}

		/**
		 * BP Search filter autocomplete and search forms
		 *
		 * @param array $settings Settings array.
		 *
		 * @return array
		 */
		function filter_search_js_settings( $settings ) {
			$settings['autocomplete_selector'] = '.header-search-wrap .search-form';
			$settings['form_selector']         = '.widget_search .search-form';
			return $settings;
		}

		public function buddyboss_theme_show_private_message_button( $user, $user2 ) {

			if ( bp_is_active( 'messages' ) ) {
				if ( function_exists( 'bp_force_friendship_to_message' ) && true === bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
					$member_friend_status = friends_check_friendship_status( $user, $user2 );
					if ( 'is_friend' === $member_friend_status ) {
						return 'yes';
					} else {
						return 'no';
					}
				}
			} else {
				return 'no';
			}
			return 'yes';
		}

		/**
		 * Display result group title on the search subset pages
		 *
		 * @param string $start_html    HTML.
		 * @param string $search_subset Search subset.
		 *
		 * @return string
		 */
		function filter_bp_search_results_group_start_html( $start_html, $search_subset ) {

			if ( ! empty( $_REQUEST['subset'] ) ) {
				ob_start();

				// Total count.
				$instance      = BP_Search::instance();
				$total_results = $instance->search_helpers[ $search_subset ]->get_total_match_count( $_REQUEST['s'], $search_subset );

				// Label.
				$search_items = bp_search_items();
				$label        = isset( $search_items[ $search_subset ] ) ? $search_items[ $search_subset ] : $search_subset;
				$label        = apply_filters( 'bp_search_label_search_type', $label );
				?>
				<header class="results-group-header clearfix">
					<h3 class="results-group-title">
						<span><?php echo esc_html( $label ); ?></span>
					</h3>
					<span class="total-results"><?php printf( _n( '%d result', '%d results', $total_results, 'buddyboss-theme' ), $total_results ); ?></span>
				</header>
				<?php
				$start_html .= ob_get_clean();
			}

			return $start_html;
		}

		/**
		 * Set default members avatar to display in search results
		 */
		function action_before_search_member() {
			add_action( 'bp_core_default_avatar_user', array( $this, 'filter_search_default_avatar_member' ), 999, 1 );
		}

		/**
		 * Unset default members avatar set to display in search results
		 */
		function action_after_search_member() {
			remove_action( 'bp_core_default_avatar_user', array( $this, 'filter_search_default_avatar_member' ), 999, 1 );
		}

		/**
		 * Set default members avatar to display in search results
		 */
		function action_before_search_activity() {
			add_action( 'bp_core_default_avatar_user', array( $this, 'filter_search_default_avatar_member' ), 999, 1 );
		}

		/**
		 * Unset default members avatar set to display in search results
		 */
		function action_after_search_activity() {
			remove_action( 'bp_core_default_avatar_user', array( $this, 'filter_search_default_avatar_member' ), 999, 1 );
		}

		/**
		 * Set default groups avatar to display in search results
		 */
		function action_before_search_group() {
			add_action( 'bp_core_default_avatar_group', array( $this, 'filter_search_default_avatar_group' ), 999, 1 );
		}

		/**
		 * Unset default groups avatar set to display in search results
		 */
		function action_after_search_group() {
			remove_action( 'bp_core_default_avatar_group', array( $this, 'filter_search_default_avatar_group' ), 999, 1 );
		}

		/**
		 * Group default avatar callback
		 *
		 * @param string $url Avatar URL.
		 *
		 * @return string
		 */
		function filter_search_default_avatar_group( $url ) {
			return get_template_directory_uri() . '/assets/images/svg/groups.svg';
		}

		/**
		 * Members default avatar callback
		 *
		 * @param string $url Avatar URL.
		 *
		 * @return string
		 */
		function filter_search_default_avatar_member( $url ) {
			return get_template_directory_uri() . '/assets/images/svg/members.svg';
		}

		/**
		 *
		 * Clone Admin Toolbar Menu to Profile Dropdown Menu
		 */
		function admin_toolbar_cloner() {
			if ( is_admin_bar_showing() ) {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function($){
							const header_profile_ul = $('#header-aside > ul.sub-menu');
							const toolbar_menu = $('#wp-admin-bar-my-account-buddypress > li.menupop').clone();

							$(header_profile_ul).children('.menupop').remove();
							$(header_profile_ul).children('li:first-child').append(toolbar_menu);
						});
					</script>
				<?php
			}
		}

		/**
		 * Gets all unread notifications && messages
		 *
		 * @param array $response Array containing Heartbeat API response.
		 *
		 * @return array $response
		 */
		function heartbeat_unread_notifications( $response = array() ) {
			$show_notifications = buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_notifications' );
			$show_messages      = buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_messages' );

			if ( $show_notifications && bp_loggedin_user_id() && bp_is_active( 'notifications' ) ) {
				ob_start();
				get_template_part( 'template-parts/unread-notifications' );
				$response['unread_notifications'] = ob_get_clean();
				$response['total_notifications']  = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
			}

			if ( $show_messages && bp_loggedin_user_id() && bp_is_active( 'messages' ) ) {
				ob_start();
				get_template_part( 'template-parts/unread-messages' );
				$response['unread_messages']       = ob_get_clean();
				$response['total_unread_messages'] = messages_get_unread_count();
			}

			return $response;
		}

		function buddyboss_theme_header_menu_admin_js() {

			$menu = wp_nav_menu(
				array(
					'theme_location' => 'header-my-account',
					'echo'           => false,
					'fallback_cb'    => '__return_false',
				)
			);

			if ( ! empty( $menu ) ) {
				?>
				<script type="text/javascript">
					jQuery(document).ready( function(){
						if( jQuery( '.admin-bar #wp-admin-bar-my-account-default' ).length ) {
							jQuery( '.admin-bar #wp-admin-bar-my-account-default > li' ).each( function() {
								if( jQuery( this ).hasClass( 'menupop' ) ) {
									jQuery( this ).closest( 'ul' ).addClass( 'has-menupop' );
									return false; // Break loop as we know this menu item has icon.
								}
							});
						}
					});
				</script>
				<style type="text/css">
					ul#wp-admin-bar-my-account-default{
						background-color: #464b50;
					}
					ul#wp-admin-bar-my-account-default.has-menupop + #wp-admin-bar-my-account-buddypress li:not(.menupop),
					#wp-admin-bar-my-account-default.has-menupop > li:not(.menupop) {
						padding-left: 16px;
					}
					#wp-admin-bar-user-actions #wp-admin-bar-user-info {
						margin-bottom: 10px;
					}
				</style>
				<?php
			}
		}


		/**
		 * Override message threads per page to 20.
		 *
		 * @param array $args Arguments.
		 *
		 * @return array
		 */
		public function has_message_threads_parse_args( $args ) {
			$args['per_page'] = 20;
			return $args;
		}

		/**
		 * Remove the user profile link when theme settings has been disabled.
		 *
		 * @since 2.1.6
		 *
		 * @param array $comment_args Comments query arguments.
		 *
		 * @return mixed
		 */
		public function buddyboss_theme_template_query_arguments( $comment_args ) {
			$platform_author_link = buddyboss_theme_get_option( 'blog_platform_author_link' );
			if ( function_exists( 'bp_core_filter_comments' ) && ! $platform_author_link ) {
				remove_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );
			}

			return $comment_args;
		}

		/**
		 * Remove the user profile link when theme settings has been disabled.
		 *
		 * @since 2.1.6
		 *
		 * @param string $theme_template The path to the theme template file.
		 *
		 * @return mixed
		 */
		public function buddyboss_theme_comments_template( $theme_template ) {
			$platform_author_link = buddyboss_theme_get_option( 'blog_platform_author_link' );
			if ( function_exists( 'bp_core_filter_comments' ) && ! $platform_author_link ) {
				add_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );
			}

			return $theme_template;
		}

		/**
		 * Adding the subnavigation classes to change the UI for the responsive.
		 *
		 * @since 2.2.5
		 *
		 * @param string $class Classed.
		 *
		 * @return mixed|string
		 */
		public function buddyboss_theme_single_item_subnav_classes( $class ) {
			if (
				( function_exists( 'bp_is_settings_component' ) && bp_is_settings_component() ) ||
				( function_exists( 'bp_is_user_profile' ) && bp_is_user_profile() )
			) {
				$class .= ' bb-subnav-private';
			}

			return $class;
		}
	}
}
