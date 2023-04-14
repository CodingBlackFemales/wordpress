<?php
/**
 * Icon Picker Type BuddyBoss Legacy
 *
 * @package Icon_Picker
 */

require_once dirname( __FILE__ ) . '/font.php';

/**
 * BuddyBoss Legacy Icons
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_BuddyBoss_Legacy extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'buddyboss_legacy';

	/**
	 * Icon type version.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '1.0';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'BuddyBoss (Legacy)', 'buddyboss-theme' );

		parent::__construct( $args );
	}

	/**
	 * Get icon groups.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_groups() {
		$groups = array(
			array(
				'id'   => 'alert',
				'name' => esc_html__( 'Alerts', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'arrow',
				'name' => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'brand',
				'name' => esc_html__( 'Brands', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'chart',
				'name' => esc_html__( 'Charts', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'cloud',
				'name' => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'courses',
				'name' => esc_html__( 'Courses', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'device',
				'name' => esc_html__( 'Devices', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-doc',
				'name'  => esc_html__( 'Doc', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'file',
				'name' => esc_html__( 'File Types', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'format',
				'name' => esc_html__( 'Formatting', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'control',
				'name' => esc_html__( 'Form Controls', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'map',
				'name' => esc_html__( 'Maps', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'media',
				'name' => esc_html__( 'Media Player', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'misc',
				'name' => esc_html__( 'Misc.', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'nature',
				'name' => esc_html__( 'Nature', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'phone',
				'name' => esc_html__( 'Phone Calls', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'web',
				'name' => esc_html__( 'Web Application', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter buddyboss_legacy groups.
		 *
		 * @since 2.0.0
		 *
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_buddyboss_legacy_groups', $groups );

		return $groups;
	}

	/**
	 * Get icon names.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_items() {
		$items = array(
			array(
				'group' => 'web',
				'id'    => 'bb-icon-activity',
				'name'  => esc_html__( 'Activity', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-airplay',
				'name'  => esc_html__( 'Airplay', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-alert-exclamation',
				'name'  => esc_html__( 'Alert: Exclamation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-alert-octagon',
				'name'  => esc_html__( 'Alert: Octagon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-alert-question',
				'name'  => esc_html__( 'Alert: Question', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-alert-thin',
				'name'  => esc_html__( 'Alert: Thin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-alert-triangle',
				'name'  => esc_html__( 'Alert: Triangle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-align-center',
				'name'  => esc_html__( 'Align: Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-align-justify',
				'name'  => esc_html__( 'Align: Justify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-align-left',
				'name'  => esc_html__( 'Align: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-align-right',
				'name'  => esc_html__( 'Align: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-anchor',
				'name'  => esc_html__( 'Anchor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-angle-down',
				'name'  => esc_html__( 'Angle: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-angle-left',
				'name'  => esc_html__( 'Angle: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-angle-right',
				'name'  => esc_html__( 'Angle: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-aperture',
				'name'  => esc_html__( 'Aperture', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-up-square',
				'name'  => esc_html__( 'Arrow Up: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-circle',
				'name'  => esc_html__( 'Arrow: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-down',
				'name'  => esc_html__( 'Arrow: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-down-left',
				'name'  => esc_html__( 'Arrow: Down Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-down-right',
				'name'  => esc_html__( 'Arrow: Down Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-left',
				'name'  => esc_html__( 'Arrow: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-right',
				'name'  => esc_html__( 'Arrow: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-up',
				'name'  => esc_html__( 'Arrow: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-up-left',
				'name'  => esc_html__( 'Arrow: Up Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-arrow-up-right',
				'name'  => esc_html__( 'Arrow: Up Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-at-sign',
				'name'  => esc_html__( 'At Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-attach',
				'name'  => esc_html__( 'Attach', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-attach-fill',
				'name'  => esc_html__( 'Attach: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-award',
				'name'  => esc_html__( 'Award', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-badge',
				'name'  => esc_html__( 'Badge', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-badge-tall',
				'name'  => esc_html__( 'Badge: Tall', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-ball-soccer',
				'name'  => esc_html__( 'Ball: Soccer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'bb-icon-bar-chart',
				'name'  => esc_html__( 'Bar Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'bb-icon-bar-chart-square',
				'name'  => esc_html__( 'Bar Chart: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'bb-icon-bar-chart-up',
				'name'  => esc_html__( 'Bar Chart: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-battery',
				'name'  => esc_html__( 'Battery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-battery-charging',
				'name'  => esc_html__( 'Battery: Charging', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bell',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bell-off',
				'name'  => esc_html__( 'Bell: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bell-plus',
				'name'  => esc_html__( 'Bell: Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bell-small',
				'name'  => esc_html__( 'Bell: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bluetooth',
				'name'  => esc_html__( 'Bluetooth', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-board',
				'name'  => esc_html__( 'Board', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-board-box',
				'name'  => esc_html__( 'Board: Box', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-board-code',
				'name'  => esc_html__( 'Board: Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-board-list',
				'name'  => esc_html__( 'Board: List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-book-open',
				'name'  => esc_html__( 'Book: Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-book-round',
				'name'  => esc_html__( 'Book: Round', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bookmark',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bookmark-small',
				'name'  => esc_html__( 'Bookmark: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-bookmark-small-fill',
				'name'  => esc_html__( 'Bookmark: Small-Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-box',
				'name'  => esc_html__( 'Box', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-briefcase',
				'name'  => esc_html__( 'Briefcase', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-bulb',
				'name'  => esc_html__( 'Bulb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-bullhorn',
				'name'  => esc_html__( 'Bullhorn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-bullhorn-filled',
				'name'  => esc_html__( 'Bullhorn: Filled', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-list-view',
				'name'  => esc_html__( 'Bulleted List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-cpu',
				'name'  => esc_html__( 'CPU', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-calendar',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-calendar-small',
				'name'  => esc_html__( 'Calendar: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-camera',
				'name'  => esc_html__( 'Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-camera-fill',
				'name'  => esc_html__( 'Camera: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-camera-off',
				'name'  => esc_html__( 'Camera: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-camera-small',
				'name'  => esc_html__( 'Camera: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-car-small',
				'name'  => esc_html__( 'Car: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-cast',
				'name'  => esc_html__( 'Cast', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-chat',
				'name'  => esc_html__( 'Chat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-check',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-check-circle',
				'name'  => esc_html__( 'Check: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-check-small',
				'name'  => esc_html__( 'Check: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-check-square',
				'name'  => esc_html__( 'Check: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-check-square-small',
				'name'  => esc_html__( 'Check: Square-Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevron-down',
				'name'  => esc_html__( 'Chevron: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevron-left',
				'name'  => esc_html__( 'Chevron: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevron-right',
				'name'  => esc_html__( 'Chevron: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevron-up',
				'name'  => esc_html__( 'Chevron: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevrons-down',
				'name'  => esc_html__( 'Chevrons: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevrons-left',
				'name'  => esc_html__( 'Chevrons: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevrons-right',
				'name'  => esc_html__( 'Chevrons: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-chevrons-up',
				'name'  => esc_html__( 'Chevrons: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-chrome',
				'name'  => esc_html__( 'Chrome', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-circle',
				'name'  => esc_html__( 'Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-clipboard',
				'name'  => esc_html__( 'Clipboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-clock',
				'name'  => esc_html__( 'Clock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-clock-small',
				'name'  => esc_html__( 'Clock: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-close',
				'name'  => esc_html__( 'Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-close-circle',
				'name'  => esc_html__( 'Close: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-download',
				'name'  => esc_html__( 'Cloud: Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-drizzle',
				'name'  => esc_html__( 'Cloud: Drizzle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-lightning',
				'name'  => esc_html__( 'Cloud: Lightning', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-off',
				'name'  => esc_html__( 'Cloud: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-rain',
				'name'  => esc_html__( 'Cloud: Rain', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-snow',
				'name'  => esc_html__( 'Cloud: Snow', 'buddyboss-theme' ),
			),
			array(
				'group' => 'cloud',
				'id'    => 'bb-icon-cloud-upload',
				'name'  => esc_html__( 'Cloud: Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-codepen',
				'name'  => esc_html__( 'Codepen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-code-format',
				'name'  => esc_html__( 'Code: format', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-command',
				'name'  => esc_html__( 'Command', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-comment',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-activity-comment',
				'name'  => esc_html__( 'Comment: Activity', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-comment-circle',
				'name'  => esc_html__( 'Comment: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-comment-square',
				'name'  => esc_html__( 'Comment: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-compass',
				'name'  => esc_html__( 'Compass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connected',
				'name'  => esc_html__( 'Connected', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connect-user',
				'name'  => esc_html__( 'User: Connect', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connected-filled',
				'name'  => esc_html__( 'Connection: Filled', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connection-minus',
				'name'  => esc_html__( 'Connection: Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connection-waiting',
				'name'  => esc_html__( 'Connection: Pending', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connection-waiting-filled',
				'name'  => esc_html__( 'Connection: Pending Filled', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connection-remove',
				'name'  => esc_html__( 'Connection: Remove', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connection-request',
				'name'  => esc_html__( 'Connection: Request', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-connections',
				'name'  => esc_html__( 'Connections', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-copy',
				'name'  => esc_html__( 'Copy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-down-left',
				'name'  => esc_html__( 'Corner: Down Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-down-right',
				'name'  => esc_html__( 'Corner: Down Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-left-down',
				'name'  => esc_html__( 'Corner: Left Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-left-up',
				'name'  => esc_html__( 'Corner: Left Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-right-down',
				'name'  => esc_html__( 'Corner: Right Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-right-up',
				'name'  => esc_html__( 'Corner: Right Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-up-left',
				'name'  => esc_html__( 'Corner: Up Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-corner-up-right',
				'name'  => esc_html__( 'Corner: Up Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-credit-card',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-crop',
				'name'  => esc_html__( 'Crop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-crosshair',
				'name'  => esc_html__( 'Crosshair', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-cube',
				'name'  => esc_html__( 'Cube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-delete',
				'name'  => esc_html__( 'Delete', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-disc',
				'name'  => esc_html__( 'Disc', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-discussion',
				'name'  => esc_html__( 'Discussion', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-dribbble',
				'name'  => esc_html__( 'Dribbble: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-email',
				'name'  => esc_html__( 'Email: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-github',
				'name'  => esc_html__( 'Github: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-droplet',
				'name'  => esc_html__( 'Droplet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-edit',
				'name'  => esc_html__( 'Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-edit-square',
				'name'  => esc_html__( 'Edit: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-edit-square-small',
				'name'  => esc_html__( 'Edit: Square-Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-edit-thin',
				'name'  => esc_html__( 'Edit: Thin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-eye',
				'name'  => esc_html__( 'Eye', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-eye-off',
				'name'  => esc_html__( 'Eye: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-eye-small',
				'name'  => esc_html__( 'Eye: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-facebook',
				'name'  => esc_html__( 'Facebook: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-facebook',
				'name'  => esc_html__( 'Facebook: Round', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-facebook-small',
				'name'  => esc_html__( 'Facebook: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-fast-forward',
				'name'  => esc_html__( 'Fast Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-feather',
				'name'  => esc_html__( 'Feather', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-7z',
				'name'  => esc_html__( 'File: 7z', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-abw',
				'name'  => esc_html__( 'File: ABW', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ace',
				'name'  => esc_html__( 'File: ACE', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ai',
				'name'  => esc_html__( 'File: AI', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-apk',
				'name'  => esc_html__( 'File: APK', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-css',
				'name'  => esc_html__( 'File: CSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-csv',
				'name'  => esc_html__( 'File: CSV', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-code',
				'name'  => esc_html__( 'File: Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-doc',
				'name'  => esc_html__( 'File: DOC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-docm',
				'name'  => esc_html__( 'File: DOCM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-docx',
				'name'  => esc_html__( 'File: DOCX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-dotm',
				'name'  => esc_html__( 'File: DOTM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-dotx',
				'name'  => esc_html__( 'File: DOTX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-default',
				'name'  => esc_html__( 'File: Default', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-gif',
				'name'  => esc_html__( 'File: GIF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-gzip',
				'name'  => esc_html__( 'File: GZIP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-hlam',
				'name'  => esc_html__( 'File: HLAM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-hlsb',
				'name'  => esc_html__( 'File: HLSB', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-hlsm',
				'name'  => esc_html__( 'File: HLSM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-htm',
				'name'  => esc_html__( 'File: HTM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-html',
				'name'  => esc_html__( 'File: HTML', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ico',
				'name'  => esc_html__( 'File: ICO', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ics',
				'name'  => esc_html__( 'File: ICS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ipa',
				'name'  => esc_html__( 'File: IPA', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-jar',
				'name'  => esc_html__( 'File: JAR', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-jpg',
				'name'  => esc_html__( 'File: JPG', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-js',
				'name'  => esc_html__( 'File: JS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-mp3',
				'name'  => esc_html__( 'File: MP3', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-minus',
				'name'  => esc_html__( 'File: Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ods',
				'name'  => esc_html__( 'File: ODS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-odt',
				'name'  => esc_html__( 'File: ODT', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-pdf',
				'name'  => esc_html__( 'File: PDF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-png',
				'name'  => esc_html__( 'File: PNG', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-potm',
				'name'  => esc_html__( 'File: POTM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-potx',
				'name'  => esc_html__( 'File: POTX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-pps',
				'name'  => esc_html__( 'File: PPS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ppsx',
				'name'  => esc_html__( 'File: PPSX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-ppt',
				'name'  => esc_html__( 'File: PPT', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-pptm',
				'name'  => esc_html__( 'File: PPTM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-pptx',
				'name'  => esc_html__( 'File: PPTX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-psd',
				'name'  => esc_html__( 'File: PSD', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-plus',
				'name'  => esc_html__( 'File: Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-rar',
				'name'  => esc_html__( 'File: RAR', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-rss',
				'name'  => esc_html__( 'File: RSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-rtf',
				'name'  => esc_html__( 'File: RTF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-svg',
				'name'  => esc_html__( 'File: SVG', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-sketch',
				'name'  => esc_html__( 'File: Sketch', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-tar',
				'name'  => esc_html__( 'File: TAR', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-txt',
				'name'  => esc_html__( 'File: Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-vcf',
				'name'  => esc_html__( 'File: VCF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-video',
				'name'  => esc_html__( 'File: Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-wav',
				'name'  => esc_html__( 'File: WAV', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-xls',
				'name'  => esc_html__( 'File: XLS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-xlsx',
				'name'  => esc_html__( 'File: XLSX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-xltm',
				'name'  => esc_html__( 'File: XLTM', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-xltx',
				'name'  => esc_html__( 'File: XLTX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-xml',
				'name'  => esc_html__( 'File: XML', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-yaml',
				'name'  => esc_html__( 'File: YAML', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-file-zip',
				'name'  => esc_html__( 'File: ZIP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-film',
				'name'  => esc_html__( 'Film', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-flag-small',
				'name'  => esc_html__( 'Flag: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-flickr',
				'name'  => esc_html__( 'Flickr: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-folder',
				'name'  => esc_html__( 'Folder', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-folder-stacked',
				'name'  => esc_html__( 'Folder: stacked', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-forest',
				'name'  => esc_html__( 'Forest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-gif',
				'name'  => esc_html__( 'GIF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-gear',
				'name'  => esc_html__( 'Gear', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-generic',
				'name'  => esc_html__( 'Generic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'bb-icon-github',
				'name'  => esc_html__( 'Github', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-gitlab',
				'name'  => esc_html__( 'Gitlab', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-globe',
				'name'  => esc_html__( 'Globe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-google-plus',
				'name'  => esc_html__( 'Google Plus: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-graduation-cap',
				'name'  => esc_html__( 'Graduation Cap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-grid-round',
				'name'  => esc_html__( 'Grid Round', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-grid-view',
				'name'  => esc_html__( 'Grid View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-grid-view-small',
				'name'  => esc_html__( 'Grid View: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-groups',
				'name'  => esc_html__( 'Groups', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-hash',
				'name'  => esc_html__( 'Hash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-headphones',
				'name'  => esc_html__( 'Headphones', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-headphones-small',
				'name'  => esc_html__( 'Headphones: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-heart',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-heart-fill',
				'name'  => esc_html__( 'Heart: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-heart-small',
				'name'  => esc_html__( 'Heart: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-help-circle',
				'name'  => esc_html__( 'Help: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-home-small',
				'name'  => esc_html__( 'Home: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-id-card',
				'name'  => esc_html__( 'ID Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-image',
				'name'  => esc_html__( 'Image', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-image-square',
				'name'  => esc_html__( 'Image: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-inbox',
				'name'  => esc_html__( 'Inbox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-inbox-o',
				'name'  => esc_html__( 'Inbox: Outline', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-inbox-small',
				'name'  => esc_html__( 'Inbox: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-info',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-info-circle',
				'name'  => esc_html__( 'Info: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-instagram',
				'name'  => esc_html__( 'Instagram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-instagram',
				'name'  => esc_html__( 'Instagram: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-key',
				'name'  => esc_html__( 'Key', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-laugh',
				'name'  => esc_html__( 'Laugh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-layers',
				'name'  => esc_html__( 'Layers', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-layout',
				'name'  => esc_html__( 'Layout', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-life-buoy',
				'name'  => esc_html__( 'Life Buoy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-like',
				'name'  => esc_html__( 'Like', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-link',
				'name'  => esc_html__( 'Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-link-1',
				'name'  => esc_html__( 'Link: 1', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-link-2',
				'name'  => esc_html__( 'Link: 2', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-link-3',
				'name'  => esc_html__( 'Link: 3', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-link-tilt',
				'name'  => esc_html__( 'Link: Tilt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-external-link',
				'name'  => esc_html__( 'Link: External', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-linkedin',
				'name'  => esc_html__( 'Linkedin: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-list-view-small',
				'name'  => esc_html__( 'List View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-list-bookmark',
				'name'  => esc_html__( 'List: Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-all-members',
				'name'  => esc_html__( 'Members: All', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-all-results',
				'name'  => esc_html__( 'List: Bullets', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-list-doc',
				'name'  => esc_html__( 'List: Doc', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-loader',
				'name'  => esc_html__( 'Loader', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-loader-small',
				'name'  => esc_html__( 'Loader: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-lock-fill',
				'name'  => esc_html__( 'Lock: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-lock-small',
				'name'  => esc_html__( 'Lock: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-log-in',
				'name'  => esc_html__( 'Log In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-log-out',
				'name'  => esc_html__( 'Log Out', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-mail',
				'name'  => esc_html__( 'Mail', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-mail-open',
				'name'  => esc_html__( 'Mail: Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-mail-small',
				'name'  => esc_html__( 'Mail: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-map',
				'name'  => esc_html__( 'Map', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-map-pin',
				'name'  => esc_html__( 'Map Pin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-map-pin-small',
				'name'  => esc_html__( 'Map Pin: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-maximize',
				'name'  => esc_html__( 'Maximize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-maximize-square',
				'name'  => esc_html__( 'Maximize: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-medium',
				'name'  => esc_html__( 'Medium', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-meetup',
				'name'  => esc_html__( 'Meetup', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-media',
				'name'  => esc_html__( 'Media', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-members',
				'name'  => esc_html__( 'Members', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-membership',
				'name'  => esc_html__( 'Membership', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-menu',
				'name'  => esc_html__( 'Menu', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-menu-dots-h',
				'name'  => esc_html__( 'Menu Dots: Horz', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-menu-dots-v',
				'name'  => esc_html__( 'Menu Dots: Vert', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-menu-left',
				'name'  => esc_html__( 'Menu: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-mic',
				'name'  => esc_html__( 'Mic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-mic-off',
				'name'  => esc_html__( 'Mic: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-minimize',
				'name'  => esc_html__( 'Minimize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-minimize-square',
				'name'  => esc_html__( 'Minimize: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-minus-circle',
				'name'  => esc_html__( 'Minus: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-minus-square',
				'name'  => esc_html__( 'Minus: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-monitor',
				'name'  => esc_html__( 'Monitor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-moon',
				'name'  => esc_html__( 'Moon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-moon-circle',
				'name'  => esc_html__( 'Moon: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-more-h',
				'name'  => esc_html__( 'More: Horz', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-more-v',
				'name'  => esc_html__( 'More: Vert', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-move',
				'name'  => esc_html__( 'Move', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-music',
				'name'  => esc_html__( 'Music', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-my-connections',
				'name'  => esc_html__( 'My Connections', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-navigation',
				'name'  => esc_html__( 'Navigation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'map',
				'id'    => 'bb-icon-navigation-up',
				'name'  => esc_html__( 'Navigation: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-octagon',
				'name'  => esc_html__( 'Octagon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-only-me',
				'name'  => esc_html__( 'Only me', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-package',
				'name'  => esc_html__( 'Package', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-paperclip',
				'name'  => esc_html__( 'Paperclip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-paragraph-bullet',
				'name'  => esc_html__( 'Paragraph: Bullet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-paragraph-numbers',
				'name'  => esc_html__( 'Paragraph: Numbers', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-pause-circle',
				'name'  => esc_html__( 'Pause: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-pencil',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-percent',
				'name'  => esc_html__( 'Percent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-call',
				'name'  => esc_html__( 'Phone: Call', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-forwarded',
				'name'  => esc_html__( 'Phone: Forwarded', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-incoming',
				'name'  => esc_html__( 'Phone: Incoming', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-missed',
				'name'  => esc_html__( 'Phone: Missed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-off',
				'name'  => esc_html__( 'Phone: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-phone-outgoing',
				'name'  => esc_html__( 'Phone: Outgoing', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'bb-icon-pie-chart',
				'name'  => esc_html__( 'Pie Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-pinterest',
				'name'  => esc_html__( 'Pinterest: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-pizza-slice',
				'name'  => esc_html__( 'Pizza Slice', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-plane',
				'name'  => esc_html__( 'Plane', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-play-circle',
				'name'  => esc_html__( 'Play: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-play-circle-fill',
				'name'  => esc_html__( 'Play: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-play-square',
				'name'  => esc_html__( 'Play: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-play-thin',
				'name'  => esc_html__( 'Play: Thin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-plus-circle',
				'name'  => esc_html__( 'Plus: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-plus-square',
				'name'  => esc_html__( 'Plus: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-pocket',
				'name'  => esc_html__( 'Pocket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-power',
				'name'  => esc_html__( 'Power', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-power-small',
				'name'  => esc_html__( 'Power: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-print',
				'name'  => esc_html__( 'Print', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-print-fill',
				'name'  => esc_html__( 'Print: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-profile',
				'name'  => esc_html__( 'Profile', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-profile-info',
				'name'  => esc_html__( 'Profile: Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-profile-types',
				'name'  => esc_html__( 'Profile: Type', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-public',
				'name'  => esc_html__( 'Public', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-question-thin',
				'name'  => esc_html__( 'Question: Thin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-quora',
				'name'  => esc_html__( 'Quora', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-quote',
				'name'  => esc_html__( 'Quote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-rss-square',
				'name'  => esc_html__( 'RSS: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-radio',
				'name'  => esc_html__( 'Radio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-reddit',
				'name'  => esc_html__( 'Reddit: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-rss',
				'name'  => esc_html__( 'RSS: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-skype',
				'name'  => esc_html__( 'Skype: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-vimeo',
				'name'  => esc_html__( 'Vimeo: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-vk',
				'name'  => esc_html__( 'VK: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-xing',
				'name'  => esc_html__( 'XING: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-refresh-ccw',
				'name'  => esc_html__( 'Refresh: CCW', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-refresh-cw',
				'name'  => esc_html__( 'Refresh: CW', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-repeat',
				'name'  => esc_html__( 'Repeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-forum-replies',
				'name'  => esc_html__( 'Replies', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-reply',
				'name'  => esc_html__( 'Reply', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-report',
				'name'  => esc_html__( 'Report', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-rewind',
				'name'  => esc_html__( 'Rewind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-rocket',
				'name'  => esc_html__( 'Rocket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-rotate-ccw',
				'name'  => esc_html__( 'Rotate: CCW', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-rotate-cw',
				'name'  => esc_html__( 'Rotate: CW', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-save',
				'name'  => esc_html__( 'Save', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-sync',
				'name'  => esc_html__( 'Sync', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-scissors',
				'name'  => esc_html__( 'Scissors', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-search',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-search-small',
				'name'  => esc_html__( 'Search: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-select',
				'name'  => esc_html__( 'Select', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-server',
				'name'  => esc_html__( 'Server', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-settings',
				'name'  => esc_html__( 'Settings', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-settings-small',
				'name'  => esc_html__( 'Settings: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-share-small',
				'name'  => esc_html__( 'Share: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-shield',
				'name'  => esc_html__( 'Shield', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-shopping-cart',
				'name'  => esc_html__( 'Shopping Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-shuffle',
				'name'  => esc_html__( 'Shuffle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-sidebar',
				'name'  => esc_html__( 'Sidebar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-skip-back',
				'name'  => esc_html__( 'Skip: Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-skip-forward',
				'name'  => esc_html__( 'Skip: Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-slack',
				'name'  => esc_html__( 'Slack', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alert',
				'id'    => 'bb-icon-slash',
				'name'  => esc_html__( 'Slash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-sliders',
				'name'  => esc_html__( 'Sliders', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-smartphone',
				'name'  => esc_html__( 'Smartphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-smile',
				'name'  => esc_html__( 'Smile', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-sort',
				'name'  => esc_html__( 'Sort', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-sort-desc',
				'name'  => esc_html__( 'Sort: Desc', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-speaker',
				'name'  => esc_html__( 'Speaker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-spin',
				'name'  => esc_html__( 'Spin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-spin-small',
				'name'  => esc_html__( 'Spin: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-square',
				'name'  => esc_html__( 'Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-star',
				'name'  => esc_html__( 'Star', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-star-fill',
				'name'  => esc_html__( 'Star: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-star-small',
				'name'  => esc_html__( 'Star: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-star-small-fill',
				'name'  => esc_html__( 'Star: Small-Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-stop-circle',
				'name'  => esc_html__( 'Stop: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-sun',
				'name'  => esc_html__( 'Sun', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-sunrise',
				'name'  => esc_html__( 'Sunrise', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-swap',
				'name'  => esc_html__( 'Swap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'mics',
				'id'    => 'bb-icon-tv',
				'name'  => esc_html__( 'TV', 'buddyboss-theme' ),
			),
			array(
				'group' => 'device',
				'id'    => 'bb-icon-tablet',
				'name'  => esc_html__( 'Tablet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-tag',
				'name'  => esc_html__( 'Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-target',
				'name'  => esc_html__( 'Target', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-text',
				'name'  => esc_html__( 'Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-text-format',
				'name'  => esc_html__( 'Text: format', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-thermometer',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-thumbs-down',
				'name'  => esc_html__( 'Thumbs: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-thumbs-up',
				'name'  => esc_html__( 'Thumbs: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-toggle-left',
				'name'  => esc_html__( 'Toggle: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-toggle-right',
				'name'  => esc_html__( 'Toggle: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-tools',
				'name'  => esc_html__( 'Tools', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-trash-empty',
				'name'  => esc_html__( 'Trash: Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-trash-small',
				'name'  => esc_html__( 'Trash: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-trending-down',
				'name'  => esc_html__( 'Trending: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrow',
				'id'    => 'bb-icon-trending-up',
				'name'  => esc_html__( 'Trending: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-triangle',
				'name'  => esc_html__( 'Triangle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-triangle-fill',
				'name'  => esc_html__( 'Triangle: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-tumblr',
				'name'  => esc_html__( 'Tumblr: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-twitter',
				'name'  => esc_html__( 'Twitter: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-twitter-small',
				'name'  => esc_html__( 'Twitter: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-type',
				'name'  => esc_html__( 'Type', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-umbrella',
				'name'  => esc_html__( 'Umbrella', 'buddyboss-theme' ),
			),
			array(
				'group' => 'format',
				'id'    => 'bb-icon-underline',
				'name'  => esc_html__( 'Underline', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-unlock',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-upload',
				'name'  => esc_html__( 'Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-alt',
				'name'  => esc_html__( 'User: Alt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-check',
				'name'  => esc_html__( 'User: Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-circle',
				'name'  => esc_html__( 'User: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-doc',
				'name'  => esc_html__( 'User: Doc', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-minus',
				'name'  => esc_html__( 'User: Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-plus',
				'name'  => esc_html__( 'User: Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-small',
				'name'  => esc_html__( 'User: Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-small-minus',
				'name'  => esc_html__( 'User: Small-Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-small-plus',
				'name'  => esc_html__( 'User: Small-Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-user-x',
				'name'  => esc_html__( 'User: X', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-users',
				'name'  => esc_html__( 'Users', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-album',
				'name'  => esc_html__( 'Video: Album', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-albums',
				'name'  => esc_html__( 'Video: Albums', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-alt',
				'name'  => esc_html__( 'Video: Alt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-alt',
				'name'  => esc_html__( 'Video: Alt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-fill',
				'name'  => esc_html__( 'Video: Fill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-next',
				'name'  => esc_html__( 'Video: Next', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-video-off',
				'name'  => esc_html__( 'Video: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-visibility',
				'name'  => esc_html__( 'Visibility', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-visibility-hidden',
				'name'  => esc_html__( 'Visibility: Hidden', 'buddyboss-theme' ),
			),
			array(
				'group' => 'phone',
				'id'    => 'bb-icon-voicemail',
				'name'  => esc_html__( 'Voicemail', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-volume-down',
				'name'  => esc_html__( 'Volume: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-volume-mute',
				'name'  => esc_html__( 'Volume: Mute', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-volume-off',
				'name'  => esc_html__( 'Volume: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'bb-icon-volume-up',
				'name'  => esc_html__( 'Volume: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'bb-icon-watch',
				'name'  => esc_html__( 'Watch', 'buddyboss-theme' ),
			),
			array(
				'group' => 'courses',
				'id'    => 'bb-icon-watch-alarm',
				'name'  => esc_html__( 'Watch Alarm', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-wifi',
				'name'  => esc_html__( 'WiFi', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web',
				'id'    => 'bb-icon-wifi-off',
				'name'  => esc_html__( 'WiFi: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-wind',
				'name'  => esc_html__( 'Wind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-x',
				'name'  => esc_html__( 'X', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-x-circle',
				'name'  => esc_html__( 'X: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-x-square',
				'name'  => esc_html__( 'X: Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-clubhouse',
				'name'  => esc_html__( 'Clubhouse: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-telegram',
				'name'  => esc_html__( 'Telegram: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-tiktok',
				'name'  => esc_html__( 'Tiktok: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-rounded-youtube',
				'name'  => esc_html__( 'Youtube: Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'bb-icon-youtube-logo',
				'name'  => esc_html__( 'Youtube: Logo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file',
				'id'    => 'bb-icon-zip',
				'name'  => esc_html__( 'ZIP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'nature',
				'id'    => 'bb-icon-zap',
				'name'  => esc_html__( 'Zap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-zoom-in',
				'name'  => esc_html__( 'Zoom: In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'control',
				'id'    => 'bb-icon-zoom-out',
				'name'  => esc_html__( 'Zoom: Out', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter BuddyBoss Legacy items.
		 *
		 * @since 2.0.0
		 *
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_buddyboss_items', $items );

		return $items;
	}
}
