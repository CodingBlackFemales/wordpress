<?php
/**
 * Dashicons
 *
 * @package Icon_Picker
 */


require_once dirname( __FILE__ ) . '/font.php';

/**
 * Icon type: Dashicons
 *
 * @since 0.1.0
 */
class Icon_Picker_Type_Dashicons extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'dashicons';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $version = '4.3.1';

	/**
	 * Stylesheet URI.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $stylesheet_uri = '';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Dashicons', 'buddyboss-theme' );

		parent::__construct( $args );
	}

	/**
	 * Register assets.
	 *
	 * @since Menu Icons  0.1.0
	 * @wp_hook action icon_picker_loader_init
	 *
	 * @param  Icon_Picker_Loader  $loader Icon_Picker_Loader instance.
	 *
	 * @return void
	 */
	public function register_assets( Icon_Picker_Loader $loader ) {
		$loader->add_style( $this->stylesheet_id );
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
				'id'   => 'admin',
				'name' => esc_html__( 'Admin', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'post-formats',
				'name' => esc_html__( 'Post Formats', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'welcome-screen',
				'name' => esc_html__( 'Welcome Screen', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'image-editor',
				'name' => esc_html__( 'Image Editor', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'text-editor',
				'name' => esc_html__( 'Text Editor', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'post',
				'name' => esc_html__( 'Post', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'sorting',
				'name' => esc_html__( 'Sorting', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'social',
				'name' => esc_html__( 'Social', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'jobs',
				'name' => esc_html__( 'Jobs', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'products',
				'name' => esc_html__( 'Internal/Products', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'taxonomies',
				'name' => esc_html__( 'Taxonomies', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'alerts',
				'name' => esc_html__( 'Alerts/Notifications', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'media',
				'name' => esc_html__( 'Media', 'buddyboss-theme' ),
			),
			array(
				'id'   => 'misc',
				'name' => esc_html__( 'Misc./Post Types', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter dashicon groups.
		 *
		 * @since 0.1.0
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_dashicons_groups', $groups );

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
				'group' => 'admin',
				'id'    => 'dashicons-admin-appearance',
				'name'  => esc_html__( 'Appearance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-collapse',
				'name'  => esc_html__( 'Collapse', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-comments',
				'name'  => esc_html__( 'Comments', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-customizer',
				'name'  => esc_html__( 'Customizer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-dashboard',
				'name'  => esc_html__( 'Dashboard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-generic',
				'name'  => esc_html__( 'Generic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-home',
				'name'  => esc_html__( 'Home', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-media',
				'name'  => esc_html__( 'Media', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-menu',
				'name'  => esc_html__( 'Menu', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-multisite',
				'name'  => esc_html__( 'Multisite', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-network',
				'name'  => esc_html__( 'Network', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-page',
				'name'  => esc_html__( 'Page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-plugins',
				'name'  => esc_html__( 'Plugins', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-settings',
				'name'  => esc_html__( 'Settings', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-site',
				'name'  => esc_html__( 'Site', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-tools',
				'name'  => esc_html__( 'Tools', 'buddyboss-theme' ),
			),
			array(
				'group' => 'admin',
				'id'    => 'dashicons-admin-users',
				'name'  => esc_html__( 'Users', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-standard',
				'name'  => esc_html__( 'Standard', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-aside',
				'name'  => esc_html__( 'Aside', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-image',
				'name'  => esc_html__( 'Image', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-audio',
				'name'  => esc_html__( 'Audio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-quote',
				'name'  => esc_html__( 'Quote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-gallery',
				'name'  => esc_html__( 'Gallery', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-links',
				'name'  => esc_html__( 'Links', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-status',
				'name'  => esc_html__( 'Status', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post-formats',
				'id'    => 'dashicons-format-chat',
				'name'  => esc_html__( 'Chat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-add-page',
				'name'  => esc_html__( 'Add page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-comments',
				'name'  => esc_html__( 'Comments', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-edit-page',
				'name'  => esc_html__( 'Edit page', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-learn-more',
				'name'  => esc_html__( 'Learn More', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-view-site',
				'name'  => esc_html__( 'View Site', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-widgets-menus',
				'name'  => esc_html__( 'Widgets', 'buddyboss-theme' ),
			),
			array(
				'group' => 'welcome-screen',
				'id'    => 'dashicons-welcome-write-blog',
				'name'  => esc_html__( 'Write Blog', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-crop',
				'name'  => esc_html__( 'Crop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-filter',
				'name'  => esc_html__( 'Filter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-rotate',
				'name'  => esc_html__( 'Rotate', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-rotate-left',
				'name'  => esc_html__( 'Rotate Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-rotate-right',
				'name'  => esc_html__( 'Rotate Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-flip-vertical',
				'name'  => esc_html__( 'Flip Vertical', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-image-flip-horizontal',
				'name'  => esc_html__( 'Flip Horizontal', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-undo',
				'name'  => esc_html__( 'Undo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'image-editor',
				'id'    => 'dashicons-redo',
				'name'  => esc_html__( 'Redo', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-bold',
				'name'  => esc_html__( 'Bold', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-italic',
				'name'  => esc_html__( 'Italic', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-ul',
				'name'  => esc_html__( 'Unordered List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-ol',
				'name'  => esc_html__( 'Ordered List', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-quote',
				'name'  => esc_html__( 'Quote', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-alignleft',
				'name'  => esc_html__( 'Align Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-aligncenter',
				'name'  => esc_html__( 'Align Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-alignright',
				'name'  => esc_html__( 'Align Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-insertmore',
				'name'  => esc_html__( 'Insert More', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-spellcheck',
				'name'  => esc_html__( 'Spell Check', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-distractionfree',
				'name'  => esc_html__( 'Distraction-free', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-kitchensink',
				'name'  => esc_html__( 'Kitchensink', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-underline',
				'name'  => esc_html__( 'Underline', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-justify',
				'name'  => esc_html__( 'Justify', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-textcolor',
				'name'  => esc_html__( 'Text Color', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-paste-word',
				'name'  => esc_html__( 'Paste Word', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-paste-text',
				'name'  => esc_html__( 'Paste Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-removeformatting',
				'name'  => esc_html__( 'Clear Formatting', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-customchar',
				'name'  => esc_html__( 'Custom Characters', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-indent',
				'name'  => esc_html__( 'Indent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-outdent',
				'name'  => esc_html__( 'Outdent', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-help',
				'name'  => esc_html__( 'Help', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-strikethrough',
				'name'  => esc_html__( 'Strikethrough', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-unlink',
				'name'  => esc_html__( 'Unlink', 'buddyboss-theme' ),
			),
			array(
				'group' => 'text-editor',
				'id'    => 'dashicons-editor-rtl',
				'name'  => esc_html__( 'RTL', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-align-left',
				'name'  => esc_html__( 'Align Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-align-right',
				'name'  => esc_html__( 'Align Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-align-center',
				'name'  => esc_html__( 'Align Center', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-align-none',
				'name'  => esc_html__( 'Align None', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-lock',
				'name'  => esc_html__( 'Lock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-calendar',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-calendar-alt',
				'name'  => esc_html__( 'Calendar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-hidden',
				'name'  => esc_html__( 'Hidden', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-visibility',
				'name'  => esc_html__( 'Visibility', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-post-status',
				'name'  => esc_html__( 'Post Status', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-post-trash',
				'name'  => esc_html__( 'Post Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-edit',
				'name'  => esc_html__( 'Edit', 'buddyboss-theme' ),
			),
			array(
				'group' => 'post',
				'id'    => 'dashicons-trash',
				'name'  => esc_html__( 'Trash', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-up',
				'name'  => esc_html__( 'Arrow: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-down',
				'name'  => esc_html__( 'Arrow: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-left',
				'name'  => esc_html__( 'Arrow: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-right',
				'name'  => esc_html__( 'Arrow: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-up-alt',
				'name'  => esc_html__( 'Arrow: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-down-alt',
				'name'  => esc_html__( 'Arrow: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-left-alt',
				'name'  => esc_html__( 'Arrow: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-right-alt',
				'name'  => esc_html__( 'Arrow: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-up-alt2',
				'name'  => esc_html__( 'Arrow: Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-down-alt2',
				'name'  => esc_html__( 'Arrow: Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-left-alt2',
				'name'  => esc_html__( 'Arrow: Left', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-arrow-right-alt2',
				'name'  => esc_html__( 'Arrow: Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-leftright',
				'name'  => esc_html__( 'Left-Right', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-sort',
				'name'  => esc_html__( 'Sort', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-list-view',
				'name'  => esc_html__( 'List View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-exerpt-view',
				'name'  => esc_html__( 'Excerpt View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'sorting',
				'id'    => 'dashicons-grid-view',
				'name'  => esc_html__( 'Grid View', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-share',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-share-alt',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-share-alt2',
				'name'  => esc_html__( 'Share', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-twitter',
				'name'  => esc_html__( 'Twitter', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-rss',
				'name'  => esc_html__( 'RSS', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-email',
				'name'  => esc_html__( 'Email', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-email-alt',
				'name'  => esc_html__( 'Email', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-facebook',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-facebook-alt',
				'name'  => esc_html__( 'Facebook', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-googleplus',
				'name'  => esc_html__( 'Google+', 'buddyboss-theme' ),
			),
			array(
				'group' => 'social',
				'id'    => 'dashicons-networking',
				'name'  => esc_html__( 'Networking', 'buddyboss-theme' ),
			),
			array(
				'group' => 'jobs',
				'id'    => 'dashicons-art',
				'name'  => esc_html__( 'Art', 'buddyboss-theme' ),
			),
			array(
				'group' => 'jobs',
				'id'    => 'dashicons-hammer',
				'name'  => esc_html__( 'Hammer', 'buddyboss-theme' ),
			),
			array(
				'group' => 'jobs',
				'id'    => 'dashicons-migrate',
				'name'  => esc_html__( 'Migrate', 'buddyboss-theme' ),
			),
			array(
				'group' => 'jobs',
				'id'    => 'dashicons-performance',
				'name'  => esc_html__( 'Performance', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-wordpress',
				'name'  => esc_html__( 'WordPress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-wordpress-alt',
				'name'  => esc_html__( 'WordPress', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-pressthis',
				'name'  => esc_html__( 'PressThis', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-update',
				'name'  => esc_html__( 'Update', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-screenoptions',
				'name'  => esc_html__( 'Screen Options', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-info',
				'name'  => esc_html__( 'Info', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-cart',
				'name'  => esc_html__( 'Cart', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-feedback',
				'name'  => esc_html__( 'Feedback', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-cloud',
				'name'  => esc_html__( 'Cloud', 'buddyboss-theme' ),
			),
			array(
				'group' => 'products',
				'id'    => 'dashicons-translation',
				'name'  => esc_html__( 'Translation', 'buddyboss-theme' ),
			),
			array(
				'group' => 'taxonomies',
				'id'    => 'dashicons-tag',
				'name'  => esc_html__( 'Tag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'taxonomies',
				'id'    => 'dashicons-category',
				'name'  => esc_html__( 'Category', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-yes',
				'name'  => esc_html__( 'Yes', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-no',
				'name'  => esc_html__( 'No', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-no-alt',
				'name'  => esc_html__( 'No', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-plus',
				'name'  => esc_html__( 'Plus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-minus',
				'name'  => esc_html__( 'Minus', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-dismiss',
				'name'  => esc_html__( 'Dismiss', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-marker',
				'name'  => esc_html__( 'Marker', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-star-filled',
				'name'  => esc_html__( 'Star: Filled', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-star-half',
				'name'  => esc_html__( 'Star: Half', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-star-empty',
				'name'  => esc_html__( 'Star: Empty', 'buddyboss-theme' ),
			),
			array(
				'group' => 'alerts',
				'id'    => 'dashicons-flag',
				'name'  => esc_html__( 'Flag', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-skipback',
				'name'  => esc_html__( 'Skip Back', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-back',
				'name'  => esc_html__( 'Back', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-play',
				'name'  => esc_html__( 'Play', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-pause',
				'name'  => esc_html__( 'Pause', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-forward',
				'name'  => esc_html__( 'Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-skipforward',
				'name'  => esc_html__( 'Skip Forward', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-repeat',
				'name'  => esc_html__( 'Repeat', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-volumeon',
				'name'  => esc_html__( 'Volume: On', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-controls-volumeoff',
				'name'  => esc_html__( 'Volume: Off', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-archive',
				'name'  => esc_html__( 'Archive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-audio',
				'name'  => esc_html__( 'Audio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-code',
				'name'  => esc_html__( 'Code', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-default',
				'name'  => esc_html__( 'Default', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-document',
				'name'  => esc_html__( 'Document', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-interactive',
				'name'  => esc_html__( 'Interactive', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-spreadsheet',
				'name'  => esc_html__( 'Spreadsheet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-text',
				'name'  => esc_html__( 'Text', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-media-video',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-playlist-audio',
				'name'  => esc_html__( 'Audio Playlist', 'buddyboss-theme' ),
			),
			array(
				'group' => 'media',
				'id'    => 'dashicons-playlist-video',
				'name'  => esc_html__( 'Video Playlist', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-album',
				'name'  => esc_html__( 'Album', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-analytics',
				'name'  => esc_html__( 'Analytics', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-awards',
				'name'  => esc_html__( 'Awards', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-backup',
				'name'  => esc_html__( 'Backup', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-building',
				'name'  => esc_html__( 'Building', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-businessman',
				'name'  => esc_html__( 'Businessman', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-camera',
				'name'  => esc_html__( 'Camera', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-carrot',
				'name'  => esc_html__( 'Carrot', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-chart-pie',
				'name'  => esc_html__( 'Chart: Pie', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-chart-bar',
				'name'  => esc_html__( 'Chart: Bar', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-chart-line',
				'name'  => esc_html__( 'Chart: Line', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-chart-area',
				'name'  => esc_html__( 'Chart: Area', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-desktop',
				'name'  => esc_html__( 'Desktop', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-forms',
				'name'  => esc_html__( 'Forms', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-groups',
				'name'  => esc_html__( 'Groups', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-id',
				'name'  => esc_html__( 'ID', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-id-alt',
				'name'  => esc_html__( 'ID', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-images-alt',
				'name'  => esc_html__( 'Images', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-images-alt2',
				'name'  => esc_html__( 'Images', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-index-card',
				'name'  => esc_html__( 'Index Card', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-layout',
				'name'  => esc_html__( 'Layout', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-location',
				'name'  => esc_html__( 'Location', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-location-alt',
				'name'  => esc_html__( 'Location', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-products',
				'name'  => esc_html__( 'Products', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-portfolio',
				'name'  => esc_html__( 'Portfolio', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-book',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-book-alt',
				'name'  => esc_html__( 'Book', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-download',
				'name'  => esc_html__( 'Download', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-upload',
				'name'  => esc_html__( 'Upload', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-clock',
				'name'  => esc_html__( 'Clock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-lightbulb',
				'name'  => esc_html__( 'Lightbulb', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-money',
				'name'  => esc_html__( 'Money', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-palmtree',
				'name'  => esc_html__( 'Palm Tree', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-phone',
				'name'  => esc_html__( 'Phone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-search',
				'name'  => esc_html__( 'Search', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-shield',
				'name'  => esc_html__( 'Shield', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-shield-alt',
				'name'  => esc_html__( 'Shield', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-slides',
				'name'  => esc_html__( 'Slides', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-smartphone',
				'name'  => esc_html__( 'Smartphone', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-smiley',
				'name'  => esc_html__( 'Smiley', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-sos',
				'name'  => esc_html__( 'S.O.S.', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-sticky',
				'name'  => esc_html__( 'Sticky', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-store',
				'name'  => esc_html__( 'Store', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-tablet',
				'name'  => esc_html__( 'Tablet', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-testimonial',
				'name'  => esc_html__( 'Testimonial', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-tickets-alt',
				'name'  => esc_html__( 'Tickets', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-thumbs-up',
				'name'  => esc_html__( 'Thumbs Up', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-thumbs-down',
				'name'  => esc_html__( 'Thumbs Down', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-unlock',
				'name'  => esc_html__( 'Unlock', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-vault',
				'name'  => esc_html__( 'Vault', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-video-alt',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-video-alt2',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-video-alt3',
				'name'  => esc_html__( 'Video', 'buddyboss-theme' ),
			),
			array(
				'group' => 'misc',
				'id'    => 'dashicons-warning',
				'name'  => esc_html__( 'Warning', 'buddyboss-theme' ),
			),
		);

		/**
		 * Filter dashicon items.
		 *
		 * @since 0.1.0
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_dashicons_items', $items );

		return $items;
	}
}
