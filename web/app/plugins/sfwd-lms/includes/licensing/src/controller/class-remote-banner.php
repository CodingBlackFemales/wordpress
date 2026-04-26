<?php
/**
 * Handle the banners
 *
 * @since 4.18.0
 *
 * @package LearnDash\Hub
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Framework\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Handle the logic for showing the banners
 *
 * @since 4.18.0
 */
class RemoteBanners extends Controller {
	const DISMISS_REMOTE_ACTION = 'dismiss_remote', IGNORE_REMOTE_SLUGS_NAME = 'ld_hub_remote_ignore';

	/**
	 * Register hooks.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'maybe_show_banners' ), 9999 );
		add_action( 'wp_ajax_flag_remote_dismiss', array( $this, 'hide_banner' ) );
	}

	/**
	 * Register scripts and styles.
	 *
	 * @since 4.18.0
	 * @deprecated 4.18.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		_deprecated_function( __METHOD__, '4.18.0', 'LearnDash\Core\Modules\Licensing\Assets::register_assets' );

		wp_register_style(
			'learndash-hub-remote',
			hub_asset_url( '/assets/css/remote.css' ),
			array(),
			HUB_VERSION
		);

		wp_register_script(
			'learndash-hub-remote',
			hub_asset_url( '/assets/scripts/remote.js' ),
			array( 'jquery' ),
			HUB_VERSION,
			array(
				'in_footer' => true,
			)
		);
	}

	/**
	 * Ajax endpoint for hide a banner permanent
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function hide_banner(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, self::DISMISS_REMOTE_ACTION ) ) {
			return;
		}

		$slug    = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
		$slugs   = get_option( self::IGNORE_REMOTE_SLUGS_NAME, array() );
		$slugs[] = $slug;
		$slugs   = array_unique( $slugs );
		update_option( self::IGNORE_REMOTE_SLUGS_NAME, $slugs );
		wp_send_json_success();
	}

	/**
	 * Return the banners.
	 *
	 * @since 4.18.0
	 *
	 * @return array
	 */
	public function get_banners(): array {
		$cache_name = 'learndash-hub-remote';
		$cached     = get_transient( $cache_name );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$request = wp_remote_get(
			REMOTE_SITE . '/wp-json/learndash/v2/banners',
			array(
				// should be very quick.
				'timeout' => 10,
			)
		);

		if ( 200 === wp_remote_retrieve_response_code( $request ) ) {
			// if it is falling here means something wrong with the request, let try again in a minute.
			$body = wp_remote_retrieve_body( $request );
			$data = json_decode( $body, true );

			if ( is_array( $data ) ) {
				$ttl = $data['ttl'] ?? 15;
				set_transient( $cache_name, $data['banners'], MINUTE_IN_SECONDS * $ttl );

				return $data['banners'];
			}
		}

		// fallback if anything wrong here, will try later in a minute.
		set_transient( $cache_name, array(), MINUTE_IN_SECONDS );

		return array();
	}

	/**
	 * Maybe show the banners
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function maybe_show_banners(): void {
		$banners = $this->filter_displayable_banners( $this->get_banners() );

		if ( empty( $banners ) ) {
			// nothing to show.
			return;
		}

		echo '<div class="wrap">';
		foreach ( $banners as $banner ) {
			$this->display_ads( $banner );
		}
		echo '</div>';
	}

	/**
	 * Filter the banner by its condition, eg page slug and time frame
	 *
	 * @since 4.18.0
	 * @since 4.18.0 Changed method visibility to public.
	 *
	 * @param array $banners The banner fetched from API.
	 *
	 * @return array
	 */
	public function filter_displayable_banners( array $banners ): array {
		$current_path = substr( wp_unslash( $_SERVER['REQUEST_URI'] ), strlen( '/wp_admin/' ) );
		$ignore_slugs = get_option( self::IGNORE_REMOTE_SLUGS_NAME, array() );

		foreach ( $banners as $key => $banner ) {
			if ( in_array( $banner['slug'], $ignore_slugs, true ) ) {
				unset( $banners[ $key ] );
				continue;
			}

			$page_slugs = explode( ',', $banner['show_on_page_slug'] );
			$page_slugs = array_map( 'trim', $page_slugs );

			if ( ! in_array( $current_path, $page_slugs, true ) ) {
				unset( $banners[ $key ] );
				continue;
			}

			// check the date.
			if (
				! empty( $banner['date_start'] )
				&& time() < strtotime( $banner['date_start'] )
			) {
				// time has not come.
				unset( $banners[ $key ] );
				continue;
			}

			if (
				! empty( $banner['date_end'] )
				&& time() > strtotime( $banner['date_end'] )
			) {
				// expired.
				unset( $banners[ $key ] );
			}
		}

		return $banners;
	}

	/**
	 * Render the ads.
	 *
	 * @since 4.18.0
	 *
	 * @param array $banner The banner settings.
	 *
	 * @return void
	 */
	protected function display_ads( array $banner ): void {
		switch ( $banner['type'] ) {
			case 'gradient_background':
				$this->render( 'banners/banner-1-responsive-gradient', $banner );
				break;
			case 'image_background':
				$this->render( 'banners/banner-1-responsive', $banner );
				break;
			case 'image_only':
				$this->render( 'banners/banner-1', $banner );
				break;
		}
	}
}
