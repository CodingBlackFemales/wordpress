import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __experimentalGetSettings, dateI18n, date } from '@wordpress/date';
import {
	TextControl,
	TextareaControl,
	PanelBody,
	Popover,
	DateTimePicker,
	DatePicker,
	Button,
	__experimentalText as Text,
	SelectControl,
	CheckboxControl,
	Placeholder,
	BaseControl,
	RadioControl
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { doAction, addAction } from '@wordpress/hooks';
import { differenceBy, camelCase, isUndefined } from 'lodash';
import moment from 'moment';
import { zoomMeetingFetch, zoomWebinarFetch } from './request';
import SelectDropdown from './SelectDropdown';

const moment_default_datetime_format = 'YYYY-MM-DD HH:mm:ss';

/**
 * Returns whether buddyboss category is in editor cats list or not
 *
 * @return {boolean} true if category is in list.
 */
export const isBuddyBossInCategories = () => {
	const blockCategories = wp.blocks.getCategories();
	for ( var i in blockCategories ) {
		if ( 'buddyboss' === blockCategories[i].slug ) {
			return true;
		}
	}
	return false;
};

const currentDateTime = new Date( bpZoomMeetingBlock.wp_date_time );
currentDateTime.setMinutes( currentDateTime.getMinutes() + ( 60 - currentDateTime.getMinutes() ) );

registerBlockType( 'bp-zoom-meeting/create-meeting', {
	title: bpZoomMeetingBlock.block_zoom_meeting,
	description: bpZoomMeetingBlock.block_create_meeting_in_zoom,
	icon: 'video-alt2',
	category: isBuddyBossInCategories() ? 'buddyboss' : 'common',
	keywords: [ __( 'zoom', 'buddyboss-pro' ), __( 'meeting', 'buddyboss-pro' ) ],
	supports: {
		html: false,
		reusable: false,
	},

	attributes: {
		id: {
			type: 'number',
			default: ''
		},
		meetingId: {
			type: 'number',
			default: ''
		},
		hostId: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.default_host_id !== 'undefined' ? bpZoomMeetingBlock.default_host_id : ''
		},
		hostDisplayName: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.default_host_user !== 'undefined' ? bpZoomMeetingBlock.default_host_user : ''
		},
		alt_hosts: {
			type: 'string',
			default: ''
		},
		title: {
			type: 'string',
			default: ''
		},
		description: {
			type: 'string',
			default: ''
		},
		startDate: {
			type: 'string',
			default: moment( currentDateTime ).format( moment_default_datetime_format )
		},
		duration: {
			type: 'string',
			default: '30'
		},
		timezone: {
			type: 'string',
			default: typeof bpZoomMeetingBlock.wp_timezone !== 'undefined' ? bpZoomMeetingBlock.wp_timezone : ''
		},
		password: {
			type: 'string',
			default: ''
		},
		registration: {
			type: 'boolean',
			default: false
		},
		registration_type: {
			type: 'number',
			default: 1
		},
		hostVideo: {
			type: 'boolean',
			default: false
		},
		participantsVideo: {
			type: 'boolean',
			default: false
		},
		joinBeforeHost: {
			type: 'boolean',
			default: false
		},
		muteParticipants: {
			type: 'boolean',
			default: false
		},
		waitingRoom: {
			type: 'boolean',
			default: false
		},
		authentication: {
			type: 'boolean',
			default: false
		},
		autoRecording: {
			type: 'string',
			default: 'none'
		},
		recurring: {
			type: 'boolean',
			default: false
		},
		recurrence: {
			type: 'number',
			default: 1
		},
		repeat_interval: {
			type: 'number',
			default: 1
		},
		end_time_select: {
			type: 'string',
			default: 'date'
		},
		end_times: {
			type: 'number',
			default: 7
		},
		end_date_time: {
			type: 'string',
			default: moment( new Date().setDate( new Date( bpZoomMeetingBlock.wp_date_time ).getDate() + 6 ) ).format( moment_default_datetime_format )
		},
		weekly_days: {
			type: 'array',
			default: ['4']
		},
		monthly_occurs_on: {
			type: 'string',
			default: 'day'
		},
		monthly_day: {
			type: 'number',
			default: 1
		},
		monthly_week: {
			type: 'number',
			default: 1
		},
		monthly_week_day: {
			type: 'number',
			default: 1
		},
		occurrences: {
			type: 'array',
			default: []
		},
		current_occurrence: {
			type: 'object',
			default: {}
		},
		occurrence_edit: {
			type: 'boolean',
			default: false
		},
		current_occurrence_start_time: {
			type: 'string',
			default: ''
		},
		current_occurrence_duration: {
			type: 'number',
			default: 0
		},
		meetingFormType: {
			type: 'string',
			default: ''
		},
		external_meeting: {
			type: 'boolean',
			default: false
		}
	},

	edit: ( props ) => {
		const { clientId, setAttributes } = props;
		const host_user_type = typeof bpZoomMeetingBlock.default_host_user_type !== 'undefined' ? bpZoomMeetingBlock.default_host_user_type : 1;
		const {
			meetingId,
			hostId,
			hostDisplayName,
			title,
			description,
			startDate,
			duration,
			timezone,
			password,
			registration,
			registration_type,
			hostVideo,
			participantsVideo,
			joinBeforeHost,
			muteParticipants,
			waitingRoom,
			authentication,
			autoRecording,
			meetingFormType,
			alt_hosts,
			external_meeting,
			recurring,
			recurrence,
			repeat_interval,
			end_times,
			end_date_time,
			end_time_select,
			weekly_days,
			monthly_occurs_on,
			monthly_day,
			monthly_week,
			monthly_week_day,
			occurrences,
			current_occurrence,
			occurrence_edit,
			current_occurrence_start_time,
			current_occurrence_duration
		} = props.attributes;

		let repeat_interval_options = [], repeat_every = __('day', 'buddyboss-pro'),
			start_date_dt = new Date(startDate),
			end_date_dt = new Date();

		const setMeetingId = ( val ) => {
			let reg = new RegExp( '^\\d+$' );
			if ( '' !== val && reg.test(val) ) {
				val = parseInt( val.toString().replace( /\s/g, '' ) );
			}
			setAttributes( { meetingId: val } );
		}
		const setHostId = ( val ) => {
			setAttributes( { hostId: val } );
		}
		const setHostDisplayName = ( val ) => {
			setAttributes( { hostDisplayName: val } );
		}
		const setTitle = ( val ) => {
			setAttributes( { title: val } );
		}
		const setDescription = ( val ) => {
			setAttributes( { description: val } );
		}
		const setStartDate = ( val ) => {
			let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
			let selectedDate = new Date( val );
			if ( nowDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { startDate: val } );

				let end_date_time_date = new Date( end_date_time );

				if ( selectedDate.getTime() >= end_date_time_date.getTime() ) {
					let start_date_dt_val = new Date( val );

					if ( recurrence === 1 ) {
						start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * repeat_interval ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					} else if ( recurrence === 2 ) {
						start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					} else if ( recurrence === 3 ) {
						start_date_dt_val.setMonth( start_date_dt_val.getMonth() + ( 6 * repeat_interval ) );
						setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
					}
				}
			}
		}
		const setDuration = ( val ) => {
			setAttributes( { duration: val } );
		}
		const setTimezone = ( val ) => {
			setAttributes( { timezone: val } );

			var currentDateTimeZoneWise = new Date( new Date().toLocaleString( 'en-US', { timeZone: val } ) );
			var month = '' + (currentDateTimeZoneWise.getMonth() + 1),
				day = '' + currentDateTimeZoneWise.getDate(),
				year = currentDateTimeZoneWise.getFullYear(),
				hour = '' + currentDateTimeZoneWise.getHours(),
				minutes = '' + currentDateTimeZoneWise.getMinutes(),
				seconds = '' + currentDateTimeZoneWise.getSeconds();

			if (month.length < 2) {
				month = '0' + month;
			}
			if (day.length < 2) {
				day = '0' + day;
			}
			if (hour.length < 2) {
				hour = '0' + hour;
			}
			if (minutes.length < 2) {
				minutes = '0' + minutes;
			}
			if (seconds.length < 2) {
				seconds = '0' + seconds;
			}

			bpZoomMeetingBlock.wp_date_time = [year, month, day].join('-') + 'T' + [hour,minutes,seconds].join(':');

			var currentStartDateObj = new Date( startDate );
			if ( meetingId.length === 0 && currentStartDateObj < currentDateTimeZoneWise ) {
				setAttributes( { startDate: bpZoomMeetingBlock.wp_date_time } );
			}
		}
		const setPassword = ( val ) => {
			setAttributes( { password: val } );
		}
		const setRegistration = ( val ) => {
			setAttributes( { registration: val } );
		}
		const setRegistrationType = ( val ) => {
			setAttributes( { registration_type: parseInt( val ) } );
		}
		const setHostVideo = ( val ) => {
			setAttributes( { hostVideo: val } );
		}
		const setParticipantsVideo = ( val ) => {
			setAttributes( { participantsVideo: val } );
		}
		const setJoinBeforeHost = ( val ) => {
			setAttributes( { joinBeforeHost: val } );
		}
		const setMuteParticipants = ( val ) => {
			setAttributes( { muteParticipants: val } );
		}
		const setWaitingRoom = ( val ) => {
			setAttributes( { waitingRoom: val } );
		}
		const setAuthentication = ( val ) => {
			setAttributes( { authentication: val } );
		}
		const setAutoRecording = ( val ) => {
			setAttributes( { autoRecording: val } );
		}
		const setMeetingFormType = ( val ) => {
			setAttributes( { meetingFormType: val } );
		}
		const setRecurring = ( val ) => {
			setAttributes( { recurring: val } );
		}
		const setRecurrence = ( val ) => {
			setAttributes( { recurrence: parseInt( val ) } );

			if ( val == 1 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * repeat_interval ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( val == 2 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( val == 3 ) {
				end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * repeat_interval ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			}
		}
		const setRepeatInterval = ( val ) => {
			setAttributes( { repeat_interval: val } );

			if ( recurrence === 1 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * val ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( recurrence === 2 ) {
				end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * val ) ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			} else if ( recurrence === 3 ) {
				end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * val ) );
				setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
			}
		}
		const setEndTimes = ( val ) => {
			setAttributes( { end_times: parseInt( val ) } );
		}
		const setEndDateTime = ( val ) => {
			let meetingDate = new Date( startDate );
			let selectedDate = new Date( val );
			if ( meetingDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { end_date_time: val } );
			}
		}
		const setOccurrenceStartTime = ( start_time ) => {
			let nowDate = new Date();
			let selectedDate = new Date( start_time );
			if ( nowDate.getTime() < selectedDate.getTime() ) {
				setAttributes( { current_occurrence_start_time: start_time } );
			}
		}
		const setOccurrenceDuration = ( duration_val ) => {
			setAttributes( { current_occurrence_duration: duration_val } );
		}
		const setEndTimeSelect = ( val ) => {
			setAttributes( { end_time_select: val } );
		}
		const setWeeklyDays = ( val ) => {
			setAttributes( { weekly_days: val } );
		}
		const setMonthlyOccursOn = ( val ) => {
			setAttributes( { monthly_occurs_on: val } );
		}
		const setMonthlyDay = ( val ) => {
			setAttributes( { monthly_day: parseInt( val ) } );
		}
		const setMonthlyWeek = ( val ) => {
			setAttributes( { monthly_week: val } );
		}
		const setMonthlyWeekDay = ( val ) => {
			setAttributes( { monthly_week_day: parseInt( val ) } );
		}
		const setOccurrences = ( val ) => {
			setAttributes( { occurrences: val } );

			for ( let o in val ) {
				let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
				let selectedDate = new Date( val[o].start_time );
				if ( nowDate.getTime() < selectedDate.getTime() && 'deleted' !== val[o].status ) {
					setStartDate( val[o].start_time );
					break;
				}
			}
		}
		const setOccurrenceEdit = ( val ) => {
			setAttributes( { occurrence_edit: val } );
		}
		const setCurrentOccurrence = ( val ) => {
			setAttributes( { current_occurrence: val } );
			setOccurrenceDuration(val.duration);
			setOccurrenceStartTime(val.start_time);
		}
		const setAltHosts = ( val ) => {
			setAttributes( { alt_hosts: val } );
		}
		const setExternalMeeting = ( val ) => {
			setAttributes( { external_meeting: val } );
		}
		const settings = __experimentalGetSettings();
		const [ isPickerOpen, setIsPickerOpen ] = useState( false );
		const [ isRecurrencePickerOpen, setIsRecurrencePickerOpen ] = useState( false );

		const resolvedFormat = settings.formats.datetime || 'Y-m-d H:i:s';

		let auto_recording_options = [];

		if ( host_user_type == 2 ) {
			auto_recording_options = [
				{ label: bpZoomMeetingBlock.block_zoom_no_recordings, value: 'none' },
				{ label: bpZoomMeetingBlock.block_zoom_cloud, value: 'cloud' },
				{ label: bpZoomMeetingBlock.block_zoom_local, value: 'local' },
			];
		} else {
			auto_recording_options = [
				{ label: bpZoomMeetingBlock.block_zoom_no_recordings, value: 'none' },
				{ label: bpZoomMeetingBlock.block_zoom_local, value: 'local' },
			];
		}

		if ( recurrence === 1 ) {
			repeat_every = __( 'day', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 15; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		} else if ( recurrence === 2 ) {
			repeat_every = __( 'week', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 12; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		} else if ( recurrence === 3 ) {
			repeat_every = __( 'month', 'buddyboss-pro' );
			repeat_interval_options = [];
			for ( let i = 1; i <= 3; i++ ) {
				repeat_interval_options.push( { label: i, value: i } );
			}
		}

		const setMeetingObject = ( response ) => {
			if ( ! isUndefined( response.host_name ) ) {
				setHostDisplayName( response.host_name );
			}
			if ( ! isUndefined( response.host_email ) ) {
				setHostId( response.host_email );
			}
			if ( ! isUndefined( response.meeting ) ) {
				let meeting = response.meeting;
				if ( ! isUndefined( meeting.id ) ) {
					setMeetingId( meeting.id );
				}
				if ( ! isUndefined( meeting.host_id ) ) {
					setHostId( meeting.host_id );
				}
				if ( ! isUndefined( meeting.topic ) ) {
					setTitle( meeting.topic );
				}
				if ( ! isUndefined( meeting.agenda ) ) {
					setDescription( meeting.agenda );
				}
				if ( ! isUndefined( meeting.timezone ) ) {
					setTimezone( meeting.timezone );
				}
				if ( ! isUndefined( meeting.start_time ) ) {
					setAttributes( { startDate: meeting.start_time } );
				}
				if ( ! isUndefined( meeting.duration ) ) {
					setDuration( meeting.duration );
				} else if ( ! isUndefined( meeting.occurrences ) && meeting.occurrences.length ) {
					setDuration( meeting.occurrences[0].duration );
				}
				if ( ! isUndefined( meeting.password ) ) {
					setPassword( meeting.password );
				}
				if ( ! isUndefined( meeting.type ) && [ 3, 8 ].includes( meeting.type ) ) {
					setRecurring( true );
				} else {
					setRecurring( false );
				}
				if ( ! isUndefined( meeting.occurrences ) && meeting.occurrences.length ) {
					setOccurrences( meeting.occurrences );
				}
				if ( ! isUndefined( meeting.recurrence ) ) {
					let meeting_recurrence = meeting.recurrence;
					if ( ! isUndefined( meeting_recurrence.type ) ) {
						setRecurrence( meeting_recurrence.type );
					}
					if ( ! isUndefined( meeting_recurrence.repeat_interval ) ) {
						setRepeatInterval( meeting_recurrence.repeat_interval );
					}
					if ( ! isUndefined( meeting_recurrence.end_times ) ) {
						setEndTimes( meeting_recurrence.end_times );
						setEndTimeSelect( 'times' );
					}
					if ( ! isUndefined( meeting_recurrence.end_date_time ) ) {
						setEndDateTime( meeting_recurrence.end_date_time );
						setEndTimeSelect( 'date' );
					}
					if ( ! isUndefined( meeting_recurrence.weekly_days ) ) {
						setWeeklyDays( meeting_recurrence.weekly_days.split( ',' ) );
					}
					if ( ! isUndefined( meeting_recurrence.monthly_day ) ) {
						setMonthlyDay( meeting_recurrence.monthly_day );
						setMonthlyOccursOn( 'day' );
					}
					if ( ! isUndefined( meeting_recurrence.monthly_week ) ) {
						setMonthlyWeek( meeting_recurrence.monthly_week );
						setMonthlyOccursOn( 'week' );
					}
					if ( ! isUndefined( meeting_recurrence.monthly_week_day ) ) {
						setMonthlyWeekDay( meeting_recurrence.monthly_week_day );
						setMonthlyOccursOn( 'week' );
					}
				}
				if ( ! isUndefined( meeting.settings ) ) {
					let meeting_settings = meeting.settings;
					if ( ! isUndefined( meeting_settings.alternative_hosts ) ) {
						setAltHosts( meeting_settings.alternative_hosts );
					}
					if ( ! isUndefined( meeting_settings.approval_type ) && 0 == meeting_settings.approval_type ) {
						setRegistration( true );
					}
					if ( ! isUndefined( meeting_settings.registration_type ) ) {
						setRegistrationType( meeting_settings.registration_type );
					}
					if ( ! isUndefined( meeting_settings.host_video ) ) {
						setHostVideo( meeting_settings.host_video );
					}
					if ( ! isUndefined( meeting_settings.participant_video ) ) {
						setParticipantsVideo( meeting_settings.participant_video );
					}
					if ( ! isUndefined( meeting_settings.join_before_host ) ) {
						setJoinBeforeHost( meeting_settings.join_before_host );
					}
					if ( ! isUndefined( meeting_settings.mute_upon_entry ) ) {
						setMuteParticipants( meeting_settings.mute_upon_entry );
					}
					if ( ! isUndefined( meeting_settings.waiting_room ) ) {
						setWaitingRoom( meeting_settings.waiting_room );
					}
					if ( ! isUndefined( meeting_settings.meeting_authentication ) ) {
						setAuthentication( meeting_settings.meeting_authentication );
					}
					if ( ! isUndefined( meeting_settings.auto_recording ) ) {
						setAutoRecording( meeting_settings.auto_recording );
					}
				}
			}
		};

		return (
			<>
				{'' === meetingFormType ?
					<Placeholder
						icon="video-alt2"
						className="bb-input-container meeting_block_title"
						label={bpZoomMeetingBlock.block_zoom_meeting}
						instructions={bpZoomMeetingBlock.block_create_add_zoom}
					>

						<Button isSecondary onClick={() => {
							setMeetingFormType( 'create' )
						}}>
							{bpZoomMeetingBlock.block_create_zoom_meeting}
						</Button>
						<Button isSecondary onClick={() => {
							setMeetingFormType( 'existing' )
						}}>
							{bpZoomMeetingBlock.block_add_zoom_meeting}
						</Button>
					</Placeholder>
					: ''
				}
				{'existing' === meetingFormType ?
					<>
						<Placeholder icon="video-alt2" className="bb-meeting-id-input-container"
						             label={bpZoomMeetingBlock.block_add_zoom_meeting}>
							<TextControl
								label={bpZoomMeetingBlock.block_zoom_meeting_id}
								value={meetingId}
								className="components-placeholder__input bb-meeting-id-wrap"
								placeholder={bpZoomMeetingBlock.block_enter_meeting_id}
								onChange={setMeetingId}
							/>
							<BaseControl
								className="bb-buttons-wrap"
							>
								<Button isPrimary onClick={( e ) => {
									var target = e.target;
									target.setAttribute( 'disabled', true );
									const meeting_data = {
										'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
										'bp-zoom-meeting-id': meetingId,
									};

									zoomMeetingFetch( meeting_data ).then( ( response ) => {
										target.removeAttribute( 'disabled' );
										target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i>';
										wp.data.dispatch( 'core/notices' ).createNotice(
											'success',
											bpZoomMeetingBlock.block_meeting_synced,
											{
												isDismissible: true,
											}
										);

										// Set meeting object.
										setMeetingObject( response );

										// Set meeting form to create.
										setMeetingFormType( 'create' );

										//Set external meeting to true.
										setExternalMeeting( true );

										// Save the post.
										var editorInfo = wp.data.select( 'core/editor' );
										if ( editorInfo.isEditedPostSaveable() ) {
											if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
												wp.data.dispatch( 'core/editor' ).autosave();
											} else {
												wp.data.dispatch( 'core/editor' ).savePost();
											}
										}
									} )
										.catch( ( error ) => {
											target.removeAttribute( 'disabled' );
											wp.data.dispatch( 'core/notices' ).createNotice(
												'error',
												error.error,
												{
													isDismissible: true,
												}
											);
										} );
								}}>
									{bpZoomMeetingBlock.block_zoom_save}
								</Button>
								{meetingId < 1 || '' === meetingId ?
									<Button isTertiary onClick={() => {
										setMeetingFormType( '' )
									}}>
										{bpZoomMeetingBlock.block_zoom_cancel}
									</Button>
									:
									''
								}
							</BaseControl>
						</Placeholder>

					</>
					:
					''
				}
				{'create' === meetingFormType ?
					<>
						<Placeholder icon="video-alt2" label={
							!external_meeting ?
								bpZoomMeetingBlock.block_create_zoom_meeting
								:
								bpZoomMeetingBlock.block_existing_meeting
						}
						             className="bp-meeting-block-create">
							{meetingId > 1 || '' !== meetingId ?
								<Button isLink onClick={(e) => {
									var target = e.currentTarget;
									target.innerHTML = '<i class="bb-icon-l bb-icon-spinner animate-spin"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
									target.setAttribute( 'disabled', true );
									const meeting_data = {
										'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
										'bp-zoom-meeting-id': meetingId,
									};

									zoomMeetingFetch( meeting_data ).then( ( response ) => {
										target.removeAttribute( 'disabled' );
										target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
										wp.data.dispatch( 'core/notices' ).createNotice(
											'success',
											bpZoomMeetingBlock.block_meeting_synced,
											{
												isDismissible: true,
											}
										);

										// Set meeting object.
										setMeetingObject( response );

										// Set meeting form to create.
										setMeetingFormType( 'create' );

										// Save the post.
										var editorInfo = wp.data.select( 'core/editor' );
										if ( editorInfo.isEditedPostSaveable() ) {
											if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
												wp.data.dispatch( 'core/editor' ).autosave();
											} else {
												wp.data.dispatch( 'core/editor' ).savePost();
											}
										}
									} )
										.catch( ( error ) => {
											target.removeAttribute( 'disabled' );
											target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
											wp.data.dispatch( 'core/notices' ).createNotice(
												'error',
												error.error,
												{
													isDismissible: true,
												}
											);
										} );
								}}>
									<i className="bb-icon-l bb-icon-bolt"></i> { bpZoomMeetingBlock.block_zoom_sync }
								</Button>
								:
								''
							}
							<TextControl
								label=''
								type="hidden"
								value={meetingId}
							/>
							<TextControl
								label={bpZoomMeetingBlock.block_zoom_title}
								value={title}
								onChange={setTitle}
							/>
							<BaseControl
								label={bpZoomMeetingBlock.block_zoom_when}
								className="bb-meeting-time-wrap"
							>
								<time dateTime={date( 'c', startDate )}>
									<Button
										icon="edit"
										isTertiary
										isLink
										onClick={() =>
											setIsPickerOpen(
												( _isPickerOpen ) => !_isPickerOpen
											)
										}>
										{moment( startDate ).format('MMMM DD, YYYY h:mm a')}
									</Button>
									{isPickerOpen && (
										<Popover onClose={setIsPickerOpen.bind( null, false )}>
											<DateTimePicker
												currentDate={startDate}
												onChange={setStartDate}
												is12Hour={true}
											/>
										</Popover>
									)}
								</time>
							</BaseControl>
							<div>
								<SelectDropdown
									timezone={timezone}
									options={bpZoomMeetingBlock.timezones}
									setTimezone={setTimezone}
									clientId={clientId}
								/>
							</div>
							<SelectControl
								label={bpZoomMeetingBlock.block_zoom_auto_recording}
								value={autoRecording}
								options={auto_recording_options}
								onChange={setAutoRecording}
								className="bb_inline_selectBox"
							/>
							<BaseControl className="bb-buttons-wrap">
								<Button
									className="submit-meeting"
									isPrimary
									onClick={( e ) => {
										const target = e.target;
										target.setAttribute( 'disabled', true );
										const meeting_data = {
											'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
											'bp-zoom-meeting-zoom-id': meetingId,
											'bp-zoom-meeting-start-date': startDate,
											'bp-zoom-meeting-timezone': timezone,
											'bp-zoom-meeting-duration': duration,
											'bp-zoom-meeting-password': password,
											'bp-zoom-meeting-recording': autoRecording,
											'bp-zoom-meeting-alt-host-ids': alt_hosts,
											'bp-zoom-meeting-title': title,
											'bp-zoom-meeting-description': description,
										};

										meeting_data['bp-zoom-meeting-type'] = 2;

										if ( recurring ) {
											if ( 1 === recurrence ) {
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 1;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else if ( 2 === recurrence ) {
												if ( weekly_days ) {
													meeting_data['bp-zoom-meeting-weekly-days'] = weekly_days;
												}
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 2;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else if ( 3 === recurrence ) {
												if ( 'day' === monthly_occurs_on ) {
													meeting_data['bp-zoom-meeting-monthly-day'] = monthly_day;
												} else if ( 'week' === monthly_occurs_on ) {
													meeting_data['bp-zoom-meeting-monthly-week'] = monthly_week;
													meeting_data['bp-zoom-meeting-monthly-week-day'] = monthly_week_day;
												}
												if ( 'date' === end_time_select ) {
													meeting_data['bp-zoom-meeting-end-date-time'] = end_date_time;
												} else {
													meeting_data['bp-zoom-meeting-end-times'] = end_times;
												}
												meeting_data['bp-zoom-meeting-recurrence'] = 3;
												meeting_data['bp-zoom-meeting-monthly-occurs-on'] = monthly_occurs_on;
												meeting_data['bp-zoom-meeting-end-time-select'] = end_time_select;
												meeting_data['bp-zoom-meeting-repeat-interval'] = repeat_interval;
												meeting_data['bp-zoom-meeting-type'] = 8;
											} else {
												meeting_data['bp-zoom-meeting-type'] = 3;
											}
										}

										if ( registration ) {
											meeting_data['bp-zoom-meeting-registration'] = 1;
											if ( meeting_data['bp-zoom-meeting-type'] === 8 ) {
												meeting_data['bp-zoom-meeting-registration-type'] = registration_type;
											}
										}

										if ( joinBeforeHost ) {
											meeting_data['bp-zoom-meeting-join-before-host'] = 1;
										}

										if ( hostVideo ) {
											meeting_data['bp-zoom-meeting-host-video'] = 1;
										}

										if ( participantsVideo ) {
											meeting_data['bp-zoom-meeting-participants-video'] = 1;
										}

										if ( muteParticipants ) {
											meeting_data['bp-zoom-meeting-mute-participants'] = 1;
										}

										if ( waitingRoom ) {
											meeting_data['bp-zoom-meeting-waiting-room'] = 1;
										}

										if ( authentication ) {
											meeting_data['bp-zoom-meeting-authentication'] = 1;
										}

										wp.ajax.send( 'zoom_meeting_block_add', {
											data: meeting_data,
											success: function ( response ) {
												if ( response.meeting.id ) {
													setMeetingId( response.meeting.id );
												}
												if ( typeof response.meeting.occurrences !== 'undefined' && response.meeting.occurrences.length ) {
													setOccurrences( response.meeting.occurrences );
												}
												target.removeAttribute( 'disabled' );
												wp.data.dispatch( 'core/notices' ).createNotice(
													'success', // Can be one of: success, info, warning, error.
													bpZoomMeetingBlock.block_meeting_updated, // Text string to display.
													{
														isDismissible: true, // Whether the user can dismiss the notice.
													}
												);
												setMeetingFormType( 'create' );
												//save post if is ok to save
												var editorInfo = wp.data.select( 'core/editor' );
												if ( editorInfo.isEditedPostSaveable() ) {
													if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
														wp.data.dispatch( 'core/editor' ).autosave();
													} else {
														wp.data.dispatch( 'core/editor' ).savePost();
													}
												}
											},
											error: function ( error ) {
												target.removeAttribute( 'disabled' );
												if ( typeof error.errors !== 'undefined' ) {
													for ( let er in error.errors ) {
														wp.data.dispatch( 'core/notices' ).createNotice(
															'error',
															error.errors[er].message, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
													}
												} else {
													wp.data.dispatch( 'core/notices' ).createNotice(
														'error', // Can be one of: success, info, warning, error.
														error.error, // Text string to display.
														{
															isDismissible: true, // Whether the user can dismiss the notice.
														}
													);
												}
											}
										} );
									}
									}>
									{bpZoomMeetingBlock.block_zoom_save_meeting}
								</Button>
								{meetingId < 1 || '' === meetingId ?
									<Button isTertiary onClick={() => {
										setMeetingFormType( '' )
									}}>
										{bpZoomMeetingBlock.block_zoom_cancel}
									</Button>
									:
									<Button isDestructive onClick={(e) => {
										const target = e.target;
										if ( confirm( 'Are you sure you want to delete this meeting?', 'buddyboss-pro' ) ) {

											target.setAttribute( 'disabled', true );
											const meeting_data = {
												'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
												'bp-zoom-meeting-zoom-id': meetingId,
											};

											wp.ajax.send( 'zoom_meeting_block_delete_meeting', {
												data: meeting_data,
												success: function () {
													wp.data.dispatch('core/block-editor').removeBlock(clientId);
													target.removeAttribute( 'disabled' );
													wp.data.dispatch( 'core/notices' ).createNotice(
														'success', // Can be one of: success, info, warning, error.
														bpZoomMeetingBlock.block_meeting_deleted, // Text string to display.
														{
															isDismissible: true, // Whether the user can dismiss the notice.
														}
													);
													var editorInfo = wp.data.select( 'core/editor' );
													// save post if is ok to save
													if ( editorInfo.isEditedPostSaveable() ) {
														if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
															wp.data.dispatch( 'core/editor' ).autosave();
														} else {
															wp.data.dispatch( 'core/editor' ).savePost();
														}
													}
												},
												error: function ( error ) {
													target.removeAttribute( 'disabled' );
													if ( typeof error.errors !== 'undefined' ) {
														for ( let er in error.errors ) {
															wp.data.dispatch( 'core/notices' ).createNotice(
																'error',
																error.errors[er].message, // Text string to display.
																{
																	isDismissible: true, // Whether the user can dismiss the notice.
																}
															);
														}
													} else {
														wp.data.dispatch( 'core/notices' ).createNotice(
															'error', // Can be one of: success, info, warning, error.
															error.error, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
													}
												}
											} );
										}
									}}>
										{bpZoomMeetingBlock.block_zoom_delete}
									</Button>
								}
							</BaseControl>
						</Placeholder>
					</>
					:
					''
				}
				{'create' === meetingFormType ?
					<InspectorControls>
						<PanelBody
							title={bpZoomMeetingBlock.block_zoom_settings}
							initialOpen={true}>
							<TextareaControl
								label={bpZoomMeetingBlock.block_zoom_description}
								value={description}
								onChange={setDescription}
							/>
							<TextControl
								label={bpZoomMeetingBlock.block_zoom_passcode}
								onChange={setPassword}
								value={password}
							/>
							<TextControl
								type="number"
								label={bpZoomMeetingBlock.block_zoom_duration}
								onChange={setDuration}
								value={duration}
							/>
							<TextControl
								label={bpZoomMeetingBlock.block_zoom_default_host}
								type="text"
								disabled
								value={hostDisplayName}
							/>
							{
								host_user_type == 2
									?
									<TextControl
										label={bpZoomMeetingBlock.block_zoom_alt_hosts}
										onChange={setAltHosts}
										value={alt_hosts}
										placeholder={bpZoomMeetingBlock.block_zoom_example}
										help={bpZoomMeetingBlock.block_zoom_email_enter}
									/>
									:
									''
							}
							<CheckboxControl
								label={bpZoomMeetingBlock.block_zoom_start_video}
								checked={hostVideo}
								onChange={setHostVideo}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={bpZoomMeetingBlock.block_zoom_start_video_par}
								checked={participantsVideo}
								onChange={setParticipantsVideo}
								className="bb-checkbox-wrap"
							/>
							{
								host_user_type == 2
									?
									<>
										<CheckboxControl
											label={bpZoomMeetingBlock.block_zoom_require_reg}
											checked={registration}
											onChange={setRegistration}
											className="bb-checkbox-wrap"
										/>
										{
											registration && recurring
												?
												<>
													<RadioControl
														selected={ registration_type }
														options={ [
															{ label: bpZoomMeetingBlock.block_att_any, value: 1 },
															{ label: bpZoomMeetingBlock.block_att_each, value: 2 },
															{ label: bpZoomMeetingBlock.block_att_choose, value: 3 },
														] }
														onChange={setRegistrationType}
													/>
												</>
												:
												''
										}
									</>
									:
									''
							}
							<CheckboxControl
								label={bpZoomMeetingBlock.block_enable_join_before}
								checked={joinBeforeHost}
								onChange={setJoinBeforeHost}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={bpZoomMeetingBlock.block_mute_part}
								checked={muteParticipants}
								onChange={setMuteParticipants}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={bpZoomMeetingBlock.block_enable_wait_room}
								checked={waitingRoom}
								onChange={setWaitingRoom}
								className="bb-checkbox-wrap"
							/>
							<CheckboxControl
								label={bpZoomMeetingBlock.block_only_auth}
								checked={authentication}
								onChange={setAuthentication}
								className="bb-checkbox-wrap"
							/>
						</PanelBody>
						<PanelBody
							title={bpZoomMeetingBlock.block_recurring_options}
							initialOpen={false}>
							<CheckboxControl
								label={bpZoomMeetingBlock.block_recurring_meeting}
								checked={recurring}
								onChange={setRecurring}
							/>
							{true === recurring ?
								<>
									<SelectControl
										label={bpZoomMeetingBlock.block_recurrence}
										value={recurrence}
										options={[
											{ label: bpZoomMeetingBlock.block_zoom_week_daily, value: 1 },
											{ label: bpZoomMeetingBlock.block_zoom_week_weekly, value: 2 },
											{ label: bpZoomMeetingBlock.block_zoom_week_monthly, value: 3 },
										]}
										onChange={setRecurrence}
									/>
									<SelectControl
										label={bpZoomMeetingBlock.block_repeat_every}
										value={repeat_interval}
										options={repeat_interval_options}
										onChange={setRepeatInterval}
										help={repeat_every}
									/>
									{2 === recurrence
										?
										<SelectControl
											label={bpZoomMeetingBlock.block_zoom_days}
											value={weekly_days}
											options={[
												{ label: bpZoomMeetingBlock.block_zoom_sunday, value: 1 },
												{ label: bpZoomMeetingBlock.block_zoom_monday, value: 2 },
												{ label: bpZoomMeetingBlock.block_zoom_tuesday, value: 3 },
												{ label: bpZoomMeetingBlock.block_zoom_wednesday, value: 4 },
												{ label: bpZoomMeetingBlock.block_zoom_thursday, value: 5 },
												{ label: bpZoomMeetingBlock.block_zoom_friday, value: 6 },
												{ label: bpZoomMeetingBlock.block_zoom_saturday, value: 7 },
											]}
											onChange={setWeeklyDays}
											multiple
										/>
										:
										''
									}
									{3 === recurrence
										?
										<>
											<SelectControl
												label={bpZoomMeetingBlock.block_zoom_occures}
												value={monthly_occurs_on}
												options={[
													{ label: bpZoomMeetingBlock.block_zoom_day_month, value: 'day' },
													{
														label: bpZoomMeetingBlock.block_zoom_week_month,
														value: 'week'
													},
												]}
												onChange={setMonthlyOccursOn}
											/>
											{'day' === monthly_occurs_on
												?
												<SelectControl
													label={bpZoomMeetingBlock.block_zoom_day}
													value={monthly_day}
													options={[
														{ label: __( '1', 'buddyboss-pro' ), value: 1 },
														{ label: __( '2', 'buddyboss-pro' ), value: 2 },
														{ label: __( '3', 'buddyboss-pro' ), value: 3 },
														{ label: __( '4', 'buddyboss-pro' ), value: 4 },
														{ label: __( '5', 'buddyboss-pro' ), value: 5 },
														{ label: __( '6', 'buddyboss-pro' ), value: 6 },
														{ label: __( '7', 'buddyboss-pro' ), value: 7 },
														{ label: __( '8', 'buddyboss-pro' ), value: 8 },
														{ label: __( '9', 'buddyboss-pro' ), value: 9 },
														{ label: __( '10', 'buddyboss-pro' ), value: 10 },
														{ label: __( '11', 'buddyboss-pro' ), value: 11 },
														{ label: __( '12', 'buddyboss-pro' ), value: 12 },
														{ label: __( '13', 'buddyboss-pro' ), value: 13 },
														{ label: __( '14', 'buddyboss-pro' ), value: 14 },
														{ label: __( '15', 'buddyboss-pro' ), value: 15 },
														{ label: __( '16', 'buddyboss-pro' ), value: 16 },
														{ label: __( '17', 'buddyboss-pro' ), value: 17 },
														{ label: __( '18', 'buddyboss-pro' ), value: 18 },
														{ label: __( '19', 'buddyboss-pro' ), value: 19 },
														{ label: __( '20', 'buddyboss-pro' ), value: 20 },
														{ label: __( '21', 'buddyboss-pro' ), value: 21 },
														{ label: __( '22', 'buddyboss-pro' ), value: 22 },
														{ label: __( '23', 'buddyboss-pro' ), value: 23 },
														{ label: __( '24', 'buddyboss-pro' ), value: 24 },
														{ label: __( '25', 'buddyboss-pro' ), value: 25 },
														{ label: __( '26', 'buddyboss-pro' ), value: 26 },
														{ label: __( '27', 'buddyboss-pro' ), value: 27 },
														{ label: __( '28', 'buddyboss-pro' ), value: 28 },
														{ label: __( '29', 'buddyboss-pro' ), value: 29 },
														{ label: __( '30', 'buddyboss-pro' ), value: 30 },
														{ label: __( '31', 'buddyboss-pro' ), value: 31 },
													]}
													onChange={setMonthlyDay}
													help={bpZoomMeetingBlock.block_zoom_of_month}
												/>
												:
												<>
													<SelectControl
														value={monthly_week}
														options={[
															{ label: bpZoomMeetingBlock.block_zoom_first, value: 1 },
															{ label: bpZoomMeetingBlock.block_zoom_second, value: 2 },
															{ label: bpZoomMeetingBlock.block_zoom_third, value: 3 },
															{ label: bpZoomMeetingBlock.block_zoom_fourth, value: 4 },
															{ label: bpZoomMeetingBlock.block_zoom_last, value: -1 },
														]}
														onChange={setMonthlyWeek}
													/>
													<SelectControl
														value={monthly_week_day}
														options={[
															{ label: bpZoomMeetingBlock.block_zoom_sunday, value: 1 },
															{ label: bpZoomMeetingBlock.block_zoom_monday, value: 2 },
															{ label: bpZoomMeetingBlock.block_zoom_tuesday, value: 3 },
															{ label: bpZoomMeetingBlock.block_zoom_wednesday, value: 4 },
															{ label: bpZoomMeetingBlock.block_zoom_thursday, value: 5 },
															{ label: bpZoomMeetingBlock.block_zoom_friday, value: 6 },
															{ label: bpZoomMeetingBlock.block_zoom_saturday, value: 7 },
														]}
														onChange={setMonthlyWeekDay}
														help={bpZoomMeetingBlock.block_zoom_of_month}
													/>
												</>
											}
										</>
										:
										''
									}
									{4 !== recurrence
										?
										<>
											<SelectControl
												label={bpZoomMeetingBlock.block_zoom_end_by}
												value={end_time_select}
												options={[
													{ label: bpZoomMeetingBlock.block_zoom_date, value: 'date' },
													{ label: bpZoomMeetingBlock.block_zoom_occurrences, value: 'times' },
												]}
												onChange={setEndTimeSelect}
											/>
											{'date' == end_time_select ?
												<time dateTime={date( 'c', end_date_time )}>
													<Button
														icon="edit"
														isTertiary
														isLink
														onClick={() =>
															setIsRecurrencePickerOpen(
																( isRecurrencePickerOpen ) => !isRecurrencePickerOpen
															)
														}>
														{moment( end_date_time ).format('MMMM DD, YYYY')}
													</Button>
													{isRecurrencePickerOpen && (
														<Popover
															onClose={setIsRecurrencePickerOpen.bind( null, false )}>
															<DatePicker
																currentDate={end_date_time}
																onChange={setEndDateTime}
															/>
														</Popover>
													)}
												</time>
												:
												''
											}
											{'times' == end_time_select ?
												<SelectControl
													label={bpZoomMeetingBlock.block_zoom_end_after}
													value={end_times}
													help={bpZoomMeetingBlock.block_zoom_occurrences_low}
													options={[
														{ label: __( '1', 'buddyboss-pro' ), value: 1 },
														{ label: __( '2', 'buddyboss-pro' ), value: 2 },
														{ label: __( '3', 'buddyboss-pro' ), value: 3 },
														{ label: __( '4', 'buddyboss-pro' ), value: 4 },
														{ label: __( '5', 'buddyboss-pro' ), value: 5 },
														{ label: __( '6', 'buddyboss-pro' ), value: 6 },
														{ label: __( '7', 'buddyboss-pro' ), value: 7 },
														{ label: __( '8', 'buddyboss-pro' ), value: 8 },
														{ label: __( '9', 'buddyboss-pro' ), value: 9 },
														{ label: __( '10', 'buddyboss-pro' ), value: 10 },
														{ label: __( '11', 'buddyboss-pro' ), value: 11 },
														{ label: __( '12', 'buddyboss-pro' ), value: 12 },
														{ label: __( '13', 'buddyboss-pro' ), value: 13 },
														{ label: __( '14', 'buddyboss-pro' ), value: 14 },
														{ label: __( '15', 'buddyboss-pro' ), value: 15 },
														{ label: __( '16', 'buddyboss-pro' ), value: 16 },
														{ label: __( '17', 'buddyboss-pro' ), value: 17 },
														{ label: __( '18', 'buddyboss-pro' ), value: 18 },
														{ label: __( '19', 'buddyboss-pro' ), value: 19 },
														{ label: __( '20', 'buddyboss-pro' ), value: 20 },
													]}
													onChange={setEndTimes}
												/>
												:
												''
											}
										</>
										:
										''
									}
								</>
								:
								''}
						</PanelBody>
						{true === recurring && occurrences.length ?
							<PanelBody
								title={bpZoomMeetingBlock.block_zoom_occurrences}
								initialOpen={false}>
								{
									occurrences.map( ( occurrence ) => {
										let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
										let selectedDate = new Date( occurrence.start_time );
										if ( nowDate.getTime() > selectedDate.getTime() || 'deleted' === occurrence.status ) {
											return '';
										}
										return <Fragment key={occurrence.occurrence_id}>
											<Text as="p">
												{moment( occurrence.start_time ).format('MMMM DD, YYYY h:mm a')}
											</Text>
											<Button
												isLink
												className="edit-occurrences-button"
												onClick={() => {
													setOccurrenceEdit( true );
													setCurrentOccurrence( occurrence );
												}
												}>
												{bpZoomMeetingBlock.block_zoom_edit}
											</Button>
											<Button isLink="true" className="edit-occurrences-button"
											        onClick={(e) => {
												        const target = e.target;

												        if ( ! confirm( bpZoomMeetingBlock.delete_occurrence_confirm_str ) ) {
												        	return false;
												        }

												        target.setAttribute( 'disabled', true );
												        const meeting_data = {
													        '_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
													        'bp-zoom-meeting-zoom-id': meetingId,
													        'bp-zoom-meeting-occurrence-id': occurrence.occurrence_id,
												        };

												        wp.ajax.send( 'zoom_meeting_block_delete_occurrence', {
													        data: meeting_data,
													        success: function () {
													        	setOccurrences(occurrences.filter(function( obj ) {
															        return obj.occurrence_id !== occurrence.occurrence_id;
														        }));
														        setOccurrenceEdit( false );
														        target.removeAttribute( 'disabled' );
														        wp.data.dispatch( 'core/notices' ).createNotice(
															        'success', // Can be one of: success, info, warning, error.
															        bpZoomMeetingBlock.block_zoom_occurrence_del, // Text string to display.
															        {
																        isDismissible: true, // Whether the user can dismiss the notice.
															        }
														        );
														        var editorInfo = wp.data.select( 'core/editor' );
														        // save post if is ok to save
														        if ( editorInfo.isEditedPostSaveable() ) {
															        if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																        wp.data.dispatch( 'core/editor' ).autosave();
															        } else {
																        wp.data.dispatch( 'core/editor' ).savePost();
															        }
														        }
													        },
													        error: function ( error ) {
														        target.removeAttribute( 'disabled' );
														        if ( typeof error.errors !== 'undefined' ) {
															        for ( let er in error.errors ) {
																        wp.data.dispatch( 'core/notices' ).createNotice(
																	        'error',
																	        error.errors[er].message, // Text string to display.
																	        {
																		        isDismissible: true, // Whether the user can dismiss the notice.
																	        }
																        );
															        }
														        } else {
															        wp.data.dispatch( 'core/notices' ).createNotice(
																        'error', // Can be one of: success, info, warning, error.
																        error.error, // Text string to display.
																        {
																	        isDismissible: true, // Whether the user can dismiss the notice.
																        }
															        );
														        }
													        }
												        } );
											}
											}>
												{bpZoomMeetingBlock.block_zoom_delete}
											</Button>
											{
												occurrence_edit && current_occurrence && current_occurrence.occurrence_id === occurrence.occurrence_id
													?
													<Fragment>
														<DateTimePicker
															is12Hour={true}
															currentDate={current_occurrence_start_time}
															onChange={setOccurrenceStartTime}
														/>
														<TextControl
															type="number"
															label={bpZoomMeetingBlock.block_zoom_duration}
															onChange={setOccurrenceDuration}
															value={current_occurrence_duration}
														/>
														<BaseControl className="bb-buttons-wrap">
															<Button
																isPrimary
																className="submit-meeting"
																onClick={(e) => {
																	const target = e.target;
																	target.setAttribute( 'disabled', true );
																	const meeting_data = {
																		'_wpnonce': bpZoomMeetingBlock.bp_zoom_meeting_nonce,
																		'bp-zoom-meeting-zoom-id': meetingId,
																		'bp-zoom-meeting-occurrence-id': current_occurrence.occurrence_id,
																		'bp-zoom-meeting-start-time': current_occurrence_start_time,
																		'bp-zoom-meeting-timezone': timezone,
																		'bp-zoom-meeting-duration': current_occurrence_duration,
																		'bp-zoom-meeting-recording': autoRecording,
																		'bp-zoom-meeting-alt-host-ids': alt_hosts,
																	};

																	if ( joinBeforeHost ) {
																		meeting_data['bp-zoom-meeting-join-before-host'] = 1;
																	}

																	if ( hostVideo ) {
																		meeting_data['bp-zoom-meeting-host-video'] = 1;
																	}

																	if ( participantsVideo ) {
																		meeting_data['bp-zoom-meeting-participants-video'] = 1;
																	}

																	if ( muteParticipants ) {
																		meeting_data['bp-zoom-meeting-mute-participants'] = 1;
																	}

																	if ( waitingRoom ) {
																		meeting_data['bp-zoom-meeting-waiting-room'] = 1;
																	}

																	if ( authentication ) {
																		meeting_data['bp-zoom-meeting-authentication'] = 1;
																	}

																	wp.ajax.send( 'zoom_meeting_block_update_occurrence', {
																		data: meeting_data,
																		success: function () {
																			for ( var o_index in occurrences ) {
																				if ( occurrences[o_index].occurrence_id === current_occurrence.occurrence_id ) {
																					occurrences[o_index].duration = current_occurrence_duration;
																					occurrences[o_index].start_time = current_occurrence_start_time;
																					break;
																				}
																			}
																			setOccurrences( occurrences );
																			setOccurrenceEdit( false );
																			target.removeAttribute( 'disabled' );
																			wp.data.dispatch( 'core/notices' ).createNotice(
																				'success', // Can be one of: success, info, warning, error.
																				bpZoomMeetingBlock.block_meeting_updated, // Text string to display.
																				{
																					isDismissible: true, // Whether the user can dismiss the notice.
																				}
																			);
																			var editorInfo = wp.data.select( 'core/editor' );
																			// save post if is ok to save
																			if ( editorInfo.isEditedPostSaveable() ) {
																				if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																					wp.data.dispatch( 'core/editor' ).autosave();
																				} else {
																					wp.data.dispatch( 'core/editor' ).savePost();
																				}
																			}
																		},
																		error: function ( error ) {
																			target.removeAttribute( 'disabled' );
																			if ( typeof error.errors !== 'undefined' ) {
																				for ( let er in error.errors ) {
																					wp.data.dispatch( 'core/notices' ).createNotice(
																						'error',
																						error.errors[er].message, // Text string to display.
																						{
																							isDismissible: true, // Whether the user can dismiss the notice.
																						}
																					);
																				}
																			} else {
																				wp.data.dispatch( 'core/notices' ).createNotice(
																					'error', // Can be one of: success, info, warning, error.
																					error.error, // Text string to display.
																					{
																						isDismissible: true, // Whether the user can dismiss the notice.
																					}
																				);
																			}
																		}
																	} );
																}}>
																{bpZoomMeetingBlock.block_zoom_save}
															</Button>
															<Button isTertiary onClick={() => {
																setOccurrenceEdit( false );
															}}>
																{bpZoomMeetingBlock.block_zoom_cancel}
															</Button>
														</BaseControl>
													</Fragment>
													:
													''
											}

										</Fragment>
									} )
								}
							</PanelBody>
							:
							''}
					</InspectorControls>
					:
					''
				}
			</>
		);
	},
} );

