<?php
/**
 * Message to display when access is denied to a single resume.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/access-denied-single-resume.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $post->post_status === 'expired' ) : ?>
	<div class="job-manager-info"><?php esc_html_e( 'This listing has expired', 'wp-job-manager-resumes' ); ?></div>
<?php else : ?>
	<p class="job-manager-error"><?php esc_html_e( 'Sorry, you do not have permission to view this resume.', 'wp-job-manager-resumes' ); ?></p>
<?php endif; ?>
