<?php
/**
 * Reaction helper functions.
 *
 * @package BuddyBossPro
 * @since   2.4.50
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the reaction path.
 *
 * @since 2.4.50
 *
 * @param string $path path of reaction.
 *
 * @return string path.
 */
function bb_reaction_path( $path = '' ) {
	$bb_platform_pro = bb_platform_pro();

	return trailingslashit( $bb_platform_pro->reactions_dir ) . trim( $path, '/\\' );
}

/**
 * Return the reaction url.
 *
 * @since 2.4.50
 *
 * @param string $path url of reaction.
 *
 * @return string url.
 */
function bb_reaction_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->reactions_url ) . trim( $path, '/\\' );
}

/**
 * Get reaction posts and settings.
 *
 * @since 2.4.50
 *
 * @param string $mode      Reaction mode 'likes', 'emotions'. Default is 'likes'.
 * @param bool   $is_active Fetch all active reactions. Default is true.
 *
 * @return array Get list of saved reaction post.
 */
function bb_pro_get_reactions( $mode = 'likes', $is_active = true ) {

	if ( ! function_exists( 'bb_load_reaction' ) ) {
		return array();
	}

	return apply_filters( 'bb_pro_get_reactions', bb_load_reaction()->bb_get_reactions( $mode, $is_active ) );
}

/**
 * Include template for reaction > emotions field markup.
 *
 * @since 2.4.50
 *
 * @return void
 */
function bb_admin_setting_reaction_emotions_field_callback() {
	require bb_reaction_path( 'templates/admin/emotion-options.php' );
}

/**
 * Include template for reaction > emotions notice field markup.
 *
 * @since 2.4.50
 *
 * @param string $print Return type. Default true.
 *
 * @return void|string
 */
