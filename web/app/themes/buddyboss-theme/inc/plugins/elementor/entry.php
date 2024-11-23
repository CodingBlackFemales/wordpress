<?php

namespace BBElementor;

use BBElementor\Widgets\Header_Bar;

// use BBElementor\Widgets\Ld_Activity;
// use BBElementor\Widgets\Ld_Courses;
use BBElementor\Widgets\BBP_Members;
use BBElementor\Widgets\BBP_Activity;
use BBElementor\Widgets\BBP_Forums;
use BBElementor\Widgets\BBP_Forums_Activity;
use BBElementor\Widgets\BBP_Profile_Completion;
use BBElementor\Widgets\BBP_Dashboard_Intro;
use BBElementor\Widgets\BBP_Dashboard_Grid;
use BBElementor\Widgets\BB_Tabs;
use BBElementor\Widgets\BB_Review;
use BBElementor\Widgets\BB_Gallery;
use BBElementor\Widgets\BB_Lms_Courses;
use BBElementor\Widgets\BB_Lms_Activity;
use BBElementor\Widgets\BB_Groups;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Main BB Elementor Widgets Class
 *
 * Register new elementor widget.
 *
 * @since 1.0.0
 */
class BB_Elementor_Widgets {

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * BB Categories
	 *
	 * @param object $elements_manager Elementor Object.
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function bb_elementor_widget_categories( $elements_manager ) {

		$elements_manager->add_category(
			'buddyboss-elements',
			array(
				'title' => __( 'BuddyBoss', 'buddyboss-theme' ),
				'icon'  => 'eicon-parallax',
			)
		);

	}

	/**
	 * Add Actions
	 *
	 * @since  1.0.0
	 *
	 * @access private
	 */
	private function add_actions() {
		$minified_js = buddyboss_theme_get_option( 'boss_minified_js' );
		$minjs       = $minified_js ? '.min' : '';

		add_action( 'elementor/elements/categories_registered', array( $this, 'bb_elementor_widget_categories' ) );

		add_action( 'elementor/widgets/register', array( $this, 'bb_elementor_widgets_registered' ) );

		add_action(
			'elementor/frontend/after_register_scripts',
			function() use ( $minjs ) {
				wp_register_script( 'elementor-bb-frontend', get_template_directory_uri() . '/inc/plugins/elementor/assets/js/frontend' . $minjs . '.js', array( 'jquery' ), '1.6.8', true );
			}
		);

		add_action(
			'elementor/editor/after_enqueue_scripts',
			function() use ( $minjs ) {
				wp_enqueue_script( 'elementor-bb-editor', get_template_directory_uri() . '/inc/plugins/elementor/assets/js/editor' . $minjs . '.js', array( 'jquery' ), '1.6.8', true );
			}
		);

		add_action( 'elementor/element/after_add_attributes', array( $this, 'bb_elementor_widgets_add_custom_class' ) );
	}

