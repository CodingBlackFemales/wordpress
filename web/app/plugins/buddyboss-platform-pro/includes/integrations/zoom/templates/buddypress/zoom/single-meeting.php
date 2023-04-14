<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

if ( bp_has_zoom_meetings( array( 'include' => bp_zoom_get_current_meeting_id() ) ) ) :
	while ( bp_zoom_meeting() ) :
		bp_the_zoom_meeting();
		bp_get_template_part( 'zoom/single-meeting-item' );
	endwhile;
endif;

