<?php
/**
 * Single Event Meta Template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe-events/modules/meta.php
 *
 * @version 4.6.10
 *
 * @package TribeEventsCalendar
 */

do_action( 'tribe_events_single_meta_before' );

// Check for skeleton mode (no outer wrappers per section)
$not_skeleton = ! apply_filters( 'tribe_events_single_event_the_meta_skeleton', false, get_the_ID() );

// Do we want to group venue meta separately?
$set_venue_apart = apply_filters( 'tribe_events_single_event_the_meta_group_venue', false, get_the_ID() );
?>

<?php if ( $not_skeleton ) : ?>
	<div class="tribe-events-single-section tribe-events-event-meta primary tribe-clearfix">
<?php endif; ?>

<?php
do_action( 'tribe_events_single_event_meta_primary_section_start' );

// Always include the main event details in this first section
tribe_get_template_part( 'modules/meta/details' );

// Include venue meta if appropriate.
if ( tribe_get_venue_id() ) {
    tribe_get_template_part( 'modules/meta/venue' );
	if ( tribe_embed_google_map() ) {
		$set_venue_apart = true;
	}
}

// Include organizer meta if appropriate
if ( tribe_has_organizer() ) {
	tribe_get_template_part( 'modules/meta/organizer' );
}

do_action( 'tribe_events_single_event_meta_primary_section_end' );
?>

<?php if ( $not_skeleton ) : ?>
	</div>
<?php endif; ?>


<?php if ( $set_venue_apart || ( ! tribe_has_organizer() && tribe_embed_google_map() ) ) : ?>
	<?php if ( $not_skeleton ) : ?>
		<div class="tribe-events-single-section tribe-events-event-meta secondary tribe-clearfix">
	<?php endif; ?>
	<?php
	do_action( 'tribe_events_single_event_meta_secondary_section_start' );
	tribe_get_template_part( 'modules/meta/map' );
	do_action( 'tribe_events_single_event_meta_secondary_section_end' );
	?>
	<?php if ( $not_skeleton ) : ?>
		</div>
	<?php endif;
endif;
do_action( 'tribe_events_single_meta_after' );
