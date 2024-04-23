<?php
/**
 * This template will display the emotion fields for reactions settings.
 *
 * @since   2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$reactions         = bb_pro_get_reactions( 'emotions', false );
$reaction_count    = is_countable( $reactions ) ? count( $reactions ) : 0;
$add_new_reactions = ( 6 - $reaction_count );

?>
<div class="bb_emotions_list">
	<?php
	foreach ( $reactions as $reaction ) {
		$is_emotion_active = get_post_meta( $reaction['id'], 'is_emotion_active', true );
		$is_emotion        = get_post_meta( $reaction['id'], 'is_emotion', true );
		if ( $is_emotion ) {
			$reaction['mode'] = 'emotions';
		}
		?>
		<div class="bb_emotions_item <?php echo esc_attr( ( ! $is_emotion_active ) ? 'is-disabled' : '' ); ?>" data-reaction-id="<?php echo esc_attr( $reaction['id'] ); ?>">
			<div class="bb_emotions_actions">
				<label class="bb_emotions_actions_enable">
					<input type="checkbox" name="reaction_checks[<?php echo esc_attr( $reaction['id'] ); ?>]" <?php echo ( $is_emotion_active ) ? 'checked' : ''; ?> value="1"/>
				</label>
				<button class="bb_emotions_actions_remove" aria-label="<?php esc_attr_e( 'Remove Emotion', 'buddyboss-pro' ); ?>">
					<i class="bb-icon-l bb-icon-times"></i>
				</button>
			</div>

			<div class="bb_emotions_icon">
			<?php
			if ( ! empty( $reaction['type'] ) && 'bb-icons' === $reaction['type'] ) {
				printf(
					'<i class="bb-icon-rf bb-icon-%s" style="color:%s"></i>',
					esc_attr( $reaction['icon'] ),
					esc_attr( $reaction['icon_color'] ),
				);
			} elseif ( ! empty( $reaction['type'] ) && 'custom' === $reaction['type'] ) {
				printf(
					'<img src="%s" alt=""/>',
					! empty( $reaction['icon_path'] ) ? esc_url( $reaction['icon_path'] ) : ''
				);
			} elseif ( ! empty( $reaction['type'] ) && 'emotions' === $reaction['type'] ) {
				$emoji = $reaction['icon'];
				if ( ! empty( $reaction['icon_path'] ) ) {
					$emoji = sprintf(
						'<img src="%s" alt=""/>',
						$reaction['icon_path']
					);
				}

				printf(
					'<span class="bbpro-icon-emoji">%s</span>',
					$emoji
				);
			}
			?>
			</div>

			<div class="bb_emotions_footer">
				<span style="color:<?php echo esc_attr( $reaction['text_color'] ); ?>"><?php echo ! empty( $reaction['icon_text'] ) ? esc_html( $reaction['icon_text'] ) : esc_html( $reaction['name'] ); ?></span>
				<button
					class="bb_emotions_edit"
					aria-label="<?php esc_attr_e( 'Edit Emotion', 'buddyboss-pro' ); ?>"
					data-icon="<?php echo htmlspecialchars( wp_json_encode( $reaction ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-type="<?php echo esc_attr( $reaction['type'] ); ?>">
					<i class="bb-icon-l bb-icon-pencil"></i>
				</button>
			</div>
			<input type="hidden" class="bb_admin_setting_reaction_item" name="reaction_items[<?php echo esc_attr( $reaction['id'] ); ?>]" value="<?php echo htmlspecialchars( wp_json_encode( $reaction ), ENT_QUOTES, 'UTF-8' ); ?>">
		</div>

		<?php
	}

	for ( $i = 0; $i < $add_new_reactions; $i++ ) {
		?>
		<div class="bb_emotions_item bb_emotions_item_action">
			<button class="bb_emotions_add_new" aria-label="<?php esc_attr_e( 'Add New Emotion', 'buddyboss-pro' ); ?>" data-bp-tooltip="<?php esc_attr_e( 'Add new', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="<?php esc_attr_e( 'up', 'buddyboss-pro' ); ?>">
				<i class="bb-icon-f bb-icon-plus"></i>
			</button>
		</div>
		<?php
	}
	?>

</div>
