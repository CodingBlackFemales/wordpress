<?php
/**
 * MemberpressLMS Integration Tab Intro.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

?>

<div class="wrap">
	<div class="bp-admin-card section-bp_meprlms-integration">
		<?php
		$meta_icon      = bb_admin_icons( 'bp_meprlms-integration' );
		$meta_icon_html = '';
		if ( ! empty( $meta_icon ) ) {
			$meta_icon_html .= '<i class="' . esc_attr( $meta_icon ) . '"></i>';
		}
		if ( bb_pro_should_lock_features() ) {
			?>
			<h2>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: 1. Text. 2. Text. */
						'%1$s&nbsp;<span>&mdash; %2$s</span>',
						esc_html__( 'Memberpress Courses', 'buddyboss-pro' ),
						esc_html__( 'Requires license', 'buddyboss-pro' )
					)
				);
				?>
			</h2>
			<p>
				<?php
				printf(
					/* translators: 1 - Platform Pro string, 2 - License URL */
					esc_html__( 'You need to activate a license key for %1$s to unlock this feature. %2$s', 'buddyboss-pro' ),
					'<strong>' . esc_html__( 'BuddyBoss Platform Pro', 'buddyboss-pro' ) . '</strong>',
					'<a href="' . esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page' => 'buddyboss-updater',
									'tab'  => 'buddyboss_theme',
								),
								'admin.php'
							)
						)
					) . '">' . esc_html__( 'Add License key', 'buddyboss-pro' ) . '</a>'
				)
				?>
			</p>
			<?php
		} elseif ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
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
				printf(
					/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'Memberpress Courses', 'buddyboss-pro' ),
					esc_html__( 'Requires plugin to activate', 'buddyboss-pro' )
				);
				?>
				<div class="bbapp-tutorial-btn">
					<a class="button" target="_blank" href="
					<?php
					echo esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page'    => 'bp-help',
									'article' => 127882,
								),
								'admin.php'
							)
						)
					);
					?>
					">
						<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
					</a>
				</div>
			</h2>
			<p>
				<?php echo esc_html__( 'BuddyBoss Platform Pro has integration settings for Memberpress courses. If using Memberpress courses we add the ability to add courses to groups as an instructor and utilize the BuddyBoss activity feeds for Course, Lessons & Topics. We have also taken the time to style Memberpress courses to match our theme for styling.', 'buddyboss-pro' ); ?>
			</p>
			<?php
		} elseif (
			defined( 'BP_PLATFORM_VERSION' ) &&
			version_compare( BP_PLATFORM_VERSION, '2.7.40', '<' )
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
				printf(
					/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'Memberpress Courses', 'buddyboss-pro' ),
					esc_html__( 'Requires plugin to activate', 'buddyboss-pro' )
				);
				?>
				<div class="bbapp-tutorial-btn">
					<a class="button" target="_blank" href="
					<?php
					echo esc_url(
						bp_get_admin_url(
							add_query_arg(
								array(
									'page'    => 'bp-help',
									'article' => 127882,
								),
								'admin.php'
							)
						)
					);
					?>
					">
						<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
					</a>
				</div>
			</h2>
			<p>
				<?php
				echo esc_html__( 'BuddyBoss Platform Pro requires BuddyBoss Platform plugin version 2.7.40 or higher to work. Please update BuddyBoss Platform.', 'buddyboss-pro' );
				?>
			</p>
			<?php
		}
		?>
	</div>
</div>
