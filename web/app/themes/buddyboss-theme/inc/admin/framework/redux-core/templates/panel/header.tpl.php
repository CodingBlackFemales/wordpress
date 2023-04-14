<?php
/**
 * The template for the panel header area.
 * Override this template by specifying the path where it is stored (templates_path) in your Redux config.
 *
 * @author      Redux Framework
 * @package     ReduxFramework/Templates
 * @version:    4.0.0
 */

$tip_title = esc_html__( 'Developer Mode Enabled', 'redux-framework' );

if ( $this->parent->args_class->dev_mode_forced ) {
	$is_debug     = false;
	$is_localhost = false;
	$debug_bit    = '';

	if ( Redux_Helpers::is_wp_debug() ) {
		$is_debug  = true;
		$debug_bit = esc_html__( 'WP_DEBUG is enabled', 'redux-framework' );
	}

	$localhost_bit = '';
	if ( Redux_Helpers::is_local_host() ) {
		$is_localhost  = true;
		$localhost_bit = esc_html__( 'you are working in a localhost environment', 'redux-framework' );
	}

	$conjunction_bit = '';
	if ( $is_localhost && $is_debug ) {
		$conjunction_bit = ' ' . esc_html__( 'and', 'redux-framework' ) . ' ';
	}

	$tip_msg = esc_html__( 'This has been automatically enabled because', 'redux-framework' ) . ' ' . $debug_bit . $conjunction_bit . $localhost_bit . '.';
} else {
	$tip_msg = esc_html__( 'If you are not a developer, your theme/plugin author shipped with developer mode enabled. Contact them directly to fix it.', 'redux-framework' );
}

?>
<div id="redux-header">
	<?php if ( ! empty( $this->parent->args['display_name'] ) ) { ?>
		<div class="display_header">
			<?php if ( isset( $this->parent->args['dev_mode'] ) && $this->parent->args['dev_mode'] ) { ?>
				<div class="redux-dev-mode-notice-container redux-dev-qtip"
					qtip-title="<?php echo esc_attr( $tip_title ); ?>"
					qtip-content="<?php echo esc_attr( $tip_msg ); ?>">
                    <span
	                    class="redux-dev-mode-notice"><?php _e( 'Developer Mode Enabled', 'redux-framework' ); ?></span>
				</div>
			<?php } elseif (isset($this->parent->args['forced_dev_mode_off']) && $this->parent->args['forced_dev_mode_off'] == true ) { ?>
				<?php $tip_title    = 'The "forced_dev_mode_off" argument has been set to true.'; ?>
				<?php $tip_msg      = 'Support options are not available while this argument is enabled.  You will also need to switch this argument to false before deploying your project.  If you are a user of this product and you are seeing this message, please contact the author of this theme/plugin.'; ?>
				<div class="redux-dev-mode-notice-container redux-dev-qtip"
					qtip-title="<?php echo esc_attr( $tip_title ); ?>"
					qtip-content="<?php echo esc_attr( $tip_msg ); ?>">
                    <span
	                    class="redux-dev-mode-notice" style="background-color: #FF001D;"><?php _e( 'FORCED DEV MODE OFF ENABLED', 'redux-framework' ); ?></span>
				</div>
			
			<?php } ?>

			<h2><?php _e( 'Theme Options', 'redux-framework' ); ?></h2>
		</div>
	<?php } ?>
	<div class="clear"></div>
</div>
