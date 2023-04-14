<?php
/**
 * @var array $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'BB_HEADER_BAR_WIDGET' ) ) {
	exit; // Exit if accessed outside widget.
}
?>

<nav id="site-navigation" class="main-navigation" data-menu-space="120">
    <div id="primary-navbar">
		<?php
		wp_nav_menu(
			array(
				'menu'           => $settings['menu_marker'],
				'theme_location' => 'header-menu',
				'menu_id'        => 'primary-menu',
				'container'      => false,
				'fallback_cb'    => '',
				'menu_class'     => 'primary-menu bb-primary-overflow',
			)
		);
		?>
        <div id="navbar-collapse">
            <a class="more-button" href="#"><i class="bb-icon-f bb-icon-ellipsis-h"></i></a>
            <ul id="navbar-extend" class="sub-menu"></ul>
        </div>
    </div>
</nav>