function bb_admin_setting_reaction_notice_field_callback( $print = true ) {
	static $html = '';

	if ( bb_pro_is_heartbeat() ) {
		return $html;
	}

	if ( empty( $html ) ) {
		ob_start();
		$status         = bb_pro_reaction_get_migration_status();
		$migration_data = bb_pro_reaction_get_migration_action();
		if (
			! empty( $migration_data ) &&
			! empty( $migration_data['action'] )
		) {

			if (
				! empty( $status ) &&
				'completed' === $status
			) {
				if ( 'like_to_emotions_action' === $migration_data['action'] ) {
					printf(
						'<div class="bb-pro-reaction-notice success"><p>%1$s</p><span class="close-reaction-notice"><i class="bb-icon-f bb-icon-times"></i></span></div>',
						sprintf(
							__( '%1$s were successfully converted to the %2$s emotion.', 'buddyboss-pro' ),
							sprintf(
								'<strong>' . bp_core_number_format( $migration_data['total_reactions'] ) . ' %s</strong>',
								$migration_data['from_emotions_name']
							),
							'<strong>' . $migration_data['to_emotions_name'] . '</strong>' // @todo Update emotion name.
						)
					);
				} elseif ( 'emotions_to_like_action' === $migration_data['action'] ) {
					printf(
						'<div class="bb-pro-reaction-notice success"><p>%1$s</p><span class="close-reaction-notice"><i class="bb-icon-f bb-icon-times"></i></span></div>',
						sprintf(
							__( '%1$s <strong>reactions</strong> were successfully converted %2$s.', 'buddyboss-pro' ),
							'<strong>' . bp_core_number_format( $migration_data['total_reactions'] ) . '</strong>',
							'<strong>' . $migration_data['to_emotions_name'] . '</strong>'
						)
					);
				} elseif ( in_array( $migration_data['action'], array( 'emotions', 'likes' ), true ) ) {
					printf(
						'<div class="bb-pro-reaction-notice success"><p>%1$s</p><span class="close-reaction-notice"><i class="bb-icon-f bb-icon-times"></i></span></div>',
						sprintf(
							__( '%1$s <strong>reactions</strong> were successfully converted %2$s.', 'buddyboss-pro' ),
							'<strong>' . bp_core_number_format( $migration_data['total_reactions'] ) . '</strong>',
							'<strong>' . $migration_data['to_emotions_name'] . '</strong>'
						)
					);
				}

				// Delete transient to avoid display notice again if page is reactions settings tab.
				$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
				$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
				if ( is_admin() && 'bp-settings' === $page && 'bp-reactions' === $tab ) {
					bp_delete_option( 'bb_pro_reaction_migration_completed' );
					bb_pro_reaction_delete_migration();
				}
			} elseif (
				! empty( $status ) &&
				'inprogress' === $status
			) {
				$total            = (int) $migration_data['total_reactions'];
				$updated_emotions = (int) ( $migration_data['updated_emotions'] ?? 0 );
				$percentage       = ! empty( $total ) ? ceil( ( $updated_emotions * 100 ) / $total ) : 0;

				printf(
					'<div class="bb-pro-reaction-notice loading">
					<div class="bb-pro-reaction-notice_content">
						<p><strong>%1$s</strong></p>
						<p>%2$s</p>
					</div>
					<div class="bb-pro-reaction-notice_actions">
						<a href="#" class="button button-outline recheck-status">%3$s</a>
						<a href="#" class="button-text stop-migration">%4$s</a>
					</div>
				</div>',
					// @todo counts based on migration.
					sprintf(
						__( '%1$s out of %2$s %3$s reactions have been converted', 'buddyboss-pro' ),
						bp_core_number_format( $updated_emotions ),
						bp_core_number_format( $total ),
						'(' . $percentage . '%)'
					),
					__( 'This action is being performed in the background, but may take some time based on the amount of data.', 'buddyboss-pro' ),
					__( 'Recheck status', 'buddyboss-pro' ),
					__( 'Stop', 'buddyboss-pro' )
				);
			} else {
				// Check the current user set 'Do Later', then hide it.
				$hide_for = ! empty( $migration_data['hide_for'] ) ? $migration_data['hide_for'] : array();
				if (
					in_array( $migration_data['action'], array( 'like_to_emotions_action', 'emotions_to_like_action' ), true ) &&
					! in_array( get_current_user_id(), $hide_for, true )
				) {
					$migration_notice = '';
					if ( 'like_to_emotions_action' === $migration_data['action'] ) {
						$migration_notice = sprintf(
							'<p><strong>%1$s</strong></p><p>%2$s</p>',
							__( 'Do you want to convert your existing Likes to an Emotion?', 'buddyboss-pro' ),
							sprintf(
								__( 'You have %s Likes previously submitted on your site which can be converted to an Emotion.', 'buddyboss-pro' ),
								'<span class="reaction-notice-count">' . bp_core_number_format( $migration_data['total_reactions'] ) . '</span>'
							)
						);
					} elseif ( 'emotions_to_like_action' === $migration_data['action'] ) {
						$migration_notice = sprintf(
							'<p><strong>%1$s</strong></p><p>%2$s</p>',
							__( 'Do you want to convert your existing reactions to Likes?', 'buddyboss-pro' ),
							sprintf(
								__( 'You have %s reactions previously submitted on your site which can be converted to Likes.', 'buddyboss-pro' ),
								'<span class="reaction-notice-count">' . bp_core_number_format( $migration_data['total_reactions'] ) . '</span>'
							)
						);
					}

					if ( ! empty( $migration_notice ) ) {
						printf(
							'<div class="bb-pro-reaction-notice info" id="bb-pro-reaction-migration-exists-notice">
							<div class="bb-pro-reaction-notice_content">%1$s</div>
							<div class="bb-pro-reaction-notice_actions">
								<a href="#" class="button-primary reaction-start-conversion">%2$s</a>
								<a href="#" class="button button-outline reaction-do-later">%3$s</a>
							</div>
						</div>',
							$migration_notice,
							__( 'Start conversion', 'buddyboss-pro' ),
							__( 'Do later', 'buddyboss-pro' )
						);
					}
				}
			}
		}

		$html = ob_get_contents();
		ob_end_clean();
	}

	if ( $print ) {
		echo $html;
	} else {
		return $html;
	}
}

/**
 * Return reaction icon types.
 *
 * @since 2.4.50
 *
 * @return array
 */
function bb_reactions_icon_types() {

	return apply_filters(
		'bb_reactions_icon_types',
		array(
			array(
				'label' => esc_html__( 'Emojis', 'buddyboss-pro' ),
				'value' => 'emotions',
			),
			array(
				'label' => esc_html__( 'Icons', 'buddyboss-pro' ),
				'value' => 'bb-icons',
			),
			array(
				'label' => esc_html__( 'Custom', 'buddyboss-pro' ),
				'value' => 'custom',
			),
		)
	);
}

/**
 * Return reaction icons for given icon type.
 *
 * @since 2.4.50
 *
 * @param string $type Type of reaction icon.
 *
 * @return array Array of reaction icons
 */
function bb_get_default_reaction_icons( $type = 'bb-icons' ) {

	$bb_emotion = BB_Reactions_Picker::instance();
	$icons      = array();

	switch ( $type ) {
		case 'emotions':
			$icons = $bb_emotion->bb_get_emojis_list();
			break;
		case 'bb-icons':
			$icons = $bb_emotion->bb_icon_font_map( 'glyphs' );
			break;
		case 'custom':
			$icons = $bb_emotion->bb_custom_icon_list();
			break;
	}

	return apply_filters( 'bb_get_default_reaction_icons', $icons, $type );
}

