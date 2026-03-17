<?php
/**
 * Template for rendering social account link and unlink buttons.
 *
 * This template handles the display of social login provider buttons
 * for linking and unlinking accounts in the user settings area.
 *
 * @since 2.6.30
 * @since 2.7.70 - Move code from render_link_and_unlink_buttons() to this template.
 *
 * @version 1.0.0
 *
 * @param array $args {
 *     Optional. Array of arguments for rendering the buttons.
 *
 *     @type string $heading   The heading text to display above the buttons.
 *     @type bool   $link      Whether to display the link buttons. Default true.
 *     @type bool   $unlink    Whether to display the unlink buttons. Default true.
 *     @type string $align     The alignment of the buttons. Default 'left'.
 *     @type array  $providers The social login providers to display buttons for.
 *     @type string $style     The style of the buttons. Default 'default'.
 * }
 *
 * @package BuddyBossPro/SSO
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Ensure we have the required arguments.
$defaults = array(
	'heading'   => '',
	'link'      => true,
	'unlink'    => true,
	'align'     => 'left',
	'providers' => false,
	'style'     => 'default',
);

$args = wp_parse_args( $args, $defaults );

// Extract variables for easier access.
$heading         = $args['heading'];
$link            = $args['link'];
$unlink          = $args['unlink'];
$align           = $args['align'];
$providers       = $args['providers'];
$style           = $args['style'];
$container_style = $args['container_style'];

// Check if we have enabled providers and user is logged in.
if ( ! count( BB_SSO::$enabled_providers ) || ! is_user_logged_in() ) {
	return;
}

// Validate and set default values.
$style = ! empty( $style ) ? $style : 'default';
$align = ! empty( $align ) ? $align : 'left';

/**
 * We shouldn't allow the icon style for Link and Unlink buttons.
 */
if ( 'icon' === $style ) {
	$style = 'default';
}

// Check if unlinking is allowed.
if ( $unlink ) {
	/**
	 * Filter to disable unlinking social accounts.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $is_unlink_allowed Whether unlinking is allowed.
	 */
	$is_unlink_allowed = apply_filters( 'bb_sso_allow_unlink', true );
	if ( ! $is_unlink_allowed ) {
		$unlink = false;
	}
}

// Get enabled providers.
if ( ! empty( $providers ) && is_array( $providers ) ) {
	$enabled_providers = array();
	foreach ( $providers as $provider ) {
		if ( $provider && isset( BB_SSO::$enabled_providers[ $provider->get_id() ] ) ) {
			$enabled_providers[ $provider->get_id() ] = $provider;
		}
	}
} else {
	$enabled_providers = BB_SSO::$enabled_providers;
}

// Check if we have providers to display.
if ( empty( $enabled_providers ) || ! count( $enabled_providers ) ) {
	return;
}

// Get container class based on style.
$container_class = 'bb-sso-container';
if ( isset( $container_style[ $style ]['container'] ) ) {
	$container_class .= ' ' . $container_style[ $style ]['container'];
}

// Build container attributes.
$container_attrs = array(
	'class' => $container_class,
);

if ( 'fullwidth' !== $style ) {
	$container_attrs['data-align'] = esc_attr( $align );
}

// Start output buffering for the social accounts HTML.
ob_start();
?>

<div class="<?php echo esc_attr( $container_class ); ?>" <?php echo 'fullwidth' !== $style ? 'data-align="' . esc_attr( $align ) . '"' : ''; ?>>
	<?php if ( ! empty( $heading ) ) : ?>
		<h2 class="bb-sso-heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="bb-sso-social-accounts">
		<?php foreach ( $enabled_providers as $provider ) : ?>
			<?php
			$connected = '';
			$buttons   = '';

			if ( $provider->is_current_user_connected() ) {
				$connected = esc_html__( 'Connected', 'buddyboss-pro' );
				if ( $unlink ) {
					$buttons = $provider->get_unlink_button();
				}
			} elseif ( $link ) {
				$buttons = $provider->get_link_button();
			}
			?>

			<div class="bb-sso-container-buttons bb-sso-container-buttons--client">
				<div class="bb-sso-option">
					<div class="bb-sso-button bb-sso-button-default bb-sso-button--client bb-sso-button-<?php echo esc_attr( $provider->get_id() ); ?>">
						<div class="bb-sso-button-auth">
							<div class="bb-sso-button-svg-container">
								<?php
								echo wp_kses(
									$provider->bb_sso_get_svg(),
									bb_sso_allowed_tags()
								);
								?>
							</div>
							<div class="bb-sso-label">
								<?php echo esc_html( $provider->get_label() ); ?>
							</div>
							<?php if ( ! empty( $connected ) ) : ?>
								<div class="bb-sso-status">
									<?php echo esc_html( $connected ); ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="bb-sso-option-actions">
							<?php if ( ! empty( $buttons ) ) : ?>
								<?php echo wp_kses_post( $buttons ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

		<?php endforeach; ?>
	</div>
</div>

<?php
$social_accounts_html = ob_get_clean();

echo $social_accounts_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
