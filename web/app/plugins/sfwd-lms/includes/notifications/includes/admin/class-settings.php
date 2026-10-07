<?php
/**
 * Settings class.
 *
 * @package LearnDash\Notifications
 */

/**
 * Don't include this file directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * LearnDash_Notifications_Settings class
 *
 * This class is responsible for managing plugin settings.
 *
 * @since 5.2.0
 */
class LearnDash_Notifications_Settings {
	/**
	 * Plugin options
	 *
	 * @since 5.2.0
	 * @var array
	 */
	protected $options;

	/**
	 * Class __construct function
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		$this->options = get_option( 'learndash_notifications_settings', [] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 100 );
		add_filter( 'learndash_submenu', [ $this, 'submenu_item' ] );
		add_action( 'learndash_admin_tabs_set', [ $this, 'admin_tabs_set' ], 10, 2 );
	}

	/**
	 * Add zapier submenu in Learndash menu
	 *
	 * @param array $submenu Existing submenu
	 *
	 * @return array           New submenu
	 */
	public function submenu_item( $submenu ) {
		$menu = [
			'ld-notifications' => [
				'name' => __( 'Notifications', 'learndash' ),
				'cap'  => 'manage_options', // @TODO Need to confirm this capability on the menu.
				'link' => 'edit.php?post_type=ld-notification',
			],
		];

		array_splice( $submenu, 9, 0, $menu );

		return $submenu;
	}

	/**
	 * Add tabs to achievement pages
	 *
	 * @param string $current_screen_parent_file Current screen parent
	 * @param object $tabs Learndash_Admin_Menus_Tabs object
	 */
	public function admin_tabs_set( $current_screen_parent_file, $tabs ) {
		$screen = get_current_screen();

		if ( $current_screen_parent_file == 'learndash-lms' && $screen->post_type == 'ld-notification' ) {
			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'post-new.php?post_type=ld-notification',
					'name' => __( 'Add New', 'learndash' ),
					'id'   => 'ld-notification',
				],
				1
			);

			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'edit.php?post_type=ld-notification',
					'name' => __( 'Notifications', 'learndash' ),
					'id'   => 'edit-ld-notification',
				],
				2
			);

			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'admin.php?page=ld-notifications-status',
					'name' => __( 'Status', 'learndash' ),
					'id'   => 'ld-notifications-settings',
				],
				3
			);
			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'admin.php?page=learndash_lms_advanced&section-advanced=learndash_logs',
					'name' => __( 'Logs', 'learndash' ),
					'id'   => 'ld-notifications-settings',
				],
				5
			);
		} elseif ( $current_screen_parent_file == 'edit.php?post_type=ld-notification' ) {
			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'post-new.php?post_type=ld-notification',
					'name' => __( 'Add New', 'learndash' ),
					'id'   => 'ld-notification',
				],
				1
			);

			$tabs->add_admin_tab_item(
				$current_screen_parent_file,
				[
					'link' => 'edit.php?post_type=ld-notification',
					'name' => __( 'Notifications', 'learndash' ),
					'id'   => 'edit-ld-notification',
				],
				2
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_scripts() {
		global $post_type;

		if ( ! is_admin() || 'ld-notification' != $post_type ) {
			return;
		}

		wp_register_style( 'learndash_notifications_jquery_ui_base_theme', 'https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css', [], '1.13.1', 'all' );

		wp_enqueue_script( 'learndash_notifications_admin_scripts', LEARNDASH_NOTIFICATIONS_PLUGIN_URL . 'assets/js/admin-scripts.js', [ 'jquery', 'learndash-select2-jquery-script', 'jquery-ui-accordion' ], LEARNDASH_SCRIPT_VERSION_TOKEN, false );

		wp_enqueue_style( 'learndash_notifications_admin_styles', LEARNDASH_NOTIFICATIONS_PLUGIN_URL . 'assets/css/admin-styles.css', [ 'learndash_notifications_jquery_ui_base_theme' ], LEARNDASH_SCRIPT_VERSION_TOKEN );

		wp_enqueue_style( 'learndash_style', plugins_url() . '/sfwd-lms/assets/css/style.css' );

		wp_enqueue_style( 'sfwd-module-style', plugins_url() . '/sfwd-lms/assets/css/sfwd_module.css' );

		wp_enqueue_script( 'sfwd-module-script', plugins_url() . '/sfwd-lms/assets/js/sfwd_module.js', [ 'jquery' ] );

		wp_localize_script(
			'learndash_notifications_admin_scripts',
			'LearnDash_Notifications_Vars',
			[
				'ajaxurl'                          => admin_url( 'admin-ajax.php' ),
				'nonce'                            => wp_create_nonce( 'ld_notifications_nonce' ),
				// translators: %s: Group label.
				'select_group'                     => sprintf( _x( '-- Select %s --', 'Group label', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
				// translators: %s: Course label.
				'select_course'                    => sprintf( _x( '-- Select %s --', 'Course label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
				// translators: %s: Lesson label.
				'select_lesson'                    => sprintf( _x( '-- Select %s --', 'Lesson label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
				// translators: %s: Topic label.
				'select_topic'                     => sprintf( _x( '-- Select %s --', 'Topic label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
				// translators: %s: Quiz label.
				'select_quiz'                      => sprintf( _x( '-- Select %s --', 'Quiz label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
				// translators: %s: Course label.
				'select_course_first'              => sprintf( _x( '-- Select %s First --', 'Course label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
				// translators: %s: Lesson label.
				'select_lesson_first'              => sprintf( _x( '-- Select %s First --', 'Lesson label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
				// translators: %s: Topic label.
				'select_topic_first'               => sprintf( _x( '-- Select %s First --', 'Topic label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
				// translators: %s: Quiz label.
				'select_quiz_first'                => sprintf( _x( '-- Select %s First --', 'Quiz label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
				'select_course_lesson_topic_first' => sprintf(
					// translators: %1$s: Course label, %2$s: Lesson label, %3$s: Topic label.
					_x( '-- Select %1$s, %2$s, or %3$s First --', 'Course, lesson, or topic label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' ),
					LearnDash_Custom_Label::get_label( 'lesson' ),
					LearnDash_Custom_Label::get_label( 'topic' )
				),
				// translators: %s: Lesson label.
				'all_lessons'                      => sprintf( _x( 'Any %s', 'Lesson label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
				// translators: %s: Topic label.
				'all_topics'                       => sprintf( _x( 'Any %s', 'Topic label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
				// translators: %s: Quiz label.
				'all_quizzes'                      => sprintf( _x( 'Any %s', 'Quiz label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
				'templates'                        => [
					'condition_field' => learndash_notifications_get_condition_field(),
				],
			]
		);
		wp_localize_script( 'sfwd-module-script', 'sfwd_data', [] );

		wp_dequeue_script( 'autosave' );
	}
}

new LearnDash_Notifications_Settings();
