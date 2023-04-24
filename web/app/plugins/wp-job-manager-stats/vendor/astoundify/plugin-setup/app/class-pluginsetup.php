<?php
/**
 * A reusable setup wizard for plugins.
 *
 * @since 1.0.0
 * @package PluginSetup
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astoundify_PluginSetup' ) ) :

	/**
	 * Plugin Setup.
	 *
	 * @class Astoundify_ContentImporter
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class Astoundify_PluginSetup {

		/**
		 * Setup Config
		 *
		 * @since 1.0.0
		 * @var array Setup Config.
		 */
		var $config = array();

		/**
		 * Setup Steps
		 *
		 * @since 1.0.0
		 * @var array Setup Steps.
		 */
		var $steps = array();

		/**
		 * Strings Labels
		 *
		 * @since 1.0.0
		 * @var array Strings Labels.
		 */
		var $labels = array();

		/**
		 * Setup Count
		 *
		 * @since 1.0.0
		 * @var int Setup Steps Count.
		 */
		var $steps_count = 0;

		/**
		 * Settings Page URL
		 *
		 * @since 1.0.0
		 * @var string Settings Page URL.
		 */
		var $page_url = '';

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $config = array() ) {
			$defaults = array(
				'id'               => '',
				'capability'       => 'manage_options',
				'redirect'         => false,
				'menu_title'       => '',
				'page_title'       => '',
				'labels'           => array(
					'next_step_button' => '',
					'skip_step_button' => '',
				),
				'steps'            => array(),
			);
			$config = wp_parse_args( $config, $defaults );

			// Set vars.
			$this->config      = $config;
			$this->steps       = $config['steps'];
			$this->labels      = $config['labels'];
			$this->page_url    = add_query_arg( 'page', $this->config['id'], admin_url( 'admin.php' ) );
			$this->steps_count = count( $this->steps );

			// Setup.
			add_action( 'admin_init', array( $this, 'action' ) );
			add_action( 'admin_menu', array( $this, 'add_settings' ) );
		}

		/**
		 * Action
		 *
		 * @since 1.0.0
		 */
		public function action() {

			// Check caps.
			if ( ! current_user_can( $this->config['capability'] ) ) {
				return;
			}

			// On Setup Page.
			if ( isset( $_GET['page'] ) && $this->config['id'] === $_GET['page'] ) {

				// Track setup.
				update_option( $this->config['id'], '1' );

				// Handler Action.
				if ( isset( $_POST['step'], $_POST['_nonce'] ) && $_POST['step'] && $_POST['_nonce'] && wp_verify_nonce( $_POST['_nonce'], "{$this->config['id']}_nonce" ) ) {

					$step = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

					// Step actions.
					if ( isset( $this->steps[ $step ]['handler'] ) && is_callable( $this->steps[ $step ]['handler'] ) ) {
						call_user_func( $this->steps[ $step ]['handler'] );
					}
				}
			} else { // Not on setup page.

				if ( $this->config['redirect'] && ! get_option( $this->config['id'] ) ) { // Redirect if set.
					wp_safe_redirect( esc_url_raw( $this->page_url ) );
					exit;
				}
			}
		}

		/**
		 * Add Settings
		 *
		 * @since 1.0.0
		 */
		public function add_settings() {
			// Add menu page.
			$page = add_menu_page(
				$page_title = $this->config['page_title'],
				$menu_title = $this->config['menu_title'],
				$capability = $this->config['capability'],
				$menu_slug  = $this->config['id'],
				$function   = array( $this, 'settings_callback' ),
				$icon       = 'dashicons-admin-generic',
				$position   = 999 // Bottom.
			);

			// Remove menu if already set.
			if ( get_option( $this->config['id'] ) ) {
				remove_menu_page( $this->config['id'] );
			}
		}

		/**
		 * HTML
		 *
		 * @since 1.0.0
		 */
		public function settings_callback() {
			// Get current step.
			$step = 1;

			if ( isset( $_GET['step'] ) && absint( $_GET['step'] ) > 1 ) {
				$step = absint( $_GET['step'] );
			}

			$next_step = $step + 1;
			$next_step_url = add_query_arg( 'step', $next_step, $this->page_url );
?>

<div class="wrap">

	<h1><?php echo esc_html( $this->config['page_title'] ); ?></h1>

	<form method="post" action="<?php echo esc_url( $next_step_url ); ?>">

		<h2 class="nav-tab-wrapper wp-clearfix plugin-setup-steps-nav">
			<?php foreach ( $this->steps as $step_num => $step_data ) : if ( isset ( $step_data['title'] ) ) : ?>
				<span class="<?php echo esc_attr( $step_num === $step ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>"><?php echo esc_html( $step_data['title'] ); ?></span>
			<?php endif; endforeach; ?>
		</h2>

		<style>
			.plugin-setup-steps-nav .nav-tab:hover {
				background: #e5e5e5;
				cursor: auto;
			}
			.plugin-setup-steps-nav .nav-tab-active:hover {
				background: #f1f1f1;
				cursor: auto;
			}
		</style>

		<?php if ( isset( $this->steps[ $step ]['view'] ) && is_callable( $this->steps[ $step ]['view'] ) ) : ?>
			<?php call_user_func( $this->steps[ $step ]['view'] ); // HTML Output. ?>
		<?php endif; ?>

		<?php if ( absint( $step ) !== absint( $this->steps_count ) ) : // Not last steps. ?>

			<p class="submit">
				<?php submit_button( $this->labels['next_step_button'], 'primary', 'submit', false ); ?> 
				<?php if ( $this->labels['skip_step_button'] ) : ?>
					<a class="button" href="<?php echo esc_attr( $next_step_url ); ?>"><?php echo esc_html( $this->labels['skip_step_button'] ); ?></a>
				<?php endif; ?>
			</p>

			<input type="hidden" name="step" value="<?php echo absint( $step );?>">
			<input type="hidden" name="next_step" value="<?php echo absint( $next_step );?>">
			<?php wp_nonce_field( "{$this->config['id']}_nonce", '_nonce' ); ?>

		<?php else : // Last step. ?>

			<?php update_option( $this->config['id'], '1' ); // Last step. ?>

		<?php endif; ?>
	</form>

</div><!-- wrap -->

<?php
		}

	}

endif;
