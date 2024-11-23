<?php
/**
 * Template part for displaying LearnDash Course Grid [ld_course_list]
 *
 * @link    https://www.learndash.com/add-on/course-grid/
 *
 * @package BuddyBoss_Theme
 */

$col   = empty( $shortcode_atts['col'] ) ? LEARNDASH_COURSE_GRID_COLUMNS : intval( $shortcode_atts['col'] );
$col   = $col > 6 ? 6 : $col;
$smcol = 1 === $col ? 1 : $col / 2;
$col   = 12 / $col;
$smcol = intval( ceil( 12 / $smcol ) );
$col   = is_float( $col ) ? number_format( $col, 1 ) : $col;
$col   = str_replace( '.', '-', $col );

global $post;
$course_id   = $post->ID;
$course_id   = ! empty( $shortcode_atts['course_id'] ) ? $shortcode_atts['course_id'] : learndash_get_course_id( $post->ID );
$user_id     = get_current_user_id();
$course_type = get_post_type( $post->ID );

$cg_short_description = get_post_meta( $post->ID, '_learndash_course_grid_short_description', true );
$enable_video         = (bool) get_post_meta( $post->ID, '_learndash_course_grid_enable_video_preview', true );
$embed_code           = get_post_meta( $post->ID, '_learndash_course_grid_video_embed_code', true );
$button_text          = get_post_meta( $post->ID, '_learndash_course_grid_custom_button_text', true );
$ribbon_custom_text   = get_post_meta( $post->ID, '_learndash_course_grid_custom_ribbon_text', true );

// Retrieve oembed HTML if URL provided.
if ( preg_match( '/^http/', $embed_code ) ) {
	$embed_code = wp_oembed_get(
		$embed_code,
		array(
			'height' => 600,
			'width'  => 400,
		)
	);
}

if ( isset( $course_id ) ) {
	$button_link = learndash_get_step_permalink( get_the_ID(), $course_id );
} else {
	$button_link = get_permalink();
}

$button_link  = apply_filters( 'learndash_course_grid_custom_button_link', $button_link, $post->ID );
$button_text  = isset( $button_text ) && ! empty( $button_text ) ? $button_text : __( 'See more...', 'buddyboss-theme' );
$button_text  = apply_filters( 'learndash_course_grid_custom_button_text', $button_text, $post->ID );
$options      = get_option( 'sfwd_cpt_options' );
$currency     = '';
$ribbon_class = '';
$ribbon_text  = '';

$currency = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();

/**
 * Currency symbol filter hook
 *
 * @param string $currency Currency symbol
 * @param int    $course_id
 */
$currency = apply_filters( 'learndash_course_grid_currency', $currency, $post->ID );

$course_options = get_post_meta( $post->ID, '_sfwd-courses', true );

$course_price      = '';
$course_price_type = '';

// For LD >= 3.0.
if ( function_exists( 'learndash_get_course_price' ) && function_exists( 'learndash_get_group_price' ) ) {
	if ( 'sfwd-courses' === $post->post_type ) {
		$price_args = learndash_get_course_price( $post->ID );
	} elseif ( 'groups' === $post->post_type ) {
		$price_args = learndash_get_group_price( $post->ID );
	}

	if ( ! empty( $price_args ) ) {
		$course_price      = $price_args['price'];
		$course_price_type = $price_args['type'];

		if ( is_numeric( $course_price ) && ! empty( $course_price ) ) {
			$price_format = apply_filters( 'learndash_course_grid_price_text_format', '{currency}{price}' );
			$price_text   = str_replace( array( '{currency}', '{price}' ), array( $currency, $course_price ), $price_format );
		} elseif ( is_string( $course_price ) && ! empty( $course_price ) ) {
			$price_text = $course_price;
		} elseif ( empty( $course_price ) ) {
			$price_text = __( 'Free', 'buddyboss-theme' );
		}

		if ( 'subscribe' === $course_price_type ) {
			$trial_price = $price_args['trial_price'] ?? false;

			$trial_duration = isset( $price_args['trial_interval'] ) && isset( $price_args['trial_frequency'] ) ? $price_args['trial_interval'] . ' ' . $price_args['trial_frequency'] : false;

			if ( isset( $price_args['interval'] ) && isset( $price_args['frequency'] ) ) {
				$subscription_duration = $price_args['interval'] > 1 ? $price_args['interval'] . ' ' . $price_args['frequency'] : $price_args['frequency'];

				$price_text = sprintf( '%s%s', $price_text, $subscription_duration ? '/' . $subscription_duration : '' );
			}
		}
	}
} else {
	$course_price      = $course_options && isset( $course_options['sfwd-courses_course_price'] ) ? $course_options['sfwd-courses_course_price'] : __( 'Free', 'buddyboss-theme' );
	$course_price_type = $course_options && isset( $course_options['sfwd-courses_course_price_type'] ) ? $course_options['sfwd-courses_course_price_type'] : '';
}

