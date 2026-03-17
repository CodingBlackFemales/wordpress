<?php
/**
 * Template for rendering social account link and unlink buttons.
 *
 * This template handles the display of social login provider buttons
 * for linking and unlinking accounts in the user settings area.
 *
 * @since 2.7.70
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
$container_class = 'bb-rl-sso-container bb-sso-container';
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
								$svg = $provider->bb_sso_get_svg();
								if ( 'google' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M28 16C28.0001 18.8255 27.0032 21.5605 25.1849 23.7232C23.3665 25.8859 20.8433 27.3376 18.0597 27.8225C15.2761 28.3075 12.4106 27.7947 9.96801 26.3744C7.52539 24.9541 5.66229 22.7174 4.70686 20.0583C3.75142 17.3992 3.76495 14.4883 4.74505 11.8382C5.72516 9.18805 7.60897 6.96879 10.0647 5.57125C12.5204 4.17371 15.3905 3.68754 18.1695 4.19837C20.9485 4.70921 23.4581 6.18427 25.2563 8.36376C25.3455 8.46421 25.4136 8.58162 25.4565 8.70898C25.4994 8.83634 25.5162 8.97103 25.5058 9.10502C25.4955 9.239 25.4582 9.36953 25.3963 9.4888C25.3344 9.60807 25.2491 9.71364 25.1455 9.7992C25.0418 9.88475 24.922 9.94855 24.7932 9.98677C24.6643 10.025 24.5291 10.0368 24.3956 10.0216C24.2621 10.0064 24.133 9.96446 24.0161 9.89825C23.8991 9.83204 23.7967 9.74293 23.715 9.63626C22.2442 7.85291 20.201 6.63418 17.933 6.1874C15.665 5.74062 13.3122 6.09339 11.275 7.1857C9.23769 8.278 7.64178 10.0424 6.7587 12.1787C5.87562 14.3149 5.75992 16.6912 6.43128 18.9032C7.10265 21.1151 8.51961 23.0262 10.4411 24.3113C12.3626 25.5963 14.67 26.176 16.9707 25.9516C19.2714 25.7273 21.4233 24.7129 23.0604 23.0808C24.6975 21.4488 25.7186 19.3 25.95 17H16C15.7348 17 15.4804 16.8947 15.2929 16.7071C15.1054 16.5196 15 16.2652 15 16C15 15.7348 15.1054 15.4804 15.2929 15.2929C15.4804 15.1054 15.7348 15 16 15H27C27.2652 15 27.5196 15.1054 27.7071 15.2929C27.8946 15.4804 28 15.7348 28 16Z" fill="#3D3D3D"/>
										</svg>';
								} elseif ( 'facebook' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M16 3C13.4288 3 10.9154 3.76244 8.77759 5.1909C6.63975 6.61935 4.97351 8.64968 3.98957 11.0251C3.00563 13.4006 2.74819 16.0144 3.2498 18.5362C3.75141 21.0579 4.98953 23.3743 6.80762 25.1924C8.6257 27.0105 10.9421 28.2486 13.4638 28.7502C15.9856 29.2518 18.5995 28.9944 20.9749 28.0104C23.3503 27.0265 25.3807 25.3603 26.8091 23.2224C28.2376 21.0846 29 18.5712 29 16C28.9964 12.5533 27.6256 9.24882 25.1884 6.81163C22.7512 4.37445 19.4467 3.00364 16 3ZM17 26.9538V19H20C20.2652 19 20.5196 18.8946 20.7071 18.7071C20.8946 18.5196 21 18.2652 21 18C21 17.7348 20.8946 17.4804 20.7071 17.2929C20.5196 17.1054 20.2652 17 20 17H17V14C17 13.4696 17.2107 12.9609 17.5858 12.5858C17.9609 12.2107 18.4696 12 19 12H21C21.2652 12 21.5196 11.8946 21.7071 11.7071C21.8946 11.5196 22 11.2652 22 11C22 10.7348 21.8946 10.4804 21.7071 10.2929C21.5196 10.1054 21.2652 10 21 10H19C17.9391 10 16.9217 10.4214 16.1716 11.1716C15.4214 11.9217 15 12.9391 15 14V17H12C11.7348 17 11.4804 17.1054 11.2929 17.2929C11.1054 17.4804 11 17.7348 11 18C11 18.2652 11.1054 18.5196 11.2929 18.7071C11.4804 18.8946 11.7348 19 12 19H15V26.9538C12.181 26.6964 9.56971 25.3622 7.7093 23.2287C5.8489 21.0952 4.8826 18.3266 5.0114 15.4988C5.1402 12.671 6.35419 10.0017 8.40085 8.04613C10.4475 6.09057 13.1693 4.9993 16 4.9993C18.8307 4.9993 21.5525 6.09057 23.5992 8.04613C25.6458 10.0017 26.8598 12.671 26.9886 15.4988C27.1174 18.3266 26.1511 21.0952 24.2907 23.2287C22.4303 25.3622 19.819 26.6964 17 26.9538Z" fill="#3D3D3D"/>
										</svg>';
								} elseif ( 'apple' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M27.9128 21.1988C27.8378 21.0274 27.7166 20.8802 27.5628 20.7738C25.4415 19.3163 25.0003 16.83 25.0003 15C25.0003 12.7913 26.684 10.8675 27.6878 9.91625C27.7865 9.8228 27.8651 9.7102 27.9189 9.58533C27.9726 9.46046 28.0003 9.32595 28.0003 9.19C28.0003 9.05406 27.9726 8.91954 27.9189 8.79467C27.8651 8.6698 27.7865 8.5572 27.6878 8.46375C26.1028 6.9675 23.4778 6 21.0003 6C19.2206 6.00137 17.481 6.5289 16.0003 7.51625C14.2729 6.35795 12.1959 5.83776 10.1266 6.04513C8.05716 6.2525 6.1247 7.17448 4.66153 8.6525C3.78683 9.54549 3.10051 10.6052 2.64335 11.7686C2.18618 12.932 1.9675 14.1754 2.00028 15.425C2.04977 17.5341 2.51895 19.6122 3.38043 21.538C4.24191 23.4638 5.47843 25.1986 7.01778 26.6413C7.94503 27.5173 9.17338 28.0037 10.449 28H21.409C22.091 28.0013 22.766 27.8625 23.3921 27.5922C24.0183 27.3219 24.5822 26.9259 25.049 26.4288C25.9138 25.4981 26.6618 24.4655 27.2765 23.3538C28.154 21.75 28.0415 21.5 27.9128 21.1988ZM23.584 25.0663C23.3049 25.3627 22.9677 25.5985 22.5936 25.7592C22.2194 25.9198 21.8162 26.0018 21.409 26H10.449C9.68499 26.0025 8.94925 25.7111 8.39403 25.1863C7.04662 23.9248 5.96409 22.4074 5.2097 20.7228C4.4553 19.0383 4.04416 17.2203 4.00028 15.375C3.97293 14.3946 4.1431 13.4187 4.50065 12.5054C4.8582 11.5921 5.39582 10.76 6.08153 10.0588C6.72218 9.40425 7.48755 8.88483 8.3324 8.53122C9.17725 8.17761 10.0844 7.99698 11.0003 8H11.0978C12.6556 8.01641 14.1625 8.55716 15.3753 9.535C15.5526 9.67701 15.7731 9.75438 16.0003 9.75438C16.2275 9.75438 16.4479 9.67701 16.6253 9.535C17.8641 8.5362 19.409 7.99418 21.0003 8C22.5894 8.01845 24.144 8.46541 25.5003 9.29375C23.8753 11.1088 23.0003 13.1025 23.0003 15C23.0003 17.9713 23.9553 20.3413 25.7728 21.9125C25.2004 23.0646 24.4631 24.127 23.584 25.0663ZM16.029 3.75C16.3064 2.67589 16.933 1.72451 17.8104 1.04562C18.6877 0.366728 19.7659 -0.00111129 20.8753 2.52202e-06H21.0003C21.2655 2.52202e-06 21.5198 0.105359 21.7074 0.292896C21.8949 0.480432 22.0003 0.734786 22.0003 1C22.0003 1.26522 21.8949 1.51957 21.7074 1.70711C21.5198 1.89465 21.2655 2 21.0003 2H20.8753C20.2101 1.99994 19.5639 2.22093 19.038 2.62821C18.5122 3.0355 18.1366 3.60599 17.9703 4.25C17.904 4.50693 17.7383 4.727 17.5098 4.86179C17.2812 4.99658 17.0085 5.03506 16.7515 4.96875C16.4946 4.90245 16.2745 4.7368 16.1397 4.50824C16.005 4.27968 15.9665 4.00693 16.0328 3.75H16.029Z" fill="#3D3D3D"/>
										</svg>';
								} elseif ( 'twitter' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M26.844 26.4638L19.019 14.1663L26.7403 5.6725C26.9149 5.47565 27.0049 5.21791 26.9907 4.95515C26.9766 4.69239 26.8595 4.44579 26.6647 4.26882C26.47 4.09185 26.2134 3.99876 25.9504 4.00974C25.6875 4.02073 25.4395 4.1349 25.2603 4.3275L17.9053 12.4175L12.844 4.46375C12.7537 4.32169 12.6291 4.20471 12.4816 4.12365C12.3341 4.04258 12.1686 4.00005 12.0003 4H6.00025C5.82095 3.99991 5.64493 4.04803 5.49061 4.13932C5.33629 4.23062 5.20936 4.36172 5.1231 4.5189C5.03684 4.67609 4.99443 4.85357 5.0003 5.03278C5.00618 5.21198 5.06013 5.3863 5.1565 5.5375L12.9815 17.8337L5.26025 26.3337C5.17008 26.4306 5.10004 26.5444 5.05417 26.6685C5.00831 26.7927 4.98754 26.9247 4.99306 27.0569C4.99858 27.1891 5.03029 27.3189 5.08635 27.4388C5.14241 27.5586 5.22171 27.6662 5.31964 27.7552C5.41757 27.8442 5.53219 27.9129 5.65686 27.9572C5.78153 28.0016 5.91377 28.0208 6.04591 28.0137C6.17805 28.0066 6.30746 27.9733 6.42665 27.9158C6.54584 27.8583 6.65243 27.7777 6.74025 27.6787L14.0953 19.5888L19.1565 27.5425C19.2475 27.6834 19.3725 27.7991 19.5199 27.8791C19.6673 27.959 19.8325 28.0006 20.0003 28H26.0003C26.1794 27.9999 26.3552 27.9518 26.5093 27.8606C26.6634 27.7693 26.7902 27.6384 26.8764 27.4814C26.9627 27.3244 27.0051 27.1472 26.9994 26.9681C26.9937 26.7891 26.94 26.6149 26.844 26.4638ZM20.549 26L7.8215 6H11.4465L24.179 26H20.549Z" fill="#3D3D3D"/>
										</svg>';
								} elseif ( 'linkedin' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M27 3H5C4.46957 3 3.96086 3.21071 3.58579 3.58579C3.21071 3.96086 3 4.46957 3 5V27C3 27.5304 3.21071 28.0391 3.58579 28.4142C3.96086 28.7893 4.46957 29 5 29H27C27.5304 29 28.0391 28.7893 28.4142 28.4142C28.7893 28.0391 29 27.5304 29 27V5C29 4.46957 28.7893 3.96086 28.4142 3.58579C28.0391 3.21071 27.5304 3 27 3ZM27 27H5V5H27V27ZM12 14V22C12 22.2652 11.8946 22.5196 11.7071 22.7071C11.5196 22.8946 11.2652 23 11 23C10.7348 23 10.4804 22.8946 10.2929 22.7071C10.1054 22.5196 10 22.2652 10 22V14C10 13.7348 10.1054 13.4804 10.2929 13.2929C10.4804 13.1054 10.7348 13 11 13C11.2652 13 11.5196 13.1054 11.7071 13.2929C11.8946 13.4804 12 13.7348 12 14ZM23 17.5V22C23 22.2652 22.8946 22.5196 22.7071 22.7071C22.5196 22.8946 22.2652 23 22 23C21.7348 23 21.4804 22.8946 21.2929 22.7071C21.1054 22.5196 21 22.2652 21 22V17.5C21 16.837 20.7366 16.2011 20.2678 15.7322C19.7989 15.2634 19.163 15 18.5 15C17.837 15 17.2011 15.2634 16.7322 15.7322C16.2634 16.2011 16 16.837 16 17.5V22C16 22.2652 15.8946 22.5196 15.7071 22.7071C15.5196 22.8946 15.2652 23 15 23C14.7348 23 14.4804 22.8946 14.2929 22.7071C14.1054 22.5196 14 22.2652 14 22V14C14.0012 13.7551 14.0923 13.5191 14.256 13.3369C14.4197 13.1546 14.6446 13.0388 14.888 13.0114C15.1314 12.9839 15.3764 13.0468 15.5765 13.188C15.7767 13.3292 15.918 13.539 15.9738 13.7775C16.6502 13.3186 17.4389 13.0526 18.2552 13.0081C19.0714 12.9637 19.8844 13.1424 20.6067 13.5251C21.329 13.9078 21.9335 14.48 22.3551 15.1803C22.7768 15.8806 22.9997 16.6825 23 17.5ZM12.5 10.5C12.5 10.7967 12.412 11.0867 12.2472 11.3334C12.0824 11.58 11.8481 11.7723 11.574 11.8858C11.2999 11.9994 10.9983 12.0291 10.7074 11.9712C10.4164 11.9133 10.1491 11.7704 9.93934 11.5607C9.72956 11.3509 9.5867 11.0836 9.52882 10.7926C9.47094 10.5017 9.50065 10.2001 9.61418 9.92597C9.72771 9.65189 9.91997 9.41762 10.1666 9.2528C10.4133 9.08797 10.7033 9 11 9C11.3978 9 11.7794 9.15804 12.0607 9.43934C12.342 9.72064 12.5 10.1022 12.5 10.5Z" fill="#3D3D3D"/>
										</svg>';
								} elseif ( 'microsoft' === $provider->get_id() ) {
									echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 5H7C6.46957 5 5.96086 5.21071 5.58579 5.58579C5.21071 5.96086 5 6.46957 5 7V13C5 13.5304 5.21071 14.0391 5.58579 14.4142C5.96086 14.7893 6.46957 15 7 15H13C13.5304 15 14.0391 14.7893 14.4142 14.4142C14.7893 14.0391 15 13.5304 15 13V7C15 6.46957 14.7893 5.96086 14.4142 5.58579C14.0391 5.21071 13.5304 5 13 5ZM13 13H7V7H13V13ZM25 5H19C18.4696 5 17.9609 5.21071 17.5858 5.58579C17.2107 5.96086 17 6.46957 17 7V13C17 13.5304 17.2107 14.0391 17.5858 14.4142C17.9609 14.7893 18.4696 15 19 15H25C25.5304 15 26.0391 14.7893 26.4142 14.4142C26.7893 14.0391 27 13.5304 27 13V7C27 6.46957 26.7893 5.96086 26.4142 5.58579C26.0391 5.21071 25.5304 5 25 5ZM25 13H19V7H25V13ZM13 17H7C6.46957 17 5.96086 17.2107 5.58579 17.5858C5.21071 17.9609 5 18.4696 5 19V25C5 25.5304 5.21071 26.0391 5.58579 26.4142C5.96086 26.7893 6.46957 27 7 27H13C13.5304 27 14.0391 26.7893 14.4142 26.4142C14.7893 26.0391 15 25.5304 15 25V19C15 18.4696 14.7893 17.9609 14.4142 17.5858C14.0391 17.2107 13.5304 17 13 17ZM13 25H7V19H13V25ZM25 17H19C18.4696 17 17.9609 17.2107 17.5858 17.5858C17.2107 17.9609 17 18.4696 17 19V25C17 25.5304 17.2107 26.0391 17.5858 26.4142C17.9609 26.7893 18.4696 27 19 27H25C25.5304 27 26.0391 26.7893 26.4142 26.4142C26.7893 26.0391 27 25.5304 27 25V19C27 18.4696 26.7893 17.9609 26.4142 17.5858C26.0391 17.2107 25.5304 17 25 17ZM25 25H19V19H25V25Z" fill="#3D3D3D"/>
										</svg>';
								} else {
									echo wp_kses(
										$provider->bb_sso_get_svg(),
										bb_sso_allowed_tags()
									);
								}
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
							<?php
							if (
								$provider->is_current_user_connected() &&
								function_exists( 'bb_enable_additional_sso_name' ) &&
								bb_enable_additional_sso_name()
							) {
								global $wpdb;

								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
								$user_info = $wpdb->get_row(
									$wpdb->prepare(
										'SELECT first_name, last_name, identifier FROM ' . $wpdb->base_prefix . 'bb_social_sign_on_users WHERE type = %s AND wp_user_id = %d',
										$provider->get_db_id(),
										get_current_user_id()
									)
								);

								$display_name = $user_info->first_name . ' ' . $user_info->last_name;
								if ( ! empty( $display_name ) ) {
									?>
								<span class="bb-sso-user-name">
									<?php echo esc_html( $display_name ); ?>
								</span>
									<?php
								}
							}
							?>

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