if ( bpZoomMeetingBlock.webinar_enabled ) {
	registerBlockType( 'bp-zoom-meeting/create-webinar', {
		title: bpZoomMeetingBlock.block_zoom_webinar,
		description: bpZoomMeetingBlock.block_zoom_create_webinar,
		icon: 'video-alt2',
		category: isBuddyBossInCategories() ? 'buddyboss' : 'common',
		keywords: [ __( 'zoom', 'buddyboss-pro' ), __( 'webinar', 'buddyboss-pro' ) ],
		supports: {
			html: false,
			reusable: false,
		},

		attributes: {
			id: {
				type: 'number',
				default: ''
			},
			webinarId: {
				type: 'number',
				default: ''
			},
			hostId: {
				type: 'string',
				default: typeof bpZoomMeetingBlock.default_host_id !== 'undefined' ? bpZoomMeetingBlock.default_host_id : ''
			},
			hostDisplayName: {
				type: 'string',
				default: typeof bpZoomMeetingBlock.default_host_user !== 'undefined' ? bpZoomMeetingBlock.default_host_user : ''
			},
			alt_hosts: {
				type: 'string',
				default: ''
			},
			title: {
				type: 'string',
				default: ''
			},
			description: {
				type: 'string',
				default: ''
			},
			startDate: {
				type: 'string',
				default: moment( currentDateTime ).format( moment_default_datetime_format )
			},
			duration: {
				type: 'string',
				default: '30'
			},
			timezone: {
				type: 'string',
				default: typeof bpZoomMeetingBlock.wp_timezone !== 'undefined' ? bpZoomMeetingBlock.wp_timezone : ''
			},
			password: {
				type: 'string',
				default: ''
			},
			registration: {
				type: 'boolean',
				default: false
			},
			registration_type: {
				type: 'number',
				default: 1
			},
			hostVideo: {
				type: 'boolean',
				default: false
			},
			panelistsVideo: {
				type: 'boolean',
				default: false
			},
			practiceSession: {
				type: 'boolean',
				default: false
			},
			onDemand: {
				type: 'boolean',
				default: false
			},
			authentication: {
				type: 'boolean',
				default: false
			},
			autoRecording: {
				type: 'string',
				default: 'none'
			},
			recurring: {
				type: 'boolean',
				default: false
			},
			recurrence: {
				type: 'number',
				default: 1
			},
			repeat_interval: {
				type: 'number',
				default: 1
			},
			end_time_select: {
				type: 'string',
				default: 'date'
			},
			end_times: {
				type: 'number',
				default: 7
			},
			end_date_time: {
				type: 'string',
				default: moment( new Date().setDate( new Date( bpZoomMeetingBlock.wp_date_time ).getDate() + 6 ) ).format( moment_default_datetime_format )
			},
			weekly_days: {
				type: 'array',
				default: [ '4' ]
			},
			monthly_occurs_on: {
				type: 'string',
				default: 'day'
			},
			monthly_day: {
				type: 'number',
				default: 1
			},
			monthly_week: {
				type: 'number',
				default: 1
			},
			monthly_week_day: {
				type: 'number',
				default: 1
			},
			occurrences: {
				type: 'array',
				default: []
			},
			current_occurrence: {
				type: 'object',
				default: {}
			},
			occurrence_edit: {
				type: 'boolean',
				default: false
			},
			current_occurrence_start_time: {
				type: 'string',
				default: ''
			},
			current_occurrence_duration: {
				type: 'number',
				default: 0
			},
			webinarFormType: {
				type: 'string',
				default: ''
			},
			external_webinar: {
				type: 'boolean',
				default: false
			}
		},

		edit: ( props ) => {
			const { clientId, setAttributes } = props;
			const host_user_type = typeof bpZoomMeetingBlock.default_host_user_type !== 'undefined' ? bpZoomMeetingBlock.default_host_user_type : 1;
			const private_webinar = typeof bpZoomMeetingBlock.private_webinar !== 'undefined' ? bpZoomMeetingBlock.private_webinar : false;
			const {
				webinarId,
				hostId,
				hostDisplayName,
				title,
				description,
				startDate,
				duration,
				timezone,
				password,
				registration,
				registration_type,
				hostVideo,
				panelistsVideo,
				practiceSession,
				onDemand,
				authentication,
				autoRecording,
				webinarFormType,
				alt_hosts,
				external_webinar,
				recurring,
				recurrence,
				repeat_interval,
				end_times,
				end_date_time,
				end_time_select,
				weekly_days,
				monthly_occurs_on,
				monthly_day,
				monthly_week,
				monthly_week_day,
				occurrences,
				current_occurrence,
				occurrence_edit,
				current_occurrence_start_time,
				current_occurrence_duration
			} = props.attributes;

			let repeat_interval_options = [], repeat_every = __( 'day', 'buddyboss-pro' ),
				start_date_dt = new Date( startDate ),
				end_date_dt = new Date();

			const setWebinarId = ( val ) => {
				let reg = new RegExp( '^\\d+$' );
				if ( '' !== val && reg.test( val ) ) {
					val = parseInt( val.toString().replace( /\s/g, '' ) );
				}
				setAttributes( { webinarId: val } );
			}
			const setHostId = ( val ) => {
				setAttributes( { hostId: val } );
			}
			const setHostDisplayName = ( val ) => {
				setAttributes( { hostDisplayName: val } );
			}
			const setTitle = ( val ) => {
				setAttributes( { title: val } );
			}
			const setDescription = ( val ) => {
				setAttributes( { description: val } );
			}
			const setStartDate = ( val ) => {
				let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
				let selectedDate = new Date( val );
				if ( nowDate.getTime() < selectedDate.getTime() ) {
					setAttributes( { startDate: val } );

					let end_date_time_date = new Date( end_date_time );

					if ( selectedDate.getTime() >= end_date_time_date.getTime() ) {
						let start_date_dt_val = new Date( val );

						if ( recurrence === 1 ) {
							start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * repeat_interval ) );
							setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
						} else if ( recurrence === 2 ) {
							start_date_dt_val.setDate( start_date_dt_val.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
							setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
						} else if ( recurrence === 3 ) {
							start_date_dt_val.setMonth( start_date_dt_val.getMonth() + ( 6 * repeat_interval ) );
							setEndDateTime( moment( start_date_dt_val ).format( moment_default_datetime_format ) );
						}
					}
				}
			}
			const setDuration = ( val ) => {
				setAttributes( { duration: val } );
			}
			const setTimezone = ( val ) => {
				setAttributes( { timezone: val } );

				var currentDateTimeZoneWise = new Date( new Date().toLocaleString( 'en-US', { timeZone: val } ) );
				var month = '' + ( currentDateTimeZoneWise.getMonth() + 1 ),
					day = '' + currentDateTimeZoneWise.getDate(),
					year = currentDateTimeZoneWise.getFullYear(),
					hour = '' + currentDateTimeZoneWise.getHours(),
					minutes = '' + currentDateTimeZoneWise.getMinutes(),
					seconds = '' + currentDateTimeZoneWise.getSeconds();

				if ( month.length < 2 ) {
					month = '0' + month;
				}
				if ( day.length < 2 ) {
					day = '0' + day;
				}
				if ( hour.length < 2 ) {
					hour = '0' + hour;
				}
				if ( minutes.length < 2 ) {
					minutes = '0' + minutes;
				}
				if ( seconds.length < 2 ) {
					seconds = '0' + seconds;
				}

				bpZoomMeetingBlock.wp_date_time = [ year, month, day ].join( '-' ) + 'T' + [ hour, minutes, seconds ].join( ':' );

				var currentStartDateObj = new Date( startDate );
				if ( webinarId.length === 0 && currentStartDateObj < currentDateTimeZoneWise ) {
					setAttributes( { startDate: bpZoomMeetingBlock.wp_date_time } );
				}
			}
			const setPassword = ( val ) => {
				setAttributes( { password: val } );
			}
			const setRegistration = ( val ) => {
				setAttributes( { registration: val } );
			}
			const setRegistrationType = ( val ) => {
				setAttributes( { registration_type: parseInt( val ) } );
			}
			const setHostVideo = ( val ) => {
				setAttributes( { hostVideo: val } );
			}
			const setPanelistsVideo = ( val ) => {
				setAttributes( { panelistsVideo: val } );
			}
			const setPracticeSession = ( val ) => {
				setAttributes( { practiceSession: val } );
			}
			const setOnDemand = ( val ) => {
				setAttributes( { onDemand: val } );
			}
			const setAuthentication = ( val ) => {
				setAttributes( { authentication: val } );
			}
			const setAutoRecording = ( val ) => {
				setAttributes( { autoRecording: val } );
			}
			const setWebinarFormType = ( val ) => {
				setAttributes( { webinarFormType: val } );
			}
			const setRecurring = ( val ) => {
				setAttributes( { recurring: val } );
			}
			const setRecurrence = ( val ) => {
				setAttributes( { recurrence: parseInt( val ) } );

				if ( val == 1 ) {
					end_date_dt.setDate( start_date_dt.getDate() + ( 6 * repeat_interval ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				} else if ( val == 2 ) {
					end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * repeat_interval ) ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				} else if ( val == 3 ) {
					end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * repeat_interval ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				}
			}
			const setRepeatInterval = ( val ) => {
				setAttributes( { repeat_interval: val } );

				if ( recurrence === 1 ) {
					end_date_dt.setDate( start_date_dt.getDate() + ( 6 * val ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				} else if ( recurrence === 2 ) {
					end_date_dt.setDate( start_date_dt.getDate() + ( 6 * ( 7 * val ) ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				} else if ( recurrence === 3 ) {
					end_date_dt.setMonth( start_date_dt.getMonth() + ( 6 * val ) );
					setEndDateTime( moment( end_date_dt ).format( moment_default_datetime_format ) );
				}
			}
			const setEndTimes = ( val ) => {
				setAttributes( { end_times: parseInt( val ) } );
			}
			const setEndDateTime = ( val ) => {
				let meetingDate = new Date( startDate );
				let selectedDate = new Date( val );
				if ( meetingDate.getTime() < selectedDate.getTime() ) {
					setAttributes( { end_date_time: val } );
				}
			}
			const setOccurrenceStartTime = ( start_time ) => {
				let nowDate = new Date();
				let selectedDate = new Date( start_time );
				if ( nowDate.getTime() < selectedDate.getTime() ) {
					setAttributes( { current_occurrence_start_time: start_time } );
				}
			}
			const setOccurrenceDuration = ( duration_val ) => {
				setAttributes( { current_occurrence_duration: duration_val } );
			}
			const setEndTimeSelect = ( val ) => {
				setAttributes( { end_time_select: val } );
			}
			const setWeeklyDays = ( val ) => {
				setAttributes( { weekly_days: val } );
			}
			const setMonthlyOccursOn = ( val ) => {
				setAttributes( { monthly_occurs_on: val } );
			}
			const setMonthlyDay = ( val ) => {
				setAttributes( { monthly_day: parseInt( val ) } );
			}
			const setMonthlyWeek = ( val ) => {
				setAttributes( { monthly_week: val } );
			}
			const setMonthlyWeekDay = ( val ) => {
				setAttributes( { monthly_week_day: parseInt( val ) } );
			}
			const setOccurrences = ( val ) => {
				setAttributes( { occurrences: val } );

				for ( let o in val ) {
					let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
					let selectedDate = new Date( val[o].start_time );
					if ( nowDate.getTime() < selectedDate.getTime() && 'deleted' !== val[o].status ) {
						setStartDate( val[o].start_time );
						break;
					}
				}
			}
			const setOccurrenceEdit = ( val ) => {
				setAttributes( { occurrence_edit: val } );
			}
			const setCurrentOccurrence = ( val ) => {
				setAttributes( { current_occurrence: val } );
				setOccurrenceDuration( val.duration );
				setOccurrenceStartTime( val.start_time );
			}
			const setAltHosts = ( val ) => {
				setAttributes( { alt_hosts: val } );
			}
			const setExternalWebinar = ( val ) => {
				setAttributes( { external_webinar: val } );
			}
			const settings = __experimentalGetSettings();
			const [ isPickerOpen, setIsPickerOpen ] = useState( false );
			const [ isRecurrencePickerOpen, setIsRecurrencePickerOpen ] = useState( false );

			const resolvedFormat = settings.formats.datetime || 'Y-m-d H:i:s';

			let auto_recording_options = [];

			if ( host_user_type == 2 ) {
				auto_recording_options = [
					{ label: bpZoomMeetingBlock.block_zoom_no_recordings, value: 'none' },
					{ label: bpZoomMeetingBlock.block_zoom_cloud, value: 'cloud' },
					{ label: bpZoomMeetingBlock.block_zoom_local, value: 'local' },
				];
			} else {
				auto_recording_options = [
					{ label: bpZoomMeetingBlock.block_zoom_no_recordings, value: 'none' },
					{ label: bpZoomMeetingBlock.block_zoom_local, value: 'local' },
				];
			}

			if ( recurrence === 1 ) {
				repeat_every = __( 'day', 'buddyboss-pro' );
				repeat_interval_options = [];
				for ( let i = 1; i <= 15; i++ ) {
					repeat_interval_options.push( { label: i, value: i } );
				}
			} else if ( recurrence === 2 ) {
				repeat_every = __( 'week', 'buddyboss-pro' );
				repeat_interval_options = [];
				for ( let i = 1; i <= 12; i++ ) {
					repeat_interval_options.push( { label: i, value: i } );
				}
			} else if ( recurrence === 3 ) {
				repeat_every = __( 'month', 'buddyboss-pro' );
				repeat_interval_options = [];
				for ( let i = 1; i <= 3; i++ ) {
					repeat_interval_options.push( { label: i, value: i } );
				}
			}

			const setWebinarObject = ( response ) => {
				if ( !isUndefined( response.host_name ) ) {
					setHostDisplayName( response.host_name );
				}
				if ( !isUndefined( response.host_email ) ) {
					setHostId( response.host_email );
				}
				if ( !isUndefined( response.webinar ) ) {
					let webinar = response.webinar;
					if ( !isUndefined( webinar.id ) ) {
						setWebinarId( webinar.id );
					}
					if ( !isUndefined( webinar.host_id ) ) {
						setHostId( webinar.host_id );
					}
					if ( !isUndefined( webinar.topic ) ) {
						setTitle( webinar.topic );
					}
					if ( !isUndefined( webinar.agenda ) ) {
						setDescription( webinar.agenda );
					}
					if ( !isUndefined( webinar.timezone ) ) {
						setTimezone( webinar.timezone );
					}
					if ( !isUndefined( webinar.start_time ) ) {
						setAttributes( { startDate: webinar.start_time } );
					}
					if ( !isUndefined( webinar.duration ) ) {
						setDuration( webinar.duration );
					} else if ( !isUndefined( webinar.occurrences ) && webinar.occurrences.length ) {
						setDuration( webinar.occurrences[0].duration );
					}
					if ( !isUndefined( webinar.password ) ) {
						setPassword( webinar.password );
					}
					if ( !isUndefined( webinar.type ) && [ 6, 9 ].includes( webinar.type ) ) {
						setRecurring( true );
					} else {
						setRecurring( false );
					}
					if ( !isUndefined( webinar.occurrences ) && webinar.occurrences.length ) {
						setOccurrences( webinar.occurrences );
					}
					if ( !isUndefined( webinar.recurrence ) ) {
						let webinar_recurrence = webinar.recurrence;
						if ( !isUndefined( webinar_recurrence.type ) ) {
							setRecurrence( webinar_recurrence.type );
						}
						if ( !isUndefined( webinar_recurrence.repeat_interval ) ) {
							setRepeatInterval( webinar_recurrence.repeat_interval );
						}
						if ( !isUndefined( webinar_recurrence.end_times ) ) {
							setEndTimes( webinar_recurrence.end_times );
							setEndTimeSelect( 'times' );
						}
						if ( !isUndefined( webinar_recurrence.end_date_time ) ) {
							setEndDateTime( webinar_recurrence.end_date_time );
							setEndTimeSelect( 'date' );
						}
						if ( !isUndefined( webinar_recurrence.weekly_days ) ) {
							setWeeklyDays( webinar_recurrence.weekly_days.split( ',' ) );
						}
						if ( !isUndefined( webinar_recurrence.monthly_day ) ) {
							setMonthlyDay( webinar_recurrence.monthly_day );
							setMonthlyOccursOn( 'day' );
						}
						if ( !isUndefined( webinar_recurrence.monthly_week ) ) {
							setMonthlyWeek( webinar_recurrence.monthly_week );
							setMonthlyOccursOn( 'week' );
						}
						if ( !isUndefined( webinar_recurrence.monthly_week_day ) ) {
							setMonthlyWeekDay( webinar_recurrence.monthly_week_day );
							setMonthlyOccursOn( 'week' );
						}
					}
					if ( !isUndefined( webinar.settings ) ) {
						let webinar_settings = webinar.settings;
						if ( !isUndefined( webinar_settings.alternative_hosts ) ) {
							setAltHosts( webinar_settings.alternative_hosts );
						}
						if ( !isUndefined( webinar_settings.approval_type ) && 0 == webinar_settings.approval_type ) {
							setRegistration( true );
						}
						if ( !isUndefined( webinar_settings.registration_type ) ) {
							setRegistrationType( webinar_settings.registration_type );
						}
						if ( !isUndefined( webinar_settings.host_video ) ) {
							setHostVideo( webinar_settings.host_video );
						}
						if ( !isUndefined( webinar_settings.panelists_video ) ) {
							setPanelistsVideo( webinar_settings.panelists_video );
						}
						if ( !isUndefined( webinar_settings.practice_session ) ) {
							setPracticeSession( webinar_settings.practice_session );
						}
						if ( !isUndefined( webinar_settings.on_demand ) ) {
							setOnDemand( webinar_settings.on_demand );
						}
						if ( !isUndefined( webinar_settings.meeting_authentication ) ) {
							setAuthentication( webinar_settings.meeting_authentication );
						}
						if ( !isUndefined( webinar_settings.auto_recording ) ) {
							setAutoRecording( webinar_settings.auto_recording );
						}
					}
				}
			};

			return (
				<>
					{'' === webinarFormType ?
						<Placeholder
							icon="video-alt2"
							className="bb-input-container webinar_block_title"
							label={bpZoomMeetingBlock.block_zoom_webinar}
							instructions={bpZoomMeetingBlock.block_zoom_create_add_webinar}
						>

							<Button isSecondary onClick={() => {
								setWebinarFormType( 'create' )
							}}>
								{bpZoomMeetingBlock.block_create_webinar}
							</Button>
							<Button isSecondary onClick={() => {
								setWebinarFormType( 'existing' )
							}}>
								{bpZoomMeetingBlock.block_add_webinar}
							</Button>
						</Placeholder>
						: ''
					}
					{'existing' === webinarFormType ?
						<>
							<Placeholder icon="video-alt2" className="bb-meeting-id-input-container"
								label={bpZoomMeetingBlock.block_add_webinar}>
								<TextControl
									label={bpZoomMeetingBlock.block_webinar_id}
									value={webinarId}
									className="components-placeholder__input bb-meeting-id-wrap"
									placeholder={bpZoomMeetingBlock.block_enter_webinar_id}
									onChange={setWebinarId}
								/>
								<BaseControl
									className="bb-buttons-wrap"
								>
									<Button isPrimary onClick={( e ) => {
										var target = e.target;
										target.setAttribute( 'disabled', true );
										const webinar_data = {
											'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
											'bp-zoom-webinar-id': webinarId,
										};

										zoomWebinarFetch( webinar_data ).then( ( response ) => {
											target.removeAttribute( 'disabled' );
											target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i>';
											wp.data.dispatch( 'core/notices' ).createNotice(
												'success',
												bpZoomMeetingBlock.block_webinar_sync,
												{
													isDismissible: true,
												}
											);

											// Set webinar object.
											setWebinarObject( response );

											// Set webinar form to create.
											setWebinarFormType( 'create' );

											//Set external webinar to true.
											setExternalWebinar( true );

											// Save the post.
											var editorInfo = wp.data.select( 'core/editor' );
											if ( editorInfo.isEditedPostSaveable() ) {
												if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
													wp.data.dispatch( 'core/editor' ).autosave();
												} else {
													wp.data.dispatch( 'core/editor' ).savePost();
												}
											}
										} )
											.catch( ( error ) => {
												target.removeAttribute( 'disabled' );
												wp.data.dispatch( 'core/notices' ).createNotice(
													'error',
													error.error,
													{
														isDismissible: true,
													}
												);
											} );
									}}>
										{bpZoomMeetingBlock.block_zoom_save}
									</Button>
									{webinarId < 1 || '' === webinarId ?
										<Button isTertiary onClick={() => {
											setWebinarFormType( '' )
										}}>
											{bpZoomMeetingBlock.block_zoom_cancel}
										</Button>
										:
										''
									}
								</BaseControl>
							</Placeholder>

						</>
						:
						''
					}
					{'create' === webinarFormType ?
						<>
							<Placeholder icon="video-alt2" label={
								!external_webinar ?
									bpZoomMeetingBlock.block_create_webinar
									:
									bpZoomMeetingBlock.block_existing_webinar
							}
								className="bp-meeting-block-create">
								{webinarId > 1 || '' !== webinarId ?
									<Button isLink onClick={( e ) => {
										var target = e.currentTarget;
										target.innerHTML = '<i class="bb-icon-l bb-icon-spinner animate-spin"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
										target.setAttribute( 'disabled', true );
										const webinar_data = {
											'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
											'bp-zoom-webinar-id': webinarId,
										};

										zoomWebinarFetch( webinar_data ).then( ( response ) => {
											target.removeAttribute( 'disabled' );
											target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
											wp.data.dispatch( 'core/notices' ).createNotice(
												'success',
												bpZoomMeetingBlock.block_webinar_sync,
												{
													isDismissible: true,
												}
											);

											// Set webinar object.
											setWebinarObject( response );

											// Set webinar form to create.
											setWebinarFormType( 'create' );

											// Save the post.
											var editorInfo = wp.data.select( 'core/editor' );
											if ( editorInfo.isEditedPostSaveable() ) {
												if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
													wp.data.dispatch( 'core/editor' ).autosave();
												} else {
													wp.data.dispatch( 'core/editor' ).savePost();
												}
											}
										} )
											.catch( ( error ) => {
												target.removeAttribute( 'disabled' );
												target.innerHTML = '<i class="bb-icon-l bb-icon-bolt"></i> ' + bpZoomMeetingBlock.block_zoom_sync;
												wp.data.dispatch( 'core/notices' ).createNotice(
													'error',
													error.error,
													{
														isDismissible: true,
													}
												);
											} );
									}}>
										<i className="bb-icon-l bb-icon-bolt"></i> {bpZoomMeetingBlock.block_zoom_sync}
									</Button>
									:
									''
								}
								<TextControl
									label=''
									type="hidden"
									value={webinarId}
								/>
								<TextControl
									label={bpZoomMeetingBlock.block_zoom_title}
									value={title}
									onChange={setTitle}
								/>
								<BaseControl
									label={bpZoomMeetingBlock.block_zoom_when}
									className="bb-meeting-time-wrap"
								>
									<time dateTime={date( 'c', startDate )}>
										<Button
											icon="edit"
											isTertiary
											isLink
											onClick={() =>
												setIsPickerOpen(
													( _isPickerOpen ) => !_isPickerOpen
												)
											}>
											{moment( startDate ).format( 'MMMM DD, YYYY h:mm a' )}
										</Button>
										{isPickerOpen && (
											<Popover onClose={setIsPickerOpen.bind( null, false )}>
												<DateTimePicker
													currentDate={startDate}
													onChange={setStartDate}
													is12Hour={true}
												/>
											</Popover>
										)}
									</time>
								</BaseControl>
								<div>
									<SelectDropdown
										timezone={timezone}
										options={bpZoomMeetingBlock.timezones}
										setTimezone={setTimezone}
										clientId={clientId}
									/>
								</div>
								<SelectControl
									label={bpZoomMeetingBlock.block_zoom_auto_recording}
									value={autoRecording}
									options={auto_recording_options}
									onChange={setAutoRecording}
									className="bb_inline_selectBox"
								/>
								<BaseControl className="bb-buttons-wrap">
									<Button
										className="submit-meeting"
										isPrimary
										onClick={( e ) => {
											const target = e.target;
											target.setAttribute( 'disabled', true );
											const webinar_data = {
												'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
												'bp-zoom-webinar-zoom-id': webinarId,
												'bp-zoom-webinar-start-date': startDate,
												'bp-zoom-webinar-timezone': timezone,
												'bp-zoom-webinar-duration': duration,
												'bp-zoom-webinar-password': password,
												'bp-zoom-webinar-recording': autoRecording,
												'bp-zoom-webinar-alt-host-ids': alt_hosts,
												'bp-zoom-webinar-title': title,
												'bp-zoom-webinar-description': description,
											};

											webinar_data['bp-zoom-webinar-type'] = 5;

											if ( recurring ) {
												if ( 1 === recurrence ) {
													if ( 'date' === end_time_select ) {
														webinar_data['bp-zoom-webinar-end-date-time'] = end_date_time;
													} else {
														webinar_data['bp-zoom-webinar-end-times'] = end_times;
													}
													webinar_data['bp-zoom-webinar-recurrence'] = 1;
													webinar_data['bp-zoom-webinar-end-time-select'] = end_time_select;
													webinar_data['bp-zoom-webinar-repeat-interval'] = repeat_interval;
													webinar_data['bp-zoom-webinar-type'] = 9;
												} else if ( 2 === recurrence ) {
													if ( weekly_days ) {
														webinar_data['bp-zoom-webinar-weekly-days'] = weekly_days;
													}
													if ( 'date' === end_time_select ) {
														webinar_data['bp-zoom-webinar-end-date-time'] = end_date_time;
													} else {
														webinar_data['bp-zoom-webinar-end-times'] = end_times;
													}
													webinar_data['bp-zoom-webinar-recurrence'] = 2;
													webinar_data['bp-zoom-webinar-end-time-select'] = end_time_select;
													webinar_data['bp-zoom-webinar-repeat-interval'] = repeat_interval;
													webinar_data['bp-zoom-webinar-type'] = 9;
												} else if ( 3 === recurrence ) {
													if ( 'day' === monthly_occurs_on ) {
														webinar_data['bp-zoom-webinar-monthly-day'] = monthly_day;
													} else if ( 'week' === monthly_occurs_on ) {
														webinar_data['bp-zoom-webinar-monthly-week'] = monthly_week;
														webinar_data['bp-zoom-webinar-monthly-week-day'] = monthly_week_day;
													}
													if ( 'date' === end_time_select ) {
														webinar_data['bp-zoom-webinar-end-date-time'] = end_date_time;
													} else {
														webinar_data['bp-zoom-webinar-end-times'] = end_times;
													}
													webinar_data['bp-zoom-webinar-recurrence'] = 3;
													webinar_data['bp-zoom-webinar-monthly-occurs-on'] = monthly_occurs_on;
													webinar_data['bp-zoom-webinar-end-time-select'] = end_time_select;
													webinar_data['bp-zoom-webinar-repeat-interval'] = repeat_interval;
													webinar_data['bp-zoom-webinar-type'] = 9;
												} else {
													webinar_data['bp-zoom-webinar-type'] = 6;
												}
											}

											if ( registration ) {
												webinar_data['bp-zoom-webinar-registration'] = 1;
												if ( webinar_data['bp-zoom-webinar-type'] === 8 ) {
													webinar_data['bp-zoom-webinar-registration-type'] = registration_type;
												}
											}

											if ( hostVideo ) {
												webinar_data['bp-zoom-webinar-host-video'] = 1;
											}

											if ( panelistsVideo ) {
												webinar_data['bp-zoom-webinar-panelists-video'] = 1;
											}

											if ( onDemand ) {
												webinar_data['bp-zoom-webinar-on-demand'] = 1;
											}

											if ( authentication ) {
												webinar_data['bp-zoom-webinar-authentication'] = 1;
											}

											if ( practiceSession ) {
												webinar_data['bp-zoom-webinar-practice-session'] = 1;
											}

											wp.ajax.send( 'zoom_webinar_block_add', {
												data: webinar_data,
												success: function ( response ) {
													if ( response.webinar.id ) {
														setWebinarId( response.webinar.id );
													}
													if ( typeof response.webinar.occurrences !== 'undefined' && response.webinar.occurrences.length ) {
														setOccurrences( response.webinar.occurrences );
													}
													target.removeAttribute( 'disabled' );
													wp.data.dispatch( 'core/notices' ).createNotice(
														'success', // Can be one of: success, info, warning, error.
														bpZoomMeetingBlock.block_webinar_updated, // Text string to display.
														{
															isDismissible: true, // Whether the user can dismiss the notice.
														}
													);
													setWebinarFormType( 'create' );
													//save post if is ok to save
													var editorInfo = wp.data.select( 'core/editor' );
													if ( editorInfo.isEditedPostSaveable() ) {
														if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
															wp.data.dispatch( 'core/editor' ).autosave();
														} else {
															wp.data.dispatch( 'core/editor' ).savePost();
														}
													}
												},
												error: function ( error ) {
													target.removeAttribute( 'disabled' );
													if ( typeof error.errors !== 'undefined' ) {
														for ( let er in error.errors ) {
															wp.data.dispatch( 'core/notices' ).createNotice(
																'error',
																error.errors[er].message, // Text string to display.
																{
																	isDismissible: true, // Whether the user can dismiss the notice.
																}
															);
														}
													} else {
														wp.data.dispatch( 'core/notices' ).createNotice(
															'error', // Can be one of: success, info, warning, error.
															error.error, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
													}
												}
											} );
										}
										}>
										{bpZoomMeetingBlock.block_webinar_save}
									</Button>
									{webinarId < 1 || '' === webinarId ?
										<Button isTertiary onClick={() => {
											setWebinarFormType( '' )
										}}>
											{bpZoomMeetingBlock.block_zoom_cancel}
										</Button>
										:
										<Button isDestructive onClick={( e ) => {
											const target = e.target;
											if ( confirm( 'Are you sure you want to delete this webinar?', 'buddyboss-pro' ) ) {

												target.setAttribute( 'disabled', true );
												const webinar_data = {
													'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
													'bp-zoom-webinar-zoom-id': webinarId,
												};

												wp.ajax.send( 'zoom_webinar_block_delete_webinar', {
													data: webinar_data,
													success: function () {
														wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId );
														target.removeAttribute( 'disabled' );
														wp.data.dispatch( 'core/notices' ).createNotice(
															'success', // Can be one of: success, info, warning, error.
															bpZoomMeetingBlock.block_webinar_deleted, // Text string to display.
															{
																isDismissible: true, // Whether the user can dismiss the notice.
															}
														);
														var editorInfo = wp.data.select( 'core/editor' );
														// save post if is ok to save
														if ( editorInfo.isEditedPostSaveable() ) {
															if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																wp.data.dispatch( 'core/editor' ).autosave();
															} else {
																wp.data.dispatch( 'core/editor' ).savePost();
															}
														}
													},
													error: function ( error ) {
														target.removeAttribute( 'disabled' );
														if ( typeof error.errors !== 'undefined' ) {
															for ( let er in error.errors ) {
																wp.data.dispatch( 'core/notices' ).createNotice(
																	'error',
																	error.errors[er].message, // Text string to display.
																	{
																		isDismissible: true, // Whether the user can dismiss the notice.
																	}
																);
															}
														} else {
															wp.data.dispatch( 'core/notices' ).createNotice(
																'error', // Can be one of: success, info, warning, error.
																error.error, // Text string to display.
																{
																	isDismissible: true, // Whether the user can dismiss the notice.
																}
															);
														}
													}
												} );
											}
										}}>
											{bpZoomMeetingBlock.block_zoom_delete}
										</Button>
									}
								</BaseControl>
							</Placeholder>
						</>
						:
						''
					}
					{'create' === webinarFormType ?
						<InspectorControls>
							<PanelBody
								title={bpZoomMeetingBlock.block_zoom_settings}
								initialOpen={true}>
								<TextareaControl
									label={bpZoomMeetingBlock.block_zoom_description}
									value={description}
									onChange={setDescription}
								/>
								<TextControl
									label={bpZoomMeetingBlock.block_zoom_passcode}
									onChange={setPassword}
									value={password}
								/>
								<TextControl
									type="number"
									label={bpZoomMeetingBlock.block_zoom_duration}
									onChange={setDuration}
									value={duration}
								/>
								<TextControl
									label={bpZoomMeetingBlock.block_zoom_default_host}
									type="text"
									disabled
									value={hostDisplayName}
								/>
								{
									host_user_type == 2
										?
										<TextControl
											label={bpZoomMeetingBlock.block_zoom_alt_hosts}
											onChange={setAltHosts}
											value={alt_hosts}
											placeholder={bpZoomMeetingBlock.block_zoom_example}
											help={bpZoomMeetingBlock.block_zoom_email_enter}
										/>
										:
										''
								}
								<CheckboxControl
									label={bpZoomMeetingBlock.block_zoom_start_video}
									checked={hostVideo}
									onChange={setHostVideo}
									className="bb-checkbox-wrap"
								/>
								<CheckboxControl
									label={bpZoomMeetingBlock.block_zoom_start_video_par}
									checked={panelistsVideo}
									onChange={setPanelistsVideo}
									className="bb-checkbox-wrap"
								/>
								{
									host_user_type == 2 && ! private_webinar
										?
										<>
											<CheckboxControl
												label={bpZoomMeetingBlock.block_zoom_require_reg}
												checked={registration}
												onChange={setRegistration}
												className="bb-checkbox-wrap"
											/>
											{
												registration && recurring
													?
													<>
														<RadioControl
															selected={registration_type}
															options={[
																{
																	label: bpZoomMeetingBlock.block_att_any,
																	value: 1
																},
																{
																	label: bpZoomMeetingBlock.block_att_each,
																	value: 2
																},
																{
																	label: bpZoomMeetingBlock.block_att_choose,
																	value: 3
																},
															]}
															onChange={setRegistrationType}
														/>
													</>
													:
													''
											}
										</>
										:
										''
								}
								<CheckboxControl
									label={bpZoomMeetingBlock.block_enable_practice_session}
									checked={practiceSession}
									onChange={setPracticeSession}
									className="bb-checkbox-wrap"
								/>
								<CheckboxControl
									label={bpZoomMeetingBlock.block_only_auth}
									checked={authentication}
									onChange={setAuthentication}
									className="bb-checkbox-wrap"
								/>
							</PanelBody>
							<PanelBody
								title={bpZoomMeetingBlock.block_recurring_options}
								initialOpen={false}>
								<CheckboxControl
									label={bpZoomMeetingBlock.block_recurring_webinar}
									checked={recurring}
									onChange={setRecurring}
								/>
								{true === recurring ?
									<>
										<SelectControl
											label={bpZoomMeetingBlock.block_recurrence}
											value={recurrence}
											options={[
												{ label: bpZoomMeetingBlock.block_zoom_week_daily, value: 1 },
												{ label: bpZoomMeetingBlock.block_zoom_week_weekly, value: 2 },
												{ label: bpZoomMeetingBlock.block_zoom_week_monthly, value: 3 },
											]}
											onChange={setRecurrence}
										/>
										<SelectControl
											label={bpZoomMeetingBlock.block_repeat_every}
											value={repeat_interval}
											options={repeat_interval_options}
											onChange={setRepeatInterval}
											help={repeat_every}
										/>
										{2 === recurrence
											?
											<SelectControl
												label={bpZoomMeetingBlock.block_zoom_days}
												value={weekly_days}
												options={[
													{ label: bpZoomMeetingBlock.block_zoom_sunday, value: 1 },
													{ label: bpZoomMeetingBlock.block_zoom_monday, value: 2 },
													{ label: bpZoomMeetingBlock.block_zoom_tuesday, value: 3 },
													{ label: bpZoomMeetingBlock.block_zoom_wednesday, value: 4 },
													{ label: bpZoomMeetingBlock.block_zoom_thursday, value: 5 },
													{ label: bpZoomMeetingBlock.block_zoom_friday, value: 6 },
													{ label: bpZoomMeetingBlock.block_zoom_saturday, value: 7 },
												]}
												onChange={setWeeklyDays}
												multiple
											/>
											:
											''
										}
										{3 === recurrence
											?
											<>
												<SelectControl
													label={bpZoomMeetingBlock.block_zoom_occures}
													value={monthly_occurs_on}
													options={[
														{
															label: bpZoomMeetingBlock.block_zoom_day_month,
															value: 'day'
														},
														{
															label: bpZoomMeetingBlock.block_zoom_week_month,
															value: 'week'
														},
													]}
													onChange={setMonthlyOccursOn}
												/>
												{'day' === monthly_occurs_on
													?
													<SelectControl
														label={bpZoomMeetingBlock.block_zoom_day}
														value={monthly_day}
														options={[
															{ label: __( '1', 'buddyboss-pro' ), value: 1 },
															{ label: __( '2', 'buddyboss-pro' ), value: 2 },
															{ label: __( '3', 'buddyboss-pro' ), value: 3 },
															{ label: __( '4', 'buddyboss-pro' ), value: 4 },
															{ label: __( '5', 'buddyboss-pro' ), value: 5 },
															{ label: __( '6', 'buddyboss-pro' ), value: 6 },
															{ label: __( '7', 'buddyboss-pro' ), value: 7 },
															{ label: __( '8', 'buddyboss-pro' ), value: 8 },
															{ label: __( '9', 'buddyboss-pro' ), value: 9 },
															{ label: __( '10', 'buddyboss-pro' ), value: 10 },
															{ label: __( '11', 'buddyboss-pro' ), value: 11 },
															{ label: __( '12', 'buddyboss-pro' ), value: 12 },
															{ label: __( '13', 'buddyboss-pro' ), value: 13 },
															{ label: __( '14', 'buddyboss-pro' ), value: 14 },
															{ label: __( '15', 'buddyboss-pro' ), value: 15 },
															{ label: __( '16', 'buddyboss-pro' ), value: 16 },
															{ label: __( '17', 'buddyboss-pro' ), value: 17 },
															{ label: __( '18', 'buddyboss-pro' ), value: 18 },
															{ label: __( '19', 'buddyboss-pro' ), value: 19 },
															{ label: __( '20', 'buddyboss-pro' ), value: 20 },
															{ label: __( '21', 'buddyboss-pro' ), value: 21 },
															{ label: __( '22', 'buddyboss-pro' ), value: 22 },
															{ label: __( '23', 'buddyboss-pro' ), value: 23 },
															{ label: __( '24', 'buddyboss-pro' ), value: 24 },
															{ label: __( '25', 'buddyboss-pro' ), value: 25 },
															{ label: __( '26', 'buddyboss-pro' ), value: 26 },
															{ label: __( '27', 'buddyboss-pro' ), value: 27 },
															{ label: __( '28', 'buddyboss-pro' ), value: 28 },
															{ label: __( '29', 'buddyboss-pro' ), value: 29 },
															{ label: __( '30', 'buddyboss-pro' ), value: 30 },
															{ label: __( '31', 'buddyboss-pro' ), value: 31 },
														]}
														onChange={setMonthlyDay}
														help={bpZoomMeetingBlock.block_zoom_of_month}
													/>
													:
													<>
														<SelectControl
															value={monthly_week}
															options={[
																{ label: bpZoomMeetingBlock.block_zoom_first, value: 1 },
																{ label: bpZoomMeetingBlock.block_zoom_second, value: 2 },
																{ label: bpZoomMeetingBlock.block_zoom_third, value: 3 },
																{ label: bpZoomMeetingBlock.block_zoom_fourth, value: 4 },
																{ label: bpZoomMeetingBlock.block_zoom_last, value: -1 },
															]}
															onChange={setMonthlyWeek}
														/>
														<SelectControl
															value={monthly_week_day}
															options={[
																{ label: bpZoomMeetingBlock.block_zoom_sunday, value: 1 },
																{ label: bpZoomMeetingBlock.block_zoom_monday, value: 2 },
																{ label: bpZoomMeetingBlock.block_zoom_tuesday, value: 3 },
																{ label: bpZoomMeetingBlock.block_zoom_wednesday, value: 4 },
																{ label: bpZoomMeetingBlock.block_zoom_thursday, value: 5 },
																{ label: bpZoomMeetingBlock.block_zoom_friday, value: 6 },
																{ label: bpZoomMeetingBlock.block_zoom_saturday, value: 7 },
															]}
															onChange={setMonthlyWeekDay}
															help={bpZoomMeetingBlock.block_zoom_of_month}
														/>
													</>
												}
											</>
											:
											''
										}
										{4 !== recurrence
											?
											<>
												<SelectControl
													label={bpZoomMeetingBlock.block_zoom_end_by}
													value={end_time_select}
													options={[
														{ label: bpZoomMeetingBlock.block_zoom_date, value: 'date' },
														{ label: bpZoomMeetingBlock.block_zoom_occurrences, value: 'times' },
													]}
													onChange={setEndTimeSelect}
												/>
												{'date' == end_time_select ?
													<time dateTime={date( 'c', end_date_time )}>
														<Button
															icon="edit"
															isTertiary
															isLink
															onClick={() =>
																setIsRecurrencePickerOpen(
																	( isRecurrencePickerOpen ) => !isRecurrencePickerOpen
																)
															}>
															{moment( end_date_time ).format( 'MMMM DD, YYYY' )}
														</Button>
														{isRecurrencePickerOpen && (
															<Popover
																onClose={setIsRecurrencePickerOpen.bind( null, false )}>
																<DatePicker
																	currentDate={end_date_time}
																	onChange={setEndDateTime}
																/>
															</Popover>
														)}
													</time>
													:
													''
												}
												{'times' == end_time_select ?
													<SelectControl
														label={bpZoomMeetingBlock.block_zoom_end_after}
														value={end_times}
														help={bpZoomMeetingBlock.block_zoom_occurrences_low}
														options={[
															{ label: __( '1', 'buddyboss-pro' ), value: 1 },
															{ label: __( '2', 'buddyboss-pro' ), value: 2 },
															{ label: __( '3', 'buddyboss-pro' ), value: 3 },
															{ label: __( '4', 'buddyboss-pro' ), value: 4 },
															{ label: __( '5', 'buddyboss-pro' ), value: 5 },
															{ label: __( '6', 'buddyboss-pro' ), value: 6 },
															{ label: __( '7', 'buddyboss-pro' ), value: 7 },
															{ label: __( '8', 'buddyboss-pro' ), value: 8 },
															{ label: __( '9', 'buddyboss-pro' ), value: 9 },
															{ label: __( '10', 'buddyboss-pro' ), value: 10 },
															{ label: __( '11', 'buddyboss-pro' ), value: 11 },
															{ label: __( '12', 'buddyboss-pro' ), value: 12 },
															{ label: __( '13', 'buddyboss-pro' ), value: 13 },
															{ label: __( '14', 'buddyboss-pro' ), value: 14 },
															{ label: __( '15', 'buddyboss-pro' ), value: 15 },
															{ label: __( '16', 'buddyboss-pro' ), value: 16 },
															{ label: __( '17', 'buddyboss-pro' ), value: 17 },
															{ label: __( '18', 'buddyboss-pro' ), value: 18 },
															{ label: __( '19', 'buddyboss-pro' ), value: 19 },
															{ label: __( '20', 'buddyboss-pro' ), value: 20 },
														]}
														onChange={setEndTimes}
													/>
													:
													''
												}
											</>
											:
											''
										}
									</>
									:
									''}
							</PanelBody>
							{true === recurring && occurrences.length ?
								<PanelBody
									title={bpZoomMeetingBlock.block_zoom_occurrences}
									initialOpen={false}>
									{
										occurrences.map( ( occurrence ) => {
											let nowDate = new Date( bpZoomMeetingBlock.wp_date_time );
											let selectedDate = new Date( occurrence.start_time );
											if ( nowDate.getTime() > selectedDate.getTime() || 'deleted' === occurrence.status ) {
												return '';
											}
											return <Fragment key={occurrence.occurrence_id}>
												<Text as="p">
													{moment( occurrence.start_time ).format( 'MMMM DD, YYYY h:mm a' )}
												</Text>
												<Button
													isLink
													className="edit-occurrences-button"
													onClick={() => {
														setOccurrenceEdit( true );
														setCurrentOccurrence( occurrence );
													}
													}>
													{bpZoomMeetingBlock.block_zoom_edit}
												</Button>
												<Button isLink="true" className="edit-occurrences-button"
													onClick={( e ) => {
														const target = e.target;

														if ( !confirm( bpZoomMeetingBlock.delete_occurrence_confirm_str ) ) {
															return false;
														}

														target.setAttribute( 'disabled', true );
														const webinar_data = {
															'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
															'bp-zoom-webinar-zoom-id': webinarId,
															'bp-zoom-webinar-occurrence-id': occurrence.occurrence_id,
														};

														wp.ajax.send( 'zoom_webinar_block_delete_occurrence', {
															data: webinar_data,
															success: function () {
																setOccurrences( occurrences.filter( function ( obj ) {
																	return obj.occurrence_id !== occurrence.occurrence_id;
																} ) );
																setOccurrenceEdit( false );
																target.removeAttribute( 'disabled' );
																wp.data.dispatch( 'core/notices' ).createNotice(
																	'success', // Can be one of: success, info, warning, error.
																	bpZoomMeetingBlock.block_zoom_occurrence_del, // Text string to display.
																	{
																		isDismissible: true, // Whether the user can dismiss the notice.
																	}
																);
																var editorInfo = wp.data.select( 'core/editor' );
																// save post if is ok to save
																if ( editorInfo.isEditedPostSaveable() ) {
																	if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																		wp.data.dispatch( 'core/editor' ).autosave();
																	} else {
																		wp.data.dispatch( 'core/editor' ).savePost();
																	}
																}
															},
															error: function ( error ) {
																target.removeAttribute( 'disabled' );
																if ( typeof error.errors !== 'undefined' ) {
																	for ( let er in error.errors ) {
																		wp.data.dispatch( 'core/notices' ).createNotice(
																			'error',
																			error.errors[er].message, // Text string to display.
																			{
																				isDismissible: true, // Whether the user can dismiss the notice.
																			}
																		);
																	}
																} else {
																	wp.data.dispatch( 'core/notices' ).createNotice(
																		'error', // Can be one of: success, info, warning, error.
																		error.error, // Text string to display.
																		{
																			isDismissible: true, // Whether the user can dismiss the notice.
																		}
																	);
																}
															}
														} );
													}
													}>
													{bpZoomMeetingBlock.block_zoom_delete}
												</Button>
												{
													occurrence_edit && current_occurrence && current_occurrence.occurrence_id === occurrence.occurrence_id
														?
														<Fragment>
															<DateTimePicker
																is12Hour={true}
																currentDate={current_occurrence_start_time}
																onChange={setOccurrenceStartTime}
															/>
															<TextControl
																type="number"
																label={bpZoomMeetingBlock.block_zoom_duration}
																onChange={setOccurrenceDuration}
																value={current_occurrence_duration}
															/>
															<BaseControl className="bb-buttons-wrap">
																<Button
																	isPrimary
																	className="submit-meeting"
																	onClick={( e ) => {
																		const target = e.target;
																		target.setAttribute( 'disabled', true );
																		const webinar_data = {
																			'_wpnonce': bpZoomMeetingBlock.bp_zoom_webinar_nonce,
																			'bp-zoom-webinar-zoom-id': webinarId,
																			'bp-zoom-webinar-occurrence-id': current_occurrence.occurrence_id,
																			'bp-zoom-webinar-start-time': current_occurrence_start_time,
																			'bp-zoom-webinar-timezone': timezone,
																			'bp-zoom-webinar-duration': current_occurrence_duration,
																			'bp-zoom-webinar-recording': autoRecording,
																			'bp-zoom-webinar-alt-host-ids': alt_hosts,
																		};

																		if ( hostVideo ) {
																			webinar_data['bp-zoom-webinar-host-video'] = 1;
																		}

																		if ( panelistsVideo ) {
																			webinar_data['bp-zoom-webinar-panelists-video'] = 1;
																		}

																		if ( practiceSession ) {
																			webinar_data['bp-zoom-webinar-practice-session'] = 1;
																		}

																		if ( onDemand ) {
																			webinar_data['bp-zoom-webinar-on-demand'] = 1;
																		}

																		if ( authentication ) {
																			webinar_data['bp-zoom-webinar-authentication'] = 1;
																		}

																		wp.ajax.send( 'zoom_webinar_block_update_occurrence', {
																			data: webinar_data,
																			success: function () {
																				for ( var o_index in occurrences ) {
																					if ( occurrences[o_index].occurrence_id === current_occurrence.occurrence_id ) {
																						occurrences[o_index].duration = current_occurrence_duration;
																						occurrences[o_index].start_time = current_occurrence_start_time;
																						break;
																					}
																				}
																				setOccurrences( occurrences );
																				setOccurrenceEdit( false );
																				target.removeAttribute( 'disabled' );
																				wp.data.dispatch( 'core/notices' ).createNotice(
																					'success', // Can be one of: success, info, warning, error.
																					bpZoomMeetingBlock.block_webinar_updated, // Text string to display.
																					{
																						isDismissible: true, // Whether the user can dismiss the notice.
																					}
																				);
																				var editorInfo = wp.data.select( 'core/editor' );
																				// save post if is ok to save
																				if ( editorInfo.isEditedPostSaveable() ) {
																					if ( !editorInfo.isCurrentPostPublished() && ~[ 'draft', 'auto-draft' ].indexOf( editorInfo.getEditedPostAttribute( 'status' ) ) ) {
																						wp.data.dispatch( 'core/editor' ).autosave();
																					} else {
																						wp.data.dispatch( 'core/editor' ).savePost();
																					}
																				}
																			},
																			error: function ( error ) {
																				target.removeAttribute( 'disabled' );
																				if ( typeof error.errors !== 'undefined' ) {
																					for ( let er in error.errors ) {
																						wp.data.dispatch( 'core/notices' ).createNotice(
																							'error',
																							error.errors[er].message, // Text string to display.
																							{
																								isDismissible: true, // Whether the user can dismiss the notice.
																							}
																						);
																					}
																				} else {
																					wp.data.dispatch( 'core/notices' ).createNotice(
																						'error', // Can be one of: success, info, warning, error.
																						error.error, // Text string to display.
																						{
																							isDismissible: true, // Whether the user can dismiss the notice.
																						}
																					);
																				}
																			}
																		} );
																	}}>
																	{bpZoomMeetingBlock.block_zoom_save}
																</Button>
																<Button isTertiary onClick={() => {
																	setOccurrenceEdit( false );
																}}>
																	{bpZoomMeetingBlock.block_zoom_cancel}
																</Button>
															</BaseControl>
														</Fragment>
														:
														''
												}

											</Fragment>
										} )
									}
								</PanelBody>
								:
								''}
						</InspectorControls>
						:
						''
					}
				</>
			);
		},
	} );
}

