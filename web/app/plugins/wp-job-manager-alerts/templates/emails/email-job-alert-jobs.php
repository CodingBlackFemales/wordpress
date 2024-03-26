<?php
/**
 * Generate HTML list of matching jobs for job alert e-mails. This is the content of the {jobs} tag.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 *
 *
 * @var WP_Post[] $jobs List of jobs matching the alert.
 */

use WP_Job_Manager_Alerts\Settings;

$brand_color       = Settings::instance()->get_the_brand_color();
$visible_fields    = Settings::instance()->get_visible_email_fields();
$show_logo         = ! empty( $visible_fields['company_logo'] );
$show_company_name = ! empty( $visible_fields['company_name'] );
$show_location     = ! empty( $visible_fields['location'] );

?>

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="border-spacing: 0; margin: 24px 0; width: 100%;">
	<?php
	$last_jobs_key = array_key_last( $jobs );
	foreach( $jobs as $key => $job ):
			$location          = get_the_job_location( $job );
			$company           = get_the_company_name( $job );
			$logo              = get_the_company_logo( $job );

			// Translators: Placeholder is the company name
			$logo_alt = sprintf( __( '%s logo', 'wp-job-manager-alerts' ), $company );
			$border   = $last_jobs_key === $key ? '' : 'border-bottom: 1px solid #E3E3E3;';
		?>
			<tr>
				<?php if ( $show_logo ): ?>
				<td valign="center"
					style="width: 60px; padding: 18px 0; padding-right: 18px; <?php echo esc_attr( $border ); ?>">
					<a href="<?php echo esc_attr( get_the_job_permalink( $job ) ); ?>">
					<?php if ( ! empty( $logo ) ): ?>
						<img src="<?php echo esc_attr( $logo ); ?>" width="60"
							style="border: none; max-width: initial; width: 60px; display: block;"
							alt="<?php echo esc_attr( $logo_alt ); ?>" />
					<?php else: ?>
						<div style="width:60px;height: 60px; background-color: #F6F7F7; "></div>
					<?php endif; ?>
					</a>
				</td>
				<?php endif; ?>
				<td valign="top" style="padding: 18px 0; <?php echo esc_attr( $border ); ?>">
					<a href="<?php echo esc_attr( get_the_job_permalink( $job ) ); ?>"
						style="font-weight: 600; letter-spacing: -0.8px; line-height: 130%; text-decoration: none;"><?php echo esc_html( $job->post_title ); ?></a>
					<?php if ( $show_location ): ?>
					<div style="font-size: 87.5%; margin: 2px 0; line-height: 1.2;">
						<?php echo esc_html( $location ); ?>
					</div>
					<?php endif; ?>
					<?php if ( $show_company_name ): ?>
					<div style="font-size: 87.5%;margin: 2px 0; line-height: 1.2; font-weight: 600;">
						<?php echo esc_html( $company ); ?>
					</div>
					<?php endif; ?>
				</td>
			</tr>
	<?php
	endforeach;
	?>
</table>
