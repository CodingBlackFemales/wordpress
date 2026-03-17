<?php
/**
 * BuddyBoss SSO Login Below Separator
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro/SSO
 */

$container_id = ! empty( $args['container_id'] ) ? $args['container_id'] : 'bb-sso-custom-login-form-main';

wp_add_inline_script(
	'bb-sso',
	'var containerId = "' . $container_id . '";
		var orString = "' . esc_html__( 'OR', 'buddyboss-pro' ) . '";
		var separatorPosition = "below-separator";
		var formId = "#loginform";',
	'before'
);

$style = '
    .bb-sso-clear {
        clear: both;
    }
    
    {{containerID}} .bb-sso-container {
        display: none;
    }

    {{containerID}} .bb-sso-separator {
        display: flex;
        flex-basis: 100%;
        align-items: center;
        color: #72777c;
        margin: 20px 0 20px;
        font-weight: bold;
    }

    {{containerID}} .bb-sso-separator::before,
    {{containerID}} .bb-sso-separator::after {
        content: "";
        flex-grow: 1;
        background: #dddddd;
        height: 1px;
        font-size: 0;
        line-height: 0;
        margin: 0 8px;
    }

    {{containerID}} .bb-sso-container-buddypress-login-layout-below-separator {
        clear: both;
    }

    .login form {
        padding-bottom: 20px;
    }';
?>
<style type="text/css">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo str_replace( '{{containerID}}', '#' . $container_id, $style );
	?>
</style>
