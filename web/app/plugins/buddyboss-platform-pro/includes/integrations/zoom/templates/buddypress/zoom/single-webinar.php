<?php
/**
 * BuddyBoss - Groups Zoom Single Webinar
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.9
 */

if ( bp_has_zoom_webinars( array( 'include' => bp_zoom_get_current_webinar_id() ) ) ) :
	while ( bp_zoom_webinar() ) :
		bp_the_zoom_webinar();
		bp_get_template_part( 'zoom/single-webinar-item' );
	endwhile;
endif;

