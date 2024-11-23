<aside class="buddypanel <?php echo esc_attr( buddyboss_theme_get_option( 'buddypanel_toggle' ) ? 'buddypanel--toggle-on' : 'buddypanel--toggle-off' ); ?>">
	<?php
	$menu              = is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout';
	$header            = (int) buddyboss_theme_get_option( 'buddyboss_header' );
	$buddypanel_logo   = buddyboss_theme_get_option( 'buddypanel_show_logo' );
	$buddypanel_state  = buddyboss_theme_get_option( 'buddypanel_state' );
	$buddypanel_toggle = buddyboss_theme_get_option( 'buddypanel_toggle' );

	if ( $buddypanel_toggle ) {
		?>
		<header class="panel-head">
			<a href="#" class="bb-toggle-panel"><i class="bb-icon-l bb-icon-sidebar"></i></a>
		</header>
		<?php
	}
	if (
		3 === $header &&
		! buddypanel_is_learndash_inner() &&
		$buddypanel_logo
	) {
		get_template_part( 'template-parts/site-logo' );

	} elseif (
		3 !== $header &&
		$buddypanel_logo
	) {
		get_template_part( 'template-parts/site-logo' );

	} elseif ( 3 === $header && buddypanel_is_learndash_inner() && $buddypanel_logo ) {
		if ( buddyboss_is_learndash_brand_logo() && buddyboss_theme_ld_focus_mode() ) {
			?>
			<div class="site-branding ld-brand-logo">
				<img src="<?php echo esc_url( wp_get_attachment_url( buddyboss_is_learndash_brand_logo() ) ); ?>" alt="<?php echo esc_attr( get_post_meta( buddyboss_is_learndash_brand_logo(), '_wp_attachment_image_alt', true ) ); ?>">
			</div>
			<?php
		} else {
			get_template_part( 'template-parts/site-logo' );
		}
	}
	$site_icon_class = $buddypanel_logo ? ' buddypanel_on_' . $buddypanel_state . '_site_icon' : 'buddypanel_off_' . $buddypanel_state . '_site_icon';
	$site_icon_url   = get_site_icon_url( 38 );
	if ( ! empty( $site_icon_url ) ) {
		?>
		<div class="buddypanel-site-icon <?php echo esc_attr( $site_icon_class ); ?>">
			<a href="<?php echo esc_url( bb_get_theme_header_logo_link() ); ?>" class="buddypanel-site-icon-link">
				<img src="<?php echo esc_url( $site_icon_url ); ?>" class="buddypanel-site-icon-src"/>
			</a>
		</div>
		<?php
	}
	?>
	<div class="side-panel-inner">
		<div class="side-panel-menu-container">
			<?php

			ob_start();
			wp_nav_menu(
				array(
					'theme_location' => $menu,
					'menu_id'        => 'buddypanel-menu',
					'container'      => false,
					'fallback_cb'    => '',
					'walker'         => new BuddyBoss_BuddyPanel_Menu_Walker(),
					'menu_class'     => 'buddypanel-menu side-panel-menu',
				)
			);

			$buddypanel_menu = ob_get_clean();

			if ( str_contains( $buddypanel_menu, 'bb-menu-section' ) ) {
				$buddypanel_menu = str_replace( 'buddypanel-menu side-panel-menu', 'buddypanel-menu side-panel-menu has-section-menu', $buddypanel_menu );
			}

			echo $buddypanel_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</aside>