/**
 * Get meeting blocks in current editor
 *
 * @return {[]} Array of meeting blocks
 */
export const getMeetingBlocks = () => {
	const editorBlocks = wp.data.select( 'core/block-editor' ).getBlocks(),
		meetingBlocks = [];
	let i = 0;

	for ( i in editorBlocks ) {
		if ( editorBlocks[i].isValid && editorBlocks[i].name === 'bp-zoom-meeting/create-meeting' ) {
			meetingBlocks.push( editorBlocks[i] );
		}
	}
	return meetingBlocks;
};

/**
 * Get webinar blocks in current editor
 *
 * @return {[]} Array of webinar blocks
 */
export const getWebinarBlocks = () => {
	const editorBlocks = wp.data.select( 'core/block-editor' ).getBlocks(),
		webinarBlocks = [];
	let i = 0;

	for ( i in editorBlocks ) {
		if ( editorBlocks[i].isValid && editorBlocks[i].name === 'bp-zoom-meeting/create-webinar' ) {
			webinarBlocks.push( editorBlocks[i] );
		}
	}
	return webinarBlocks;
};

wp.domReady( function () {
	var postSaveButtonClasses = '.editor-post-publish-button';
	jQuery( document ).on( 'click', postSaveButtonClasses, function ( e ) {
		e.stopPropagation();
		e.preventDefault();
		let meetingBlocks = getMeetingBlocks();
		if ( meetingBlocks.length ) {
			for ( let i in meetingBlocks ) {
				jQuery( '#block-' + meetingBlocks[i].clientId ).find( '.submit-meeting' ).trigger( 'click' );
			}
		}
		let webinarBlocks = getWebinarBlocks();
		if ( webinarBlocks.length ) {
			for ( let i in webinarBlocks ) {
				jQuery( '#block-' + webinarBlocks[i].clientId ).find( '.submit-meeting' ).trigger( 'click' );
			}
		}
		//wp.data.dispatch( 'core/editor' ).lockPostSaving( 'bpZoomMeetingBlocks' );
	} )
} )

