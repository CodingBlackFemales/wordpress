<?php
/**
 * BuddyPanel Section class.
 *
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPanel_Section Class
 *
 * This class handles to add sections into the BuddyPanel menus in BuddyBoss Theme.
 */
class BuddyPanel_Section {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @return object
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'load-nav-menus.php', array( $this, 'load_nav_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_nav_menu_item' ), 99, 1 );
	}

	/******************** HOOKS ********************/

	/**
	 * Add custom code when load the nav menus into backend.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function load_nav_menus() {
		add_meta_box(
			'add-buddypanel-sections-nav-menu',
			esc_html__( 'BuddyPanel Sections', 'buddyboss-theme' ),
			array( $this, 'bb_admin_do_wp_nav_menu_meta_box_buddypanel_sections' ),
			'nav-menus',
			'side'
		);
	}

	/**
	 * Load scripts into the admin.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function load_scripts() {
		$minified_js = buddyboss_theme_get_option( 'boss_minified_js' );
		$minjs       = $minified_js ? '.min' : '';

		if ( 'nav-menus' === get_current_screen()->id ) {
			wp_register_script( 'bb-theme-buddypanel-sections', get_template_directory_uri() . '/assets/js/buddypanel-sections' . $minjs . '.js', array( 'jquery' ), buddyboss_theme()->version(), true );
			wp_enqueue_script( 'bb-theme-buddypanel-sections' );
		}
	}

	/******************** FILTERS ********************/

	/**
	 * Fires immediately after a new navigation menu item has been added.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $menu_item Nav menu object.
	 *
	 * @return mixed
	 */
	public function setup_nav_menu_item( $menu_item ) {

		if ( isset( $menu_item->post_content ) && 'bb-theme-section' === $menu_item->post_content ) {
			$menu_item->object     = 'section';
			$menu_item->type_label = esc_html__( 'Section', 'buddyboss-theme' );
		}

		return $menu_item;
	}

	/******************** FUNCTIONS ********************/

	/**
	 * Build and populate the BuddyPanel Sections accordion on Appearance > Menus.
	 *
	 * @since 2.0.0
	 *
	 * @global $nav_menu_selected_id , $menu_locations
	 */
	public static function bb_admin_do_wp_nav_menu_meta_box_buddypanel_sections() {
		global $nav_menu_selected_id;
		$theme_locations = get_nav_menu_locations();

		if ( empty( $nav_menu_selected_id ) || empty( $theme_locations ) ) {
			return;
		}

		$ele_class = in_array( $nav_menu_selected_id, array_values( $theme_locations ), true ) ? 'style="display: none;"' : '';
		$menu_ids  = array( 'buddypanel-loggedin', 'buddypanel-loggedout', 'mobile-menu-logged-in', 'mobile-menu-logged-out' );
		foreach ( $theme_locations as $key => $value ) {
			if ( $value === $nav_menu_selected_id && ! in_array( $key, $menu_ids, true ) ) {
				$ele_class = '';
				break;
			}
		}
		?>

		<div id="buddypanel-menu" class="posttypediv">
			<p><?php esc_attr_e( 'You can visually group menu items in the BuddyPanel menu by indenting them within Sections.', 'buddyboss-theme' ); ?></p>

			<p class="button-controls" <?php echo empty( $ele_class ) ? 'style="display: none;"' : ''; ?>>
				<span class="add-to-menu add-buddypanel-sections">
					<input type="submit" class="button-secondary right" value="<?php esc_html_e( 'Add Section', 'buddyboss-theme' ); ?>" name="add-buddypanel-sections-menu-item" id="submit-buddypanel-section-menu"/>
					<span class="spinner"></span>
				</span>
			</p>
			<p class="warning" <?php echo wp_kses_post( $ele_class ); ?>>
				<?php
				printf(
					/* translators: BuddyPanel menu location text. */
					wp_kses_post( __( 'Link this menu to either %s only to add sections.', 'buddyboss-theme' ) ),
					'<strong>' . esc_html__( 'BuddyPanel or Mobile menu location', 'buddyboss-theme' ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

}

if ( class_exists( 'BuddyPanel_Section' ) ) {
	BuddyPanel_Section::instance();
}
