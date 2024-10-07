<?php
/**
 * Email Summary header template (plain text).
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/summary-header-plain.php.
 *
 * @since 1.8.8
 *
 * @var array $license_banner License banner arguments.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $license_banner ) && ! empty( $license_banner['status'] ) ) {

	if ( ! empty( $license_banner['title'] ) ) {
		echo esc_html( $license_banner['title'] ) . "\n\n";
	}

	if ( ! empty( $license_banner['content'] ) ) {
		echo esc_html( implode( "\n\n", array_map( 'wp_strip_all_tags', $license_banner['content'] ) ) );
	}

	if ( ! empty( $license_banner['help_url'] ) ) {
		echo "\n\n" . esc_url( $license_banner['help_url'] ) . "\n\n";
	}

	if ( ! empty( $license_banner['cta']['url'] ) && ! empty( $license_banner['cta']['text'] ) ) {
		echo esc_html( $license_banner['cta']['text'] ) . ': ' . esc_url( $license_banner['cta']['url'] ) . "\n\n";
	}

	echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