$legacy_short_description = '';
if ( 'sfwd-courses' === $course_type ) {
	$legacy_short_description = isset( $course_options['sfwd-courses_course_short_description'] ) ? $course_options['sfwd-courses_course_short_description'] : '';
}

$short_description = '';
if ( ! empty( $cg_short_description ) ) {
	$short_description = $cg_short_description;
} elseif ( ! empty( $legacy_short_description ) ) {
	$short_description = $legacy_short_description;
} else {
	$short_description = wp_trim_words( get_the_excerpt( $post->ID ), 20 );
}

/**
 * Filter: individual grid class
 *
 * @param int   $post->ID       Post ID
 * @param array $course_options Course options
 *
 * @var string
 */
$grid_class = apply_filters( 'learndash_course_grid_class', '', $post->ID, $course_options );

$is_completed = false;
$has_access   = false;

if ( 'sfwd-courses' === $post->post_type ) {
	$has_access   = sfwd_lms_has_access( $post->ID, $user_id );
	$is_completed = learndash_course_completed( $user_id, $post->ID );
} elseif ( 'groups' === $post->post_type ) {
	$has_access   = learndash_is_user_in_group( $user_id, $post->ID );
	$is_completed = learndash_get_user_group_completed_timestamp( $post->ID, $user_id );
} elseif ( 'sfwd-lessons' === $post->post_type || 'sfwd-topic' === $post->post_type ) {
	$parent_course_id = learndash_get_course_id( $post->ID );
	$has_access   = is_user_logged_in() && ! empty( $parent_course_id ) ? sfwd_lms_has_access( $post->ID, $user_id ) : false;
	if ( 'sfwd-lessons' === $post->post_type ) {
		$is_completed = learndash_is_lesson_complete( $user_id, $post->ID, $parent_course_id );
	} elseif ( 'sfwd-topic' === $post->post_type ) {
		$is_completed = learndash_is_topic_complete( $user_id, $post->ID, $parent_course_id );
	}
}

if ( in_array( $post->post_type, array( 'sfwd-courses', 'groups' ), true ) ) {
	if ( 'open' !== $course_price_type ) {
		if ( $has_access && ! $is_completed ) {
			$ribbon_class .= ' ld-primary-background';
			$ribbon_text   = __( 'Enrolled', 'buddyboss-theme' );
		} elseif ( $has_access && $is_completed ) {
			$ribbon_class .= ' ld-status-complete ld-secondary-background';
			$ribbon_text   = __( 'Completed', 'buddyboss-theme' );
		} elseif ( is_numeric( $course_price ) ) {
			$ribbon_class .= ' ld-third-background';
			$ribbon_text   = $price_text;
		} elseif ( 'free' === $course_price_type ) {
			$ribbon_class .= ' free ld-third-background';
			$ribbon_text   = __( 'Free', 'buddyboss-theme' );
		} elseif ( ! $has_access ) {
			$ribbon_class .= ' ld-third-background';
			$ribbon_text   = __( 'Not Enrolled', 'buddyboss-theme' );
		} else {
			$ribbon_class .= ' ld-third-background';
			$ribbon_text   = __( 'Not Completed', 'buddyboss-theme' );
		}
	} elseif ( 'open' === $course_price_type ) {
		if ( is_user_logged_in() && ! $is_completed ) {
			$ribbon_class .= ' ld-primary-background';
			$ribbon_text   = __( 'Enrolled', 'buddyboss-theme' );
		} elseif ( is_user_logged_in() && $is_completed ) {
			$ribbon_class .= ' ld-status-complete ld-secondary-background';
			$ribbon_text   = __( 'Completed', 'buddyboss-theme' );
		} else {
			$ribbon_class .= ' free ld-secondary-background';
			$ribbon_text   = __( 'Free', 'buddyboss-theme' );
		}
	}
} elseif ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic' ), true ) ) {
	$has_started = false;

	if ( 'sfwd-lessons' === $post->post_type ) {
		$activity_type = 'lesson';
	} elseif ( 'sfwd-topic' === $post->post_type ) {
		$activity_type = 'topic';
	}

	$course_id = learndash_get_course_id( $post->ID );
	if ( ! empty( $user_id ) ) {
		$activity = learndash_get_user_activity(
			array(
				'course_id'     => $course_id,
				'user_id'       => $user_id,
				'post_id'       => $post->ID,
				'activity_type' => $activity_type,
			)
		);

		if ( ! empty( $activity ) ) {
			if ( ! empty( $activity->activity_started ) ) {
				$has_started = true;
			}
		}
	}
	if ( $has_access && $is_completed ) {
		$ribbon_class .= ' ld-status-complete ld-secondary-background';
		$ribbon_text   = __( 'Completed', 'buddyboss-theme' );
	} elseif ( $has_access && ! $has_started ) {
		$ribbon_class .= ' ld-primary-background';
		$ribbon_text   = __( 'Not Started', 'buddyboss-theme' );
	} elseif ( $has_access && $has_started ) {
		$ribbon_class .= ' ld-primary-background ld-status-progress';
		$ribbon_text   = __( 'In Progress', 'buddyboss-theme' );
	}
}
$ribbon_text = ! empty( $ribbon_custom_text ) ? $ribbon_custom_text : $ribbon_text;

