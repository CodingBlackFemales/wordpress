<?php
/**
 * LearnDash ProPanel Overview
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnDash_ProPanel_Overview' ) ) {
	class LearnDash_ProPanel_Overview extends LearnDash_ProPanel_Widget {
		/**
		 * @var string
		 */
		protected $name;

		/**
		 * @var string
		 */
		protected $label;

		/**
		 * LearnDash_ProPanel_Overview constructor.
		 */
		public function __construct() {
			$this->name  = 'overview';
			$this->label = esc_html__( 'LearnDash Reports Overview', 'learndash' );

			parent::__construct();
			add_filter( 'learndash_propanel_template_ajax', array( $this, 'overview_template' ), 10, 2 );
		}

		function initial_template() {
			?>
			<div class="ld-propanel-widget ld-propanel-widget-<?php echo $this->name; ?> <?php echo ld_propanel_get_widget_screen_type_class( $this->name ); ?>" data-ld-widget-type="<?php echo $this->name; ?>"></div>
			<?php
		}

		public function overview_template( $output, $template ) {
			if ( 'overview' == $template ) {
				ob_start();
				include ld_propanel_get_template( 'ld-propanel-overview.php' );
				$output = ob_get_clean();
			}

			return $output;
		}
	}
}
