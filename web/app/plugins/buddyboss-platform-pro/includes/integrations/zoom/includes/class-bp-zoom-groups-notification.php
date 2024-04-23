<?php
/**
 * BuddyBoss Groups Notification Class.
 *
 * @package BuddyBoss/Groups
 *
 * @since 1.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Groups_Notification class.
 *
 * @since 1.2.1
 */
class BP_Zoom_Groups_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @since 1.2.1
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.2.1
	 *
	 * @return null|BP_Zoom_Groups_Notification|Controller|object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since 1.2.1
	 */
	public function __construct() {
		// Initialize.
		$this->start();
	}

	/**
	 * Initialize all methods inside it.
	 *
	 * @since 1.2.1
	 *
	 * @return mixed|void
	 */
	public function load() {
		if ( ! bbp_pro_is_license_valid() || ! bp_is_active( 'groups' ) || ! bp_zoom_is_zoom_groups_enabled() ) {
			return;
		}

		$this->register_notification_group(
			'groups',
			esc_html__( 'Social Groups', 'buddyboss-pro' ),
			esc_html__( 'Social Groups', 'buddyboss-pro' ),
			6
		);

		// Group zoom meeting schedule.
		$this->register_notification_for_group_zoom_schedule();
	}

	/**
	 * Register notification for meeting schedule.
	 *
	 * @since 1.2.1
	 */
	public function register_notification_for_group_zoom_schedule() {
		$this->register_notification_type(
			'bb_groups_new_zoom',
			esc_html__( 'New meeting or webinar is scheduled in one of your groups', 'buddyboss-pro' ),
			esc_html__( 'A Zoom meeting or webinar is scheduled in a group', 'buddyboss-pro' ),
			'groups'
		);

		$this->register_email_type(
			'zoom-scheduled-meeting-email',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} scheduled a Zoom Meeting in the group: "{{group.name}}"', 'buddyboss-pro' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> scheduled a Zoom Meeting in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{zoom_meeting}}}", 'buddyboss-pro' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} scheduled a Zoom Meeting in the group \"{{group.name}}\":\n\n{{{zoom_meeting}}}", 'buddyboss-pro' ),
				'situation_label'     => __( 'A Zoom meeting is scheduled in a group', 'buddyboss-pro' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone schedules a meeting in a group.', 'buddyboss-pro' ),
			),
			'bb_groups_new_zoom'
		);

		$this->register_email_type(
			'zoom-scheduled-webinar-email',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} scheduled a Zoom Webinar in the group: "{{group.name}}"', 'buddyboss-pro' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> scheduled a Zoom Webinar in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{zoom_webinar}}}", 'buddyboss-pro' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} scheduled a Zoom Webinar in the group \"{{group.name}}\":\n\n{{{zoom_webinar}}}", 'buddyboss-pro' ),
				'situation_label'     => __( 'A Zoom webinar is scheduled in a group', 'buddyboss-pro' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone schedules a webinar in a group.', 'buddyboss-pro' ),
			),
			'bb_groups_new_zoom'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_zoom',
			'bb_groups_new_zoom',
			'bb-icon-f bb-icon-calendar'
		);

		$this->register_notification_filter(
			__( 'Group meetings and webinars', 'buddyboss-pro' ),
			array( 'bb_groups_new_zoom' ),
			95
		);

		add_action( 'bp_groups_bb_groups_new_zoom_notification', array( $this, 'bb_render_meeting_notification' ), 10, 7 );
	}

	/**
	 * Format the notifications.
	 *
	 * @since 1.2.1
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $action_item_count, $component_action_name, $component_name, $notification_id, $screen ) {
		return $content;
	}

	/**
	 * Create meeting modern notification for groups.
	 *
	 * @since 1.2.1
	 *
	 * @param string $content            Notification content.
	 * @param int    $item_id           Item for notification.
	 * @param int    $secondary_item_id Secondary item for notification.
	 * @param int    $total_items       Total items.
	 * @param string $format            Format html or string.
	 * @param int    $notification_id   Notification ID.
	 * @param string $screen            Notification Screen type.
	 *
	 * @return mixed|void
	 */
	public function bb_render_meeting_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {
		$group_id          = $item_id;
		$group             = groups_get_group( $group_id );
		$group_name        = bp_get_group_name( $group );
		$group_link        = bp_get_group_permalink( $group );
		$amount            = 'single';
		$start_date        = '';
		$notification_link = '';
		$text              = '';

		$type       = bp_notifications_get_meta( $notification_id, 'type' );
		$is_created = bp_notifications_get_meta( $notification_id, 'is_created' );

		// Check the type of zoom like is webinar or meeting.
		if ( 'meeting' === $type ) {
			$meeting = new BP_Zoom_Meeting( $secondary_item_id );
		} else {
			$meeting = new BP_Zoom_Webinar( $secondary_item_id );
		}

		if ( property_exists( $meeting, 'start_date_utc' ) && ! empty( $meeting->start_date_utc ) ) {
			$start_date = new DateTime( $meeting->start_date_utc, new DateTimeZone( $meeting->timezone ) );
			$start_date = $start_date->format( 'd-m-Y' );
		}

		if ( 'web_push' === $screen ) {
			if ( 'meeting' === $type ) {
				if ( ! empty( $start_date ) ) {
					if ( $is_created ) {
						$text = sprintf(
							/* translators: %s: The meeting start date. */
							__( 'New meeting scheduled for %s', 'buddyboss-pro' ),
							$start_date
						);
					} else {
						$text = sprintf(
							/* translators: %s: The meeting start date. */
							__( 'Update meeting scheduled for %s', 'buddyboss-pro' ),
							$start_date
						);
					}
				} else {
					if ( $is_created ) {
						$text = __( 'New meeting scheduled', 'buddyboss-pro' );
					} else {
						$text = __( 'Update meeting scheduled', 'buddyboss-pro' );
					}
				}

				$notification_link = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'bp_mark_read',
							'group_id'   => $item_id,
							'meeting_id' => $secondary_item_id,
						),
						$group_link . 'zoom/meetings/' . $secondary_item_id
					),
					'bp_mark_meeting_' . $item_id
				);
			} elseif ( 'webinar' === $type ) {
				if ( ! empty( $start_date ) ) {
					if ( $is_created ) {
						$text = sprintf(
						/* translators: %s: The meeting start date */
							__( 'New webinar scheduled for %s', 'buddyboss-pro' ),
							$start_date
						);
					} else {
						$text = sprintf(
						/* translators: %s: The meeting start date. */
							__( 'Update webinar scheduled for %s', 'buddyboss-pro' ),
							$start_date
						);
					}
				} else {
					if ( $is_created ) {
						$text = __( 'New webinar scheduled', 'buddyboss-pro' );
					} else {
						$text = __( 'Update webinar scheduled', 'buddyboss-pro' );
					}
				}

				$notification_link = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'bp_mark_read',
							'group_id'   => $item_id,
							'webinar_id' => $secondary_item_id,
						),
						$group_link . 'zoom/webinars/' . $secondary_item_id
					),
					'bp_mark_webinar_' . $item_id
				);
			}
		} else {
			if ( (int) $total_items > 1 ) {

				if ( 'meeting' === $type ) {
					$text = sprintf(
					/* translators: total number of groups. */
						esc_html__( 'You have %1$d new Zoom meetings in groups', 'buddyboss-pro' ),
						(int) $total_items
					);
				} else {
					$text = sprintf(
					/* translators: total number of groups. */
						esc_html__( 'You have %1$d new Zoom webinars in groups', 'buddyboss-pro' ),
						(int) $total_items
					);
				}
				$amount            = 'multiple';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			} else {
				if ( 'meeting' === $type ) {
					if ( ! empty( $start_date ) ) {
						if ( $is_created ) {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: 1. Group name. 2. The meeting start date. */
									esc_html__( '%1$s: New meeting scheduled for %2$s', 'buddyboss-pro' ),
									$group_name,
									$start_date
								);
							} else {
								$text = sprintf(
								/* translators: %s: The meeting start date */
									esc_html__( 'New meeting scheduled for %1$s', 'buddyboss-pro' ),
									$start_date
								);
							}
						} else {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: 1. Group name. 2. The meeting start date. */
									esc_html__( '%1$s: Update meeting scheduled for %2$s', 'buddyboss-pro' ),
									$group_name,
									$start_date
								);
							} else {
								$text = sprintf(
								/* translators: %s: The meeting start date */
									esc_html__( 'Update meeting scheduled for %1$s', 'buddyboss-pro' ),
									$start_date
								);
							}
						}
					} else {
						if ( $is_created ) {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: %s: Group name */
									esc_html__( '%1$s: New meeting scheduled', 'buddyboss-pro' ),
									$group_name
								);
							} else {
								$text = esc_html__( 'New meeting scheduled', 'buddyboss-pro' );
							}
						} else {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: %s: Group name */
									esc_html__( '%1$s: Update meeting scheduled', 'buddyboss-pro' ),
									$group_name
								);
							} else {
								$text = esc_html__( 'Update meeting scheduled', 'buddyboss-pro' );
							}
						}
					}

					$notification_link = wp_nonce_url(
						add_query_arg(
							array(
								'action'     => 'bp_mark_read',
								'group_id'   => $item_id,
								'meeting_id' => $secondary_item_id,
							),
							$group_link . 'zoom/meetings/' . $secondary_item_id
						),
						'bp_mark_meeting_' . $item_id
					);
				} elseif ( 'webinar' === $type ) {
					if ( ! empty( $start_date ) ) {
						if ( $is_created ) {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: 1. Group name. 2. The meeting start date. */
									esc_html__( '%1$s: New webinar scheduled for %2$s', 'buddyboss-pro' ),
									$group_name,
									$start_date
								);
							} else {
								$text = sprintf(
								/* translators: %s: The meeting start date */
									esc_html__( 'New webinar scheduled for %1$s', 'buddyboss-pro' ),
									$start_date
								);
							}
						} else {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: 1. Group name. 2. The meeting start date. */
									esc_html__( '%1$s: Update webinar scheduled for %2$s', 'buddyboss-pro' ),
									$group_name,
									$start_date
								);
							} else {
								$text = sprintf(
								/* translators: %s: The meeting start date */
									esc_html__( 'Update webinar scheduled for %1$s', 'buddyboss-pro' ),
									$start_date
								);
							}
						}
					} else {
						if ( $is_created ) {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: %s: Group name */
									esc_html__( '%1$s: New webinar scheduled', 'buddyboss-pro' ),
									$group_name
								);
							} else {
								$text = esc_html__( 'New webinar scheduled', 'buddyboss-pro' );
							}
						} else {
							if ( ! empty( $group_name ) ) {
								$text = sprintf(
								/* translators: %s: Group name */
									esc_html__( '%1$s: Update webinar scheduled', 'buddyboss-pro' ),
									$group_name
								);
							} else {
								$text = esc_html__( 'Update webinar scheduled', 'buddyboss-pro' );
							}
						}
					}

					$notification_link = wp_nonce_url(
						add_query_arg(
							array(
								'action'     => 'bp_mark_read',
								'group_id'   => $item_id,
								'webinar_id' => $secondary_item_id,
							),
							$group_link . 'zoom/webinars/' . $secondary_item_id
						),
						'bp_mark_webinar_' . $item_id
					);
				}
			}
		}

		$content = apply_filters(
			'bb_groups_' . $amount . '_bb_groups_new_zoom_notification',
			array(
				'link'  => $notification_link,
				'text'  => $text,
				'title' => $group_name,
				'image' => bb_notification_avatar_url( bp_notifications_get_notification( $notification_id ) ),
			),
			$group_link,
			$group->name,
			$text,
			$notification_link,
			$screen
		);

		// Validate the return value & return if validated.
		if (
			! empty( $content ) &&
			is_array( $content ) &&
			isset( $content['text'] ) &&
			isset( $content['link'] )
		) {
			if ( 'string' === $format ) {
				if ( empty( $content['link'] ) ) {
					$content = esc_html( $content['text'] );
				} else {
					$content = '<a href="' . esc_url( $content['link'] ) . '">' . esc_html( $content['text'] ) . '</a>';
				}
			} else {
				$content = array(
					'text'  => $content['text'],
					'link'  => $content['link'],
					'title' => ( isset( $content['title'] ) ? $content['title'] : '' ),
					'image' => ( isset( $content['image'] ) ? $content['image'] : '' ),
				);
			}
		}

		return $content;
	}

}