/**
 * Display class if content is disabled
 */
$class_price_type = '';
if (
	! empty( $course_price ) &&
	(
		'paynow' === $course_price_type ||
		'subscribe' === $course_price_type ||
		'closed' === $course_price_type
	) &&
	( 'true' === $shortcode_atts['show_content'] )
) {
	$class_price_type = 'bb-course-paid';
}

/**
 * Display class if course has content disabled
 */
$class_content_type = '';
if ( 'true' !== $shortcode_atts['show_content'] ) {
	$class_content_type = 'bb-course-no-content';
}

/**
 * Filter: individual course ribbon text
 *
 * @param string $ribbon_text Returned ribbon text
 * @param int    $post->ID    Post ID
 * @param string $price_type  Course price type
 */
$ribbon_text = apply_filters( 'learndash_course_grid_ribbon_text', $ribbon_text, $post->ID, $course_price_type );

if ( '' === $ribbon_text ) {
	$ribbon_class = '';
}

/**
 * Filter: individual course ribbon class names
 *
 * @param string $class          Returned class names
 * @param int    $post->ID       Post ID
 * @param array  $course_options Course's options
 * @var string
 */
$ribbon_class = apply_filters( 'learndash_course_grid_ribbon_class', $ribbon_class, $post->ID, $course_options );

$thumb_size = isset( $shortcode_atts['thumb_size'] ) && ! empty( $shortcode_atts['thumb_size'] ) ? $shortcode_atts['thumb_size'] : 'course-thumb';

/**
 * Display class if content is disabled
 */
$class_price_type = '';
if (
	! empty( $course_price ) &&
	in_array( $course_price_type, array( 'closed', 'paynow', 'subscribe' ), true ) &&
	( 'true' === $shortcode_atts['show_content'] )
) {
	$class_price_type = 'bb-course-paid';
}

/**
 * Display class if course has content disabled
 */
$class_content_type = '';
if ( 'true' !== $shortcode_atts['show_content'] ) {
	$class_content_type = 'bb-course-no-content';
}

/**
 * Filter: individual course container class names
 *
 * @param string $course_class   Returned class names
 * @param int    $course_id      Course ID
 * @param array  $course_options Course's options
 *
 * @var string
 */
$class_content_type = apply_filters( 'learndash_course_grid_course_class', $class_content_type, $course_id, $course_options );

$course_pricing = learndash_get_course_price( get_the_ID() );

