<?php
/**
 * Integration tab.
 *
 * @package BuddyBossPro/Integration/Zoom
 */

?>
<div class="wrap">

	<div class="bp-admin-card section-bp_zoom_settings_section">
		<h2><?php echo wp_kses_post( 'Zoom <span>&mdash; requires license</span>', 'buddyboss-pro' ); ?></h2>
		<p>
			<?php
			printf(
					/* translators: %1$s - Platform Pro string, %2$s - License URL */
				esc_html__( 'You need to activate a license key for %1$s to unlock this feature. %2$s', 'buddyboss-pro' ),
				'<strong>' . esc_html__( 'BuddyBoss Platform Pro', 'buddyboss-pro' ) . '</strong>',
				sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page' => 'buddyboss-updater',
									'tab'  => 'buddyboss_theme',
								),
								'admin.php'
							)
						)
					),
					esc_html__( 'Add License key', 'buddyboss-pro' )
				)
			)
			?>
		</p>
	</div>

</div>
