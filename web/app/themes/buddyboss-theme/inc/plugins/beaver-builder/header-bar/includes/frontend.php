<?php
/**
 * This file should be used to render each module instance.
 * You have access to two variables in this file:
 *
 * $module An instance of your module class.
 * $settings The module's settings.
 *
 * Example:
 */

$settings_search_ico         = $settings->search_icon;
$settings_messages_icon      = $settings->messages_icon;
$settings_notifications_icon = $settings->notifications_icon;
$settings_cart_icon          = $settings->cart_icon;
?>

<div class="site-header site-header--beaver-builder site-header--align-<?php echo esc_attr( $settings->module_align ); ?>" data-search-icon="<?php echo esc_attr( $settings_search_ico ); ?>" data-messages-icon="<?php echo esc_attr( $settings_messages_icon ); ?>" data-notifications-icon="<?php echo esc_attr( $settings_notifications_icon ); ?>" data-cart-icon="<?php echo esc_attr( $settings_cart_icon ); ?>">

	<?php get_template_part( 'template-parts/header-aside' ); ?>

	<div class="header-search-wrap header-search-wrap--beaver-builder">
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
