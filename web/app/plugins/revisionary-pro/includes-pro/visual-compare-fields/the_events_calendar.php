<?php

return [
    'the_events_calendar' => [
        'label' => 'The Events Calendar',
        'post_types' => ['tribe_events', 'tribe_venue', 'tribe_organizer'],
        'caption_prefixes' => ['_Event', '_Venue', '_Organizer'],
        'fields' => RevisionaryVisualCompareFieldProviders::eventsCalendarFields(),
        'blacklist' => [
            '_EventDuration', '_EventStartDateUTC', '_EventEndDateUTC', '_EventTimezoneAbbr',
            '_EventOrigin', '_EventRecurrenceRRULE', '_preview_organizers', '_preview_venues',
            '_tribe_aggregator_global_id', '_tribe_aggregator_queue', '_tribe_importer_original_url',
            '_tribe_legacy_ignored_event', '_VenueOrigin', '_VenueEventBriteID', '_OrganizerEventBriteID',
        ],
    ],
];
