<?php
/**
 * LearnDash LD30 Displays a custom alert message
 *
 * This file contains the wrapper for a custom alert message
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters custom alert type.
 *
 * @since 3.1.4
 *
 * @param string $type Alert message type.
 */
$type = apply_filters(
	'ld-alert-type',
	( ( isset( $type ) ) && ( ! empty( $type ) ) ? $type : '' )
);

/**
 * Filters custom alert icon CSS class.
 *
 * @since 3.0.0
 *
 * @param string $alert_class List of alert Icon CSS classes.
 * @param string $type        Alert message type.
 * @param string $icon        List of alert icon CSS classes.
 */
$icon = apply_filters(
	'ld-alert-icon',
	'ld-alert-icon ld-icon' . ( ( isset( $icon ) ) && ( ! empty( $icon ) ) ? ' ld-icon-' . $icon : '' ),
	( ! empty( $type ) ? $type : '' ),
	( ( isset( $icon ) ) && ( ! empty( $icon ) ) ? $icon : '' )
);

/**
 * Filters custom alert message CSS class.
 *
 * @since 3.0.0
 *
 * @param string $alert_class List of alert CSS classes.
 * @param string $type        Alert message type.
 * @param string $icon        List of alert icon CSS classes.
 */
$class = apply_filters(
	'ld-alert-class',
	'ld-alert ' . ( ! empty( $type ) ? 'ld-alert-' . $type : '' ),
	( ! empty( $type ) ? $type : '' ),
	( ! empty( $icon ) ? $icon : '' )
);

/**
 * Filters LearnDash custom alert message text.
 *
 * @since 3.0.0
 *
 * @param string $message Alert message text.
 * @param string $type    Alert message type.
 * @param string $icon    List of alert icon CSS classes.
 */
$message = apply_filters(
	'learndash_alert_message',
	$message,
	( ! empty( $type ) ? $type : '' ),
	( ! empty( $icon ) ? $icon : '' )
);

if ( ( isset( $message ) ) && ( ! empty( $message ) ) ) :

	/**
	 * Fires before an alert.
	 *
	 * @since 3.0.0
	 *
	 * @param string $class   List of alert CSS classes.
	 * @param string $icon    List of alert icon CSS classes.
	 * @param string $message Alert message text.
	 * @param string $type    Alert message type.
	 */
	do_action( 'learndash-alert-before', $class, $icon, $message, $type ); ?>

	<div class="<?php echo esc_attr( $class ); ?>">
		<div class="ld-alert-content">

			<?php
			/**
			 * Fires before an alert icon.
			 *
			 * @since 3.0.0
			 *
			 * @param string $class   List of alert CSS classes.
			 * @param string $icon    List of alert icon CSS classes.
			 * @param string $message Alert message text.
			 * @param string $type    Alert message type.
			 */
			do_action( 'learndash-alert-icon-before', $class, $icon, $message, $type );

			if ( ! empty( $icon ) ) :
				?>
				<div class="<?php echo esc_attr( $icon ); ?>"></div>
				<?php
			endif;

			/**
			 * Fires after an alert icon.
			 *
			 * @since 3.0.0
			 *
			 * @param string $class   List of alert CSS classes.
			 * @param string $icon    List of alert icon CSS classes.
			 * @param string $message Alert message text.
			 * @param string $type    Alert message type.
			 */
			do_action( 'learndash-alert-icon-after', $class, $icon, $message, $type );

			?>
			<div class="ld-alert-messages">
			<?php
			echo wp_kses_post( $message );
			?>
			</div>
			<?php

			/**
			 * Fires after an alert message.
			 *
			 * @since 3.0.0
			 *
			 * @param string $class   List of alert CSS classes.
			 * @param string $icon    List of alert icon CSS classes.
			 * @param string $message Alert message text.
			 * @param string $type    Alert message type.
			 */
			do_action( 'learndash-alert-message-after', $class, $icon, $message, $type );
			?>
		</div>

		<?php
		/**
		 * Fires between alert message and button
		 *
		 * @since 3.0.0
		 *
		 * @param string $class   List of alert CSS classes.
		 * @param string $icon    List of alert icon CSS classes.
		 * @param string $message Alert message text.
		 * @param string $type    Alert message type.
		 */
		do_action( 'learndash-alert-between-message-button', $class, $icon, $message, $type );

		/**
		 * Filters alert button data.
		 *
		 * @since 3.1.4
		 *
		 * @param array $button An array of alert button data.
		 */
		$button = apply_filters(
			'ld-alert-button',
			( ( isset( $button ) ) && ( ! empty( $button ) ) ? $button : array() )
		);

		if ( is_array( $button ) && ! empty( $button ) ) :

			$button_target = ( ( isset( $button['target'] ) ) && ( ! empty( $button['target'] ) ) ? 'target="' . esc_attr( $button['target'] ) . '"' : '' );
			$button_class  = 'class="ld-button' . ( ( isset( $button['class'] ) ) && ( ! empty( $button['class'] ) ) ? ' ' . esc_attr( $button['class'] ) : '' ) . '"';
			$button_url    = ( ( isset( $button['url'] ) ) && ( ! empty( $button['url'] ) ) ? 'href="' . esc_url( $button['url'] ) . '"' : '' );
			$button_label  = ( ( isset( $button['label'] ) ) && ( ! empty( $button['label'] ) ) ? esc_html( $button['label'] ) : '' );
			$button_icon   = ( ( isset( $button['icon'] ) ) && ( ! empty( $button['icon'] ) ) ? '<span class="ld-icon ld-icon-' . esc_attr( $button['icon'] ) . '"></span>' : '' );

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above
			?>
			<a <?php echo $button_class; ?> <?php echo $button_url; ?> <?php echo $button_target; ?>>
				<?php echo $button_icon; ?>
				<?php echo $button_label; ?>
			</a>
			<?php
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		endif;

		/**
		 * Fires after an alert button
		 *
		 * @since 3.0.0
		 *
		 * @param string $class   List of alert CSS classes.
		 * @param string $icon    List of alert icon CSS classes.
		 * @param string $message Alert message text.
		 * @param string $type    Alert message type.
		 */
		do_action( 'learndash-alert-content-after', $class, $icon, $message, $type );
		?>
	</div>

	<?php
	/**
	 * Fires after an alert.
	 *
	 * @since 3.0.0
	 *
	 * @param string $class   List of alert CSS classes.
	 * @param string $icon    List of alert icon CSS classes.
	 * @param string $message Alert message text.
	 * @param string $type    Alert message type.
	 */
	do_action( 'learndash-alert-after', $class, $icon, $message, $type );

endif; ?>
