<?php
/**
 * Secondary navigation shared by the plugin's admin screens.
 *
 * @class   Admin\Tabs
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabs class.
 *
 * The plugin owns two screens with different capabilities — importing is open
 * to editors, configuring Google credentials is not — so only one of them
 * belongs in the LearnDash menu. The other is reached from here.
 *
 * Uses core's `nav-tab-wrapper`, the same markup `themes.php` and Site Health
 * use, so the tabs inherit admin styling and stay correct through theme and
 * colour-scheme changes rather than carrying their own CSS.
 */
final class Tabs {

	/**
	 * The screens in the tab bar, in display order.
	 *
	 * Keyed by page slug. A tab whose capability the current user lacks is
	 * omitted, so an editor sees no tab bar at all rather than a lone tab or a
	 * link to a screen that would refuse them.
	 *
	 * @return array<string, array{label: string, capability: string}>
	 */
	private static function screens(): array {
		return array(
			ImporterPage::PAGE_SLUG => array(
				'label'      => __( 'Import', 'cbf-slides-importer' ),
				'capability' => 'cbf_slides_import',
			),
			SettingsPage::PAGE_SLUG => array(
				'label'      => __( 'Settings', 'cbf-slides-importer' ),
				'capability' => 'manage_options',
			),
		);
	}


	/**
	 * Render the tab bar for the screen being displayed.
	 *
	 * @param string $current Page slug of the screen rendering the bar.
	 */
	public static function render( string $current ): void {
		$screens = array_filter(
			self::screens(),
			static fn( array $screen ): bool => current_user_can( $screen['capability'] )
		);

		// One tab is not a choice, and a bar with nothing to switch to is noise.
		if ( count( $screens ) < 2 ) {
			return;
		}

		?>
		<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Slides Importer screens', 'cbf-slides-importer' ); ?>">
			<?php
			foreach ( $screens as $slug => $screen ) {
				printf(
					'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
					esc_url( admin_url( 'admin.php?page=' . $slug ) ),
					$slug === $current ? ' nav-tab-active' : '',
					$slug === $current ? ' aria-current="page"' : '',
					esc_html( $screen['label'] )
				);
			}
			?>
		</nav>
		<?php
	}
}
