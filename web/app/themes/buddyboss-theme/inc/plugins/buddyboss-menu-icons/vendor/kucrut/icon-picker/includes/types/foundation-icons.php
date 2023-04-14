<?php
/**
 * Foundation Icons
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_Foundation extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'foundation-icons';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '3.0';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Foundation', 'buddyboss-theme' );

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
				'id'   => 'accessibility',
				'name' => esc_html__( 'Accessibility', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'arrows',
				'name' => esc_html__( 'Arrows', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'devices',
				'name' => esc_html__( 'Devices', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'ecommerce',
				'name' => esc_html__( 'Ecommerce', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'editor',
				'name' => esc_html__( 'Editor', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'file-types',
				'name' => esc_html__( 'File Types', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'general',
				'name' => esc_html__( 'General', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'media-control',
				'name' => esc_html__( 'Media Controls', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'misc',
				'name' => esc_html__( 'Miscellaneous', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'people',
				'name' => esc_html__( 'People', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'social',
				'name' => esc_html__( 'Social/Brand', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon groups.
		 *
		 * @since 0.1.0
		 *
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_foundations_groups', $groups );

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
				'group' => 'social',
				'id'    => 'fi-social-500px',
				'name'  => esc_html__( '500px', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-at-sign',
				'name'  => esc_html__( '@', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-asl',
				'name'  => esc_html__( 'ASL', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-add',
				'name'  => esc_html__( 'Add Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-address-book',
				'name'  => esc_html__( 'Addressbook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-adobe',
				'name'  => esc_html__( 'Adobe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-alert',
				'name'  => esc_html__( 'Alert', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-align-center',
				'name'  => esc_html__( 'Align Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-align-left',
				'name'  => esc_html__( 'Align Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-align-right',
				'name'  => esc_html__( 'Align Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-amazon',
				'name'  => esc_html__( 'Amazon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-anchor',
				'name'  => esc_html__( 'Anchor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-android',
				'name'  => esc_html__( 'Android', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-annotate',
				'name'  => esc_html__( 'Annotate', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-apple',
				'name'  => esc_html__( 'Apple', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-archive',
				'name'  => esc_html__( 'Archive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrow-down',
				'name'  => esc_html__( 'Arrow: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrow-left',
				'name'  => esc_html__( 'Arrow: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrow-right',
				'name'  => esc_html__( 'Arrow: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrow-up',
				'name'  => esc_html__( 'Arrow: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrows-compress',
				'name'  => esc_html__( 'Arrows: Compress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrows-expand',
				'name'  => esc_html__( 'Arrows: Expand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrows-in',
				'name'  => esc_html__( 'Arrows: In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'arrows',
				'id'    => 'fi-arrows-out',
				'name'  => esc_html__( 'Arrows: Out', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-asterisk',
				'name'  => esc_html__( 'Asterisk', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-background-color',
				'name'  => esc_html__( 'Background Color', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-battery-empty',
				'name'  => esc_html__( 'Battery: Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-battery-full',
				'name'  => esc_html__( 'Battery: Full', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-battery-half',
				'name'  => esc_html__( 'Battery: Half', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-behance',
				'name'  => esc_html__( 'Behance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-bing',
				'name'  => esc_html__( 'Bing', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-bitcoin',
				'name'  => esc_html__( 'Bitcoin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-bitcoin-circle',
				'name'  => esc_html__( 'Bitcoin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-blind',
				'name'  => esc_html__( 'Blind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-blogger',
				'name'  => esc_html__( 'Blogger', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-bluetooth',
				'name'  => esc_html__( 'Bluetooth', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-bookmark',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-book-bookmark',
				'name'  => esc_html__( 'Bookmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-braille',
				'name'  => esc_html__( 'Braille', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-burst',
				'name'  => esc_html__( 'Burst', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-burst-new',
				'name'  => esc_html__( 'Burst: New', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-burst-sale',
				'name'  => esc_html__( 'Burst: Sale', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-css3',
				'name'  => esc_html__( 'CSS3', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fi-page-csv',
				'name'  => esc_html__( 'CSV', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-calendar',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-camera',
				'name'  => esc_html__( 'Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-check',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-checkbox',
				'name'  => esc_html__( 'Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-clipboard',
				'name'  => esc_html__( 'Clipboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-clipboard-notes',
				'name'  => esc_html__( 'Clipboard: Notes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-clipboard-pencil',
				'name'  => esc_html__( 'Clipboard: Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-clock',
				'name'  => esc_html__( 'Clock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-closed-caption',
				'name'  => esc_html__( 'Closed Caption', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-comment',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-comment-minus',
				'name'  => esc_html__( 'Comment: Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-comment-quotes',
				'name'  => esc_html__( 'Comment: Quotes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-comment-video',
				'name'  => esc_html__( 'Comment: Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-comments',
				'name'  => esc_html__( 'Comments', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-compass',
				'name'  => esc_html__( 'Compass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-safety-cone',
				'name'  => esc_html__( 'Cone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-contrast',
				'name'  => esc_html__( 'Contrast', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-copy',
				'name'  => esc_html__( 'Copy Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-credit-card',
				'name'  => esc_html__( 'Credit Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-crop',
				'name'  => esc_html__( 'Crop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-crown',
				'name'  => esc_html__( 'Crown', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-database',
				'name'  => esc_html__( 'Database', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-delete',
				'name'  => esc_html__( 'Delete Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-delicious',
				'name'  => esc_html__( 'Delicious', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-designer-news',
				'name'  => esc_html__( 'Designer News', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-deviant-art',
				'name'  => esc_html__( 'DeviantArt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-one',
				'name'  => esc_html__( 'Dice: 1', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-two',
				'name'  => esc_html__( 'Dice: 2', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-three',
				'name'  => esc_html__( 'Dice: 3', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-four',
				'name'  => esc_html__( 'Dice: 4', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-five',
				'name'  => esc_html__( 'Dice: 5', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-die-six',
				'name'  => esc_html__( 'Dice: 6', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-digg',
				'name'  => esc_html__( 'Digg', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-dislike',
				'name'  => esc_html__( 'Dislike', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fi-page-doc',
				'name'  => esc_html__( 'Doc', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-dollar',
				'name'  => esc_html__( 'Dollar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-dollar-bill',
				'name'  => esc_html__( 'Dollar Bill', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-dribbble',
				'name'  => esc_html__( 'Dribbble', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-drive',
				'name'  => esc_html__( 'Drive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-dropbox',
				'name'  => esc_html__( 'DropBox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-multiple',
				'name'  => esc_html__( 'Duplicate Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-euro',
				'name'  => esc_html__( 'EURO', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-edit',
				'name'  => esc_html__( 'Edit Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-eject',
				'name'  => esc_html__( 'Eject', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-elevator',
				'name'  => esc_html__( 'Elevator', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-evernote',
				'name'  => esc_html__( 'Evernote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-export',
				'name'  => esc_html__( 'Export', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-export-csv',
				'name'  => esc_html__( 'Export to CSV', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-export-pdf',
				'name'  => esc_html__( 'Export to PDF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-eye',
				'name'  => esc_html__( 'Eye', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-fast-forward',
				'name'  => esc_html__( 'Fast Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-female',
				'name'  => esc_html__( 'Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-female-symbol',
				'name'  => esc_html__( 'Female Symbol', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fi-page',
				'name'  => esc_html__( 'File', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-filled',
				'name'  => esc_html__( 'Fill Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-first-aid',
				'name'  => esc_html__( 'Firs Aid', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-flickr',
				'name'  => esc_html__( 'Flickr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-folder',
				'name'  => esc_html__( 'Folder', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-folder-add',
				'name'  => esc_html__( 'Folder: Add', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-folder-lock',
				'name'  => esc_html__( 'Folder: Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-foot',
				'name'  => esc_html__( 'Foot', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-forrst',
				'name'  => esc_html__( 'Forrst', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-foundation',
				'name'  => esc_html__( 'Foundation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-foursquare',
				'name'  => esc_html__( 'Foursquare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-game-center',
				'name'  => esc_html__( 'Game Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-github',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-google-plus',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-graph-bar',
				'name'  => esc_html__( 'Graph: Bar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-graph-horizontal',
				'name'  => esc_html__( 'Graph: Horizontal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-graph-pie',
				'name'  => esc_html__( 'Graph: Pie', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-graph-trend',
				'name'  => esc_html__( 'Graph: Trend', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-guide-dog',
				'name'  => esc_html__( 'Guide Dog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-html5',
				'name'  => esc_html__( 'HTML5', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-hacker-news',
				'name'  => esc_html__( 'Hacker News', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-hearing-aid',
				'name'  => esc_html__( 'Hearing Aid', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-heart',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-hi5',
				'name'  => esc_html__( 'Hi5', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-indent-more',
				'name'  => esc_html__( 'Indent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-info',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-instagram',
				'name'  => esc_html__( 'Instagram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-joomla',
				'name'  => esc_html__( 'Joomla!', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-align-justify',
				'name'  => esc_html__( 'Justify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-key',
				'name'  => esc_html__( 'Key', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-laptop',
				'name'  => esc_html__( 'Laptop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-lastfm',
				'name'  => esc_html__( 'Last.fm', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-layout',
				'name'  => esc_html__( 'Layout', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-lightbulb',
				'name'  => esc_html__( 'Lightbulb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-like',
				'name'  => esc_html__( 'Like', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-link',
				'name'  => esc_html__( 'Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-linkedin',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-list',
				'name'  => esc_html__( 'List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-list-bullet',
				'name'  => esc_html__( 'List: Bullet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-list-number',
				'name'  => esc_html__( 'List: Number', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-list-thumbnails',
				'name'  => esc_html__( 'List: Thumbnails', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-loop',
				'name'  => esc_html__( 'Loop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-magnifying-glass',
				'name'  => esc_html__( 'Magnifying Glass', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-male',
				'name'  => esc_html__( 'Male', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-male-female',
				'name'  => esc_html__( 'Male & Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-male-symbol',
				'name'  => esc_html__( 'Male Symbol', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-map',
				'name'  => esc_html__( 'Map', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-marker',
				'name'  => esc_html__( 'Marker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-medium',
				'name'  => esc_html__( 'Medium', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-megaphone',
				'name'  => esc_html__( 'Megaphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-microphone',
				'name'  => esc_html__( 'Microphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-minus-circle',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-mobile',
				'name'  => esc_html__( 'Mobile', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-mobile-signal',
				'name'  => esc_html__( 'Mobile Signal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-monitor',
				'name'  => esc_html__( 'Monitor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-mountains',
				'name'  => esc_html__( 'Mountains', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-music',
				'name'  => esc_html__( 'Music', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-myspace',
				'name'  => esc_html__( 'My Space', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-next',
				'name'  => esc_html__( 'Next', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-no-dogs',
				'name'  => esc_html__( 'No Dogs', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-no-smoking',
				'name'  => esc_html__( 'No Smoking', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-orkut',
				'name'  => esc_html__( 'Orkut', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-indent-less',
				'name'  => esc_html__( 'Outdent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'file-types',
				'id'    => 'fi-page-pdf',
				'name'  => esc_html__( 'PDF', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-paint-bucket',
				'name'  => esc_html__( 'Paint Bucket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-paperclip',
				'name'  => esc_html__( 'Paperclip', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-path',
				'name'  => esc_html__( 'Path', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-paw',
				'name'  => esc_html__( 'Paw', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-paypal',
				'name'  => esc_html__( 'PayPal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-pencil',
				'name'  => esc_html__( 'Pencil', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-photo',
				'name'  => esc_html__( 'Photo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-picasa',
				'name'  => esc_html__( 'Picasa', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-pinterest',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-play-circle',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-play-video',
				'name'  => esc_html__( 'Play Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-pound',
				'name'  => esc_html__( 'Pound', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-power',
				'name'  => esc_html__( 'Power', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-previous',
				'name'  => esc_html__( 'Previous', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-price-tag',
				'name'  => esc_html__( 'Price Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-pricetag-multiple',
				'name'  => esc_html__( 'Price Tag: Multiple', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-print',
				'name'  => esc_html__( 'Print', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-prohibited',
				'name'  => esc_html__( 'Prohibited', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-projection-screen',
				'name'  => esc_html__( 'Projection Screen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-puzzle',
				'name'  => esc_html__( 'Puzzle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-quote',
				'name'  => esc_html__( 'Quote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-rss',
				'name'  => esc_html__( 'RSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-rdio',
				'name'  => esc_html__( 'Rdio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-record',
				'name'  => esc_html__( 'Record', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-reddit',
				'name'  => esc_html__( 'Reddit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-refresh',
				'name'  => esc_html__( 'Refresh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-remove',
				'name'  => esc_html__( 'Remove Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-results',
				'name'  => esc_html__( 'Results', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-results-demographics',
				'name'  => esc_html__( 'Results: Demographics', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-rewind',
				'name'  => esc_html__( 'Rewind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-rewind-ten',
				'name'  => esc_html__( 'Rewind 10', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-save',
				'name'  => esc_html__( 'Save', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-page-search',
				'name'  => esc_html__( 'Search in Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-sheriff-badge',
				'name'  => esc_html__( 'Sheriff Badge', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-shield',
				'name'  => esc_html__( 'Shield', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-shopping-bag',
				'name'  => esc_html__( 'Shopping Bag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-shopping-cart',
				'name'  => esc_html__( 'Shopping Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-shuffle',
				'name'  => esc_html__( 'Shuffle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-skillshare',
				'name'  => esc_html__( 'SkillShare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-skull',
				'name'  => esc_html__( 'Skull', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-skype',
				'name'  => esc_html__( 'Skype', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-smashing-mag',
				'name'  => esc_html__( 'Smashing Mag.', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-snapchat',
				'name'  => esc_html__( 'Snapchat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-sound',
				'name'  => esc_html__( 'Sound', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-spotify',
				'name'  => esc_html__( 'Spotify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-squidoo',
				'name'  => esc_html__( 'Squidoo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-stack-overflow',
				'name'  => esc_html__( 'StackOverflow', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-star',
				'name'  => esc_html__( 'Star', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-steam',
				'name'  => esc_html__( 'Steam', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-stop',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-strikethrough',
				'name'  => esc_html__( 'Strikethrough', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-stumbleupon',
				'name'  => esc_html__( 'StumbleUpon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-subscript',
				'name'  => esc_html__( 'Subscript', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-superscript',
				'name'  => esc_html__( 'Superscript', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-tablet-landscape',
				'name'  => esc_html__( 'Tablet: Landscape', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-tablet-portrait',
				'name'  => esc_html__( 'Tablet: Portrait', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-target',
				'name'  => esc_html__( 'Target', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-target-two',
				'name'  => esc_html__( 'Target', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-telephone',
				'name'  => esc_html__( 'Telephone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-text-color',
				'name'  => esc_html__( 'Text Color', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-thumbnails',
				'name'  => esc_html__( 'Thumbnails', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-ticket',
				'name'  => esc_html__( 'Ticket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torso',
				'name'  => esc_html__( 'Torso', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torso-business',
				'name'  => esc_html__( 'Torso: Business', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torso-female',
				'name'  => esc_html__( 'Torso: Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torsos',
				'name'  => esc_html__( 'Torsos', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torsos-all',
				'name'  => esc_html__( 'Torsos: All', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torsos-all-female',
				'name'  => esc_html__( 'Torsos: All Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torsos-female-male',
				'name'  => esc_html__( 'Torsos: Female & Male', 'buddyboss-theme' ),
			),
			array(
				'group' => 'people',
				'id'    => 'fi-torsos-male-female',
				'name'  => esc_html__( 'Torsos: Male & Female', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-treehouse',
				'name'  => esc_html__( 'TreeHouse', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-trees',
				'name'  => esc_html__( 'Trees', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'fi-trophy',
				'name'  => esc_html__( 'Trophy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-tumblr',
				'name'  => esc_html__( 'Tumblr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-usb',
				'name'  => esc_html__( 'USB', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-underline',
				'name'  => esc_html__( 'Underline', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-universal-access',
				'name'  => esc_html__( 'Universal Access', 'buddyboss-theme' ),
			),
			array(
				'group' => 'editor',
				'id'    => 'fi-unlink',
				'name'  => esc_html__( 'Unlink', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-unlock',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-upload',
				'name'  => esc_html__( 'Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-upload-cloud',
				'name'  => esc_html__( 'Upload to Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'devices',
				'id'    => 'fi-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-volume',
				'name'  => esc_html__( 'Volume', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-volume-none',
				'name'  => esc_html__( 'Volume: Low', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-control',
				'id'    => 'fi-volume-strike',
				'name'  => esc_html__( 'Volume: Mute', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-web',
				'name'  => esc_html__( 'Web', 'buddyboss-theme' ),
			),
			array(
				'group' => 'accessibility',
				'id'    => 'fi-wheelchair',
				'name'  => esc_html__( 'Wheelchair', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-widget',
				'name'  => esc_html__( 'Widget', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-windows',
				'name'  => esc_html__( 'Windows', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-wrench',
				'name'  => esc_html__( 'Wrench', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-x',
				'name'  => esc_html__( 'X', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-x-circle',
				'name'  => esc_html__( 'X', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-xbox',
				'name'  => esc_html__( 'XBox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-yahoo',
				'name'  => esc_html__( 'Yahoo!', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-yelp',
				'name'  => esc_html__( 'Yelp', 'buddyboss-theme' ),
			),
			array(
				'group' => 'ecommerce',
				'id'    => 'fi-yen',
				'name'  => esc_html__( 'Yen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-youtube',
				'name'  => esc_html__( 'YouTube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-zerply',
				'name'  => esc_html__( 'Zerply', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-zoom-in',
				'name'  => esc_html__( 'Zoom In', 'buddyboss-theme' ),
			),
			array(
				'group' => 'general',
				'id'    => 'fi-zoom-out',
				'name'  => esc_html__( 'Zoom Out', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'fi-social-zurb',
				'name'  => esc_html__( 'Zurb', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter genericon items.
		 *
		 * @since 0.1.0
		 *
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_foundations_items', $items );

		return $items;
	}
}
