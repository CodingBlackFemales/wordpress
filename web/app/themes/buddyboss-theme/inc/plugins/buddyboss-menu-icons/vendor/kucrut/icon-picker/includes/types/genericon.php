<?php
/**
 * Genericons
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_Genericons extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'genericon';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '3.4';

	/**
	 * Stylesheet ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $stylesheet_id = 'genericons';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Genericons', 'buddyboss-theme' );

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
				'id'   => 'media-player',
				'name' => esc_html__( 'Media Player', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'meta',
				'name' => esc_html__( 'Meta', 'buddyboss-theme' ),
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
				'id'   => 'post-formats',
				'name' => esc_html__( 'Post Formats', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'text-editor',
				'name' => esc_html__( 'Text Editor', 'buddyboss-theme' ),
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
				'group' => 'places',
				'id'    => 'genericon-404',
				'name'  => esc_html__( '404', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-activity',
				'name'  => esc_html__( 'Activity', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-anchor',
				'name'  => esc_html__( 'Anchor', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-downarrow',
				'name'  => esc_html__( 'Arrow Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-leftarrow',
				'name'  => esc_html__( 'Arrow Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-rightarrow',
				'name'  => esc_html__( 'Arrow Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-uparrow',
				'name'  => esc_html__( 'Arrow Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-aside',
				'name'  => esc_html__( 'Aside', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-attachment',
				'name'  => esc_html__( 'Attachment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-audio',
				'name'  => esc_html__( 'Audio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-bug',
				'name'  => esc_html__( 'Bug', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-cart',
				'name'  => esc_html__( 'Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-category',
				'name'  => esc_html__( 'Category', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-chat',
				'name'  => esc_html__( 'Chat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-checkmark',
				'name'  => esc_html__( 'Checkmark', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-close',
				'name'  => esc_html__( 'Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-close-alt',
				'name'  => esc_html__( 'Close', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-cloud-download',
				'name'  => esc_html__( 'Cloud Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-cloud-upload',
				'name'  => esc_html__( 'Cloud Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-code',
				'name'  => esc_html__( 'Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-codepen',
				'name'  => esc_html__( 'CodePen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-cog',
				'name'  => esc_html__( 'Cog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-collapse',
				'name'  => esc_html__( 'Collapse', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-comment',
				'name'  => esc_html__( 'Comment', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-day',
				'name'  => esc_html__( 'Day', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-digg',
				'name'  => esc_html__( 'Digg', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-document',
				'name'  => esc_html__( 'Document', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-dot',
				'name'  => esc_html__( 'Dot', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-draggable',
				'name'  => esc_html__( 'Draggable', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-dribbble',
				'name'  => esc_html__( 'Dribbble', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-dropbox',
				'name'  => esc_html__( 'DropBox', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-dropdown',
				'name'  => esc_html__( 'Dropdown', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-dropdown-left',
				'name'  => esc_html__( 'Dropdown left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-edit',
				'name'  => esc_html__( 'Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-ellipsis',
				'name'  => esc_html__( 'Ellipsis', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-expand',
				'name'  => esc_html__( 'Expand', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-external',
				'name'  => esc_html__( 'External', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-facebook-alt',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-fastforward',
				'name'  => esc_html__( 'Fast Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-feed',
				'name'  => esc_html__( 'Feed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-flickr',
				'name'  => esc_html__( 'Flickr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-foursquare',
				'name'  => esc_html__( 'Foursquare', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-fullscreen',
				'name'  => esc_html__( 'Fullscreen', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-gallery',
				'name'  => esc_html__( 'Gallery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-github',
				'name'  => esc_html__( 'GitHub', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-googleplus',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-googleplus-alt',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-handset',
				'name'  => esc_html__( 'Handset', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-heart',
				'name'  => esc_html__( 'Heart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-help',
				'name'  => esc_html__( 'Help', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-hide',
				'name'  => esc_html__( 'Hide', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-hierarchy',
				'name'  => esc_html__( 'Hierarchy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-image',
				'name'  => esc_html__( 'Image', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-info',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-instagram',
				'name'  => esc_html__( 'Instagram', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'genericon-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-key',
				'name'  => esc_html__( 'Key', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-link',
				'name'  => esc_html__( 'Link', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-linkedin',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-linkedin-alt',
				'name'  => esc_html__( 'LinkedIn', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-location',
				'name'  => esc_html__( 'Location', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-mail',
				'name'  => esc_html__( 'Mail', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-maximize',
				'name'  => esc_html__( 'Maximize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-menu',
				'name'  => esc_html__( 'Menu', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-microphone',
				'name'  => esc_html__( 'Microphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-minimize',
				'name'  => esc_html__( 'Minimize', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-month',
				'name'  => esc_html__( 'Month', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-move',
				'name'  => esc_html__( 'Move', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-next',
				'name'  => esc_html__( 'Next', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-notice',
				'name'  => esc_html__( 'Notice', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-paintbrush',
				'name'  => esc_html__( 'Paint Brush', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-path',
				'name'  => esc_html__( 'Path', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-phone',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-picture',
				'name'  => esc_html__( 'Picture', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-pinned',
				'name'  => esc_html__( 'Pinned', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-pinterest',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-pinterest-alt',
				'name'  => esc_html__( 'Pinterest', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-plugin',
				'name'  => esc_html__( 'Plugin', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-pocket',
				'name'  => esc_html__( 'Pocket', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-polldaddy',
				'name'  => esc_html__( 'PollDaddy', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-portfolio',
				'name'  => esc_html__( 'Portfolio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-previous',
				'name'  => esc_html__( 'Previous', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-print',
				'name'  => esc_html__( 'Print', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-quote',
				'name'  => esc_html__( 'Quote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-rating-empty',
				'name'  => esc_html__( 'Rating: Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-rating-full',
				'name'  => esc_html__( 'Rating: Full', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-rating-half',
				'name'  => esc_html__( 'Rating: Half', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-reddit',
				'name'  => esc_html__( 'Reddit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-refresh',
				'name'  => esc_html__( 'Refresh', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-reply',
				'name'  => esc_html__( 'Reply', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-reply-alt',
				'name'  => esc_html__( 'Reply alt', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-reply-single',
				'name'  => esc_html__( 'Reply single', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-rewind',
				'name'  => esc_html__( 'Rewind', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-search',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-send-to-phone',
				'name'  => esc_html__( 'Send to', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-send-to-tablet',
				'name'  => esc_html__( 'Send to', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-show',
				'name'  => esc_html__( 'Show', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-shuffle',
				'name'  => esc_html__( 'Shuffle', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-sitemap',
				'name'  => esc_html__( 'Sitemap', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-skip-ahead',
				'name'  => esc_html__( 'Skip ahead', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-skip-back',
				'name'  => esc_html__( 'Skip back', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-skype',
				'name'  => esc_html__( 'Skype', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-spam',
				'name'  => esc_html__( 'Spam', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-spotify',
				'name'  => esc_html__( 'Spotify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-standard',
				'name'  => esc_html__( 'Standard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-star',
				'name'  => esc_html__( 'Star', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-status',
				'name'  => esc_html__( 'Status', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media-player',
				'id'    => 'genericon-stop',
				'name'  => esc_html__( 'Stop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-stumbleupon',
				'name'  => esc_html__( 'StumbleUpon', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-subscribe',
				'name'  => esc_html__( 'Subscribe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-subscribed',
				'name'  => esc_html__( 'Subscribed', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-summary',
				'name'  => esc_html__( 'Summary', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-tablet',
				'name'  => esc_html__( 'Tablet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-tag',
				'name'  => esc_html__( 'Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-time',
				'name'  => esc_html__( 'Time', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-top',
				'name'  => esc_html__( 'Top', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-tumblr',
				'name'  => esc_html__( 'Tumblr', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-twitch',
				'name'  => esc_html__( 'Twitch', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-unapprove',
				'name'  => esc_html__( 'Unapprove', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-unsubscribe',
				'name'  => esc_html__( 'Unsubscribe', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-unzoom',
				'name'  => esc_html__( 'Unzoom', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-user',
				'name'  => esc_html__( 'User', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'genericon-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-videocamera',
				'name'  => esc_html__( 'Video Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-vimeo',
				'name'  => esc_html__( 'Vimeo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'genericon-warning',
				'name'  => esc_html__( 'Warning', 'buddyboss-theme' ),
			),
			array(
				'group' => 'places',
				'id'    => 'genericon-website',
				'name'  => esc_html__( 'Website', 'buddyboss-theme' ),
			),
			array(
				'group' => 'meta',
				'id'    => 'genericon-week',
				'name'  => esc_html__( 'Week', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-wordpress',
				'name'  => esc_html__( 'WordPress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-xpost',
				'name'  => esc_html__( 'X-Post', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'genericon-youtube',
				'name'  => esc_html__( 'Youtube', 'buddyboss-theme' ),
			),
			array(
				'group' => 'actions',
				'id'    => 'genericon-zoom',
				'name'  => esc_html__( 'Zoom', 'buddyboss-theme' ),
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
