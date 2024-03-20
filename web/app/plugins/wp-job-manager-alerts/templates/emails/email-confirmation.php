<?php
/**
 * E-mail address confirmation e-mail HTML template. Includes styling layout and footer.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 *
 * @var string   $site_url Site URL.
 * @var string   $site_name Site name.
 * @var \WP_Post $alert The alert post.
 * @var array    $search_terms Search terms set for the alert.
 * @var string   $alert_confirm_url Confirmation URL.
 */

$term_names = [
	'keywords'   => __( 'Keywords', 'wp-job-manager-alerts' ),
	'categories' => __( 'Category', 'wp-job-manager-alerts' ),
	'tags'       => __( 'Tags', 'wp-job-manager-alerts' ),
	'types'      => __( 'Type', 'wp-job-manager-alerts' ),
	'regions'    => __( 'Location', 'wp-job-manager-alerts' ),
	'location'   => __( 'Location', 'wp-job-manager-alerts' ),
];

?>

<p><?php echo esc_html__( 'Hello,', 'wp-job-manager-alerts' ); ?></p>

<p>
	<?php
	$link = '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>';

	echo wp_kses(
	// Translators: Placeholder is the link to the site.
		sprintf( __( 'You set up a new job alert on the site %s. Please verify your e-mail address by clicking the button below to start receiving messages for this alert.', 'wp-job-manager-alerts' ), $link ),
		[
			'a' => [
				'href'  => [],
				'style' => [],
			],
		]
	);

	?>
</p>

<p><?php echo esc_html__( 'Alert details:', 'wp-job-manager-alerts' ); ?></p>

<div class="box">
	<div style="font-size: 110%; margin-bottom: 24px; font-weight: bold;"><?php echo esc_html( $alert->post_title ); ?></div>
	<?php foreach ( $search_terms as $type => $terms ) : ?>

		<div style="margin: 6px 0;">
			<span><?php echo esc_html( $term_names[ $type ] ); ?></span>: <strong><?php echo empty( $terms ) || empty( $terms[0] ) ? esc_html__( 'Any', 'wp-job-manager-alerts' ) : esc_html( implode( ', ', $terms ) ); ?></strong>
		</div>
	<?php endforeach; ?>
</div>

<p><?php echo esc_html__( 'If you didn\'t request this email, please ignore it.', 'wp-job-manager-alerts' ); ?></p>

<a class="button-single"
	href="<?php echo esc_url( $alert_confirm_url ); ?>"><?php echo esc_html__( 'Confirm Job Alert', 'wp-job-manager-alerts' ); ?></a>

<div class="footer">

	<div class="small-separator"></div>

	<div class="footer__content">
		<?php
		$link = '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>';

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
