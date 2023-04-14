<?php
/**
 * Card: New Share
 * @since    1.0.0
 * @version  1.0.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

llms_sl_card_open_html( 'new-share', array( 'new-share' ) ); ?>

	<header class="llms-sl-card-header custom-sl-card-header">
		<?php echo $student->get_avatar( 80 ); ?>
		<h4 class="llms-sl-custom-title"><?php echo $student->get( 'display_name' ); ?></h4>
	</header>

	<div class="llms-sl-card-main custom-sl-card-main">

		<div class="llms-sl-new-share-content" contenteditable="true" id="llms-sl-new-share-content" placeholder="<?php esc_attr_e( 'How\'s it going?', 'buddyboss-theme' ); ?>"></div>

	</div>

	<footer class="llms-sl-card-footer custom-sl-card-footer">

		<input id="llms-sl-new-share-noun" type="hidden" value="<?php echo $noun; ?>">
		<?php wp_nonce_field( 'llms_sl_new_share', 'llms-sl-new-share-nonce' ); ?>
		<button class="llms-button-primary button-right llms-sl-button" disabled="disabled" id="llms-sl-new-share-submit"><?php _e( 'Post', 'buddyboss-theme' ); ?></button>

		<div class="llms-sl-card-error"></div>

	</footer>

<?php llms_sl_card_close_html();
