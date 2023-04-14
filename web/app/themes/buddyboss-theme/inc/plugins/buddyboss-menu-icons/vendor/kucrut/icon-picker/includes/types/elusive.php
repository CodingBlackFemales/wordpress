<?php
/**
 * Elusive Icons
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_Elusive extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'elusive';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '2.0';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Elusive', 'buddyboss-theme' );

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
				'id'   => 'actions',
				'name' => esc_html__( 'Actions', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'currency',
				'name' => esc_html__( 'Currency', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'media',
				'name' => esc_html__( 'Media', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'misc',
				'name' => esc_html__( 'Misc.', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'places',
				'name' => esc_html__( 'Places', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'social',
				'name' => esc_html__( 'Social', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon groups.
		 *
		 * @since 0.1.0
		 *
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_genericon_groups', $groups );

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
				'group' => 'misc',
				'id'    => 'el-icon-asl',
				'name'  => esc_html__( 'ASL', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-address-book',
				'name'  => esc_html__( 'Address Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-address-book-alt',
				'name'  => esc_html__( 'Address Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-adjust',
				'name'  => esc_html__( 'Adjust', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-adjust-alt',
				'name'  => esc_html__( 'Adjust', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-adult',
				'name'  => esc_html__( 'Adult', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-align-center',
				'name'  => esc_html__( 'Align Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-align-left',
				'name'  => esc_html__( 'Align Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-align-right',
				'name'  => esc_html__( 'Align Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-arrow-down',
				'name'  => esc_html__( 'Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-arrow-left',
				'name'  => esc_html__( 'Arrow Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-arrow-right',
				'name'  => esc_html__( 'Arrow Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-arrow-up',
				'name'  => esc_html__( 'Arrow Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-asterisk',
				'name'  => esc_html__( 'Asterisk', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-backward',
				'name'  => esc_html__( 'Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-ban-circle',
				'name'  => esc_html__( 'Ban Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-barcode',
				'name'  => esc_html__( 'Barcode', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-behance',
				'name'  => esc_html__( 'Behance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-bell',
				'name'  => esc_html__( 'Bell', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-blind',
				'name'  => esc_html__( 'Blind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-blogger',
				'name'  => esc_html__( 'Blogger', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-bookmark',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-bookmark-empty',
				'name'  => esc_html__( 'Bookmark Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-braille',
				'name'  => esc_html__( 'Braille', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-briefcase',
				'name'  => esc_html__( 'Briefcase', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-broom',
				'name'  => esc_html__( 'Broom', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-brush',
				'name'  => esc_html__( 'Brush', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-bulb',
				'name'  => esc_html__( 'Bulb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-bullhorn',
				'name'  => esc_html__( 'Bullhorn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-cc',
				'name'  => esc_html__( 'CC', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-css',
				'name'  => esc_html__( 'CSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-calendar',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-calendar-sign',
				'name'  => esc_html__( 'Calendar Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-camera',
				'name'  => esc_html__( 'Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-car',
				'name'  => esc_html__( 'Car', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-caret-down',
				'name'  => esc_html__( 'Caret Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-caret-left',
				'name'  => esc_html__( 'Caret Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-caret-right',
				'name'  => esc_html__( 'Caret Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-caret-up',
				'name'  => esc_html__( 'Caret Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-certificate',
				'name'  => esc_html__( 'Certificate', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-check',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-check-empty',
				'name'  => esc_html__( 'Check Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-chevron-down',
				'name'  => esc_html__( 'Chevron Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-chevron-left',
				'name'  => esc_html__( 'Chevron Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-chevron-right',
				'name'  => esc_html__( 'Chevron Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-chevron-up',
				'name'  => esc_html__( 'Chevron Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-child',
				'name'  => esc_html__( 'Child', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-circle-arrow-down',
				'name'  => esc_html__( 'Circle Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-circle-arrow-left',
				'name'  => esc_html__( 'Circle Arrow Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-circle-arrow-right',
				'name'  => esc_html__( 'Circle Arrow Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-circle-arrow-up',
				'name'  => esc_html__( 'Circle Arrow Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-cloud-alt',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-cog',
				'name'  => esc_html__( 'Cog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-cog-alt',
				'name'  => esc_html__( 'Cog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-cogs',
				'name'  => esc_html__( 'Cogs', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-comment',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-comment-alt',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-compass',
				'name'  => esc_html__( 'Compass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-compass-alt',
				'name'  => esc_html__( 'Compass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-credit-card',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-dashboard',
				'name'  => esc_html__( 'Dashboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-delicious',
				'name'  => esc_html__( 'Delicious', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-deviantart',
				'name'  => esc_html__( 'DeviantArt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-digg',
				'name'  => esc_html__( 'Digg', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-download-alt',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-dribbble',
				'name'  => esc_html__( 'Dribbble', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'el-icon-eur',
				'name'  => esc_html__( 'EUR', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-edit',
				'name'  => esc_html__( 'Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-eject',
				'name'  => esc_html__( 'Eject', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-envelope',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-envelope-alt',
				'name'  => esc_html__( 'Envelope', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-error',
				'name'  => esc_html__( 'Error', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-error-alt',
				'name'  => esc_html__( 'Error', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-exclamation-sign',
				'name'  => esc_html__( 'Exclamation Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-eye-close',
				'name'  => esc_html__( 'Eye Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-eye-open',
				'name'  => esc_html__( 'Eye Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-facetime-video',
				'name'  => esc_html__( 'Facetime Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-fast-backward',
				'name'  => esc_html__( 'Fast Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-fast-forward',
				'name'  => esc_html__( 'Fast Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-female',
				'name'  => esc_html__( 'Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-file',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-file-alt',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-file-edit',
				'name'  => esc_html__( 'File Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-file-edit-alt',
				'name'  => esc_html__( 'File Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-file-new',
				'name'  => esc_html__( 'File New', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-file-new-alt',
				'name'  => esc_html__( 'File New', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-film',
				'name'  => esc_html__( 'Film', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-fire',
				'name'  => esc_html__( 'Fire', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-flag-alt',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-flickr',
				'name'  => esc_html__( 'Flickr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-folder',
				'name'  => esc_html__( 'Folder', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-folder-close',
				'name'  => esc_html__( 'Folder Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-folder-open',
				'name'  => esc_html__( 'Folder Open', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-folder-sign',
				'name'  => esc_html__( 'Folder Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-font',
				'name'  => esc_html__( 'Font', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-fontsize',
				'name'  => esc_html__( 'Font Size', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-fork',
				'name'  => esc_html__( 'Fork', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-forward',
				'name'  => esc_html__( 'Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-forward-alt',
				'name'  => esc_html__( 'Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-foursquare',
				'name'  => esc_html__( 'Foursquare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-friendfeed',
				'name'  => esc_html__( 'FriendFeed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-friendfeed-rect',
				'name'  => esc_html__( 'FriendFeed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-fullscreen',
				'name'  => esc_html__( 'Fullscreen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'el-icon-gbp',
				'name'  => esc_html__( 'GBP', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-gift',
				'name'  => esc_html__( 'Gift', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-github',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-github-text',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-glass',
				'name'  => esc_html__( 'Glass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-glasses',
				'name'  => esc_html__( 'Glasses', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-globe',
				'name'  => esc_html__( 'Globe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-globe-alt',
				'name'  => esc_html__( 'Globe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-googleplus',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-graph',
				'name'  => esc_html__( 'Graph', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-graph-alt',
				'name'  => esc_html__( 'Graph', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-group',
				'name'  => esc_html__( 'Group', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-group-alt',
				'name'  => esc_html__( 'Group', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-guidedog',
				'name'  => esc_html__( 'Guide Dog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hdd',
				'name'  => esc_html__( 'HDD', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hand-down',
				'name'  => esc_html__( 'Hand Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hand-left',
				'name'  => esc_html__( 'Hand Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hand-right',
				'name'  => esc_html__( 'Hand Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hand-up',
				'name'  => esc_html__( 'Hand Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-headphones',
				'name'  => esc_html__( 'Headphones', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hearing-impaired',
				'name'  => esc_html__( 'Hearing Impaired', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-heart',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-heart-alt',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-heart-empty',
				'name'  => esc_html__( 'Heart Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-home-alt',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-iphone-home',
				'name'  => esc_html__( 'Home (iPhone)', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-hourglass',
				'name'  => esc_html__( 'Hourglass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-idea',
				'name'  => esc_html__( 'Idea', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-idea-alt',
				'name'  => esc_html__( 'Idea', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-inbox',
				'name'  => esc_html__( 'Inbox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-inbox-alt',
				'name'  => esc_html__( 'Inbox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-inbox-box',
				'name'  => esc_html__( 'Inbox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-indent-left',
				'name'  => esc_html__( 'Indent Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-indent-right',
				'name'  => esc_html__( 'Indent Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-info-sign',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-instagram',
				'name'  => esc_html__( 'Instagram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-align-justify',
				'name'  => esc_html__( 'Justify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-key',
				'name'  => esc_html__( 'Key', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-laptop',
				'name'  => esc_html__( 'Laptop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-laptop-alt',
				'name'  => esc_html__( 'Laptop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-lastfm',
				'name'  => esc_html__( 'Last.fm', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-leaf',
				'name'  => esc_html__( 'Leaf', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-lines',
				'name'  => esc_html__( 'Lines', 'buddyboss-theme' ),
			),
			array(
				'group' => 'c',
				'id'    => 'el-icon-link',
				'name'  => esc_html__( 'Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-linkedin',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-list',
				'name'  => esc_html__( 'List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-list-alt',
				'name'  => esc_html__( 'List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-livejournal',
				'name'  => esc_html__( 'LiveJournal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-lock-alt',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-magic',
				'name'  => esc_html__( 'Magic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-magnet',
				'name'  => esc_html__( 'Magnet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-male',
				'name'  => esc_html__( 'Male', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-map-marker',
				'name'  => esc_html__( 'Map Marker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-map-marker-alt',
				'name'  => esc_html__( 'Map Marker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-mic',
				'name'  => esc_html__( 'Mic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-minus-sign',
				'name'  => esc_html__( 'Minus Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-move',
				'name'  => esc_html__( 'Move', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-music',
				'name'  => esc_html__( 'Music', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-volume-off',
				'name'  => esc_html__( 'Mute', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-myspace',
				'name'  => esc_html__( 'MySpace', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-network',
				'name'  => esc_html__( 'Network', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-ok',
				'name'  => esc_html__( 'OK', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-ok-circle',
				'name'  => esc_html__( 'OK Circle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-ok-sign',
				'name'  => esc_html__( 'OK Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-off',
				'name'  => esc_html__( 'Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-opensource',
				'name'  => esc_html__( 'Open Source', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-paper-clip',
				'name'  => esc_html__( 'Paper Clip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-paper-clip-alt',
				'name'  => esc_html__( 'Paper Clip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-path',
				'name'  => esc_html__( 'Path', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-pause-alt',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-pencil',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-pencil-alt',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-person',
				'name'  => esc_html__( 'Person', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-phone',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-phone-alt',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-photo',
				'name'  => esc_html__( 'Photo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-photo-alt',
				'name'  => esc_html__( 'Photo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-picasa',
				'name'  => esc_html__( 'Picasa', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-picture',
				'name'  => esc_html__( 'Picture', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-pinterest',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-plane',
				'name'  => esc_html__( 'Plane', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-play-alt',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-plus-sign',
				'name'  => esc_html__( 'Plus Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-podcast',
				'name'  => esc_html__( 'Podcast', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-print',
				'name'  => esc_html__( 'Print', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-puzzle',
				'name'  => esc_html__( 'Puzzle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-qrcode',
				'name'  => esc_html__( 'QR Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-question',
				'name'  => esc_html__( 'Question', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-question-sign',
				'name'  => esc_html__( 'Question Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-quotes',
				'name'  => esc_html__( 'Quotes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-quotes-alt',
				'name'  => esc_html__( 'Quotes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-rss',
				'name'  => esc_html__( 'RSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-random',
				'name'  => esc_html__( 'Random', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-record',
				'name'  => esc_html__( 'Record', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-reddit',
				'name'  => esc_html__( 'Reddit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-refresh',
				'name'  => esc_html__( 'Refresh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-remove',
				'name'  => esc_html__( 'Remove', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-repeat',
				'name'  => esc_html__( 'Repeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-repeat-alt',
				'name'  => esc_html__( 'Repeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-resize-full',
				'name'  => esc_html__( 'Resize Full', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-resize-horizontal',
				'name'  => esc_html__( 'Resize Horizontal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-resize-small',
				'name'  => esc_html__( 'Resize Small', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-resize-vertical',
				'name'  => esc_html__( 'Resize Vertical', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-return-key',
				'name'  => esc_html__( 'Return', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-retweet',
				'name'  => esc_html__( 'Retweet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-reverse-alt',
				'name'  => esc_html__( 'Reverse', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-scissors',
				'name'  => esc_html__( 'Scissors', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-screen',
				'name'  => esc_html__( 'Screen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-screen-alt',
				'name'  => esc_html__( 'Screen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-screenshot',
				'name'  => esc_html__( 'Screenshot', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-search',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-search-alt',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-share-alt',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-shopping-cart',
				'name'  => esc_html__( 'Shopping Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-shopping-cart-sign',
				'name'  => esc_html__( 'Shopping Cart Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-signal',
				'name'  => esc_html__( 'Signal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-skype',
				'name'  => esc_html__( 'Skype', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-slideshare',
				'name'  => esc_html__( 'Slideshare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-smiley',
				'name'  => esc_html__( 'Smiley', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-smiley-alt',
				'name'  => esc_html__( 'Smiley', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-soundcloud',
				'name'  => esc_html__( 'SoundCloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-speaker',
				'name'  => esc_html__( 'Speaker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-spotify',
				'name'  => esc_html__( 'Spotify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-stackoverflow',
				'name'  => esc_html__( 'Stack Overflow', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-step-backward',
				'name'  => esc_html__( 'Step Backward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-step-forward',
				'name'  => esc_html__( 'Step Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-stop',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-stop-alt',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-stumbleupon',
				'name'  => esc_html__( 'StumbleUpon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-tag',
				'name'  => esc_html__( 'Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-tags',
				'name'  => esc_html__( 'Tags', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-tasks',
				'name'  => esc_html__( 'Tasks', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-text-height',
				'name'  => esc_html__( 'Text Height', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-text-width',
				'name'  => esc_html__( 'Text Width', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-th',
				'name'  => esc_html__( 'Thumbnails', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-th-large',
				'name'  => esc_html__( 'Thumbnails (Large)', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-th-list',
				'name'  => esc_html__( 'Thumbnails (List)', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-thumbs-down',
				'name'  => esc_html__( 'Thumbs Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-thumbs-up',
				'name'  => esc_html__( 'Thumbs Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-time',
				'name'  => esc_html__( 'Time', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-time-alt',
				'name'  => esc_html__( 'Time', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-tint',
				'name'  => esc_html__( 'Tint', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-torso',
				'name'  => esc_html__( 'Torso', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-trash-alt',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-tumblr',
				'name'  => esc_html__( 'Tumblr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'currency',
				'id'    => 'el-icon-usd',
				'name'  => esc_html__( 'USD', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-universal-access',
				'name'  => esc_html__( 'Universal Access', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-unlock',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-unlock-alt',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-upload',
				'name'  => esc_html__( 'Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-user',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-vkontakte',
				'name'  => esc_html__( 'VKontakte', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-viadeo',
				'name'  => esc_html__( 'Viadeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'el-icon-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'el-icon-video-alt',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-view-mode',
				'name'  => esc_html__( 'View Mode', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-vimeo',
				'name'  => esc_html__( 'Vimeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-volume-down',
				'name'  => esc_html__( 'Volume Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-volume-up',
				'name'  => esc_html__( 'Volume Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-w3c',
				'name'  => esc_html__( 'W3C', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-warning-sign',
				'name'  => esc_html__( 'Warning Sign', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-website',
				'name'  => esc_html__( 'Website', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'el-icon-website-alt',
				'name'  => esc_html__( 'Website', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-wheelchair',
				'name'  => esc_html__( 'Wheelchair', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-wordpress',
				'name'  => esc_html__( 'WordPress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-wrench',
				'name'  => esc_html__( 'Wrench', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'el-icon-wrench-alt',
				'name'  => esc_html__( 'Wrench', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'el-icon-youtube',
				'name'  => esc_html__( 'YouTube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-zoom-in',
				'name'  => esc_html__( 'Zoom In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'el-icon-zoom-out',
				'name'  => esc_html__( 'Zoom Out', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon items.
		 *
		 * @since 0.1.0
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_genericon_items', $items );

		return $items;
	}
}