// const unsubscribe = wp.data.subscribe(function () {
//     let select = wp.data.select('core/editor');
//     var isSavingPost = select.isSavingPost();
//     var isAutosavingPost = select.isAutosavingPost();
//     if (isSavingPost && !isAutosavingPost) {
//         unsubscribe();
//         wp.data.dispatch('core/notices').createNotice(
//             'error', // Can be one of: success, info, warning, error.
//             __( 'Please save the meeting.', 'buddyboss-pro' ), // Text string to display.
//             {
//                 isDismissible: true, // Whether the user can dismiss the notice.
//             }
//         );
//     }
// });

/**
 * A compare helper for lodash's difference by
 */
const compareBlocks = ( block ) => { return block.clientId };

/**
 * A change listener for blocks
 *
 * The subscribe on the 'core/editor' getBlocks() function fires on any change,
 * not just additions/removals. Therefore we actually compare the array with a
 * previous state and look for changes in length or uid.
 */
const onBlocksChangeListener = ( selector, listener ) => {
	let previousBlocks = selector();
	return () => {
		const selectedBlocks = selector();

		if( selectedBlocks.length !== previousBlocks.length ) {
			listener( selectedBlocks, previousBlocks );
			previousBlocks = selectedBlocks;
		} else if ( differenceBy( selectedBlocks, previousBlocks, compareBlocks ).length ) {
			listener( selectedBlocks, previousBlocks, differenceBy( selectedBlocks, previousBlocks, compareBlocks ) );
			previousBlocks = selectedBlocks;
		}
	}
}

