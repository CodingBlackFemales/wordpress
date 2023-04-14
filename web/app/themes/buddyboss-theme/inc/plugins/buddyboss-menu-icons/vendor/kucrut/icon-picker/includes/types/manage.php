<?php
/**
 * Manage tab handler
 *
 * @package Icon_Picker
 * @author  Dzikri Aziz <kvcrvt@gmail.com>
 */

require_once dirname( __FILE__ ) . '/image.php';

/**
 * Image icon
 *
 */
class Icon_Picker_Type_Manage extends Icon_Picker_Type_Image {

	/**
	 * Icon type ID.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @var    string
	 */
	protected $id = 'manage';

	/**
	 * Template ID.
	 *
	 * @since 2.0.0
	 * @access protected
	 * @var    string
	 */
	protected $template_id = 'manage';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param array $args Misc. arguments.
	 */
	public function __construct( $args = array() ) {
		$this->name = esc_html__( 'Manage', 'buddyboss-theme' );

		parent::__construct( $args );
	}
}
