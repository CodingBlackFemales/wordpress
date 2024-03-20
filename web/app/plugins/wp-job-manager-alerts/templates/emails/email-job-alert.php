<?php
/**
 * Job alert e-mail HTML template. Includes styling layout and footer.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 *
 * @var string $subject E-mail title.
 * @var string $content Main content.
 * @var string $site_url Site URL.
 * @var string $site_name Site name.
 * @var string $alert_page_url Manage alerts page URL
 * @var string $alert_unsubscribe_url Unsubscribe URL.
 */
?>

<?php echo $content; ?>

<div class="footer">

	<div class="small-separator"></div>

	<div class="actions">

		<a class="action" href="<?php echo esc_url( $alert_page_url ) ?>"><?php esc_html_e( 'Manage Alerts', 'wp-job-manager-alerts' ); ?></a>
		<span class="action-separator">|</span>
		<a class="action" href="<?php echo esc_url( $alert_unsubscribe_url ) ?>"><?php esc_html_e( 'Unsubscribe', 'wp-job-manager-alerts' ); ?></a>
	</div>

	<div class="footer__content">
		<?php
		$link = '<a href="'. esc_url( $site_url ) . '">' . esc_html( $site_name ).'</a>';

		echo wp_kses(
			// Translators: Placeholder is the link to the site.
			sprintf( __( 'You are receiving this email because you created a job alert on %s.', 'wp-job-manager-alerts' ), $link ),
			[
			'a' => [
				'href'  => [],
				'style' => [],
				],
			]
		);

		?>
	</div>
</div>

