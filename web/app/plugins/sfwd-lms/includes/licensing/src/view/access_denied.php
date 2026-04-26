<?php
/**
 * Access Denied template.
 *
 * @since 4.18.0
 * @version 4.18.0
 *
 * @package LearnDash\Core
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap learndash-hub" style="background:white;padding:10px">
	<h3 class="text-2xl tracking-tight font-bold"><?php esc_html_e( 'Restrict Area', 'learndash' ); ?></h3>
	<p><?php esc_html_e( "This license area is protected. If you feel like you've reached this page in error or would like to request access, please contact the site owner.", 'learndash' ); ?></p>
</div>
