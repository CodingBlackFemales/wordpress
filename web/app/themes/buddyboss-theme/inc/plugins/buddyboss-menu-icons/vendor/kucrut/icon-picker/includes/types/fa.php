<?php

require_once dirname( __FILE__ ) . '/font.php';

/**
 * Font Awesome
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_Font_Awesome extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'fa';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '4.7.0';

	/**
	 * Stylesheet ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $stylesheet_id = 'font-awesome';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Font Awesome', 'buddyboss-theme' );

		parent::__construct( $args );
	}

	/**
	 * Get icon groups.
	 *
	 * @since Menu Icons 0.1.0
	 *
	 * @return array
	 */
	public function get_groups() {
		$groups = array(
			array(
				'id'   => 'a11y',
				'name' => esc_html__( 'Accessibility', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'brand',
				'name' => esc_html__( 'Brand', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'chart',
				'name' => esc_html__( 'Charts', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'currency',
				'name' => esc_html__( 'Currency', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'directional',
				'name' => esc_html__( 'Directional', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'file-types',
				'name' => esc_html__( 'File Types', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'form-control',
				'name' => esc_html__( 'Form Controls', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'gender',
				'name' => esc_html__( 'Genders', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'medical',
				'name' => esc_html__( 'Medical', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'payment',
				'name' => esc_html__( 'Payment', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'spinner',
				'name' => esc_html__( 'Spinners', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'transportation',
				'name' => esc_html__( 'Transportation', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'text-editor',
				'name' => esc_html__( 'Text Editor', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'video-player',
				'name' => esc_html__( 'Video Player', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'web-application',
				'name' => esc_html__( 'Web Application', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon groups.
		 *
		 * @since 0.1.0
		 *
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_fa_groups', $groups );

		return $groups;
	}


	/**
	 * Get icon names.
	 *
	 * @since Menu Icons 0.1.0
	 *
	 * @return array
	 */
	public function get_items() {
		$items = array(
			array(
				'group' => 'brand',
				'id'    => 'fa-500px',
				'name'  => esc_html__( '500px', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-adn',
				'name'  => esc_html__( 'ADN', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-address-book',
				'name'  => esc_html__( 'Address Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-address-book-o',
				'name'  => esc_html__( 'Address Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-address-card',
				'name'  => esc_html__( 'Address Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-address-card-o',
				'name'  => esc_html__( 'Address Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-adjust',
				'name'  => esc_html__( 'Adjust', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-align-center',
				'name'  => esc_html__( 'Align Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-align-left',
				'name'  => esc_html__( 'Align Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-align-right',
				'name'  => esc_html__( 'Align Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-amazon',
				'name'  => esc_html__( 'Amazon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-ambulance',
				'name'  => esc_html__( 'Ambulance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-amex',
				'name'  => esc_html__( 'American Express', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-american-sign-language-interpreting',
				'name'  => esc_html__( 'American Sign Language', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-anchor',
				'name'  => esc_html__( 'Anchor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-android',
				'name'  => esc_html__( 'Android', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-angellist',
				'name'  => esc_html__( 'AngelList', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-double-down',
				'name'  => esc_html__( 'Angle Double Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-double-left',
				'name'  => esc_html__( 'Angle Double Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-double-right',
				'name'  => esc_html__( 'Angle Double Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-double-up',
				'name'  => esc_html__( 'Angle Double Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-down',
				'name'  => esc_html__( 'Angle Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-left',
				'name'  => esc_html__( 'Angle Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-right',
				'name'  => esc_html__( 'Angle Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-angle-up',
				'name'  => esc_html__( 'Angle Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-apple',
				'name'  => esc_html__( 'Apple', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-archive',
				'name'  => esc_html__( 'Archive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'fa-area-chart',
				'name'  => esc_html__( 'Area Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-down',
				'name'  => esc_html__( 'Arrow Circle Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-o-down',
				'name'  => esc_html__( 'Arrow Circle Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-left',
				'name'  => esc_html__( 'Arrow Circle Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-o-left',
				'name'  => esc_html__( 'Arrow Circle Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-o-right',
				'name'  => esc_html__( 'Arrow Circle Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-right',
				'name'  => esc_html__( 'Arrow Circle Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-o-up',
				'name'  => esc_html__( 'Arrow Circle Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-circle-up',
				'name'  => esc_html__( 'Arrow Circle Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-down',
				'name'  => esc_html__( 'Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-left',
				'name'  => esc_html__( 'Arrow Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-right',
				'name'  => esc_html__( 'Arrow Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrow-up',
				'name'  => esc_html__( 'Arrow Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrows',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrows-alt',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrows-h',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-arrows-v',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-arrows-alt',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-arrows',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-arrows-h',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-arrows-v',
				'name'  => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-assistive-listening-systems',
				'name'  => esc_html__( 'Assistive Listening Systems', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-asterisk',
				'name'  => esc_html__( 'Asterisk', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-at',
				'name'  => esc_html__( 'At', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-audio-description',
				'name'  => esc_html__( 'Audio Description', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-backward',
				'name'  => esc_html__( 'Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-balance-scale',
				'name'  => esc_html__( 'Balance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-ban',
				'name'  => esc_html__( 'Ban', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-bandcamp',
				'name'  => esc_html__( 'Bandcamp', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'fa-bar-chart-o',
				'name'  => esc_html__( 'Bar Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-barcode',
				'name'  => esc_html__( 'Barcode', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bars',
				'name'  => esc_html__( 'Bars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bathtub',
				'name'  => esc_html__( 'Bathtub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-battery-empty',
				'name'  => esc_html__( 'Battery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-battery-full',
				'name'  => esc_html__( 'Battery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-battery-half',
				'name'  => esc_html__( 'Battery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-battery-quarter',
				'name'  => esc_html__( 'Battery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bed',
				'name'  => esc_html__( 'Bed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-beer',
				'name'  => esc_html__( 'Beer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-behance',
				'name'  => esc_html__( 'Behance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-behance-square',
				'name'  => esc_html__( 'Behance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bell',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bell-o',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bell-slash',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bell-slash-o',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-bicycle',
				'name'  => esc_html__( 'Bicycle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-binoculars',
				'name'  => esc_html__( 'Binoculars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-birthday-cake',
				'name'  => esc_html__( 'Birthday Cake', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-bitbucket',
				'name'  => esc_html__( 'Bitbucket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-bitbucket-square',
				'name'  => esc_html__( 'Bitbucket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-bitcoin',
				'name'  => esc_html__( 'Bitcoin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-black-tie',
				'name'  => esc_html__( 'BlackTie', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-blind',
				'name'  => esc_html__( 'Blind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-bluetooth',
				'name'  => esc_html__( 'Bluetooth', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-bluetooth-b',
				'name'  => esc_html__( 'Bluetooth', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bolt',
				'name'  => esc_html__( 'Bolt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bomb',
				'name'  => esc_html__( 'Bomb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bookmark',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bookmark-o',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-braille',
				'name'  => esc_html__( 'Braille', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-briefcase',
				'name'  => esc_html__( 'Briefcase', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bug',
				'name'  => esc_html__( 'Bug', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-building',
				'name'  => esc_html__( 'Building', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-building-o',
				'name'  => esc_html__( 'Building', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bullhorn',
				'name'  => esc_html__( 'Bullhorn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-bullseye',
				'name'  => esc_html__( 'Bullseye', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-bus',
				'name'  => esc_html__( 'Bus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-buysellads',
				'name'  => esc_html__( 'BuySellAds', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-css3',
				'name'  => esc_html__( 'CSS3', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calculator',
				'name'  => esc_html__( 'Calculator', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calendar',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calendar-check-o',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calendar-minus-o',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calendar-o',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-calendar-times-o',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-camera',
				'name'  => esc_html__( 'Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-camera-retro',
				'name'  => esc_html__( 'Camera Retro', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-car',
				'name'  => esc_html__( 'Car', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-down',
				'name'  => esc_html__( 'Caret Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-square-o-down',
				'name'  => esc_html__( 'Caret Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-caret-square-o-down',
				'name'  => esc_html__( 'Caret Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-left',
				'name'  => esc_html__( 'Caret Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-square-o-left',
				'name'  => esc_html__( 'Caret Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-caret-square-o-left',
				'name'  => esc_html__( 'Caret Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-right',
				'name'  => esc_html__( 'Caret Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-square-o-right',
				'name'  => esc_html__( 'Caret Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-caret-square-o-right',
				'name'  => esc_html__( 'Caret Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-square-o-up',
				'name'  => esc_html__( 'Caret Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-caret-up',
				'name'  => esc_html__( 'Caret Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-caret-square-o-up',
				'name'  => esc_html__( 'Caret Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cart-arrow-down',
				'name'  => esc_html__( 'Cart Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cart-plus',
				'name'  => esc_html__( 'Cart Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-certificate',
				'name'  => esc_html__( 'Certificate', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-check-square',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-check-square-o',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-check',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-check-circle',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-check-circle-o',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-circle-down',
				'name'  => esc_html__( 'Chevron Circle Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-circle-left',
				'name'  => esc_html__( 'Chevron Circle Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-circle-right',
				'name'  => esc_html__( 'Chevron Circle Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-circle-up',
				'name'  => esc_html__( 'Chevron Circle Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-down',
				'name'  => esc_html__( 'Chevron Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-left',
				'name'  => esc_html__( 'Chevron Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-right',
				'name'  => esc_html__( 'Chevron Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-chevron-up',
				'name'  => esc_html__( 'Chevron Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-child',
				'name'  => esc_html__( 'Child', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-chrome',
				'name'  => esc_html__( 'Chrome', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-circle',
				'name'  => esc_html__( 'Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-circle-o',
				'name'  => esc_html__( 'Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'spinner',
				'id'    => 'fa-circle-o-notch',
				'name'  => esc_html__( 'Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-circle-thin',
				'name'  => esc_html__( 'Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-clipboard',
				'name'  => esc_html__( 'Clipboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-clock-o',
				'name'  => esc_html__( 'Clock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-clone',
				'name'  => esc_html__( 'Clone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cloud-download',
				'name'  => esc_html__( 'Cloud Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cloud-upload',
				'name'  => esc_html__( 'Cloud Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-code',
				'name'  => esc_html__( 'Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-code-fork',
				'name'  => esc_html__( 'Code Fork', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-codepen',
				'name'  => esc_html__( 'CodePen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-codiepie',
				'name'  => esc_html__( 'Codie Pie', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-coffee',
				'name'  => esc_html__( 'Coffee', 'buddyboss-theme' ),
			),
			array(
				'group' => 'spinner',
				'id'    => 'fa-cog',
				'name'  => esc_html__( 'Cog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cogs',
				'name'  => esc_html__( 'Cogs', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-columns',
				'name'  => esc_html__( 'Columns', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-comment',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-comment-o',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-commenting',
				'name'  => esc_html__( 'Commenting', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-commenting-o',
				'name'  => esc_html__( 'Commenting', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-comments',
				'name'  => esc_html__( 'Comments', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-comments-o',
				'name'  => esc_html__( 'Comments', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-compass',
				'name'  => esc_html__( 'Compass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-compress',
				'name'  => esc_html__( 'Compress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-connectdevelop',
				'name'  => esc_html__( 'Connect + Develop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-contao',
				'name'  => esc_html__( 'Contao', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-copy',
				'name'  => esc_html__( 'Copy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-copyright',
				'name'  => esc_html__( 'Copyright', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-creative-commons',
				'name'  => esc_html__( 'Creative Commons', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-credit-card',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-credit-card-alt',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-credit-card',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-crop',
				'name'  => esc_html__( 'Crop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-crosshairs',
				'name'  => esc_html__( 'Crosshairs', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cube',
				'name'  => esc_html__( 'Cube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cubes',
				'name'  => esc_html__( 'Cubes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-i-cursor',
				'name'  => esc_html__( 'Cursor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-cut',
				'name'  => esc_html__( 'Cut', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-cutlery',
				'name'  => esc_html__( 'Cutlery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-dashboard',
				'name'  => esc_html__( 'Dashboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-dashcube',
				'name'  => esc_html__( 'Dashcube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-database',
				'name'  => esc_html__( 'Database', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-deaf',
				'name'  => esc_html__( 'Deaf', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-delicious',
				'name'  => esc_html__( 'Delicious', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-desktop',
				'name'  => esc_html__( 'Desktop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-deviantart',
				'name'  => esc_html__( 'DeviantART', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-diamond',
				'name'  => esc_html__( 'Diamond', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-digg',
				'name'  => esc_html__( 'Digg', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-diners-club',
				'name'  => esc_html__( 'Diners Club', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-discover',
				'name'  => esc_html__( 'Discover', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-dollar',
				'name'  => esc_html__( 'Dollar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-dot-circle-o',
				'name'  => esc_html__( 'Dot', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-dribbble',
				'name'  => esc_html__( 'Dribbble', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-dropbox',
				'name'  => esc_html__( 'DropBox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-drupal',
				'name'  => esc_html__( 'Drupal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-edge',
				'name'  => esc_html__( 'Edge', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-edit',
				'name'  => esc_html__( 'Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-eercast',
				'name'  => esc_html__( 'Eercast', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-eject',
				'name'  => esc_html__( 'Eject', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-ellipsis-h',
				'name'  => esc_html__( 'Ellipsis', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-ellipsis-v',
				'name'  => esc_html__( 'Ellipsis', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-empire',
				'name'  => esc_html__( 'Empire', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-envelope',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-envelope-o',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-envelope-open',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-envelope-open-o',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-envelope-square',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-envira',
				'name'  => esc_html__( 'Envira', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-eraser',
				'name'  => esc_html__( 'Eraser', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-eraser',
				'name'  => esc_html__( 'Eraser', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-etsy',
				'name'  => esc_html__( 'Etsy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-euro',
				'name'  => esc_html__( 'Euro', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-exchange',
				'name'  => esc_html__( 'Exchange', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-exclamation',
				'name'  => esc_html__( 'Exclamation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-exclamation-circle',
				'name'  => esc_html__( 'Exclamation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-exclamation-triangle',
				'name'  => esc_html__( 'Exclamation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-expand',
				'name'  => esc_html__( 'Expand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-expeditedssl',
				'name'  => esc_html__( 'ExpeditedSSL', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-external-link',
				'name'  => esc_html__( 'External Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-external-link-square',
				'name'  => esc_html__( 'External Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-eye',
				'name'  => esc_html__( 'Eye', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-eye-slash',
				'name'  => esc_html__( 'Eye', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-eyedropper',
				'name'  => esc_html__( 'Eye Dropper', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-facebook-official',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-facebook-square',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-fast-backward',
				'name'  => esc_html__( 'Fast Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-fast-forward',
				'name'  => esc_html__( 'Fast Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-fax',
				'name'  => esc_html__( 'Fax', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-female',
				'name'  => esc_html__( 'Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-fighter-jet',
				'name'  => esc_html__( 'Fighter Jet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-o',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-archive-o',
				'name'  => esc_html__( 'File: Archive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-audio-o',
				'name'  => esc_html__( 'File: Audio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-code-o',
				'name'  => esc_html__( 'File: Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-excel-o',
				'name'  => esc_html__( 'File: Excel', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-image-o',
				'name'  => esc_html__( 'File: Image', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-pdf-o',
				'name'  => esc_html__( 'File: PDF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-powerpoint-o',
				'name'  => esc_html__( 'File: Powerpoint', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-text',
				'name'  => esc_html__( 'File: Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-text-o',
				'name'  => esc_html__( 'File: Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-video-o',
				'name'  => esc_html__( 'File: Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fa-file-word-o',
				'name'  => esc_html__( 'File: Word', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-files-o',
				'name'  => esc_html__( 'Files', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-film',
				'name'  => esc_html__( 'Film', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-fire',
				'name'  => esc_html__( 'Fire', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-fire-extinguisher',
				'name'  => esc_html__( 'Fire Extinguisher', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-firefox',
				'name'  => esc_html__( 'Firefox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-flag-checkered',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-flag-o',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-flash',
				'name'  => esc_html__( 'Flash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-flask',
				'name'  => esc_html__( 'Flask', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-flickr',
				'name'  => esc_html__( 'Flickr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-folder',
				'name'  => esc_html__( 'Folder', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-folder-o',
				'name'  => esc_html__( 'Folder', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-folder-open',
				'name'  => esc_html__( 'Folder Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-folder-open-o',
				'name'  => esc_html__( 'Folder Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-font',
				'name'  => esc_html__( 'Font', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-fonticons',
				'name'  => esc_html__( 'FontIcons', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-futbol-o',
				'name'  => esc_html__( 'Foot Ball', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-fort-awesome',
				'name'  => esc_html__( 'Fort Awesome', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-forumbee',
				'name'  => esc_html__( 'Forumbee', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-forward',
				'name'  => esc_html__( 'Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-foursquare',
				'name'  => esc_html__( 'Foursquare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-free-code-camp',
				'name'  => esc_html__( 'Free Code Camp', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-frown-o',
				'name'  => esc_html__( 'Frown', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-gbp',
				'name'  => esc_html__( 'GBP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-gg',
				'name'  => esc_html__( 'GBP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-gg-circle',
				'name'  => esc_html__( 'GG', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-gamepad',
				'name'  => esc_html__( 'Gamepad', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-gavel',
				'name'  => esc_html__( 'Gavel', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-gear',
				'name'  => esc_html__( 'Gear', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-gears',
				'name'  => esc_html__( 'Gears', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-genderless',
				'name'  => esc_html__( 'Genderless', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-gift',
				'name'  => esc_html__( 'Gift', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-git',
				'name'  => esc_html__( 'Git', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-git-square',
				'name'  => esc_html__( 'Git', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-github',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-github-alt',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-github-square',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-gittip',
				'name'  => esc_html__( 'GitTip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-gitlab',
				'name'  => esc_html__( 'Gitlab', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-glass',
				'name'  => esc_html__( 'Glass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-glide',
				'name'  => esc_html__( 'Glide', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-glide-g',
				'name'  => esc_html__( 'Glide', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-globe',
				'name'  => esc_html__( 'Globe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-google',
				'name'  => esc_html__( 'Google', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-google-wallet',
				'name'  => esc_html__( 'Google Wallet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-google-plus',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-google-plus-square',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-graduation-cap',
				'name'  => esc_html__( 'Graduation Cap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-grav',
				'name'  => esc_html__( 'Grav', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-group',
				'name'  => esc_html__( 'Group', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hdd-o',
				'name'  => esc_html__( 'HDD', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-html5',
				'name'  => esc_html__( 'HTML5', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-hacker-news',
				'name'  => esc_html__( 'Hacker News', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-lizard-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-paper-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-peace-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-pointer-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-rock-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-scissors-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hand-spock-o',
				'name'  => esc_html__( 'Hand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-hand-o-down',
				'name'  => esc_html__( 'Hand Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-hand-o-left',
				'name'  => esc_html__( 'Hand Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-hand-o-right',
				'name'  => esc_html__( 'Hand Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-hand-o-up',
				'name'  => esc_html__( 'Hand Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-handshake-o',
				'name'  => esc_html__( 'Handshake', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hashtag',
				'name'  => esc_html__( 'Hash Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-header',
				'name'  => esc_html__( 'Header', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-headphones',
				'name'  => esc_html__( 'Headphones', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-heart',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-heart-o',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-heartbeat',
				'name'  => esc_html__( 'Heartbeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-history',
				'name'  => esc_html__( 'History', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-h-square',
				'name'  => esc_html__( 'Hospital', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-hospital-o',
				'name'  => esc_html__( 'Hospital', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hourglass',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hourglass-end',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hourglass-half',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hourglass-o',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-hourglass-start',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-houzz',
				'name'  => esc_html__( 'Houzz', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-id-badge',
				'name'  => esc_html__( 'ID Badge', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-id-card',
				'name'  => esc_html__( 'ID Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-id-card-o',
				'name'  => esc_html__( 'ID Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-imdb',
				'name'  => esc_html__( 'IMDb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-inbox',
				'name'  => esc_html__( 'Inbox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-indent',
				'name'  => esc_html__( 'Indent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-industry',
				'name'  => esc_html__( 'Industry', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-info',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-info-circle',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-instagram',
				'name'  => esc_html__( 'Instagram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-internet-explorer',
				'name'  => esc_html__( 'Internet Explorer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-ioxhost',
				'name'  => esc_html__( 'IoxHost', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-ils',
				'name'  => esc_html__( 'Israeli Sheqel', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-jcb',
				'name'  => esc_html__( 'JCB', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-jsfiddle',
				'name'  => esc_html__( 'JSFiddle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-joomla',
				'name'  => esc_html__( 'Joomla', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-align-justify',
				'name'  => esc_html__( 'Justify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-key',
				'name'  => esc_html__( 'Key', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-keyboard-o',
				'name'  => esc_html__( 'Keyboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-language',
				'name'  => esc_html__( 'Language', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-laptop',
				'name'  => esc_html__( 'Laptop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-lastfm',
				'name'  => esc_html__( 'Last.fm', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-lastfm-square',
				'name'  => esc_html__( 'Last.fm', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-leaf',
				'name'  => esc_html__( 'Leaf', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-leanpub',
				'name'  => esc_html__( 'Leanpub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-legal',
				'name'  => esc_html__( 'Legal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-lemon-o',
				'name'  => esc_html__( 'Lemon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-level-down',
				'name'  => esc_html__( 'Level Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-level-up',
				'name'  => esc_html__( 'Level Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-life-ring',
				'name'  => esc_html__( 'Life Buoy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-lightbulb-o',
				'name'  => esc_html__( 'Lightbulb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'fa-line-chart',
				'name'  => esc_html__( 'Line Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-link',
				'name'  => esc_html__( 'Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-linkedin',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-linkedin-square',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-linode',
				'name'  => esc_html__( 'Linode', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-linux',
				'name'  => esc_html__( 'Linux', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-list',
				'name'  => esc_html__( 'List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-list-alt',
				'name'  => esc_html__( 'List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-location-arrow',
				'name'  => esc_html__( 'Location Arrow', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-long-arrow-down',
				'name'  => esc_html__( 'Long Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-long-arrow-left',
				'name'  => esc_html__( 'Long Arrow Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-long-arrow-right',
				'name'  => esc_html__( 'Long Arrow Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'directional',
				'id'    => 'fa-long-arrow-up',
				'name'  => esc_html__( 'Long Arrow Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-low-vision',
				'name'  => esc_html__( 'Low Vision', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-modx',
				'name'  => esc_html__( 'MODX', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-magic',
				'name'  => esc_html__( 'Magic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-magnet',
				'name'  => esc_html__( 'Magnet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mail-forward',
				'name'  => esc_html__( 'Mail Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mail-reply',
				'name'  => esc_html__( 'Mail Reply', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mail-reply-all',
				'name'  => esc_html__( 'Mail Reply All', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-male',
				'name'  => esc_html__( 'Male', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-map',
				'name'  => esc_html__( 'Map', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-map-o',
				'name'  => esc_html__( 'Map', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-map-marker',
				'name'  => esc_html__( 'Map Marker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-map-pin',
				'name'  => esc_html__( 'Map Pin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-map-signs',
				'name'  => esc_html__( 'Map Signs', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mars',
				'name'  => esc_html__( 'Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mars-double',
				'name'  => esc_html__( 'Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mars-stroke',
				'name'  => esc_html__( 'Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mars-stroke-h',
				'name'  => esc_html__( 'Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mars-stroke-v',
				'name'  => esc_html__( 'Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-mastercard',
				'name'  => esc_html__( 'MasterCard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-maxcdn',
				'name'  => esc_html__( 'MaxCDN', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-meanpath',
				'name'  => esc_html__( 'Meanpath', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-medium',
				'name'  => esc_html__( 'Medium', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-medkit',
				'name'  => esc_html__( 'Medkit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-meetup',
				'name'  => esc_html__( 'Meetup', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-meh-o',
				'name'  => esc_html__( 'Meh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-mercury',
				'name'  => esc_html__( 'Mercury', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-microchip',
				'name'  => esc_html__( 'Microchip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-microphone',
				'name'  => esc_html__( 'Microphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-microphone-slash',
				'name'  => esc_html__( 'Microphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-minus-square',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-minus-square-o',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-minus-circle',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-mixcloud',
				'name'  => esc_html__( 'Mixcloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mobile',
				'name'  => esc_html__( 'Mobile', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mobile-phone',
				'name'  => esc_html__( 'Mobile Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-money',
				'name'  => esc_html__( 'Money', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-moon-o',
				'name'  => esc_html__( 'Moon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-motorcycle',
				'name'  => esc_html__( 'Motorcycle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-mouse-pointer',
				'name'  => esc_html__( 'Mouse Pointer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-music',
				'name'  => esc_html__( 'Music', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-neuter',
				'name'  => esc_html__( 'Neuter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-newspaper-o',
				'name'  => esc_html__( 'Newspaper', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-object-group',
				'name'  => esc_html__( 'Object Group', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-object-ungroup',
				'name'  => esc_html__( 'Object Ungroup', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-odnoklassniki',
				'name'  => esc_html__( 'Odnoklassniki', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-odnoklassniki-square',
				'name'  => esc_html__( 'Odnoklassniki', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-opencart',
				'name'  => esc_html__( 'OpenCart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-openid',
				'name'  => esc_html__( 'OpenID', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-opera',
				'name'  => esc_html__( 'Opera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-optin-monster',
				'name'  => esc_html__( 'OptinMonster', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-list-ol',
				'name'  => esc_html__( 'Ordered List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-outdent',
				'name'  => esc_html__( 'Outdent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pagelines',
				'name'  => esc_html__( 'Pagelines', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-paint-brush',
				'name'  => esc_html__( 'Paint Brush', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-paper-plane',
				'name'  => esc_html__( 'Paper Plane', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-paper-plane-o',
				'name'  => esc_html__( 'Paper Plane', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-paperclip',
				'name'  => esc_html__( 'Paperclip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-paragraph',
				'name'  => esc_html__( 'Paragraph', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-paste',
				'name'  => esc_html__( 'Paste', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-pause-circle',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-pause-circle-o',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-paw',
				'name'  => esc_html__( 'Paw', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-paypal',
				'name'  => esc_html__( 'PayPal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-paypal',
				'name'  => esc_html__( 'PayPal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-pencil',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-pencil-square',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-pencil-square-o',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-percent',
				'name'  => esc_html__( 'Percent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-phone',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-phone-square',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-volume-control-phone',
				'name'  => esc_html__( 'Phone Volume Control', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-picture-o',
				'name'  => esc_html__( 'Picture', 'buddyboss-theme' ),
			),
			array(
				'group' => 'chart',
				'id'    => 'fa-pie-chart',
				'name'  => esc_html__( 'Pie Chart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pied-piper',
				'name'  => esc_html__( 'Pied Piper', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pied-piper-alt',
				'name'  => esc_html__( 'Pied Piper', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pinterest',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pinterest-p',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-pinterest-square',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-plane',
				'name'  => esc_html__( 'Plane', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-play-circle',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-play-circle-o',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-plug',
				'name'  => esc_html__( 'Plug', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-plus-square',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-plus-square-o',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-plus-circle',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-get-pocket',
				'name'  => esc_html__( 'Pocket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-podcast',
				'name'  => esc_html__( 'Podcast', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-power-off',
				'name'  => esc_html__( 'Power Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-print',
				'name'  => esc_html__( 'Print', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-product-hunt',
				'name'  => esc_html__( 'Product Hunt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-puzzle-piece',
				'name'  => esc_html__( 'Puzzle Piece', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-qq',
				'name'  => esc_html__( 'QQ', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-qrcode',
				'name'  => esc_html__( 'QR Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-question',
				'name'  => esc_html__( 'Question', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-question-circle',
				'name'  => esc_html__( 'Question', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-question-circle-o',
				'name'  => esc_html__( 'Question', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-quora',
				'name'  => esc_html__( 'Quora', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-quote-left',
				'name'  => esc_html__( 'Quote Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-quote-right',
				'name'  => esc_html__( 'Quote Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-rss',
				'name'  => esc_html__( 'RSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-rss-square',
				'name'  => esc_html__( 'RSS Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-random',
				'name'  => esc_html__( 'Random', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-ravelry',
				'name'  => esc_html__( 'Ravelry', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-rebel',
				'name'  => esc_html__( 'Rebel', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-recycle',
				'name'  => esc_html__( 'Recycle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-reddit',
				'name'  => esc_html__( 'Reddit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-reddit-alien',
				'name'  => esc_html__( 'Reddit Alien', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-reddit-square',
				'name'  => esc_html__( 'Reddit Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'spinner',
				'id'    => 'fa-refresh',
				'name'  => esc_html__( 'Refresh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-registered',
				'name'  => esc_html__( 'Registered', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-renren',
				'name'  => esc_html__( 'Renren', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-repeat',
				'name'  => esc_html__( 'Repeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-reply',
				'name'  => esc_html__( 'Reply', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-reply-all',
				'name'  => esc_html__( 'Reply All', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-retweet',
				'name'  => esc_html__( 'Retweet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-road',
				'name'  => esc_html__( 'Road', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-rocket',
				'name'  => esc_html__( 'Rocket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-rouble',
				'name'  => esc_html__( 'Rouble', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-inr',
				'name'  => esc_html__( 'Rupee', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-sellsy',
				'name'  => esc_html__( 'SELLSY', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-safari',
				'name'  => esc_html__( 'Safari', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-save',
				'name'  => esc_html__( 'Save', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-scribd',
				'name'  => esc_html__( 'Scribd', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-search',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-search-minus',
				'name'  => esc_html__( 'Search Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-search-plus',
				'name'  => esc_html__( 'Search Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-server',
				'name'  => esc_html__( 'Server', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-share-alt',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-share-alt-square',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-share-square',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-share-square-o',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-shield',
				'name'  => esc_html__( 'Shield', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-ship',
				'name'  => esc_html__( 'Ship', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-shirtsinbulk',
				'name'  => esc_html__( 'Shirts In Bulk', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-shopping-bag',
				'name'  => esc_html__( 'Shopping Bag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-shopping-basket',
				'name'  => esc_html__( 'Shopping Basket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-shopping-cart',
				'name'  => esc_html__( 'Shopping Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-shower',
				'name'  => esc_html__( 'Shower', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sign-in',
				'name'  => esc_html__( 'Sign In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-sign-language',
				'name'  => esc_html__( 'Sign Language', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sign-out',
				'name'  => esc_html__( 'Sign Out', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-signal',
				'name'  => esc_html__( 'Signal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-simplybuilt',
				'name'  => esc_html__( 'SimplyBuilt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sitemap',
				'name'  => esc_html__( 'Sitemap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-skyatlas',
				'name'  => esc_html__( 'Skyatlas', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-skype',
				'name'  => esc_html__( 'Skype', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-slack',
				'name'  => esc_html__( 'Slack', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-slideshare',
				'name'  => esc_html__( 'SlideShare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sliders',
				'name'  => esc_html__( 'Sliders', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-smile-o',
				'name'  => esc_html__( 'Smile', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-snapchat',
				'name'  => esc_html__( 'Snapchat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-snapchat-ghost',
				'name'  => esc_html__( 'Snapchat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-snapchat-square',
				'name'  => esc_html__( 'Snapchat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-snowflake',
				'name'  => esc_html__( 'Snowflake', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort',
				'name'  => esc_html__( 'Sort', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-asc',
				'name'  => esc_html__( 'Sort ASC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-alpha-asc',
				'name'  => esc_html__( 'Sort Alpha ASC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-alpha-desc',
				'name'  => esc_html__( 'Sort Alpha DESC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-amount-asc',
				'name'  => esc_html__( 'Sort Amount ASC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-amount-desc',
				'name'  => esc_html__( 'Sort Amount DESC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-desc',
				'name'  => esc_html__( 'Sort DESC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-down',
				'name'  => esc_html__( 'Sort Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-numeric-asc',
				'name'  => esc_html__( 'Sort Numeric ASC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-numeric-desc',
				'name'  => esc_html__( 'Sort Numeric DESC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sort-up',
				'name'  => esc_html__( 'Sort Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-soundcloud',
				'name'  => esc_html__( 'SoundCloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-space-shuttle',
				'name'  => esc_html__( 'Space Shuttle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'spinner',
				'id'    => 'fa-spinner',
				'name'  => esc_html__( 'Spinner', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-spoon',
				'name'  => esc_html__( 'Spoon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-spotify',
				'name'  => esc_html__( 'Spotify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-square',
				'name'  => esc_html__( 'Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'form-control',
				'id'    => 'fa-square-o',
				'name'  => esc_html__( 'Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-stack-exchange',
				'name'  => esc_html__( 'Stack Exchange', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-stack-overflow',
				'name'  => esc_html__( 'Stack Overflow', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star',
				'name'  => esc_html__( 'Star', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star-o',
				'name'  => esc_html__( 'Star', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star-half',
				'name'  => esc_html__( 'Star Half', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star-half-o',
				'name'  => esc_html__( 'Star Half', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star-half-empty',
				'name'  => esc_html__( 'Star Half Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-star-half-full',
				'name'  => esc_html__( 'Star Half Full', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-steam',
				'name'  => esc_html__( 'Steam', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-steam-square',
				'name'  => esc_html__( 'Steam', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-step-backward',
				'name'  => esc_html__( 'Step Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-step-forward',
				'name'  => esc_html__( 'Step Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-stethoscope',
				'name'  => esc_html__( 'Stethoscope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sticky-note',
				'name'  => esc_html__( 'Sticky Note', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sticky-note-o',
				'name'  => esc_html__( 'Sticky Note', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-stop',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-stop-circle',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-stop-circle-o',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-street-view',
				'name'  => esc_html__( 'Street View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-strikethrough',
				'name'  => esc_html__( 'Strikethrough', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-stripe',
				'name'  => esc_html__( 'Stripe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-stumbleupon',
				'name'  => esc_html__( 'StumbleUpon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-stumbleupon-circle',
				'name'  => esc_html__( 'StumbleUpon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-subscript',
				'name'  => esc_html__( 'Subscript', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-subway',
				'name'  => esc_html__( 'Subway', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-suitcase',
				'name'  => esc_html__( 'Suitcase', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-sun-o',
				'name'  => esc_html__( 'Sun', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-superpowers',
				'name'  => esc_html__( 'Superpowers', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-superscript',
				'name'  => esc_html__( 'Superscript', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-th-large',
				'name'  => esc_html__( 'TH Large', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-th-list',
				'name'  => esc_html__( 'TH List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tty',
				'name'  => esc_html__( 'TTY', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-table',
				'name'  => esc_html__( 'Table', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-th',
				'name'  => esc_html__( 'Table Header', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tablet',
				'name'  => esc_html__( 'Tablet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tachometer',
				'name'  => esc_html__( 'Tachometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tag',
				'name'  => esc_html__( 'Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tags',
				'name'  => esc_html__( 'Tags', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tasks',
				'name'  => esc_html__( 'Tasks', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-taxi',
				'name'  => esc_html__( 'Taxi', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-telegram',
				'name'  => esc_html__( 'Telegram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-television',
				'name'  => esc_html__( 'Television', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-tencent-weibo',
				'name'  => esc_html__( 'Tencent Weibo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-terminal',
				'name'  => esc_html__( 'Terminal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-text-height',
				'name'  => esc_html__( 'Text Height', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-text-width',
				'name'  => esc_html__( 'Text Width', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-thermometer-empty',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-thermometer-full',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-thermometer-half',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-thermometer-quarter',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-thermometer-three-quarters',
				'name'  => esc_html__( 'Thermometer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-thumb-tack',
				'name'  => esc_html__( 'Thumb Tack', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-thumbs-down',
				'name'  => esc_html__( 'Thumbs Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-thumbs-o-down',
				'name'  => esc_html__( 'Thumbs Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-thumbs-o-up',
				'name'  => esc_html__( 'Thumbs Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-thumbs-up',
				'name'  => esc_html__( 'Thumbs Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-ticket',
				'name'  => esc_html__( 'Ticket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-times',
				'name'  => esc_html__( 'Times', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-times-circle',
				'name'  => esc_html__( 'Times', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-times-circle-o',
				'name'  => esc_html__( 'Times', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tint',
				'name'  => esc_html__( 'Tint', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-down',
				'name'  => esc_html__( 'Toggle Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-left',
				'name'  => esc_html__( 'Toggle Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-off',
				'name'  => esc_html__( 'Toggle Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-on',
				'name'  => esc_html__( 'Toggle On', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-right',
				'name'  => esc_html__( 'Toggle Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-toggle-up',
				'name'  => esc_html__( 'Toggle Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-trademark',
				'name'  => esc_html__( 'Trademark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-train',
				'name'  => esc_html__( 'Train', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-transgender',
				'name'  => esc_html__( 'Transgender', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-transgender-alt',
				'name'  => esc_html__( 'Transgender', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-trash-o',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-tree',
				'name'  => esc_html__( 'Tree', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-trello',
				'name'  => esc_html__( 'Trello', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-tripadvisor',
				'name'  => esc_html__( 'TripAdvisor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-trophy',
				'name'  => esc_html__( 'Trophy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-truck',
				'name'  => esc_html__( 'Truck', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-tumblr',
				'name'  => esc_html__( 'Tumblr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-tumblr-square',
				'name'  => esc_html__( 'Tumblr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-try',
				'name'  => esc_html__( 'Turkish Lira', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-twitch',
				'name'  => esc_html__( 'Twitch', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-twitter-square',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-usb',
				'name'  => esc_html__( 'USB', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-umbrella',
				'name'  => esc_html__( 'Umbrella', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-underline',
				'name'  => esc_html__( 'Underline', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-undo',
				'name'  => esc_html__( 'Undo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'a11y',
				'id'    => 'fa-universal-access',
				'name'  => esc_html__( 'Universal Access', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-university',
				'name'  => esc_html__( 'University', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-unlink',
				'name'  => esc_html__( 'Unlink', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-unlock',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-unlock-alt',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'fa-list-ul',
				'name'  => esc_html__( 'Unordered List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-unsorted',
				'name'  => esc_html__( 'Unsorted', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-upload',
				'name'  => esc_html__( 'Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-circle',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-circle-o',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-o',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'medical',
				'id'    => 'fa-user-md',
				'name'  => esc_html__( 'User MD', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-plus',
				'name'  => esc_html__( 'User: Add', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-secret',
				'name'  => esc_html__( 'User: Password', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-user-times',
				'name'  => esc_html__( 'User: Remove', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-users',
				'name'  => esc_html__( 'Users', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-vk',
				'name'  => esc_html__( 'VK', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-venus',
				'name'  => esc_html__( 'Venus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-venus-double',
				'name'  => esc_html__( 'Venus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'gender',
				'id'    => 'fa-venus-mars',
				'name'  => esc_html__( 'Venus + Mars', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-viacoin',
				'name'  => esc_html__( 'Viacoin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-viadeo',
				'name'  => esc_html__( 'Viadeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-viadeo-square',
				'name'  => esc_html__( 'Viadeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-video-camera',
				'name'  => esc_html__( 'Video Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-vimeo',
				'name'  => esc_html__( 'Vimeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-vimeo-square',
				'name'  => esc_html__( 'Vimeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-vine',
				'name'  => esc_html__( 'Vine', 'buddyboss-theme' ),
			),
			array(
				'group' => 'payment',
				'id'    => 'fa-cc-visa',
				'name'  => esc_html__( 'Visa', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-volume-down',
				'name'  => esc_html__( 'Volume Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-volume-off',
				'name'  => esc_html__( 'Volume Of', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-volume-up',
				'name'  => esc_html__( 'Volume Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-wpbeginner',
				'name'  => esc_html__( 'WP Beginner', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-wpexplorer',
				'name'  => esc_html__( 'WP Explorer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-wpforms',
				'name'  => esc_html__( 'WP Forms', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-warning',
				'name'  => esc_html__( 'Warning', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-weixin',
				'name'  => esc_html__( 'Weixin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-whatsapp',
				'name'  => esc_html__( 'WhatsApp', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-wheelchair',
				'name'  => esc_html__( 'Wheelchair', 'buddyboss-theme' ),
			),
			array(
				'group' => 'transportation',
				'id'    => 'fa-wheelchair-alt',
				'name'  => esc_html__( 'Wheelchair', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-wifi',
				'name'  => esc_html__( 'WiFi', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-weibo',
				'name'  => esc_html__( 'Wibo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-wikipedia-w',
				'name'  => esc_html__( 'Wikipedia', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-window-close',
				'name'  => esc_html__( 'Window Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-window-close-o',
				'name'  => esc_html__( 'Window Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-window-maximize',
				'name'  => esc_html__( 'Window Maximize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-window-minimize',
				'name'  => esc_html__( 'Window Minimize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-window-restore',
				'name'  => esc_html__( 'Window Restore', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-windows',
				'name'  => esc_html__( 'Windows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-krw',
				'name'  => esc_html__( 'Won', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-wordpress',
				'name'  => esc_html__( 'WordPress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'web-application',
				'id'    => 'fa-wrench',
				'name'  => esc_html__( 'Wrench', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-xing',
				'name'  => esc_html__( 'XING', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-xing-square',
				'name'  => esc_html__( 'XING Square', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-y-combinator',
				'name'  => esc_html__( 'Y Combinator', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-yahoo',
				'name'  => esc_html__( 'Yahoo!', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-yelp',
				'name'  => esc_html__( 'Yelp', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'fa-jpy',
				'name'  => esc_html__( 'Yen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-youtube',
				'name'  => esc_html__( 'YouTube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'video-player',
				'id'    => 'fa-youtube-play',
				'name'  => esc_html__( 'YouTube Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'brand',
				'id'    => 'fa-youtube-square',
				'name'  => esc_html__( 'YouTube Square', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon items.
		 *
		 * @since 0.1.0
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_fa_items', $items );

		return $items;
	}
}
