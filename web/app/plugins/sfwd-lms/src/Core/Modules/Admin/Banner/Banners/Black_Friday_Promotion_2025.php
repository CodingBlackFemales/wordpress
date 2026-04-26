<?php
/**
 * 2025 Black Friday Promotion Banner.
 *
 * @package LearnDash\Core
 *
 * @since 4.25.4
 */

namespace LearnDash\Core\Modules\Admin\Banner\Banners;

use LearnDash\Core\Modules\Admin\Banner\Contracts\Banner;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Location;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;

/**
 * 2025 Black Friday Promotion Banner class.
 *
 * Displays a promotional banner for Black Friday/Cyber Monday 2025.
 *
 * @since 4.25.4
 */
class Black_Friday_Promotion_2025 implements Banner {
	/**
	 * Banner ID.
	 *
	 * @since 4.25.4
	 *
	 * @var string
	 */
	private const BANNER_ID = 'learndash-black-friday-promotion-2025';

	/**
	 * Template name.
	 *
	 * @since 4.25.4
	 *
	 * @var string
	 */
	private const TEMPLATE_NAME = 'modules/banners/2025-black-friday-promotion';

	/**
	 * Start date for the banner display.
	 *
	 * @since 4.25.4
	 *
	 * @var string
	 */
	private const START_DATE = '2025-11-24 00:00:00';

	/**
	 * End date for the banner display.
	 *
	 * @since 4.25.4
	 *
	 * @var string
	 */
	private const END_DATE = '2025-12-02 23:59:59';

	/**
	 * Gets the banner ID.
	 *
	 * @since 4.25.4
	 *
	 * @return string
	 */
	public function get_banner_id(): string {
		return self::BANNER_ID;
	}

	/**
	 * Registers the banner with WordPress hooks.
	 *
	 * @since 4.25.4
	 *
	 * @return AdminNotice
	 */
	public function register(): AdminNotice {
		// Display the banner using StellarWP Admin Notices.
		return AdminNotices::show(
			$this->get_banner_id(),
			$this->get_banner_content()
		)
			->when( [ Location::class, 'is_learndash_admin_page' ] )
			->between( self::START_DATE, self::END_DATE )
			->dismissible()
			->asInfo();
	}

	/**
	 * Gets the banner content HTML.
	 *
	 * @since 4.25.4
	 *
	 * @return string The banner HTML content.
	 */
	private function get_banner_content(): string {
		// translators: Promotional banner title for Black Friday/Cyber Monday sale.
		$promo_title = __( 'Teach More, Spend Less: 30% Off', 'learndash' );
		$promo_text  = sprintf(
			// translators: Promotional banner description. %s is replaced with exclusions text wrapped in italics.
			__( 'on LearnDash LMS, Add-ons, and bundles. <i>%s</i>', 'learndash' ),
			__( 'Exclusions apply.', 'learndash' )
		);
		// translators: Call-to-action button text for promotional banner.
		$shop_now_text = __( 'Shop now', 'learndash' );
		// translators: Alt text for LearnDash logo image.
		$logo_alt_text = __( 'LearnDash Logo', 'learndash' );
		// translators: Alt text for promotional banner image.
		$promo_alt_text = __( 'Woman smiling and holding a laptop, representing LearnDash promotions.', 'learndash' );

		return Template::get_admin_template(
			self::TEMPLATE_NAME,
			[
				'promo_title'     => $promo_title,
				'promo_text'      => $promo_text,
				'shop_now_text'   => $shop_now_text,
				'logo_url'        => LEARNDASH_LMS_PLUGIN_URL . 'assets/images/learndash.svg',
				'logo_alt_text'   => $logo_alt_text,
				'promo_image_url' => LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/images/admin/promo/2025_learndash_promo_woman_banner_image.webp',
				'promo_alt_text'  => $promo_alt_text,
				'shop_url'        => 'https://go.learndash.com/bfcm25',
			]
		);
	}
}
