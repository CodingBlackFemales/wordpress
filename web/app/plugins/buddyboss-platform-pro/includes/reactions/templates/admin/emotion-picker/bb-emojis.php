<?php
/**
 * This template will display the emojis for reactions settings.
 *
 * @since 2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div id="bbpro-emojis" class="icons bbpro-emojis-list">
	<?php
	$emoji_icons = bb_get_default_reaction_icons( 'emotions' );

	if ( ! empty( $emoji_icons ) && is_array( $emoji_icons ) ) {
		foreach ( $emoji_icons as $icon ) {

			$icon_label = str_replace( '-', ' ', $icon['name'] );

			printf(
				'<a href="javascript:void(0);" class="bbpro-icon bbpro-emoji-tag-render" data-name="%1$s" data-unicode="%2$s" data-html="%3$s" data-group="%4$s">
					<span class="bbpro-icon-emoji">
					<img loading="lazy" alt="%1$s" class="lazy-emoji" data-src="%5$s" src="" />
					</span>
					<span class="bbpro-icon-title">%6$s</span>
				</a>',
				esc_attr( $icon['name'] ),
				esc_attr( $icon['unicode'] ),
				esc_attr( $icon['emoji'] ),
				esc_attr( $icon['category'] ),
				esc_url( $icon['emoji_url'] ),
				esc_html( $icon_label ),
			);
		}
	}
	?>
</div>
