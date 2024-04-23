<?php
/**
 * BuddyBoss Profile TutorLMS.
 *
 * @package BuddyBoss\TutorLMS
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_TutorLMS_Profile
 */
class BB_TutorLMS_Profile {

	/**
	 * Singleton instance.
	 *
	 * @since 2.4.40
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Profile course subtab.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $profile_course_subtab;

	/**
	 * Courses label name.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $courses_label = '';

	/**
	 * My enrolled courses label name.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $my_enrolled_courses_label = '';

	/**
	 * My created courses label name.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $my_created_courses_label = '';

	/**
	 * Courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $courses_slug = '';

	/**
	 * Enrolled courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $enrolled_courses_slug = '';

	/**
	 * Created courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @var string
	 */
	public $created_courses_slug = '';
	
	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 2.4.40
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Get the Singleton instance.
	 *
	 * @return object BB_TutorLMS_Profile instance.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup actions.
	 *
	 * @since 2.4.40
	 */
	public function setup_actions() {
		if ( bb_tutorlms_enable() && function_exists( 'tutor_utils' ) ) {
			add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
			add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), 75 );
			add_action( 'buddyboss_theme_after_bb_groups_menu', array( $this, 'setup_user_profile_bar' ), 10 );
		}
	}

	/**
	 * Add Menu and Sub menu navigation link for profile menu.
	 *
	 * @since 2.4.40
	 *
	 * @param string $slug        Slug of the nav link.
	 * @param string $parent_slug Parent item slug.
	 *
	 * @return string
	 */
	public function get_nav_link( $slug, $parent_slug = '' ) {
		$displayed_user_id = bp_displayed_user_id();
		$user_domain       = ( ! empty( $displayed_user_id ) ) ? bp_displayed_user_domain() : bp_loggedin_user_domain();
		if ( ! empty( $parent_slug ) ) {
			$nav_link = trailingslashit( $user_domain . $parent_slug . '/' . $slug );
		} else {
			$nav_link = trailingslashit( $user_domain . $slug );
		}

		return $nav_link;
	}

	/**
	 * Setup navigation for buddyboss profile.
	 *
	 * @since 2.4.40
	 */
	public function setup_nav() {
		$courses_label             = esc_html__( 'Courses', 'buddyboss-pro' );
		$my_created_courses_label  = esc_html__( 'My Created Courses', 'buddyboss-pro' );
		$created_courses_label     = esc_html__( 'Created Courses', 'buddyboss-pro' );
		$my_enrolled_courses_label = esc_html__( 'My Enrolled Courses', 'buddyboss-pro' );
		$enrolled_courses_label    = esc_html__( 'Enrolled Courses', 'buddyboss-pro' );
		$courses_slug              = bb_tutorlms_profile_courses_slug();
		$created_courses_slug      = bb_tutorlms_profile_instructor_courses_slug();
		$enrolled_courses_slug     = bb_tutorlms_profile_enrolled_courses_slug();
		$displayed_user_id         = bp_displayed_user_id();
		$loggedin_user_id          = bp_loggedin_user_id();
		$user_same                 = empty( $displayed_user_id ) || $displayed_user_id === $loggedin_user_id;
		$enrolled_courses          = bb_tutorlms_get_enrolled_courses( $displayed_user_id );
		$user_courses_count        = $enrolled_courses ? $enrolled_courses->post_count : 0;

		$this->courses_label             = $courses_label;
		$this->my_enrolled_courses_label = $my_enrolled_courses_label;
		$this->my_created_courses_label  = $my_created_courses_label;
		$this->courses_slug              = $courses_slug;
		$this->enrolled_courses_slug     = $enrolled_courses_slug;
		$this->created_courses_slug      = $created_courses_slug;

		// Only grab count if we're on a user page.
		if ( bp_is_user() ) {
			$class = ( 0 === $user_courses_count ) ? 'no-count' : 'count';

			$nav_name = sprintf(
				/* translators: %s: Courses count for the current user */
				__( '%1$s %2$s', 'buddyboss-pro' ),
				$courses_label,
				sprintf(
					'<span class="%s">%s</span>',
					esc_attr( $class ),
					$user_courses_count
				)
			);
		} else {
			$nav_name = $courses_label;
		}

		bp_core_new_nav_item(
			array(
				'name'                    => $nav_name,
				'slug'                    => $courses_slug,
				'screen_function'         => array( $this, 'enrolled_course_page' ),
				'position'                => 75,
				'default_subnav_slug'     => $enrolled_courses_slug,
			)
		);

		$all_subnav_items = array(
			array(
				'name'            => empty( $user_same ) ? $enrolled_courses_label : $my_enrolled_courses_label,
				'slug'            => $enrolled_courses_slug,
				'parent_url'      => $this->get_nav_link( $courses_slug ),
				'parent_slug'     => $courses_slug,
				'screen_function' => array( $this, 'enrolled_course_page' ),
				'position'        => 75,
			),
		);

		if ( tutor_utils()->is_instructor( $displayed_user_id, true ) ) {
			$all_subnav_items[] = array(
				'name'            => empty( $user_same ) ? $created_courses_label : $my_created_courses_label,
				'slug'            => $created_courses_slug,
				'parent_url'      => $this->get_nav_link( $courses_slug ),
				'parent_slug'     => $courses_slug,
				'screen_function' => array( $this, 'instructor_course_page' ),
			);
		}

		foreach ( $all_subnav_items as $all_subnav_item ) {
			bp_core_new_subnav_item( $all_subnav_item );
		}
	}

	/**
	 * Display Enrolled Course Page Content in Profile course menu.
	 *
	 * @since 2.4.40
	 */
	public function enrolled_course_page() {
		$this->profile_course_subtab = 'enrolled-courses';
		add_action( 'bp_template_content', array( $this, 'enrolled_course_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Certificates Page Content.
	 *
	 * @since 2.4.40
	 */
	public function enrolled_course_page_content() {
		do_action( 'template_notices' );
		do_action( 'bb_tutorlms_before_enrolled_course_page_content' );
		bp_get_template_part( 'members/single/tutor/courses' );
	}

	/**
	 * Display Course Page Content in Profile course menu.
	 *
	 * @since 2.4.40
	 */
	public function instructor_course_page() {
		$this->profile_course_subtab = 'instructor-courses';
		add_action( 'bp_template_content', array( $this, 'instructor_courses_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Instructor Courses in My Course Profile Page.
	 *
	 * @since 2.4.40
	 */
	public function instructor_courses_page_content() {
		do_action( 'template_notices' );
		do_action( 'bb_tutorlms_before_instructor_courses_page_content' );
		bp_get_template_part( 'members/single/tutor/courses' );
	}

	/**
	 * Add Menu in Profile section.
	 *
	 * @since 2.4.40
	 */
	public function setup_user_profile_bar() {
		?>
		<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->courses_slug ); ?>" class="menupop">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $this->adminbar_nav_link( $this->courses_slug ) ); ?>">
				<i class="bb-icon-l bb-icon-course"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo esc_attr( $this->courses_label ); ?>
			</a>

			<div class="ab-sub-wrapper">
				<ul id="wp-admin-bar-my-account-courses-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->enrolled_courses_slug ); ?>">
						<a class="ab-item" href="<?php echo esc_url( $this->adminbar_nav_link( $this->courses_slug ) ); ?>"><?php echo esc_attr( $this->my_enrolled_courses_label ); ?></a>
					</li>
					<?php
						if ( tutor_utils()->is_instructor( bp_displayed_user_id(), true ) ) {
							?>
							<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->created_courses_slug ); ?>">
								<a class="ab-item" href="<?php echo esc_url( $this->adminbar_nav_link( $this->created_courses_slug, $this->courses_slug ) ); ?>"><?php echo esc_attr( $this->my_created_courses_label ); ?></a>
							</li>
							<?php
						}
					?>
				</ul>
			</div>
		</li>
		<?php
	}

	/**
	 * Add Courses tab in admin menu.
	 *
	 * @since 2.4.40
	 */
	public function setup_admin_bar() {
		$all_menu = array(
			array(
				'name'     => $this->courses_label,
				'slug'     => $this->courses_slug,
				'parent'   => 'buddypress',
				'nav_link' => $this->adminbar_nav_link( $this->courses_slug ),
				'position' => 1,
			),
			array(
				'name'     => $this->my_enrolled_courses_label,
				'slug'     => $this->enrolled_courses_slug,
				'parent'   => $this->courses_slug,
				'nav_link' => $this->adminbar_nav_link( $this->courses_slug ),
				'position' => 1,
			),
		);

		if ( tutor_utils()->is_instructor( bp_displayed_user_id(), true ) ) {
			$all_menu[] = array(
				'name'     => $this->my_created_courses_label,
				'slug'     => $this->created_courses_slug,
				'parent'   => $this->courses_slug,
				'nav_link' => $this->adminbar_nav_link( $this->created_courses_slug, $this->courses_slug ),
				'position' => 1,
			);
		}

		global $wp_admin_bar;
		foreach ( $all_menu as $single ) {
			$wp_admin_bar->add_menu(
				array(
					'parent'   => 'my-account-' . $single['parent'],
					'id'       => 'my-account-' . $single['slug'],
					'title'    => $single['name'],
					'href'     => $single['nav_link'],
					'position' => $single['position'],
				)
			);
		}
	}

	/**
	 * Add Menu and Sub menu navigation link for admin menu.
	 *
	 * @since 2.4.40
	 *
	 * @param string $slug        Slug of the nav link.
	 * @param string $parent_slug Parent item slug.
	 *
	 * @return string
	 */
	public function adminbar_nav_link( $slug, $parent_slug = '' ) {
		$user_domain = bp_loggedin_user_domain();
		if ( ! empty( $parent_slug ) ) {
			$nav_link = trailingslashit( $user_domain . $parent_slug . '/' . $slug );
		} else {
			$nav_link = trailingslashit( $user_domain . $slug );
		}

		return $nav_link;
	}

}

// Create an instance of the Singleton.
BB_TutorLMS_Profile::get_instance();
