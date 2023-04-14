<?php
$header = (int) buddyboss_theme_get_option( 'buddyboss_header' );
if ( 4 === $header ) {
	?>
	<div class="header-search-wrap header-search-primary">
		<div class="container">
			<?php
				add_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
				get_search_form();
				remove_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
			?>
		</div>
	</div>
	<?php
} else {
	?>
	<nav id="site-navigation" class="main-navigation" data-menu-space="120">
		<div id="primary-navbar">
			<?php
			if ( is_user_logged_in() ) {
				wp_nav_menu(
					array(
						'theme_location' => 'header-menu',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'fallback_cb'    => '',
						'walker'         => new BuddyBoss_SubMenuWrap(),
						'menu_class'     => 'primary-menu bb-primary-overflow',
					)
				);
			} else {
				wp_nav_menu(
					array(
						'theme_location' => 'header-menu-logout',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'fallback_cb'    => '',
						'walker'         => new BuddyBoss_SubMenuWrap(),
						'menu_class'     => 'primary-menu bb-primary-overflow',
					)
				);
			}
			?>
			<div id="navbar-collapse">
				<a class="more-button" href="#"><i class="bb-icon-f bb-icon-ellipsis-h"></i></a>
				<div class="sub-menu">
					<div class="wrapper">
						<ul id="navbar-extend" class="sub-menu-inner"></ul>
					</div>
				</div>
			</div>
		</div>
	</nav>
	<?php
}