	public function bb_elementor_widgets_add_custom_class( $data ) {
		$classes  = array();
		$controls = $data->get_controls();
		if ( ! empty( $controls ) && ! empty( $controls['wp']['id_base'] ) ) {

			$min = '';
			if ( function_exists( 'bp_core_get_minified_asset_suffix' ) ) {
				$min = bp_core_get_minified_asset_suffix();
			}

			if ( 'bp_xprofile_profile_completion_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget_bp_profile_completion_widget',
					'widget',
				);
			}
			if ( 'bp_core_members_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_core_members_widget',
				);
			}
			if ( 'bp_groups_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_groups_widget',
				);
				// enqueue script if it is not yet enqueued
				if ( ! wp_script_is( 'groups_widget_groups_list-js', 'enqueued' ) ) {
					wp_enqueue_script( 'groups_widget_groups_list-js', buddypress()->plugin_url . "bp-groups/js/widget-groups{$min}.js", array( 'jquery' ), bp_get_version() );
				}
			}
			if ( 'bbp_login_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'bbp_widget_login',
				);
			}
			if ( 'bp_core_whos_online_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_core_whos_online_widget',
				);
			}
			if ( 'bp_core_friends_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_core_friends_widget',
				);
				// enqueue script if it is not yet enqueued
				if ( ! wp_script_is( 'bp_core_widget_friends-js', 'enqueued' ) ) {
					wp_enqueue_script( 'bp_core_widget_friends-js', buddypress()->plugin_url . "bp-friends/js/widget-friends{$min}.js", array( 'jquery' ), bp_get_version() );
				}
			}
			if ( 'bp_core_follow_following_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_follow_following_widget',
				);
			}
			if ( 'bp_core_follow_follower_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_bp_follow_follower_widget',
				);
			}
			if ( 'bbp_search_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_display_search',
				);
			}
			if ( 'lduserstatus' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_lduserstatus ',
				);
			}
			if ( 'ldcourseinfo' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_ldcourseinfo',
				);
			}
			if ( 'boss-recent-posts' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'bb_widget_recent_posts',
				);
			}
			if ( 'bp_latest_activities' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'bp-latest-activities',
				);
			}
			if ( 'recent-posts' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'bp-latest-activities',
				);
			}
			if ( 'bbp_views_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_display_views',
				);
			}
			if ( 'bbp_forums_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_display_forums',
				);
			}
			if ( 'bbp_topics_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_display_topics',
				);
			}
			if ( 'bbp_replies_widget' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_display_replies',
				);
			}
			if ( 'recent-comments' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
					'widget_recent_comments',
				);
			}
			if ( 'bbp_stats_widget' === $controls['wp']['id_base'] || 'bp_core_recently_active_widget' === $controls['wp']['id_base'] || 'boss-follow-us' === $controls['wp']['id_base'] || 'widget_recent_jobs' === $controls['wp']['id_base'] ) {
				$classes = array(
					'widget',
				);
			}
		}
		if ( ! empty( $classes ) ) {
			$data->add_render_attribute( '_wrapper', 'class', $classes );
		}
	}

	/**
	 * BB Widgets Registered
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function bb_elementor_widgets_registered() {
		$this->includes();
		$this->register_widget();
	}

	/**
	 * Includes
	 *
	 * @since  1.0.0
	 *
	 * @access private
	 */
	private function includes() {
		require __DIR__ . '/widgets/header-bar/bb-header-bar.php';
		require __DIR__ . '/widgets/bb-dashboard-grid.php';
		require __DIR__ . '/widgets/bb-tabs.php';
		require __DIR__ . '/widgets/bb-review.php';
		require __DIR__ . '/widgets/gallery/bb-gallery.php';
		if ( function_exists( 'bp_is_active' ) ) {
			require __DIR__ . '/widgets/members/bb-members.php';
			require __DIR__ . '/widgets/bb-profile-completion.php';
			require __DIR__ . '/widgets/bb-dashboard-intro.php';
		}
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			require __DIR__ . '/widgets/bb-activity.php';
		}
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) ) {
			require __DIR__ . '/widgets/bb-forums.php';
			require __DIR__ . '/widgets/bb-forums-activity.php';
		}

		if ( class_exists( 'LifterLMS' ) || class_exists( 'SFWD_LMS' ) ) {
			require __DIR__ . '/widgets/courses/bb-lms-courses.php';
			require __DIR__ . '/widgets/courses/bb-lms-activity.php';
		}

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
			require __DIR__ . '/widgets/groups/bb-groups.php';
		}
	}

	/**
	 * Register Widget
	 *
	 * @since  1.0.0
	 *
	 * @access private
	 */
	private function register_widget() {
		\Elementor\Plugin::instance()->widgets_manager->register( new Header_Bar() );
		\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Dashboard_Grid() );
		\Elementor\Plugin::instance()->widgets_manager->register( new BB_Tabs() );
		\Elementor\Plugin::instance()->widgets_manager->register( new BB_Review() );
		\Elementor\Plugin::instance()->widgets_manager->register( new BB_Gallery() );
		if ( function_exists( 'bp_is_active' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Members() );
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Profile_Completion() );
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Dashboard_Intro() );
		}
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Activity() );
		}
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Forums() );
			\Elementor\Plugin::instance()->widgets_manager->register( new BBP_Forums_Activity() );
		}

		if ( class_exists( 'LifterLMS' ) || class_exists( 'SFWD_LMS' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register( new BB_Lms_Courses() );
			\Elementor\Plugin::instance()->widgets_manager->register( new BB_Lms_Activity() );
		}

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register( new BB_Groups() );
		}
	}
}

new BB_Elementor_Widgets();
