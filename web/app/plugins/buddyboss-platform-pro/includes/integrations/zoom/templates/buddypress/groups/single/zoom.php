<?php
/**
 * BuddyBoss - Groups Zoom
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

switch ( bp_zoom_group_current_tab() ) :

	// Meetings.
	case 'zoom':
	case 'meetings':
	case 'create-meeting':
	case 'past-meetings':
			bp_get_template_part( 'zoom/meetings' );
		break;

	// Webinars.
	case 'webinars':
	case 'create-webinar':
	case 'past-webinars':
		bp_get_template_part( 'zoom/webinars' );
		break;

	// Any other.
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