$types_array = array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment', 'sfwd-essays', 'sfwd-courses' );
?>
<div class="ld_course_grid col-sm-<?php echo esc_attr( $smcol ); ?> col-md-<?php echo esc_attr( $col ); ?> <?php echo esc_attr( $grid_class ); ?> bb-course-item-wrap">

	<div class="bb-cover-list-item <?php echo esc_attr( $class_price_type ); ?> <?php echo esc_attr( $class_content_type ); ?>">
		<?php if ( true === (bool) $shortcode_atts['show_thumbnail'] ) : ?>

				<div class="bb-course-cover <?php echo ( true === $enable_video && ! empty( $embed_code ) ) ? 'has-video-cover' : ''; ?>">
				<?php

					if ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses', 'groups', 'sfwd-quiz', 'sfwd-assignment' ), true ) ) {
						if ( isset( $ribbon_text ) && ! empty( $ribbon_text ) ) {
							echo '<div class="ld-status ' . esc_attr( $ribbon_class ) . '">' . esc_html( $ribbon_text ) . '</div>';
						}
					}

					if ( true === $enable_video && ! empty( $embed_code ) ) {
						?>
						<div class="ld_course_grid_video_embed">
							<?php echo $embed_code; ?>
						</div>
						<?php
					} else 	{
						?>
						<a title="<?php the_title_attribute(); ?>" href="<?php echo $button_link; ?>" class="bb-cover-wrap">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail();
							} elseif( defined( 'LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL' ) ) {
								?>
								<img alt="" src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/thumbnail.jpg'; ?>"/>
								<?php
							}
							?>
						</a>
						<?php
					}
				?>
				</div>

		<?php endif; ?>

		<?php if ( 'true' !== $shortcode_atts['show_content'] ) : ?>
			<style type="text/css">
				.bb-card-course-details {
					display: none !important;
				}
			</style>
		<?php endif; ?>

		<div class="bb-card-course-details">
			<?php
			if ( 'sfwd-courses' === $course_type || 'sfwd-lessons' === $course_type ) {

				if ( 'sfwd-courses' === get_post_type() ) {
					$lessons_arr = learndash_get_lesson_list( get_the_ID(), array( 'num' => - 1 ) );
					$lessons_arr = $lessons_arr ? $lessons_arr : array();
					$_count      = count( $lessons_arr );
					$p_lable     = 'lessons';
					$s_lable     = 'lesson';
				} elseif ( 'sfwd-lessons' === get_post_type() ) {
					$topic_arr = learndash_get_topic_list( get_the_ID(), $course_id );
					$topic_arr = $topic_arr ? $topic_arr : array();
					$_count    = count( $topic_arr );
					$p_lable   = 'topics';
					$s_lable   = 'topic';
				}

				$total_str = (
					$_count > 1 ?
					sprintf( __( '%1$s %2$s', 'buddyboss-theme' ), $_count, LearnDash_Custom_Label::get_label( $p_lable ) ) :
					sprintf( __( '%1$s %2$s ', 'buddyboss-theme' ), $_count, LearnDash_Custom_Label::get_label( $s_lable ) )
				);

				if ( $_count > 0 ) {
					echo '<div class="course-lesson-count">' . $total_str . '</div>';
				} else {
					echo '<div class="course-lesson-count">' . __( '0 ', 'buddyboss-theme' ) . sprintf( __( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( $p_lable ) ) . '</div>';
				}
			}
			?>
			<h2 class="bb-course-title">
				<a href="<?php echo $button_link; ?>"><?php echo esc_html( get_the_title() ); ?></a>
			</h2>
			<?php if ( ! empty( $short_description ) ) { ?>
				<p class="entry-content"><?php echo do_shortcode( htmlspecialchars_decode( $short_description, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ); ?></p>
				<?php
			}

			if ( buddyboss_theme_get_option( 'learndash_course_author' ) ) {
				SFWD_LMS::get_template( 'course_list_course_author', compact( 'post' ), true );
			}

			if (
				'sfwd-courses' === $post->post_type &&
				isset( $shortcode_atts['progress_bar'] ) &&
				true === (bool) $shortcode_atts['progress_bar']
			) {
				?>
				<div class="course-progress-wrap">
					<?php
					learndash_get_template_part(
						'modules/progress.php',
						array(
							'context'   => 'course',
							'user_id'   => $user_id,
							'course_id' => $post->ID,
						),
						true
					);
					?>
				</div>
				<?php
			}
			if ( isset( $button_text ) && ! empty( $button_text ) ) {
				?>
                <p class="ld_course_grid_button"><a class="btn btn-primary" role="button" href="<?php echo esc_url( $button_link ); ?>" rel="bookmark"><?php echo esc_attr( $button_text ); ?></a></p>
				<?php
			}

			// Price.
			if (
				in_array( $post->post_type, array( 'sfwd-courses', 'groups' ), true ) &&
				( ! empty( $course_price ) && ! $has_access ) &&
				in_array( $course_price_type, array( 'closed', 'paynow', 'subscribe' ), true )
			) {
				?>
				<div class="bb-course-footer bb-course-pay">
					<span class="course-fee">
						<?php echo $price_text; ?>
					</span>
				</div>
				<?php
			}
			?>

		</div><!-- .entry-header -->
	</div><!-- #post-## -->
</div>
<?php