/**
 * Return default reaction data.
 *
 * @since 2.4.50
 *
 * @return array
 */
function bb_pro_get_reaction_default_data() {

	return apply_filters(
		'bb_pro_get_reaction_default_data',
		array(
			array(
				'name'              => 'thumbs-up',
				'icon'              => 'thumbs-up',
				'type'              => 'bb-icons',
				'icon_text'         => 'Like',
				'icon_color'        => '#3379f6',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => '',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
			array(
				'name'              => 'red heart',
				'icon'              => '2764-fe0f',
				'type'              => 'emotions',
				'icon_text'         => 'Love',
				'icon_color'        => '#000000',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/2764.svg',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
			array(
				'name'              => 'face with tears of joy',
				'icon'              => '1f602',
				'type'              => 'emotions',
				'icon_text'         => 'Laugh',
				'icon_color'        => '#000000',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f602.svg',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
			array(
				'name'              => 'angry face',
				'icon'              => '1f620',
				'type'              => 'emotions',
				'icon_text'         => 'Angry',
				'icon_color'        => '#000000',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f620.svg',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
			array(
				'name'              => 'sad but relieved face',
				'icon'              => '1f625',
				'type'              => 'emotions',
				'icon_text'         => 'Sad',
				'icon_color'        => '#000000',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f625.svg',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
			array(
				'name'              => 'face with open mouth',
				'icon'              => '1f62e',
				'type'              => 'emotions',
				'icon_text'         => 'Wow',
				'icon_color'        => '#000000',
				'text_color'        => '#000000',
				'notification_text' => '',
				'icon_path'         => 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f62e.svg',
				'mode'              => 'emotions',
				'is_emotion_active' => true,
			),
		)
	);
}

/**
 * Function to prepare the screen when no emotion data exists for migration.
 *
 * @since 2.4.50
 *
 * @param string $reaction_mode The mode of reaction.
 *
 * @return string
 */
function bb_pro_reaction_get_no_data_screen( $reaction_mode ) {
	ob_start();
	?>
	<p class="text-center">
		<svg xmlns="http://www.w3.org/2000/svg" width="66" height="64" viewBox="0 0 66 64" fill="none">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M43.4661 53.1942H56.9998C60.9763 53.1942 64.1998 49.9706 64.1998 45.9942V9.20098C64.1998 5.22453 60.9763 2.00098 56.9998 2.00098H8.9998C5.02335 2.00098 1.7998 5.22453 1.7998 9.20098V45.9942C1.7998 49.9706 5.02335 53.1942 8.9998 53.1942H22.5955L30.0273 60.7548C31.6297 62.3849 34.2634 62.4204 35.9099 60.834C35.934 60.8108 35.9578 60.7873 35.9813 60.7635L43.4661 53.1942Z" fill="#F0F6FC" stroke="#2271B1" stroke-width="2.34" stroke-linecap="round" stroke-linejoin="round"/>
			<path fill-rule="evenodd" clip-rule="evenodd" d="M26.8311 22.4145L36.3307 12.4524C36.9508 11.8021 37.9536 11.7043 38.6877 12.2223L38.8125 12.3104C39.662 12.9099 40.0683 13.961 39.843 14.976L38.3013 21.9219C38.2009 22.3744 38.4863 22.8226 38.9387 22.923C38.9984 22.9362 39.0594 22.9429 39.1206 22.9429H44.984C46.9861 22.9429 48.6092 24.566 48.6092 26.5681C48.6092 26.7306 48.5982 26.893 48.5764 27.054L47.2341 36.9784C46.9909 38.7764 45.4559 40.1177 43.6416 40.1177C42.1422 40.1177 40.6427 40.1177 39.1433 40.1177H30.1413C28.2874 40.1177 26.7846 38.6148 26.7846 36.761V22.4585L26.8311 22.4145ZM19.9169 22.4145H24.6938V37.5121C24.6938 38.9025 23.5667 40.0296 22.1764 40.0296H19.9169C18.5265 40.0296 17.3994 38.9025 17.3994 37.5121V24.932C17.3994 23.5416 18.5265 22.4145 19.9169 22.4145Z" fill="#2271B1"/>
		</svg>
	</p>
	<?php
	echo sprintf(
		'<h3 class="text-center">%s</h3>',
		__( 'You have no reactions to convert', 'buddyboss-pro' )
	);

	if ( 'emotions' === $reaction_mode ) {
		echo sprintf(
			'<p class="text-center">%s</p>',
			__( 'Unable to find any existing reactions to convert to an Emotion.', 'buddyboss-pro' )
		);
	} elseif ( 'likes' === $reaction_mode ) {
		echo sprintf(
			'<p class="text-center">%s</p>',
			__( 'Unable to find any existing reactions to convert to Likes.', 'buddyboss-pro' )
		);
	}

	return ob_get_clean();
}

/**
 * Function to return the migration wizard.
 *
 * @sicne 2.4.50
 *
 * @param array $args Array of reaction actions.
 *
 * @return array Array of reaction migration screens HTML.
 */
function bb_pro_reaction_get_migration_wizard( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'action_type'    => '',
			'reaction_mode'  => bb_get_reaction_mode(),
		)
	);

	// Prepare likes.
	$likes          = current( bb_pro_get_reactions() );
	$likes['count'] = bb_load_reaction()->bb_get_user_reactions_count( array( 'reaction_id' => $likes['id'] ) );

	// Prepare emotions.
	$emotions            = bb_pro_get_reactions( 'emotions', false );
	$total_emotion_count = 0;

	if ( ! empty( $emotions ) ) {
		foreach ( $emotions as $reaction_key => $reaction ) {
			// Fetch total count.
			$total_count                        = bb_load_reaction()->bb_get_user_reactions_count( array( 'reaction_id' => $reaction['id'] ) );
			$emotions[ $reaction_key ]['count'] = $total_count;

			$total_emotion_count = $total_emotion_count + $total_count;
		}
	}

	$total_reaction_count = (int) $total_emotion_count + $likes['count'];

	switch ( $r['action_type'] ) {
		case 'footer_wizard':

			if ( 0 < $total_reaction_count && 'emotions' === $r['reaction_mode'] ) {
				ob_start();
				?>
				<p><?php echo __( 'This action will convert reactions previously submitted by members on your site to an Emotion of your choice. Reactions not selected can be converted at any point in the future using this migration wizard.', 'buddyboss-pro' ); ?></p>
				<p><strong><?php echo __( 'Which reactions do you want to convert?', 'buddyboss-pro' ); ?></strong></p>
				<div class="migration_emotion_list">
					<?php
					if ( 0 < (int) $likes['count'] ) {
						?>
						<p>
							<label for="migrate_like_reaction">
								<input type="checkbox" class="migrate_single_emotion_input" name="from_reactions[]" id="migrate_like_reaction" value="<?php echo esc_attr( $likes['id'] ); ?>" data-count="<?php echo esc_attr( $likes['count'] ); ?>">
								<?php echo __( 'Likes', 'buddyboss-pro' ); ?> (<?php echo esc_html( bp_core_number_format( $likes['count'] ) ); ?>)
							</label>
						</p>
						<?php
					}
					?>
					<p>
						<label for="migrate_all_emotions">
							<input type="checkbox" class="migrate_emotion_input" name="from_all_emotions" id="migrate_all_emotions">
							<?php echo __( 'Emotions', 'buddyboss-pro' ); ?> (<?php echo esc_html( bp_core_number_format( $total_emotion_count ) ); ?>)
						</label>
					</p>
					<ul>
						<?php
						if ( ! empty( $emotions ) ) {
							foreach ( $emotions as $reaction ) {
								echo sprintf(
									'<li><label for="emotion_%1$s"><input type="checkbox" class="migrate_emotion_input migrate_single_emotion_input" name="from_reactions[]" id="emotion_%1$s" value="%1$s" data-count="%3$s"><span>%2$s (%4$s)</span></label></li>',
									esc_attr( $reaction['id'] ),
									esc_html( $reaction['icon_text'] ),
									$reaction['count'],
									bp_core_number_format( $reaction['count'] )
								);
							}
						}
						?>
					</ul>
				</div>

				<p><strong><?php echo __( 'Which Emotion would you like to convert your reactions to?', 'buddyboss-pro' ); ?></strong></p>
				<select name="to_reactions" id="migration_emotion_select">
					<?php
					if ( ! empty( $emotions ) ) {
						foreach ( $emotions as $emotion ) {
							echo sprintf(
								'<option value="%1$s">%2$s</option>',
								esc_attr( $emotion['id'] ),
								esc_html( $emotion['icon_text'] )
							);
						}
					}
					?>
				</select>
				<?php
				$wizard_screen1 = ob_get_clean();

				ob_start();
				?>
				<p>
					<?php
					echo sprintf(
						__( 'You are about to convert %1$s to the %2$s Emotion', 'buddyboss-pro' ),
						sprintf(
							'<strong><span class="from-reactions-count"></span> %s</strong>',
							__( 'reactions', 'buddyboss-pro' )
						),
						'<strong><span class="to-reactions-label"></span></strong>'
					);
					?>
				</p>
				<ul>
					<li><?php echo __( 'The new reactions will be immediately visible on your site after being converted', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'Depending on the amount of data to convert, the migration may take a while', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'You will be unable to edit reactions while the conversion is in progress', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'This action cannot be undone, but you can convert reactions to another reaction in the future', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'We recommend backing up your site before migrating and performing this action during an off-peak period', 'buddyboss-pro' ); ?></li>
				</ul>
				<p><?php echo __( 'Do you want to start the conversion now?', 'buddyboss-pro' ); ?></p>
				<?php
				$wizard_screen2 = ob_get_clean();

			} elseif ( 0 < $total_emotion_count && 'likes' === $r['reaction_mode'] ) {
				ob_start();
				?>
				<p><?php echo __( 'This action will convert reactions previously submitted by members on your site to Likes. Reactions not selected can be converted at any point in the future using this migration wizard.', 'buddyboss-pro' ); ?></p>
				<p><strong><?php echo __( 'Which reactions do you want to convert to Likes?', 'buddyboss-pro' ); ?></strong></p>
				<div class="migration_emotion_list">
					<p>
						<label for="migrate_all_emotions">
							<input type="checkbox" class="migrate_emotion_input" name="from_all_emotions" id="migrate_all_emotions">
							<?php echo __( 'All emotions', 'buddyboss-pro' ); ?> (<?php echo esc_html( bp_core_number_format( $total_emotion_count ) ); ?>)
						</label>
					</p>
					<ul>
						<?php
						if ( ! empty( $emotions ) ) {
							foreach ( $emotions as $reaction ) {
								echo sprintf(
									'<li><label for="emotion_%1$s"><input type="checkbox" class="migrate_emotion_input migrate_single_emotion_input" name="from_reactions[]" id="emotion_%1$s" value="%1$s" data-count="%3$s"><span>%2$s (%4$s)</span></label></li>',
									esc_attr( $reaction['id'] ),
									esc_html( $reaction['icon_text'] ),
									$reaction['count'],
									bp_core_number_format( $reaction['count'] )
								);
							}
						}
						?>
					</ul>
				</div>
				<?php
				$wizard_screen1 = ob_get_clean();

				ob_start();
				?>
				<p>
					<?php
					echo sprintf(
						__( 'You are about to convert %1$s to the %2$s Likes', 'buddyboss-pro' ),
						sprintf(
							'<strong><span class="from-reactions-count"></span> %s</strong>',
							__( 'reactions', 'buddyboss-pro' )
						),
						'<strong><span class="to-reactions-label"></span></strong>'
					);
					?>
				</p>
				<ul>
					<li><?php echo __( 'The new Likes will be immediately visible on your site after being converted', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'Depending on the amount of data to convert, the migration may take a while', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'You will be unable to edit reactions while the conversion is in progress', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'This action cannot be undone, but you can convert Likes back to other reactions in the future', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'We recommend backing up your site before migrating and performing this action during an off-peak period', 'buddyboss-pro' ); ?></li>
				</ul>
				<p><?php echo __( 'Do you want to start the conversion now?', 'buddyboss-pro' ); ?></p>
				<?php
				$wizard_screen2 = ob_get_clean();

			} else {
				$wizard_screen1 = bb_pro_reaction_get_no_data_screen( $r['reaction_mode'] );
				$wizard_screen2 = '';
			}
			break;

		case 'switch_wizard':
			if ( 'emotions' === $r['reaction_mode'] ) {
				ob_start();
				?>
				<p><?php echo __( 'This action will convert reactions previously submitted by members on your site to Likes. Reactions not selected can be converted at any point in the future using the migration wizard.', 'buddyboss-pro' ); ?></p>
				<p><strong><?php echo __( 'Which reactions do you want to convert to Likes?', 'buddyboss-pro' ); ?></strong></p>
				<div class="migration_emotion_list">
					<p>
						<label for="migrate_all_emotions">
							<input type="checkbox" class="migrate_emotion_input" name="from_all_emotions" id="migrate_all_emotions">
							<?php echo __( 'All emotions', 'buddyboss-pro' ); ?> (<?php echo esc_html( bp_core_number_format( $total_emotion_count ) ); ?>)
						</label>
					</p>
					<ul>
						<?php
						if ( ! empty( $emotions ) ) {
							foreach ( $emotions as $reaction ) {
								echo sprintf(
									'<li><label for="emotion_%1$s"><input type="checkbox" class="migrate_emotion_input migrate_single_emotion_input" name="from_reactions[]" id="emotion_%1$s" value="%1$s" data-count="%3$s"><span>%2$s (%4$s)</span></label></li>',
									esc_attr( $reaction['id'] ),
									esc_html( $reaction['icon_text'] ),
									$reaction['count'],
									bp_core_number_format( $reaction['count'] )
								);
							}
						}
						?>
					</ul>
				</div>
				<?php
				$wizard_screen1 = ob_get_clean();

				ob_start();
				?>
				<p>
					<?php
					echo sprintf(
						__( 'You are about to convert %1$s to %2$s', 'buddyboss-pro' ),
						sprintf(
							'<strong><span class="from-reactions-count"></span> %s</strong>',
							__( 'reactions', 'buddyboss-pro' )
						),
						sprintf(
							'<strong>%s</strong>',
							__( 'Likes', 'buddyboss-pro' )
						)
					);
					?>
				</p>
				<ul>
					<li><?php echo __( 'The new Likes will be immediately visible on your site after being converted', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'Depending on the amount of data to convert, the migration may take a while', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'You will be unable to edit reactions while the conversion is in progress', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'This action cannot be undone, but you can convert Likes back to other reactions in the future', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'We recommend backing up your site before migrating and performing this action during an off-peak period', 'buddyboss-pro' ); ?></li>
				</ul>
				<p><?php echo __( 'Do you want to start the conversion now?', 'buddyboss-pro' ); ?></p>
				<?php
				$wizard_screen2 = ob_get_clean();
			} else {
				ob_start();
				?>
				<p>
					<?php
					echo sprintf(
					/* translators: Emotion name with count. */
						__( 'This action will convert the %s previously submitted by members on your site to an Emotion of your choice. You can preform this action at any point in the future using this migration wizard.', 'buddyboss-pro' ),
						sprintf(
							'<strong>%1$s %2$s</strong>',
							bp_core_number_format( $likes['count'] ),
							__( 'Likes', 'buddyboss-pro' )
						)
					);
					?>
				</p>

				<p><?php echo __( 'Which Emotion would you like to convert your Likes to?', 'buddyboss-pro' ); ?></p>
				<select name="to_reactions" id="migration_emotion_select">
					<option value=""><?php echo __( 'Select an Emotion', 'buddyboss-pro' ); ?></option>
					<?php
					if ( ! empty( $emotions ) ) {
						foreach ( $emotions as $emotion ) {
							echo sprintf(
								'<option value="%1$s">%2$s</option>',
								esc_attr( $emotion['id'] ),
								esc_html( $emotion['icon_text'] )
							);
						}
					}
					?>
				</select>
				<?php
				$wizard_screen1 = ob_get_clean();

				ob_start();
				?>
				<p>
					<?php
					echo sprintf(
					/* translators: 1: Emotion name with count, 2: Convert to emotion name. */
						__( 'You are about to convert %1$s to the %2$s Emotion', 'buddyboss-pro' ),
						sprintf(
							'<strong>%1$s %2$s</strong>',
							bp_core_number_format( $likes['count'] ),
							__( 'Likes', 'buddyboss-pro' )
						),
						'<strong><span class="to-reactions-label"></span></strong>'
					);
					?>
				</p>
				<ul>
					<li><?php echo __( 'The new reactions will be immediately visible on your site after being converted', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'Depending on the amount of data to convert, the migration may take a while', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'You will be unable to edit reactions while the conversion is in progress', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'This action cannot be undone, but you can convert reactions back to to Likes in the future', 'buddyboss-pro' ); ?></li>
					<li><?php echo __( 'We recommend backing up your site before migrating and performing this action during an off-peak period', 'buddyboss-pro' ); ?></li>
				</ul>
				<p><?php echo __( 'Do you want to start the conversion now?', 'buddyboss-pro' ); ?></p>
				<?php
				$wizard_screen2 = ob_get_clean();
			}
			break;

		default:
			$wizard_screen1 = '';
			$wizard_screen2 = '';
	}

	return array(
		'wizard_screen1' => $wizard_screen1,
		'wizard_screen2' => $wizard_screen2,
	);
}

/**
 * Register a new background job.
 *
 * @sicne 2.4.50
 *
 * @param string|int|array $migrate_from_ids Migrate from IDs with comma seperated.
 * @param string|int       $migrate_to_id    Migrate to ID.
 * @param string           $job_from         Where is coming from this job.
 * @param string           $job_type         Job type.
 *
 * @return void
 */
function bb_pro_reaction_dispatch_migration( $migrate_from_ids, $migrate_to_id, $job_from = 'settings_change', $job_type = 'dispatch' ) {
	global $bb_reaction_background_process;

	if ( 'dispatch' !== $job_type && $bb_reaction_background_process->is_cancelled() ) {
		return;
	}

	$background_args = array(
		'type'     => 'migrate_reactions',
		'group'    => 'bb_pro_migrate_reactions',
		'callback' => 'bb_pro_migrate_reactions_callback',
		'args'     => array( $migrate_from_ids, $migrate_to_id, $job_from ),
		'priority' => 5,
	);

	// Set variable to show notice after completed the migration.
	$is_reaction_migration = true;

	// When found delete action, then set background args.
	if ( 'delete' === $migrate_to_id ) {
		$background_args['args']              = array( $migrate_from_ids, 'delete', $job_from );
		$background_args['priority']          = 10;
		$background_args['secondary_data_id'] = 'delete';

		// Set variable to do not show notice after completed the migration for delete the reactions.
		$is_reaction_migration = false;
	}

	if ( 'dispatch' === $job_type ) {
		bp_update_option( 'is_reaction_migration', $is_reaction_migration );
		$bb_reaction_background_process->data( $background_args )->save()->dispatch();
	} else {
		$bb_reaction_background_process->data( $background_args )->save()->schedule_event();
	}
}

/**
 * Migration callback function.
 *
 * @sicne 2.4.50
 *
 * @param string|int|array $migrate_from_ids Migrate from IDs with comma seperated.
 * @param string|int       $migrate_to_id    Migrate to ID.
 * @param string           $job_from         Where is coming from this job.
 *
 * @return void
 */
function bb_pro_migrate_reactions_callback( $migrate_from_ids, $migrate_to_id, $job_from ) {
	global $wpdb, $bb_reaction_background_process;

	$migrate_from_ids = wp_parse_id_list( $migrate_from_ids );
	$migrate_to_id    = ( 'delete' === $migrate_to_id ) ? $migrate_to_id : current( wp_parse_id_list( $migrate_to_id ) );

	$from_ids_in    = implode( ',', wp_parse_id_list( $migrate_from_ids ) );
	$migration_data = bb_pro_reaction_get_migration_action();

	$limit                = (int) apply_filters( 'bb_pro_migrate_reactions_limit', 200 );
	$bb_user_reaction_tbl = bb_load_reaction()::$user_reaction_table;
	$results              = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, user_id, reaction_id, item_type, item_id FROM {$bb_user_reaction_tbl} WHERE `reaction_id` IN ({$from_ids_in}) order by item_id, item_type LIMIT %d",
			$limit
		),
		ARRAY_A
	);

	if ( empty( $results ) ) {
		$migration_data['updated_emotions'] = 0;
		bb_pro_reaction_update_migration_action( $migration_data );
		return;
	}

	$reaction_index_ids = array_column( $results, 'id' );
	$updated_index = implode( ',', wp_parse_id_list( $reaction_index_ids ) );
	if ( 'delete' !== $migrate_to_id ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$bb_user_reaction_tbl} SET `reaction_id` = %d WHERE `reaction_id` IN ({$from_ids_in}) AND id IN ({$updated_index})",
				$migrate_to_id
			)
		);

		bp_core_reset_incrementor( 'bb_reactions' );
		if ( ! empty( $reaction_index_ids ) ) {
			foreach ( $reaction_index_ids as $user_reaction_id ) {
				wp_cache_delete( $user_reaction_id, 'bb_reactions' );
			}
		}
	} else {
		$wpdb->query( "DELETE from {$bb_user_reaction_tbl} WHERE `id` IN ({$updated_index})" );

		bp_core_reset_incrementor( 'bb_reactions' );
		if ( ! empty( $reaction_index_ids ) ) {
			foreach ( $reaction_index_ids as $user_reaction_id ) {
				wp_cache_delete( $user_reaction_id, 'bb_reactions' );
			}
		}
	}

	$desired_keys = array( 'reaction_id', 'item_type', 'item_id' );

	$old_data = array();
	$new_data = array();

	// Prepare reaction summary data.
	foreach ( $results as $reaction ) {
		$reaction = array_intersect_key( $reaction, array_flip( $desired_keys ) );
		array_push( $old_data, $reaction );
		if ( 'delete' !== $migrate_to_id ) {
			$reaction['reaction_id'] = $migrate_to_id;
			array_push( $new_data, $reaction );
		}
	}

	if ( ! empty( $old_data ) ) {
		$old_data = array_unique( $old_data, SORT_REGULAR );
		foreach ( $old_data as $reaction ) {
			bb_load_reaction()->bb_prepare_reaction_summary_data( $reaction );
		}
	}

	if ( ! empty( $new_data ) ) {
		$new_data = array_unique( $new_data, SORT_REGULAR );
		foreach ( $new_data as $reaction ) {
			bb_load_reaction()->bb_prepare_reaction_summary_data( $reaction );
		}
	}

	if ( $bb_reaction_background_process->is_cancelled() ) {
		return;
	}

	// Update migration data.
	$migration_data['updated_emotions'] = ( $migration_data['updated_emotions'] ?? 0 ) + count( $results );
	bb_pro_reaction_update_migration_action( $migration_data );

	// If a total background job is more than 5, then don't create a new background job.
	$total_bg_jobs = $bb_reaction_background_process->get_job_count_by_type_group( 'migrate_reactions', 'bb_pro_migrate_reactions' );
	if ( $total_bg_jobs > 5 ) {
		return;
	}

	$is_reaction_migration = (bool) bp_get_option( 'is_reaction_migration' );

	if ( 'delete' === $migrate_to_id ) {
		// Register a new job abd schedule it.
		bb_pro_reaction_dispatch_migration( $migrate_from_ids, $migrate_to_id, $job_from, 'schedule' );
	} elseif ( $is_reaction_migration ) {
		// Register a new job abd schedule it.
		bb_pro_reaction_dispatch_migration( $migrate_from_ids, $migrate_to_id, $job_from, 'schedule' );
	}
}

