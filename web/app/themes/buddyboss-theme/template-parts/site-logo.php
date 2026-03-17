<?php
// Site Logo
$buddypanel      = buddyboss_theme_get_option( 'buddypanel' );
$header          = (int) buddyboss_theme_get_option( 'buddyboss_header' );
$show            = buddyboss_theme_get_option( 'logo_switch' );
$show_dark       = buddyboss_theme_get_option( 'logo_dark_switch' );
$logo_id         = buddyboss_theme_get_option( 'logo', 'id' );
$logo_dark_id    = buddyboss_theme_get_option( 'logo_dark', 'id' );
$buddypanel_logo = buddyboss_theme_get_option( 'buddypanel_show_logo' );

// Get proper alt text for logo.
$logo_alt      = '';
$logo_dark_alt = '';

if ( $logo_id ) {
	// Prioritize site name over image title for better UX.
	$logo_alt = get_post_meta( $logo_id, '_wp_attachment_image_alt', true );
	if ( empty( $logo_alt ) ) {
		$logo_alt = get_bloginfo( 'name' );
	}
	if ( empty( $logo_alt ) ) {
		$logo_alt = get_the_title( $logo_id );
	}
}

if ( $logo_dark_id ) {
	// Prioritize site name over image title for better UX.
	$logo_dark_alt = get_post_meta( $logo_dark_id, '_wp_attachment_image_alt', true );
	if ( empty( $logo_dark_alt ) ) {
		$logo_dark_alt = get_bloginfo( 'name' );
	}
	if ( empty( $logo_dark_alt ) ) {
		$logo_dark_alt = get_the_title( $logo_dark_id );
	}
}

$logo      = ( $show && $logo_id ) ? wp_get_attachment_image( $logo_id, 'full', '', array(  'class' => 'bb-logo', 'alt'   => $logo_alt ) ) : get_bloginfo( 'name' );
$logo_dark = ( $show && $show_dark && $logo_dark_id ) ? wp_get_attachment_image( $logo_dark_id, 'full', '', array( 'class' => 'bb-logo bb-logo-dark', 'alt'   => $logo_dark_alt ) ) : '';

// This is for better SEO.
$elem       = ( is_front_page() && is_home() ) ? 'h1' : 'div';
$logo_class = $buddypanel ? $buddypanel_logo && 5 !== $header ? 'buddypanel_logo_display_on' : 'buddypanel_logo_display_off' : '';

// Show Logo in header if buddypanel does not have menu to show.
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
		<a href="<?php echo esc_url( bb_get_theme_header_logo_link() ); ?>" rel="home" aria-label="<?php echo esc_attr( sprintf( __( 'Go to %s homepage', 'buddyboss-theme' ), get_bloginfo( 'name' ) ) ); ?>">
			<?php echo $logo; echo $logo_dark;?>
		</a>
	</<?php echo $elem; ?>>
</div>