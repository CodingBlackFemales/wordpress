<?php
/**
 * Register the BuddyPanel block.
 *
 * @package buddyboss-theme
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @since 2.0.0
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 *
 * @throws WP_Error When build is not found.
 */
function bb_theme_register_buddypanel_block() {

	$script_asset_path = dirname( __FILE__ ) . '/build/buddypanel.asset.php';
	if ( ! file_exists( $script_asset_path ) ) {
		return new WP_Error(
			'bb_theme_buddypanel_block_error',
			esc_html__( 'You need to run `npm start` or `npm run build` for the "buddyboss-theme/buddypanel" block first.', 'buddyboss-theme' )
		);
	}

	// Include the asset file get dependencies and version.
	$script_asset = require $script_asset_path;

	// Register block editor script for backend.
	wp_register_script(
		'bb_theme_block-buddypanel-block-js',
		get_template_directory_uri() . '/blocks/buddypanel/build/buddypanel.js',
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	// Register block styles for both frontend + backend.
	wp_register_style(
		'bb_theme_block-buddypanel-style-css',
		get_template_directory_uri() . '/blocks/buddypanel/build/style-buddypanel.css',
		is_admin() ? array( 'wp-editor' ) : null,
		$script_asset['version']
	);

	register_block_type(
		__DIR__,
		array(
			'render_callback' => 'bb_theme_render_buddypanel_block',
		)
	);
}
add_action( 'init', 'bb_theme_register_buddypanel_block' );

/**
 * Render the buddypanel block content.
 *
 * @since 2.0.0
 */
function bb_theme_render_buddypanel_block() {

	ob_start();
	wp_nav_menu(
		array(
			'theme_location' => is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout',
			'menu_id'        => 'buddypanel-menu',
			'container'      => false,
			'fallback_cb'    => '',
			'walker'         => new BuddyBoss_BuddyPanel_Menu_Walker(),
			'menu_class'     => 'buddypanel-menu side-panel-menu buddypanel-menu-block',
		)
	);

	$buddypanel_menu = ob_get_clean();

	if ( str_contains( $buddypanel_menu, 'bb-menu-section' ) ) {
		$buddypanel_menu = str_replace( 'buddypanel-menu side-panel-menu buddypanel-menu-block', 'buddypanel-menu side-panel-menu buddypanel-menu-block has-section-menu', $buddypanel_menu );
	}

	if ( empty( $buddypanel_menu ) && current_user_can( 'administrator' ) ) {
		$buddypanel_menu = sprintf(
		/* translators: 1. Start 'a' tag. 2. Close 'a' tag. */
			__( '%1$sCreate a menu%2$s and assign it to the BuddyPanel location to show it in this block.', 'buddyboss-theme' ),
			/* translators: 1. Admin nav menu URL.  */
			sprintf( '<a href="%s">', esc_url( admin_url( 'nav-menus.php' ) ) ),
			'</a>'
		);
	}

	ob_start();

	if ( ! empty( $buddypanel_menu ) ) {
		?>
		<div class="side-panel-inner is_block">
			<div class="side-panel-menu-container">
				<?php echo wp_kses_post( $buddypanel_menu ); ?>
			</div>
		</div>
		<?php
	}

	return ob_get_clean();
}
