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

$settings_align = $settings['content_align'];

$settings_search_ico         = $settings['search_icon']['value'];
$settings_messages_icon      = ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) ? $settings['messages_icon']['value'] : '';
$settings_notifications_icon = ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) ? $settings['notifications_icon']['value'] : '';
$settings_cart_icon          = ( class_exists( 'WooCommerce' ) ) ? $settings['cart_icon']['value'] : '';
$settings_dark_icon          = ( class_exists( 'SFWD_LMS' ) ) ? $settings['dark_icon']['value'] : '';
$settings_sidebartoggle_icon = ( class_exists( 'SFWD_LMS' ) ) ? $settings['sidebartoggle_icon']['value'] : '';
$settings_avatar_border      = $settings['avatar_border_style'];

$this->add_render_attribute( 'site-header', 'class', 'site-header site-header--elementor icon-fill-in' );
$this->add_render_attribute( 'site-header', 'class', 'site-header--align-' . esc_attr( $settings_align ) . '' );
$this->add_render_attribute( 'site-header', 'class', 'avatar-' . esc_attr( $settings_avatar_border ) . '' );
if ( $settings['switch_logo'] ) {
	$this->add_render_attribute( 'site-header', 'class', 'site-header--is-logo' );
}
if ( $settings['switch_nav'] ) {
	$this->add_render_attribute( 'site-header', 'class', 'site-header--is-nav' );
}
$this->add_render_attribute( 'site-header', 'data-search-icon', esc_attr( $settings_search_ico ) );
$this->add_render_attribute( 'site-header', 'data-messages-icon', esc_attr( $settings_messages_icon ) );
$this->add_render_attribute( 'site-header', 'data-notifications-icon', esc_attr( $settings_notifications_icon ) );
$this->add_render_attribute( 'site-header', 'data-cart-icon', esc_attr( $settings_cart_icon ) );
$this->add_render_attribute( 'site-header', 'data-dark-icon', esc_attr( $settings_dark_icon ) );
$this->add_render_attribute( 'site-header', 'data-sidebartoggle-icon', esc_attr( $settings_sidebartoggle_icon ) );

$this->add_render_attribute( 'site-header-container', 'class', 'container site-header-container flex default-header' );
$container = '<div ' . $this->get_render_attribute_string( 'site-header-container' ) . '>';
?>

<div <?php echo $this->get_render_attribute_string( 'site-header' ); ?>>

	<?php echo ( $settings['switch_logo'] || $settings['switch_nav'] ) ? wp_kses_post( $container ) : ''; ?>

	<?php if ( $settings['switch_logo'] ) : ?>
		<?php get_template_part( 'template-parts/site-logo' ); ?>
	<?php endif; ?>
	<?php if ( $settings['switch_nav'] ) : ?>
		<?php
		$nav_template_path = ELEMENTOR_BB__DIR__ . '/widgets/header-bar/templates/bb-header-bar-nav.php';

		if ( file_exists( $nav_template_path ) ) {
			require $nav_template_path;
		}
		?>
	<?php endif; ?>
	<?php if ( $settings['switch_bar'] ) : ?>
		<?php get_template_part( 'template-parts/header-aside' ); ?>
	<?php endif; ?>

	<?php echo ( $settings['switch_logo'] || $settings['switch_nav'] ) ? '</div>' : ''; ?>

	<?php get_template_part( 'template-parts/header-mobile', apply_filters( 'buddyboss_header_mobile', '' ) ); ?>
	<div class="header-search-wrap header-search-wrap--elementor">
		<div class="container">
			<?php
			add_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
			get_search_form();
			remove_filter( 'search_placeholder', 'buddyboss_search_input_placeholder_text' );
			?>
			<a href="#" class="close-search"><i class="bb-icon-rl bb-icon-times"></i></a>
		</div>
	</div>
</div>
