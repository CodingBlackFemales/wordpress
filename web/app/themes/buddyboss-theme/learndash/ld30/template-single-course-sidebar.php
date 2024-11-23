<?php
global $wpdb;
$is_enrolled         = false;
$current_user_id     = get_current_user_id();
$course_price        = learndash_get_course_meta_setting( $course_id, 'course_price' );
$course_price_type   = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
$course_button_url   = learndash_get_course_meta_setting( $course_id, 'custom_button_url' );
$paypal_settings     = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
$course_video_embed  = get_post_meta( $course_id, '_buddyboss_lms_course_video', true );
$course_certificate  = learndash_get_course_meta_setting( $course_id, 'certificate' );
$courses_progress    = buddyboss_theme()->learndash_helper()->get_courses_progress( $current_user_id );
$course_progress     = isset( $courses_progress[ $course_id ] ) ? $courses_progress[ $course_id ] : 0;
$course_progress_new = buddyboss_theme()->learndash_helper()->ld_get_progress_course_percentage( get_current_user_id(), $course_id );
$admin_enrolled      = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
$lesson_count        = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lesson_count        = array_column( $lesson_count, 'post' );
$course_pricing      = learndash_get_course_price( $course_id );
$has_access          = sfwd_lms_has_access( $course_id, $current_user_id );
$file_info           = pathinfo( $course_video_embed );

if ( buddyboss_theme_get_option( 'learndash_course_participants', null, true ) ) {
	$course_members_count = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_course_enrolled_users_list( $course_id );
	$members_arr          = learndash_get_users_for_course( $course_id, array( 'number' => 5 ), false );

	if ( ( $members_arr instanceof WP_User_Query ) && ( property_exists( $members_arr, 'results' ) ) && ( ! empty( $members_arr->results ) ) ) {
		$course_members = $members_arr->get_results();
	} else {
		$course_members = array();
	}
}

if ( '' !== trim( $course_video_embed ) ) {
	$thumb_mode = 'thumbnail-container-vid';
} else {
	$thumb_mode = 'thumbnail-container-img';
}

if ( sfwd_lms_has_access( $course->ID, $current_user_id ) ) {
	$is_enrolled = true;
} else {
	$is_enrolled = false;
}

$ld_product = null;
if ( class_exists( 'LearnDash\Core\Models\Product' ) ) {
	$ld_product = LearnDash\Core\Models\Product::find( $course_id );
}

