<?php
/**
 * Certificate: Content
 *
 * @package LifterLMS/Templates
 *
 * @since 1.0.0
 * @version 4.5.0
 */

defined( 'ABSPATH' ) || exit;

$certificate = new LLMS_User_Certificate( get_the_ID() );

/**
 * Action triggered to display a single certificate.
 *
 * @since 6.0.0
 *
 * @hooked llms_certificate_content - 10.
 * @hooked llms_certificate_actions - 20.
 *
 * @param LLMS_User_Certificate $certificate The certificate object.
 */
do_action( 'llms_display_certificate', $certificate );
