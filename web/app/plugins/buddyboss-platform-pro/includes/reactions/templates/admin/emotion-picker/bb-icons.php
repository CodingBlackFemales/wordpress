<?php
/**
 * This template will display the icons for reactions settings.
 *
 * @since 2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div id="bbpro-icons" class="icons bbpro-icons-list bbpro-hide">
	<?php
	$icon_items = bb_get_default_reaction_icons( 'bb-icons' );

	if ( ! empty( $icon_items ) ) {
		foreach ( $icon_items as $icon_item ) {
			printf(
				'<a href="javascript:void(0);" class="bbpro-icon bbpro-icon-tag-render" data-css="%1$s" data-code="%2$s" data-label="%3$s" data-label-lower="%4$s" data-group="%5$s"><i class="bb-icon-rf bb-icon-%6$s"></i><span class="bbpro-icon-title"><span>%7$s</span></span></a>',
				esc_attr( $icon_item['css'] ),
				esc_attr( $icon_item['code'] ),
				esc_attr( $icon_item['label'] ),
				esc_attr( strtolower( $icon_item['label'] ) ),
				esc_attr( $icon_item['group'] ),
				esc_attr( $icon_item['css'] ),
				esc_html( $icon_item['label'] )
			);
		}
	}
	?>
</div>