let blockEditorLoaded = false;
let blockEditorLoadedInterval = setInterval( function () {
	if ( document.getElementById( 'post-title-0' ) || document.getElementById( 'post-title-1' ) ) {/*post-title-1 is ID of Post Title Textarea*/
		blockEditorLoaded = true;

		/**
		 * Subscribe to block data
		 *
		 * This function subscribes to block data, compares old and new states upon
		 * change and fires actions accordingly.
		 */
		wp.data.subscribe( onBlocksChangeListener( wp.data.select( 'core/block-editor' ).getBlocks, ( blocks, oldBlocks, difference = null ) => {
			let addedBlocks = differenceBy( blocks, oldBlocks, compareBlocks );
			let deletedBlocks = differenceBy( oldBlocks, blocks, compareBlocks );

			if ( oldBlocks.length == blocks.length && difference ) {

				// A block has been deleted
				for ( var i in deletedBlocks ) {
					const block = deletedBlocks[i];
					const actionName = 'blocks.transformed.from.' + camelCase( block.name );
					doAction( actionName, block );
				}

				// A block has been added
				for ( var i in addedBlocks ) {
					const block = addedBlocks[i];
					const actionName = 'blocks.transformed.to.' + camelCase( block.name );
					doAction( actionName, block );
				}
			}

			// A block has been added
			for ( var i in addedBlocks ) {
				const block = addedBlocks[i];
				const actionName = 'blocks.added.' + camelCase( block.name );
				doAction( actionName, block );
			}

			// A block has been deleted
			for ( var i in deletedBlocks ) {
				const block = deletedBlocks[i];
				const actionName = 'blocks.removed.' + camelCase( block.name );
				doAction( actionName, block );
			}
		} ) );
	}
	if ( blockEditorLoaded ) {
		clearInterval( blockEditorLoadedInterval );
	}
}, 500 );

/**
 * An action listener, which fires the deletion of the metadata
 * once the remove action is seen.
 */
addAction('blocks.added.bpZoomMeetingCreateMeeting', 'bpZoomMeetingCreateMeeting/addBlock', ( block ) => {
	block.attributes.meetingId = '';
	block.attributes.id = '';
});
addAction('blocks.added.bpZoomMeetingCreateWebinar', 'bpZoomMeetingCreateWebinar/addBlock', ( block ) => {
	block.attributes.webinarId = '';
	block.attributes.id = '';
});

// addAction('blocks.removed.bpZoomMeetingCreateMeeting', 'bpZoomMeetingCreateMeeting/removeBlock', ( block ) => {
// 	console.log('remove');
// });