$progress = learndash_course_progress(
	array(
		'user_id'   => $current_user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

if ( empty( $progress ) ) {
	$progress = array(
		'percentage' => 0,
		'completed'  => 0,
		'total'      => 0,
	);
}
$progress_status = ( 100 == $progress['percentage'] ) ? 'completed' : 'notcompleted';
if ( 0 < $progress['percentage'] && 100 !== $progress['percentage'] ) {
	$progress_status = 'progress';
}
?>

<div class="bb-single-course-sidebar bb-preview-wrap">
	<div class="bb-ld-sticky-sidebar">
		<div class="widget bb-enroll-widget">
			<div class="bb-enroll-widget flex-1 push-right">
				<div class="bb-course-preview-wrap bb-thumbnail-preview">
					<div class="bb-preview-course-link-wrap">
						<div class="thumbnail-container <?php echo esc_attr( $thumb_mode ); ?>">
							<div class="bb-course-video-overlay">
								<div>
									<span class="bb-course-play-btn-wrapper"><span class="bb-course-play-btn"></span></span>
									<div>
										<?php printf( __( 'Preview this %s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
									</div>
								</div>
							</div>
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail();
							}
							?>
						</div>
					</div>
				</div>
			</div>

			<div class="bb-course-preview-content">
				<?php if ( buddyboss_theme_get_option( 'learndash_course_participants', null, true ) && ! empty( $course_members ) ) { ?>
					<div class="bb-course-member-wrap flex align-items-center">
						<span class="bb-course-members">
						<?php
						$count = 0;
						foreach ( $course_members as $course_member ) :
							if ( $count > 2 ) {
								break;
							}
							?>
								<img class="round" src="<?php echo get_avatar_url( (int) $course_member, array( 'size' => 96 ) ); ?>" alt=""/>
							<?php
							$count ++;
						endforeach;
						?>
						</span>

						<?php
						if ( $course_members_count > 3 ) {
							?>
							<span class="members">
								<span class="members-count-g">
									<?php
										_e( '+', 'buddyboss-theme' );
										echo $course_members_count - 3;
									?>
								</span>
								<?php _e( 'enrolled', 'buddyboss-theme' ); ?>
							</span>
							<?php
						}
						?>
					</div>
				<?php } ?>

				<div class="bb-course-status-wrap">
					<?php
					do_action( 'learndash-course-infobar-status-cell-before', get_post_type(), $course_id, $current_user_id );

					if ( class_exists( 'LearnDash\Core\Models\Product' ) ) {

						if ( ! $has_access ) {
							$ld_seats_available      = $ld_product->get_seats_available();
							$ld_seats_available_text = ( ! empty( $ld_seats_available )
								? sprintf(
								// translators: placeholder: number of places remaining.
									_nx(
										'(%s place remaining)',
										'(%s places remaining)',
										$ld_seats_available,
										'placeholder: number of places remaining',
										'buddyboss-theme'
									),
									number_format_i18n( $ld_seats_available )
								)
								: '' );

							if ( $ld_product->has_ended() ) {
								$tooltips = sprintf(
								// translators: placeholder: course.
									esc_attr_x( 'This %s has ended', 'placeholder: course', 'buddyboss-theme' ),
									esc_html( learndash_get_custom_label_lower( 'course' ) )
								);

								echo '<div class="bb-course-status-content"><div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="' . $tooltips . '">' . __( 'Not Enrolled', 'buddyboss-theme' ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} elseif ( ! $ld_product->has_started() ) {

								if ( ! $ld_product->can_be_purchased() ) {
									$tooltips = sprintf(
									// translators: placeholder: course, course start date.
										esc_attr_x( 'This %1$s starts on %2$s', 'placeholder: course, course start date', 'buddyboss-theme' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) ),
										esc_html( learndash_adjust_date_time_display( $ld_product->get_start_date() ) )
									);
								} else {
									$tooltips = sprintf(
									// translators: placeholder: course, course start date.
										esc_attr_x( 'It is a pre-order. Enroll in this %1$s to get access after %2$s', 'placeholder: course', 'buddyboss-theme' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) ),
										esc_html( learndash_adjust_date_time_display( $ld_product->get_start_date() ) )
									);
								}

								echo '<div class="bb-course-status-content"><div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="' . $tooltips . '">' . __( 'Pre-order', 'buddyboss-theme' ) . ' ' . esc_html( $ld_seats_available_text ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {

								if ( $ld_product->can_be_purchased() ) {
									$tooltips = sprintf(
									// translators: placeholder: course.
										esc_attr_x( 'Enroll in this %s to get access', 'placeholder: course', 'buddyboss-theme' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) )
									);
								} else {
									$tooltips = sprintf(
									// translators: placeholder: course.
										esc_attr_x( 'This %s is not available', 'placeholder: course', 'buddyboss-theme' ),
										esc_html( learndash_get_custom_label_lower( 'course' ) )
									);
								}

								echo '<div class="bb-course-status-content"><div class="ld-status ld-status-incomplete ld-third-background" data-ld-tooltip="' . $tooltips . '">' . __( 'Not Enrolled', 'buddyboss-theme' ) . ' ' . esc_html( $ld_seats_available_text ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} else {
							?>
							<div class="bb-course-status-content">
								<?php learndash_status_bubble( $progress_status ); ?>
							</div>
							<?php
						}

					} else {

						if ( is_user_logged_in() && isset( $has_access ) && $has_access ) {
							?>
							<div class="bb-course-status-content">
								<?php learndash_status_bubble( $progress_status ); ?>
							</div>
							<?php
						} elseif ( 'open' !== $course_pricing['type'] ) {
							echo '<div class="bb-course-status-content"><div class="ld-status ld-status-incomplete ld-third-background">' . __( 'Not Enrolled', 'buddyboss-theme' ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					}

					do_action( 'learndash-course-infobar-status-cell-after', get_post_type(), $course_id, $current_user_id );
					?>
				</div>

				<div class="bb-button-wrap">
					<?php
					// Some simple price settings validation logic. Not 100%.
					$course_pricing = wp_parse_args(
						$course_pricing,
						array(
							'type'             => LEARNDASH_DEFAULT_COURSE_PRICE_TYPE,
							'price'            => '',
							'interval'         => '',
							'frequency'        => '',
							'trial_price'      => '',
							'trial_interval'   => '',
							'trial_frequency'  => '',
							'repeats'          => '',
							'repeat_frequency' => '',
						)
					);

					if ( 'subscribe' === $course_pricing['type'] ) {
						if ( ( empty( $course_pricing['price'] ) ) || ( empty( $course_pricing['interval'] ) ) || ( empty( $course_pricing['frequency'] ) ) ) {
							$course_pricing['type']             = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
							$course_pricing['interval']         = '';
							$course_pricing['frequency']        = '';
							$course_pricing['trial_price']      = '';
							$course_pricing['trial_interval']   = '';
							$course_pricing['trial_frequency']  = '';
							$course_pricing['repeats']          = '';
							$course_pricing['repeat_frequency'] = '';
						} else {
							if ( empty( $course_pricing['trial_price'] ) ) {
								$course_pricing['trial_interval']  = '';
								$course_pricing['trial_frequency'] = '';
							} elseif ( ( empty( $course_pricing['trial_interval'] ) ) || ( empty( $course_pricing['trial_frequency'] ) ) ) {
								$course_pricing['trial_price'] = '';
							}
						}
					}

					$resume_link = '';

					if ( empty( $course_progress ) && 100 > $course_progress ) {
						$btn_advance_class = 'btn-advance-start';
						$btn_advance_label = sprintf( __( 'Start %s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) );
						$resume_link       = buddyboss_theme()->learndash_helper()->boss_theme_course_resume( $course_id );
					} elseif ( 100 == $course_progress ) {
						$btn_advance_class = 'btn-advance-completed';
						$btn_advance_label = __( 'Completed', 'buddyboss-theme' );
					} else {
						$btn_advance_class = 'btn-advance-continue';
						$btn_advance_label = __( 'Continue', 'buddyboss-theme' );
						$resume_link       = buddyboss_theme()->learndash_helper()->boss_theme_course_resume( $course_id );
					}

					if ( 0 === learndash_get_course_steps_count( $course_id ) && false !== $is_enrolled ) {
						$btn_advance_class .= ' btn-advance-disable';
					}

					$login_model           = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
					$login_url             = apply_filters( 'learndash_login_url', ( $login_model === 'yes' ? '#login' : wp_login_url( get_the_permalink( $course_id ) ) ) );
					$learndash_login_modal = apply_filters( 'learndash_login_modal', true, $course_id, $user_id ) && ! is_user_logged_in() && 'open' !== $course_price_type;
					$learndash_login_modal = ( class_exists( 'LearnDash\Core\Models\Product' ) ) ? ( $learndash_login_modal && $ld_product->can_be_purchased() ) : $learndash_login_modal;

					if ( 'open' === $course_pricing['type'] || 'free' === $course_pricing['type'] ) {

						if ( $learndash_login_modal ) {
							echo '<div class="learndash_join_button ' . esc_attr( $btn_advance_class ) . '"><a href="' . esc_url( $login_url ) . '" class="btn-advance ld-primary-background">' . __( 'Login to Enroll', 'buddyboss-theme' ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							if ( 'free' === $course_pricing['type'] && false === $is_enrolled ) {
								echo '<div class="learndash_join_button ' . $btn_advance_class . '">' . learndash_payment_buttons( $course ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Payment button HTML
							} else {
								echo '<div class="learndash_join_button ' . esc_attr( $btn_advance_class ) . '"><a href="' . esc_url( $resume_link ) . '" class="btn-advance ld-primary-background">' . esc_html( $btn_advance_label ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}

						if ( 'open' === $course_pricing['type'] ) {
							echo '<span class="bb-course-type bb-course-type-open">' . __( 'Open Registration', 'buddyboss-theme' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<span class="bb-course-type bb-course-type-free">' . __( 'Free', 'buddyboss-theme' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					} elseif ( 'closed' === $course_pricing['type'] ) {

						$learndash_payment_buttons = learndash_payment_buttons( $course );

						if ( empty( $learndash_payment_buttons ) ) {

							if ( false === $is_enrolled ) {
								echo '<span class="ld-status ld-status-incomplete ld-third-background ld-text">' . __( 'This course is currently closed', 'buddyboss-theme' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								if ( ! empty( $course_pricing['price'] ) ) {
									echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							} else {
								echo '<div class="learndash_join_button ' . esc_attr( $btn_advance_class ) . '"><a href="' . esc_url( $resume_link ) . '" class="btn-advance ld-primary-background">' . esc_html( $btn_advance_label ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} else {
							echo '<div class="learndash_join_button btn-advance-continue">' . $learndash_payment_buttons . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( ! empty( $course_pricing['price'] ) ) {
								echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
					} elseif ( 'paynow' === $course_pricing['type'] || 'subscribe' === $course_pricing['type'] ) {
						if ( false === $is_enrolled ) {
							$meta                    = get_post_meta( $course_id, '_sfwd-courses', true );
							$course_pricing['type']  = @$meta['sfwd-courses_course_price_type'];
							$course_pricing['price'] = @$meta['sfwd-courses_course_price'];
							$course_no_of_cycles     = @$meta['sfwd-courses_course_no_of_cycles'];
							$course_pricing['price'] = @$meta['sfwd-courses_course_price'];
							$custom_button_url       = @$meta['sfwd-courses_custom_button_url'];
							$custom_button_label     = @$meta['sfwd-courses_custom_button_label'];

							if ( 'subscribe' === $course_pricing['type'] && '' === $course_pricing['price'] ) {

								if ( empty( $custom_button_label ) ) {
									$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
								} else {
									$button_text = esc_attr( $custom_button_label );
								}

								echo '<div class="learndash_join_button"><form method="post"><input type="hidden" value="' . $course->ID . '" name="course_id" /><input type="hidden" name="course_join" value="' . wp_create_nonce( 'course_join_' . get_current_user_id() . '_' . $course->ID ) . '" /><input type="submit" value="' . $button_text . '" class="btn-join" id="btn-join" /></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo '<div class="learndash_join_button">' . learndash_payment_buttons( $course ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} else {
							echo '<div class="learndash_join_button ' . $btn_advance_class . '"><a href="' . esc_url( $resume_link ) . '" class="btn-advance ld-primary-background">' . $btn_advance_label . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}

						if ( $learndash_login_modal ) {
							echo '<span class="ld-status">' . __( 'or ', 'buddyboss-theme' ) . '<a class="ld-login-text" href="' . esc_attr( $login_url ) . '">' . __( 'Login', 'buddyboss-theme' ) . '</a></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}

						if ( false === $is_enrolled ) {
							if ( 'paynow' === $course_pricing['type'] ) {
								if ( ! empty( $course_pricing['price'] ) ) {
									echo '<span class="bb-course-type bb-course-type-paynow">' . wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							} else {
								$course_price_billing_p3 = get_post_meta( $course_id, 'course_price_billing_p3', true );
								$course_price_billing_t3 = get_post_meta( $course_id, 'course_price_billing_t3', true );

								if ( $course_price_billing_t3 == 'D' ) {
									$course_price_billing_t3 = 'day(s)';
								} elseif ( $course_price_billing_t3 == 'W' ) {
									$course_price_billing_t3 = 'week(s)';
								} elseif ( $course_price_billing_t3 == 'M' ) {
									$course_price_billing_t3 = 'month(s)';
								} elseif ( $course_price_billing_t3 == 'Y' ) {
									$course_price_billing_t3 = 'year(s)';
								}

								$recurring = ( '' === $course_price_billing_p3 ) ? 0 : $course_price_billing_p3;

								$recurring_label = '<span class="bb-course-type bb-course-type-subscribe">';
								if ( '' === $course_pricing['price'] && 'subscribe' === $course_pricing['type'] ) {
									$recurring_label .= '<span class="bb-course-type bb-course-type-subscribe">' . __( 'Free', 'buddyboss-theme' ) . '</span>';
								} else {
									$recurring_label .= wp_kses_post( learndash_get_price_formatted( $course_pricing['price'] ) );
								}
								$recurring_label .= '<span class="course-bill-cycle"> / ' . $recurring . ' ' . $course_price_billing_t3 . '</span></span>';

								echo $recurring_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
					}
					?>
				</div>

				<?php
				$topics_count = 0;
				foreach ( $lesson_count as $lesson ) {
					$lesson_topics = learndash_get_topic_list( $lesson->ID );
					if ( $lesson_topics ) {
						$topics_count += sizeof( $lesson_topics );
					}
				}

				// course quizzes.
				$course_quizzes       = learndash_get_course_quiz_list( $course_id );
				$course_quizzes_count = sizeof( $course_quizzes );

				// lessons quizzes.
				if ( is_array( $lesson_count ) || is_object( $lesson_count ) ) {
					foreach ( $lesson_count as $lesson ) {
						$quizzes       = learndash_get_lesson_quiz_list( $lesson->ID, null, $course_id );
						$lesson_topics = learndash_topic_dots( $lesson->ID, false, 'array', null, $course_id );
						if ( $quizzes && ! empty( $quizzes ) ) {
							$course_quizzes_count += count( $quizzes );
						}
						if ( $lesson_topics && ! empty( $lesson_topics ) ) {
							foreach ( $lesson_topics as $topic ) {
								$quizzes = learndash_get_lesson_quiz_list( $topic, null, $course_id );
								if ( ! $quizzes || empty( $quizzes ) ) {
									continue;
								}
								$course_quizzes_count += count( $quizzes );
							}
						}
					}
				}

				if ( 0 < sizeof( $lesson_count ) || 0 < $topics_count || 0 < $course_quizzes_count || $course_certificate ) {
					$course_label = LearnDash_Custom_Label::get_label( 'course' );
					?>
					<div class="bb-course-volume">
					<h4><?php echo sprintf( esc_html__( '%s Includes', 'buddyboss-theme' ), $course_label ); ?></h4>
					<ul class="bb-course-volume-list">
						<?php if ( sizeof( $lesson_count ) > 0 ) { ?>
							<li>
								<i class="bb-icon-l bb-icon-book"></i><?php echo sizeof( $lesson_count ); ?> <?php echo sizeof( $lesson_count ) > 1 ? LearnDash_Custom_Label::get_label( 'lessons' ) : LearnDash_Custom_Label::get_label( 'lesson' ); ?>
							</li>
						<?php } ?>
						<?php if ( $topics_count > 0 ) { ?>
							<li>
								<i class="bb-icon-l bb-icon-text"></i><?php echo $topics_count; ?> <?php echo $topics_count != 1 ? LearnDash_Custom_Label::get_label( 'topics' ) : LearnDash_Custom_Label::get_label( 'topic' ); ?>
							</li>
						<?php } ?>
						<?php if ( $course_quizzes_count > 0 ) { ?>
							<li>
								<i class="bb-icon-rl bb-icon-question"></i><?php echo $course_quizzes_count; ?> <?php echo $course_quizzes_count != 1 ? LearnDash_Custom_Label::get_label( 'quizzes' ) : LearnDash_Custom_Label::get_label( 'quiz' ); ?>
							</li>
						<?php } ?>
						<?php if ( $course_certificate ) { ?>
							<li>
								<i class="bb-icon-l bb-icon-certificate"></i><?php echo sprintf( esc_html__( '%s Certificate', 'buddyboss-theme' ), $course_label ); ?>
							</li>
						<?php } ?>
					</ul>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		if ( is_active_sidebar( 'learndash_course_sidebar' ) ) {
			?>
			<ul class="ld-sidebar-widgets">
				<?php dynamic_sidebar( 'learndash_course_sidebar' ); ?>
			</ul>
			<?php
		}
		?>
	</div>
</div>

<div class="bb-modal bb_course_video_details mfp-hide">
	<?php
	if ( '' !== $course_video_embed ) {
		if ( wp_oembed_get( $course_video_embed ) ) {
			echo wp_oembed_get( $course_video_embed );
		} elseif ( isset( $file_info['extension'] ) && 'mp4' === $file_info['extension'] ) {
			?>
			<video width="100%" controls>
				<source src="<?php echo $course_video_embed; ?>" type="video/mp4">
				<?php _e( 'Your browser does not support HTML5 video.', 'buddyboss-theme' ); ?>
			</video>
			<?php
		} else {
			_e( 'Video format is not supported, use Youtube video or MP4 format.', 'buddyboss-theme' );
		}
	}
	?>
</div>
