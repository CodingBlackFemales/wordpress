<?php
// Site Logo
$buddypanel      = buddyboss_theme_get_option( 'buddypanel' );
$show            = buddyboss_theme_get_option( 'logo_switch' );
$show_dark       = buddyboss_theme_get_option( 'logo_dark_switch' );
$logo_id         = buddyboss_theme_get_option( 'logo', 'id' );
$logo_dark_id    = buddyboss_theme_get_option( 'logo_dark', 'id' );
$buddypanel_logo = buddyboss_theme_get_option( 'buddypanel_show_logo' );
$logo            = ( $show && $logo_id ) ? wp_get_attachment_image( $logo_id, 'full', '', array( 'class' => 'bb-logo' ) ) : get_bloginfo( 'name' );
$logo_dark       = ( $show && $show_dark && $logo_dark_id ) ? wp_get_attachment_image( $logo_dark_id, 'full', '', array( 'class' => 'bb-logo bb-logo-dark' ) ) : '';

// This is for better SEO
$elem       = ( is_front_page() && is_home() ) ? 'h1' : 'div';
$logo_class = $buddypanel ? $buddypanel_logo ? 'buddypanel_logo_display_on' : 'buddypanel_logo_display_off' : '';

// Show Logo in header if buddypanel does not have menu to show
if ( 'buddypanel_logo_display_on' === $logo_class ) {

	$menu = is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout';

	if ( has_nav_menu( $menu ) ) {
		$logo_class = 'buddypanel_logo_display_on';
	} else {
		$logo_class = 'buddypanel_logo_display_off';
	}
}
?>

<div id="site-logo" class="site-branding <?php echo esc_attr( $logo_class ); ?>">
	<<?php echo $elem; ?> class="site-title">
		<a href="<?php echo esc_url( bb_get_theme_header_logo_link() ); ?>" rel="home">
			<?php echo $logo; echo $logo_dark;?>
		</a>
	</<?php echo $elem; ?>>
</div>