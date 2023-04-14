<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains update information about BuddyBoss PRO.
 *
 * @package BuddyBoss
 * @since   2.1.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


// If you have not any release note then set $show_overview as false.
$show_overview = false;

// Get release data based on plugin version from gitHub API.
$cache_key        = 'bb_pro_changelog_' . bb_platform_pro()->version;
$bb_pro_changelog = wp_cache_get( $cache_key, 'bb-pro' );
if ( false === $bb_pro_changelog ) {
	if ( ! function_exists( 'plugins_api' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	}

	$api = plugins_api(
		'plugin_information',
		array(
			'slug' => wp_unslash( 'buddyboss-platform-pro' ),
		)
	);

	if ( is_wp_error( $api ) ) {
		wp_die( $api );
	}

	// Sanitize HTML.
	$api->sections['changelog'] = wp_kses_post( $api->sections['changelog'] );

	$section_content = ! empty( $api->sections['changelog'] ) ? $api->sections['changelog'] : array();
	if ( ! empty( $section_content ) ) {
		$section_content = links_add_base_url( $section_content, 'https://wordpress.org/plugins/' . $api->slug . '/' );
		$lines           = preg_split( '/[\n\r]+/', $section_content );
		$version         = $api->version;
		$versions        = array();
		$changelog       = '';
		$version_content = '';
		if ( ! empty( $lines ) ) {
			foreach ( $lines as $line ) {
				if ( empty( $line ) ) {
					continue;
				}
				if ( preg_match( '/^\d/', trim( wp_strip_all_tags( $line ) ) ) ) {
					$version = trim( wp_strip_all_tags( $line ) );
				} else {
					$version_content .= $line;
				}
				$versions[ $version ] = $version_content;
			}
		}
		$bb_pro_changelog = $versions[ $api->version ];
		wp_cache_set( $cache_key, $bb_pro_changelog, 'bb-pro' );
	}
}


// If you have any video then add url here.
$video_url = 'https://www.youtube.com/embed/ThTdHOYwNxU';
?>
<div id="bp-pro-hello-backdrop" style="display: none;"></div>

<div id="bp-pro-hello-container" class="bp-pro-hello-buddyboss bb-update-modal bb-onload-modal" role="dialog" aria-labelledby="bp-pro-hello-title" style="display: none;">
	<div class="bp-pro-hello-header" role="document">
		<div class="bp-pro-hello-close">
			<button type="button" class="close-modal button bp-pro-tooltip" data-bp-pro-tooltip-pos="down" data-bp-pro-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss-pro' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss-pro' ); ?>
			</button>
		</div>

		<div class="bp-pro-hello-title">
			<h1 id="bp-pro-hello-title" tabindex="-1"><?php esc_html_e( 'Release Notes', 'buddyboss-pro' ); ?></h1>
			<span class="bb-version"><?php echo esc_html__( 'BuddyBoss Platform Pro v', 'buddyboss-pro' ) . esc_html( bb_platform_pro()->version ); ?></span>
		</div>
		<ul class="bb-pro-hello-tabs">
			<?php if ( true === $show_overview ) { ?>
				<li><a href="#bb-pro-release-overview" class="bb-pro-hello-tabs_anchor is_active" data-action="bb-pro-release-overview"><?php esc_html_e( 'Overview', 'buddyboss-pro' ); ?></a></li>
				<?php if ( isset( $bb_pro_changelog ) && ! empty( $bb_pro_changelog ) ) { ?>
					<li><a href="#bb-pro-release-changelog" class="bb-pro-hello-tabs_anchor" data-action="bb-pro-release-changelog"><?php esc_html_e( 'Changelog', 'buddyboss-pro' ); ?></a></li>
				<?php
				}
			}
			?>
		</ul>
	</div>

	<div class="bp-pro-hello-content">
		<div id="bb-release-content" class="bb-release-content">
			<?php
			if ( true === $show_overview ) {
				?>
				<div id="bb-pro-release-overview" class="bb-pro-hello-tabs_content is_active">
					<h3><?php esc_html_e( 'Welcome to BuddyBoss Theme 2.0 🥳', 'buddyboss-pro' ); ?></h3>
					<p><?php esc_html_e( 'Check out the video below for a full walkthrough of all the new features and updates available to you in this release.', 'buddyboss-pro' ); ?></p>
					<p>
						<?php
						echo sprintf(
							/* translators: %1$s - Overview tab link for details. */
							esc_html__( 'As this update contains a number of improvements to the theme’s colors, layouts and styling, we recommend you reconfigure your Theme Options and review any custom CSS you may have.  For more information on how to update, %1$s.', 'buddyboss-pro' ),
							/* translators: %1$s - Overview tab link for details. %2$s - tutorial text */
							sprintf(
								'<a href="%1$s" target="_blank">%2$s</a>',
								esc_url( 'https://www.buddyboss.com/resources/docs/buddyboss-theme/getting-started/updating-to-buddyboss-theme-2-0' ),
								esc_html__( 'check out this tutorial', 'buddyboss-pro' )
							)
						);
						?>
					</p>
					<?php
					if ( ! empty( $video_url ) ) {
						?>
						<p><?php esc_html_e( 'For more information, please watch the video below:', 'buddyboss-pro' ); ?></p>
						<div class="video-wrapper">
							<div class="video-container">
								<iframe width="560" height="315" src="<?php echo esc_url( $video_url ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			if ( isset( $bb_pro_changelog ) && ! empty( $bb_pro_changelog ) ) {
				?>
				<div id="bb-pro-release-changelog" class="bb-pro-hello-tabs_content bb-release-changelog <?php echo esc_attr( false === $show_overview ? 'is_active' : '' ); ?>">
					<h2><?php esc_html_e( 'Changes:', 'buddyboss-pro' ); ?></h2>
					<?php
					echo wp_kses_post( $bb_pro_changelog );
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
