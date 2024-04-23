<?php
/**
 * TutorLMS Integration Tab Intro.
 *
 * @since   2.4.40
 *
 * @package BuddyBoss\TutorLMS
 */

?>

<div class="wrap">
	<div class="bp-admin-card section-bp_tutor-integration">
		<?php
		$meta_icon      = bb_admin_icons( 'bp_tutor-integration' );
		$meta_icon_html = '';
		if ( ! empty( $meta_icon ) ) {
			$meta_icon_html .= '<i class="' . esc_attr( $meta_icon ) . '"></i>';
		}
		if ( ! bbp_pro_is_license_valid() ) {
			?>
			<h2>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: 1. Text. 2. Text. */
						'%1$s&nbsp;<span>&mdash; %2$s</span>',
						esc_html__( 'TutorLMS', 'buddyboss-pro' ),
						esc_html__( 'Requires license', 'buddyboss-pro' )
					)
				);
				?>
			</h2>
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
			<?php
		} elseif ( ! is_plugin_active( 'tutor/tutor.php' ) ) {
			?>
			<h2 class="has_tutorial_btn">
				<?php
				echo wp_kses(
					$meta_icon_html,
					array(
						'i' => array(
							'class' => array()
						)
					)
				);
				echo sprintf(
					/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'TutorLMS', 'buddyboss-pro' ),
					esc_html__( 'Requires plugin to activate', 'buddyboss-pro' )
				);
				?>
				<div class="bbapp-tutorial-btn">
					<a class="button" href="<?php echo esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page'    => 'bp-help',
									'article' => 87907,
								),
								'admin.php'
							)
						)
					); ?>">
						<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
					</a>
				</div>
			</h2>
			<p>
				<?php echo __( 'BuddyBoss Platform Pro has integration settings for TutorLMS. If using TutorLMS we add the ability to add courses to groups as an instructor and utilize the BuddyBoss activity feeds for Course, Lessons & Topics. We have also taken the time to style TutorLMS to match our theme for styling.', 'buddyboss-pro' ); ?>
			</p>
			<?php
		} elseif (
			defined( 'BP_PLATFORM_VERSION' ) &&
			version_compare( BP_PLATFORM_VERSION, '2.5.00', '<' )
		) {
			?>
			<h2 class="has_tutorial_btn">
				<?php
				echo wp_kses(
					$meta_icon_html,
					array(
						'i' => array(
							'class' => array(),
						),
					)
				);
				echo sprintf(
					/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'TutorLMS', 'buddyboss-pro' ),
					esc_html__( 'Requires plugin to activate', 'buddyboss-pro' )
				);
				?>
				<div class="bbapp-tutorial-btn">
					<a class="button" href="<?php echo esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page'    => 'bp-help',
									'article' => 87907,
								),
								'admin.php'
							)
						)
					); ?>">
						<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
					</a>
				</div>
			</h2>
			<p>
				<?php echo __( 'BuddyBoss Platform Pro requires BuddyBoss Platform plugin version 2.5.00 or higher to work. Please update BuddyBoss Platform.', 'buddyboss-pro' ); ?>
			</p>
			<?php
		}
		?>
	</div>
</div>