/**
 * Function to return migration status.
 *
 * @sicne 2.4.50
 *
 * @return string
 */
function bb_pro_reaction_get_migration_status() {
	static $cache = null;

	if ( null === $cache ) {
		$cache = '';
		$reaction_process = bp_get_option( 'bb_pro_reaction_migration_completed' );
		if (
			! empty( $reaction_process ) &&
			! empty( $reaction_process['success'] ) &&
			$reaction_process['success'] === 'yes'
		) {
			$current_time = time();
			$expire_time  = isset( $reaction_process['expire_time'] ) ? $reaction_process['expire_time'] : 0;
			if (
				! empty( $expire_time ) &&
				$current_time < $expire_time
			) {
				$cache = 'completed';
			} else {
				bp_delete_option( 'bb_pro_reaction_migration_completed' );
			}
		} else {
			global $bb_reaction_background_process;
			if (
				is_object( $bb_reaction_background_process ) &&
				$bb_reaction_background_process->is_inprocess(
					array(
						'not_secondary_data_id' => 'delete'
					)
				) &&
				true === (bool) bp_get_option( 'is_reaction_migration' )
			) {
				$cache = 'inprogress';
			}
		}
	}

	return $cache;
}

/**
 * Function to add migration action.
 *
 * @sicne 2.4.50
 *
 * @param array  $args Data of migration.
 *
 * @return void
 */
