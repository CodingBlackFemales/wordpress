<?php
/**
 * BuddyBoss Profile MemberpressLMS.
 *
 * @package BuddyBoss\MemberpressLMS
 *
 * @since 2.6.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_MeprLMS_Profile
 */
class BB_MeprLMS_Profile {

	/**
	 * Singleton instance.
	 *
	 * @since 2.6.30
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Profile course subtab.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $profile_course_subtab;

	/**
	 * Courses label name.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $courses_label = '';

	/**
	 * My accessible courses label name.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $my_accessible_courses_label = '';

	/**
	 * My created courses label name.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $my_created_courses_label = '';

	/**
	 * Courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $courses_slug = '';

	/**
	 * Accessible courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $accessible_courses_slug = '';

	/**
	 * Created courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $created_courses_slug = '';

	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Get the Singleton instance.
	 *
	 * @return object BB_MeprLMS_Profile instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup actions.
	 *
	 * @since 2.6.30
	 */
	public function setup_actions() {
		if ( bb_meprlms_enable() && class_exists( 'memberpress\courses\helpers\Courses' ) ) {
			$this->courses_label               = esc_html__( 'Courses', 'buddyboss-pro' );
			$this->my_accessible_courses_label = esc_html__( 'My Courses', 'buddyboss-pro' );
			$this->my_created_courses_label    = esc_html__( 'My Created Courses', 'buddyboss-pro' );
			$this->courses_slug                = bb_meprlms_profile_courses_slug();
			$this->accessible_courses_slug     = bb_meprlms_profile_user_courses_slug();
			$this->created_courses_slug        = bb_meprlms_profile_instructor_courses_slug();

			add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
			add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), 75 );
			add_action( 'buddyboss_theme_after_bb_groups_menu', array( $this, 'setup_user_profile_bar' ), 10 );
		}
	}

	/**
	 * Add Menu and Sub menu navigation link for profile menu.
	 *
	 * @since 2.6.30
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
	 * @since 2.6.30
	 */
	public function setup_nav() {
		$displayed_user_id = bp_displayed_user_id();
		if ( ! bp_displayed_user_id() ) {
			return;
		}

		$courses_label               = $this->courses_label;
		$my_created_courses_label    = $this->my_created_courses_label;
		$my_accessible_courses_label = $this->my_accessible_courses_label;
		$created_courses_label       = esc_html__( 'Created Courses', 'buddyboss-pro' );
		$accessible_courses_label    = esc_html__( 'User Courses', 'buddyboss-pro' );
		$courses_slug                = $this->courses_slug;
		$created_courses_slug        = $this->created_courses_slug;
		$accessible_courses_slug     = $this->accessible_courses_slug;
		$loggedin_user_id            = bp_loggedin_user_id();
		$user_same                   = empty( $displayed_user_id ) || $displayed_user_id === $loggedin_user_id;
		$nav_name                    = $courses_label;

		bp_core_new_nav_item(
			array(
				'name'                => $nav_name,
				'slug'                => $courses_slug,
				'screen_function'     => array( $this, 'user_course_page' ),
				'position'            => 75,
				'default_subnav_slug' => $accessible_courses_slug,
			)
		);

		$all_subnav_items = array(
			array(
				'name'            => empty( $user_same ) ? $accessible_courses_label : $my_accessible_courses_label,
				'slug'            => $accessible_courses_slug,
				'parent_url'      => $this->get_nav_link( $courses_slug ),
				'parent_slug'     => $courses_slug,
				'screen_function' => array( $this, 'user_course_page' ),
				'position'        => 75,
			),
		);

		if ( user_can( bp_loggedin_user_id(), 'administrator' ) ) {
			$all_subnav_items[] = array(
				'name'            => empty( $user_same ) ? $created_courses_label : $my_created_courses_label,
				'slug'            => $created_courses_slug,
				'parent_url'      => $this->get_nav_link( $courses_slug ),
				'parent_slug'     => $courses_slug,
				'screen_function' => array( $this, 'instructor_course_page' ),
			);
		}

		// Do not display this menu in member detail rest endpoint.
		foreach ( $all_subnav_items as $all_subnav_item ) {
			bp_core_new_subnav_item( $all_subnav_item );
		}
	}

	/**
	 * Display User Course Page Content in Profile course menu.
	 *
	 * @since 2.6.30
	 */
	public function user_course_page() {
		$this->profile_course_subtab = 'user-courses';
		add_action( 'bp_template_content', array( $this, 'user_course_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Certificates Page Content.
	 *
	 * @since 2.6.30
	 */
	public function user_course_page_content() {
		do_action( 'template_notices' );
		do_action( 'bb_meprlms_before_user_course_page_content' );
		bp_get_template_part( 'members/single/courses' );
	}

	/**
	 * Display Course Page Content in Profile course menu.
	 *
	 * @since 2.6.30
	 */
	public function instructor_course_page() {
		$this->profile_course_subtab = 'instructor-courses';
		add_action( 'bp_template_content', array( $this, 'instructor_courses_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Instructor Courses in My Course Profile Page.
	 *
	 * @since 2.6.30
	 */
	public function instructor_courses_page_content() {
		do_action( 'template_notices' );
		do_action( 'bb_meprlms_before_instructor_courses_page_content' );
		bp_get_template_part( 'members/single/courses' );
	}

	/**
	 * Add Menu in Profile section.
	 *
	 * @since 2.6.30
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
					<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->accessible_courses_slug ); ?>">
						<a class="ab-item" href="<?php echo esc_url( $this->adminbar_nav_link( $this->courses_slug ) ); ?>"><?php echo esc_attr( $this->my_accessible_courses_label ); ?></a>
					</li>
					<?php
					if ( user_can( bp_loggedin_user_id(), 'administrator' ) ) {
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
	 * @since 2.6.30
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
				'name'     => $this->my_accessible_courses_label,
				'slug'     => $this->accessible_courses_slug,
				'parent'   => $this->courses_slug,
				'nav_link' => $this->adminbar_nav_link( $this->courses_slug ),
				'position' => 1,
			),
		);

		if ( user_can( bp_loggedin_user_id(), 'administrator' ) ) {
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
	 * @since 2.6.30
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

add_action(
	'bp_init',
	function () {
		// Create an instance of the Singleton.
		BB_MeprLMS_Profile::get_instance();
	},
	5
);
