<?php
/**
 * View: PayPal Standard - Current Subscriptions Migration Status.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var string $migration_status Current migration status.
 *
 * @package LearnDash\Core
 */

$migration_progress_classes = [
	'not-started' => 'ld-paypal-standard__current-subscriptions--migration-status-not-started',
	'failed'      => 'ld-paypal-standard__current-subscriptions--migration-status-failed',
	'migrated'    => 'ld-paypal-standard__current-subscriptions--migration-status-migrated',
];

$migration_status_labels = [
	'not-started' => __( 'Not Started', 'learndash' ),
	'failed'      => __( 'Failed', 'learndash' ),
	'migrated'    => __( 'Migrated', 'learndash' ),
];

$migration_status_icons = [
	'not-started' => 'dashicons-minus',
	'failed'      => 'dashicons-no-alt',
	'migrated'    => 'dashicons-saved',
];

?>
<span
	class="ld-paypal-standard__current-subscriptions--migration-status <?php echo esc_attr( $migration_progress_classes[ $migration_status ] ); ?>"
>
	<span class="dashicons <?php echo esc_attr( $migration_status_icons[ $migration_status ] ); ?> ld-paypal-standard__current-subscriptions--migration-status-icon" aria-hidden="true"></span>
	<?php echo esc_html( $migration_status_labels[ $migration_status ] ); ?>
</span>