function bb_pro_reaction_add_migration_action( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'action'           => '',
			'type'             => 'switch',
			'total_reactions'  => 0,
			'updated_emotions' => 0,
			'hide_for'         => array(),
			'status'           => '',
		)
	);

	if ( ! empty( $r['action'] ) ) {
		bb_pro_reaction_update_migration_action( $r );
	}
}

/**
 * Function to add migration action.
 *
 * @sicne 2.4.50
 *
 * @param array  $args Data of migration.
 *
 * @return void
 */
function bb_pro_reaction_update_migration_action( $args = array() ) {
	if ( ! empty( $args ) ) {

		// Get all migration data.
		$existing_action = bb_pro_reaction_get_migration_action();
		if (
			! empty( $existing_action ) &&
			! empty( $existing_action['action'] )
		) {

			// Merge hide_for key.
			if ( ! empty( $args['hide_for'] ) ) {
				$args['hide_for'] = array_unique( array_merge( $args['hide_for'], $existing_action['hide_for'] ) );
			}

			// Assign updated data.
			$existing_action = bp_parse_args( $args, $existing_action );
		} else {
			$existing_action = $args;
		}

		// Update the data.
		bp_update_option( 'bb_pro_reaction_migration', $existing_action );
	}
}

/**
 * Function to get migration action.
 *
 * @sicne 2.4.50
 *
 * @return array
 */
function bb_pro_reaction_get_migration_action() {
	return bp_get_option( 'bb_pro_reaction_migration', array() );
}

/**
 * Function to delete a migration option.
 *
 * @sicne 2.4.50
 *
 * @return void
 */
function bb_pro_reaction_delete_migration() {
	bp_delete_option( 'bb_pro_reaction_migration' );
}
