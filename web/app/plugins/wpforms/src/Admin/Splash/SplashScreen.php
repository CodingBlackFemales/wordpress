<?php

namespace WPForms\Admin\Splash;

/**
 * What's New class.
 *
 * @since 1.8.7
 */
class SplashScreen {

	use SplashTrait;

	/**
	 * Splash data.
	 *
	 * @since 1.8.7
	 *
	 * @var array
	 */
	private $splash_data = [];

	/**
	 * Initialize class.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.7
	 */
	private function hooks() {

		add_action( 'admin_init', [ $this, 'initialize_splash_data' ], 15 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
		add_filter( 'wpforms_pro_admin_dashboard_widget_welcome_block_html_message', [ $this, 'add_splash_link' ] );
		add_filter( 'wpforms_lite_admin_dashboard_widget_welcome_block_html_message', [ $this, 'add_splash_link' ] );
	}

	/**
	 * Initialize splash data.
	 *
	 * @since 1.8.7
	 */
	public function initialize_splash_data() {

		if ( ! $this->is_allow_splash() ) {
			return;
		}

		if ( empty( $this->splash_data ) ) {
			$cached_data = wpforms()->get( 'splash_cache' )->get();

			if ( empty( $cached_data ) ) {
				return;
			}

			$default_data = $this->get_default_data();

			$this->splash_data = wp_parse_args( $cached_data, $default_data );

			$version = $this->get_major_version( WPFORMS_VERSION );

			$this->update_splash_data_version( $version );
		}
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.8.7
	 */
	public function admin_enqueue_scripts() {

		$min = wpforms_get_min_suffix();

		// jQuery confirm.
		wp_register_script(
			'jquery-confirm',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);

		wp_register_style(
			'jquery-confirm',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
			[],
			'1.0.0'
		);

		wp_register_script(
			'wpforms-splash-modal',
			WPFORMS_PLUGIN_URL . "assets/js/admin/splash/modal{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION,
			true
		);

		wp_register_style(
			'wpforms-splash-modal',
			WPFORMS_PLUGIN_URL . "assets/css/admin/admin-splash-modal{$min}.css",
			[],
			WPFORMS_VERSION
		);

		wp_localize_script(
			'wpforms-splash-modal',
			'wpforms_splash_data',
			[
				'triggerForceOpen' => $this->is_force_open_splash(),
			]
		);
	}

	/**
	 * Output splash modal.
	 *
	 * @since 1.8.7
	 */
	public function admin_footer() {

		if ( $this->is_splash_empty() ) {
			return;
		}

		$this->render_modal();
	}

	/**
	 * Check if splash data is empty.
	 *
	 * @since 1.8.7
	 *
	 * @return bool True if empty, false otherwise.
	 */
	private function is_splash_empty(): bool {

		if ( empty( $this->splash_data ) ) {
			return true;
		}

		return empty( $this->retrieve_blocks_for_user( $this->splash_data['blocks'] ?? [] ) );
	}

	/**
	 * Retrieve blocks for user.
	 *
	 * @since 1.8.7
	 *
	 * @param array $blocks Splash modal blocks.
	 */
	private function retrieve_blocks_for_user( array $blocks ): array {

		$user_license = wpforms_get_license_type();

		if ( ! $user_license ) {
			$user_license = 'lite';
		}

		return array_filter(
			$blocks,
			static function ( $block ) use ( $user_license ) { //phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement

				return in_array( $user_license, $block['type'] ?? [], true );
			}
		);
	}

	/**
	 * Render splash modal.
	 *
	 * @since 1.8.7
	 *
	 * @param array $data Splash modal data.
	 */
	public function render_modal( array $data = [] ) {

		wp_enqueue_script( 'jquery-confirm' );
		wp_enqueue_style( 'jquery-confirm' );

		wp_enqueue_script( 'wpforms-splash-modal' );
		wp_enqueue_style( 'wpforms-splash-modal' );

		if ( $this->is_force_open_splash() ) {
			$this->update_splash_version();
		}

		if ( empty( $data ) ) {
			$data = $this->splash_data ?? [];

			$data['blocks'] = $this->retrieve_blocks_for_user( $data['blocks'] ?? [] );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render( 'admin/splash/modal', $data, true );
	}

	/**
	 * Add splash link to footer.
	 *
	 * @since 1.8.7
	 *
	 * @param string $content Footer content.
	 *
	 * @return string Footer content.
	 */
	public function add_splash_link( $content ): string {

		// Return if splash data is empty.
		if ( $this->is_splash_empty() ) {
			return (string) $content;
		}

		// Return if splash data version is not the same as current plugin major version.
		if ( $this->get_splash_data_version() !== $this->get_major_version( WPFORMS_VERSION ) ) {
			return (string) $content;
		}

		$content .= sprintf(
			' <span>-</span> <a href="#" class="wpforms-splash-modal-open">%s</a>',
			__( 'See the new features!', 'wpforms-lite' )
		);

		return (string) $content;
	}

	/**
	 * Check if splash modal is allowed.
	 * Only allow on form pages and dashboard.
	 *
	 * @since 1.8.7
	 *
	 * @return bool True if allowed, false otherwise.
	 */
	private function is_allow_splash(): bool {

		global $pagenow;

		return wpforms_is_admin_page() || $pagenow === 'index.php';
	}

	/**
	 * Check if splash modal should be forced open.
	 *
	 * @since 1.8.7
	 *
	 * @return bool True if forced open, false otherwise.
	 */
	private function is_force_open_splash(): bool {

		// Skip if announcements are hidden.
		if ( wpforms_setting( 'hide-announcements' ) ) {
			return false;
		}

		$splash_version = $this->get_latest_splash_version();

		// Allow if splash version not the same as current plugin major version.
		return $splash_version !== $this->get_major_version( WPFORMS_VERSION );
	}
}
