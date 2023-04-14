<?php
/**
 * Icon Picker Type BuddyBoss
 *
 * @package Icon_Picker
 */

require_once dirname( __FILE__ ) . '/font.php';

/**
 * BuddyBoss Icons
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */
class Icon_Picker_Type_BuddyBoss extends Icon_Picker_Type_Font {

	/**
	 * Icon type ID.
	 *
	 * @since Menu Icons 0.1.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'buddyboss';

	/**
	 * Icon type version.
	 *
	 * @since Menu Icons 0.1.0
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
		$this->name = esc_html__( 'BuddyBoss', 'buddyboss-theme' );

		parent::__construct( $args );
	}

	/**
	 * Get icon groups
	 *
	 * @since Menu Icons 0.1.0
	 *
	 * @return array
	 */
	public function get_groups() {
		$groups = bb_icon_font_map( 'groups' );

		/**
		 * Filter buddyboss groups.
		 *
		 * @since 0.1.0
		 *
		 * @param array $groups Icon groups.
		 */
		$groups = apply_filters( 'icon_picker_buddyboss_groups', $groups );

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
		$items = bb_icon_font_map( 'glyphs' );

		/**
		 * Filter BuddyBoss items.
		 *
		 * @since 0.1.0
		 *
		 * @param array $items Icon names.
		 */
		$items = apply_filters( 'icon_picker_buddyboss_items', $items );

		return $items;
	}
}
